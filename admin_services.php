 <?php
session_start();
include("db_connect.php");

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

// ✅ Fetch all service records
$sql = "SELECT sr.id, sr.service_type, sr.description, sr.service_date, sr.created_at,
               c.make, c.model, c.license_plate,
               u1.name AS owner_name, u2.name AS mechanic_name
        FROM service_records sr
        JOIN cars c ON sr.car_id = c.id
        JOIN users u1 ON c.owner_id = u1.id
        LEFT JOIN users u2 ON sr.mechanic_id = u2.id
        ORDER BY sr.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Service Records (Admin)</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* =====================================
           🌸 Coco Crochet Pink Theme (Admin Page)
        ===================================== */
        @import url('https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@400;500;600&display=swap');

        body {
            font-family: 'Edu SA Hand', cursive;
            background-color: #fff8fa; /* soft baby pink background */
            color: #3b302a;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            background-color: #ffffff;
            margin: 60px auto;
            padding: 40px 50px;
            border-radius: 25px;
            box-shadow: 0 6px 20px rgba(255, 182, 193, 0.3);
            border: 2px solid #f8bbd0;
        }

        .container h2 {
            text-align: center;
            font-size: 2em;
            color: #c2185b;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #f8bbd0;
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            font-size: 0.95em;
        }

        table th {
            background-color: #f8bbd0;
            color: #3b302a;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #f48fb1;
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #fce4ec;
        }

        table tr:nth-child(even) {
            background-color: #fff0f5;
        }

        table tr:hover {
            background-color: #f8bbd0;
            color: #3b302a;
            transition: background 0.3s ease;
        }

        a {
            text-decoration: none;
            color: #c2185b;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        a:hover {
            color: #880e4f;
            text-decoration: underline;
        }

        a[href*="delete_id"] {
            color: #e91e63;
        }

        a[href*="delete_id"]:hover {
            color: #ad1457;
        }

        p {
            text-align: center;
            font-size: 1em;
            margin-bottom: 20px;
        }

        p[style*="color:red"] {
            background-color: #ffe4ec;
            padding: 10px;
            border-radius: 10px;
            display: inline-block;
            border: 1px solid #f48fb1;
        }

        p a {
            background-color: #f48fb1;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 1em;
            display: inline-block;
            margin-top: 30px;
            transition: all 0.3s ease;
        }

        p a:hover {
            background-color: #c2185b;
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(194, 24, 91, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                width: 90%;
                padding: 25px;
            }

            table, th, td {
                font-size: 0.9em;
            }

            .container h2 {
                font-size: 1.6em;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🛠 All Service Records (Admin)</h2>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<p style='color:red;'>Record deleted successfully.</p>"; ?>

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
                    <td><?php echo $row['make']." ".$row['model']." (".$row['license_plate'].")"; ?></td>
                    <td><?php echo $row['owner_name']; ?></td>
                    <td><?php echo $row['mechanic_name'] ?: '—'; ?></td>
                    <td><?php echo $row['service_type']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td><?php echo $row['service_date']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <a href="edit_service.php?id=<?php echo $row['id']; ?>">✏ Edit</a> | 
                        <a href="admin_services.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this record?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No service records available.</p>
    <?php endif; ?>
    <p><a href="admin_dashboard.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
