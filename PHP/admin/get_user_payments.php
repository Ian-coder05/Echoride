<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("User ID is required");
    }

    $stmt = mysqli_prepare($conn, "
        SELECT *
        FROM payments
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");

    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $payments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[] = $row;
    }
    
    echo json_encode($payments);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
