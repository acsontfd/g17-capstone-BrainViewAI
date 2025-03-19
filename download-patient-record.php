<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false, 
        'error' => 'Not authenticated', 
        'error_code' => 'AUTH_REQUIRED'
    ]);
    exit;
}

// Get analysis ID from query parameter
$analysisId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($analysisId <= 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid or missing analysis ID', 
        'error_code' => 'INVALID_PARAM'
    ]);
    exit;
}

try {
    require_once 'db-connection.php';
    $db = new Database();
    
    // Get patient record data
    $sql = "SELECT 
                a.id as analysis_id,
                a.ct_scan_id,
                a.patient_id,
                a.classification,
                a.confidence,
                a.accuracy,
                a.analysis_date,
                c.image_name,
                c.upload_date
            FROM 
                analysis_results a
            JOIN 
                ct_scans c ON a.ct_scan_id = c.id
            WHERE 
                a.id = ? AND c.user_id = ?";
    
    $stmt = $db->conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->conn->error);
    }
    
    $stmt->bind_param("is", $analysisId, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode([
            'success' => false, 
            'error' => 'Record not found or access denied', 
            'error_code' => 'ACCESS_DENIED'
        ]);
        exit;
    }
    
    $record = $result->fetch_assoc();
    $stmt->close();
    
    // Format record for download
    $record['analysis_date_formatted'] = date('M d, Y H:i', strtotime($record['analysis_date']));
    $record['upload_date_formatted'] = date('M d, Y H:i', strtotime($record['upload_date']));
    
    // Create image URLs
    $imageUrl = "get-scan-image.php?id=" . $record['ct_scan_id'];
    $contourUrl = "get-analysis-image.php?id=" . $record['analysis_id'] . "&type=contour";
    $edgeUrl = "get-analysis-image.php?id=" . $record['analysis_id'] . "&type=edge";
    $thresholdMaskUrl = "get-analysis-image.php?id=" . $record['analysis_id'] . "&type=threshold_mask";
    $damageOverlayUrl = "get-analysis-image.php?id=" . $record['analysis_id'] . "&type=damage_overlay";
    
    // Get absolute URLs based on server name
    $serverUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    // Create download filename
    $filename = 'Patient_' . $record['patient_id'] . '_Record_' . $record['analysis_id'] . '.html';
    
    // Set headers for HTML file download
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Generate HTML report
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient ' . htmlspecialchars($record['patient_id']) . ' Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .report-header h1 {
            color: #008cff;
            margin-bottom: 5px;
        }
        .patient-info {
            margin-bottom: 30px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .info-table th {
            text-align: left;
            background-color: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            width: 30%;
        }
        .info-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .analysis-result {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
        }
        .positive {
            background-color: #ffecec;
            color: #d32f2f;
        }
        .negative {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        .images-section {
            margin-bottom: 30px;
        }
        .images-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .image-container {
            text-align: center;
        }
        .image-container h3 {
            margin-bottom: 10px;
            color: #008cff;
        }
        .image-container img {
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 5px;
            height: auto;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>BrainView AI - Patient Record</h1>
        <p>Comprehensive brain CT scan analysis report</p>
    </div>
    
    <div class="patient-info">
        <h2>Patient Information</h2>
        <table class="info-table">
            <tr>
                <th>Patient ID</th>
                <td>' . htmlspecialchars($record['patient_id']) . '</td>
            </tr>
            <tr>
                <th>Analysis Date</th>
                <td>' . htmlspecialchars($record['analysis_date_formatted']) . '</td>
            </tr>
            <tr>
                <th>CT Scan Name</th>
                <td>' . htmlspecialchars($record['image_name']) . '</td>
            </tr>
            <tr>
                <th>Upload Date</th>
                <td>' . htmlspecialchars($record['upload_date_formatted']) . '</td>
            </tr>
        </table>
    </div>
    
    <div class="analysis-results">
        <h2>Analysis Results</h2>
        <div class="analysis-result ' . (strpos(strtolower($record['classification']), 'hemorrhage detected') !== false ? 'positive' : 'negative') . '">
            ' . htmlspecialchars($record['classification']) . '
        </div>
        
        <table class="info-table">
            <tr>
                <th>Confidence</th>
                <td>' . htmlspecialchars($record['confidence']) . '%</td>
            </tr>
            <tr>
                <th>Accuracy</th>
                <td>' . htmlspecialchars($record['accuracy']) . '%</td>
            </tr>
            <tr>
                <th>Analysis ID</th>
                <td>' . htmlspecialchars($record['analysis_id']) . '</td>
            </tr>
        </table>
    </div>
    
    <div class="images-section">
        <h2>CT Scan Analysis Images</h2>
        <p>Note: The images below are embedded as links. To view the images, you will need to be logged into the BrainView AI system.</p>
        
        <div class="images-grid">
            <div class="image-container">
                <h3>Original CT Scan</h3>
                <img src="' . $serverUrl . '/' . $imageUrl . '" alt="Original CT Scan">
            </div>
            <div class="image-container">
                <h3>Contour Segmentation</h3>
                <img src="' . $serverUrl . '/' . $contourUrl . '" alt="Contour Segmentation">
            </div>
            <div class="image-container">
                <h3>Edge Detection</h3>
                <img src="' . $serverUrl . '/' . $edgeUrl . '" alt="Edge Detection">
            </div>
            <div class="image-container">
                <h3>Threshold Mask</h3>
                <img src="' . $serverUrl . '/' . $thresholdMaskUrl . '" alt="Threshold Mask">
            </div>
            <div class="image-container">
                <h3>Damage Area Overlay</h3>
                <img src="' . $serverUrl . '/' . $damageOverlayUrl . '" alt="Damage Area Overlay">
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Generated by BrainView AI on ' . date('Y-m-d H:i:s') . '</p>
        <p>© 2024 BrainView AI. All Rights Reserved</p>
    </div>
</body>
</html>';
    
    // Output HTML
    echo $html;
    
    $db->closeConnection();
    exit;
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'DB_ERROR'
    ]);
}
?> 