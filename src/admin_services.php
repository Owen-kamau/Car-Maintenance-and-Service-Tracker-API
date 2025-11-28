<?php
session_start();
include("DBConn.php");

// Only admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $sql = "DELETE FROM service_records WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: admin_services.php?msg=deleted");
    exit();
}

// Fetch all service records
$sql = "SELECT sr.id, sr.service_type, sr.description, sr.service_date, sr.created_at,
               c.make, c.model, c.license_plate,
               u1.username AS owner_name, u2.username AS mechanic_name
        FROM service_records sr
        JOIN cars c ON sr.car_id = c.id
        JOIN users u1 ON c.user_id = u1.id
        LEFT JOIN users u2 ON sr.mechanic_id = u2.id
        ORDER BY sr.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Service Records (Admin)</title>
<link rel="stylesheet" href="styles.css">
<style>
/* ================================
   🚀 Admin Dashboard Metallic Theme
================================ */
body {
    font-family: 'Roboto', sans-serif;
    background-color: #111;
    color: #f0f0f0;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1000px;
    background-color: #1a1a1a;
    margin: 60px auto;
    padding: 40px 50px;
    border-radius: 15px;
    box-shadow: 0 0 20px #00ff7f44;
}

h2 {
    text-align: center;
    font-size: 2em;
    color: #00ff7f;
    margin-bottom: 25px;
}

.message {
    text-align: center;
    margin-bottom: 20px;
    padding: 10px;
    border-radius: 8px;
    display: inline-block;
    border: 1px solid #00ff7f;
    color: #00ff7f;
    background-color: #00330033;
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: #222;
    border-radius: 12px;
    overflow: hidden;
    font-size: 0.95em;
    margin-top: 20px;
}

th {
    background-color: #000;
    color: #00ff7f;
    padding: 12px;
    text-align: left;
    font-weight: 600;
}

td {
    padding: 10px 12px;
    border-bottom: 1px solid #333;
}

tr:nth-child(even) {
    background-color: #1f1f1f;
}

tr:hover {
    background-color: #00ff7f11;
    color: #00ff7f;
    transition: background 0.3s ease;
}

a {
    text-decoration: none;
    color: #00ff7f;
    font-weight: 600;
    transition: all 0.3s ease;
}

a:hover {
    color: #00cc6a;
    text-decoration: underline;
}

a[href*="delete_id"] {
    color: #ff4b5c;
}

a[href*="delete_id"]:hover {
    color: #ff1c2a;
}

.back-link {
    background-color: #00ff7f;
    color: #000;
    padding: 10px 25px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 1em;
    display: inline-block;
    margin-top: 30px;
    transition: all 0.3s ease;
}

.back-link:hover {
    background-color: #00cc6a;
    transform: translateY(-3px);
    box-shadow: 0 0 10px #00ff7f44;
}

@media (max-width: 768px) {
    .container { width: 90%; padding: 25px; }
    table, th, td { font-size: 0.9em; }
    h2 { font-size: 1.6em; }
}
</style>
</head>
<body>
<div class="container">
    <h2>🛠 All Service Records (Admin)</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <p class="message">Record deleted successfully.</p>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Car</th>
                <th>Owner</th>
                <th>Mechanic</th>
                <th>Service Type</th>
                <th>Description</th>
                <th>Service Date</th>
                <th>Recorded At</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['make']." ".$row['model']." (".$row['license_plate'].")") ?></td>
                    <td><?= htmlspecialchars($row['owner_name']) ?></td>
                    <td><?= htmlspecialchars($row['mechanic_name'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($row['service_type']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['service_date']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <a href="edit_service.php?id=<?= $row['id'] ?>">✏ Edit</a> | 
                        <a href="admin_services.php?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this record?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No service records available.</p>
    <?php endif; ?>

    <p><a href="admin_dash.php" class="back-link">⬅ Back to Dashboard</a></p>     
</div>
</body>
</html>
