<?php
session_start();
include("DBConn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Different queries for roles
if ($_SESSION['role'] == 'owner') {
    $sql = "SELECT s.*, c.make, c.model, c.license_plate
            FROM services s
            JOIN cars c ON s.car_id = c.id
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
} else {
    // mechanics and admins can see all service records
    $sql = "SELECT s.*, c.make, c.model, c.license_plate
            FROM services s
            JOIN cars c ON s.car_id = c.id";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Service Records</title>
     <style>
        /* Import Google Font */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

/* Reset and base */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Poppins', sans-serif;
  background-color: #fff6f8; /* soft pink background */
  color: #3b302a;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 100vh;
  padding: 40px;
}

/* Main container */
.container {
  background-color: #ffffff;
  width: 90%;
  max-width: 1100px;
  border-radius: 18px;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
  padding: 30px;
  text-align: center;
  animation: fadeIn 0.5s ease-in-out;
}

/* Title */
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
  border-radius: 12px;
  overflow: hidden;
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

/* Links and buttons */
a {
  text-decoration: none;
  background-color: #f8b8d0;
  color: #3b302a;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.25s ease;
  display: inline-block;
}

a:hover {
  background-color: #e89ab8;
  color: white;
}

/* Message text */
p {
  font-size: 16px;
  margin-top: 20px;
}

/* Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Responsive design */
@media (max-width: 768px) {
  body {
    padding: 20px;
  }

  .container {
    padding: 20px;
  }

  table, th, td {
    font-size: 14px;
  }

  h2 {
    font-size: 22px;
  }

  a {
    padding: 8px 16px;
    font-size: 14px;
  }
}

        </style>
</head>
<body>
<div class="container">
    <h2>📝 Service Records</h2>
    <?php if ($result->num_rows > 0): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>Car</th>
                <th>Service Type</th>
                <th>Service Date</th>
                <th>Mileage</th>
                <th>Notes</th>
                <th>Next Service</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['make']." ".$row['model']." (".$row['license_plate'].")"; ?></td>
                    <td><?php echo $row['service_type']; ?></td>
                    <td><?php echo $row['service_date']; ?></td>
                    <td><?php echo $row['mileage']; ?></td>
                    <td><?php echo $row['notes']; ?></td>
                    <td><?php echo $row['next_service_date']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No service records found.</p>
    <?php endif; ?>
    <p><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
