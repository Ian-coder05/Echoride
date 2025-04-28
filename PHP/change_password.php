<?php
// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if required fields are provided
if (!isset($_POST['current_password']) || empty($_POST['current_password']) ||
    !isset($_POST['new_password']) || empty($_POST['new_password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Current password and new password are required'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];

try {
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
        exit;
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $update_sql = "UPDATE users SET password = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $hashed_password, $user_id);
    $update_result = $update_stmt->execute();
    
    if ($update_result) {
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error changing password: ' . $update_stmt->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error changing password: ' . $e->getMessage()
    ]);
}
?>
