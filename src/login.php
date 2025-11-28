<?php
session_start();
require("DBConn.php"); // Your DB connection file

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    //validate input
    if (empty($email) || empty($password)) {
        die("❌ Please enter both email and password.");
    }

    // Fetch user by email
    $sql = "SELECT id, username, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $username, $hashedPassword, $role);
        $stmt->fetch();

        // Check that $hashedPassword is not null before verifying
        if ($hashedPassword !== null && password_verify($password, $hashedPassword)) {
            // ✅ Store session data
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;

            // Redirect by role
            switch ($_SESSION['role']) {
                case 'admin':
                    header("Location: admin_dash.php");
                    break;
                case 'mechanic':
                    header("Location: mechanic_dash.php");
                    break;
                case 'owner':
                    header("Location: owner_dash.php");
                    break;
                default:
                    echo "❌ Unknown role. Please contact admin.";
            }
            exit();
        } else {
            echo "❌ Invalid password!";
        }
    } else {
        echo "❌ No account found with that email!";
    }

    $stmt->close();
    $conn->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Maintenance Tracker - Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="theme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

 <style>
body {
    background-color: #0f0f0f;
    color: #eee;
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
}
.container {
    background-color: #1e1e1e;
    padding: 40px 35px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(255, 77, 0, 0.15);
    width: 370px;
    text-align: center;
}
h2 {
    color: #ff4d00;
    margin-bottom: 25px;
    font-weight: 600;
    letter-spacing: 1px;
}
label {
    display: block;
    text-align: left;
    font-size: 15px;
    color: #eee;
    margin-bottom: 6px;
}
input[type="email"], 
input[type="password"] {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #333;
    background-color: #222;
    color: #eee;
    font-size: 15px;
    transition: border-color 0.25s, box-shadow 0.25s;
}
input:focus {
    border-color: #ff4d00;
    box-shadow: 0 0 6px rgba(255,77,0,0.3);
    outline: none;
}
button {
    width: 100%;
    background: #ff4d00;
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    font-weight: 600;
    margin-top: 10px;
    transition: background 0.3s, transform 0.15s;
}
button:hover {
    background: #ff6600;
    transform: translateY(-1px);
}
p {
    font-size: 14px;
    margin-top: 15px;
    color: #bbb;
}
a {
    color: #ff4d00;
    text-decoration: none;
    font-weight: 500;
}
a:hover {
    text-decoration: underline;
}
.g_id_signin {
    margin-top: 20px;
}
</style>
</head>
<body>
    <div class="container">
        <h2>Login to CMTS</h2>

        <?php if (!empty($error)): ?>
            <p style="color:red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="post">
            <label>Email:</label><br>
            <input type="email" name="email" required><br><br>

            <label>Password:</label><br>
            <input type="password" name="password" required><br><br>

            <button type="submit">Login</button>
        </form>

        <!-- Include Google Sign-In script -->
        <script src="https://accounts.google.com/gsi/client" async defer></script>

        <!-- Google Sign-In setup -->
        <div id="g_id_onload"
            data-client_id="231390608595-inktm6l0jjqkpibklja2g9r32caabhec.apps.googleusercontent.com"
            data-login_uri="http://localhost/CMTS/google_auth.php"
            data-auto_prompt="false">
        </div>

        <!-- Actual log-In button -->
        <div class="g_id_signin"
            data-type="standard"
            data-shape="rectangular"
            data-theme="outline"
            data-text="sign_in_with"
            data-size="large">
        </div>

        <p>Don't have an account? <a href="signup.php">Create one here</a></p>
        <p><a href="forgot_password.php">Forgot your password?</a></p>

    </div>
</body>
</html>