<?php
session_start();
include("DBConn.php");
include("mail.php");

// -- Access control --
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo "<div style='color:red;text-align:center;margin-top:20px;'>🚫 Access denied. Please log in as an owner.</div>";
    echo "<script>setTimeout(()=>{window.location.href='index.php'},1500);</script>";
    exit();
}

$userId = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'Owner');
$email = $_SESSION['email'] ?? '';

// -- Validate car Id early --
$car_id = $_GET['car_id'] ?? $_POST['car_id'] ?? null;
$carId = (int)$car_id;

if ($carId <= 0) {
    echo "<div style='color:red; text-align:center; margin-top:20px;'>⚠️ Invalid car ID.</div>";
    exit();
}

// Fetch car details (your table uses 'user_id', not 'owner_id')
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $carId, $userId);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$car) {
    die("<div style='color:red;text-align:center;margin-top:20px;'>🚫 Car not found or not owned by you.</div>");
}

// Handle request for verification code
if (isset($_POST['request_code'])) {
    $code = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Insert new verification record
    $stmt = $conn->prepare("INSERT INTO delete_requests (user_id, car_id, verification_code, expires_at, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $userId, $carId, $code, $expiry);
    $stmt->execute();
    $stmt->close();

    // Send verification email (to admin)
    $subject = "Deletion Request from $username";
    $message = "Owner $username (ID: $userId) has requested to delete car: {$car['model']} ({$car['license_plate']}).\n\nVerification code: $code\n\nExpires in 10 minutes.";
    mail("admin@cmts.com", $subject, $message);

    $msg = "✅ Request sent! Admin will review and send you the code shortly.";
}

// Handle actual deletion after verification
if (isset($_POST['delete_car'])) {
    $inputCode = $_POST['verification_code'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM delete_requests WHERE user_id = ? AND car_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("ii", $userId, $carId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($req && $req['verification_code'] === $inputCode && strtotime($req['expires_at']) > time()) {
        // ✅ Delete the car — your table uses user_id, not owner_id
        $stmt = $conn->prepare("DELETE FROM cars WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $carId, $userId);
        $stmt->execute();
        $stmt->close();

        // Mark request as used
        $stmt = $conn->prepare("UPDATE delete_requests SET status = 'used' WHERE id = ?");
        $stmt->bind_param("i", $req['id']);
        $stmt->execute();
        $stmt->close();

        $msg = "🚗💨 Car successfully deleted and scrapped!";
    } else {
        $msg = "❌ Invalid or expired verification code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Car | CMTS</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
<style>
body {
  background: linear-gradient(135deg, #2b2b2b, #1a1a1a);
  color: #ffd8b3;
  font-family: 'Orbitron', sans-serif;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  overflow: hidden;
}

.delete-card {
  background: radial-gradient(circle at top, #3c3c3c, #1a1a1a);
  border: 2px solid #b87333;
  box-shadow: 0 0 30px rgba(255,140,0,0.3);
  border-radius: 12px;
  padding: 30px;
  width: 420px;
  text-align: center;
  position: relative;
}

.delete-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: url('https://www.transparenttextures.com/patterns/rust.png');
  opacity: 0.15;
  border-radius: 12px;
}

h2 { color: #ffb347; margin-bottom: 20px; }

input[type="text"] {
  width: 80%;
  padding: 10px;
  margin-top: 10px;
  border-radius: 6px;
  border: 1px solid #b87333;
  background: #2c2c2c;
  color: #ffd8b3;
}

button {
  background: linear-gradient(90deg, #cc5500, #8b0000);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 10px 20px;
  margin-top: 15px;
  cursor: pointer;
  font-weight: bold;
  transition: 0.3s;
}
button:hover {
  transform: scale(1.05);
  background: linear-gradient(90deg, #ff6600, #b22222);
}

.message {
  margin-top: 20px;
  font-size: 0.95rem;
}

.loader-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.5s ease;
}

.loader-overlay.active {
  opacity: 1;
  pointer-events: auto;
}

.gears {
  position: relative;
  width: 120px;
  height: 120px;
}

.gear {
  position: absolute;
  border-radius: 50%;
  border: 5px solid #d2691e;
  background: radial-gradient(circle at 30% 30%, #ffa500, #654321);
  box-shadow: inset 0 0 10px rgba(255,165,0,0.3);
}

.gear1 { width: 60px; height: 60px; top: 10px; left: 0; animation: spin 2s linear infinite; }
.gear2 { width: 80px; height: 80px; top: 20px; left: 40px; animation: spinReverse 2.5s linear infinite; }
.gear3 { width: 50px; height: 50px; top: 60px; left: 70px; animation: spin 1.8s linear infinite; }

@keyframes spin { from {transform: rotate(0);} to {transform: rotate(360deg);} }
@keyframes spinReverse { from {transform: rotate(0);} to {transform: rotate(-360deg);} }

.loader-text {
  color: #ffb347;
  margin-top: 20px;
  font-size: 1rem;
}
</style>
</head>
<body>

<div class="delete-card">
  <h2>Delete Car: <?= htmlspecialchars($car['model']); ?></h2>
  <p>Car Reg No: <strong><?= htmlspecialchars($car['license_plate']); ?></strong></p>

  <form method="POST">
    <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['id']); ?>">
    <button type="submit" name="request_code">Request Verification Code</button>
  </form>

  <form method="POST" style="margin-top:20px;">
    <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['id'] ?? '') ?>">
    <input type="text" name="verification_code" placeholder="Enter verification code" required>
    <button type="submit" name="delete_car">Confirm Delete</button>
  </form>

  <?php if(isset($msg)): ?>
    <div class="message"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
</div>

<!-- Rusty Loader Overlay -->
<div class="loader-overlay" id="loaderOverlay">
  <div class="gears">
    <div class="gear gear1"></div>
    <div class="gear gear2"></div>
    <div class="gear gear3"></div>
  </div>
  <div class="loader-text">Scrapping your car...</div>
</div>

<script>
const loader = document.getElementById('loaderOverlay');
document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', () => {
    loader.classList.add('active');
    setTimeout(() => loader.classList.remove('active'), 3000);
  });
});
// JS snippet for showing loader
document.querySelectorAll("form[action='delete_car.php']").forEach(form => {
  form.addEventListener("submit", () => {
    const loader = document.getElementById("loaderOverlay");
    if (loader) loader.style.display = "flex";
  });
});

</script>
</body>
</html>
