<?php
// Include database connection
include 'db.php';

// Set header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['message'])) {
            throw new Exception("All fields are required");
        }

        // Sanitize input
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $message = trim($_POST['message']);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Validate name (at least 2 characters)
        if (strlen($name) < 2) {
            throw new Exception("Name must be at least 2 characters long");
        }

        // Validate message (at least 10 characters)
        if (strlen($message) < 10) {
            throw new Exception("Message must be at least 10 characters long");
        }

        // Check if database connection exists
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Prepare and execute the insert statement
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param("sss", $name, $email, $message);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Thank you for your message! We'll get back to you soon.";
        } else {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = "Invalid request method";
}

// Close database connection if it exists
if (isset($conn)) {
    $conn->close();
}

// Return JSON response
echo json_encode($response);
?> 