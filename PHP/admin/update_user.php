<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // Validate required fields
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception("User ID is required");
    }

    $required_fields = ['full_name', 'email'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if email exists for other users
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($stmt, "si", $_POST['email'], $_POST['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        throw new Exception("Email already exists for another user");
    }

    // Prevent changing own admin status
    if ($_POST['id'] == $_SESSION['user_id'] && isset($_POST['is_admin']) && !$_POST['is_admin']) {
        throw new Exception("You cannot remove your own admin privileges");
    }

    // Update user
    if (!empty($_POST['password'])) {
        // Update with new password
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "
            UPDATE users 
            SET full_name = ?,
                email = ?,
                password = ?,
                is_admin = ?
            WHERE id = ?
        ");
        
        $is_admin = isset($_POST['is_admin']) ? intval($_POST['is_admin']) : 0;
        mysqli_stmt_bind_param($stmt, "sssii", 
            $_POST['full_name'],
            $_POST['email'],
            $password_hash,
            $is_admin,
            $_POST['id']
        );
    } else {
        // Update without changing password
        $stmt = mysqli_prepare($conn, "
            UPDATE users 
            SET full_name = ?,
                email = ?,
                is_admin = ?
            WHERE id = ?
        ");
        
        $is_admin = isset($_POST['is_admin']) ? intval($_POST['is_admin']) : 0;
        mysqli_stmt_bind_param($stmt, "ssii", 
            $_POST['full_name'],
            $_POST['email'],
            $is_admin,
            $_POST['id']
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error updating user: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
