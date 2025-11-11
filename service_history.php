<?php
session_start();
include("DBConn.php");

// Only owners, mechanics, admins can view their own cars' service history
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// If owner → show only their cars
// If mechanic/admin → they can also see their own cars (from `cars` table)
$sql = "SELECT sr.*, c.make, c.model, c.license_plate, u.username AS mechanic_name
        FROM service_records sr
        JOIN cars c ON sr.car_id = c.id
        LEFT JOIN users u ON sr.mechanic_id = u.id
        WHERE c.user_id = ?
        ORDER BY sr.service_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Service History</title>
    <style> 
    /* Import Google Font */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

/* Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* General body styling */
body {
  font-family: 'Poppins', sans-serif;
  background-color: #fff6f8; /* soft pink tone */
  color: #3b302a;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  min-height: 100vh;
  padding: 40px;
}

/* Main content container */
.container {
  background-color: #ffffff;
  width: 90%;
  max-width: 1100px;
  border-radius: 18px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  padding: 30px;
  text-align: center;
  animation: fadeIn 0.5s ease-in-out;
}

/* Page title */
.container h2 {
  color: #d67ca8; /* soft rose pink */
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 25px;
}

/* Table layout */
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
  font-size: 15px;
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
  transition: background 0.25s ease;
}

tr:hover td {
  background-color: #fff0f5;
}

/* "No records" message */
td[colspan="6"] {
  text-align: center;
  font-style: italic;
  color: #7a6d69;
  background-color: #fff9fb;
}

/* Link button styling */
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

/* Fade animation for smooth load */
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
    font-size: 13px;
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
    <h2>📜 My Service History</h2>

    <table border="1" cellpadding="10">
        <tr>
            <th>Car</th>
            <th>Service Type</th>
            <th>Date</th>
            <th>Notes</th>
            <th>Cost</th>
            <th>Mechanic</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['make']." ".$row['model']." (".$row['license_plate'].")"; ?></td>
                    <td><?php echo $row['service_type']; ?></td>
                    <td><?php echo $row['service_date']; ?></td>
                    <td><?php echo $row['notes']; ?></td>
                    <td><?php echo $row['cost'] ? "KSh " . number_format($row['cost'], 2) : "-"; ?></td>
                    <td><?php echo $row['mechanic_name'] ? $row['mechanic_name'] : "N/A"; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No service history found.</td></tr>
        <?php endif; ?>
    </table>

    <p><a href="<?php echo $role . '_dash.php'; ?>">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
