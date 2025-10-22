<?php
session_start();
include("db_connect.php");

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_requests.php");
    exit();
}
$request_id = intval($_GET['id']);

// Get mechanics
$mechanics = $conn->query("SELECT id, username FROM users WHERE role='mechanic'");

// Assign mechanic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mechanic_id = $_POST['mechanic_id'];
    $sql = "UPDATE service_requests SET mechanic_id=?, status='approved' WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $mechanic_id, $request_id);
    if ($stmt->execute()) {
        header("Location: manage_requests.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assign Mechanic</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>🛠 Assign Mechanic to Request</h2>
    <form method="post">
        <label>Select Mechanic:</label><br>
        <select name="mechanic_id" required>
            <?php while ($m = $mechanics->fetch_assoc()): ?>
                <option value="<?php echo $m['id']; ?>"><?php echo $m['username']; ?></option>
            <?php endwhile; ?>
        </select><br><br>
        <button type="submit">Assign Mechanic</button>
    </form>
    <p><a href="manage_requests.php">⬅ Back</a></p>
</div>
</body>
</html>