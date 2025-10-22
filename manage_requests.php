<?php
session_start();
include("db_connect.php");

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
    <link rel="stylesheet" href="styles.css">
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
    <p><a href="admin_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
