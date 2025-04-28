<?php
// Set the content type to JSON for API response
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("User not logged in");
        }
        
        // Get form data
        $user_id = $_SESSION['user_id'];
        $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
        $pickup = isset($_POST['pickup']) ? $_POST['pickup'] : null;
        $dropoff = isset($_POST['dropoff']) ? $_POST['dropoff'] : null;
        $ride_time = isset($_POST['ride_time']) ? $_POST['ride_time'] : null;
        $distance = isset($_POST['distance']) ? floatval($_POST['distance']) : 0;
        $carbon_saved = isset($_POST['carbon_saved']) ? floatval($_POST['carbon_saved']) : 0;
        $battery_usage = isset($_POST['battery_usage']) ? intval($_POST['battery_usage']) : 0;
        
        // Validate required fields
        if (!$vehicle_id || !$pickup || !$dropoff || !$ride_time) {
            throw new Exception("Missing required fields");
        }
        
        // Format ride time to MySQL datetime
        $ride_datetime = date('Y-m-d H:i:s', strtotime($ride_time));
        
        // Insert ride into database
        $stmt = $conn->prepare("INSERT INTO rides (user_id, vehicle_id, pickup, dropoff, ride_time, status, distance, carbon_saved, battery_usage) VALUES (?, ?, ?, ?, ?, 'ongoing', ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("iisssidi", 
            $user_id, 
            $vehicle_id, 
            $pickup, 
            $dropoff, 
            $ride_datetime, 
            $distance, 
            $carbon_saved, 
            $battery_usage
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $ride_id = $stmt->insert_id;
        
        // Update vehicle status to 'in_use'
        $update_vehicle = $conn->prepare("UPDATE vehicles SET status = 'in_use' WHERE id = ?");
        if ($update_vehicle) {
            $update_vehicle->bind_param("i", $vehicle_id);
            $update_vehicle->execute();
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Ride booked successfully',
            'ride_id' => $ride_id
        ]);
        
    } catch (Exception $e) {
        // Return error response
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    // Return method not allowed response
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
