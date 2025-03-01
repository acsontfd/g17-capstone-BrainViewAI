<?php
session_start();
require_once 'db-connection.php';

if (!isset($_GET['id'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$db = new Database();

try {
    $stmt = $db->conn->prepare("SELECT image_data, image_name FROM ct_scans WHERE id = ? AND user_id = ?");
    $stmt->bind_param("is", $_GET['id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        header("Content-Type: image/jpeg"); // Adjust content type based on image type
        header("Content-Disposition: inline; filename=\"" . $row['image_name'] . "\"");
        echo $row['image_data'];
    } else {
        header("HTTP/1.0 404 Not Found");
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Image retrieval error: " . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
} finally {
    $db->closeConnection();
}
?> 