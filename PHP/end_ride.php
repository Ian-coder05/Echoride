<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ride_id = $_POST['ride_id'];
    $end_time = date("Y-m-d H:i:s");

    // Update the ride as ended
    $stmt = $conn->prepare("UPDATE rides SET status = 'completed', end_time = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $end_time, $ride_id, $_SESSION['user_id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ride ended successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error ending ride: ' . $stmt->error]);
    }
}
?>
