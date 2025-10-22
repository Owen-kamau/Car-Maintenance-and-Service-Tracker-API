<?php
session_start();
include("db_connect.php");

// Only owners
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

// Fetch owner's cars
$sql = "SELECT id, make, model, license_plate FROM cars WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cars = $stmt->get_result();

// Handle form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $car_id = $_POST['car_id'];
    $request_type = $_POST['request_type'];
    $description = $_POST['description'];
    $request_date = $_POST['request_date'];

    $sql = "INSERT INTO service_requests (car_id, owner_id, request_type, description, request_date) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $car_id, $_SESSION['user_id'], $request_type, $description, $request_date);

    if ($stmt->execute()) {
        $success = "✅ Service request submitted!";
    } else {
        $error = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Request Service</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>📩 Request a Service</h2>
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

        <label>Request Type:</label><br>
        <input type="text" name="request_type" required><br><br>

        <label>Description:</label><br>
        <textarea name="description"></textarea><br><br>

        <label>Preferred Date:</label><br>
        <input type="date" name="request_date" required><br><br>

        <button type="submit">Submit Request</button>
    </form>

    <p><a href="owner_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
