<?php
session_start();
include("DBConn.php");

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Approve/Reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        $status = 'approved';
    } elseif ($action == 'reject') {
        $status = 'rejected';
    }
    $sql = "UPDATE service_requests SET status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    header("Location: manage_requests.php");
    exit();
}

// Fetch requests
$sql = "SELECT sr.*, c.make, c.model, c.license_plate, u.username AS owner_name
        FROM service_requests sr
        JOIN cars c ON sr.car_id = c.id
        JOIN users u ON sr.owner_id = u.id
        ORDER BY sr.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Service Requests</title>
    <style>
        /* Import Google Font */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

/* Reset defaults */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* Base styling */
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
  max-width: 1150px;
  border-radius: 18px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  padding: 30px;
  text-align: center;
  animation: fadeIn 0.5s ease-in-out;
}

/* Header title */
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

/* Status color coding */
td:nth-child(6) {
  font-weight: 500;
  text-transform: capitalize;
}

td:nth-child(6):contains("approved") {
  color: #388e3c;
}

td:nth-child(6):contains("rejected") {
  color: #d32f2f;
}

td:nth-child(6):contains("pending") {
  color: #f57c00;
}

/* Action buttons */
td a {
  display: inline-block;
  text-decoration: none;
  color: #3b302a;
  padding: 6px 14px;
  margin: 3px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 14px;
  transition: all 0.25s ease;
}

td a[href*="approve"] {
  background-color: #b2dfdb;
}

td a[href*="approve"]:hover {
  background-color: #80cbc4;
  color: #fff;
}

td a[href*="reject"] {
  background-color: #ffcdd2;
}

td a[href*="reject"]:hover {
  background-color: #ef9a9a;
  color: #fff;
}

td a[href*="assign_mechanic"] {
  background-color: #f8b8d0;
}

td a[href*="assign_mechanic"]:hover {
  background-color: #e89ab8;
  color: #fff;
}

/* Back button */
p a {
  background-color: #f8b8d0;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.25s ease;
}

p a:hover {
  background-color: #e89ab8;
  color: #fff;
}

/* Fade-in animation */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Responsive styling */
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

  td a {
    font-size: 12px;
    padding: 5px 10px;
  }
}

        </style>
</head>
<body>
<div class="container">
    <h2>⚙ Manage Service Requests</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>Car</th>
            <th>Owner</th>
            <th>Request Type</th>
            <th>Description</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['make']." ".$row['model']." (".$row['license_plate'].")"; ?></td>
                <td><?php echo $row['owner_name']; ?></td>
                <td><?php echo $row['request_type']; ?></td>
                <td><?php echo $row['description']; ?></td>
                <td><?php echo $row['request_date']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td>
                    <?php if ($row['status'] == 'pending'): ?>
                        <a href="manage_requests.php?action=approve&id=<?php echo $row['id']; ?>">✅ Approve</a> |
                        <a href="manage_requests.php?action=reject&id=<?php echo $row['id']; ?>">❌ Reject</a>
                    <?php elseif ($row['status'] == 'approved'): ?>
                        <a href="assign_mechanic.php?id=<?php echo $row['id']; ?>">🛠 Assign Mechanic</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <p><a href="admin_dash.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
