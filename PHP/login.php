<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, full_name, is_admin FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hashed_password, $name, $is_admin);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['is_admin'] = $is_admin;
            
            // Set localStorage status through JavaScript
            echo "<script>
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('userName', '" . addslashes($name) . "');
                localStorage.setItem('isAdmin', '" . ($is_admin ? 'true' : 'false') . "');
                window.location.href = '" . ($is_admin ? '../admin/index.html' : '../dashboard.html') . "';
            </script>";
            exit;
        } else {
            echo "Incorrect password.";
        }
    } else {
        echo "No account found with that email.";
    }
    $stmt->close();
    $conn->close();
}
?>

