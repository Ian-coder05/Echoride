<?php
require_once('../db.php');
require_once('check_admin.php');

// Query to get recent rides
$query = "
    SELECT 
        r.id,
        u.full_name as user_name,
        v.type as vehicle_type,
        v.id as vehicle_id,
        r.status
    FROM rides r
    JOIN users u ON r.user_id = u.id
    JOIN vehicles v ON r.vehicle_id = v.id
    ORDER BY r.created_at DESC
    LIMIT 10
";

$result = mysqli_query($conn, $query);

// Check if query was successful
if ($result) {
    $rides = [];
    
    // Fetch all rides
    while ($row = mysqli_fetch_assoc($result)) {
        $rides[] = $row;
    }
    
    // Free result set
    mysqli_free_result($result);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($rides);
} else {
    // Return error if query failed
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
}
?>
