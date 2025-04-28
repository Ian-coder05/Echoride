<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // Validate input
    $required_fields = ['id', 'type', 'model', 'status', 'location', 'battery_level'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate type
    if (!in_array($_POST['type'], ['scooter', 'bike'])) {
        throw new Exception("Invalid vehicle type");
    }

    // Validate status
    if (!in_array($_POST['status'], ['available', 'in_use', 'maintenance', 'charging'])) {
        throw new Exception("Invalid vehicle status");
    }

    // Validate battery level
    $battery_level = intval($_POST['battery_level']);
    if ($battery_level < 0 || $battery_level > 100) {
        throw new Exception("Battery level must be between 0 and 100");
    }

    $stmt = mysqli_prepare($conn, "
        UPDATE vehicles 
        SET type = ?, 
            model = ?, 
            status = ?, 
            battery_level = ?, 
            location = ?
        WHERE id = ?
    ");

    mysqli_stmt_bind_param($stmt, "sssisi", 
        $_POST['type'],
        $_POST['model'],
        $_POST['status'],
        $battery_level,
        $_POST['location'],
        $_POST['id']
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error updating vehicle: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
