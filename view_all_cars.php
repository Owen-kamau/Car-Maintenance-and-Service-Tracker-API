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
    <link rel="stylesheet" href="styles.css">
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
    <p><a href="admin_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
