<?php
require_once('../db.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if users table exists and has records
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
    if (!$result) {
        throw new Exception("Error checking users table: " . mysqli_error($conn));
    }
    
    $row = mysqli_fetch_assoc($result);
    if ($row['count'] < 2) {
        throw new Exception("Not enough users in the database. Please add at least 2 users first.");
    }
    
    // Get admin user ID
    $admin_result = mysqli_query($conn, "SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
    if (!$admin_result || mysqli_num_rows($admin_result) == 0) {
        throw new Exception("No admin user found in the database.");
    }
    $admin = mysqli_fetch_assoc($admin_result);
    $admin_id = $admin['id'];
    
    // Get regular users
    $users_result = mysqli_query($conn, "SELECT id FROM users WHERE is_admin = 0 LIMIT 5");
    if (!$users_result || mysqli_num_rows($users_result) == 0) {
        throw new Exception("No regular users found in the database.");
    }
    
    $user_ids = [];
    while ($user = mysqli_fetch_assoc($users_result)) {
        $user_ids[] = $user['id'];
    }
    
    // Make sure we have at least 2 user IDs
    if (count($user_ids) < 2) {
        // Create a dummy user if needed
        $dummy_query = "INSERT INTO users (full_name, email, password, is_admin) VALUES ('Test User', 'testuser@example.com', 'password', 0)";
        mysqli_query($conn, $dummy_query);
        $user_ids[] = mysqli_insert_id($conn);
        echo "Created a dummy user for testing<br>";
    }
    
    // Get user IDs safely
    $user_id_1 = isset($user_ids[0]) ? $user_ids[0] : $admin_id;
    $user_id_2 = isset($user_ids[1]) ? $user_ids[1] : $user_id_1;
    
    // Sample message data
    $sample_messages = [
        [
            'from_id' => $user_id_1,
            'to_id' => $admin_id,
            'subject' => 'Question about ride sharing',
            'content' => "Hello,\n\nI have a question about the ride sharing feature. How do I set up recurring rides?\n\nThanks,\nUser",
            'message_type' => 'inquiry',
            'status' => 'unread',
            'is_flagged' => 0,
            'reply_to_id' => null
        ],
        [
            'from_id' => $user_id_1,
            'to_id' => $admin_id,
            'subject' => 'App not working properly',
            'content' => "Hi,\n\nI'm having trouble with the app. It keeps crashing when I try to book a ride.\n\nCan you help?\nUser",
            'message_type' => 'support',
            'status' => 'read',
            'is_flagged' => 1,
            'reply_to_id' => null
        ],
        [
            'from_id' => $admin_id,
            'to_id' => $user_id_1,
            'subject' => 'RE: App not working properly',
            'content' => "Hello,\n\nI'm sorry to hear you're having trouble. Could you please provide more details about your device and the app version?\n\nBest regards,\nAdmin",
            'message_type' => 'support',
            'status' => 'unread',
            'is_flagged' => 0,
            'reply_to_id' => 2
        ],
        [
            'from_id' => $user_id_2,
            'to_id' => $admin_id,
            'subject' => 'Feedback on new features',
            'content' => "Hello Admin,\n\nI really like the new features in the latest update. The carbon footprint tracker is awesome!\n\nKeep up the good work,\nUser",
            'message_type' => 'feedback',
            'status' => 'unread',
            'is_flagged' => 0,
            'reply_to_id' => null
        ],
        [
            'from_id' => $user_id_2,
            'to_id' => $admin_id,
            'subject' => 'Driver was late',
            'content' => "Hi,\n\nI had a ride scheduled for 9am but the driver was 20 minutes late. This caused me to be late for an important meeting.\n\nI would like a refund or credit for this ride.\n\nRegards,\nUser",
            'message_type' => 'complaint',
            'status' => 'unread',
            'is_flagged' => 1,
            'reply_to_id' => null
        ],
        [
            'from_id' => $admin_id,
            'to_id' => $user_id_2,
            'subject' => 'Welcome to Echoride',
            'content' => "Hello,\n\nWelcome to Echoride! We're excited to have you on board.\n\nHere are some tips to get started:\n- Complete your profile\n- Add your payment method\n- Book your first ride\n\nIf you have any questions, feel free to reach out.\n\nBest regards,\nEchoride Team",
            'message_type' => 'notification',
            'status' => 'read',
            'is_flagged' => 0,
            'reply_to_id' => null
        ]
    ];
    
    // Insert sample messages
    $insert_count = 0;
    foreach ($sample_messages as $message) {
        $query = "
            INSERT INTO messages (from_id, to_id, subject, content, message_type, status, is_flagged, reply_to_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        
        // Handle null reply_to_id
        $from_id = $message['from_id'];
        $to_id = $message['to_id'];
        $subject = $message['subject'];
        $content = $message['content'];
        $message_type = $message['message_type'];
        $status = $message['status'];
        $is_flagged = $message['is_flagged'];
        $reply_to_id = $message['reply_to_id'];
        
        mysqli_stmt_bind_param(
            $stmt, 
            "iissssii", 
            $from_id, 
            $to_id, 
            $subject, 
            $content, 
            $message_type, 
            $status, 
            $is_flagged, 
            $reply_to_id
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $insert_count++;
        } else {
            echo "Error inserting message: " . mysqli_stmt_error($stmt) . "<br>";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    echo "Added $insert_count sample messages successfully";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
