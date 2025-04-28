<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // Validate input
    $required_fields = ['type', 'model', 'location', 'battery_level'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate type
    if (!in_array($_POST['type'], ['scooter', 'bike'])) {
        throw new Exception("Invalid vehicle type");
    }

    // Validate battery level
    $battery_level = intval($_POST['battery_level']);
    if ($battery_level < 0 || $battery_level > 100) {
        throw new Exception("Battery level must be between 0 and 100");
    }

    $stmt = mysqli_prepare($conn, "
        INSERT INTO vehicles (type, model, status, battery_level, location, created_at)
        VALUES (?, ?, 'available', ?, ?, NOW())
    ");

    mysqli_stmt_bind_param($stmt, "ssis", 
        $_POST['type'],
        $_POST['model'],
        $battery_level,
        $_POST['location']
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn)]);
    } else {
        throw new Exception("Error adding vehicle: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
