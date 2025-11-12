<?php
session_start();
include("DBConn.php");

// Only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: index.php");
    exit();
}

$mechanic_id = $_SESSION['user_id'];

// Fetch assigned work orders with owner and car info
$sql = "SELECT sr.id AS service_id, sr.service_type, sr.description, sr.service_date, sr.service_status,
               c.make, c.model, c.license_plate, u.username AS owner_name, sr.priority
        FROM service_records sr
        JOIN cars c ON sr.car_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sr.mechanic_id = ?
        ORDER BY FIELD(sr.service_status,'pending','in_progress','completed'), sr.priority DESC, sr.service_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🛠 Mechanic Work Orders</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
    font-family:'Roboto',sans-serif;
    margin:0;
    background:#121212;
    color:#eee;
}
.container {
    max-width:1200px;
    margin:40px auto;
    padding:20px;
}
header {
    text-align:center;
    margin-bottom:30px;
}
h1 { color:#00ff7f; font-size:2.5em; margin-bottom:5px; }
h2 { color:#ccc; font-size:1.2em; }
table {
    width:100%; border-collapse:collapse; border-radius:10px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.5);
}
th,td { padding:12px; text-align:left; }
th { background:#1f1f1f; color:#00ff7f; }
tr:nth-child(even){ background:#171717; }
tr:hover { background:#00ff7f11; }
input, select { background:#222; color:#00ff7f; border:none; padding:6px 8px; border-radius:6px; width:100%; }
button { background:#00ff7f; color:#121212; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; transition:0.3s; }
button:hover { background:#00ff7fff; }
.status-msg { font-size:0.9em; margin-top:3px; }
.status-msg.error { color:#ff4b5c; }
.high-priority { background:#2a0000 !important; color:#ff4b5c; font-weight:bold; }
.search-bar { margin-bottom:15px; padding:8px; width:100%; max-width:400px; border-radius:6px; border:none; background:#222; color:#00ff7f; }
.logout { text-align:center; margin-top:20px; }
.logout a { text-decoration:none; color:#ff4b5c; font-weight:600; }
.logout a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="container">
<header>
<h1>🛠 My Work Orders</h1>
<h2>Update status, description, and stay on top of your assignments 😁</h2>
<input type="text" class="search-bar" placeholder="Search by Car, Owner or Status">
</header>

<?php if($result && $result->num_rows > 0): ?>
<table id="work-orders">
<tr>
    <th>Car</th>
    <th>Owner</th>
    <th>Service Type</th>
    <th>Description</th>
    <th>Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>
<?php while($row = $result->fetch_assoc()): ?>
<tr data-id="<?= $row['service_id'] ?>" class="<?= $row['priority']==1?'high-priority':'' ?>">
    <td><?= htmlspecialchars($row['make'].' '.$row['model'].' ('.$row['license_plate'].')') ?></td>
    <td><?= htmlspecialchars($row['owner_name']) ?></td>
    <td><?= htmlspecialchars($row['service_type']) ?></td>
    <td><input type="text" class="desc" value="<?= htmlspecialchars($row['description']) ?>"></td>
    <td><?= htmlspecialchars($row['service_date']) ?></td>
    <td>
        <select class="status" data-current-status="<?= $row['service_status'] ?>">
            <option value="pending" <?= $row['service_status']=='pending'?'selected':'' ?>>Pending</option>
            <option value="in_progress" <?= $row['service_status']=='in_progress'?'selected':'' ?>>In Progress</option>
            <option value="completed" <?= $row['service_status']=='completed'?'selected':'' ?>>Completed</option>
        </select>
    </td>
    <td>
        <button class="update-btn">Update</button>
        <div class="status-msg"></div>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p>No work orders assigned 😎</p>
<?php endif; ?>

<div class="logout">
<a href="logout.php">Logout</a>
</div>
</div>

<script>
// Inline update with workflow enforcement
document.querySelectorAll('.update-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        const row = this.closest('tr');
        const id = row.getAttribute('data-id');
        const descInput = row.querySelector('.desc');
        const statusSelect = row.querySelector('.status');
        const description = descInput.value.trim();
        const status = statusSelect.value;
        const currentStatus = statusSelect.getAttribute('data-current-status');
        const msgDiv = row.querySelector('.status-msg');

        // Description validation
        if(description.length < 3){
            msgDiv.textContent = "Description too short!";
            msgDiv.classList.add('error');
            return;
        }

        // Workflow enforcement
        const validTransitions = {
            'pending': ['pending','in_progress'],
            'in_progress': ['in_progress','completed'],
            'completed': ['completed']
        };
        if(!validTransitions[currentStatus].includes(status)){
            msgDiv.textContent = `Cannot change ${currentStatus} → ${status}`;
            msgDiv.classList.add('error');
            return;
        }

        fetch('update_service_ajax.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`service_id=${id}&description=${encodeURIComponent(description)}&status=${status}`
        })
        .then(res=>res.text())
        .then(data=>{
            msgDiv.textContent = data;
            msgDiv.classList.remove('error');
            statusSelect.setAttribute('data-current-status', status);
        })
        .catch(err=>{
            msgDiv.textContent = "Update failed. Try again!";
            msgDiv.classList.add('error');
        });
    });
});

// Live search
const searchInput = document.querySelector('.search-bar');
searchInput.addEventListener('input', function(){
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#work-orders tr[data-id]').forEach(row=>{
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter)?'':'none';
    });
});
</script>
</body>
</html>
