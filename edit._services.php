<?php
session_start();
include("db_connect.php");

// Only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_services.php");
    exit();
}

$service_id = intval($_GET['id']);

// Fetch service record
$sql = "SELECT * FROM service_records WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    die("Service record not found.");
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_type = $_POST['service_type'];
    $description = $_POST['description'];
    $service_date = $_POST['service_date'];

    $sql = "UPDATE service_records SET service_type=?, description=?, service_date=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $service_type, $description, $service_date, $service_id);

    if ($stmt->execute()) {
        header("Location: admin_services.php?msg=updated");
        exit();
    } else {
        $error = "Error updating record: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Service Record</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>✏ Edit Service Record</h2>

    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="post">
        <label>Service Type:</label><br>
        <input type="text" name="service_type" value="<?php echo $record['service_type']; ?>" required><br><br>

        <label>Description:</label><br>
        <textarea name="description"><?php echo $record['description']; ?></textarea><br><br>

        <label>Service Date:</label><br>
        <input type="date" name="service_date" value="<?php echo $record['service_date']; ?>" required><br><br>

        <button type="submit">Update Service</button>
    </form>

    <p><a href="admin_services.php">⬅ Back to All Records</a></p>
</div>
</body>
</html>
