<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // Validate required fields
    if (empty($_POST['id'])) {
        throw new Exception("Maintenance ID is required");
    }

    $required_fields = ['vehicle_id', 'type', 'description', 'priority', 'due_date', 'status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    // Get current status
    $query = "SELECT status FROM maintenance WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $current = mysqli_fetch_assoc($result);

    // Update maintenance task
    $query = "
        UPDATE maintenance SET
            vehicle_id = ?,
            type = ?,
            description = ?,
            priority = ?,
            due_date = ?,
            status = ?,
            assigned_to = ?,
            notes = ?
        WHERE id = ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isssssssi",
        $_POST['vehicle_id'],
        $_POST['type'],
        $_POST['description'],
        $_POST['priority'],
        $_POST['due_date'],
        $_POST['status'],
        $_POST['assigned_to'] ?: null,
        $_POST['notes'],
        $_POST['id']
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error updating maintenance task: " . mysqli_stmt_error($stmt));
    }

    // Add history record if status changed
    if ($current['status'] !== $_POST['status']) {
        $query = "
            INSERT INTO maintenance_history (
                maintenance_id,
                status,
                notes
            ) VALUES (?, ?, ?)
        ";

        $notes = $_POST['notes'] ?? "Status updated to " . $_POST['status'];
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iss",
            $_POST['id'],
            $_POST['status'],
            $notes
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error creating history record: " . mysqli_stmt_error($stmt));
        }
    }

    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
