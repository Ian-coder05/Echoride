/* php/forgot-password.php */
<?php
include 'config.php';

$email = $_POST['email'];

$sql = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    // Send reset email (mock-up for now)
    echo "A password reset link has been sent to your email.";
} else {
    echo "No user found with that email.";
}
?>
