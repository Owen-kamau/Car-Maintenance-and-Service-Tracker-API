 <?php
session_start();
include("DBConn.php");

// ✅ Ensure only owners can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

// ✅ Handle car deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $license_plate = trim($_POST['license_plate']);

    $sql = "DELETE FROM cars WHERE user_id = ? AND license_plate = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $license_plate);

    if ($stmt->execute()) {
        $success = "Car deleted successfully!";
    } else {
        $error = "Error deleting car: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Car</title>
    <link href="https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@500&display=swap" rel="stylesheet">
    <style>
/* 🌸 General Body Styling */
body {
  font-family: 'Edu SA Hand', cursive;
  background: linear-gradient(135deg, #ffe6ec, #ffd6e3, #fff0f4);
  color: #4a2c2c;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  margin: 0;
}

/* 💗 Delete Card */
.container {
  background: #fffafc;
  border: 2px solid #f5a6c1;
  box-shadow: 0 4px 20px rgba(245, 166, 193, 0.4);
  border-radius: 16px;
  padding: 30px;
  width: 420px;
  text-align: center;
  position: relative;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.container:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 25px rgba(245, 166, 193, 0.6);
}

h2 {
  color: #e75480;
  font-size: 1.6rem;
  margin-bottom: 15px;
}

p {
  color: #6d3b47;
}

/* 🩷 Inputs */
input[type="text"] {
  width: 80%;
  padding: 10px;
  margin-top: 10px;
  border-radius: 8px;
  border: 1px solid #f5a6c1;
  background: #fff0f6;
  color: #4a2c2c;
  outline: none;
  transition: all 0.3s ease;
}

input[type="text"]:focus {
  border-color: #e75480;
  background: #ffe6ec;
  box-shadow: 0 0 8px rgba(231, 84, 128, 0.3);
}

/* 🌷 Buttons */
button {
  background: linear-gradient(90deg, #f9a1b8, #f5a6c1, #e75480);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  margin-top: 15px;
  cursor: pointer;
  font-weight: bold;
  font-family: 'Edu SA Hand', cursive;
  transition: all 0.3s ease;
  box-shadow: 0 4px 10px rgba(245, 166, 193, 0.4);
}

button:hover {
  transform: scale(1.05);
  background: linear-gradient(90deg, #f5a6c1, #e75480);
  box-shadow: 0 6px 18px rgba(231, 84, 128, 0.4);
}

/* Disabled/cooldown button */
button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  background: linear-gradient(90deg, #f5c6d8, #f9a1b8);
}

/* 💫 Messages */
p[style*="color:green"] {
  background-color: #e8f5e9;
  color: #2e7d32;
  padding: 10px;
  border-radius: 8px;
  width: 85%;
  margin: 10px auto;
}

p[style*="color:red"] {
  background-color: #ffebee;
  color: #c62828;
  padding: 10px;
  border-radius: 8px;
  width: 85%;
  margin: 10px auto;
}

/* 💎 Back link */
a {
  color: #e75480;
  text-decoration: none;
  font-weight: bold;
  display: inline-block;
  margin-top: 10px;
}

a:hover {
  color: #f58fa2;
  text-decoration: underline;
}
    </style>
</head>
<body>
    <div class="container">
        <h2>💖 Delete a Car</h2>
        <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form method="post">
            <label>License Plate:</label><br>
            <input type="text" name="license_plate" required><br><br>

            <button type="submit">Delete Car</button>
        </form>

        <p><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
    </div>
</body>
</html>
