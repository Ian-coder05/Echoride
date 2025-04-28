<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // Get all admin users who can be assigned to maintenance tasks
    $query = "
        SELECT 
            id,
            full_name as name
        FROM users 
        WHERE is_admin = 1
        ORDER BY full_name
    ";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception("Error fetching technicians: " . mysqli_error($conn));
    }
    
    $technicians = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $technicians[] = $row;
    }
    
    echo json_encode($technicians);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
