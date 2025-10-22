<?php
session_start();
include("db_connect.php");

// Only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql = "DELETE FROM service_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: admin_services.php?msg=deleted");
    exit();
}

$sql = "SELECT sr.id, sr.service_type, sr.description, sr.service_date, sr.created_at, 
               c.make, c.model, c.license_plate, 
               o.username AS owner_name, m.username AS mechanic_name
        FROM service_records sr
        JOIN cars c ON sr.car_id = c.id
        JOIN users o ON c.user_id = o.id
        JOIN users m ON sr.mechanic_id = m.id
        ORDER BY sr.service_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Service Records (Admin)</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>🛠 All Service Records (Admin)</h2>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<p style='color:red;'>Record deleted successfully.</p>"; ?>

    <?php if ($result->num_rows > 0): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>Car</th>
                <th>Owner</th>
                <th>Mechanic</th>
                <th>Service Type</th>
                <th>Description</th>
                <th>Service Date</th>
                <th>Recorded At</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['make']." ".$row['model']." (".$row['license_plate'].")"; ?></td>
                    <td><?php echo $row['owner_name']; ?></td>
                    <td><?php echo $row['mechanic_name']; ?></td>
                    <td><?php echo $row['service_type']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td><?php echo $row['service_date']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <a href="edit_service.php?id=<?php echo $row['id']; ?>">✏ Edit</a> | 
                        <a href="admin_services.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this record?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No service records available.</p>
    <?php endif; ?>
    <p><a href="admin_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>