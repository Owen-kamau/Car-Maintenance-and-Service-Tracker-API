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

<style>
  :root{
    --accent:#f4d35e;
    --accent-2:#00b4d8;
    --bg-1:#0b0b0b;
    --panel:#111218;
    --muted:#bdbdbd;
    --glass: rgba(255,255,255,0.03);
  }
  *{box-sizing:border-box}
  body{
    margin:0;
    font-family:"Poppins",system-ui,Segoe UI,Roboto,"Helvetica Neue",Arial;
    background: linear-gradient(180deg,#060606 0%, #0f1113 60%);
    color:#e8e8e8;
    -webkit-font-smoothing:antialiased;
  }
  header{
    padding:18px 28px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    background:linear-gradient(90deg,#0f0f0f,#151515);
    border-bottom:1px solid rgba(255,255,255,0.04);
  }
  .brand {display:flex;gap:12px;align-items:center}
  .logo {
    width:52px;height:52px;border-radius:8px;
    background:linear-gradient(135deg,#111,#222);
    display:flex;align-items:center;justify-content:center;font-family:"Orbitron";
    color:var(--accent); font-weight:700; box-shadow: 0 2px 10px rgba(0,0,0,0.6);
  }
/* --- LUXURY MODERN NAV LINKS --- */
nav {
  display: flex;
  gap: 22px;
  align-items: center;
}

nav a {
  position: relative;
  color: var(--muted);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 10px 16px;
  border-radius: 10px;
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
  border: 1px solid rgba(255,255,255,0.04);
  box-shadow: 0 2px 6px rgba(0,0,0,0.5), inset 0 0 10px rgba(255,255,255,0.02);
  transition: all 0.25s ease;
}

/* glowing hover with soft lift */
nav a:hover {
  color: var(--accent);
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(255,255,255,0.08),
              0 0 12px rgba(255,215,100,0.2);
  border-color: rgba(255,255,255,0.1);
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

/* mobile responsiveness */
@media (max-width: 680px) {
  nav {
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }
  nav a {
    font-size: 0.9rem;
    padding: 8px 10px;
  }
}

  /* Hero section and rest unchanged */
  .hero {
    display:grid;
    grid-template-columns: 1fr 420px;
    gap:24px;
    max-width:1200px;margin:28px auto;padding:18px;
  }
  .panel {
    background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border:1px solid rgba(255,255,255,0.03);
    padding:18px;border-radius:12px;
    box-shadow: 0 6px 30px rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
  }
  .hero-left h1{margin:0;font-size:28px;color:var(--accent)}
  .hero-left p{margin:6px 0 0;color:var(--muted)}
  .stat-grid{display:flex;gap:12px;margin-top:18px}
  .stat{flex:1;padding:14px;border-radius:10px;background:var(--glass);border:1px solid rgba(255,255,255,0.02)}
  .stat h3{margin:0;color:var(--accent)}
  .stat p{margin:8px 0 0;color:var(--muted);font-size:0.95rem}

  .hero-right {
    border-radius:12px;overflow:hidden;position:relative;
    min-height:220px;background-size:cover;background-position:center;
  }
  .badge {
    position:absolute;left:18px;top:18px;background:var(--accent);color:#000;padding:8px 12px;border-radius:999px;font-weight:700;
  }

  /* dashboard cards */
  .cards {max-width:1200px;margin:18px auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;padding:0 18px}
  .card{padding:18px;border-radius:12px;background:linear-gradient(180deg, rgba(255,255,255,0.01), rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.03); min-height:120px;}
  .card h4{margin:0;color:var(--accent)}
  .card p{color:var(--muted);margin:10px 0 0}
  .btn {display:inline-block;padding:8px 12px;border-radius:8px;background:var(--accent-2);color:#000;font-weight:700;text-decoration:none;margin-top:12px}
  .small {font-size:0.9rem;color:var(--muted)}

  /* slideshow */
  .slideshow {position:relative;overflow:hidden;border-radius:10px}
  .slides {display:flex;transition:transform 0.6s ease}
  .slide {min-width:100%;height:180px;background-size:cover;background-position:center;flex-shrink:0}
  .dots {display:flex;gap:6px;justify-content:center;margin-top:10px}
  .dot {width:10px;height:10px;border-radius:50%;background:rgba(255,255,255,0.2)}
  .dot.active {background:var(--accent)}

  footer{padding:14px 18px;text-align:center;color:var(--muted);border-top:1px solid rgba(255,255,255,0.02);margin-top:28px}
  @media (max-width:980px){
    .hero{grid-template-columns:1fr}
    .hero-right{order: -1}
  }
  /* Quick Access Section with SVG Icons */
.quick-access {
  max-width: 1200px;
  margin: 60px auto 80px;
  padding: 0 20px;
  text-align: center;
}
.quick-access h2 {
  color: var(--accent);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 30px;
  font-size: 1.4rem;
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
<header>
  <div class="brand">
    <div class="logo">CMTS</div>
    <div>
      <div style="font-weight:700">Car Maintenance & Tracking</div>
      <div class="small">Vintage aesthetics — modern power</div>
    </div>
  </div>
  <nav> 
    <a href="index.php">Home</a>
    <?php if($isLoggedIn): ?>
      <?php if($role==='admin'): ?><a href="admin_dash.php">Admin</a><?php endif; ?>
      <?php if($role==='mechanic'): ?><a href="mechanic_dash.php">Mechanic</a><?php endif; ?>
      <?php if($role==='owner'): ?><a href="owner_dash.php">Owner</a><?php endif; ?>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="signup.php">Sign Up</a>
    <?php endif; ?>
  </nav>
</header>

<section class="hero">
  <div class="panel hero-left">
    <?php if($isLoggedIn): ?>
      <h1>Welcome back, <?php echo htmlspecialchars($username); ?>.</h1>
      <p class="small">You are signed in as <strong><?php echo ucfirst(htmlspecialchars($role)); ?></strong>. Quick actions below get you where you need in one tap.</p>
    <?php else: ?>
      <h1>Welcome to CMTS</h1>
      <p class="small">Sign up or login to manage cars, services, and work orders — with a vintage vibe.</p>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="stat">
        <h3><?php echo number_format($total_users); ?></h3>
        <p>Registered users</p>
      </div>
      <div class="stat">
        <h3><?php echo number_format($total_cars); ?></h3>
        <p>Total vehicles</p>
      </div>
      <div class="stat">
        <h3><?php echo number_format($upcoming_7days); ?></h3>
        <p>Services due (7 days)</p>
      </div>
    </div>

    <!-- owner quick info -->
    <?php if($isLoggedIn && $role==='owner'): ?>
      <div style="margin-top:14px;" class="panel">
        <h4>Next scheduled service</h4>
        <?php if($next_service): ?>
          <p><?php echo htmlspecialchars($next_service['service_type']); ?> — <?php echo htmlspecialchars($next_service['make'].' '.$next_service['model']); ?></p>
          <p class="small">On <?php echo htmlspecialchars($next_service['next_service_date']); ?></p>
          <a class="btn" href="upcoming_services.php">View Details</a>
        <?php else: ?>
          <p class="small">No upcoming services found. <a href="add_service.php">Schedule one</a>.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>

  <div class="hero-right panel" style="background-image:url('<?php echo htmlspecialchars($hero); ?>');">
    <?php if($upcoming_7days > 0): ?>
      <div class="badge"><?php echo $upcoming_7days; ?> due</div>
    <?php endif; ?>
    <!-- slideshow embedded inside hero-right -->
    <div style="position:absolute;left:0;right:0;bottom:12px;padding:12px">
      <div class="slideshow panel" id="slideshow">
        <div class="slides" id="slides">
          <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1400&q=60')"></div>
          <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?auto=format&fit=crop&w=1400&q=60')"></div>
          <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1400&q=60')"></div>
        </div>
      </div>
      <div class="dots" id="dots"></div>
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
// simple slideshow
(function(){
  const slidesEl = document.getElementById('slides');
  const dotsEl = document.getElementById('dots');
  const slides = slidesEl.children;
  const n = slides.length;
  let idx = 0;
  // create dots
  for(let i=0;i<n;i++){
    const d = document.createElement('div');
    d.className = 'dot' + (i===0? ' active':'');
    d.dataset.i = i;
    d.addEventListener('click', () => { goTo(i); reset(); });
    dotsEl.appendChild(d);
  }
  function show(i){
    slidesEl.style.transform = 'translateX(' + (-i*100) + '%)';
    Array.from(dotsEl.children).forEach((dot,di)=> dot.classList.toggle('active', di===i));
  }
  function goTo(i){ idx = i; show(idx); }
  function next(){ idx = (idx+1) % n; show(idx); }
  let timer = setInterval(next, 4000);
  function reset(){ clearInterval(timer); timer = setInterval(next, 4000); }
})();
</script>
</body>
</html>
