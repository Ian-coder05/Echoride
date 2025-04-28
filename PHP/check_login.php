<?php
// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);

// Return login status
echo json_encode([
    'loggedIn' => $loggedIn,
    'userId' => $loggedIn ? $_SESSION['user_id'] : null
]);
?>
