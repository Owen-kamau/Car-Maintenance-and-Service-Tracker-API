<?php
session_start();
include("DBConn.php");

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['code_verified'])) {
    die("❌ Session expired. Please restart the password reset process.");
}

$email = $_SESSION['reset_email'];
$error_msg = '';
$success_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $error_msg("❌ Passwords do not match.");
    } elseif (strlen($new_password) < 6) {
        $error_msg = "⚠️ Password must be at least 6 characters long.";
    }else{
        //Hash the password for security
        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

         // Update user password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);

    if ($stmt->execute()) {
        // Delete the reset record
        $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete->bind_param("s", $email);
        $delete->execute();

        
        // Destroy session data
        unset($_SESSION['reset_email'], $_SESSION['code_verified']);
        session_destroy();

        $success_msg = "✅ Password updated successfully. You can now <a href='index.php'>login</a>.";
    } else {
        $error_msg = "❌ Error updating password: " . $stmt->error;

    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>Reset Password</h2>

    <?php
    if ($error_msg) echo "<p style='color:red;'>$error_msg</p>";
    if ($success_msg) echo "<p style='color:green;'>$success_msg</p>";
    ?>

    <form method="post">
        <label>New Password:</label><br>
        <input type="password" name="new_password" required><br><br>

        <label>Confirm New Password:</label><br>
        <input type="password" name="confirm_password" required><br><br>

        <button type="submit">Reset Password</button>
    </form>
</div>
</body>
</html>
