<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin Dashboard</title>

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<style>
:root{
  --bg-1:#0b0b0b;            /* deep black */
  --bg-2:#071428;            /* deep rolls-blue */
  --panel:#0f1113;           /* panel surface */
  --glass: rgba(255,255,255,0.03);
  --muted:#bfc6cf;
  --accent:#d4af37;          /* gold accent */
  --accent-2:#6bd3ff;        /* neon blue */
  --silver:#bdbdbd;
  --card-border: rgba(212,175,55,0.12);
  --glass-strong: rgba(255,255,255,0.06);
}

/* Reset */
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%}
body{
  font-family:'Poppins',sans-serif;
  background: radial-gradient(circle at 10% 10%, rgba(18,21,24,1) 0%, rgba(7,18,40,1) 45%, rgba(3,6,10,1) 100%);
  color:var(--muted);
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
  overflow-y: auto;
}

/* ===== Top Control Panel (full width, fixed) ===== */
.header {
  position:fixed;
  top:0;left:0;right:0;
  height:72px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:12px 28px;
  z-index:1100;
  background: linear-gradient(90deg, rgba(12,12,12,0.8), rgba(10,18,36,0.8));
  border-bottom:1px solid rgba(255,255,255,0.03);
  backdrop-filter: blur(6px) saturate(120%);
  box-shadow: 0 8px 30px rgba(0,0,0,0.6);
}

/* Left area: logo (kept minimal) & optional system badge */
.header .left {
  display:flex;
  align-items:center;
  gap:16px;
}
.logo {
  width:46px;height:46px;
  border-radius:10px;
  background: linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04));
  display:flex;align-items:center;justify-content:center;
  border:1px solid rgba(255,255,255,0.04);
  box-shadow: inset 0 -6px 18px rgba(0,0,0,0.5), 0 6px 18px rgba(0,0,0,0.6);
  color:var(--accent);
  font-weight:700;
  font-size:18px;
}
.branding {
  display:flex;flex-direction:column;
}
.branding .sys { font-weight:600; color:var(--silver); font-size:12px; letter-spacing:0.6px; opacity:0.9; }
.branding .env { font-size:11px; color:rgba(189,189,189,0.55) }

/* CENTER: navigation (menu spans across top) */
.nav {
  flex:1;
  display:flex;
  align-items:center;
  justify-content:center;
}
.nav ul {
  list-style:none;
  display:flex;
  gap:18px;
  align-items:center;
  padding:6px 10px;
}

/* Each nav item is a single unit — hover affects whole item (matrix pulse) */
.nav ul li {
  position:relative;
  padding:6px 8px;
  border-radius:8px;
  overflow:visible;
}

/* Matrix hover effect: use a pseudo-element that animates across the full item */
.nav ul li a {
  display:inline-block;
  text-decoration:none;
  color:var(--muted);
  font-weight:600;
  padding:10px 14px;
  border-radius:8px;
  position:relative;
  z-index:2;
  transition:color .25s ease, transform .18s ease;
  letter-spacing:0.2px;
  background:transparent;
}

/* pseudo background that creates the unified 'matrix' sweep */
.nav ul li .matrix {
  position:absolute;
  inset:0;
  z-index:1;
  border-radius:8px;
  pointer-events:none;
  background: linear-gradient(90deg, rgba(255,255,255,0.02), rgba(107,211,255,0.05) 20%, rgba(212,175,55,0.06) 55%, rgba(255,255,255,0.02));
  transform: translateX(-120%);
  opacity:0;
}

/* On hover: slide the matrix overlay across, glow the text */
.nav ul li:hover .matrix{
  animation: sweep 900ms cubic-bezier(.2,.9,.3,1) forwards;
  opacity:1;
  box-shadow: 0 8px 30px rgba(10,36,66,0.12), 0 0 18px rgba(212,175,55,0.04) inset;
}
@keyframes sweep{
  0%{ transform:translateX(-120%); filter:blur(6px) saturate(0.6)}
  50%{ transform:translateX(8%); filter:blur(2px) saturate(1.2)}
  100%{ transform:translateX(120%); filter:blur(0) saturate(1.4)}
}

/* text highlight when hovered (apply to both words as a unit) */
.nav ul li:hover a {
  color: white;
  transform: translateY(-2px);
  text-shadow: 0 0 8px rgba(107,211,255,0.08), 0 0 18px rgba(212,175,55,0.06);
}

/* Right area: controls/status */
.header .right {
  display:flex;
  align-items:center;
  gap:12px;
}
.icon-btn{
  display:inline-flex;align-items:center;justify-content:center;
  min-width:44px;height:44px;padding:8px;border-radius:10px;
  background:var(--glass);border:1px solid rgba(255,255,255,0.03);
  color:var(--muted);cursor:pointer;font-size:15px;
}
.icon-btn:hover{ background:linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04)); color:var(--accent-2) }

/* Logout styled */
.logout-btn {
  padding:8px 14px;border-radius:12px;background:linear-gradient(90deg,var(--accent),#f0c86b);
  color:var(--dark, #06121b); font-weight:700;text-decoration:none;
  box-shadow: 0 6px 20px rgba(212,175,55,0.12);
}

/* ===== Page content ===== */
.container {
  width: calc(100% - 80px);
  max-width: 1280px;
  margin: 110px auto 60px; /* account for fixed header */
}

/* Top stat cards */
.grid {
  display:grid;
  grid-template-columns: repeat(4,1fr);
  gap:20px;
  margin-bottom:28px;
}
.card {
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
  border-radius:14px;
  padding:18px;
  border:1px solid var(--card-border);
  box-shadow: 0 10px 30px rgba(2,6,12,0.6);
  min-height:108px;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
}
.card .label{ font-size:12px; color:var(--silver); font-weight:600; letter-spacing:0.6px }
.card .value{ font-size:28px; color:white; font-weight:700; margin-top:6px }

/* Action shortcuts */
.actions {
  display:flex;
  gap:16px;
  margin-bottom:28px;
  flex-wrap:wrap;
}
.shortcut {
  flex:1 1 220px;
  min-height:86px;
  padding:14px;
  border-radius:12px;
  background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.08));
  border:1px solid rgba(255,255,255,0.03);
  display:flex;align-items:center;gap:12px;cursor:pointer;
}
.shortcut .ico{ width:46px;height:46px;border-radius:10px;background:linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04)); display:flex;align-items:center;justify-content:center; color:var(--accent); font-size:18px; }
.shortcut:hover{ transform:translateY(-6px); box-shadow: 0 15px 40px rgba(0,0,0,0.6) }

/* Logs and Activity */
.layout {
  display:grid;
  grid-template-columns: 1fr 420px;
  gap:20px;
  align-items:start;
}

/* logs */
.logs {
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
  border-radius:12px;padding:18px;border:1px solid rgba(255,255,255,0.03);
  max-height:520px; overflow:auto;
}
.logs h3{ color:var(--accent); margin-bottom:12px }
.log-entry{ padding:10px;border-radius:8px;margin-bottom:10px;background:rgba(255,255,255,0.01); border-left:3px solid rgba(255,255,255,0.02) }
.log-entry .meta{ font-size:12px;color:var(--silver); margin-bottom:6px }
.log-entry .msg{ color:var(--muted); font-weight:600 }

/* quick system panel */
.panel {
  background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(0,0,0,0.06));
  border-radius:12px;padding:18px;border:1px solid rgba(255,255,255,0.03);
}
.panel h3{ color:var(--accent); margin-bottom:12px }
.panel .row{ display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px dashed rgba(255,255,255,0.02) }
.panel .row:last-child{ border-bottom:none }

/* Toast */
.toast {
  position:fixed; right:20px; bottom:20px; z-index:2000; display:flex;flex-direction:column; gap:8px;
}
.toast .item {
  background: linear-gradient(90deg, rgba(20,20,20,0.9), rgba(10,10,20,0.9));
  color:var(--muted);
  padding:10px 14px;border-radius:10px;border:1px solid rgba(255,255,255,0.03);
  box-shadow: 0 10px 30px rgba(0,0,0,0.6);
  min-width:260px;
}

/* Responsive adjustments */
@media (max-width:1100px){ .grid{grid-template-columns:repeat(2,1fr)} .layout{grid-template-columns:1fr} .nav ul{gap:10px} .container{width:calc(100% - 40px)} }
@media (max-width:640px){
  .grid{grid-template-columns:1fr}
  .header{padding:8px 14px;height:auto;gap:8px;flex-direction:column;align-items:flex-start}
  .nav{order:3;width:100%;justify-content:flex-start}
  .nav ul{overflow:auto;padding:6px 6px;gap:8px}
  .branding{display:none}
}
</style>
</head>
<body>

<!-- Top fixed control bar (no title text, minimal branding) -->
<header class="header">
  <div class="left">
    <div class="logo">RR</div>
    <div class="branding">
      <div class="sys">SYSTEM</div>
      <div class="env">Administrator Console</div>
    </div>
  </div>

  <nav class="nav" aria-label="Main navigation">
    <ul id="mainNav">
      <li><a href="manage_users.php"><span class="label-text">Manage Users</span></a><span class="matrix"></span></li>
      <li><a href="edit_users.php"><span class="label-text">Edit Users</span></a><span class="matrix"></span></li>
      <li><a href="view_all_cars.php"><span class="label-text">View All Cars</span></a><span class="matrix"></span></li>
      <li><a href="assign_car.php"><span class="label-text">Assign Cars</span></a><span class="matrix"></span></li>
      <li><a href="view_services.php"><span class="label-text">Service Reports</span></a><span class="matrix"></span></li>
      <li><a href="admin_services.php"><span class="label-text">Admin Reports</span></a><span class="matrix"></span></li>
      <li><a href="edit_services.php"><span class="label-text">Edit Records</span></a><span class="matrix"></span></li>
      <li><a href="manage_requests.php"><span class="label-text">Requests</span></a><span class="matrix"></span></li>
      <li><a href="service_history.php"><span class="label-text">History</span></a><span class="matrix"></span></li>
      <li><a href="CarReg.php"><span class="label-text">Register Car</span></a><span class="matrix"></span></li>
      <li><a href="View_Cars.php"><span class="label-text">My Cars</span></a><span class="matrix"></span></li>
    </ul>
  </nav>

  <div class="right">
    <button class="icon-btn" title="Refresh Stats" id="btnRefresh"><i class="fas fa-sync-alt"></i></button>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<!-- Main container -->
<div class="container">
  <!-- Top stat cards -->
  <section class="grid" aria-label="System statistics">
    <div class="card">
      <div class="label">Active Users</div>
      <div class="value" id="statUsers">—</div>
      <div class="muted" style="font-size:12px;margin-top:8px;color:var(--silver)">Live connected users</div>
    </div>

    <div class="card">
      <div class="label">Cars Registered</div>
      <div class="value" id="statCars">—</div>
      <div class="muted" style="font-size:12px;margin-top:8px;color:var(--silver)">Total vehicles</div>
    </div>

    <div class="card">
      <div class="label">Open Requests</div>
      <div class="value" id="statRequests">—</div>
      <div class="muted" style="font-size:12px;margin-top:8px;color:var(--silver)">Pending actions</div>
    </div>

    <div class="card">
      <div class="label">Avg Service Time</div>
      <div class="value" id="statServiceTime">—</div>
      <div class="muted" style="font-size:12px;margin-top:8px;color:var(--silver)">Mins per service</div>
    </div>
  </section>

  <!-- Action shortcuts -->
  <section class="actions" aria-label="Quick actions">
    <div class="shortcut" onclick="runAction('rebuild_search')">
      <div class="ico"><i class="fas fa-wrench"></i></div>
      <div>
        <div style="font-weight:700;color:white">Rebuild Search Index</div>
        <div style="font-size:13px;color:var(--silver)">Reindex vehicles & services</div>
      </div>
    </div>

    <div class="shortcut" onclick="runAction('clear_cache')">
      <div class="ico"><i class="fas fa-broom"></i></div>
      <div>
        <div style="font-weight:700;color:white">Clear Cache</div>
        <div style="font-size:13px;color:var(--silver)">Free memory & optimize</div>
      </div>
    </div>

    <div class="shortcut" onclick="runAction('email_broadcast')">
      <div class="ico"><i class="fas fa-envelope"></i></div>
      <div>
        <div style="font-weight:700;color:white">Broadcast Message</div>
        <div style="font-size:13px;color:var(--silver)">Send notice to users</div>
      </div>
    </div>

    <div class="shortcut" onclick="runAction('backup_db')">
      <div class="ico"><i class="fas fa-database"></i></div>
      <div>
        <div style="font-weight:700;color:white">Backup Database</div>
        <div style="font-size:13px;color:var(--silver)">Create full DB snapshot</div>
      </div>
    </div>
  </section>

  <!-- Main layout: logs + system panel -->
  <section class="layout">
    <div class="logs" aria-live="polite" id="logsArea">
      <h3>Activity Feed</h3>
      <!-- loaded dynamically -->
    </div>

    <aside class="panel">
      <h3>System Controls</h3>
      <div class="row">
        <div>Maintenance Mode</div>
        <div><label id="maintenanceStatus">OFF</label></div>
      </div>
      <div class="row">
        <div>Last Backup</div>
        <div id="lastBackup">—</div>
      </div>
      <div class="row">
        <div>Server Time</div>
        <div id="serverTime">—</div>
      </div>

      <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="icon-btn" onclick="runAction('toggle_maintenance')"><i class="fas fa-exclamation-triangle"></i> Toggle</button>
        <button class="icon-btn" onclick="runAction('trigger_backup')"><i class="fas fa-save"></i> Backup</button>
        <button class="icon-btn" onclick="runAction('health_check')"><i class="fas fa-heartbeat"></i> Health</button>
      </div>
    </aside>
  </section>
</div>

<!-- Toast container -->
<div class="toast" id="toast"></div>

<!-- Inline JS: interactive behavior and polling -->
<script>
/*
  Interactivity & Live Updates:
  - Attempts to fetch real data from these endpoints:
      /api/stats.php     -> returns JSON { users, cars, requests, avg_service_time }
      /api/logs.php      -> returns JSON array [ { ts, level, msg }, ... ]
      /api/action.php    -> POST { action: 'name' } -> returns { success:true, message }
  - If endpoints return error or are not present, demo/random data is used.
  - Poll frequency: stats every 6s, logs every 5s.
*/

const POLL_STATS_MS = 6000;
const POLL_LOGS_MS = 5000;
let demoMode = false;

// UTIL: toast
function toast(msg){
  const t = document.createElement('div');
  t.className = 'item';
  t.textContent = msg;
  document.getElementById('toast').appendChild(t);
  setTimeout(()=>{ t.style.opacity = '0'; t.style.transform = 'translateY(6px)'; }, 3500);
  setTimeout(()=> t.remove(), 4200);
}

// Try fetching JSON safely
async function tryFetchJson(url, options){
  try {
    const res = await fetch(url, options);
    if(!res.ok) throw new Error('Network response not ok');
    return await res.json();
  } catch(e){
    return null;
  }
}

/* Update stats cards */
async function updateStats(){
  const data = await tryFetchJson('/api/stats.php');
  if(!data){
    // demo fallback
    demoMode = true;
    const users = Math.floor(20 + Math.random()*180);
    const cars = Math.floor(200 + Math.random()*1800);
    const requests = Math.floor(Math.random()*30);
    const avg = Math.floor(20 + Math.random()*40);
    setStat('statUsers', users);
    setStat('statCars', cars);
    setStat('statRequests', requests);
    setStat('statServiceTime', avg + 'm');
  } else {
    setStat('statUsers', data.users ?? '—');
    setStat('statCars', data.cars ?? '—');
    setStat('statRequests', data.requests ?? '—');
    setStat('statServiceTime', (data.avg_service_time ?? '—') + (data.avg_service_time ? 'm' : ''));
  }
}

function setStat(id, text){ document.getElementById(id).textContent = text; }

/* Update logs */
async function updateLogs(){
  const data = await tryFetchJson('/api/logs.php');
  const area = document.getElementById('logsArea');
  if(!data){
    // demo logs fallback (prepend)
    const demo = generateDemoLog();
    prependLog(demo.ts, demo.level, demo.msg);
  } else {
    // replace whole log list for simplicity
    area.querySelectorAll('.log-entry').forEach(n=>n.remove());
    data.forEach(item => prependLog(item.ts, item.level, item.msg));
  }
}

function prependLog(ts, level, msg){
  const area = document.getElementById('logsArea');
  const e = document.createElement('div');
  e.className = 'log-entry';
  const meta = document.createElement('div');
  meta.className = 'meta';
  meta.textContent = `${ts} • ${level.toUpperCase()}`;
  const m = document.createElement('div');
  m.className = 'msg';
  m.textContent = msg;
  e.appendChild(meta); e.appendChild(m);
  // insert after heading
  const first = area.querySelector('.log-entry');
  if(first) area.insertBefore(e, first);
  else area.appendChild(e);
  // keep max 40 logs
  const items = area.querySelectorAll('.log-entry');
  if(items.length>40) items[items.length-1].remove();
}

/* Demo log generator */
function generateDemoLog(){
  const now = new Date();
  const ts = now.toLocaleString();
  const levels = ['info','warn','debug','error'];
  const msgs = [
    'User 374 logged in',
    'Service record updated (Car ID 1289)',
    'Backup job completed successfully',
    'Cache cleared',
    'New registration pending approval',
    'Assigned mechanic to booking #2044',
    'Search index rebuilt (partial)'
  ];
  return { ts, level: levels[Math.floor(Math.random()*levels.length)], msg: msgs[Math.floor(Math.random()*msgs.length)] };
}

/* System panel updates (time & last backup sample) */
function updateSystemPanel(){
  const t = new Date();
  document.getElementById('serverTime').textContent = t.toLocaleString();
  // last backup sample (demo)
  const last = new Date(Date.now() - Math.floor(Math.random()*3600*1000*48));
  document.getElementById('lastBackup').textContent = last.toLocaleString();
}

/* Actions: call /api/action.php or fallback demo toast */
async function runAction(action){
  toast('Executing: ' + action);
  try {
    const res = await fetch('/api/action.php', {
      method:'POST',
      headers:{ 'Content-Type':'application/json' },
      body: JSON.stringify({ action })
    });
    if(!res.ok){
      throw new Error('Action endpoint failed');
    }
    const json = await res.json();
    if(json.success){
      toast('Success: ' + (json.message ?? action + ' completed.'));
    } else {
      toast('Failed: ' + (json.message ?? 'Unknown reason'));
    }
  } catch(e){
    // demo fallback
    toast('Demo mode: ' + action + ' simulated.');
  }
}

/* Initialize polling */
async function startPolling(){
  await updateStats();
  await updateLogs();
  updateSystemPanel();
  setInterval(updateStats, POLL_STATS_MS);
  setInterval(updateLogs, POLL_LOGS_MS);
  setInterval(updateSystemPanel, 5000);
}

/* Manual refresh button */
document.getElementById('btnRefresh').addEventListener('click', ()=>{
  updateStats(); updateLogs(); toast('Manual refresh triggered');
});

/* Run on start */
startPolling();

/* Optional: keyboard shortcuts for power actions (Ctrl+Shift+1..4) */
document.addEventListener('keydown', (e)=>{
  if(e.ctrlKey && e.shiftKey){
    if(e.key === '1') runAction('rebuild_search');
    if(e.key === '2') runAction('clear_cache');
    if(e.key === '3') runAction('backup_db');
    if(e.key === '4') runAction('toggle_maintenance');
  }
});
</script>

</body>
</html>
