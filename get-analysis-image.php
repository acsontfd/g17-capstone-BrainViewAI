<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

// Get ID and TYPE
$analysisId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$imageType = isset($_GET['type']) ? $_GET['type'] : '';

if ($analysisId <= 0 || !in_array($imageType, ['contour', 'edge', 'threshold_mask', 'damage_overlay'])) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

try {
    require_once 'db-connection.php';
    $db = new Database();

    $imageColumn = '';
    switch ($imageType) {
        case 'contour':
            $imageColumn = 'contour_image';
            break;
        case 'edge':
            $imageColumn = 'edge_image';
            break;
        case 'threshold_mask':
            $imageColumn = 'threshold_mask_image';
            break;
        case 'damage_overlay':
            $imageColumn = 'damage_overlay_image';
            break;
    }

    $sql = "SELECT ar.$imageColumn 
            FROM analysis_results ar
            JOIN ct_scans cs ON ar.ct_scan_id = cs.id
            WHERE ar.id = ? AND cs.user_id = ?";
            
    $stmt = $db->conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->conn->error);
    }
    
    $stmt->bind_param("is", $analysisId, $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->store_result();
    
    if ($stmt->num_rows == 0) {
        header('HTTP/1.1 404 Not Found');
        $stmt->close();
        $db->closeConnection();
        exit;
    }
    
    $imageData = null;
    $stmt->bind_result($imageData);
    $stmt->fetch();

    header('Content-Type: image/png');
    echo $imageData;
    
    $stmt->close();
    $db->closeConnection();
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo $e->getMessage();
}
?> 