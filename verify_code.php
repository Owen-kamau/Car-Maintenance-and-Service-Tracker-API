<?php
session_start();
include("DBConn.php");
include("mail.php"); // ensure this is included so resend works

if(!isset($_SESSION['reset_email'])) {
     die("❌ Session expired. Please restart the password reset process. <a href='forgot_password.php'>Try again</a>");
}

$email = $_SESSION['reset_email'];
$error_msg = '';
$success_msg = '';

// 🧩 Handle verification form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $entered_code = trim($_POST['code']);

    // ✅ Check if code exists in HeidiSQL and is still valid
    $stmt = $conn->prepare("SELECT code, expires_at FROM password_resets WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $db_code = $row['code'];
        $expires_at = $row['expires_at'];

     if($entered_code === $db_code) {
        if(strtotime($row['expires_at']) > time()) {
            $_SESSION['code_verified'] = true;

            // Delete code immediately after successful verification
            $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete->bind_param("s", $email);
            $delete->execute();

                header("Location: reset_password.php");
                exit();
            } else {
                $error_msg = "❌ This Code expired. Please request a new one.";
            }
        } else {
            $error_msg = "❌ Invalid verification code.";
        }
    } else {
        $error_msg = "❌ No verification code found. Please request a new one.";
    }
}
// ✅ Handle resend link
if (isset($_GET['resend']) && $_GET['resend'] == '1') {
    $code = rand(100000, 999999);

    // Delete old codes
    $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
    $delete->bind_param("s", $email);
    $delete->execute();

    // Insert new code
    $expires_at = date("Y-m-d H:i:s", time() + 600); // 10 minutes
    $insert = $conn->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
    $insert->bind_param("sss", $email, $code, $expires_at);
    $insert->execute();


    // Send email again
    $subject = "🔁 New Password Reset Verification Code";
    $body = "
        <h2>Password Reset - Car Maintenance Tracker</h2>
        <p>Your new verification code is: <b>$code</b></p>
      <p>This code expires in <b>10 minutes</b>.</p>
    ";
    sendMail($email, $subject, $body);

    $success_msg = "✅ A new verification code has been sent to your email.";
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

    <?php
    if ($error_msg) echo "<p style='color:red;'>$error_msg</p>";
    if ($success_msg) echo "<p style='color:green;'>$success_msg</p>";
    ?>

    <form method="post">
        <label>Verification Code:</label><br>
        <input type="text" name="code" required><br><br>
        <button type="submit">Verify Code</button>
    </form>

    <p><a href="verify_code.php?resend=1">Resend Code</a></p>
</body>
</html>