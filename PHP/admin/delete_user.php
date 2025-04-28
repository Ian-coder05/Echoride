<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("User ID is required");
    }

    // Prevent self-deletion
    if ($_POST['id'] == $_SESSION['user_id']) {
        throw new Exception("You cannot delete your own account");
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Delete user's rides
        $stmt = mysqli_prepare($conn, "DELETE FROM rides WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
        mysqli_stmt_execute($stmt);

        // Delete user's payments
        $stmt = mysqli_prepare($conn, "DELETE FROM payments WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
        mysqli_stmt_execute($stmt);

        // Delete user
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
        mysqli_stmt_execute($stmt);

        // Commit transaction
        mysqli_commit($conn);
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
