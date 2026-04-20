<?php
// index.php
session_start();
require 'db_connect.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if user exists
    $sql = "SELECT user_id, username, user_type FROM user WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Start Session
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['user_type'] = $row['user_type'];

        // Route based on Subclass
        if ($row['user_type'] == 'student') {
            header("Location: student_dashboard.php");
        } elseif ($row['user_type'] == 'counsellor') {
            header("Location: counsellor_dashboard.php");
        } elseif ($row['user_type'] == 'admin') {
            header("Location: admin_dashboard.php");
        }
        exit();
    } else {
        $error_message = "Email not found in the system.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MindPace Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #faf8f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: center; width: 300px; }
        input { width: 90%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #2980b9; }
        .error { color: red; font-size: 0.9em; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>MindPace Login</h2>
    <?php if($error_message) echo "<p class='error'>$error_message</p>"; ?>
    <form method="POST" action="">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Log In</button>
    </form>
</div>
</body>
</html>