<?php
session_start();
include("DBConn.php");

// Ensure only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: index.php");
    exit();
}

$mechanic_id = $_SESSION['user_id'];

// Fetch assigned cars
$sql = "SELECT c.id, c.make, c.model, c.license_plate
        FROM car_assignments ca
        JOIN cars c ON ca.car_id = c.id
        WHERE ca.mechanic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$cars = $stmt->get_result();

// Fetch all service records assigned to mechanic
$sql2 = "SELECT sr.id, sr.car_id, sr.service_type, sr.description, sr.service_date, sr.service_status,
                c.make, c.model, c.license_plate
         FROM service_records sr
         JOIN cars c ON sr.car_id = c.id
         WHERE sr.mechanic_id = ?
         ORDER BY sr.service_date DESC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $mechanic_id);
$stmt2->execute();
$services = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Service Records</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
    font-family:'Roboto',sans-serif;
    margin:0;
    background:#111;
    color:#f0f0f0;
}
.container {
    max-width:1100px;
    margin:40px auto;
    padding:20px;
}
h1,h2 { color:#00ff7f; text-align:center; }
form, table { background:#1a1a1a; padding:20px; border-radius:12px; margin-bottom:30px; }
input, select, textarea { width:100%; padding:8px; margin-bottom:10px; border-radius:6px; border:none; background:#222; color:#00ff7f; }
button { background:#00ff7f; color:#111; padding:10px 15px; border:none; border-radius:6px; cursor:pointer; transition:0.3s; }
button:hover { background:#00ff7fff; }
table { width:100%; border-collapse:collapse; }
th,td { padding:12px; text-align:left; }
th { background:#222; color:#00ff7f; }
tr:nth-child(even){background:#111;}
tr:hover{background:#00ff7f11;}
.logout{text-align:center;margin-top:20px;}
.logout a{color:#ff4b5c;text-decoration:none;font-weight:600;}
.logout a:hover{text-decoration:underline;}
.message { text-align:center; margin-bottom:15px; padding:10px; border-radius:8px; }
.message.success { background:#00ff7f22; color:#00ff7f; }
.message.error { background:#ff4b5c22; color:#ff4b5c; }
</style>
</head>
<body>
<div class="container">
<h1>🛠 Mechanic Service Records</h1>

<!-- Add New Service -->
<h2>Add New Service</h2>
<div id="add-message"></div>
<form id="addServiceForm">
    <label>Car:</label>
    <select name="car_id" required>
        <?php while($car = $cars->fetch_assoc()): ?>
            <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['make']." ".$car['model']." (".$car['license_plate'].")") ?></option>
        <?php endwhile; ?>
    </select>
    <label>Service Type:</label>
    <input type="text" name="service_type" required>
    <label>Description:</label>
    <textarea name="description"></textarea>
    <label>Service Date:</label>
    <input type="date" name="service_date" required>
    <button type="submit">Add Service</button>
</form>

<!-- Existing Services -->
<h2>My Assigned Services</h2>
<div id="update-message"></div>
<?php if($services && $services->num_rows>0): ?>
<table>
<tr>
<th>Car</th>
<th>Service Type</th>
<th>Description</th>
<th>Date</th>
<th>Status</th>
<th>Action</th>
</tr>
<?php while($row=$services->fetch_assoc()): ?>
<tr data-id="<?= $row['id'] ?>">
    <td><?= htmlspecialchars($row['make']." ".$row['model']." (".$row['license_plate'].")") ?></td>
    <td><input type="text" class="service_type" value="<?= htmlspecialchars($row['service_type']) ?>"></td>
    <td><textarea class="description"><?= htmlspecialchars($row['description']) ?></textarea></td>
    <td><?= htmlspecialchars($row['service_date']) ?></td>
    <td>
        <select class="status">
            <option value="pending" <?= $row['service_status']=='pending'?'selected':'' ?>>Pending</option>
            <option value="in_progress" <?= $row['service_status']=='in_progress'?'selected':'' ?>>In Progress</option>
            <option value="completed" <?= $row['service_status']=='completed'?'selected':'' ?>>Completed</option>
        </select>
    </td>
    <td><button class="update-btn">Update</button></td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p>No services assigned yet.</p>
<?php endif; ?>

<div class="logout">
    <a href="mechanic_dash.php">⬅ Back to Dashboard</a>
</div>
</div>

<script>
// Add new service via AJAX
document.getElementById('addServiceForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    fetch('add_service_ajax.php',{
        method:'POST',
        body:formData
    }).then(res=>res.text())
    .then(data=>{
        document.getElementById('add-message').innerHTML = data;
        this.reset();
    });
});

// Update existing service via AJAX
document.querySelectorAll('.update-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        const row = this.closest('tr');
        const service_id = row.getAttribute('data-id');
        const service_type = row.querySelector('.service_type').value;
        const description = row.querySelector('.description').value;
        const status = row.querySelector('.status').value;

        fetch('update_service_ajax.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`service_id=${service_id}&service_type=${encodeURIComponent(service_type)}&description=${encodeURIComponent(description)}&status=${status}`
        }).then(res=>res.text())
        .then(data=>{
            document.getElementById('update-message').innerHTML = data;
        });
    });
});
</script>
</body>
</html>
