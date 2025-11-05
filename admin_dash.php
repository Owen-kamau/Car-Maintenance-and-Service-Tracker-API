<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="dashboard-header">
        <div class="container">
            <h1>🚗 Owner Dashboard</h1>
            <nav aria-label="Main navigation">
                <ul class="nav-list">
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <li><a href="edit_users.php">Edit Users</a></li>
                    <li><a href="view_all_cars.php">View All Cars</a></li>
                    <li><a href="assign_car.php">Assign Cars to Mechanics</a></li>
                    <li><a href="view_services.php">Service Reports</a></li>
                    <li><a href="admin_services.php">Admin Service Reports</a></li>
                    <li><a href="edit_services.php">Edit Service Records</a></li>
                    <li><a href="manage_requests.php">Manage Booking Requests</a></li>
                    <li><a href="service_history.php">My Cars' Service History</a></li>
                    <li><a href="CarReg.php">Register a New Car</a></li>
                    <li><a href="View_Cars.php">View My Cars</a></li>
                </ul>
            </nav>
            <p class="logout"><a href="logout.php" class="logout-btn">Logout</a></p>
        </div>
    </header>

    <main class="dashboard-content">
        <div class="container">
            <p>Welcome to your dashboard. Select an option above to manage users, cars, and services.</p>
        </div>
    </main>
</body>
</html>
