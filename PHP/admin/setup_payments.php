<?php
require_once('../db.php');

// Check if payments table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
if (mysqli_num_rows($result) == 0) {
    // Create payments table if it doesn't exist
    $query = "
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ride_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_status ENUM('pending', 'completed', 'failed') NOT NULL,
            transaction_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (ride_id) REFERENCES rides(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (mysqli_query($conn, $query)) {
        echo "Payments table created successfully";
    } else {
        echo "Error creating payments table: " . mysqli_error($conn);
    }
}

// Create payment history table if it doesn't exist
$result = mysqli_query($conn, "SHOW TABLES LIKE 'payment_history'");
if (mysqli_num_rows($result) == 0) {
    $query = "
        CREATE TABLE IF NOT EXISTS payment_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_id INT NOT NULL,
            status ENUM('completed', 'pending', 'failed') NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (payment_id) REFERENCES payments(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if (mysqli_query($conn, $query)) {
        echo "\nPayment history table created successfully";
    } else {
        echo "\nError creating payment history table: " . mysqli_error($conn);
    }
}
?>
