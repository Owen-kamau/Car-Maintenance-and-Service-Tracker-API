<?php
session_start();
include("DBConn.php");

// Only owners
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM cars WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Cars</title>
     <style>
        /* ===== General Page Style ===== */
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f5f7fa;
    margin: 0;
    padding: 0;
    color: #333;
}

/* ===== Container ===== */
.container {
    width: 90%;
    max-width: 900px;
    margin: 50px auto;
    background: #fff;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* ===== Title ===== */
h2 {
    text-align: center;
    color: #2d3436;
    margin-bottom: 30px;
    font-size: 28px;
}

/* ===== Table Styling ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 16px;
}

th, td {
    border: 1px solid #ddd;
    padding: 12px 15px;
    text-align: left;
}

th {
    background-color: #007bff;
    color: white;
    text-transform: uppercase;
    font-size: 15px;
    letter-spacing: 0.5px;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

tr:hover {
    background-color: #e9f5ff;
}

/* ===== Links ===== */
a {
    text-decoration: none;
    color: #007bff;
    font-weight: 600;
    transition: color 0.3s ease;
}

a:hover {
    color: #0056b3;
}

/* ===== Paragraph ===== */
p {
    text-align: center;
    margin-top: 20px;
    font-size: 16px;
}

/* ===== No Cars Message ===== */
p.no-cars {
    color: #888;
    text-align: center;
    font-style: italic;
    margin-top: 30px;
}

        </style>
</head>
<body>
    <div class="container">
        <h2>🚙 My Registered Cars</h2>
        <?php if ($result->num_rows > 0): ?>
            <table border="1" cellpadding="10">
                <tr>
                    <th>Make</th>
                    <th>Model</th>
                    <th>Year</th>
                    <th>License Plate</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['make']; ?></td>
                        <td><?php echo $row['model']; ?></td>
                        <td><?php echo $row['year']; ?></td>
                        <td><?php echo $row['license_plate']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No cars registered yet.</p>
        <?php endif; ?>
  <p><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
    </div>
</body>
</html>
