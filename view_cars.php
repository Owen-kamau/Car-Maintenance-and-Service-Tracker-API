<?php
session_start();
include("DBConn.php");

// Only owners
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM cars WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Cars</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>🚙 My Registered Cars</h2>
        <?php if ($result->num_rows > 0): ?>
            <table border="1" cellpadding="10">
                <tr>
                    <th>Make</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>License Plate</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['make']; ?></td>
                        <td><?php echo $row['model']; ?></td>
                        <td><?php echo $row['year']; ?></td>
                        <td><?php echo $row['license_plate']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No cars registered yet.</p>
        <?php endif; ?>
  <p><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
    </div>
</body>
</html>
