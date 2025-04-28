<?php
require_once('../db.php');
require_once('check_admin.php');

// Query to get vehicle status
$query = "
    SELECT 
        id,
        type,
        status,
        battery_level
    FROM vehicles
    ORDER BY 
        CASE WHEN status = 'maintenance' THEN 0 ELSE 1 END, 
        battery_level ASC
    LIMIT 10
";

$result = mysqli_query($conn, $query);

// Check if query was successful
if ($result) {
    $vehicles = [];
    
    // Fetch all vehicles
    while ($row = mysqli_fetch_assoc($result)) {
        $vehicles[] = $row;
    }
    
    // Free result set
    mysqli_free_result($result);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($vehicles);
} else {
    // Return error if query failed
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
}
?>
