<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    // First get a vehicle ID
    $result = mysqli_query($conn, "SELECT id FROM vehicles LIMIT 1");
    if (!$result || mysqli_num_rows($result) === 0) {
        throw new Exception("No vehicles found");
    }
    $vehicle = mysqli_fetch_assoc($result);
    $vehicle_id = $vehicle['id'];

    // Sample maintenance tasks
    $tasks = [
        [
            'vehicle_id' => $vehicle_id,
            'type' => 'routine',
            'description' => 'Regular monthly checkup',
            'priority' => 'medium',
            'status' => 'pending',
            'due_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
            'notes' => 'Standard inspection and maintenance'
        ],
        [
            'vehicle_id' => $vehicle_id,
            'type' => 'battery',
            'description' => 'Battery replacement needed',
            'priority' => 'high',
            'status' => 'pending',
            'due_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'notes' => 'Battery performance below 60%'
        ],
        [
            'vehicle_id' => $vehicle_id,
            'type' => 'software',
            'description' => 'System update required',
            'priority' => 'low',
            'status' => 'pending',
            'due_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
            'notes' => 'New firmware version available'
        ]
    ];

    // Insert sample tasks
    $query = "
        INSERT INTO maintenance 
        (vehicle_id, type, description, priority, status, due_date, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    foreach ($tasks as $task) {
        mysqli_stmt_bind_param($stmt, "issssss", 
            $task['vehicle_id'],
            $task['type'],
            $task['description'],
            $task['priority'],
            $task['status'],
            $task['due_date'],
            $task['notes']
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
        }

        $maintenance_id = mysqli_insert_id($conn);

        // Add to history
        $history_query = "
            INSERT INTO maintenance_history 
            (maintenance_id, status, notes)
            VALUES (?, ?, ?)
        ";

        $history_stmt = mysqli_prepare($conn, $history_query);
        if ($history_stmt === false) {
            throw new Exception("History prepare failed: " . mysqli_error($conn));
        }

        $status = $task['status'];
        $notes = "Task created";
        mysqli_stmt_bind_param($history_stmt, "iss", $maintenance_id, $status, $notes);

        if (!mysqli_stmt_execute($history_stmt)) {
            throw new Exception("History execute failed: " . mysqli_stmt_error($history_stmt));
        }
    }

    echo "Sample maintenance tasks added successfully\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
