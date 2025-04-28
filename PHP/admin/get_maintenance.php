<?php
require_once('../db.php');
require_once('check_admin.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    error_log("Starting get_maintenance.php");

    // First check if maintenance table exists
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'maintenance'");
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Maintenance table does not exist');
    }

    $conditions = [];
    $params = [];
    $types = "";

    // Check if specific task is requested
    if (!empty($_GET['id'])) {
        $conditions[] = "m.id = ?";
        $params[] = $_GET['id'];
        $types .= "i";
    } else {
        // Apply filters for list view
        if (!empty($_GET['status'])) {
            $conditions[] = "m.status = ?";
            $params[] = $_GET['status'];
            $types .= "s";
        }

        if (!empty($_GET['priority'])) {
            $conditions[] = "m.priority = ?";
            $params[] = $_GET['priority'];
            $types .= "s";
        }

        if (!empty($_GET['search'])) {
            $conditions[] = "(v.model LIKE ? OR m.description LIKE ?)";
            $params[] = "%" . $_GET['search'] . "%";
            $params[] = "%" . $_GET['search'] . "%";
            $types .= "ss";
        }

        // Update overdue tasks
        mysqli_query($conn, "UPDATE maintenance SET status = 'overdue' WHERE status = 'pending' AND due_date < NOW()");
    }

    // Get maintenance tasks
    $query = "
        SELECT 
            m.*,
            v.model as vehicle_name,
            v.type as vehicle_type,
            COALESCE(u.full_name, 'Unassigned') as assigned_to_name
        FROM maintenance m
        LEFT JOIN vehicles v ON m.vehicle_id = v.id
        LEFT JOIN users u ON m.assigned_to = u.id
    ";

    // Add conditions if any
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // Add ordering for list view
    if (empty($_GET['id'])) {
        $query .= "
            ORDER BY 
                CASE 
                    WHEN m.status = 'overdue' THEN 1
                    WHEN m.status = 'pending' THEN 2
                    WHEN m.status = 'in_progress' THEN 3
                    WHEN m.status = 'completed' THEN 4
                END,
                m.due_date ASC
        ";
    }

    error_log("Query: " . $query);
    error_log("Params: " . ($types ? implode(", ", $params) : "none"));

    // Prepare and execute the query
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
    
    $tasks = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Convert dates to ISO format for JavaScript
        if ($row['created_at']) $row['created_at'] = date('c', strtotime($row['created_at']));
        if ($row['updated_at']) $row['updated_at'] = date('c', strtotime($row['updated_at']));
        if ($row['due_date']) $row['due_date'] = date('c', strtotime($row['due_date']));
        $tasks[] = $row;
    }

    // Get statistics for list view
    if (empty($_GET['id'])) {
        $stats_query = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue
            FROM maintenance
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
    }

    error_log("Successfully retrieved maintenance data");
    echo json_encode([
        'tasks' => $tasks,
        'stats' => isset($stats) ? $stats : null
    ]);

} catch (Exception $e) {
    error_log("Error in get_maintenance.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
