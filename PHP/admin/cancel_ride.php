<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("Ride ID is required");
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Get ride details
        $stmt = mysqli_prepare($conn, "
            SELECT r.*, v.id as vehicle_id 
            FROM rides r
            JOIN vehicles v ON r.vehicle_id = v.id
            WHERE r.id = ? AND r.status = 'ongoing'
            FOR UPDATE
        ");
        mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$ride = mysqli_fetch_assoc($result)) {
            throw new Exception("Ride not found or already completed/cancelled");
        }

        // Update ride status
        $stmt = mysqli_prepare($conn, "
            UPDATE rides 
            SET status = 'cancelled',
                end_time = NOW()
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $_POST['id']);
        mysqli_stmt_execute($stmt);

        // Update vehicle status
        $stmt = mysqli_prepare($conn, "
            UPDATE vehicles 
            SET status = 'available'
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $ride['vehicle_id']);
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
