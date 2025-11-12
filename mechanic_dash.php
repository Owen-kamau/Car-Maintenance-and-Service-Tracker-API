<?php
session_start();
include("DBConn.php");

// Only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: index.php");
    exit();
}

$mechanic_id = $_SESSION['user_id'];

// Fetch assigned cars and their service records
$sql = "SELECT sr.id AS service_id, sr.service_type, sr.description, sr.service_date, sr.service_status,
               c.make, c.model, c.license_plate, u.username AS owner_name
        FROM service_records sr
        JOIN cars c ON sr.car_id = c.id
        JOIN users u ON c.user_id = u.id
        WHERE sr.mechanic_id = ?
        ORDER BY sr.service_date DESC";
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
<title>Mechanic Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Roboto', sans-serif;
    margin:0;
    background-color:#111;
    color:#f0f0f0;
}
.container {
    max-width:1100px;
    margin:40px auto;
    padding:20px;
}
header {
    text-align:center;
    margin-bottom:40px;
}
h1 {
    color:#00ff7f;
    font-size:2.2em;
    margin-bottom:10px;
}
table {
    width:100%;
    border-collapse: collapse;
    background:#1a1a1a;
    margin-bottom:30px;
    border-radius:10px;
    overflow:hidden;
}
th, td {
    padding:12px;
    text-align:left;
}
th {
    background:#222;
    color:#00ff7f;
}
tr:nth-child(even) {
    background:#111;
}
tr:hover {
    background:#00ff7f11;
}
input, select {
    background:#222;
    color:#00ff7f;
    border:none;
    padding:6px 8px;
    border-radius:6px;
}
button.update-btn {
    background:#00ff7f;
    color:#111;
    padding:6px 10px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    transition:0.3s;
}
button.update-btn:hover {
    background:#00ff7fff;
}
.logout {
    text-align:center;
    margin-top:20px;
}
.logout a {
    text-decoration:none;
    color:#ff4b5c;
    font-weight:600;
}
.logout a:hover {
    text-decoration:underline;
}
.status-msg {
    font-size:0.9em;
    margin-top:5px;
    color:#00ff7f;
}
.status-msg.error {
    color:#ff4b5c;
}
</style>
</head>
<body>
<div class="container">
<header>
<h1>🛠 Mechanic Dashboard</h1>
<p>Update your assigned service records easily</p>
</header>

<?php if($result && $result->num_rows > 0): ?>
<table>
    <tr>
        <th>Car</th>
        <th>Owner</th>
        <th>Service Type</th>
        <th>Description</th>
        <th>Service Date</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr data-id="<?= $row['service_id'] ?>">
        <td><?= htmlspecialchars($row['make'] . ' ' . $row['model'] . ' (' . $row['license_plate'] . ')') ?></td>
        <td><?= htmlspecialchars($row['owner_name']) ?></td>
        <td><?= htmlspecialchars($row['service_type']) ?></td>
        <td><input type="text" class="desc" value="<?= htmlspecialchars($row['description']) ?>"></td>
        <td><?= htmlspecialchars($row['service_date']) ?></td>
        <td>
            <select class="status">
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
<p>No assigned service records.</p>
<?php endif; ?>

<div class="logout">
    <a href="logout.php">Logout</a>
</div>
</div>

<script>
document.querySelectorAll('.update-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        const row = this.closest('tr');
        const id = row.getAttribute('data-id');
        const description = row.querySelector('.desc').value.trim();
        const status = row.querySelector('.status').value;
        const msgDiv = row.querySelector('.status-msg');

        // Simple validation
        if(description.length < 3){
            msgDiv.textContent = "Description too short!";
            msgDiv.classList.add('error');
            return;
        }

        fetch('update_service_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type':'application/x-www-form-urlencoded' },
            body: `service_id=${id}&description=${encodeURIComponent(description)}&status=${status}`
        })
        .then(res=>res.text())
        .then(data=>{
            msgDiv.textContent = data;
            msgDiv.classList.remove('error');
        })
        .catch(err=>{
            msgDiv.textContent = "Update failed. Try again.";
            msgDiv.classList.add('error');
        });
    });
});
</script>
</body>
</html>
