<?php
session_start();
include("DBConn.php");
include("mail.php"); // must contain sendMail($to, $subject, $body)


if (!isset($_SESSION['reset_email']) || !isset($_SESSION['code_verified'])) {
    die("❌ Session expired. Please restart the password reset process.");
}

$email = $_SESSION['reset_email'];
$error_msg = '';
$current_hash = '';
$success_msg = '';
$form_visible = true; // Controls whether to show the form

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

 // Fetch current password (old) hash to prevent reuse
    $check = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $current_hash = $user['password'];
    } else {
        $error_msg = "❌ User not found.";
    }

    if (empty($error_msg)) {
        if ($new_password !== $confirm_password) {
            $error_msg = "❌ Passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error_msg = "⚠️ Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $new_password) ||
                  !preg_match('/[a-z]/', $new_password) ||
                  !preg_match('/[0-9]/', $new_password) ||
                  !preg_match('/[\W_]/', $new_password)) {
            $error_msg = "⚠️ Password must include uppercase, lowercase, number, and special character.";
        } elseif (password_verify($new_password, $current_hash)) {
            $error_msg = "⚠️ New password cannot be the same as your previous password.";
        } else {
            // Hash and update new password
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $email);

    if ($stmt->execute()) {
        $stmt->close();

        // Delete the reset record
        $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete->bind_param("s", $email);
        $delete->execute();
        $delete->close();

        // Destroy session data
        unset($_SESSION['reset_email'], $_SESSION['code_verified']);
        session_destroy();

        // ✅ Send confirmation email
    $subject = "Password Change Confirmation";
    $body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Password Change Successful</h2>
            <p>Hello,</p>
            <p>This is to confirm that your password for your account (<b>$email</b>) has been changed successfully.</p>
            <p>If you did not make this change, please <a href='http://localhost/CMTS/forgot_password.php'>reset your password</a> immediately or contact support.</p>
            <br>
            <p>Best regards,<br><b>CMTS Security Team</b></p>
        </body>
        </html>
    ";

    if (function_exists('sendMail')) {
        sendMail($email, $subject, $body);
    }

        $success_msg = "✅ Password updated successfully. You can now <a href='index.php'>login</a>.";
        $form_visible = false; // Hide form after success
    } else {
        $error_msg = "❌ Error updating password: " . $stmt->error;
    }

    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Segoe UI' Arial, sans-serif;
            background: #f3f6fa;
        }
        .container {
            width: 360px;
            margin: 80px auto;
            padding: 25px;
            line-height: 1.5;
            background: white;
            border: 1px solid #ccc;
            border-radius: 14px;
            box-shadow: 0px 3px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        p {
            text-align: center;
        }
        input {
            width: 95%;
            padding: 10px;
            margin: 6px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }
        button {
            width: 100%;
            padding: 10px;
            background: linear-gradient(90deg, #007BFF, #00C851);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s ease, background 0.3s ease;
        }
        button:hover {
            transform: scale(1.03);
            background: linear-gradient(90deg, #0056b3, #009f42);
        }
        #strength-container {
           margin-top: 8px;
            height: 12px;
            width: 100%;
            background: #e0e0e0;
            border-radius: 8px;
            overflow: hidden; 
        }
        #strength-bar {
        height: 100%; 
        width: 0%;
        border-radius: 8px; 
        transition: width 0.4s ease, background 0.4s ease;
        }
        #strength-text { 
        text-align: center;
        font-size: 0.9em; 
        margin-top: 4px;
        font-weight: 500;
        }
        #requirements {
        list-style: none;
        padding: 0;
        font-size: 0.9em;
        margin-top: 10px;
        }
        #requirements li {
        margin-bottom: 4px;
        transition: color 0.3s ease;
        }
        #requirements li.valid {
        color: green;
        }
        #requirements li.invalid {
        color: red;
        }
        button:disabled {
        background: gray;
        cursor: not-allowed;
        }
        .password-wrapper {
        position: relative;
        width: 100%;
        }
        .password-wrapper input {
        width: 100%;
        padding-right: 35px;
        }
        .password-wrapper .toggle {
        position: absolute;
        right: 10px;
        top: 8px;
        cursor: pointer;
        user-select: none;
        font-size: 18px;
        }
        .password-wrapper .toggle:hover {
        opacity: 0.7;
         }
</style>
</head>
<body>
<div class="container">
    <h2>Reset Password</h2>

    <?php
    if ($error_msg) echo "<p style='color:red;'>$error_msg</p>";
    if ($success_msg) echo "<p style='color:green;'>$success_msg</p>";

    if ($form_visible): ?> <!-- Show form only if visible -->
    

    <form method="post">
        <label>New Password:</label><br>
         <div class="password-wrapper">
            <input type="password" id="new_password" name="new_password" required><br><br>
            <span class="toggle" onclick="togglePassword('new_password', this)">👁️</span>
         </div>

        <!-- Password Strength Meter -->
        <div id="strength-container">
            <div id="strength-bar"></div>
        </div>
        <div id="strength-text"></div>


         <!-- ✅ Live Password Requirements -->
        <ul id="requirements">
            <li id="length">❌ At least 8 characters</li>
            <li id="uppercase">❌ At least one uppercase letter (A-Z)</li>
            <li id="lowercase">❌ At least one lowercase letter (a-z)</li>
            <li id="number">❌ At least one number (0-9)</li>
            <li id="special">❌ At least one special character (!@#$...)</li>
        </ul>

        <br>

        <label>Confirm New Password:</label><br>
         <div class="password-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" required><br><br>
            <div id="match-status" style="text-align:center; font-size:0.9em; margin-top:-10px;"></div>
            <span class="toggle" onclick="togglePassword('confirm_password', this)">👁️</span>
        </div>

        <br><br>
        <button type="submit" id="submitBtn" disabled>Reset Password</button>
    </form>
    <?php endif; ?>
</div>

<!-- ✅ SweetAlert for success -->
<?php if ($success_msg): ?>
<script>
    Swal.fire({
        title: 'Success!',
        text: 'Your password has been updated successfully. Redirecting to login...',
        icon: 'success',
        showConfirmButton: false,
        timer: 4000
    });
    //  Auto Redirect to login after 5 seconds
    setTimeout(() => window.location.href = 'index.php', 5000);

</script>
<?php endif; ?>

<!-- ✅Animated Password Strength Meter -->
<script>
const passwordInput = document.getElementById('new_password');
const bar = document.getElementById('strength-bar');
const text = document.getElementById('strength-text');
const submitBtn = document.getElementById('submitBtn');
const confirmInput = document.getElementById('confirm_password');

const requirements = {
    length: document.getElementById('length'),
    uppercase: document.getElementById('uppercase'),
    lowercase: document.getElementById('lowercase'),
    number: document.getElementById('number'),
    special: document.getElementById('special')
};


passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    let strength = 0;

    //validate each rule
    const rules = {
       length: val.length >= 8,
       uppercase: /[A-Z]/.test(val),
       lowercase: /[a-z]/.test(val),
       number: /[0-9]/.test(val),
       special: /[\W_]/.test(val)
    };

    // Update checklist display
    Object.keys(rules).forEach(rule => {
        if (rules[rule]) {
            requirements[rule].classList.add('valid');
            requirements[rule].classList.remove('invalid');
            requirements[rule].textContent = '✅ ' + requirements[rule].textContent.replace('❌ ', '');
        } else {
            requirements[rule].classList.add('invalid');
            requirements[rule].classList.remove('valid');
            if (!requirements[rule].textContent.includes('❌'))
                requirements[rule].textContent = requirements[rule].textContent.replace('✅ ', '❌ ');
        }
    });

    // Count how many passed rules
    strength = Object.values(rules).filter(Boolean).length;

    const gradients = [
        'linear-gradient(90deg, #ff0000, #ff4d00)', // Very Weak
        'linear-gradient(90deg, #ff6600, #ffaa00)', // Weak
        'linear-gradient(90deg, #ffd500, #ffee00)', // Fair
        'linear-gradient(90deg, #c6ff00, #76ff03)', // Strong
        'linear-gradient(90deg, #00C851, #007E33)'  // Very Strong
    ];

    const labels = ["Very Weak", "Weak", "Fair", "Strong", "Very Strong"];

    bar.style.width = (strength * 20) + "%";
    bar.style.background = gradients[strength - 1] || "transparent";
    text.innerText = val.length === 0 ? "" : (labels[strength - 1] || "Very Weak");

    text.style.color = strength >= 4 ? "green" : strength >= 2 ? "orange" : "red";

        //Enable button if all rules are valid
    submitBtn.disabled = !(strength === 5 && passwordInput.value === confirmInput.value);
});
const matchStatus = document.getElementById('match-status');

confirmInput.addEventListener('input', () => {
    if (passwordInput.value === confirmInput.value && passwordInput.value.length > 0) {
        matchStatus.textContent = "✅ Passwords match";
        matchStatus.style.color = "green";
        submitBtn.disabled = false;
    } else {
        matchStatus.textContent = "❌ Passwords do not match";
        matchStatus.style.color = "red";
        submitBtn.disabled = true;
    }
});

     //Toggle password visibility
    function togglePassword(fieldId, icon) {
    const input = document.getElementById(fieldId);
    if (input.type === "password") {
        input.type = "text";
        icon.textContent = "🙈";
    } else {
        input.type = "password";
        icon.textContent = "👁️";
    }
}
   </script>
</body>
</html>
