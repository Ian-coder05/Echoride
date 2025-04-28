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
    $required_fields = ['subject', 'content'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Get form data
    $subject = $_POST['subject'];
    $content = $_POST['content'];
    $message_type = !empty($_POST['message_type']) ? $_POST['message_type'] : 'inquiry';
    $reply_to_id = !empty($_POST['reply_to_id']) ? $_POST['reply_to_id'] : null;
    
    // If this is a reply, get the recipient from the original message
    if ($reply_to_id) {
        $recipient_id = !empty($_POST['recipient_id']) ? $_POST['recipient_id'] : null;
        
        if (!$recipient_id) {
            // Get recipient from original message
            $query = "
                SELECT from_id 
                FROM messages 
                WHERE id = ? AND to_id = ?
            ";
            
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($stmt, "ii", $reply_to_id, $user_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
            }
            
            $result = mysqli_stmt_get_result($stmt);
            if ($result === false) {
                throw new Exception("Get result failed: " . mysqli_error($conn));
            }
            
            if (mysqli_num_rows($result) === 0) {
                throw new Exception("Original message not found or you don't have permission to reply to it");
            }
            
            $row = mysqli_fetch_assoc($result);
            $recipient_id = $row['from_id'];
        }
    } else {
        // For new messages, send to admin by default
        // Get admin user ID
        $query = "SELECT id FROM users WHERE is_admin = 1 LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            throw new Exception("No admin user found to send the message to");
        }
        
        $row = mysqli_fetch_assoc($result);
        $recipient_id = $row['id'];
    }
    
    // Insert message
    $query = "
        INSERT INTO messages (from_id, to_id, subject, content, message_type, status, reply_to_id)
        VALUES (?, ?, ?, ?, ?, 'unread', ?)
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param(
        $stmt, 
        "iisssi", 
        $user_id, 
        $recipient_id, 
        $subject, 
        $content, 
        $message_type, 
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
    
    // Return JSON response
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in send_message.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
