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
    
    // Initialize conditions
    $conditions = ["(m.from_id = ? OR m.to_id = ?)"];
    $params = [$user_id, $user_id];
    $types = "ii";
    
    // Apply filters
    if (!empty($_GET['status'])) {
        $conditions[] = "m.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }
    
    if (!empty($_GET['type'])) {
        $conditions[] = "m.message_type = ?";
        $params[] = $_GET['type'];
        $types .= "s";
    }
    
    // Get messages
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
        WHERE " . implode(" AND ", $conditions) . "
        ORDER BY m.created_at DESC
    ";
    
    // Prepare and execute query
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    // Fetch messages
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format date
        $row['date_formatted'] = date('M d, Y h:i A', strtotime($row['created_at']));
        
        // Truncate content for preview
        $row['content_preview'] = substr($row['content'], 0, 100) . (strlen($row['content']) > 100 ? '...' : '');
        
        $messages[] = $row;
    }
    
    // Get unread count
    $unread_query = "
        SELECT COUNT(*) as unread_count
        FROM messages
        WHERE to_id = ? AND status = 'unread'
    ";
    
    $unread_stmt = mysqli_prepare($conn, $unread_query);
    if ($unread_stmt === false) {
        throw new Exception("Unread prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
    
    if (!mysqli_stmt_execute($unread_stmt)) {
        throw new Exception("Unread execute failed: " . mysqli_stmt_error($unread_stmt));
    }
    
    $unread_result = mysqli_stmt_get_result($unread_stmt);
    if ($unread_result === false) {
        throw new Exception("Unread get result failed: " . mysqli_error($conn));
    }
    
    $unread_row = mysqli_fetch_assoc($unread_result);
    $unread_count = $unread_row['unread_count'];
    
    // Prepare response
    $response = [
        'messages' => $messages,
        'unread_count' => (int)$unread_count
    ];
    
    // Return JSON response
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
