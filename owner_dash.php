<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

include("DBConn.php");

$userId = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// --- Fetch Total Cars ---
$totalCars = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM cars WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) $totalCars = $row['count'];
$stmt->close();

// --- Fetch Upcoming Services ---
$upcomingCount = 0;
$nextServiceDate = null;
$stmt = $conn->prepare("
    SELECT COUNT(*) AS count, MIN(next_service_date) AS next_date
    FROM services s
    JOIN cars c ON s.car_id = c.id
    WHERE c.user_id = ? AND s.next_service_date >= CURDATE()
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $upcomingCount = $row['count'];
    $nextServiceDate = $row['next_date'];
}
$stmt->close();

// --- Fetch Last Completed Service ---
$lastService = null;
$stmt = $conn->prepare("
    SELECT s.service_type, s.service_date, c.make, c.model
    FROM services s
    JOIN cars c ON s.car_id = c.id
    WHERE c.user_id = ? AND s.service_date IS NOT NULL
    ORDER BY s.service_date DESC
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $lastService = $res->fetch_assoc();
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Dashboard | CMTS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Quicksand:wght@500&display=swap" rel="stylesheet">
<style>
:root {
  --accent: #b89b5e;
  --accent-glow: rgba(184,155,94,0.25);
  --bg: #f7f7f9;
  --panel: #fff;
  --text: #222;
  --muted: #666;
}
* { box-sizing:border-box; margin:0; padding:0; }
body {
  font-family: "Poppins", sans-serif;
  background: linear-gradient(180deg,#f9f9fa 0%, #e9e9eb 100%);
  color: var(--text);
  min-height: 100vh;
  display:flex;
  flex-direction:column;
}
header {
  background: var(--panel);
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  padding: 20px 40px;
  display:flex;
  align-items:center;
  justify-content:space-between;
}
.brand {
  font-family: "Quicksand";
  font-weight:600;
  font-size:1.4rem;
  color: var(--text);
}
.brand span { color: var(--accent); }
header a {
  color: var(--muted);
  text-decoration:none;
  margin-left:20px;
  font-weight:500;
}
header a:hover { color: var(--accent); }

main {
  flex:1;
  display:flex;
  flex-direction:column;
  align-items:center;
  padding: 50px 20px;
}

.stats {
  display:grid;
  grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
  gap:20px;
  max-width:1000px;
  margin-bottom:40px;
  width:100%;
}
.stat-card {
  background: var(--panel);
  border-radius:14px;
  padding:25px;
  text-align:center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
  border:1px solid rgba(0,0,0,0.05);
}
.stat-card:hover {
  box-shadow: 0 8px 35px var(--accent-glow);
  transform: translateY(-4px);
}
.stat-card h2 {
  font-size:2rem;
  color: var(--accent);
  margin-bottom:8px;
}
.stat-card p { color: var(--muted); font-weight:500; }

.dashboard {
  display:grid;
  grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
  gap:25px;
  width:100%;
  max-width:1000px;
}
.card {
  background: var(--panel);
  border-radius:16px;
  padding:25px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
  border:1px solid rgba(0,0,0,0.05);
  transition: all 0.25s ease;
}
.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px var(--accent-glow);
}
.card h3 {
  font-weight:600;
  margin-bottom:10px;
}
.card p {
  color: var(--muted);
  font-size:0.95rem;
}
.card a {
  display:inline-block;
  margin-top:14px;
  background: var(--accent);
  color:#fff;
  text-decoration:none;
  padding:10px 16px;
  border-radius:8px;
  font-weight:500;
}
.card a:hover { background:#a48449; }

footer {
  text-align:center;
  padding:20px;
  font-size:0.9rem;
  color:var(--muted);
  border-top:1px solid #ddd;
  background:#fff9;
}
</style>
</head>
<body>
<header>
  <div class="brand">CMTS <span>Owner</span> Dashboard</div>
  <nav>
    <a href="index.php">Home</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main>
  <h1 style="font-family:'Quicksand'; font-weight:600; margin-bottom:20px;">Welcome back, <?php echo $username; ?> 👋</h1>

  <div class="stats">
    <div class="stat-card">
      <h2><?php echo $totalCars; ?></h2>
      <p>Registered Cars</p>
      <img src="<?php echo !empty($row['car_image']) ? $row['car_image'] : 'default-car.jpg'; ?>" 
     alt="Car Image" width="200" style="border-radius:10px;">
    </div>
    <div class="stat-card">
      <h2><?php echo $upcomingCount; ?></h2>
      <p>Upcoming Services</p>
    </div>
    <div class="stat-card">
      <h2><?php echo $nextServiceDate ? date("M j, Y", strtotime($nextServiceDate)) : "-"; ?></h2>
      <p>Next Service Date</p>
    </div>
  </div>

  <?php if ($lastService): ?>
  <div style="max-width:800px; background:#fff; border-radius:16px; padding:25px; box-shadow:0 4px 20px rgba(0,0,0,0.05); margin-bottom:40px;">
    <h3 style="color:var(--accent); margin-bottom:8px;">Last Service Summary</h3>
    <p><strong><?php echo htmlspecialchars($lastService['make'].' '.$lastService['model']); ?></strong></p>
    <p>Service Type: <?php echo htmlspecialchars($lastService['service_type']); ?></p>
    <p>Date: <?php echo date("M j, Y", strtotime($lastService['service_date'])); ?></p>
  </div>
  <?php endif; ?>

  <div class="dashboard">
    <div class="card">
      <h3>Register a New Car</h3>
      <p>Add a new vehicle to your profile and keep its records safe.</p>
      <a href="CarReg.php">Register Car</a>
    </div>
    <div class="card">
      <h3>My Cars</h3>
      <p>View, update or remove your registered cars.</p>
      <a href="View_Cars">View Cars</a>
    </div>
    <div class="card">
      <h3>Book a Service</h3>
      <p>Schedule maintenance or repairs for any of your vehicles.</p>
      <a href="service_booking">Book Service</a>
    </div>
    <div class="card">
      <h3>Track Services</h3>
      <p>Monitor the progress and status of your booked services.</p>
      <a href="view_services.php">Track Services</a>
    </div>
    <div class="card">
      <h3>Service History</h3>
      <p>Review completed services and maintenance records.</p>
      <a href="service_history.php">View History</a>
    </div>
    <div class="card">
      <h3>Upcoming Reminders</h3>
      <p>Check upcoming maintenance schedules and alerts.</p>
      <a href="upcoming_services.php">View Reminders</a>
    </div>
  </div>
</main>

<footer>
  &copy; <?php echo date("Y"); ?> Car Maintenance & Tracking System — Crafted with Precision.
</footer>
</body>
</html>
