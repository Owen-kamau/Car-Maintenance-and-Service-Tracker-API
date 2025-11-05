 <?php
session_start();
include("db_connect.php");

// ✅ Restrict access to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// ✅ Fetch all cars
$carsQuery = "SELECT c.id, c.make, c.model, c.license_plate, u.username 
               FROM cars c 
               JOIN users u ON c.user_id = u.id";
$cars = $conn->query($carsQuery);

// ✅ Fetch all mechanics
$mechQuery = "SELECT id, username FROM users WHERE role='mechanic'";
$mechanics = $conn->query($mechQuery);

// ✅ Handle assignment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $car_id = $_POST['car_id'];
    $mechanic_id = $_POST['mechanic_id'];

    $sql = "INSERT INTO car_assignments (car_id, mechanic_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $car_id, $mechanic_id);

    if ($stmt->execute()) {
        $success = "✅ Mechanic assigned successfully!";
    } else {
        $error = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assign Cars to Mechanics</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* =====================================
           🌸 Coco Crochet Pink Theme (Assign Cars Page)
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
            max-width: 700px;
            background-color: #ffffff;
            margin: 70px auto;
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

        label {
            font-size: 1.1em;
            color: #880e4f;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        select {
            width: 100%;
            padding: 12px 15px;
            font-size: 1em;
            border-radius: 15px;
            border: 1.8px solid #f8bbd0;
            background-color: #fff0f5;
            outline: none;
            transition: all 0.3s ease;
            font-family: 'Edu SA Hand', cursive;
        }

        select:focus {
            border-color: #f48fb1;
            background-color: #ffffff;
            box-shadow: 0 0 6px rgba(244, 143, 177, 0.4);
        }

        button {
            display: block;
            width: 100%;
            background-color: #f48fb1;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            font-family: 'Edu SA Hand', cursive;
        }

        button:hover {
            background-color: #c2185b;
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(194, 24, 91, 0.3);
        }

        p[style*="color:*]()
