<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id   = $_SESSION['user_id'];
    $pickup    = $_POST['pickup'];
    $dropoff   = $_POST['dropoff'];
    $ride_time = $_POST['ride_time'];

    $stmt = $conn->prepare("INSERT INTO rides (user_id, pickup, dropoff, ride_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $pickup, $dropoff, $ride_time);

    if ($stmt->execute()) {
        echo "Ride booked successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
