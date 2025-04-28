<?php
require_once('../db.php');
require_once('check_admin.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $query = "
        SELECT 
            id,
            CONCAT(full_name, ' (', email, ')') as display_text
        FROM users
        ORDER BY full_name ASC
    ";

    $result = mysqli_query($conn, $query);
    if ($result === false) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    // Log the response
    error_log("Sending response with " . count($users) . " users");
    
    // Return JSON response
    echo json_encode($users);

} catch (Exception $e) {
    error_log("Error in get_users_for_messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
