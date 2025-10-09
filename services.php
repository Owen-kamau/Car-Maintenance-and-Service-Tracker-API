<?php
session_start();
include("db_connect.php");

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch cars owned by the user (if owner/mechanic/admin – fetch differently)
if ($_SESSION['role'] == 'owner') {
    $sql = "SELECT * FROM cars WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
} else {
    // mechanics/admins can see all cars
    $sql = "SELECT * FROM cars";
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$cars = $stmt->get_result();

// Handle service submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $car_id = $_POST['car_id'];
    $service_type = $_POST['service_type'];
    $service_date = $_POST['service_date'];
    $mileage = $_POST['mileage'];
    $notes = $_POST['notes'];
    $next_service_date = $_POST['next_service_date'];

    $sql = "INSERT INTO services (car_id, service_type, service_date, mileage, notes, next_service_date)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ississ", $car_id, $service_type, $service_date, $mileage, $notes, $next_service_date);

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
    <title>Add Service</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>🛠 Add Service Record</h2>

    <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="post">
        <label>Select Car:</label><br>
        <select name="car_id" required>
            <?php while ($car = $cars->fetch_assoc()): ?>
                <option value="<?php echo $car['id']; ?>">
                    <?php echo $car['make'] . " " . $car['model'] . " (" . $car['license_plate'] . ")"; ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Service Type:</label><br>
        <input type="text" name="service_type" required><br><br>

        <label>Service Date:</label><br>
        <input type="date" name="service_date" required><br><br>

        <label>Mileage:</label><br>
        <input type="number" name="mileage"><br><br>

        <label>Notes:</label><br>
        <textarea name="notes"></textarea><br><br>

        <label>Next Service Date:</label><br>
        <input type="date" name="next_service_date"><br><br>

        <button type="submit">Add Service</button>
    </form>

    <p><a href="<?php echo $_SESSION['role']; ?>_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
