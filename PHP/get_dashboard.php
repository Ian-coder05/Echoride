<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

$sql = "SELECT id, pickup, dropoff, ride_time, end_time, status FROM rides WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$rides = [];

while ($row = $result->fetch_assoc()) {
    $start = strtotime($row['ride_time']);
    $end = strtotime($row['end_time']);
    $duration = ($end && $start) ? round(($end - $start) / 60) : 0;
    $cost = $duration * 5;

    $row['duration'] = $duration . ' mins';
    $row['cost'] = 'KES ' . number_format($cost, 2);
    $rides[] = $row;
}

echo json_encode($rides);3r1
