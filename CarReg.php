<?php
session_start();
include("DBConn.php");

// Ensure only owners can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $license_plate = trim($_POST['license_plate']);

    $sql = "INSERT INTO cars (user_id, make, model, year, license_plate) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issis", $user_id, $make, $model, $year, $license_plate);

    if ($stmt->execute()) {
        $success = "Car registered successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Car</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>🚗 Register a New Car</h2>
        <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form method="post">
            <label>Make:</label><br>
            <input type="text" name="make" required><br><br>

            <label>Model:</label><br>
            <input type="text" name="model" required><br><br>

            <label>Year:</label><br>
            <input type="number" name="year" min="1918" max="2100" required><br><br>

            <label>License Plate:</label><br>
            <input type="text" name="license_plate" required><br><br>

            <button type="submit">Register Car</button>
        </form>

       <p><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
    </div>
</body>
</html>
