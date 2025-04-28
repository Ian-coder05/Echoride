<?php
require_once('../db.php');
require_once('check_admin.php');

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log request details
error_log("add_payment.php called with POST data: " . json_encode($_POST));

try {
    // Validate required fields
    $required_fields = ['user_id', 'amount', 'payment_method', 'payment_status', 'transaction_id', 'ride_id'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error_msg = "Missing required fields: " . implode(', ', $missing_fields);
        error_log($error_msg);
        throw new Exception($error_msg);
    }
    
    error_log("All required fields present");

    // Start transaction
    mysqli_begin_transaction($conn);

    // Insert payment
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
    
    error_log("Insert query: $query");

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    error_log("Binding parameters: ride_id={$_POST['ride_id']}, user_id={$_POST['user_id']}, amount={$_POST['amount']}, method={$_POST['payment_method']}, status={$_POST['payment_status']}, transaction_id={$_POST['transaction_id']}");
    
    mysqli_stmt_bind_param($stmt, "iidsss", 
        $_POST['ride_id'],
        $_POST['user_id'],
        $_POST['amount'],
        $_POST['payment_method'],
        $_POST['payment_status'],
        $_POST['transaction_id']
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error creating payment: " . mysqli_stmt_error($stmt));
    }
    
    $payment_id = mysqli_insert_id($conn);

    // Add initial history record
    $query = "
        INSERT INTO payment_history (
            payment_id,
            status,
            notes
        ) VALUES (?, ?, ?)
    ";

    $notes = !empty($_POST['notes']) ? $_POST['notes'] : "Payment created";
    $history_note = "Payment created with status: " . $_POST['payment_status'] . ($notes ? ". Notes: $notes" : "");
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed for history: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "iss", $payment_id, $_POST['payment_status'], $history_note);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error creating history record: " . mysqli_stmt_error($stmt));
    }

    // Commit transaction
    mysqli_commit($conn);
    
    $response = [
        'success' => true,
        'id' => $payment_id,
        'message' => 'Payment created successfully'
    ];
    
    error_log("Success response: " . json_encode($response));
    
    // Make sure we're sending a string, not an object
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    error_log("Error in add_payment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
