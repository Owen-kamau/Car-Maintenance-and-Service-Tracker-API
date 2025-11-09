<?php 
session_start();
include("DBConn.php");

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

    // Check if assignment already exists
    $check = $conn->prepare("SELECT * FROM car_assignments WHERE car_id=? AND mechanic_id=?");
    $check->bind_param("ii", $car_id, $mechanic_id);
    $check->execute();
    $resCheck = $check->get_result();

    if($resCheck->num_rows > 0){
        $error = "❌ This mechanic is already assigned to this car!";
    } else {
        $sql = "INSERT INTO car_assignments (car_id, mechanic_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $car_id, $mechanic_id);

        if ($stmt->execute()) {
            $success = "✅ Mechanic assigned successfully!";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Cars to Mechanics</title>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
/* 🌌 Matrix / metallic modern admin theme */
body { font-family: 'Roboto', sans-serif; background:#111; color:#f0f0f0; margin:0; padding:0; }
header { background:#1c1c1c; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 10px #000; }
header h1 { color:#00ff7f; margin:0; font-size:1.8em; }
header a.logout { color:#fff; background:#ff4b5c; padding:8px 16px; border-radius:6px; text-decoration:none; font-weight:500; }
header a.logout:hover { box-shadow:0 0 10px #ff4b5c; transform:translateY(-2px); }

.container { max-width:800px; margin:50px auto; background:#1f1f1f; padding:30px; border-radius:15px; box-shadow:0 0 20px #00ff7f44; }
.container h2 { text-align:center; color:#00ff7f; margin-bottom:20px; }

label { display:block; margin-top:15px; font-weight:600; color:#00ff7f; }
select { width:100%; padding:10px; border-radius:8px; border:none; background:#222; color:#f0f0f0; margin-top:5px; }
select:focus { outline:none; box-shadow:0 0 10px #00ff7f66; }

button { display:block; width:100%; margin-top:20px; padding:12px; border:none; border-radius:12px; font-weight:600; font-size:1.1em; cursor:pointer; background:#00ff7f; color:#000; transition:0.3s; }
button:hover { background:#00cc6a; transform:translateY(-2px); box-shadow:0 0 15px #00ff7f66; }

table { width:100%; border-collapse:collapse; margin-top:30px; background:#222; border-radius:10px; overflow:hidden; box-shadow:0 0 15px #00ff7f33; }
table th, table td { padding:12px 15px; text-align:left; }
table th { background:#000; color:#00ff7f; }
table tr:hover { background:#00ff7f22; transform:scale(1.01); transition:0.2s; }
.actions a { margin-right:10px; text-decoration:none; padding:6px 12px; border-radius:6px; font-weight:500; color:#fff; }
.actions a.delete { background:#ff4b5c; }
.actions a.delete:hover { box-shadow:0 0 10px #ff4b5c; }

.message { text-align:center; margin-top:20px; padding:10px; border-radius:8px; }
.success { background:#00ff7f33; color:#00ff7f; }
.error { background:#ff4b5c33; color:#ff4b5c; }
</style>
</head>
<body>

<header>
<h1>Assign Cars to Mechanics</h1>
<a href="admin_dash.php" class="logout">Back to Dashboard</a>
</header>

<div class="container">
    <?php if(isset($success)) echo "<p class='message success'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p class='message error'>$error</p>"; ?>

    <form method="POST" id="assignForm">
        <label>Select Car</label>
        <select name="car_id" required>
            <option value="">-- Choose Car --</option>
            <?php while($car=$cars->fetch_assoc()): ?>
                <option value="<?= $car['id'] ?>"><?= $car['make'] ?> <?= $car['model'] ?> (<?= $car['license_plate'] ?>) - Owner: <?= $car['username'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Select Mechanic</label>
        <select name="mechanic_id" required>
            <option value="">-- Choose Mechanic --</option>
            <?php while($mech=$mechanics->fetch_assoc()): ?>
                <option value="<?= $mech['id'] ?>"><?= $mech['username'] ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Assign Mechanic</button>
    </form>

    <h2>Assigned Cars</h2>
    <table>
        <thead>
            <tr>
                <th>Car</th>
                <th>Mechanic</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="assignmentsBody">
            <?php
            $assignmentsQuery = "SELECT ca.id as assign_id, c.make, c.model, c.license_plate, u.username as owner_name, m.username as mech_name
                                 FROM car_assignments ca
                                 JOIN cars c ON ca.car_id=c.id
                                 JOIN users u ON c.user_id=u.id
                                 JOIN users m ON ca.mechanic_id=m.id";
            $assignments = $conn->query($assignmentsQuery);
            while($a=$assignments->fetch_assoc()):
            ?>
            <tr>
                <td><?= $a['make'] ?> <?= $a['model'] ?> (<?= $a['license_plate'] ?>)</td>
                <td><?= $a['mech_name'] ?></td>
                <td class="actions">
                    <a href="delete_assignment.php?id=<?= $a['assign_id'] ?>" class="delete" onclick="return confirm('Delete this assignment?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>
