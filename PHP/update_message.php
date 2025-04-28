<?php
session_start();
require_once('db.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not logged in");
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($_POST['id'])) {
        throw new Exception("Message ID is required");
    }
    
    if (empty($_POST['action'])) {
        throw new Exception("Action is required");
    }
    
    $message_id = $_POST['id'];
    $action = $_POST['action'];
    
    // Verify the message belongs to the user
    $check_query = "
        SELECT id FROM messages 
        WHERE id = ? AND to_id = ?
    ";
    
    $check_stmt = mysqli_prepare($conn, $check_query);
    if ($check_stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "ii", $message_id, $user_id);
    
    if (!mysqli_stmt_execute($check_stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($check_stmt));
    }
    
    $check_result = mysqli_stmt_get_result($check_stmt);
    if ($check_result === false) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($check_result) === 0) {
        throw new Exception("Message not found or you don't have permission to update it");
    }
    
    // Perform action based on type
    switch ($action) {
        case 'mark_read':
            $query = "UPDATE messages SET status = 'read' WHERE id = ? AND to_id = ?";
            $params = [$message_id, $user_id];
            $types = "ii";
            $success_message = "Message marked as read";
            break;
            
        default:
            throw new Exception("Invalid action: $action");
    }
    
    // Execute query
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    // Check if any rows were affected
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    if ($affected_rows === 0) {
        throw new Exception("No message found with ID: $message_id");
    }
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => $success_message
    ];
    
    // Return JSON response
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in update_message.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
