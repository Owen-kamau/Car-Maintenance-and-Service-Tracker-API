<?php
session_start();
include("db_connect.php");

// Only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = intval($_GET['id']);
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'];
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $role, $user_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="styles.css">
<style>
/* General Page */
body {
  font-family: "Edu SA Hand", cursive;
  background: linear-gradient(135deg, #fffaf2 0%, #ffeef4 100%);
  color: #3b302a;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
}

/* Form Container */
.container {
  background: #ffffff;
  padding: 40px 45px;
  border-radius: 25px;
  box-shadow: 0 5px 20px rgba(233, 137, 185, 0.3);
  width: 400px;
  text-align: center;
  border: 2px solid #f7c7d3;
  position: relative;
  overflow: hidden;
}

.container::before {
  content: "";
  position: absolute;
  top: -30%;
  left: -30%;
  width: 160%;
  height: 160%;
  background: radial-gradient(circle at top left, #ffe5ec, transparent 70%);
  z-index: 0;
}

.container * {
  position: relative;
  z-index: 1;
}

/* Title */
h2 {
  color: #d97a9c;
  font-size: 1.8em;
  margin-bottom: 25px;
  font-weight: bold;
}

/* Labels & Select */
label {
  display: block;
  margin-bottom: 8px;
  text-align: left;
  font-weight: 600;
  color: #3b302a;
}

select {
  width: 100%;
  padding: 10px;
  border: 1.5px solid #f5b6c5;
  border-radius: 12px;
  background: linear-gradient(135deg, #fffaf2, #ffe5ec);
  font-size: 15px;
  margin-bottom: 20px;
  transition: 0.3s ease;
}

select:focus {
  border-color: #e989b9;
  box-shadow: 0 0 6px rgba(233, 137, 185, 0.4);
  outline: none;
}

/* Button */
button {
  background: linear-gradient(135deg, #e989b9, #f5b6c5);
  color: white;
  border: none;
  padding: 10px 25px;
  border-radius: 12px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: 0.3s ease;
}

button:hover {
  background: linear-gradient(135deg, #d97a9c, #f7b9d0);
  transform: scale(1.05);
}

/* Back link */
a {
  text-decoration: none;
  color: #d97a9c;
  font-weight: 600;
  font-size: 15px;
  display: inline-block;
  margin-top: 15px;
  transition: 0.3s ease;
}

a:hover {
  color: #b85a7e;
  text-decoration: underline;
}

/* Mobile */
@media (max-width: 480px) {
  .container {
    width: 90%;
    padding: 25px;
  }
}

    </style>
</head>
<body>
<div class="container">
    <h2>Edit User: <?php echo $user['username']; ?></h2>
    <form method="post">
        <label>Role:</label>
        <select name="role" required>
            <option value="owner" <?php if ($user['role']=='owner') echo 'selected'; ?>>Owner</option>
            <option value="mechanic" <?php if ($user['role']=='mechanic') echo 'selected'; ?>>Mechanic</option>
            <option value="admin" <?php if ($user['role']=='admin') echo 'selected'; ?>>Admin</option>
        </select><br><br>
        <button type="submit">Update Role</button>
    </form>
    <p><a href="manage_users.php">⬅ Back to Manage Users</a></p>
</div>
</body>
</html>
