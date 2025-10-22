<?php
session_start();
include("db_connect.php");

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch all cars
$cars = $conn->query("SELECT c.id, c.make, c.model, c.license_plate, u.username 
                      FROM cars c 
                      JOIN users u ON c.user_id = u.id");

// Fetch all mechanics
$mechanics = $conn->query("SELECT id, username FROM users WHERE role='mechanic'");

// Handle assignment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $car_id = $_POST['car_id'];
    $mechanic_id = $_POST['mechanic_id'];

    $sql = "INSERT INTO car_assignments (car_id, mechanic_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $car_id, $mechanic_id);

    if ($stmt->execute()) {
        $success = "✅ Mechanic assigned successfully!";
    } else {
        $error = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assign Cars to Mechanics</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h2>🛠 Assign Cars to Mechanics</h2>

    <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="post">
        <label>Select Car:</label><br>
        <select name="car_id" required>
            <?php while ($car = $cars->fetch_assoc()): ?>
                <option value="<?php echo $car['id']; ?>">
                    <?php echo $car['make']." ".$car['model']." (".$car['license_plate'].") - Owner: ".$car['username']; ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Select Mechanic:</label><br>
        <select name="mechanic_id" required>
            <?php while ($mech = $mechanics->fetch_assoc()): ?>
                <option value="<?php echo $mech['id']; ?>"><?php echo $mech['username']; ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Assign</button>
    </form>

    <p><a href="admin_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>