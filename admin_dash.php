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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@400;500;600&display=swap');

        :root {
            --baby-pink: #ffe4ec;
            --light-pink: #f8bbd0;
            --medium-pink: #f48fb1;
            --deep-pink: #c2185b;
            --text-dark: #3b302a;
            --white: #fff8fa;
        }

        body {
            font-family: 'Edu SA Hand', cursive;
            margin: 0;
            background-color: var(--white);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* ===== Sidebar ===== */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--light-pink), var(--baby-pink));
            padding: 30px 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 2px solid var(--medium-pink);
        }

        .sidebar h2 {
            text-align: center;
            color: var(--deep-pink);
            font-size: 1.8em;
            margin-bottom: 30px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            margin: 12px 0;
        }

        .sidebar ul li a {
            display: block;
            text-decoration: none;
            color: var(--text-dark);
            background-color: #ffffffa8;
            padding: 10px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-align: center;
            font-weight: 600;
        }

        .sidebar ul li a:hover {
            background-color: var(--medium-pink);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(244,143,177,0.4);
        }

        .sidebar .logout {
            margin-top: 40px;
            text-align: center;
        }

        .sidebar .logout a {
            display: inline-block;
            background-color: var(--deep-pink);
            color: white;
            padding: 10px 25px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .sidebar .logout a:hover {
            background-color: #ad1457;
        }

        /* ===== Main Content ===== */
        .main-content {
            flex: 1;
            padding: 60px;
            background-color: white;
            border-left: 1px solid var(--light-pink);
        }

        .main-content h1 {
            color: var(--deep-pink);
            font-size: 2em;
            margin-bottom: 20px;
        }

        .main-content p {
            color: var(--text-dark);
            font-size: 1.1em;
        }

        /* ===== Responsive ===== */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                flex-direction: row;
                justify-content: space-around;
                padding: 10px;
                border-right: none;
                border-bottom: 2px solid var(--medium-pink);
            }

            .sidebar h2 {
                display: none;
            }

            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<<<<<<< HEAD
    <div class="sidebar">
        <div>
            <h2>🚗 Owner Menu</h2>
            <ul>
                <li><a href="manage_users">Manage Users</a></li>
                <li><a href="edit_users">Edit Users</a></li>
                <li><a href="view_all_cars">View All Cars</a></li>
                <li><a href="assign_car.php">Assign Cars to Mechanics</a></li>
                <li><a href="view_services.php">Service Reports</a></li>
                <li><a href="admin_services.php">Admin Service Reports</a></li>
                <li><a href="edit_services.php">Edit Service Records</a></li>
                <li><a href="manage_requests.php">Manage Requests</a></li>
                <li><a href="service_history.php">Service History</a></li>
                <li><a href="CarReg.php">Register New Car</a></li>
                <li><a href="View_Cars">View My Cars</a></li>
            </ul>
        </div>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1>Welcome to the Owner Dashboard</h1>
        <p>Here you can manage users, view cars, track services, and oversee all maintenance records — all in one place!</p>
    </div>
=======
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
>>>>>>> e0882c6fd3b8aebe617d7c937ac58863705c7058
</body>
</html>
