<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    // Return unauthorized status for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Redirect to login page for direct access
    header('Location: ../login.html');
    exit;
}
?>
