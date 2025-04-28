<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    $conditions = ["1=1"];
    $params = [];
    $types = "";

    // Apply filters
    if (!empty($_GET['status'])) {
        $conditions[] = "r.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    if (!empty($_GET['vehicleType'])) {
        $conditions[] = "v.type = ?";
        $params[] = $_GET['vehicleType'];
        $types .= "s";
    }

    if (!empty($_GET['search'])) {
        $conditions[] = "(u.full_name LIKE ? OR r.pickup_location LIKE ? OR r.dropoff_location LIKE ?)";
        $search = "%" . $_GET['search'] . "%";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sss";
    }

    // Build query
    $query = "
        SELECT 
            r.*,
            u.full_name as user_name,
            v.type as vehicle_type
        FROM rides r
        JOIN users u ON r.user_id = u.id
        JOIN vehicles v ON r.vehicle_id = v.id
        WHERE " . implode(" AND ", $conditions) . "
        ORDER BY r.created_at DESC
        LIMIT 50
    ";

    $stmt = mysqli_prepare($conn, $query);
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $rides = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rides[] = $row;
    }
    
    echo json_encode($rides);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
