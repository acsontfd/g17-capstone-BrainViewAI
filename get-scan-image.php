<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

// Get CT scan ID from query parameter
$ctScanId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ctScanId <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

try {
    require_once 'db-connection.php';
    $db = new Database();
    $sql = "SELECT image_data, image_name FROM ct_scans WHERE id = ? AND user_id = ?";
    $stmt = $db->conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->conn->error);
    }
    
    $stmt->bind_param("is", $ctScanId, $_SESSION['user_id']);
    
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
    
    $stmt->bind_result($imageData, $imageName);
    $stmt->fetch();
    
    // Determine MIME type based on file extension
    $extension = pathinfo($imageName, PATHINFO_EXTENSION);
    $mimeType = 'image/jpeg'; // Default MIME type
    
    if ($extension) {
        switch (strtolower($extension)) {
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $mimeType = 'image/jpeg';
                break;
            case 'gif':
                $mimeType = 'image/gif';
                break;
            case 'bmp':
                $mimeType = 'image/bmp';
                break;
        }
    }
    
    // OUTPUT the image with the appropriate MIME type
    header('Content-Type: ' . $mimeType);
    echo $imageData;
    
    $stmt->close();
    $db->closeConnection();
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo $e->getMessage();
}
?> 