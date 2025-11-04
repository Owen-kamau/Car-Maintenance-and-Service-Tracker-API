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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Service Records (Admin)</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* You can move this to styles.css later */
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            max-width: 1100px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .actions a {
            margin-right: 8px;
        }

        .message {
            color: red;
            font-weight: 500;
            text-align: center;
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: #333;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            color: #007bff;
        }

        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            tr {
                margin-bottom: 1rem;
                border-bottom: 2px solid #eee;
                padding-bottom: 1rem;
            }

            th {
                display: none;
            }

            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #555;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>🛠 All Service Records (Admin)</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') echo "<p class='message'>Record deleted successfully.</p>"; ?>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
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
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="Car"><?php echo $row['make']." ".$row['model']." (".$row['license_plate'].")"; ?></td>
                    <td data-label="Owner"><?php echo $row['owner_name']; ?></td>
                    <td data-label="Mechanic"><?php echo $row['mechanic_name']; ?></td>
                    <td data-label="Service Type"><?php echo $row['service_type']; ?></td>
                    <td data-label="Description"><?php echo $row['description']; ?></td>
                    <td data-label="Service Date"><?php echo $row['service_date']; ?></td>
                    <td data-label="Recorded At"><?php echo $row['created_at']; ?></td>
                    <td data-label="Actions" class="actions">
                        <a href="edit_service.php?id=<?php echo $row['id']; ?>">✏ Edit</a>
                        <a href="admin_services.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this record?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No service records available.</p>
    <?php endif; ?>

    <p><a href="admin_dashboard.php" class="back-link">⬅ Back to Dashboard</a></p>     
</div>
</body>
</html>