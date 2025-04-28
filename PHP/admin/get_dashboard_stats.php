<?php
require_once('../db.php');
require_once('check_admin.php');

// Initialize stats array
$stats = [
    'totalVehicles' => 0,
    'activeRides' => 0,
    'totalUsers' => 0,
    'carbonSaved' => 0
];

// Get total vehicles
$query = "SELECT COUNT(*) AS count FROM vehicles";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['totalVehicles'] = (int)$row['count'];
    mysqli_free_result($result);
}

// Get active rides
$query = "SELECT COUNT(*) AS count FROM rides WHERE status = 'ongoing'";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['activeRides'] = (int)$row['count'];
    mysqli_free_result($result);
}

// Get total users (excluding current admin)
$query = "SELECT COUNT(*) AS count FROM users WHERE id != " . $_SESSION['user_id'];
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['totalUsers'] = (int)$row['count'];
    mysqli_free_result($result);
}

// Get total carbon saved
$query = "SELECT SUM(carbon_saved) AS total FROM rides WHERE status = 'completed'";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['carbonSaved'] = $row['total'] ? (float)$row['total'] : 0;
    mysqli_free_result($result);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($stats);
?>
