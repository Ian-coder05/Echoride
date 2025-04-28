<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // Create maintenance table
    $query = "
        CREATE TABLE IF NOT EXISTS maintenance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT NOT NULL,
            type ENUM('routine', 'repair', 'battery', 'tire', 'software') NOT NULL,
            description TEXT NOT NULL,
            priority ENUM('high', 'medium', 'low') NOT NULL,
            status ENUM('pending', 'in_progress', 'completed', 'overdue') NOT NULL DEFAULT 'pending',
            due_date DATETIME NOT NULL,
            assigned_to INT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
        )
    ";
    
    if (mysqli_query($conn, $query)) {
        echo "Maintenance table created successfully\n";
    } else {
        throw new Exception("Error creating maintenance table: " . mysqli_error($conn));
    }

    // Create maintenance_history table for tracking changes
    $query = "
        CREATE TABLE IF NOT EXISTS maintenance_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            maintenance_id INT NOT NULL,
            status ENUM('pending', 'in_progress', 'completed', 'overdue') NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (maintenance_id) REFERENCES maintenance(id) ON DELETE CASCADE
        )
    ";
    
    if (mysqli_query($conn, $query)) {
        echo "Maintenance history table created successfully\n";
    } else {
        throw new Exception("Error creating maintenance history table: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
