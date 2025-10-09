<?php
session_start();
include("DBConn.php");
include("mail.php"); // ensure sendMail() is available

if (!isset($_SESSION['reset_email'])) {
    die("⚠️ Session expired. Please start again from <a href='forgot_password.php'>Forgot Password</a>.");
}

$email = $_SESSION['reset_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_code = trim($_POST['code']);

    echo "<pre>";
    echo "DEBUG: Checking for email = $email\n";
    echo "DEBUG: Entered code = $entered_code\n";

    // Fetch actual code info from database for comparison
    $check = $conn->prepare("SELECT code, expires_at FROM password_resets WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($row = $result->fetch_assoc()) {
        echo "DEBUG: DB Code = {$row['code']}\n";
        echo "DEBUG: Expires At = {$row['expires_at']}\n";
        echo "DEBUG: Current Time = " . date("Y-m-d H:i:s") . "\n";

        // Check code and expiry
        if ($entered_code === $row['code']) {
            if (strtotime($row['expires_at']) > time()) {
                $_SESSION['code_verified'] = true;
                echo "✅ Code verified successfully!";
                header("Location: reset_password.php");
                exit();
            } else {
                echo "❌ Code expired.";
            }
        } else {
            echo "❌ Entered code does not match the one in the database.";
        }
    } else {
        echo "❌ No verification record found for this email.";
    }

    echo "</pre>";
}

// Handle resend request
if (isset($_GET['resend']) && $_GET['resend'] === 'true') {
    $code = rand(100000, 999999);
    $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $delete->bind_param("s", $email);
    $delete->execute();

    $stmt = $conn->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $code, $expires_at);
    $stmt->execute();

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
</body>
</html>
