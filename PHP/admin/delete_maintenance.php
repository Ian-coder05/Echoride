<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (empty($_POST['id'])) {
        throw new Exception("Maintenance ID is required");
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    // Delete maintenance task (history will be deleted automatically due to CASCADE)
    $query = "DELETE FROM maintenance WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error deleting maintenance task: " . mysqli_stmt_error($stmt));
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
