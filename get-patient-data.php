<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Not authenticated', 
        'error_code' => 'AUTH_REQUIRED',
        'debug' => 'No user_id found in session'
    ]);
    exit;
}

try {
    require_once 'db-connection.php';
    $db = new Database();
    $patientId = isset($_GET['patientId']) ? $_GET['patientId'] : null;
    $debugInfo = [
        'user_id' => $_SESSION['user_id'],
        'patient_id_filter' => $patientId
    ];
    //SQL START JOIN QUERY
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
                c.user_id = ?";
    // Serarch bar filter here if exists
    $params = [$_SESSION['user_id']];
    
    if ($patientId) {
        $sql .= " AND a.patient_id = ?";
        $params[] = $patientId;
    }

    $sql .= " ORDER BY a.analysis_date DESC";
    
    $debugInfo['sql'] = $sql;
    $debugInfo['params'] = $params;
    
    $stmt = $db->conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->conn->error);
    }

    if (count($params) == 1) {
        $stmt->bind_param("s", $params[0]);
    } else {
        $stmt->bind_param("ss", $params[0], $params[1]);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $analyses = [];
    
    while ($row = $result->fetch_assoc()) {

        $row['analysis_date'] = date('M d, Y H:i', strtotime($row['analysis_date']));
        $row['upload_date'] = date('M d, Y H:i', strtotime($row['upload_date']));
        $row['image_url'] = "get-scan-image.php?id=" . $row['ct_scan_id'];
        $row['contour_url'] = "get-analysis-image.php?id=" . $row['analysis_id'] . "&type=contour";
        $row['edge_url'] = "get-analysis-image.php?id=" . $row['analysis_id'] . "&type=edge";
        $row['threshold_mask_url'] = "get-analysis-image.php?id=" . $row['analysis_id'] . "&type=threshold_mask";
        $row['damage_overlay_url'] = "get-analysis-image.php?id=" . $row['analysis_id'] . "&type=damage_overlay";
        
        $analyses[] = $row;
    }
    
    $stmt->close();
    $db->closeConnection();
    
    $debugInfo['found_records'] = count($analyses);
    
    echo json_encode([
        'success' => true,
        'count' => count($analyses),
        'data' => $analyses,
        'debug_info' => $debugInfo
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'DB_ERROR',
        'debug_info' => [
            'exception_trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?> 