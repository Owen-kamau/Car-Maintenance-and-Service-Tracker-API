<?php
session_start();
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
        <h2>🚗 Mechanic Dashboard</h2>
        <ul>
            <li><a href="assigned_cars">View Assigned Cars</a></li>
            <li><a href="mech_requests.php">My Assigned Requests</a></li>
            <li><a href="Update_services.php">Update Service Records</a></li>
            <li><a href="upcoming_services.php">Upcoming services</a></li>
            <li><a href="service_history.php">My Service History</a></li>
            <li><a href="CarReg.php">Register a new car</a></li>
            <li><a href="View_Cars">View my cars</a></li>
        </ul>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>
