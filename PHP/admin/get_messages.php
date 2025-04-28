<?php
require_once('../db.php');
require_once('check_admin.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Initialize conditions
    $conditions = [];
    $params = [];
    $types = "";
    
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
    
    if (!empty($_GET['search'])) {
        $conditions[] = "(m.subject LIKE ? OR m.content LIKE ?)";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
        $types .= "ss";
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
    ";
    
    // Add conditions if any
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Add order by
    $query .= " ORDER BY m.created_at DESC";
    
    // Prepare and execute query
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
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
        $messages[] = $row;
    }
    
    // Get message statistics
    $stats_query = "
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
            SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread_count,
            SUM(CASE WHEN is_flagged = 1 THEN 1 ELSE 0 END) as flagged_count
        FROM messages
    ";
    
    $stats_result = mysqli_query($conn, $stats_query);
    if ($stats_result === false) {
        throw new Exception("Stats query failed: " . mysqli_error($conn));
    }
    
    $stats_row = mysqli_fetch_assoc($stats_result);
    $stats = [
        'total' => (int)$stats_row['total'],
        'read' => (int)$stats_row['read_count'],
        'unread' => (int)$stats_row['unread_count'],
        'flagged' => (int)$stats_row['flagged_count']
    ];
    
    // Prepare response
    $response = [
        'messages' => $messages,
        'stats' => $stats
    ];
    
    // Log the response
    error_log("Sending response with " . count($messages) . " messages");
    
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
