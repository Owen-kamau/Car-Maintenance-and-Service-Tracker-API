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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Car</title>
  <link rel="stylesheet" href="car_style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
  body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(to right, #ffeaf0, #fff5f9);
  display: flex;
}

/* Sidebar */
.sidebar {
  width: 230px;
  background: #ffcce0;
  color: #4b2e2e;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  padding: 30px 20px;
  box-shadow: 4px 0 12px rgba(0,0,0,0.1);
  border-top-right-radius: 25px;
  border-bottom-right-radius: 25px;
}

.sidebar .logo {
  font-size: 1.8em;
  text-align: center;
  color: #ff4da6;
  margin-bottom: 30px;
  font-weight: 700;
}

.sidebar nav a {
  display: block;
  color: #4b2e2e;
  padding: 12px 15px;
  text-decoration: none;
  border-radius: 12px;
  margin-bottom: 12px;
  font-size: 1em;
  transition: all 0.3s ease;
}

.sidebar nav a:hover, .sidebar nav a.active {
  background: #ffb6d9;
  color: #fff;
  transform: translateX(5px);
}

.sidebar nav i {
  margin-right: 10px;
  color: #ff80b5;
}

/* Main content */
.main-content {
  margin-left: 250px;
  flex: 1;
  padding: 40px;
}

.form-card {
  background: #fff;
  max-width: 450px;
  margin: 40px auto;
  padding: 30px 40px;
  border-radius: 20px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  text-align: center;
}

.form-card h2 {
  color: #ff4da6;
  margin-bottom: 25px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

label {
  display: block;
  text-align: left;
  margin: 12px 0 5px;
  font-weight: 600;
  color: #4b2e2e;
}

input {
  width: 100%;
  padding: 10px;
  border-radius: 12px;
  border: 1.5px solid #ffb6d9;
  margin-bottom: 10px;
  outline: none;
  font-size: 1em;
}

input:focus {
  border-color: #ff4da6;
  box-shadow: 0 0 5px #ffb6d9;
}

button {
  background: linear-gradient(135deg, #ff80b5, #ff4da6);
  color: #fff;
  padding: 12px 25px;
  border: none;
  border-radius: 15px;
  font-size: 1em;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s ease;
}

button:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 12px rgba(255,77,166,0.3);
}

.success {
  color: #009e60;
  font-weight: bold;
}

.error {
  color: #e63946;
  font-weight: bold;
}

.back a {
  color: #ff4da6;
  text-decoration: none;
  font-weight: 600;
}

.back a:hover {
  text-decoration: underline;
}
</style>
</head>
<body>
  <div class="sidebar">
    <h2 class="logo">🚗 CMTS</h2>
    <nav>
      <a href="owner_dash.php"><i class="fa-solid fa-house"></i> Dashboard</a>
      <a href="register_car.php" class="active"><i class="fa-solid fa-car-side"></i> Register Car</a>
      <a href="#"><i class="fa-solid fa-wrench"></i> Services</a>
      <a href="#"><i class="fa-solid fa-user"></i> Profile</a>
      <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
  </div>

  <div class="main-content">
    <div class="form-card">
      <h2>💖 Register a New Car</h2>
      <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
      <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

      <form method="post">
        <label>Make</label>
        <input type="text" name="make" required>

        <label>Model</label>
        <input type="text" name="model" required>

        <label>Year</label>
        <input type="number" name="year" min="1918" max="2100" required>

        <label>License Plate</label>
        <input type="text" name="license_plate" required>

        <button type="submit">✨ Register Car</button>
      </form>
      <p class="back"><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
    </div>
  </div>
</body>
</html>
