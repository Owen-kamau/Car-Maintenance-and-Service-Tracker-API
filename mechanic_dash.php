<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();



}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="stylesheet" href="styles.css">

    <!-- Optional: Google Fonts and simple icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #e0f7fa, #80deea);
            margin: 0;
            padding: 0;
        }

        .dashboard {
            max-width: 600px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #006064;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        ul li {
            margin: 12px 0;
        }

        ul li a {
            text-decoration: none;
            color: #004d40;
            background: #e0f2f1;
            padding: 12px 20px;
            display: block;
            border-radius: 8px;
            font-weight: 500;
            transition: 0.3s;
        }

        ul li a:hover {
            background: #4db6ac;
            color: #fff;
        }

        .logout {
            margin-top: 25px;
        }

        .logout a {
            color: #d32f2f;
            text-decoration: none;
            font-weight: 600;
        }

        .logout a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h2>🚗 Mechanic Dashboard</h2>
        <ul>
            <li><a href="assigned_cars">View Assigned Cars</a></li>
            <li><a href="mech_requests.php">My Assigned Requests</a></li>
            <li><a href="Update_services.php">Update Service Records</a></li>
            <li><a href="upcoming_services.php">Upcoming Services</a></li>
            <li><a href="service_history.php">My Service History</a></li>
            <li><a href="CarReg.php">Register a New Car</a></li>
            <li><a href="View_Cars">View My Cars</a></li>
        </ul>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>
</body>
</html>