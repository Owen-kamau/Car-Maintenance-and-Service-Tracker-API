<?php
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // go back to login if not logged in
    exit();
}

// Redirect based on role
switch ($_SESSION['role']) {
    case 'owner':
        header("Location: owner_dash.php");
        exit();
    case 'mechanic':
        header("Location: mechanic_dash.php");
        exit();
    case 'admin':
        header("Location: admin_dash.php");
        exit();
    default:
        echo "❌ Unknown role. Please contact admin.";
        exit();
}
?>
