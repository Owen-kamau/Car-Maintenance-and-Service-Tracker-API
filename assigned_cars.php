<?php
session_start();
include("db_connect.php");

// Only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: index.php");
    exit();
}

$sql = "SELECT c.*, u.username AS owner_name
        FROM car_assignments ca
        JOIN cars c ON ca.car_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE ca.mechanic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assigned Cars</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>🔧 My Assigned Cars</h2>
    <?php if ($result->num_rows > 0): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>Make</th>
                <th>Model</th>
                <th>Year</th>
                <th>License Plate</th>
                <th>Owner</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['make']; ?></td>
                    <td><?php echo $row['model']; ?></td>
                    <td><?php echo $row['year']; ?></td>
                    <td><?php echo $row['license_plate']; ?></td>
                    <td><?php echo $row['owner_name']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No cars assigned yet.</p>
    <?php endif; ?>
    <p><a href="mechanic_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
