<?php 
session_start();
include("DBConn.php");
include("mail.php");

// ✅ Initialize
$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error_msg = "❌ All fields are required.";
    } elseif ($password !== $confirm_password) { 
        $error_msg = "❌ Passwords do not match.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error_msg = "❌ Email already exists. Please <a href='index.php'>login</a> or use a different one."; 
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

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

                header("Location: index.php?signup=success");
                exit();
            } else {
                $error_msg = "❌ Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Signup | CMTS</title>
 <style>
/* 🌟 Clean, professional signup form with soft-glass style */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: linear-gradient(135deg, #edf3f8, #eaf0f4);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    padding: 40px 35px;
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    width: 390px;
    animation: fadeIn 0.5s ease-in-out;
    border: 1px solid rgba(200, 200, 200, 0.2);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

h2 {
    text-align: center;
    color: #243447;
    margin-bottom: 25px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

p { 
    text-align: center; 
    margin-top: 15px; 
    font-size: 14px;
    color: #333;
}

a {
    color: #0078d7;
    text-decoration: none;
    font-weight: 500;
}
a:hover { text-decoration: underline; }

.input-group {
    position: relative;
    margin-bottom: 22px;
}

.input-group label {
    display: block;
    margin-bottom: 6px;
    color: #333;
    font-size: 14px;
    font-weight: 500;
}

.input-group input, 
.input-group select {
    width: 100%;
    padding: 10px 40px 10px 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
    background: #f8fafc;
    transition: border-color 0.25s, box-shadow 0.25s;
}

.input-group input:focus, 
.input-group select:focus {
    border-color: #0078d7;
    box-shadow: 0 0 4px rgba(0,120,215,0.3);
    outline: none;
}

/* 👁 Password peek toggle */
.peek-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: #eef3f7;
    border: 1px solid #ccc;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease, transform 0.15s ease;
}

.peek-btn:hover { 
    background: #e0e8ee; 
    transform: translateY(-50%) scale(1.05); 
}

.peek-dot {
    width: 6px;
    height: 6px;
    background: #444;
    border-radius: 50%;
}
.peek-btn.active .peek-dot {
    background: transparent;
    border: 2px solid #444;
    width: 8px;
    height: 8px;
}

/* ✨ Button */
button {
    width: 100%;
    background: linear-gradient(135deg, #0078d7, #0098ff);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s, transform 0.15s;
}
button:hover {
    background: linear-gradient(135deg, #005fc5, #0076ff);
    transform: translateY(-1px);
}

/* 🪶 Message boxes */
.msg {
    text-align: center;
    padding: 10px;
    border-radius: 6px;
    font-size: 14px;
    margin-bottom: 12px;
}
.msg.error { background: #ffe5e5; color: #d93025; }
.msg.success { background: #e7f7e7; color: #188038; }

/* 🧩 Google section */
.google-container {
    text-align: center;
    margin-top: 25px;
}

@media (max-width: 430px) {
    .container {
        width: 90%;
        padding: 30px 25px;
    }
    h2 {
        font-size: 20px;
    }
}
</style> 

<script src="https://accounts.google.com/gsi/client" async defer></script>
</head>

<body>
<div class="container">
    <h2>Sign Up</h2>

    <?php 
        if (!empty($error_msg)) echo "<div class='msg error'>$error_msg</div>"; 
        if (!empty($success_msg)) echo "<div class='msg success'>$success_msg</div>"; 
    ?>

    <form method="post" action="signup.php">
        <div class="input-group">
            <label>Username:</label>
            <input type="text" name="username" placeholder="Enter username" required>
        </div>

        <div class="input-group">
            <label>Email:</label>
            <input type="email" name="email" placeholder="Enter email" required>
        </div>

        <div class="input-group">
            <label>Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter password" required>
            <div class="peek-btn" onclick="togglePeek('password', this)">
                <div class="peek-dot"></div>
            </div>
        </div>

        <div class="input-group">
            <label>Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
            <div class="peek-btn" onclick="togglePeek('confirm_password', this)">
                <div class="peek-dot"></div>
            </div>
        </div>

        <div class="input-group">
            <label>Role:</label>
            <select name="role" required>
                <option value="" disabled selected>Select role</option>
                <option value="owner">Car Owner</option>
                <option value="admin">Admin</option>
                <option value="mechanic">Mechanic</option>
            </select>
        </div>

        <button type="submit" name="signup">Create Account</button>

        <div class="google-container">
            <!-- Google Sign-Up -->
            <div id="g_id_onload"
                data-client_id="231390608595-inktm6l0jjqkpibklja2g9r32caabhec.apps.googleusercontent.com"
                data-login_uri="http://localhost/CMTS/google_signup.php"
                data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                data-type="standard"
                data-shape="rectangular"
                data-theme="outline"
                data-text="signup_with"
                data-size="large">
            </div>
        </div>

        <p>Already have an account? <a href="index.php">Login here</a></p>
    </form>
</div>

<script>
function togglePeek(inputId, btn) {
    const input = document.getElementById(inputId);
    input.type = input.type === "password" ? "text" : "password";
    btn.classList.toggle("active", input.type === "text");
}

function handleGoogleSignIn(response) {
    fetch("google_signup.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ credential: response.credential })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) window.location.href = "index.php?signup=success";
        else alert(data.message);
    });
}
</script>
</body>
</html>
