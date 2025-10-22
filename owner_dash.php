<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Owner Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>🚗 Car Owner Dashboard</h2>
        <ul>
            <li><a href="CarReg.php">Register a new car</a></li>
            <li><a href="View_Cars">View my cars</a></li>
            <li><a href="service_booking">Book my cars a Service</a></li>
            <li><a href="view_services.php">Track my services</a></li>
            <li><a href="service_history.php">My Service History</a></li>
            <li><a href="upcoming_services.php">Upcoming Service Reminders</a></li>
        </ul>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
