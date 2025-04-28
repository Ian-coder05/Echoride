<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // Create vehicles table
    $query = "
        CREATE TABLE IF NOT EXISTS vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type ENUM('bike', 'scooter', 'car') NOT NULL,
            model VARCHAR(255) NOT NULL,
            status ENUM('available', 'in_use', 'maintenance', 'disabled') NOT NULL DEFAULT 'available',
            location_lat DECIMAL(10, 8),
            location_lng DECIMAL(11, 8),
            battery_level INT,
            last_maintenance DATETIME,
            total_rides INT DEFAULT 0,
            total_distance DECIMAL(10, 2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    if (mysqli_query($conn, $query)) {
        echo "Vehicles table created successfully\n";
    } else {
        throw new Exception("Error creating vehicles table: " . mysqli_error($conn));
    }

    // Add some sample vehicles if the table is empty
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM vehicles");
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        $sample_vehicles = [
            ['EcoBike-001', 'bike', 'Mountain Bike Pro', 'available'],
            ['EcoBike-002', 'bike', 'City Cruiser', 'available'],
            ['EcoScoot-001', 'scooter', 'Electric Scooter X1', 'available'],
            ['EcoScoot-002', 'scooter', 'Electric Scooter X1', 'available'],
            ['EcoCar-001', 'car', 'Electric Sedan Model Y', 'available']
        ];

        $query = "
            INSERT INTO vehicles (name, type, model, status)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $query);
        foreach ($sample_vehicles as $vehicle) {
            mysqli_stmt_bind_param($stmt, "ssss", $vehicle[0], $vehicle[1], $vehicle[2], $vehicle[3]);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting sample vehicle: " . mysqli_stmt_error($stmt));
            }
        }
        echo "Sample vehicles added successfully\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
