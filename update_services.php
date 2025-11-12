<?php
session_start();
include("DBConn.php");

// Only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    echo "<div class='message error'>Unauthorized</div>";
    exit();
}

$mechanic_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id   = $_POST['service_id'] ?? 0;
    $car_id       = $_POST['car_id'] ?? 0; // new
    $status       = $_POST['status'] ?? '';
    $description  = $_POST['description'] ?? '';
    $service_type = $_POST['service_type'] ?? '';

    $valid_statuses = ['pending','in_progress','completed'];
    if (!in_array($status, $valid_statuses)) {
        echo "<div class='message error'>Invalid input</div>";
        exit();
    }

    // If service_id = 0, create a new service record first
    if ($service_id == 0 && $car_id > 0) {
        $stmtInsert = $conn->prepare("INSERT INTO service_records (car_id, mechanic_id, service_type, description, service_date, service_status, priority) VALUES (?, ?, ?, ?, NOW(), ?, 0)");
        $stmtInsert->bind_param("iisss", $car_id, $mechanic_id, $service_type, $description, $status);
        if ($stmtInsert->execute()) {
            $service_id = $stmtInsert->insert_id;
        } else {
            echo "<div class='message error'>❌ Could not create service: ".$stmtInsert->error."</div>";
            exit();
        }
    }

    // Fetch current status for workflow enforcement
    $stmtCheck = $conn->prepare("SELECT service_status FROM service_records WHERE id=? AND mechanic_id=?");
    $stmtCheck->bind_param("ii", $service_id, $mechanic_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows === 0) {
        echo "<div class='message error'>Service not found or not assigned to you</div>";
        exit();
    }

    $current = $resultCheck->fetch_assoc()['service_status'];

    // Workflow transitions
    $valid_transitions = [
        'pending'     => ['pending','in_progress'],
        'in_progress' => ['in_progress','completed'],
        'completed'   => ['completed']
    ];

    if (!in_array($status, $valid_transitions[$current])) {
        echo "<div class='message error'>Cannot change status from '$current' to '$status'</div>";
        exit();
    }

    // Update service record
    $sql = "UPDATE service_records 
            SET service_status=?, description=?, service_type=? 
            WHERE id=? AND mechanic_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $status, $description, $service_type, $service_id, $mechanic_id);

    if ($stmt->execute()) {
        $statusEmoji = $status === 'completed' ? "✔" : ($status === 'in_progress' ? "⏳" : "");
        echo "<div class='message success'>✅ Service updated to '$status' $statusEmoji</div>";
    } else {
        echo "<div class='message error'>❌ Update failed: ".$stmt->error."</div>";
    }

} else {
    echo "<div class='message error'>Invalid request</div>";
}
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

// Event delegation for update buttons (start/finish/update)
document.addEventListener('click', function(e){
    if(e.target.classList.contains('update-btn') || e.target.classList.contains('start-btn') || e.target.classList.contains('finish-btn')){
        const row = e.target.closest('tr');
        const service_id = row.dataset.id;
        const car_id = row.dataset.carId || 0;
        const service_type = row.querySelector('.service_type') ? row.querySelector('.service_type').value : '';
        const description = row.querySelector('.description') ? row.querySelector('.description').value : '';
        let status = '';

        if(e.target.classList.contains('start-btn')){
            status = 'in_progress';
        } else if(e.target.classList.contains('finish-btn')){
            status = 'completed';
        } else {
            status = row.querySelector('.status').value;
        }

        fetch('update_service_ajax.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`service_id=${service_id}&car_id=${car_id}&service_type=${encodeURIComponent(service_type)}&description=${encodeURIComponent(description)}&status=${status}`
        }).then(res=>res.text())
        .then(data=>{
            document.getElementById('update-message').innerHTML = data;

            // Update row UI for start/finish
            if(status === 'in_progress'){
                row.classList.add('in-progress');
                if(row.querySelector('.start-btn')) row.querySelector('.start-btn').remove();
                const finishBtn = document.createElement('button');
                finishBtn.textContent = 'Finish';
                finishBtn.className = 'finish-btn';
                row.querySelector('td:last-child').prepend(finishBtn);
            } else if(status === 'completed'){
                row.classList.remove('in-progress');
                if(row.querySelector('.finish-btn')) row.querySelector('.finish-btn').remove();
                row.querySelector('td:last-child').innerHTML = '<span>✔ Completed</span>';
            }

            row.querySelector('.status').textContent = status;
        });
    }
});

</script>
</body>
</html>
