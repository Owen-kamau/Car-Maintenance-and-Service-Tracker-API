<?php
session_start();
include("db_connect.php");

// Only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: index.php");
    exit();
}
$mechanic_id = $_SESSION['user_id'];

// Handle completion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_id'])) {
    $request_id = intval($_POST['complete_id']);
    $service_type = $_POST['service_type'];
    $notes = $_POST['notes'];
    $cost = $_POST['cost'];

    // Fetch request details
    $sql = "SELECT car_id, request_date FROM service_requests WHERE id=? AND mechanic_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $request_id, $mechanic_id);
    $stmt->execute();
    $stmt->bind_result($car_id, $service_date);
    $stmt->fetch();
    $stmt->close();

    if ($car_id) {
        // Insert into service_records
        $sql = "INSERT INTO service_records (car_id, mechanic_id, service_type, service_date, notes, cost) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssd", $car_id, $mechanic_id, $service_type, $service_date, $notes, $cost);
        $stmt->execute();

        // Update request status
        $sql = "UPDATE service_requests SET status='completed' WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();

        $success = "✅ Service completed and recorded!";
    } else {
        $error = "❌ Invalid request!";
    }
}

// Fetch mechanic's assigned requests
$sql = "SELECT sr.*, c.make, c.model, c.license_plate, u.username AS owner_name
        FROM service_requests sr
        JOIN cars c ON sr.car_id = c.id
        JOIN users u ON sr.owner_id = u.id
        WHERE sr.mechanic_id = ? AND sr.status='approved'
        ORDER BY sr.request_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Service Requests</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>🛠 My Assigned Service Requests</h2>
    <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <table border="1" cellpadding="10">
        <tr>
            <th>Car</th>
            <th>Owner</th>
            <th>Request Type</th>
            <th>Description</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['make']." ".$row['model']." (".$row['license_plate'].")"; ?></td>
                <td><?php echo $row['owner_name']; ?></td>
                <td><?php echo $row['request_type']; ?></td>
                <td><?php echo $row['description']; ?></td>
                <td><?php echo $row['request_date']; ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="complete_id" value="<?php echo $row['id']; ?>">
                        <label>Service Type:</label><br>
                        <input type="text" name="service_type" required><br>
                        <label>Notes:</label><br>
                        <textarea name="notes"></textarea><br>
                        <label>Cost:</label><br>
                        <input type="number" step="0.01" name="cost"><br><br>
                        <button type="submit">✅ Complete Service</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <p><a href="mechanic_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
