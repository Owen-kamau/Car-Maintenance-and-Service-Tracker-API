<?php
session_start();
include("DBConn.php");

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$sql = "SELECT c.*, u.username, u.email 
        FROM cars c 
        JOIN users u ON c.user_id = u.id
        ORDER BY c.id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Cars</title>
    <style>
        /* Import Google Font */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

/* Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  background-color: #fff5f8; /* soft pink background */
  color: #3b302a;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 100vh;
  padding: 40px;
}

/* Container */
.container {
  background-color: #ffffff;
  width: 90%;
  max-width: 1000px;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  padding: 30px;
  text-align: center;
}

/* Heading */
.container h2 {
  color: #d67ca8; /* soft rose pink */
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 25px;
}

/* Table styling */
table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 25px;
}

th, td {
  padding: 12px 15px;
  text-align: left;
}

th {
  background-color: #fce4ec;
  color: #5e4450;
  font-weight: 600;
  border-bottom: 2px solid #f8b8d0;
}

td {
  background-color: #fff;
  border-bottom: 1px solid #f2c9da;
  transition: background 0.2s ease;
}

tr:hover td {
  background-color: #fff0f5;
}

/* Back link */
a {
  text-decoration: none;
  background-color: #f8b8d0;
  color: #3b302a;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
}

a:hover {
  background-color: #e89ab8;
  color: white;
}

/* Responsive */
@media (max-width: 768px) {
  .container {
    padding: 20px;
  }
  
  table, th, td {
    font-size: 14px;
  }
  
  h2 {
    font-size: 22px;
  }
}

        </style>
</head>
<body>
<div class="container">
    <h2>🚗 All Registered Cars</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Owner</th>
            <th>Email</th>
            <th>Make</th>
            <th>Model</th>
            <th>Year</th>
            <th>License Plate</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['username']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['make']; ?></td>
                <td><?php echo $row['model']; ?></td>
                <td><?php echo $row['year']; ?></td>
                <td><?php echo $row['license_plate']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <p><a href="admin_dash.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
