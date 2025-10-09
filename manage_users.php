<?php
session_start();
include("db_connect.php");

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle delete user
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}

// Get all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>👥 Manage Users</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['username']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo ucfirst($row['role']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td>
                    <a href="edit_user.php?id=<?php echo $row['id']; ?>">✏ Edit</a> | 
                    <a href="manage_users.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this user?');">🗑 Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <p><a href="admin_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
