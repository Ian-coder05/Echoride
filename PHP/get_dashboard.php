<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT id, pickup, dropoff, ride_time, end_time, status FROM rides WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$rides = [];

while ($row = $result->fetch_assoc()) {
    $start = strtotime($row['ride_time']);
    $end = $row['end_time'] ? strtotime($row['end_time']) : null;
    $duration = ($end && $start) ? round(($end - $start) / 60) : 0;
    $cost = $duration * 5;

    $row['duration'] = $end ? $duration . ' mins' : 'Ongoing';
    $row['cost'] = $end ? 'KES ' . number_format($cost, 2) : 'Pending';
    $rides[] = $row;
}

echo json_encode($rides);
