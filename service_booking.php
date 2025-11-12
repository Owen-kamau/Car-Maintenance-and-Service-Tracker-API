<?php
session_start();
include("DBConn.php");

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
     <style>
        /* ===== Request Service Page ===== */
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f5f7fa;
    margin: 0;
    padding: 0;
}

.container {
    width: 90%;
    max-width: 500px;
    margin: 50px auto;
    background: #ffffff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    text-align: center;
}

h2 {
    color: #2d3436;
    margin-bottom: 25px;
    font-size: 24px;
}

label {
    display: block;
    text-align: left;
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
}

input[type="text"],
input[type="date"],
select,
textarea {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 15px;
    outline: none;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

input[type="text"]:focus,
input[type="date"]:focus,
select:focus,
textarea:focus {
    border-color: #007bff;
}

textarea {
    min-height: 80px;
    resize: vertical;
}

button {
    width: 100%;
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 12px;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

p {
    font-size: 15px;
    margin-top: 15px;
    text-align: center;
}

p[style*="green"] {
    background-color: #e6ffed;
    color: #007b22;
    padding: 10px;
    border-radius: 6px;
}

p[style*="red"] {
    background-color: #ffecec;
    color: #d8000c;
    padding: 10px;
    border-radius: 6px;
}

a {
    text-decoration: none;
    color: #007bff;
    font-weight: 500;
    transition: color 0.3s ease;
}

a:hover {
    color: #0056b3;
}

     </style>
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
