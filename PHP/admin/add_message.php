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
    $required_fields = ['recipient_id', 'subject', 'content'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Get current admin user ID
    $from_id = $_SESSION['user_id'];
    $to_id = $_POST['recipient_id'];
    $subject = $_POST['subject'];
    $content = $_POST['content'];
    $message_type = !empty($_POST['message_type']) ? $_POST['message_type'] : 'notification';
    $is_flagged = !empty($_POST['is_flagged']) ? 1 : 0;
    $reply_to_id = !empty($_POST['reply_to_id']) ? $_POST['reply_to_id'] : null;
    
    // Insert message
    $query = "
        INSERT INTO messages (from_id, to_id, subject, content, message_type, status, is_flagged, reply_to_id)
        VALUES (?, ?, ?, ?, ?, 'unread', ?, ?)
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param(
        $stmt, 
        "iisssii", 
        $from_id, 
        $to_id, 
        $subject, 
        $content, 
        $message_type, 
        $is_flagged, 
        $reply_to_id
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $message_id = mysqli_insert_id($conn);
    
    // Prepare success response
    $response = [
        'success' => true,
        'id' => $message_id,
        'message' => 'Message sent successfully'
    ];
    
    // Log the response
    error_log("Message added successfully with ID: $message_id");
    
    // Return JSON response
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in add_message.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
