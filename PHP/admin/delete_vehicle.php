<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("Vehicle ID is required");
    }

    // Check if vehicle is in use
    $check_stmt = mysqli_prepare($conn, "
        SELECT status FROM vehicles WHERE id = ?
    ");
    mysqli_stmt_bind_param($check_stmt, "i", $_POST['id']);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $vehicle = mysqli_fetch_assoc($result);

    if (!$vehicle) {
        throw new Exception("Vehicle not found");
    }

    if ($vehicle['status'] === 'in_use') {
        throw new Exception("Cannot delete vehicle while it is in use");
    }

    // Delete vehicle
    $stmt = mysqli_prepare($conn, "DELETE FROM vehicles WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_POST['id']);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error deleting vehicle: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
