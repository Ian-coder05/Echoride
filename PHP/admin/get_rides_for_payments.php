<?php
require_once('../db.php');

// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if we need the admin check
if (file_exists('check_admin.php')) {
    require_once('check_admin.php');
}

try {
    // Check if rides table exists
    $table_exists = false;
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'rides'");
    if (mysqli_num_rows($result) > 0) {
        $table_exists = true;
    }
    
    if (!$table_exists) {
        // Return empty array if rides table doesn't exist
        echo json_encode([]);
        exit;
    }
    
    $query = "
        SELECT 
            id,
            CONCAT('Ride #', id, ' - ', DATE_FORMAT(ride_date, '%Y-%m-%d')) as display_text
        FROM rides
        ORDER BY ride_date DESC
        LIMIT 100
    ";

    $result = mysqli_query($conn, $query);
    if ($result === false) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }
    
    $rides = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rides[] = $row;
    }

    // Log the response
    error_log("Sending response with " . count($rides) . " rides");
    
    echo json_encode($rides);

} catch (Exception $e) {
    error_log("Error in get_rides_for_payments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
