<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

include("DBConn.php");

// session variables assumed set at login
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : null;
$username = $isLoggedIn ? $_SESSION['username'] : null;
$role = $isLoggedIn ? $_SESSION['role'] : 'guest';

// --- SMART STATS ---
// total users
$total_users = 0;
if ($res = $conn->query("SELECT COUNT(*) AS cnt FROM users")) {
    $row = $res->fetch_assoc();
    $total_users = (int)$row['cnt'];
    $res->free();
}

// total cars
$total_cars = 0;
if ($res = $conn->query("SELECT COUNT(*) AS cnt FROM cars")) {
    $row = $res->fetch_assoc();
    $total_cars = (int)$row['cnt'];
    $res->free();
}

// upcoming services (global within next 7 days)
$upcoming_7days = 0;
if ($res = $conn->query("SELECT COUNT(*) AS cnt FROM services WHERE next_service_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")) {
    $row = $res->fetch_assoc();
    $upcoming_7days = (int)$row['cnt'];
    $res->free();
}

// mechanic assigned cars (if mechanic)
$assigned_count = 0;
if ($isLoggedIn && $role === 'mechanic') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM car_assignments WHERE mechanic_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $r = $res->fetch_assoc()) $assigned_count = (int)$r['cnt'];
    $stmt->close();
}

// owner: next upcoming service for this owner
$next_service = null;
if ($isLoggedIn && $role === 'owner') {
    $stmt = $conn->prepare("
        SELECT s.service_type, s.next_service_date, c.make, c.model
        FROM services s
        JOIN cars c ON s.car_id = c.id
        WHERE c.user_id = ? AND s.next_service_date >= CURDATE()
        ORDER BY s.next_service_date ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows) {
        $next_service = $res->fetch_assoc();
    }
    $stmt->close();
}

// helper: role->hero image mapping
$role_hero = [
    'admin' => 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?auto=format&fit=crop&w=1400&q=60', // Bugatti-ish
    'mechanic' => 'https://images.unsplash.com/photo-1519643381401-22c77e60520e?auto=format&fit=crop&w=1400&q=60', // garage
    'owner' => 'https://images.unsplash.com/photo-1511398591251-28f6f165b6d0?auto=format&fit=crop&w=1400&q=60', // classic car
    'guest' => 'https://cdn.pixabay.com/photo/2016/03/09/09/16/car-1245741_1280.jpg'
];
$hero = $role_hero[$role] ?? $role_hero['guest'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CMTS Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="theme.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<style>
@import url('https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@500&display=swap');

body {
    font-family: 'Edu SA Hand', cursive;
    background: linear-gradient(135deg, #fff0f5, #ffe6f0, #ffd6e8);
    margin: 0;
    padding: 0;
    display: flex;
    height: 100vh;
    overflow: hidden;
}

/* Sidebar Section */
.sidebar {
    width: 35%;
    background: #fffafa;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 30px;
    box-shadow: 3px 0 10px rgba(255, 182, 193, 0.3);
    text-align: center;
    z-index: 2;
}

/* elegant underline animation */
nav a::after {
  content: "";
  position: absolute;
  bottom: 5px;
  left: 50%;
  width: 0%;
  height: 2px;
  background: var(--accent);
  transition: width 0.3s ease, left 0.3s ease;
  border-radius: 2px;
  opacity: 0.8;
}

nav a:hover::after {
  width: 60%;
  left: 20%;
}

/* active link (current page) */
nav a:focus,
nav a:active {
  color: var(--accent-2);
  box-shadow: 0 0 10px rgba(0,180,216,0.3);
  outline: none;
}

/* logout link - distinct red hue */
nav a[href*="logout"] {
  color: #ff7676;
  background: rgba(255,0,0,0.06);
  border: 1px solid rgba(255,0,0,0.15);
}

nav a[href*="logout"]:hover {
  color: #ffb3b3;
  transform: translateY(-3px);
  box-shadow: 0 0 12px rgba(255,0,0,0.2);
  border-color: rgba(255,0,0,0.25);
}

.nav-links {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.nav-links a {
    text-decoration: none;
    color: white;
    background: linear-gradient(90deg, #ff9eb8, #ffb6c1, #ffcce0);
    padding: 12px 30px;
    border-radius: 25px;
    font-size: 1.1em;
    font-weight: bold;
    transition: 0.3s;
}

.nav-links a:hover {
    background: linear-gradient(90deg, #ff7ca3, #ff94b6, #ffb6c1);
    transform: translateY(-2px);
}

/* Slideshow Section */
.slideshow-container {
    width: 65%;
    position: relative;
    overflow: hidden;
}

.mySlides {
    position: absolute;
    width: 100%;
    height: 100vh;
    opacity: 0;
    transition: opacity 2s ease-in-out;
}

.mySlides img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Active slide */
.active {
    opacity: 1;
    z-index: 1;
}
.qa-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
}
.qa-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 14px;
  padding: 25px 15px;
  text-decoration: none;
  color: var(--muted);
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.qa-card .icon {
  width: 36px;
  height: 36px;
  fill: var(--muted);
  margin-bottom: 10px;
  transition: fill 0.3s ease, transform 0.3s ease;
}
.qa-card span {
  font-weight: 600;
  font-size: 0.95rem;
  letter-spacing: 0.3px;
}
.qa-card:hover {
  transform: translateY(-4px);
  color: var(--accent);
  border-color: rgba(255,255,255,0.15);
  box-shadow: 0 8px 25px rgba(255,255,255,0.08);
}
.qa-card:hover .icon {
  fill: var(--accent);
  transform: scale(1.1);
}
.qa-card.logout {
  background: rgba(255,0,0,0.05);
  border-color: rgba(255,0,0,0.15);
}
.qa-card.logout:hover {
  color: #ff6b6b;
  box-shadow: 0 0 10px rgba(255,0,0,0.15);
}
.qa-card.logout:hover .icon {
  fill: #ff6b6b;
}

</style>


</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h1>🚗 CarMaintenance</h1>
    <p>Keep your car running smoothly with style — track, manage, and maintain effortlessly.</p>
    <div class="nav-links">
        <a href="login.php">🔑 Login</a>
        <a href="signup.php">📝 Sign Up</a>
    </div>
</div>

<!-- Slideshow -->
<div class="slideshow-container">
    <div class="mySlides active">
        <img src="https://images.unsplash.com/photo-1541447271487-0963b1e4c6d0?auto=format&fit=crop&w=1500&q=80" alt="Car 1">
    </div>
    <div class="mySlides">
        <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1500&q=80" alt="Car 2">
    </div>
    <div class="mySlides">
        <img src="https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?auto=format&fit=crop&w=1500&q=80" alt="Car 3">
    </div>
  </div>
</section>


<section class="quick-access">
  <h2>Quick Access</h2>
  <div class="qa-grid">
    <?php if(!$isLoggedIn): ?>
      <a href="signup.php" class="qa-card">
        <svg class="icon" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5S14.7 2 12 2 7 4.3 7 7s2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v2h20v-2c0-3.3-6.7-5-10-5z"/></svg>
        <span>Create Account</span>
      </a>
      <a href="login.php" class="qa-card">
        <svg class="icon" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5v3H3v4h7v3zm9-14H5c-1.1 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
        <span>Login</span>
      </a>
      <a href="about.php" class="qa-card">
        <svg class="icon" viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        <span>Learn More</span>
      </a>
    <?php else: ?>

      <?php if($role === 'admin'): ?>
        <a href="manage_users.php" class="qa-card">
          <svg class="icon" viewBox="0 0 24 24"><path d="M16 11c1.7 0 3-1.3 3-3s-1.3-3-3-3-3 1.3-3 3 1.3 3 3 3zm-8 0c1.7 0 3-1.3 3-3S9.7 5 8 5 5 6.3 5 8s1.3 3 3 3zm0 2c-2.3 0-7 1.2-7 3.5V19h8v-2c0-.7.3-1.3.7-1.8-.4-.1-.9-.2-1.7-.2zm8 0c-.8 0-1.3.1-1.7.2.4.5.7 1.1.7 1.8v2h8v-2.5c0-2.3-4.7-3.5-7-3.5z"/></svg>
          <span>Manage Users</span>
        </a>
        <a href="reports.php" class="qa-card">
          <svg class="icon" viewBox="0 0 24 24"><path d="M3 13h2v8H3zm4-5h2v13H7zm4 3h2v10h-2zm4-6h2v16h-2zm4 9h2v7h-2z"/></svg>
          <span>Reports</span>
        </a>
        <a href="upcoming_services.php" class="qa-card">
          <svg class="icon" viewBox="0 0 24 24"><path d="M12 8v5l4 2 .7-1.4-3.2-1.6V8zM19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v16c0 
          1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H5V8h14v13z"/></svg>
          <span>Upcoming</span>
        </a>

      <?php elseif($role === 'mechanic'): ?>
        <a href="assigned_cars.php" class="qa-card">
          <svg class="icon" viewBox="0 0 24 24"><path d="M17.3 6.3l-1.6 1.6c-.2-.1-.5-.1-.7-.1-.6 0-1.2.2-1.7.7-1 1-1 2.6 0 3.5.5.5 1.1.7 1.7.7.6 0 1.3-.2 1.8-.7.9-.9 1-2.3.2-3.3l1.5-1.5-1.2-1.2zM12 2C6.5 2 2 6.5 2 12c0 1.7.4 3.2 1 4.6L12 22l9-5.4c.6-1.4 1-2.9 1-4.6 0-5.5-4.5-10-10-10z"/></svg>
          <span>Assigned Cars</span>
        </a>
        <a href="work_orders.php" class="qa-card">
          <svg class="icon" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16l4-4h6c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm7 
          6h-4v2h4v10H8v-2H6v2c0 1.1.9 2 2 2h13c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2z"/></svg>
          <span>Work Orders</span>
        </a>

      <?php elseif($role === 'owner'): ?>
        <a href="owner_dash.php" class="qa-card">
          <svg class="icon" viewBox="0 0 24 24"><path d="M20 8h-3V4H7v4H4c-1.1 0-2 .9-2 2v9h2v3h2v-3h12v3h2v-3h2v-9c0-1.1-.9-2-2-2zM7 
          8V6h10v2H7z"/></svg>
          <span>My Garage</span>
        </a>
        <a href="add_service.php" class="qa-card">
          <svg class="icon" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2zM9 11H7v2h2v-2zm4 
          0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>
          <span>Schedule Service</span>
        </a>
        <a href="service_history.php" class="qa-card">
          <svg class="icon" viewBox="0 0 24 24"><path d="M13 3a9 9 0 109 9h-2a7 7 0 11-7-7V3l3 3-3 3V6a6 6 0 106 6h2a8 8 0 11-8-8z"/></svg>
          <span>Service History</span>
        </a>
      <?php endif; ?>

      <a href="logout.php" class="qa-card logout">
        <svg class="icon" viewBox="0 0 24 24"><path d="M16 13v-2H7V8l-5 4 5 4v-3h9zm3-10H5c-1.1 0-2 
        .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.9 2 2 2h14c1.1 
        0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
        <span>Logout</span>
      </a>
    <?php endif; ?>
  </div>
</section>


<footer>
  &copy; <?php echo date('Y'); ?> CMTS • Vintage cars • Modern tracking
</footer>

<script>
// Smooth fading slideshow
let slideIndex = 0;
const slides = document.getElementsByClassName("mySlides");

function showSlides() {
    for (let i = 0; i < slides.length; i++) {
        slides[i].classList.remove("active");
    }
    slideIndex++;
    if (slideIndex > slides.length) {slideIndex = 1}
    slides[slideIndex - 1].classList.add("active");
    setTimeout(showSlides, 4000); // Change every 4 seconds
}

// Start after short delay to avoid "Car 1" static pause
setTimeout(showSlides, 1000);
</script>
</body>
</html>