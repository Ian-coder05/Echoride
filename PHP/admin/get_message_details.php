<?php
require_once('../db.php');
require_once('check_admin.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if message ID is provided
    if (empty($_GET['id'])) {
        throw new Exception("Message ID is required");
    }
    
    $message_id = $_GET['id'];
    
    // Get message details
    $query = "
        SELECT 
            m.id,
            m.from_id,
            m.to_id,
            m.subject,
            m.content,
            m.message_type,
            m.status,
            m.is_flagged,
            m.reply_to_id,
            m.created_at,
            u_from.full_name as from_name,
            u_to.full_name as to_name
        FROM messages m
        JOIN users u_from ON m.from_id = u_from.id
        JOIN users u_to ON m.to_id = u_to.id
        WHERE m.id = ?
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception("Message not found");
    }
    
    $message = mysqli_fetch_assoc($result);
    
    // Format date
    $message['date_formatted'] = date('M d, Y h:i A', strtotime($message['created_at']));
    
    // Get thread messages if this is a reply or has replies
    $thread = [];
    if ($message['reply_to_id'] !== null) {
        // Get parent message and any siblings
        $thread_query = "
            SELECT 
                m.id,
                m.from_id,
                m.to_id,
                m.subject,
                m.content,
                m.message_type,
                m.status,
                m.is_flagged,
                m.reply_to_id,
                m.created_at,
                u_from.full_name as from_name,
                u_to.full_name as to_name
            FROM messages m
            JOIN users u_from ON m.from_id = u_from.id
            JOIN users u_to ON m.to_id = u_to.id
            WHERE m.id = ? OR m.reply_to_id = ?
            ORDER BY m.created_at ASC
        ";
        
        $thread_stmt = mysqli_prepare($conn, $thread_query);
        if ($thread_stmt === false) {
            throw new Exception("Thread prepare failed: " . mysqli_error($conn));
        }
        
        $parent_id = $message['reply_to_id'];
        mysqli_stmt_bind_param($thread_stmt, "ii", $parent_id, $parent_id);
        
        if (!mysqli_stmt_execute($thread_stmt)) {
            throw new Exception("Thread execute failed: " . mysqli_stmt_error($thread_stmt));
        }
        
        $thread_result = mysqli_stmt_get_result($thread_stmt);
        if ($thread_result === false) {
            throw new Exception("Thread get result failed: " . mysqli_error($conn));
        }
        
        while ($thread_row = mysqli_fetch_assoc($thread_result)) {
            $thread_row['date_formatted'] = date('M d, Y h:i A', strtotime($thread_row['created_at']));
            $thread[] = $thread_row;
        }
    } else {
        // Get replies to this message
        $replies_query = "
            SELECT 
                m.id,
                m.from_id,
                m.to_id,
                m.subject,
                m.content,
                m.message_type,
                m.status,
                m.is_flagged,
                m.reply_to_id,
                m.created_at,
                u_from.full_name as from_name,
                u_to.full_name as to_name
            FROM messages m
            JOIN users u_from ON m.from_id = u_from.id
            JOIN users u_to ON m.to_id = u_to.id
            WHERE m.reply_to_id = ?
            ORDER BY m.created_at ASC
        ";
        
        $replies_stmt = mysqli_prepare($conn, $replies_query);
        if ($replies_stmt === false) {
            throw new Exception("Replies prepare failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($replies_stmt, "i", $message_id);
        
        if (!mysqli_stmt_execute($replies_stmt)) {
            throw new Exception("Replies execute failed: " . mysqli_stmt_error($replies_stmt));
        }
        
        $replies_result = mysqli_stmt_get_result($replies_stmt);
        if ($replies_result === false) {
            throw new Exception("Replies get result failed: " . mysqli_error($conn));
        }
        
        while ($reply_row = mysqli_fetch_assoc($replies_result)) {
            $reply_row['date_formatted'] = date('M d, Y h:i A', strtotime($reply_row['created_at']));
            $thread[] = $reply_row;
        }
    }
    
    // Prepare response
    $response = [
        'message' => $message,
        'thread' => $thread
    ];
    
    // Log the response
    error_log("Sending response with message details and " . count($thread) . " thread messages");
    
    // Return JSON response
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_message_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
