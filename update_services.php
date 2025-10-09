<?php
session_start();
include("db_connect.php");

// Ensure only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: index.php");
    exit();
}

// Fetch only cars assigned to this mechanic
$sql = "SELECT c.id, c.make, c.model, c.license_plate
        FROM car_assignments ca
        JOIN cars c ON ca.car_id = c.id
        WHERE ca.mechanic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cars = $stmt->get_result();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $car_id = $_POST['car_id'];
    $service_type = $_POST['service_type'];
    $description = $_POST['description'];
    $service_date = $_POST['service_date'];

    $sql = "INSERT INTO service_records (car_id, mechanic_id, service_type, description, service_date) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $car_id, $_SESSION['user_id'], $service_type, $description, $service_date);

    if ($stmt->execute()) {
        $success = "✅ Service record added successfully!";
    } else {
        $error = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Service Record</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>🛠 Add Service Record</h2>

    <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="post">
        <label>Car:</label><br>
        <select name="car_id" required>
            <?php while ($car = $cars->fetch_assoc()): ?>
                <option value="<?php echo $car['id']; ?>">
                    <?php echo $car['make']." ".$car['model']." (".$car['license_plate'].")"; ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Service Type:</label><br>
        <input type="text" name="service_type" required><br><br>

        <label>Description:</label><br>
        <textarea name="description"></textarea><br><br>

        <label>Service Date:</label><br>
        <input type="date" name="service_date" required><br><br>

        <button type="submit">Add Service</button>
    </form>

    <p><a href="mechanic_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
