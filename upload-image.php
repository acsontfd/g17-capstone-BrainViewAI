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

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'error'   => 'File upload error: ' . ($_FILES['image']['error'] ?? 'No file uploaded')
    ]);
    exit;
}

require_once 'db-connection.php';
$db = new Database();

try {
    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed.');
    }

    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }

    $imageData = file_get_contents($file['tmp_name']);
    $imageName = $file['name'];
    $userId    = $_SESSION['user_id'];

    // Insert into database
    $stmt = $db->conn->prepare("INSERT INTO ct_scans (user_id, image_data, image_name) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->conn->error);
    }

    $null = NULL; // placeholder for bind_param
    $stmt->bind_param("sbs", $userId, $null, $imageName);
    $stmt->send_long_data(1, $imageData);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $ctScanId = $db->conn->insert_id;
    
    echo json_encode([
        'success'  => true,
        'message'  => 'Image uploaded successfully',
        'image_id' => $ctScanId
    ]);

    $stmt->close();

} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
} finally {
    $db->closeConnection();
}
?>