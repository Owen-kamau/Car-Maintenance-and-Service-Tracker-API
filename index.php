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

  /* ✨ Enhanced Navigation Bar with Luxury Hover + Power-On Animation ✨ */
  nav {
    background-color: #1f1f1f;
    display: flex;
    justify-content: center;
    gap: 30px;
    padding: 15px;
    border-bottom: 1px solid #333;
    position: relative;
    opacity: 0;
    transform: translateY(-20px);
    animation: navFadeIn 1s ease forwards;
  }

  @keyframes navFadeIn {
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  nav::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, #b89b5e, transparent);
    opacity: 0.2;
  }

  nav a {
    position: relative;
    color: var(--muted);
    text-decoration: none;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: color 0.3s ease, transform 0.3s ease;
    padding: 8px 12px;
    opacity: 0;
    animation: linkFade 0.8s ease forwards;
  }

  /* Staggered delays for a smooth rollout */
  nav a:nth-child(1) { animation-delay: 0.3s; }
  nav a:nth-child(2) { animation-delay: 0.45s; }
  nav a:nth-child(3) { animation-delay: 0.6s; }
  nav a:nth-child(4) { animation-delay: 0.75s; }
  nav a:nth-child(5) { animation-delay: 0.9s; }

  @keyframes linkFade {
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  nav a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0%;
    height: 2px;
    background: linear-gradient(90deg, #b89b5e, #d4af37);
    border-radius: 2px;
    transition: all 0.4s ease;
    transform: translateX(-50%);
    opacity: 0;
  }

  nav a:hover {
    color: #f5e8c7;
    transform: translateY(-2px) scale(1.05);
  }

  nav a:hover::after {
    width: 100%;
    opacity: 1;
    box-shadow: 0 0 6px #b89b5e;
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

<section class="cards">
  <?php if(!$isLoggedIn): ?>
    <div class="card panel">
      <h4>Get started</h4>
      <p class="small">Create an account to manage cars and services.</p>
      <a class="btn" href="signup.php">Sign Up</a>
    </div>
    <div class="card panel">
      <h4>How it works</h4>
      <p class="small">Owners add cars, schedule services. Mechanics accept assignments. Admins manage data.</p>
    </div>
  <?php else: ?>

    <?php if($role === 'admin'): ?>
      <div class="card panel">
        <h4>Manage Users</h4>
        <p class="small">Create, edit or remove user accounts.</p>
        <a class="btn" href="manage_users.php">Open</a>
      </div>
      <div class="card panel">
        <h4>System Reports</h4>
        <p class="small">Download maintenance and usage reports.</p>
        <a class="btn" href="reports.php">Reports</a>
      </div>
      <div class="card panel">
        <h4>Upcoming Maintenance</h4>
        <p class="small">Quick glance of items due in the next 7 days.</p>
        <a class="btn" href="upcoming_services.php">View</a>
      </div>

    <?php elseif($role === 'mechanic'): ?>
      <div class="card panel">
        <h4>Assigned Cars</h4>
        <p class="small"><?php echo number_format($assigned_count); ?> cars assigned to you</p>
        <a class="btn" href="assigned_cars.php">Open</a>
      </div>
      <div class="card panel">
        <h4>Work Orders</h4>
        <p class="small">View and update work orders.</p>
        <a class="btn" href="work_orders.php">Open</a>
      </div>

    <?php elseif($role === 'owner'): ?>
      <div class="card panel">
        <h4>My Garage</h4>
        <p class="small">Manage your vehicles and view history.</p>
        <a class="btn" href="owner_dash.php">Open</a>
      </div>
      <div class="card panel">
        <h4>Schedule Service</h4>
        <p class="small">Quick schedule for routine maintenance.</p>
        <a class="btn" href="add_service.php">Schedule</a>
      </div>
    <?php endif; ?>

  <?php endif; ?>
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
