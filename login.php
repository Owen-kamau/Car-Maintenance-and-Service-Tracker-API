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
    $result = $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $username, $hashedPassword, $role);
        $stmt->fetch();

        // Verify password
    if (password_verify($password, $hashedPassword)) {
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Maintenance Tracker - Login</title>
<<<<<<< HEAD
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="theme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

 <style>
/* ✅ Modern login styling (matches your signup form) */
=======
   <style>
>>>>>>> 1dd91d402eb777fd3671aa2141ce7dd06b5b73e7
body {
    font-family: 'Edu SA Hand', cursive;
    background: linear-gradient(135deg, #fffaf2, #fdeef4); /* cream to pastel pink */
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.container {
    background: #fff8f9; /* soft cream-pink card */
    padding: 40px 35px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(238, 192, 204, 0.4);
    width: 370px;
    text-align: center;
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

h2 {
    color: #c15b7a; /* dusty rose pink */
    margin-bottom: 25px;
    font-weight: 600;
    letter-spacing: 1px;
}

label {
    display: block;
    text-align: left;
    font-size: 15px;
    color: #4b3c2a; /* warm text dark tone */
    margin-bottom: 6px;
}

input[type="email"], 
input[type="password"] {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #f3b5c4; /* soft blush border */
    background-color: #fffaf2;
    font-size: 15px;
    transition: border-color 0.25s, box-shadow 0.25s;
}

input:focus {
    border-color: #ef9aad; /* light rose focus */
    box-shadow: 0 0 6px rgba(239,154,173,0.4);
    outline: none;
}

button {
    width: 100%;
    background: #f4a4b4; /* pastel rose */
    color: #fffaf2;
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
    background: #e68a9e; /* darker pink hover */
    transform: translateY(-1px);
}

p {
    font-size: 14px;
    margin-top: 15px;
    color: #5e4433; /* warm brown for text */
}

a {
    color: #d4798b; /* pink accent link */
    text-decoration: none;
    font-weight: 500;
}

a:hover {
    text-decoration: underline;
}

/* 🌸 Google sign-in button spacing */
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
