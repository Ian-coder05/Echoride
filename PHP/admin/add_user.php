<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // Validate required fields
    $required_fields = ['full_name', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if email already exists
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $_POST['email']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        throw new Exception("Email already exists");
    }

    // Hash password
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = mysqli_prepare($conn, "
        INSERT INTO users (full_name, email, password, is_admin, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $is_admin = isset($_POST['is_admin']) ? intval($_POST['is_admin']) : 0;
    mysqli_stmt_bind_param($stmt, "sssi", 
        $_POST['full_name'],
        $_POST['email'],
        $password_hash,
        $is_admin
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn)]);
    } else {
        throw new Exception("Error adding user: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
