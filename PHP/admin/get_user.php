<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("User ID is required");
    }

    $stmt = mysqli_prepare($conn, "
        SELECT id, full_name, email, is_admin
        FROM users
        WHERE id = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        echo json_encode($user);
    } else {
        throw new Exception("User not found");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
