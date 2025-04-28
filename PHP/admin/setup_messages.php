<?php
require_once('../db.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create messages table if it doesn't exist
    $query = "
    CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_id INT NOT NULL,
        to_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        message_type ENUM('support', 'feedback', 'complaint', 'inquiry', 'notification') NOT NULL,
        status ENUM('read', 'unread') NOT NULL DEFAULT 'unread',
        is_flagged TINYINT(1) NOT NULL DEFAULT 0,
        reply_to_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (mysqli_query($conn, $query)) {
        echo "Messages table created successfully<br>";
    } else {
        throw new Exception("Error creating messages table: " . mysqli_error($conn));
    }

    echo "Setup completed successfully";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
