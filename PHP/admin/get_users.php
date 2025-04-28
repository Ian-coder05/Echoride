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
    // First check if rides table exists
    $table_exists = false;
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'rides'");
    if (mysqli_num_rows($result) > 0) {
        $table_exists = true;
    }

    // Initialize conditions
    $conditions = [];
    $params = [];
    $types = "";    
    
    // Add user ID condition if session exists
    if (isset($_SESSION) && isset($_SESSION['user_id'])) {
        $conditions[] = "u.id != ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
    }

    // Apply filters
    if (!empty($_GET['search'])) {
        $conditions[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = "%" . $_GET['search'] . "%";
        $params[] = "%" . $_GET['search'] . "%";
        $types .= "ss";
    }

    if (isset($_GET['role']) && $_GET['role'] !== '') {
        $conditions[] = "u.is_admin = ?";
        $params[] = $_GET['role'];
        $types .= "i";
    }

    // Build query based on whether rides table exists
    if ($table_exists) {
        $query = "
            SELECT 
                u.id,
                u.full_name,
                u.email,
                u.is_admin,
                u.created_at,
                COALESCE(COUNT(r.id), 0) as total_rides,
                COALESCE(SUM(r.carbon_saved), 0) as total_carbon_saved
            FROM users u
            LEFT JOIN rides r ON u.id = r.user_id
            WHERE " . implode(" AND ", $conditions) . "
            GROUP BY u.id, u.full_name, u.email, u.is_admin, u.created_at
        ";
    } else {
        $query = "
            SELECT 
                u.id,
                u.full_name,
                u.email,
                u.is_admin,
                u.created_at,
                0 as total_rides,
                0 as total_carbon_saved
            FROM users u
            WHERE " . implode(" AND ", $conditions) . "
        ";
    }

    // Add sorting
    if (!empty($_GET['sort'])) {
        $query .= " ORDER BY ";
        switch($_GET['sort']) {
            case 'name':
                $query .= "u.full_name ASC";
                break;
            case 'rides':
                $query .= "total_rides DESC";
                break;
            case 'date':
                $query .= "u.created_at DESC";
                break;
            default:
                $query .= "u.id ASC";
        }
    } else {
        $query .= " ORDER BY u.id ASC";
    }

    // For debugging
    error_log("Query: " . $query);
    error_log("Params: " . implode(", ", $params));

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Remove password hash from response
        unset($row['password']);
        $users[] = $row;
    }
    
    // Log the response
    error_log("Sending response with " . count($users) . " users");
    
    // Make sure we're sending a string, not an object
    header('Content-Type: application/json');
    echo json_encode($users);

} catch (Exception $e) {
    error_log("Error in get_users.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
