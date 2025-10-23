<?php
session_start();
include(__DIR__ . '/db_connect.php');

$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : null;
$role = $isLoggedIn ? $_SESSION['role'] : 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMTS | Car Maintenance & Service Tracker</title>
<style>
    body {
        margin: 0;
        font-family: 'Poppins', sans-serif;
        background: radial-gradient(circle at top, #1a1a1a, #0d0d0d);
        color: #e5e5e5;
    }

    header {
        background: linear-gradient(90deg, #111, #222);
        color: #f8f8f8;
        padding: 20px;
        text-align: center;
        border-bottom: 2px solid #555;
        font-family: 'Orbitron', sans-serif;
        letter-spacing: 1px;
    }

    nav {
        background-color: #1f1f1f;
        display: flex;
        justify-content: center;
        gap: 30px;
        padding: 15px;
        border-bottom: 1px solid #333;
    }

    nav a {
        color: #d6d6d6;
        text-decoration: none;
        font-weight: bold;
        transition: color 0.3s;
    }

    nav a:hover {
        color: #00b4d8;
    }

    main {
        text-align: center;
        padding: 50px 20px;
    }

    .dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        max-width: 1000px;
        margin: 0 auto;
    }

    .card {
        background: #1b1b1b;
        border: 1px solid #333;
        border-radius: 12px;
        padding: 30px 20px;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
    }

    .card h3 {
        color: #f4d35e;
        margin-bottom: 10px;
    }

    .card p {
        color: #bbb;
        font-size: 0.95em;
    }

    .btn {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 20px;
        border-radius: 8px;
        background-color: #00b4d8;
        color: black;
        text-decoration: none;
        font-weight: bold;
    }

    .btn:hover {
        background-color: #0096c7;
    }

    footer {
        background: #111;
        text-align: center;
        padding: 20px;
        color: #888;
        border-top: 1px solid #333;
        position: fixed;
        width: 100%;
        bottom: 0;
    }

    .car-banner {
        margin-top: 40px;
        max-width: 600px;
        width: 100%;
        border-radius: 10px;
        border: 1px solid #333;
        box-shadow: 0 0 15px rgba(255,255,255,0.1);
    }
</style>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&family=Poppins&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <h1>🚗 Car Maintenance & Tracking System</h1>
    <p>Where vintage class meets modern tech</p>
</header>

<nav>
    <a href="index.php">Home</a>
    <?php if ($isLoggedIn): ?>
        <?php if ($role === 'admin'): ?>
            <a href="admin_dashboard.php">Admin Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="reports.php">Reports</a>
        <?php elseif ($role === 'mechanic'): ?>
            <a href="mechanic_dashboard.php">Mechanic Dashboard</a>
            <a href="assigned_cars.php">My Cars</a>
        <?php elseif ($role === 'owner'): ?>
            <a href="owner_dashboard.php">Owner Dashboard</a>
            <a href="upcoming_services.php">Upcoming Services</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <a href="login.php">Login</a>
        <a href="signup.php">Sign Up</a>
    <?php endif; ?>
</nav>

<main>
    <?php if ($isLoggedIn): ?>
        <h2>Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
        <p>Your role: <strong><?php echo ucfirst($role); ?></strong></p>

        <div class="dashboard">
            <?php if ($role === 'admin'): ?>
                <div class="card">
                    <h3>Manage Users</h3>
                    <p>View and manage system accounts.</p>
                    <a href="manage_users.php" class="btn">Open</a>
                </div>
                <div class="card">
                    <h3>Reports</h3>
                    <p>Check maintenance and performance data.</p>
                    <a href="reports.php" class="btn">View</a>
                </div>
            <?php elseif ($role === 'mechanic'): ?>
                <div class="card">
                    <h3>My Assigned Cars</h3>
                    <p>See which cars are under your care.</p>
                    <a href="assigned_cars.php" class="btn">View Cars</a>
                </div>
                <div class="card">
                    <h3>Upcoming Services</h3>
                    <p>Check scheduled maintenance for vehicles.</p>
                    <a href="upcoming_services.php" class="btn">Open</a>
                </div>
            <?php elseif ($role === 'owner'): ?>
                <div class="card">
                    <h3>My Cars</h3>
                    <p>View and manage your vehicles.</p>
                    <a href="owner_dashboard.php" class="btn">Open</a>
                </div>
                <div class="card">
                    <h3>Upcoming Services</h3>
                    <p>Check when your cars need attention.</p>
                    <a href="upcoming_services.php" class="btn">View</a>
                </div>
            <?php endif; ?>
        </div>

        <img src="https://cdn.pixabay.com/photo/2017/06/05/14/58/classic-car-2378640_1280.jpg" class="car-banner" alt="Classic Car">
    <?php else: ?>
        <h2>Welcome to CMTS</h2>
        <p>Track and manage your car maintenance effortlessly.</p>
        <a href="signup.php" class="btn">Get Started</a>
        <img src="https://cdn.pixabay.com/photo/2016/03/09/09/16/car-1245741_1280.jpg" class="car-banner" alt="Vintage Car">
    <?php endif; ?>
</main>

<footer>
    &copy; <?php echo date('Y'); ?> Car Maintenance & Tracking System. All rights reserved.
</footer>

</body>
</html>
