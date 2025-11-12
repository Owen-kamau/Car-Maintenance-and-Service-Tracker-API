 <?php
session_start();
include("DBConn.php");

// Only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: index.php");
    exit();
}

// Fetch assigned cars
$sql = "SELECT c.*, u.username AS owner_name
        FROM car_assignments ca
        JOIN cars c ON ca.car_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE ca.mechanic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assigned Cars</title>
    <link href="https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    /* ===============================
       🌸 Coco Crochets Pink Theme
       Page: Assigned Cars
    ================================ */
    body {
        font-family: 'Edu SA Hand', cursive;
        background-color: #fff8fa;
        color: #3b302a;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    /* ===== Container ===== */
    .container {
        max-width: 850px;
        width: 90%;
        background: #ffffff;
        padding: 40px 50px;
        border-radius: 25px;
        box-shadow: 0 6px 20px rgba(255, 182, 193, 0.35);
        border: 2px solid #f8bbd0;
        text-align: center;
    }

    /* ===== Heading ===== */
    h2 {
        font-size: 2em;
        color: #c2185b;
        margin-bottom: 30px;
    }

    /* ===== Table ===== */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 25px;
    }

    th, td {
        padding: 12px 15px;
        border: 1.5px solid #f8bbd0;
        text-align: center;
    }

    th {
        background-color: #f8bbd0;
        color: #3b302a;
        font-weight: 700;
    }

    tr:nth-child(even) {
        background-color: #fff0f6;
    }

    tr:hover {
        background-color: #f48fb1;
        color: #fff;
        transition: 0.3s ease;
    }

    /* ===== Message ===== */
    p {
        font-size: 1.1em;
        margin-top: 20px;
        color: #3b302a;
    }

    /* ===== Back Button ===== */
    a {
        display: inline-block;
        background-color: #f8bbd0;
        color: #3b302a;
        padding: 10px 25px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    a:hover {
        background-color: #f48fb1;
        color: white;
        box-shadow: 0 4px 10px rgba(244, 143, 177, 0.3);
        transform: translateY(-3px);
    }

    /* ===== Responsive Design ===== */
    @media (max-width: 700px) {
        .container {
            padding: 25px;
        }

        table {
            font-size: 0.9em;
        }

        h2 {
            font-size: 1.6em;
        }

        a {
            font-size: 0.95em;
        }
    }
    </style>
</head>
<body>
<div class="container">
    <h2>🔧 My Assigned Cars</h2>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Make</th>
                <th>Model</th>
                <th>Year</th>
                <th>License Plate</th>
                <th>Owner</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['make']); ?></td>
                    <td><?php echo htmlspecialchars($row['model']); ?></td>
                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                    <td><?php echo htmlspecialchars($row['license_plate']); ?></td>
                    <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>🚗 No cars assigned yet.</p>
    <?php endif; ?>
    <p><a href="mechanic_dash.php">⬅ Back to Dashboard</a></p>
</div>
</body>
</html>
