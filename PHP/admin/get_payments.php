<?php
require_once('../db.php');
require_once('check_admin.php');

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log request details
error_log("get_payments.php called with: " . json_encode($_GET));

try {
    $conditions = [];
    $params = [];
    $types = "";

    // Apply filters
    if (!empty($_GET['status'])) {
        $conditions[] = "p.payment_status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    if (!empty($_GET['method'])) {
        $conditions[] = "p.payment_method = ?";
        $params[] = $_GET['method'];
        $types .= "s";
    }

    if (!empty($_GET['search'])) {
        $conditions[] = "(p.transaction_id LIKE ? OR u.full_name LIKE ?)";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
        $types .= "ss";
    }

    // Get payments
    $query = "
        SELECT 
            p.*,
            u.full_name as user_name,
            r.pickup as ride_from,
            r.dropoff as ride_to
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN rides r ON p.ride_id = r.id
    ";

    // Add conditions if any
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // Add ordering
    $query .= " ORDER BY p.created_at DESC";

    // Log the query for debugging
    error_log("Query: $query");
    if (!empty($params)) {
        error_log("Params: " . json_encode($params));
    }
    
    // Prepare and execute the query
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    if ($types && $params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    $payments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[] = $row;
    }

    // Get statistics
    $stats_query = "
        SELECT
            COUNT(*) as total,
            SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM payments
    ";
    
    $result = mysqli_query($conn, $stats_query);
    if ($result === false) {
        throw new Exception("Stats query failed: " . mysqli_error($conn));
    }

    $stats = mysqli_fetch_assoc($result);
    // Convert null values to 0
    $stats = array_map(function($value) {
        return $value === null ? 0 : (int)$value;
    }, $stats);

    $response = [
        'payments' => $payments,
        'stats' => $stats
    ];
    
    // Log the response for debugging
    error_log("Response: " . json_encode(['payment_count' => count($payments), 'stats' => $stats]));
    
    // Make sure we're sending a string, not an object
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_payments.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
