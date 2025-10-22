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
        <h2>🚗 Admin Dashboard</h2>
        <ul>
            <li><a href="manage_users">Manage Users</a></li>
            <li><a href="edit_users">Edit Users</a></li>
            <li><a href="view_all_cars">View All Cars</a></li>
            <li><a href="assign_car.php">Assign Cars to Mechanics</a></li>
            <li><a href="view_services.php">Service Reports</a></li>
            <li><a href="admin_services.php">Admin Service Reports</a></li>
            <li><a href="edit_services.php">Edits service_records Reports</a></li>
            <li><a href="manage_requests.php">Manage Booking request</a></li>
            <li><a href="service_history.php">My Cars' Service History</a></li>
            <li><a href="CarReg.php">Register a new car</a></li>
            <li><a href="View_Cars">View my cars</a></li>
            
        </ul>
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>