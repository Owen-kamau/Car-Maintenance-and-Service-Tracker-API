<?php
session_start();
include("DBConn.php");
include("mail.php");

// ✅ Ensure only owners can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo "
    <script>
        setTimeout(() => {
            window.location.href = '" . (isset($_SESSION['role']) ? $_SESSION['role'] : 'index') . "_dash.php';
        }, 800);
    </script>";
    exit();
}

// ✅ Ensure session variables exist
$userEmail = $_SESSION['email'] ?? '';
$userName  = $_SESSION['username'] ?? 'Owner';

// ✅ Initialize messages
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $license_plate = trim($_POST['license_plate']);
    $garage_type = $_POST['garage_type'];
    $car_image = null;

    // ✅ Handle image upload
    if (!empty($_FILES['car_image']['name'])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES["car_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["car_image"]["tmp_name"], $target_file)) {
                $car_image = $target_file;
            } else {
                $error = "❌ Error uploading image.";
            }
        } else {
            $error = "❌ Only JPG, PNG, and GIF files are allowed.";
        }
    }

    // ✅ Insert car into DB
    if (empty($error)) {
        $sql = "INSERT INTO cars (user_id, make, model, year, license_plate, garage_type, car_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ississs", $user_id, $make, $model, $year, $license_plate, $garage_type, $car_image);

        if ($stmt->execute()) {
            // ✅ Prepare and send vintage-style email
            $subject = "Your Car Registration is Complete! 🚗";
            $body = "
            <div style='font-family: Georgia, serif; background: #f9f9f9; padding: 20px;'>
                <div style='max-width: 600px; margin:auto; background: #fff; border-radius: 10px; padding: 20px; border: 2px solid #ff4d00;'>
                    <h2 style='color:#ff4d00;'>Hi $userName!</h2>
                    <p>Thank you for registering your car. Here are the details:</p>
                    <table style='width:100%; border-collapse: collapse;'>
                        <tr><td style='padding:10px;'><b>Make:</b></td><td style='padding:10px;'>$make</td></tr>
                        <tr><td style='padding:10px;'><b>Model:</b></td><td style='padding:10px;'>$model</td></tr>
                        <tr><td style='padding:10px;'><b>Year:</b></td><td style='padding:10px;'>$year</td></tr>
                        <tr><td style='padding:10px;'><b>License Plate:</b></td><td style='padding:10px;'>$license_plate</td></tr>
                        <tr><td style='padding:10px;'><b>Garage Type:</b></td><td style='padding:10px;'>".ucfirst($garage_type)."</td></tr>
                    </table>
                    <p style='margin-top:20px; font-size:0.9em; color:#555;'>⚙️ This is an automated message. Please do not reply.</p>
                </div>
            </div>";

            // ✅ No-reply header added
            $emailStatus = sendMail($userEmail, $subject, $body);

            $success = "✅ Car registered successfully!<br>📧 $emailStatus";

        } else {
            $error = "❌ Database Error: " . $stmt->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Car | My Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: radial-gradient(circle at 20% 20%, #1b1b1b, #2a2a2a, #111);
            color: #f0f0f0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: rgba(30, 30, 30, 0.95);
            border: 2px solid #ff4d00;
            border-radius: 16px;
            box-shadow: 0 0 25px rgba(255, 77, 0, 0.4);
            padding: 40px;
            width: 420px;
            text-align: center;
        }
        h2 { color: #ff4d00; 
            font-size: 1.8rem; 
            margin-bottom: 20px; 
        }
        input, select {
            width: 90%; 
            padding: 10px; 
            border: none; 
            border-radius: 8px;
            margin-bottom: 15px; 
            background: #333; 
            color: #fff;
        }
        input:focus, select:focus { 
            outline: 2px solid #ff4d00; 
        }
        button {
            background-color: #ff4d00; 
            color: #fff; border: none;
            border-radius: 8px; 
            padding: 10px 20px; 
            cursor: pointer;
            transition: 0.3s ease; 
            font-weight: bold;
        }
        button:hover { 
            background-color: #ff6600; 
            transform: scale(1.05); 
        }
        .message-container { 
            margin-top: 25px; 
            text-align: center; 
        }
        .message {
            display: inline-block; 
            padding: 15px 20px; 
            border-radius: 12px;
            font-family: 'Georgia', serif; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }
        .success { 
            background: #f0e6d2; 
            color: #5a2e0b; 
            border: 2px solid #c17f0d; 
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 2px solid #f5c6cb; 
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Register Your Car</h2>

    <form method="post" enctype="multipart/form-data">
        <input type="text" name="make" placeholder="Make" required>
        <input type="text" name="model" placeholder="Model" required>
        <input type="number" name="year" min="1918" max="2100" placeholder="Year" required>
        <input type="text" name="license_plate" placeholder="License Plate" required>

        <label style="color:#aaa;">Garage Type:</label><br>
        <select name="garage_type" required>
            <option value="vehicle">Normal Vehicle</option>
            <option value="truck">Truck</option>
            <option value="tractor">Tractor</option>
        </select><br>

        <label style="color:#aaa;">Upload Car Image:</label><br>
        <input type="file" name="car_image" accept="image/*"><br><br>

        <button type="submit">Register Car</button>
    </form>

    <div class="message-container">
        <?php 
        if (!empty($success)) {
            echo "<p class='message success'>$success</p>";
            echo "<script>document.addEventListener('DOMContentLoaded', () => {
                showGearLoaderAndRedirect('" . $_SESSION['role'] . "_dash.php');
            });</script>";
        }       
        if (!empty($error)) echo "<p class='message error'>$error</p>";
        ?>
    </div>

    <p><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
</div>

<!-- Gear Loader -->
<div id="gear-loader" class="loader-overlay">
  <div class="gear-container">
    <div class="gear"></div>
    <p class="loading-text">Revving up your dashboard...</p>
  </div>
</div>

<style>
.loader-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: radial-gradient(circle, #1a1a1a 0%, #000 100%);
  display: none; align-items: center; justify-content: center;
  z-index: 9999;
}
.gear-container { text-align: center; }
.gear {
  width: 80px; height: 80px;
  border: 10px solid #ff4d00;
  border-top: 10px solid #2a2a2a;
  border-radius: 50%;
  animation: spin 1.8s linear infinite;
  box-shadow: 0 0 15px rgba(255,77,0,0.4);
}
.loading-text {
  margin-top: 20px; color: #ffb366;
  font-family: 'Georgia', serif;
  font-size: 1.1rem; text-shadow: 0 0 8px rgba(255,102,0,0.5);
}
@keyframes spin { from { transform: rotate(0deg);} to { transform: rotate(360deg);} }
</style>

<script>
function showGearLoaderAndRedirect(url) {
  const loader = document.getElementById('gear-loader');
  loader.style.display = 'flex';
  setTimeout(() => { window.location.href = url; }, 2200);
}
</script>

</body>
</html>
