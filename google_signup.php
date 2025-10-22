<?php
session_start();
require("DBConn.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['credential'] ?? '';

    if (!$token) {
        die(json_encode(["success" => false, "message" => "No token received."]));
    }

    // Verify Google token
    $clientID = "231390608595-inktm6l0jjqkpibklja2g9r32caabhec.apps.googleusercontent.com";
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;

    $response = file_get_contents($url);
    $googleUser = json_decode($response, true);

    if (!isset($googleUser['email'])) {
        die(json_encode(["success" => false, "message" => "Invalid Google token."]));
    }

    $email = $googleUser['email'];
    $name = $googleUser['name'];

    // ✅ Check if user already exists
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Existing user → log in
        $user = $result->fetch_assoc();
    } else {
        // New user → create account
        $role = 'owner'; // default role (change as needed)
        $stmt = $conn->prepare("INSERT INTO users (username, email, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $role);
        $stmt->execute();

        // Fetch new user info
        $user = [
            "id" => $conn->insert_id,
            "username" => $name,
            "email" => $email,
            "role" => $role
        ];
    }

    // ✅ Start session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $user['role'];

    // ✅ Redirect based on role
    if ($user['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($user['role'] === 'mechanic') {
        header("Location: mechanic_dashboard.php");
    } else {
        header("Location: owner_dashboard.php");
    }

    exit();
}
?>