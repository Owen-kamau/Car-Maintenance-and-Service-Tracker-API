<?php
session_start();
include("db_connect.php");

// Only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_services.php");
    exit();
}

$service_id = intval($_GET['id']);

// Fetch service record
$sql = "SELECT * FROM service_records WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    die("Service record not found.");
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_type = $_POST['service_type'];
    $description = $_POST['description'];
    $service_date = $_POST['service_date'];

    $sql = "UPDATE service_records SET service_type=?, description=?, service_date=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $service_type, $description, $service_date, $service_id);

    if ($stmt->execute()) {
        header("Location: admin_services.php?msg=updated");
        exit();
    } else {
        $error = "Error updating record: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Service Record</title>
    <link rel="stylesheet" href="styles.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@500&display=swap');

body {
    font-family: 'Edu SA Hand', cursive;
    background: linear-gradient(135deg, #ffe6f2, #ffd6e8, #fff0f6);
    color: #3b302a;
    margin: 0;
    padding: 0;
    text-align: center;
}

.container {
    background: #fffafb;
    max-width: 550px;
    margin: 80px auto;
    padding: 40px;
    border-radius: 25px;
    box-shadow: 0 6px 25px rgba(255, 182, 193, 0.4);
    border: 2px solid #ffb6c1;
    transition: all 0.3s ease-in-out;
}

.container:hover {
    box-shadow: 0 8px 30px rgba(255, 105, 180, 0.3);
}

h2 {
    color: #c2185b;
    font-size: 1.9em;
    margin-bottom: 25px;
}

label {
    color: #5e3a50;
    font-size: 1.1em;
    display: block;
    text-align: left;
    margin-left: 20px;
    margin-bottom: 5px;
}

input[type="text"],
input[type="date"],
textarea {
    width: 90%;
    padding: 10px;
    margin-top: 6px;
    border: 1.5px solid #f4a6b8;
    border-radius: 10px;
    background-color: #fff0f5;
    font-size: 1em;
    outline: none;
    transition: 0.3s;
}

textarea {
    resize: none;
    height: 100px;
}

input:focus, textarea:focus {
    border-color: #ff8fab;
    box-shadow: 0 0 5px #ffc2d1;
}

button {
    background: linear-gradient(90deg, #ff9eb8, #ffb6c1, #ffcce0);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 12px;
    font-size: 1em;
    cursor: pointer;
    transition: 0.3s;
    font-weight: bold;
    margin-top: 15px;
}

button:hover {
    background: linear-gradient(90deg, #ff7ca3, #ff94b6, #ffb6c1);
    transform: translateY(-2px);
}

p {
    font-size: 1em;
    margin-top: 20px;
}

a {
    color: #c2185b;
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    color: #e75480;
    text-decoration: underline;
}

/* error message */
p[style*="color:red"] {
    background-color: #ffebee;
    color: #c62828;
    padding: 10px;
    border-radius: 8px;
    width: 85%;
    margin: 10px auto;
}

    </style>
</head>
<body>
<div class="container">
    <h2>✏ Edit Service Record</h2>

    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="post">
        <label>Service Type:</label><br>
        <input type="text" name="service_type" value="<?php echo $record['service_type']; ?>" required><br><br>

        <label>Description:</label><br>
        <textarea name="description"><?php echo $record['description']; ?></textarea><br><br>

        <label>Service Date:</label><br>
        <input type="date" name="service_date" value="<?php echo $record['service_date']; ?>" required><br><br>

        <button type="submit">Update Service</button>
    </form>

    <p><a href="admin_services.php">⬅ Back to All Records</a></p>
</div>
</body>
</html>
