<?php
// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if ride_id is provided
if (!isset($_POST['ride_id']) || empty($_POST['ride_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ride ID is required'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$ride_id = intval($_POST['ride_id']);

try {
    // First, check if the ride exists and belongs to the user
    $check_sql = "SELECT r.id, r.vehicle_id, r.ride_time, r.status 
                 FROM rides r 
                 WHERE r.id = ? AND r.user_id = ? AND r.status = 'ongoing'";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $ride_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ride not found or already completed'
        ]);
        exit;
    }
    
    $ride = $result->fetch_assoc();
    $vehicle_id = $ride['vehicle_id'];
    $ride_time = $ride['ride_time'];
    
    // Set the current time as the end time
    $end_time = date('Y-m-d H:i:s');
    
    // Calculate duration in minutes
    $start = strtotime($ride_time);
    $end = strtotime($end_time);
    $duration_mins = round(($end - $start) / 60);
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Update ride status to completed and set end time
    $update_ride_sql = "UPDATE rides SET status = 'completed', end_time = ? WHERE id = ?";
    $update_ride_stmt = $conn->prepare($update_ride_sql);
    $update_ride_stmt->bind_param("si", $end_time, $ride_id);
    $update_ride_stmt->execute();
    
    // Update vehicle status back to available
    $update_vehicle_sql = "UPDATE vehicles SET status = 'available' WHERE id = ?";
    $update_vehicle_stmt = $conn->prepare($update_vehicle_sql);
    $update_vehicle_stmt->bind_param("i", $vehicle_id);
    $update_vehicle_stmt->execute();
    
    // Update user's total_rides and total_carbon_saved
    $get_carbon_sql = "SELECT carbon_saved FROM rides WHERE id = ?";
    $get_carbon_stmt = $conn->prepare($get_carbon_sql);
    $get_carbon_stmt->bind_param("i", $ride_id);
    $get_carbon_stmt->execute();
    $carbon_result = $get_carbon_stmt->get_result();
    $carbon_row = $carbon_result->fetch_assoc();
    $carbon_saved = $carbon_row['carbon_saved'];
    
    $update_user_sql = "UPDATE users SET 
                        total_rides = total_rides + 1, 
                        total_carbon_saved = total_carbon_saved + ? 
                        WHERE id = ?";
    $update_user_stmt = $conn->prepare($update_user_sql);
    $update_user_stmt->bind_param("di", $carbon_saved, $user_id);
    $update_user_stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Ride completed successfully',
        'end_time' => $end_time,
        'duration' => $duration_mins . ' mins'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error completing ride: ' . $e->getMessage()
    ]);
}
?>
