<?php
session_start();
include("DBConn.php"); // Your DB connection file

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

//for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    //validate input
    if (empty($email) || empty($password)) {
        die("❌ Please enter both email and password.");
    }

    // Fetch user by email
    $sql = "SELECT id, username, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->store_result();

    if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $username, $hashedPassword, $role);
    $stmt->fetch();

        // Verify password (hashed with password_hash)
        if (password_verify($password, $hashedPassword)) {

            // ✅ Start session and store user data
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role']    = $row['role'];

            // Redirect based on role
            if ($role == "admin") {
                header("Location: admin_dash.php");
            } elseif ($role == "mechanic") {
                header("Location: mechanic_dash.php");
            } else {
                header("Location: owner_dash.php");
            }
            exit();
                 } else {
            echo  "❌ Invalid password!";
        }
    } else {
        echo "❌ No account found with that email!";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Maintenance Tracker - Login</title>
    <link rel="stylesheet" href="style.css"> <!-- External CSS -->
</head>
<body>
    <div class="container">
        <h2>Login to CMTS</h2>

        <?php if (!empty($error)): ?>
            <p style="color:red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="post">
            <label>Email:</label><br>
            <input type="email" name="email" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="signup.php">Create one here</a></p>
        <p><a href="forgot_password.php">Forgot your password?</a></p>

    </div>
</body>
</html>
