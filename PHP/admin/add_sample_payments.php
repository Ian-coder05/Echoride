<?php
require_once('../db.php');

// First, make sure the payments table exists
require_once('setup_payments.php');

// Get some user IDs to assign payments to
$query = "SELECT id FROM users LIMIT 5";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("No users found. Please add users first.");
}

$user_ids = [];
while ($row = mysqli_fetch_assoc($result)) {
    $user_ids[] = $row['id'];
}

// Check if we need to create dummy rides
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM rides");
$row = mysqli_fetch_assoc($result);
if ($row['count'] == 0) {
    echo "No rides found. We'll need to create some dummy rides.\n";
}

// Get some ride IDs to assign payments to
$query = "SELECT id FROM rides LIMIT 5";
$result = mysqli_query($conn, $query);

$ride_ids = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ride_ids[] = $row['id'];
    }
} else {
    // Create dummy rides if none exist
    echo "Creating dummy rides...\n";
    $locations = [
        ['Nairobi CBD', 'Westlands'],
        ['Kileleshwa', 'Kilimani'],
        ['Lavington', 'Karen'],
        ['Embakasi', 'Jomo Kenyatta Airport'],
        ['Ngong Road', 'Upper Hill']
    ];
    
    // Get a vehicle ID
    $result = mysqli_query($conn, "SELECT id FROM vehicles LIMIT 1");
    $vehicle_id = 1; // Default
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $vehicle_id = $row['id'];
    }
    
    for ($i = 0; $i < 5; $i++) {
        $user_id = $user_ids[array_rand($user_ids)];
        $distance = rand(3, 20);
        $carbon_saved = $distance * 0.2;
        $battery_usage = rand(5, 20);
        $ride_time = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
        $end_time = date('Y-m-d H:i:s', strtotime($ride_time . ' +' . rand(20, 60) . ' minutes'));
        
        $query = "
            INSERT INTO rides (
                user_id,
                pickup,
                dropoff,
                ride_time,
                end_time,
                status,
                vehicle_id,
                distance,
                carbon_saved,
                battery_usage
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $status = 'completed';
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isssssiddi", 
            $user_id,
            $locations[$i][0],
            $locations[$i][1],
            $ride_time,
            $end_time,
            $status,
            $vehicle_id,
            $distance,
            $carbon_saved,
            $battery_usage
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $ride_ids[] = mysqli_insert_id($conn);
            echo "Created ride ID: " . $ride_ids[count($ride_ids) - 1] . "\n";
        } else {
            echo "Error creating ride: " . mysqli_stmt_error($stmt) . "\n";
        }
    }
}

// Sample payment data
$payments = [
    [
        'ride_id' => isset($ride_ids[0]) ? $ride_ids[0] : 1,
        'user_id' => $user_ids[0],
        'amount' => 1500.00,
        'payment_method' => 'credit_card',
        'payment_status' => 'completed',
        'transaction_id' => 'PAY-' . rand(1000000, 9999999),
        'notes' => 'Monthly subscription payment'
    ],
    [
        'ride_id' => isset($ride_ids[1]) ? $ride_ids[1] : 2,
        'user_id' => $user_ids[0],
        'amount' => 250.00,
        'payment_method' => 'mpesa',
        'payment_status' => 'completed',
        'transaction_id' => 'PAY-' . rand(1000000, 9999999),
        'notes' => 'One-time ride payment'
    ],
    [
        'ride_id' => isset($ride_ids[2]) ? $ride_ids[2] : 3,
        'user_id' => isset($user_ids[1]) ? $user_ids[1] : $user_ids[0],
        'amount' => 2000.00,
        'payment_method' => 'bank_transfer',
        'payment_status' => 'pending',
        'transaction_id' => 'PAY-' . rand(1000000, 9999999),
        'notes' => 'Quarterly subscription payment - awaiting bank confirmation'
    ],
    [
        'ride_id' => isset($ride_ids[3]) ? $ride_ids[3] : 4,
        'user_id' => isset($user_ids[1]) ? $user_ids[1] : $user_ids[0],
        'amount' => 500.00,
        'payment_method' => 'paypal',
        'payment_status' => 'failed',
        'transaction_id' => 'PAY-' . rand(1000000, 9999999),
        'notes' => 'Payment failed due to insufficient funds'
    ],
    [
        'ride_id' => isset($ride_ids[4]) ? $ride_ids[4] : 5,
        'user_id' => isset($user_ids[2]) ? $user_ids[2] : $user_ids[0],
        'amount' => 1000.00,
        'payment_method' => 'credit_card',
        'payment_status' => 'completed',
        'transaction_id' => 'PAY-' . rand(1000000, 9999999),
        'notes' => 'Monthly subscription payment'
    ]
];

// Insert sample payments
$success_count = 0;
foreach ($payments as $payment) {
    $query = "
        INSERT INTO payments (
            ride_id,
            user_id,
            amount,
            payment_method,
            payment_status,
            transaction_id
        ) VALUES (?, ?, ?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        echo "Prepare failed: " . mysqli_error($conn) . "<br>";
        continue;
    }

    mysqli_stmt_bind_param($stmt, "iidsss", 
        $payment['ride_id'],
        $payment['user_id'],
        $payment['amount'],
        $payment['payment_method'],
        $payment['payment_status'],
        $payment['transaction_id']
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $payment_id = mysqli_insert_id($conn);
        
        // Add history record
        $history_query = "
            INSERT INTO payment_history (
                payment_id,
                status,
                notes
            ) VALUES (?, ?, ?)
        ";
        
        $history_note = "Payment created with status: " . $payment['payment_status'];
        
        $history_stmt = mysqli_prepare($conn, $history_query);
        mysqli_stmt_bind_param($history_stmt, "iss", $payment_id, $payment['payment_status'], $history_note);
        mysqli_stmt_execute($history_stmt);
        
        $success_count++;
    } else {
        echo "Error adding payment: " . mysqli_stmt_error($stmt) . "<br>";
    }
}

echo "Added $success_count sample payments successfully.";
?>
