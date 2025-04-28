<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("User ID is required");
    }

    $stmt = mysqli_prepare($conn, "
        SELECT 
            r.*,
            v.type as vehicle_type
        FROM rides r
        JOIN vehicles v ON r.vehicle_id = v.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
        LIMIT 10
    ");

    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $rides = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rides[] = $row;
    }
    
    echo json_encode($rides);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
