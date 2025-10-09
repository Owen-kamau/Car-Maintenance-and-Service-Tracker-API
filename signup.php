<?php 
session_start();
include("DBConn.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role']; // owner, admin, mechanic

    // ✅ Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        die("❌ All fields are required.");
    }

    // ✅ Check if passwords match
    if ($password !== $confirm_password) {
        die("❌ Passwords do not match. Please re-enter your password.");
    }

    // ✅ Hash password before saving
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "❌ Email already exists. Please <a href='index.php'>login</a> or use a different one."; 
    } else {
        // ✅ Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // ✅ Start session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;

            // ✅ Send Welcome Email
            if (function_exists('sendMail')) {
                $subject = "Welcome to Car Maintenance Tracker Service 🚗";
                $message = "
                    <h2>Hi $username,</h2>
                    <p>🎉 Thank you for signing up at <b>Car Maintenance Tracker Service [CMTS]</b>.</p>
                    <p>You have registered as: <b>$role</b></p>
                    <p>You can now log in and start managing your cars and services that we gladly offer.</p>
                    <hr>
                    <p>Best regards,<br>CMTS Team</p>
                ";
                sendMail($email, $subject, $message);
            }
               // ✅ Redirect to login page after signup
            header("Location: index.php?signup=success");
            exit();
        } else {
            echo "❌ Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $check->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Maintenance Tracker - Signup</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>

        <form method="post">
            <label>Username:</label><br>
            <input type="text" name="username" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <label>Confirm Password:</label><br>
            <input type="password" name="confirm_password" required><br><br>

            <label>Role:</label><br>
            <select name="role" required>
                <option value="owner">Car Owner</option>
                <option value="admin">Admin</option>
                <option value="mechanic">Mechanic</option>
            </select><br><br>

            <button type="submit">Sign Up</button>
        </form>

        <p>Already have an account? <a href="index.php">Login here</a></p>
        <p><a href="forgot_password.php">Forgot your password?</a></p>

    </div>
</body>
</html>
