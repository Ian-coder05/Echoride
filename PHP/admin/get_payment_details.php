<?php
require_once('../db.php');
require_once('check_admin.php');

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log request details
error_log("get_payment_details.php called with ID: " . (isset($_GET['id']) ? $_GET['id'] : 'none'));

try {
    if (empty($_GET['id'])) {
        throw new Exception("Payment ID is required");
    }

    // Get payment details
    $query = "
        SELECT 
            p.*,
            u.full_name as user_name,
            u.email as user_email,
            r.pickup as ride_from,
            r.dropoff as ride_to,
            r.distance as ride_distance,
            TIMESTAMPDIFF(MINUTE, r.ride_time, r.end_time) as ride_duration
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN rides r ON p.ride_id = r.id
        WHERE p.id = ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception("Get result failed: " . mysqli_error($conn));
    }
    
    $payment = mysqli_fetch_assoc($result);
    if (!$payment) {
        error_log("Payment not found with ID: " . $_GET['id']);
        throw new Exception("Payment not found");
    }
    
    error_log("Payment found: " . json_encode($payment));

    // Get payment history
    $query = "
        SELECT * FROM payment_history
        WHERE payment_id = ?
        ORDER BY created_at DESC
    ";

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt === false) {
        throw new Exception("Prepare failed for history: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed for history: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception("Get result failed for history: " . mysqli_error($conn));
    }
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }

    // Format the payment data for display
    $payment['payment_method_formatted'] = ucwords(str_replace('_', ' ', $payment['payment_method']));
    $payment['payment_status_formatted'] = ucfirst($payment['payment_status']);
    $payment['amount_formatted'] = 'KES ' . number_format($payment['amount'], 2);
    $payment['date_formatted'] = date('F j, Y, g:i a', strtotime($payment['created_at']));
    
    $response = [
        'payment' => $payment,
        'history' => $history
    ];
    
    // Log the response
    error_log("Sending response with payment details and " . count($history) . " history records");
    
    // Make sure we're sending a string, not an object
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in get_payment_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
