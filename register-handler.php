<?php
header('Content-Type: application/json');


error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit();
}

require_once 'db-connection.php';

// Function to send JSON response and exit
function sendResponse($success, $message = '', $error = '') {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'error' => $error
    ]);
    exit();
}

try {
    // Get and sanitize inputs
    $userId = filter_input(INPUT_POST, 'userId', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validation
    if (empty($userId)) {
        sendResponse(false, '', 'User ID is required');
    }

    if (empty($password)) {
        sendResponse(false, '', 'Password is required');
    }

    if ($password !== $confirmPassword) {
        sendResponse(false, '', 'Passwords do not match');
    }

    // Password strength validation
    if (strlen($password) < 8) {
        sendResponse(false, '', 'Password must be at least 8 characters long');
    }

    // Database connection
    $db = new Database();
    $conn = $db->conn;

    // Check if user already exists
    $checkUser = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $checkUser->bind_param("s", $userId);
    $checkUser->execute();
    $result = $checkUser->get_result();

    if ($result->num_rows > 0) {
        sendResponse(false, '', 'User ID already exists');
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO users (user_id, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $userId, $hashedPassword);

    // Execute the statement
    if ($stmt->execute()) {
        sendResponse(true, 'Account created successfully');
    } else {
        sendResponse(false, '', 'Error creating account: ' . $conn->error);
    }
} catch (Exception $e) {
    // Log the full error for server-side debugging
    error_log($e->getMessage());
    sendResponse(false, '', 'An unexpected error occurred');
} finally {
    // Ensure database connections are closed
    if (isset($stmt)) $stmt->close();
    if (isset($checkUser)) $checkUser->close();
    if (isset($db)) $db->closeConnection();
}
?>