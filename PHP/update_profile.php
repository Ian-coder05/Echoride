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
if (!isset($_POST['full_name']) || empty($_POST['full_name'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Full name is required'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name']);
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
$address = isset($_POST['address']) ? trim($_POST['address']) : null;

try {
    // Update user profile
    $sql = "UPDATE users SET 
            full_name = ?, 
            phone = ?, 
            address = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $full_name, $phone, $address, $user_id);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating profile: ' . $stmt->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating profile: ' . $e->getMessage()
    ]);
}
?>
