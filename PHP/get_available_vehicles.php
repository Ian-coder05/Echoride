<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('db.php');

try {
    // Check if vehicles table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'vehicles'");
    
    if (mysqli_num_rows($check_table) == 0) {
        // If vehicles table doesn't exist, return sample data
        $sample_vehicles = [
            [
                'id' => 1,
                'type' => 'scooter',
                'model' => 'Yamaha Eco',
                'battery_level' => 95,
                'location' => 'Nairobi CBD',
                'status' => 'available',
                'image' => 'assets/img/scooter1.jpg'
            ],
            [
                'id' => 2,
                'type' => 'bike',
                'model' => 'Honda E-Bike',
                'battery_level' => 88,
                'location' => 'Westlands',
                'status' => 'available',
                'image' => 'assets/img/bike1.jpg'
            ],
            [
                'id' => 3,
                'type' => 'scooter',
                'model' => 'Eco Scooter Pro',
                'battery_level' => 75,
                'location' => 'Kilimani',
                'status' => 'available',
                'image' => 'assets/img/scooter2.jpg'
            ],
            [
                'id' => 4,
                'type' => 'bike',
                'model' => 'Mountain E-Bike',
                'battery_level' => 100,
                'location' => 'Karen',
                'status' => 'available',
                'image' => 'assets/img/bike2.jpg'
            ]
        ];
        
        echo json_encode($sample_vehicles);
        exit;
    }
    
    // Get available vehicles from database
    $query = "
        SELECT 
            id,
            type,
            model,
            battery_level,
            location,
            status
        FROM 
            vehicles
        WHERE 
            status = 'available'
        ORDER BY 
            battery_level DESC
    ";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
    
    $vehicles = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Add image path based on type
        $row['image'] = 'assets/img/' . $row['type'] . '1.jpg';
        
        $vehicles[] = $row;
    }
    
    echo json_encode($vehicles);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
