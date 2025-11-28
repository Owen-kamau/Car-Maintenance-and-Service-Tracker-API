<?php
session_start();
include("DBConn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$today = date("Y-m-d");
$next30days = date("Y-m-d", strtotime("+30 days"));

if ($_SESSION['role'] == 'owner') {
    // Owner: see only their own cars’ upcoming services
    $sql = "SELECT s.*, c.make, c.model, c.license_plate 
            FROM services s 
            JOIN cars c ON s.car_id = c.id 
            WHERE c.user_id = ? 
              AND s.next_service_date IS NOT NULL 
              AND s.next_service_date <= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $_SESSION['user_id'], $next30days);

} else {
    // Admin or other roles: see all upcoming services
    $sql = "SELECT s.*, c.make, c.model, c.license_plate 
            FROM services s 
            JOIN cars c ON s.car_id = c.id 
            WHERE s.next_service_date IS NOT NULL 
              AND s.next_service_date <= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $next30days);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upcoming Service Reminders</title>
    <style>
        /* ===== General Page Styles ===== */
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f5f7fa;
    margin: 0;
    padding: 0;
}

/* ===== Container ===== */
.container {
    width: 90%;
    max-width: 1000px;
    margin: 50px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

/* ===== Heading ===== */
h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 26px;
    color: #2d3436;
}

/* ===== Table ===== */
table {
    width: 100%;
    border-collapse: collapse;
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
    font-size: 14px;
    letter-spacing: 0.5px;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:hover {
    background-color: #e9f5ff;
}

/* ===== Overdue & Upcoming Service Rows ===== */
tr[style*="f8d7da"] {
    background-color: #f8d7da !important; /* Overdue - red */
}

tr[style*="fff3cd"] {
    background-color: #fff3cd !important; /* Upcoming - yellow */
}

/* ===== Links ===== */
a {
    text-decoration: none;
    color: #007bff;
    font-weight: 500;
    transition: color 0.3s ease;
}

a:hover {
    color: #0056b3;
}

/* ===== No services message ===== */
p {
    text-align: center;
    font-size: 16px;
    color: #555;
    margin-top: 20px;
}

    </style>

</head>
<body>
<div class="container">
    <h2>🔔 Upcoming Service Reminders</h2>

    <?php if ($result->num_rows > 0): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>Car</th>
                <th>Service Type</th>
                <th>Last Service</th>
                <th>Next Service Due</th>
                <?php if ($_SESSION['role'] != 'owner'): ?>
                    <th>Owner</th>
                <?php endif; ?>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr style="<?php echo ($row['next_service_date'] < $today) ? 'background:#f8d7da;' : 'background:#fff3cd;'; ?>">
                    <td><?php echo $row['make']." ".$row['model']." (".$row['license_plate'].")"; ?></td>
                    <td><?php echo $row['service_type']; ?></td>
                    <td><?php echo $row['service_date']; ?></td>
                    <td><?php echo $row['next_service_date']; ?></td>
                    <?php if ($_SESSION['role'] != 'owner'): ?>
                        <td><?php echo $row['username']; ?></td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No upcoming services within 30 days ✅</p>
    <?php endif; ?>

    <p><a href="<?php echo $_SESSION['role']; ?>index.php">⬅ Back to Dashboard </a></p>
</div>
</body>
</html>
