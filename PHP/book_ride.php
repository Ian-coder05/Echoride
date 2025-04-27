<?php
// This is a backend PHP file and does not require a navbar update.
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html?error=notloggedin");
        exit;
    }

    $user_id   = $_SESSION['user_id'];
    $pickup    = $_POST['pickup'];
    $dropoff   = $_POST['dropoff'];
    $ride_time = $_POST['ride_time'];

    $stmt = $conn->prepare("INSERT INTO rides (user_id, pickup, dropoff, ride_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $pickup, $dropoff, $ride_time);

    if ($stmt->execute()) {
        header("Location: ../ride-success.html?status=success");
        exit;
    } else {
        header("Location: ../ride-success.html?status=error");
        exit;
    }
}
?>
