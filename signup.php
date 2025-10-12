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
    $role = $_POST['role']; // owner, admin, mechanic

    // ✅ Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error_msg = "❌ All fields are required.";
    }elseif ($password !== $confirm_password) { 
        $error_msg = "❌ Passwords do not match..";
    }else {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error_msg = "❌ Email already exists. Please <a href='index.php'>login</a> or use a different one."; 
    } else {
        // ✅ Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // ✅ Start session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;

            // ✅ Send Welcome Email
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
               // ✅ Redirect to login page after signup
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
        body {
            font-family: 'Ubuntu', sans-serif;
            background: #ECEFF1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            width: 380px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background: #2C6BED;
            color: white;
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 8px;
            margin-top: 15px;
            cursor: pointer;
        }
        button:hover { background: #1B4FCC; }

         /* 🔹 Flex container for Sign Up and Google buttons */
        .button-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .google-container {
            flex: 1;
            text-align: center;
        }

        .g_id_signin {
            display: inline-block;
            transform: scale(1.05);
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        p {
            text-align: center;
        }

        a {
            color: #2C6BED;
            text-decoration: none;
        }
    </style>

    <!-- ✅ Google Identity API -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
</head>
<body>
    <div class="container">
        <h2>Sign Up</h2>

        <?php if (!empty($error_msg)) echo "<p style='color:red;'>$error_msg</p>"; ?>
        <?php if (!empty($success_msg)) echo "<p style='color:green;'>$success_msg</p>"; ?>


        <form method="post">
            <label>Username:</label><br>
            <input type="text" name="username" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <label>Confirm Password:</label><br>
            <input type="password" name="confirm_password" required><br><br>

            <label>Role:</label><br>
            <select name="role" required>
                <option value="owner">Car Owner</option>
                <option value="admin">Admin</option>
                <option value="mechanic">Mechanic</option>
            </select>
             
             <!-- 🔹 Button Row -->
            <div class="button-row"></div>
            <button type="submit" name="signup">Sign Up</button>

            
                <!-- ✅ Google Sign-Up -->
                <div class="google-container">
                    <div id="g_id_onload"
                        data-client_id="231390608595-inktm6l0jjqkpibklja2g9r32caabhec.apps.googleusercontent.com"
                        data-login_uri="http://localhost/CMTS/google_auth.php"
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
            </div>
        </form>

         <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>

    <script>
    function handleGoogleSignIn(response) {
          // Decode Google token & send to backend via AJAX
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
