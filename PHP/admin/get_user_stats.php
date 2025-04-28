<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    $stats = [
        'totalUsers' => 0,
        'activeUsers' => 0,
        'totalRides' => 0,
        'totalCarbonSaved' => 0
    ];

    // Get total users (excluding current admin)
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM users WHERE id != ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['totalUsers'] = mysqli_fetch_assoc($result)['count'];

    // Get active users (users with rides in the last 30 days)
    $stmt = mysqli_prepare($conn, "
        SELECT COUNT(DISTINCT user_id) as count 
        FROM rides 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['activeUsers'] = mysqli_fetch_assoc($result)['count'];

    // Get total rides
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM rides");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['totalRides'] = mysqli_fetch_assoc($result)['count'];

    // Get total carbon saved
    $stmt = mysqli_prepare($conn, "SELECT SUM(carbon_saved) as total FROM rides");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['totalCarbonSaved'] = floatval(mysqli_fetch_assoc($result)['total'] ?: 0);

    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
