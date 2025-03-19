<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['image']) || !isset($data['ctScanId']) || !isset($data['patientId'])) {
        throw new Exception('Missing required data (image, ctScanId, or patientId)');
    }

    $flaskUrl = 'http://127.0.0.1:5000/predict';
    $postData = ['image_base64' => $data['image']];

    $ch = curl_init($flaskUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $flaskResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Flask API returned HTTP ' . $httpCode . ': ' . $flaskResponse);
    }

    $respData = json_decode($flaskResponse, true);
    if (!$respData['success']) {
        throw new Exception('Flask error: ' . ($respData['error'] ?? 'Unknown error'));
    }

    // Store analysis results in the database
    require_once 'db-connection.php';
    $db = new Database();
    
    // Get the base64 image data without the data URI prefix
    $contourImage = preg_replace('/^data:image\/\w+;base64,/', '', $respData['contour_image']);
    $edgeImage = preg_replace('/^data:image\/\w+;base64,/', '', $respData['edge_image']);
    $thresholdMaskImage = preg_replace('/^data:image\/\w+;base64,/', '', $respData['threshold_mask_image']);
    $damageOverlayImage = preg_replace('/^data:image\/\w+;base64,/', '', $respData['damage_overlay_image']);
    
    // Decode the base64 data
    $contourImageData = base64_decode($contourImage);
    $edgeImageData = base64_decode($edgeImage);
    $thresholdMaskImageData = base64_decode($thresholdMaskImage);
    $damageOverlayImageData = base64_decode($damageOverlayImage);
    
    // Insert the analysis results
    $stmt = $db->conn->prepare("INSERT INTO analysis_results (ct_scan_id, patient_id, classification, confidence, accuracy, contour_image, edge_image, threshold_mask_image, damage_overlay_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->conn->error);
    }
    
    $ctScanId = $data['ctScanId'];
    $patientId = $data['patientId'];
    $classification = $respData['analysis'];
    $confidence = $respData['confidence'];
    $accuracy = $respData['accuracy'];
    
    $null1 = NULL; // placeholder for bind_param for contour image
    $null2 = NULL; // placeholder for bind_param for edge image
    $null3 = NULL; // placeholder for bind_param for threshold mask image
    $null4 = NULL; // placeholder for bind_param for damage overlay image
    
    $stmt->bind_param("issddbbbb", $ctScanId, $patientId, $classification, $confidence, $accuracy, $null1, $null2, $null3, $null4);
    $stmt->send_long_data(5, $contourImageData);
    $stmt->send_long_data(6, $edgeImageData);
    $stmt->send_long_data(7, $thresholdMaskImageData);
    $stmt->send_long_data(8, $damageOverlayImageData);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $analysisId = $db->conn->insert_id;
    $stmt->close();
    $db->closeConnection();

    // Return results including accuracy and segmentation images
    echo json_encode([
        'success'       => true,
        'analysis_id'   => $analysisId,
        'analysis'      => $respData['analysis'],
        'confidence'    => $respData['confidence'],
        'accuracy'      => $respData['accuracy'],
        'contour_image' => $respData['contour_image'],
        'edge_image'    => $respData['edge_image'],
        'threshold_mask_image' => $respData['threshold_mask_image'],
        'damage_overlay_image' => $respData['damage_overlay_image']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
?>