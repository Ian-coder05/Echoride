<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Ride ID is required");
    }

    $stmt = mysqli_prepare($conn, "
        SELECT 
            r.*,
            u.full_name as user_name,
            u.email as user_email,
            v.type as vehicle_type,
            v.model as vehicle_model,
            v.battery_level,
            (SELECT COUNT(*) FROM rides WHERE user_id = r.user_id) as user_total_rides,
            TIMESTAMPDIFF(MINUTE, r.start_time, COALESCE(r.end_time, NOW())) as duration
        FROM rides r
        JOIN users u ON r.user_id = u.id
        JOIN vehicles v ON r.vehicle_id = v.id
        WHERE r.id = ?
    ");

    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($ride = mysqli_fetch_assoc($result)) {
        echo json_encode($ride);
    } else {
        throw new Exception("Ride not found");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
