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
    <style>
/* 🌸 Soft pink-centered layout for Forgot Password */
body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: linear-gradient(135deg, #ffe6f0, #fff5f8);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.container {
    background: #ffffff;
    padding: 40px 35px;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    width: 370px;
    text-align: center;
    animation: fadeIn 0.4s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

h2 {
    color: #b30059;
    margin-bottom: 25px;
    font-weight: 600;
}

label {
    display: block;
    text-align: left;
    font-size: 14px;
    color: #333;
    margin-bottom: 6px;
}

input[type="email"] {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #f5b7c4;
    font-size: 15px;
    transition: border-color 0.25s, box-shadow 0.25s;
}

input:focus {
    border-color: #ff5c8a;
    box-shadow: 0 0 4px rgba(255, 92, 138, 0.4);
    outline: none;
}

button {
    width: 100%;
    background: #ff5c8a;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    font-weight: 500;
    margin-top: 10px;
    transition: background 0.3s, transform 0.15s;
}
button:hover {
    background: #e44b78;
    transform: translateY(-1px);
}

/* ✅ Message styles */
p {
    font-size: 14px;
    margin-top: 10px;
}

p[style*="color:green"] {
    background: #e7f7e7;
    color: #188038;
    padding: 8px;
    border-radius: 8px;
}

p[style*="color:red"] {
    background: #ffe5e5;
    color: #d93025;
    padding: 8px;
    border-radius: 8px;
}
</style>

</head>
<body>
    <div class="container">
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
