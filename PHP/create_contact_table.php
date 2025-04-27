<?php
include 'db.php';

// SQL to create contact_messages table
$sql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('new', 'read', 'replied') DEFAULT 'new'
)";

if ($conn->query($sql) === TRUE) {
    echo "Table contact_messages created successfully or already exists";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 