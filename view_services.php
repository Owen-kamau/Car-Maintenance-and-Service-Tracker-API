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
    <link rel="stylesheet" href="styles.css">
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
