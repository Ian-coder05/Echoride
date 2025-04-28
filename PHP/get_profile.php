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

$user_id = $_SESSION['user_id'];

try {
    // Get user profile data
    $sql = "SELECT id, full_name, email, phone, address, total_rides, total_carbon_saved, wallet_balance 
            FROM users 
            WHERE id = ?";
    
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
    
    // Return success response
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving profile: ' . $e->getMessage()
    ]);
}
?>
