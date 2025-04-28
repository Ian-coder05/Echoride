<?php
require_once('../db.php');
require_once('check_admin.php');

header('Content-Type: application/json');

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

    echo json_encode($users);

} catch (Exception $e) {
    error_log("Error in get_users_for_payments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
