<?php
// Set content type to JSON
header('Content-Type: application/json');

require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Query to get rides with vehicle information
$sql = "SELECT r.id, r.pickup, r.dropoff, r.ride_time, r.end_time, r.status, 
        r.distance, r.carbon_saved, r.battery_usage, r.vehicle_id,
        v.type as vehicle_type, v.model as vehicle_model
        FROM rides r 
        LEFT JOIN vehicles v ON r.vehicle_id = v.id
        WHERE r.user_id = ?
        ORDER BY r.ride_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$rides = [];

while ($row = $result->fetch_assoc()) {
    // Format ride time
    $row['formatted_ride_time'] = date('M d, Y h:i A', strtotime($row['ride_time']));
    
    // Format end time if available
    if ($row['end_time']) {
        $row['formatted_end_time'] = date('M d, Y h:i A', strtotime($row['end_time']));
        
        // Calculate duration in minutes
        $start = strtotime($row['ride_time']);
        $end = strtotime($row['end_time']);
        $duration_mins = round(($end - $start) / 60);
        $row['duration'] = $duration_mins . ' mins';
    } else {
        $row['formatted_end_time'] = 'Not completed';
        $row['duration'] = 'Ongoing';
    }
    
    // Format distance and carbon saved
    $row['formatted_distance'] = number_format($row['distance'], 1) . ' km';
    $row['formatted_carbon'] = number_format($row['carbon_saved'], 1) . ' kg CO₂';
    
    // Format vehicle info
    if ($row['vehicle_type'] && $row['vehicle_model']) {
        $vehicle_type = ucfirst($row['vehicle_type']);
        $row['vehicle_info'] = "$vehicle_type: {$row['vehicle_model']}";
    } else {
        $row['vehicle_info'] = 'Unknown vehicle';
    }
    
    $rides[] = $row;
}

echo json_encode($rides);
