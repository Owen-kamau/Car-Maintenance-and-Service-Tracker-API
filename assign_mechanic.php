 <?php
session_start();
include("db_connect.php");

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_requests.php");
    exit();
}
$request_id = intval($_GET['id']);

// Get mechanics
$mechanics = $conn->query("SELECT id, username FROM users WHERE role='mechanic'");

// Assign mechanic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mechanic_id = $_POST['mechanic_id'];
    $sql = "UPDATE service_requests SET mechanic_id=?, status='approved' WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $mechanic_id, $request_id);
    if ($stmt->execute()) {
        header("Location: manage_requests.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Mechanic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
    /* ===============================
       🌸 Coco Crochets Baby Pink Theme
    ================================ */
    body {
        font-family: 'Edu SA Hand', cursive;
        background-color: #fff5f8;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    /* ==== Card Container ==== */
    .container {
        background: #ffeef3;
        width: 420px;
        padding: 40px 45px;
        border-radius: 22px;
        box-shadow: 0 5px 25px rgba(255, 182, 193, 0.3);
        text-align: center;
        border: 2px solid #f8c6d8;
    }

    /* ==== Heading ==== */
    h2 {
        color: #b6426e;
        margin-bottom: 25px;
        font-size: 24px;
        letter-spacing: 0.6px;
    }

    /* ==== Form ==== */
    form {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    label {
        color: #6b2c48;
        font-size: 16px;
        margin-bottom: 10px;
        font-weight: 600;
    }

    select {
        width: 90%;
        padding: 12px;
        border-radius: 12px;
        border: 1.5px solid #f7b7cd;
        outline: none;
        background: #fffafc;
        color: #6b2c48;
        font-size: 15px;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    select:focus {
        border-color: #f08ca0;
        box-shadow: 0 0 6px rgba(240, 140, 160, 0.4);
        background-color: #fff;
    }

    /* ==== Button ==== */
    button {
        background-color: #f08ca0;
        color: #fff;
        border: none;
        padding: 12px 25px;
        font-size: 16px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    button:hover {
        background-color: #e46b8e;
        box-shadow: 0 4px 10px rgba(228, 107, 142, 0.3);
        transform: translateY(-2px);
    }

    /* ==== Link ==== */
    a {
        text-decoration: none;
        color: #a24b68;
        font-weight: 600;
        transition: color 0.3s ease;
        display: inline-block;
        margin-top: 15px;
    }

    a:hover {
        color: #c06184;
        text-decoration: underline;
    }

    /* ==== Success / Error ==== */
    .success {
        color: #3cb371;
        font-weight: 600;
    }

    .error {
        color: #e74c3c;
        font-weight: 600;
    }

    /* ==== Responsive ==== */
    @media (max-width: 500px) {
        .container {
            width: 90%;
            padding: 30px;
        }

        h2 {
            font-size: 20px;
        }

        button {
            font-size: 15px;
        }
    }
    </style>
</head>
<body>
<div class="container">
    <h2>🛠 Assign Mechanic to Request</h2>

    <form method="post">
        <label for="mechanic_id">Select Mechanic:</label>
        <select name="mechanic_id" id="mechanic_id" required>
            <?php while ($m = $mechanics->fetch_assoc()): ?>
                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['username']); ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Assign Mechanic</button>
    </form>

    <p><a href="manage_requests.php">⬅ Back</a></p>
</div>
</body>
</html>
