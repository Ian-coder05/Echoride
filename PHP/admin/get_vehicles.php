<?php
require_once('../db.php');
require_once('check_admin.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $conditions = [];
    $params = [];
    $types = "";

    // Apply filters
    if (!empty($_GET['type'])) {
        $conditions[] = "v.type = ?";
        $params[] = $_GET['type'];
        $types .= "s";
    }

    if (!empty($_GET['status'])) {
        $conditions[] = "v.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    if (!empty($_GET['search'])) {
        $conditions[] = "(v.model LIKE ? OR v.location LIKE ?)";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
        $types .= "ss";
    }

    // Build base query with maintenance info
    $query = "
        SELECT 
            v.id,
            v.type,
            v.model,
            v.status,
            v.battery_level,
            v.location,
            v.last_maintenance_date,
            v.total_distance,
            v.created_at,
            COUNT(m.id) as pending_maintenance,
            MAX(CASE WHEN m.status IN ('pending', 'in_progress') THEN m.due_date ELSE NULL END) as next_maintenance
        FROM vehicles v
        LEFT JOIN maintenance m ON v.id = m.vehicle_id AND m.status IN ('pending', 'in_progress')
    ";

    // Add conditions if any
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // Add grouping and ordering
    $query .= "
        GROUP BY 
            v.id, v.type, v.model, v.status, v.battery_level,
            v.location, v.last_maintenance_date, v.total_distance, v.created_at
        ORDER BY v.id ASC
    ";

    // For debugging
    error_log("Query: " . $query);
    error_log("Params: " . ($types ? implode(", ", $params) : "none"));

    // Prepare and execute
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    if ($types && $params) {
        if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
            throw new Exception("Bind param failed: " . mysqli_stmt_error($stmt));
        }
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    $vehicles = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format dates
        if ($row['last_maintenance_date']) {
            $row['last_maintenance_date'] = date('c', strtotime($row['last_maintenance_date']));
        }
        if ($row['next_maintenance']) {
            $row['next_maintenance'] = date('c', strtotime($row['next_maintenance']));
        }
        $row['created_at'] = date('c', strtotime($row['created_at']));
        
        // Format numbers
        $row['battery_level'] = (int)$row['battery_level'];
        $row['total_distance'] = (float)$row['total_distance'];
        $row['pending_maintenance'] = (int)$row['pending_maintenance'];
        
        $vehicles[] = $row;
    }
    
    echo json_encode($vehicles);

} catch (Exception $e) {
    $error = [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log("Error in get_vehicles.php: " . json_encode($error));
    http_response_code(500);
    echo json_encode($error);
}
?>
