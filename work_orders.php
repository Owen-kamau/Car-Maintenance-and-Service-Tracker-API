<?php
session_start();
include("DBConn.php");

// Only mechanics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: index.php");
    exit();
}

$mechanic_id = $_SESSION['user_id'];

// Fetch all assigned cars (assigned + pending + in_progress) for this mechanic
$sql = "SELECT 
            ca.id AS assign_id,
            COALESCE(sr.id, 0) AS service_id,
            COALESCE(sr.service_type, '-') AS service_type,
            COALESCE(sr.description, '-') AS description,
            COALESCE(sr.service_date, '-') AS service_date,
            COALESCE(sr.service_status, 'assigned') AS service_status,
            c.make,
            c.model,
            c.license_plate,
            u.username AS owner_name,
            COALESCE(sr.priority, 0) AS priority
        FROM car_assignments ca
        JOIN cars c ON ca.car_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN service_records sr ON sr.car_id = ca.car_id AND sr.mechanic_id = ca.mechanic_id
        WHERE ca.mechanic_id = ?
        ORDER BY FIELD(COALESCE(sr.service_status,'assigned'),'assigned','pending','in_progress','completed'),
                 COALESCE(sr.priority,0) DESC,
                 COALESCE(sr.service_date,NOW()) DESC";

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
body { font-family:'Roboto',sans-serif; margin:0; background:#121212; color:#eee; display:flex; }
.container { flex:1; padding:20px; }
h1{ color:#00ff7f; font-size:2em; }
h2{ color:#ccc; font-size:1em; margin-bottom:20px; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td{ padding:10px; text-align:left; border-bottom:1px solid #222; }
th{ background:#1f1f1f; color:#00ff7f; }
tr.in_progress{ background:#003300; color:#00ff7f; font-weight:bold; transition:0.3s; }
tr.high-priority{ background:#2a0000 !important; color:#ff4b5c; font-weight:bold; }
button{ padding:6px 12px; border:none; border-radius:5px; background:#00ff7f; color:#121212; cursor:pointer; transition:0.3s; }
button:hover{ background:#00ff7fff; }
button.disabled{ opacity:0.5; pointer-events:none; }
#sidebar { width:300px; background:#1a1a1a; padding:20px; box-shadow: -4px 0 20px rgba(0,0,0,0.5); }
#sidebar h3 { color:#00ff7f; margin-top:0; }
.pending-car { padding:10px; margin-bottom:10px; border-radius:6px; background:#222; cursor:pointer; transition:0.3s; }
.pending-car:hover { background:#00ff7f11; }
.active-timer { font-weight:bold; color:#00ff7f; margin-top:5px; }
.logout { text-align:center; margin-top:20px; }
.logout a { text-decoration:none; color:#ff4b5c; font-weight:600; }
.logout a:hover { text-decoration:underline; }
.search-bar { margin-bottom:15px; padding:8px; width:100%; max-width:400px; border-radius:6px; border:none; background:#222; color:#00ff7f; }
</style>
</head>
<body>

<div class="container">
<h1>🛠 My Work Orders</h1>
<h2>Lock-In Mode: Pick a car to start, finish, then move to next 😁</h2>
<input type="text" class="search-bar" placeholder="Search by Car, Owner, or Status">

<table id="work-orders">
<tr>
<th>Car</th><th>Owner</th><th>Service Type</th><th>Description</th><th>Date</th><th>Status</th><th>Action</th>
</tr>
<?php while($row=$result->fetch_assoc()): ?>
<tr data-id="<?= $row['service_id'] ?>" class="<?= $row['priority']==1?'high-priority':'' ?> <?= $row['service_status']=='in_progress'?'in_progress':'' ?>">
<td><?= htmlspecialchars($row['make'].' '.$row['model'].' ('.$row['license_plate'].')') ?></td>
<td><?= htmlspecialchars($row['owner_name']) ?></td>
<td><?= htmlspecialchars($row['service_type']) ?></td>
<td><input type="text" class="desc" value="<?= htmlspecialchars($row['description']) ?>"></td>
<td><?= htmlspecialchars($row['service_date']) ?></td>
<td class="status"><?= $row['service_status'] ?></td>
<td>
<?php if($row['service_status']=='assigned' || $row['service_status']=='pending'): ?>
<button class="start-btn">🛠 Start Service</button>
<?php elseif($row['service_status']=='in_progress'): ?>
<button class="finish-btn">✅ Dismiss / Complete</button>
<div class="active-timer" data-start="<?= time() ?>"></div>
<?php else: ?>
<span>✔ Completed</span>
<?php endif; ?>
<div class="status-msg"></div>
</td>
</tr>
<?php endwhile; ?>
</table>

<div class="logout"><a href="logout.php">Logout</a></div>
</div>

<div id="sidebar">
<h3>Pending Cars Queue</h3>
<div id="pending-list"></div>
</div>

<script>
// Update pending list
function updatePendingQueue() {
    const pendingList = document.getElementById('pending-list');
    pendingList.innerHTML = '';
    document.querySelectorAll('#work-orders tr').forEach(row => {
        const status = row.querySelector('.status').textContent;
        if (status === 'pending') {
            const carName = row.querySelector('td').innerText;
            const div = document.createElement('div');
            div.className = 'pending-car';
            div.textContent = carName;
            div.onclick = () => row.querySelector('.start-btn').click();
            pendingList.appendChild(div);
        }
    });
}
updatePendingQueue();

// Start Service
function startService(row) {
    const service_id = row.getAttribute('data-id');
    const description = row.querySelector('.desc').value;
    const service_type = row.querySelector('.service_type') ? row.querySelector('.service_type').value : '';

    // Disable all start buttons (lock-in mode)
    document.querySelectorAll('.start-btn').forEach(b => b.classList.add('disabled'));

    fetch('update_service.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `service_id=${service_id}&status=in_progress&description=${encodeURIComponent(description)}&service_type=${encodeURIComponent(service_type)}`
    })
    .then(res => res.text())
    .then(data => {
        row.classList.add('in-progress');
        row.querySelector('.status').textContent = 'in_progress';
        const btnCell = row.querySelector('td:last-child');
        btnCell.innerHTML = '';
        const finishBtn = document.createElement('button');
        finishBtn.textContent = 'Finish';
        finishBtn.className = 'finish-btn';
        btnCell.prepend(finishBtn);

        // Timer
        const timerDiv = document.createElement('div');
        timerDiv.className = 'active-timer';
        timerDiv.dataset.start = Math.floor(Date.now()/1000);
        btnCell.appendChild(timerDiv);

        finishBtn.addEventListener('click', () => finishService(row, timerDiv));
        updatePendingQueue();
    });
}

// Finish Service
function finishService(row, timerDiv) {
    const service_id = row.getAttribute('data-id');
    const description = row.querySelector('.desc').value;
    const service_type = row.querySelector('.service_type') ? row.querySelector('.service_type').value : '';

    fetch('update_service.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `service_id=${service_id}&status=completed&description=${encodeURIComponent(description)}&service_type=${encodeURIComponent(service_type)}`
    })
    .then(res => res.text())
    .then(data => {
        row.classList.remove('in-progress');
        row.querySelector('.status').textContent = 'completed';
        const btnCell = row.querySelector('td:last-child');
        btnCell.innerHTML = '<span>✔ Completed</span>';
        updatePendingQueue();

        // Re-enable start buttons
        document.querySelectorAll('.start-btn').forEach(b => b.classList.remove('disabled'));
    });
}

// Attach event listeners to start buttons
document.querySelectorAll('.start-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        startService(row);
    });
});

// Timer update every second
setInterval(() => {
    document.querySelectorAll('.active-timer').forEach(timer => {
        const start = parseInt(timer.dataset.start);
        const elapsed = Math.floor(Date.now()/1000) - start;
        const mins = Math.floor(elapsed/60);
        const secs = elapsed % 60;
        timer.textContent = `⏱ ${mins}m ${secs}s`;
    });
}, 1000);

// Live search
const searchInput = document.querySelector('.search-bar');
if(searchInput){
    searchInput.addEventListener('input', function(){
        const filter = this.value.toLowerCase();
        document.querySelectorAll('#work-orders tr[data-id]').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
}
</script>

</body>
</html>
