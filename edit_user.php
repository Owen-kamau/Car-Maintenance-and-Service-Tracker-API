<?php
session_start();
include("db_connect.php");

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = intval($_GET['id']);
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $role, $user_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>Edit User: <?php echo $user['username']; ?></h2>
    <form method="post">
        <label>Role:</label>
        <select name="role" required>
            <option value="owner" <?php if ($user['role']=='owner') echo 'selected'; ?>>Owner</option>
            <option value="mechanic" <?php if ($user['role']=='mechanic') echo 'selected'; ?>>Mechanic</option>
            <option value="admin" <?php if ($user['role']=='admin') echo 'selected'; ?>>Admin</option>
        </select><br><br>
        <button type="submit">Update Role</button>
    </form>
    <p><a href="manage_users.php">⬅ Back to Manage Users</a></p>
</div>
</body>
</html>
