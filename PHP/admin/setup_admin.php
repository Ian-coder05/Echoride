<?php
require_once('../db.php');

try {
    // Add is_admin column to users table if it doesn't exist
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_admin'");
    if (mysqli_num_rows($check_column) == 0) {
        $alter_query = "ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE";
        if (!mysqli_query($conn, $alter_query)) {
            throw new Exception("Error adding is_admin column: " . mysqli_error($conn));
        }
    }
    
    // Check if admin user exists
    $check_admin = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE is_admin = TRUE");
    $admin_count = mysqli_fetch_assoc($check_admin)['count'];
    
    if ($admin_count == 0) {
        // Create default admin user
        $admin_name = 'Admin User';
        $admin_email = 'admin@echoride.com';
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (full_name, email, password, is_admin) 
                        VALUES (?, ?, ?, TRUE)";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "sss", $admin_name, $admin_email, $admin_password);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error creating admin user: " . mysqli_error($conn));
        }
        
        echo "Admin user created successfully!<br>";
        echo "Email: admin@echoride.com<br>";
        echo "Password: admin123<br>";
        echo "Please change these credentials after first login!";
    } else {
        echo "Admin user already exists.";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
