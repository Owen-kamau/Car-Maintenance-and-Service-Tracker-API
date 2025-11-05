 <?php
session_start();
include("DBConn.php");

// Ensure only owners can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $license_plate = trim($_POST['license_plate']);

    $sql = "INSERT INTO cars (user_id, make, model, year, license_plate) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issis", $user_id, $make, $model, $year, $license_plate);

    if ($stmt->execute()) {
        $success = "Car registered successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Car</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@500&display=swap');

        body {
            font-family: 'Edu SA Hand', cursive;
            background: linear-gradient(135deg, #ffdee9, #ffe6f2, #ffd6e8, #fff0f6);
            color: #3b302a;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: #fffafb;
            width: 420px;
            padding: 35px 40px;
            border-radius: 20px;
            box-shadow: 0 6px 20px rgba(255, 182, 193, 0.4);
            border: 2px solid #ffb6c1;
            transition: all 0.3s ease-in-out;
            text-align: center;
        }

        .container:hover {
            box-shadow: 0 8px 25px rgba(255, 105, 180, 0.3);
        }

        h2 {
            color: #c2185b;
            font-size: 1.9em;
            margin-bottom: 20px;
        }

        label {
            color: #5e3a50;
            font-size: 1.05em;
        }

        input[type="text"],
        input[type="number"] {
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

        input:focus {
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
        }

        button:hover {
            background: linear-gradient(90deg, #ff7ca3, #ff94b6, #ffb6c1);
            transform: translateY(-2px);
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
        <h2>🚗 Register a New Car</h2>
        <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form method="post">
            <label>Make:</label><br>
            <input type="text" name="make" required><br><br>

            <label>Model:</label><br>
            <input type="text" name="model" required><br><br>

            <label>Year:</label><br>
            <input type="number" name="year" min="1918" max="2100" required><br><br>

            <label>License Plate:</label><br>
            <input type="text" name="license_plate" required><br><br>

            <button type="submit">Register Car</button>
        </form>

        <p><a href="<?php echo $_SESSION['role']; ?>_dash.php">⬅ Back to Dashboard</a></p>
    </div>
</body>
</html>
