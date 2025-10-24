<?php
session_start();
include("DBConn.php");

// Ensure only owners can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $license_plate = trim($_POST['license_plate']);
    $car_image = null;

    // ✅ Handle image upload
    if (!empty($_FILES['car_image']['name'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["car_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $target_file)) {
                $car_image = $target_file;
            } else {
                $error = "Error uploading image.";
            }
        } else {
            $error = "Only JPG, PNG, and GIF files are allowed.";
        }
    }

    if (empty($error)) {
        $sql = "INSERT INTO cars (user_id, make, model, year, license_plate, car_image) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ississ", $user_id, $make, $model, $year, $license_plate, $car_image);

        if ($stmt->execute()) {
            $success = "✅ Car registered successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Car | My Garage</title>
    <style>
        body {
            background: linear-gradient(135deg, #1b1b1b, #2e2e2e);
            color: #f0f0f0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #202020;
            border: 2px solid #ff4d00;
            border-radius: 16px;
            box-shadow: 0 0 25px rgba(255, 77, 0, 0.5);
            padding: 40px;
            width: 400px;
            text-align: center;
        }
        h2 {
            color: #ff4d00;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        input[type="text"], input[type="number"], input[type="file"] {
            width: 90%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #333;
            color: #fff;
        }
        button {
            background-color: #ff4d00;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background-color: #ff6600;
            transform: scale(1.05);
        }
        a {
            color: #ccc;
            text-decoration: none;
        }
        a:hover {
            color: #ff4d00;
        }
        .message {
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2> Register Your Car</h2>

        <?php if ($success) echo "<p class='message' style='color: #00ff88;'>$success</p>"; ?>
        <?php if ($error) echo "<p class='message' style='color: #ff4d00;'>$error</p>"; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="text" name="make" placeholder="Make" required><br>
            <input type="text" name="model" placeholder="Model" required><br>
            <input type="number" name="year" min="1918" max="2100" placeholder="Year" required><br>
            <input type="text" name="license_plate" placeholder="License Plate" required><br>

            <label style="color:#aaa;">Upload Car Image:</label><br>
            <input type="file" name="car_image" accept="image/*"><br><br>

            <button type="submit">Register Car</button>
        </form>

        <p><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
    </div>
</body>
</html>
