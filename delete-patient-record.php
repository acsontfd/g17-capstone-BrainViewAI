<?php
/**
* The - analysis_id - parameter AS THE INDEX and make sure that the user only deletes records they have access to
 */
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Authentication process stricted to only authenticated users
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Not authenticated', 
        'error_code' => 'AUTH_REQUIRED'
    ]);
    exit;
}

// Check if analysis_id is provided
if (!isset($_POST['analysis_id']) || !is_numeric($_POST['analysis_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid or missing analysis ID',
        'error_code' => 'INVALID_PARAM'
    ]);
    exit;
}

$analysis_id = intval($_POST['analysis_id']);
$user_id = $_SESSION['user_id'];

try {
    require_once 'db-connection.php';
    $db = new Database();

    $db->conn->begin_transaction();

    $stmt = $db->conn->prepare("
        SELECT a.id, a.ct_scan_id, a.patient_id
        FROM analysis_results a
        JOIN ct_scans c ON a.ct_scan_id = c.id
        WHERE a.id = ? AND c.user_id = ?
    ");
    $stmt->bind_param("is", $analysis_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if record exists and belongs to the user
    if ($result->num_rows === 0) {
        $db->conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'Record not found or access denied',
            'error_code' => 'ACCESS_DENIED'
        ]);
        exit;
    }
    
    $record = $result->fetch_assoc();
    $stmt->close();
    
    // DELETE operations
    $stmt = $db->conn->prepare("DELETE FROM analysis_results WHERE id = ?");
    $stmt->bind_param("i", $analysis_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    if ($affected === 0) {
        $db->conn->rollback();
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete record',
            'error_code' => 'DELETE_FAILED'
        ]);
        exit;
    }

    $db->conn->commit();
    $db->closeConnection();
    echo json_encode([
        'success' => true,
        'message' => 'Patient record successfully deleted',
        'deleted_id' => $analysis_id,
        'patient_id' => $record['patient_id']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->conn) {
        $db->conn->rollback();
        $db->closeConnection();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'DB_ERROR'
    ]);
}
?> 