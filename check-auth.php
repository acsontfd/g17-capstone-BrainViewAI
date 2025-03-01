<?php
session_start();
header('Content-Type: application/json');

// Check if the user is authenticated
if (!isset($_SESSION['user_id'])) {
    // If not authenticated, return a response indicating unauthenticated status
    echo json_encode(['authenticated' => false]);
    exit();
}

require_once 'db-connection.php';
$db = new Database();

if (!$db->conn) {
    // If database connection fails, return error response
    echo json_encode([
        'authenticated' => false,
        'error' => 'Database connection failed'
    ]);
    exit();
}

try {
    // Prepare a statement to check if the user exists in the database
    $stmt = $db->conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $db->conn->error);
    }

    // Bind session user ID to the query
    $stmt->bind_param("s", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Fetch the user details
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Return authenticated status and user data
        echo json_encode([
            'authenticated' => true,
            'user_id' => $user['user_id']
        ]);
    } else {
        // If user not found in the database, return unauthenticated
        echo json_encode(['authenticated' => false]);
    }

    $stmt->close();
} catch (Exception $e) {
    // Log the error and return a database error response
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'authenticated' => false,
        'error' => 'Database error occurred'
    ]);
} finally {
    // Close the database connection
    $db->closeConnection();
}
?>