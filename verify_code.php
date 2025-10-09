<?php
session_start();
include("DBConn.php");
include("mail.php"); // ensure this is included so resend works

if (!isset($_SESSION['reset_email'])) {
    die("⚠️ Session expired. Please start again from <a href='forgot_password.php'>Forgot Password</a>.");
}

$email = $_SESSION['reset_email'];

// 🧩 Handle verification form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_code = trim($_POST['code']);

    // Debugging (you can remove these two lines later)
    echo "DEBUG: Checking for email = $email<br>";
    echo "DEBUG: Entered code = $entered_code<br>";

    // ✅ Check if code exists and is still valid
    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email = ? AND code = ? AND expires_at > NOW()");
    $stmt->bind_param("ss", $email, $entered_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        // ✅ Code verified — proceed to reset password
        $_SESSION['code_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        echo "❌ Invalid or expired verification code.";
    }
}

// 🧩 Handle resend request
if (isset($_GET['resend']) && $_GET['resend'] === 'true') {
    $code = rand(100000, 999999);
    $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // Delete old code
    $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $delete->bind_param("s", $email);
    $delete->execute();

    // Insert new code
    $stmt = $conn->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $code, $expires_at);
    $stmt->execute();

    // Send email again
    $subject = "🔁 New Password Reset Verification Code";
    $body = "
        <h2>Password Reset - Car Maintenance Tracker</h2>
        <p>Your new verification code is: <b>$code</b></p>
        <p>This code expires in <b>10 minutes</b>.</p>
    ";
    echo sendMail($email, $subject, $body);

    echo "<p>✅ A new verification code has been sent to your email.</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code - CMTS</title>
</head>
<body>
    <h2>Enter Verification Code</h2>
    <form method="post">
        <label>Verification Code:</label><br>
        <input type="text" name="code" required><br><br>
        <button type="submit">Verify</button>
    </form>
    <br>
    <a href="?resend=true">🔁 resend
