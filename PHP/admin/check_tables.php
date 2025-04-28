<?php
require_once('../db.php');

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Checking database tables...\n\n";

// Check vehicles table
$result = mysqli_query($conn, "SHOW TABLES LIKE 'vehicles'");
if (mysqli_num_rows($result) > 0) {
    echo "Vehicles table exists\n";
    
    // Check columns
    $result = mysqli_query($conn, "SHOW COLUMNS FROM vehicles");
    echo "Vehicles table columns:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
    // Check number of records
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM vehicles");
    $row = mysqli_fetch_assoc($result);
    echo "Total vehicles: {$row['count']}\n";
} else {
    echo "Vehicles table does not exist!\n";
}

echo "\n";

// Check maintenance table
$result = mysqli_query($conn, "SHOW TABLES LIKE 'maintenance'");
if (mysqli_num_rows($result) > 0) {
    echo "Maintenance table exists\n";
    
    // Check columns
    $result = mysqli_query($conn, "SHOW COLUMNS FROM maintenance");
    echo "Maintenance table columns:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
    // Check number of records
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM maintenance");
    $row = mysqli_fetch_assoc($result);
    echo "Total maintenance records: {$row['count']}\n";
} else {
    echo "Maintenance table does not exist!\n";
}

echo "\n";

// Check maintenance_history table
$result = mysqli_query($conn, "SHOW TABLES LIKE 'maintenance_history'");
if (mysqli_num_rows($result) > 0) {
    echo "Maintenance history table exists\n";
    
    // Check columns
    $result = mysqli_query($conn, "SHOW COLUMNS FROM maintenance_history");
    echo "Maintenance history table columns:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
    // Check number of records
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM maintenance_history");
    $row = mysqli_fetch_assoc($result);
    echo "Total history records: {$row['count']}\n";
} else {
    echo "Maintenance history table does not exist!\n";
}
?>
