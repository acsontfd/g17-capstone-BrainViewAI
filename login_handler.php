<?php
session_start();
require_once 'db-connection.php';

error_log("POST data: " . print_r($_POST, true));

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_POST['userId'];
    $password = $_POST['password'];

    $response = [];

    // Validate inputs
    if (empty($userId) || empty($password)) {
        $response['error'] = 'User ID and password are required';
        echo json_encode($response);
        exit();
    }

    $db = new Database();
    $conn = $db->conn;

    // Prepare statement
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Store user info in session
            $_SESSION['user_id'] = $userId;
            $_SESSION['logged_in'] = true;

            $response['success'] = true;
            $response['message'] = 'Login successful';
        } else {
            $response['error'] = 'Invalid credentials';
        }
    } else {
        $response['error'] = 'User not found';
    }

    // Close statement and connection
    $stmt->close();
    $db->closeConnection();

    // Send JSON response
    echo json_encode($response);
    exit();
}
?>
