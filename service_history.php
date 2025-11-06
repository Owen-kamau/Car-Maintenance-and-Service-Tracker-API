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
    <link rel="stylesheet" href="styles.css">
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

    <p><a href="<?php echo $role . '_dashboard.php'; ?>">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
