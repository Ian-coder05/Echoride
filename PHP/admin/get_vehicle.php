<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("Vehicle ID is required");
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM vehicles WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $vehicle = mysqli_fetch_assoc($result);
    
    if (!$vehicle) {
        throw new Exception("Vehicle not found");
    }
    
    echo json_encode($vehicle);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
