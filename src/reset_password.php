<?php
session_start();
include("DBConn.php");
include("mail.php"); // must contain sendMail($to, $subject, $body)

// ✅ Initialize variables to prevent warnings
$error_msg = "";
$success_msg = "";
$form_visible = true;

// 🧹 Automatically clean up old brute-force records (older than 1 day)
$conn->query("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL 1 DAY)");

$ip_address = $_SERVER['REMOTE_ADDR'];
$max_attempts = 5;        // allowed attempts
$lockout_time = 15 * 60;  // lock for 15 minutes

// Create a session record if not exists
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Check if user is locked out
if ($_SESSION['attempts'] >= $max_attempts) {
    $elapsed = time() - $_SESSION['last_attempt_time'];
    if ($elapsed < $lockout_time) {
        die("⛔ Too many attempts. Please try again after " . ceil(($lockout_time - $elapsed) / 60) . " minutes.");
    } else {
        // Reset after lockout expires
        $_SESSION['attempts'] = 0;
    }
}
if ($error_msg) {
$stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)");
$stmt->bind_param("ss", $ip_address, $email);
$stmt->execute();
$stmt->close();
}


if (!isset($_SESSION['reset_email']) || !isset($_SESSION['code_verified'])) {
    die("❌ Session expired. Please restart the password reset process.");
}

$email = $_SESSION['reset_email'];
$error_msg = '';
$current_hash = '';
$success_msg = '';
$form_visible = true; // Controls whether to show the form

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recaptcha = $_POST['g-recaptcha-response'];
    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=6Lfuh-UrAAAAALe6PaYKJQEBrFmsDjrpId0zYqWj&response=".$recaptcha);
    $responseKeys = json_decode($response, true);

    if (!$responseKeys["success"]) {
    die("❌ CAPTCHA verification failed. Please confirm you’re not a robot.");
}
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
        $delete = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $delete->bind_param("s", $ip);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">


    <style>

        /* === GLOBAL STYLES === */
            * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI' Arial, sans-serif;
            background: #f3f6fa;
            line-height: 1.5;
            color: #333;
        }
        /* === CONTAINER === */
        .container {
            width: 360px;
            max-width: 380px;
            margin: 80px auto;
            padding: 25px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 14px;
            box-shadow: 0px 3px 10px rgba(0,0,0,0.1);
        }
        /* === HEADINGS & TEXT === */
        h2 {
            text-align: center;
            color: #333;
        }
        p {
            text-align: center;
            margin-bottom: 10px;
        }
        /* === INPUTS === */
        input[type="password"],
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin: 6px 0 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s ease;
        }
        input:focus {
            outline: none;
            border-color: #007bff;
        }
        /* === BUTTONS === */
        button {
            width: 100%;
            padding: 10px;
            background: linear-gradient(90deg, #007BFF, #00C851);
            color: #fff;
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
        button:disabled {
            background: gray;
            cursor: not-allowed;
        }
        /* === PASSWORD STRENGTH === */
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
        /* === REQUIREMENTS LIST === */
        #requirements {
        list-style: none;
        padding: 0;
        font-size: 0.9em;
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid #eee;
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

        :root {
        --ubuntu-orange: #E95420;
        --ubuntu-purple: #772953;
        --input-bg: #f8f9fa;
        --border-color: #ccc;
        }

        /* === PASSWORD TOGGLE === */
        .password-wrapper {
        position: relative;
        width: 100%;
        }
        .password-wrapper input {
        width: 100%;
        padding: 10px 38px 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 15px;
        background: var(--input-bg);
        color: #333;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .password-wrapper input:focus {
        outline: none;
        border-color: var(--ubuntu-orange);
        box-shadow: 0 0 0 2px rgba(233,84,32,0.15);
        }
        .password-wrapper .toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 18px;
        color: var(--ubuntu-purple);
        transition: color 0.3s ease, opacity 0.2s ease;
        }
        .password-wrapper .toggle:hover {
        color: var(--ubuntu-orange);
        opacity: 0.85;
        }
        /* === RESPONSIVE DESIGN === */
        @media (max-width: 420px) {
        .container {
        margin: 40px 15px;
        padding: 20px;
        }
        h2 {
        font-size: 20px;
        }
        button {
        font-size: 15px;
        }
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
    

    <form method="post" onsubmit="return validateRecaptcha();">
        <label>New Password:</label><br>
         <div class="password-wrapper">
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
            <i class="fa-solid fa-eye toggle" id="toggleNew"></i>
        </div>


        <!-- Password Strength Meter -->
        <div id="strength-container">
            <div id="strength-bar"></div>
        </div>
        <div id="strength-text"></div>

        <br>

        <label>Confirm New Password:</label><br>
         <div class="password-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
            <i class="fa-solid fa-eye toggle" id="toggleConfirm"></i>
        </div>
      
        <!-- Add this line (for JS status updates) -->
        <p id="match-status"></p>

        <!-- ✅ Move requirements list here -->
        <ul id="requirements">
            <li id="length">❌ At least 8 characters</li>
            <li id="uppercase">❌ At least one uppercase letter (A-Z)</li>
            <li id="lowercase">❌ At least one lowercase letter (a-z)</li>
            <li id="number">❌ At least one number (0-9)</li>
            <li id="special">❌ At least one special character (!@#$...)</li>
        </ul>

        <br><br>
        <div class="g-recaptcha" data-sitekey="6Lfuh-UrAAAAAHEvnYf4ndorZSQbZkAfur6KT-9O"></div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>

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
    function setupToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);

    toggle.addEventListener("click", () => {
        const type = input.getAttribute("type") === "password" ? "text" : "password";
        input.setAttribute('type', type);
        toggle.classList.toggle("fa-eye");
        toggle.classList.toggle("fa-eye-slash");
    });
}
setupToggle('toggleNew', 'new_password');
setupToggle('toggleConfirm', 'confirm_password');

//Validates the user to tick the recaptcha box
function validateRecaptcha() {
    const response = grecaptcha.getResponse();

    if (response.length === 0) {
        Swal.fire({
            title: "Hold on!",
            text: "Please confirm you’re not a robot before continuing.",
            icon: "warning",
            confirmButtonText: "OK",
            confirmButtonColor: "#007BFF"
        });
        return false; // stop form submission
    }

    return true; // allow form to submit
}
   </script>
</body>
</html>
