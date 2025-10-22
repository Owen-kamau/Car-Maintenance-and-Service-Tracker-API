<?php
session_start();
include("DBConn.php");
include("mail.php"); // ensures sendMail() is available


// Initialize variables
$email = '';
$success_msg = '';
$error_msg = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    //  ✅ Only read the value if it exists
    $email = isset($_POST['reset_email'])  ? trim($_POST['reset_email']) : '';

    if (!empty($email)) {
        // Check if user exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Generate code
        $code = rand(100000, 999999);
        $expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Delete old codes for the given email
        $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete->bind_param("s", $email);
        $delete->execute();

        // Insert new code
        $insert = $conn->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $email, $code, $expires_at);
        $insert->execute();

        // Send verification code via email
        $subject = "Password Reset Code - CMTS";
        $body = "<p>Your verification code is: <b>$code</b></p><p> This Code expires in 10 minutes.</p>";

        if(sendMail($email, $subject, $body)){
            $_SESSION['reset_email'] = $email;
            header("Location: verify_code.php");  // Redirect to verification page
            exit();
        } else {
             $error_msg = "❌ Failed to send email. Please check your email settings.";
        }
    } else {
            $error_msg = "❌ No user found with that email.";
        }
} else {
          $error_msg = "❌ Please enter your email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - CMTS</title>
</head>
<body>
    <h2>Forgot password</h2>

    <?php
    if ($success_msg) echo "<p style='color:green;'>$success_msg</p>";
    if ($error_msg) echo "<p style='color:red;'>$error_msg</p>";
    ?>

    <form method="post">
        <label>Enter your registered email:</label><br>
        <input type="email" name="reset_email" required><br><br>
        <button type="submit">Send Verification Code</button>
    </form>
</body>
</html>
