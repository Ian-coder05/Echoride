<?php
require_once('../db.php');
require_once('check_admin.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Validate required fields
    if (empty($_POST['id'])) {
        throw new Exception("Message ID is required");
    }
    
    if (empty($_POST['action'])) {
        throw new Exception("Action is required");
    }
    
    $message_id = $_POST['id'];
    $action = $_POST['action'];
    
    // Perform action based on type
    switch ($action) {
        case 'mark_read':
            $query = "UPDATE messages SET status = 'read' WHERE id = ?";
            $params = [$message_id];
            $types = "i";
            $success_message = "Message marked as read";
            break;
            
        case 'toggle_flag':
            if (!isset($_POST['flag'])) {
                throw new Exception("Flag value is required for toggle_flag action");
            }
            $flag = (int)$_POST['flag'];
            $query = "UPDATE messages SET is_flagged = ? WHERE id = ?";
            $params = [$flag, $message_id];
            $types = "ii";
            $success_message = $flag ? "Message flagged" : "Message unflagged";
            break;
            
        case 'delete':
            $query = "DELETE FROM messages WHERE id = ?";
            $params = [$message_id];
            $types = "i";
            $success_message = "Message deleted";
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
    
    // Log the response
    error_log("Message updated successfully: $success_message");
    
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
