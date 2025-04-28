<?php
require_once('../db.php');
require_once('check_admin.php');

header('Content-Type: application/json');

try {
    // Validate required fields
    $required_fields = ['vehicle_id', 'type', 'description', 'priority', 'due_date'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    // Insert maintenance task
    $query = "
        INSERT INTO maintenance (
            vehicle_id,
            type,
            description,
            priority,
            status,
            due_date,
            assigned_to,
            notes
        ) VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)
    ";

    $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "issssss",
        $_POST['vehicle_id'],
        $_POST['type'],
        $_POST['description'],
        $_POST['priority'],
        $_POST['due_date'],
        $assigned_to,
        $notes
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error creating maintenance task: " . mysqli_stmt_error($stmt));
    }
    
    $maintenance_id = mysqli_insert_id($conn);

    // Add initial history record
    $query = "
        INSERT INTO maintenance_history (
            maintenance_id,
            status,
            notes
        ) VALUES (?, 'pending', ?)
    ";

    $history_note = "Task created" . ($notes ? ": $notes" : "");
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed for history: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "is", $maintenance_id, $history_note);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error creating history record: " . mysqli_stmt_error($stmt));
    }

    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'id' => $maintenance_id,
        'message' => 'Maintenance task created successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    error_log("Error in add_maintenance.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
