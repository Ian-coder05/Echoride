<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    $stats = [
        'totalRides' => 0,
        'activeRides' => 0,
        'totalDistance' => 0,
        'carbonSaved' => 0
    ];

    // Get total rides
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM rides");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['totalRides'] = mysqli_fetch_assoc($result)['count'];

    // Get active rides
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM rides WHERE status = 'ongoing'");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['activeRides'] = mysqli_fetch_assoc($result)['count'];

    // Get total distance and carbon saved
    $stmt = mysqli_prepare($conn, "
        SELECT 
            SUM(distance) as total_distance,
            SUM(carbon_saved) as total_carbon_saved
        FROM rides
        WHERE status = 'completed'
    ");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $stats['totalDistance'] = floatval($row['total_distance'] ?: 0);
    $stats['carbonSaved'] = floatval($row['total_carbon_saved'] ?: 0);

    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
