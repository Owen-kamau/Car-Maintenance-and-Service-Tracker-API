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

$userId   = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'Owner');
$email    = $_SESSION['email'] ?? '';

// -- Validate car ID early --
$car_id = $_GET['car_id'] ?? $_POST['car_id'] ?? null;
$carId  = (int)$car_id;

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
    $code   = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Insert new verification record
    $stmt = $conn->prepare("
        INSERT INTO delete_requests (user_id, car_id, verification_code, expires_at, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iiss", $userId, $carId, $code, $expiry);
    $stmt->execute();
    $stmt->close();

    // Send verification email to Admin
    $subject = "Deletion Request from $username";
    $message = "
        Owner <b>$username</b> (ID: $userId) has requested to delete car:
        <b>{$car['model']} ({$car['license_plate']})</b>.<br><br>
        Verification Code: <b>$code</b><br>
        Expires in 10 minutes.
    ";

    $result = sendMail("admin@cmts.com", $subject, $message);

    if (strpos($result, "❌") === 0) {
        echo "<script>alert('❌ Failed to send verification email. Please try again later.');</script>";
    } else {
        echo "OK";
    }
        exit();
}

// Handle actual deletion after verification

if (isset($_POST['delete_car'])) {
    $inputCode = $_POST['verification_code'] ?? '';

    $stmt = $conn->prepare("
        SELECT * FROM delete_requests 
        WHERE user_id = ? AND car_id = ? AND status = 'pending' 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->bind_param("ii", $userId, $carId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($req && $req['verification_code'] === $inputCode && strtotime($req['expires_at']) > time()) {
        // ✅ Delete car
        $stmt = $conn->prepare("DELETE FROM cars WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $carId, $userId);
        $stmt->execute();
        $stmt->close();

        // Mark request as used
        $stmt = $conn->prepare("UPDATE delete_requests SET status = 'used' WHERE id = ?");
        $stmt->bind_param("i", $req['id']);
        $stmt->execute();
        $stmt->close();

        // Send confirmation email to owner
        if (!empty($email)) {
            $subject = "Car Deletion Confirmation – CMTS";
            $message = "
                Hello <b>$username</b>,<br><br>
                Your car <b>{$car['model']} ({$car['license_plate']})</b> has been successfully deleted from your account.<br>
                This action is now final and irreversible.<br><br>
                If you did not authorize this deletion, please contact support immediately.
            ";
            sendMail($email, $subject, $message);
        }

        header("Location: owner_dash.php?status=deleted");
    } else {
        header("Location: owner_dash.php?status=invalid_code");
    }
    exit();
}
?>
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

h2 { 
  color: #ffb347; 
  margin-bottom: 20px; 
}
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
.verify-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.verify-modal.active {
  display: flex;
  animation: fadeIn 0.3s ease forwards;
}

.verify-box {
  background: #222;
  border: 2px solid #b87333;
  border-radius: 10px;
  padding: 25px 30px;
  text-align: center;
  color: #ffd8b3;
  width: 350px;
  box-shadow: 0 0 20px rgba(255,140,0,0.3);
}

.verify-box input {
  width: 80%;
  padding: 10px;
  margin: 10px 0;
  border: 1px solid #b87333;
  border-radius: 6px;
  background: #2c2c2c;
  color: #ffd8b3;
}

.verify-box button {
  margin: 5px;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  background: linear-gradient(90deg, #cc5500, #8b0000);
  color: #fff;
  transition: 0.3s;
}

.verify-box button:hover {
  transform: scale(1.05);
}

@keyframes fadeIn {
  from {opacity: 0;}
  to {opacity: 1;}
}
.cooldown-active {
  position: relative;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: radial-gradient(circle, #1a1a1a, #000);
  border: none;
  box-shadow: 0 0 15px rgba(255, 102, 0, 0.5);
  cursor: not-allowed;
  transition: all 0.3s ease;
}

.cooldown-active svg {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-90deg);
  width: 90px;
  height: 90px;
}

.cooldown-active .bg {
  fill: none;
  stroke: rgba(255,255,255,0.1);
  stroke-width: 3;
}

.cooldown-active .progress {
  fill: none;
  stroke: #ff6600;
  stroke-width: 3;
  stroke-linecap: round;
  transition: stroke-dasharray 1s linear;
}

.cooldown-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-family: 'Orbitron', sans-serif;
  font-size: 1.2em;
  color: #ffb347;
  text-shadow: 0 0 8px rgba(255, 102, 0, 0.8);
}

.sending-text {
  animation: pulse 1.5s infinite;
  font-family: 'Orbitron', sans-serif;
  color: #ffb347;
  letter-spacing: 1px;
}

@keyframes pulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}
button[name='request_code'] {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: linear-gradient(90deg, #cc5500, #8b0000);
  color: #fff;
  border: 1px solid rgba(255, 165, 0, 0.3);
  border-radius: 10px;
  padding: 14px 26px;
  font-family: 'Orbitron', sans-serif;
  font-weight: bold;
  letter-spacing: 1px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 0 12px rgba(255, 102, 0, 0.4), inset 0 0 10px rgba(255,69,0,0.3);
  overflow: hidden;
}

button[name='request_code']:hover:not(:disabled) {
  transform: scale(1.08);
  box-shadow: 0 0 30px rgba(255, 102, 0, 1), 0 0 10px rgba(255,165,0,0.8);
}

button[name='request_code']:disabled {
  opacity: 0.85;
  background: linear-gradient(90deg, #663300, #330000);
  cursor: not-allowed;
}

/* Countdown circle clearly visible */
.countdown-wrapper {
  position: absolute;
  top: 8px;
  right: 10px;
  width: 24px;
  height: 24px;
}

.countdown-ring {
  transform: rotate(-90deg);
  width: 24px;
  height: 24px;
}

.countdown-ring .bg {
  fill: none;
  stroke: rgba(255,255,255,0.15);
  stroke-width: 3;
}

.countdown-ring .progress {
  fill: none;
  stroke: #ff6600;
  stroke-width: 3;
  stroke-linecap: round;
  transition: stroke-dasharray 1s linear;
  filter: drop-shadow(0 0 5px #ff6600);
}

.countdown-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.65rem;
  color: #ffb347;
  font-family: 'Orbitron', sans-serif;
}

/* Subtle button pulse while cooling down */
.cooldown-active {
  animation: glowPulse 2s infinite;
}
@keyframes glowPulse {
  0%, 100% {
    box-shadow: 0 0 15px rgba(255, 102, 0, 0.8), inset 0 0 10px rgba(255,69,0,0.5);
  }
  50% {
    box-shadow: 0 0 30px rgba(255, 140, 0, 1), inset 0 0 20px rgba(255,69,0,0.8);
  }
}
/* --- Progressive glow speed as countdown nears 0 --- */
@keyframes speedPulse {
  0%   { filter: drop-shadow(0 0 4px #ff6600); opacity: 1; }
  50%  { filter: drop-shadow(0 0 10px #ffa500); opacity: 0.8; }
  100% { filter: drop-shadow(0 0 4px #ff6600); opacity: 1; }
}

/* Animate countdown ring dynamically as time runs out */
.countdown-ring .progress.fast-glow {
  animation: speedPulse 0.5s infinite;
}
.countdown-ring .progress.medium-glow {
  animation: speedPulse 1s infinite;
}
.countdown-ring .progress.slow-glow {
  animation: speedPulse 1.5s infinite;
}

</style>
</head>
<body>

<div class="delete-card">
  <h2>Delete Car: <?= htmlspecialchars($car['model']); ?></h2>
  <p>Car Reg No: <strong><?= htmlspecialchars($car['license_plate']); ?></strong></p>

  <!-- Request code -->
  <form method="POST" id="requestCodeForm">
    <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['id']); ?>">
    <button type="submit" name="request_code">Request Verification Code</button>
  </form>
  </div>

  <!-- Verification modal -->
  <div class="verify-modal" id="verifyModal">
  <div class="verify-box">
    <h3>Enter Verification Code</h3>
    <form method="POST" id="deleteCarForm">
      <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['id']); ?>">
      <input type="text" name="verification_code" placeholder="Enter 6-digit code" required>
      <button type="submit" name="delete_car">Confirm Delete</button>
      <button type="button" id="cancelBtn">Cancel</button>
    </form>
  </div>

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
const verifyModal = document.getElementById('verifyModal');
const cancelBtn = document.getElementById('cancelBtn');
const requestForm = document.getElementById('requestCodeForm');
const deleteForm = document.getElementById('deleteCarForm');
const requestBtn = requestForm?.querySelector("button[name='request_code']");

// 🔸 Countdown animation handler
function startCountdown(button, duration = 60) {
  let timeLeft = duration;
  button.disabled = true;
  button.classList.add('cooldown-active');

  // inject countdown ring and text
  button.innerHTML = `
    <span class="btn-text">wait a moment</span>
    <span class="countdown-wrapper">
      <svg class="countdown-ring" viewBox="0 0 36 36">
        <path class="bg" d="M18 2.0845
          a 15.9155 15.9155 0 0 1 0 31.831
          a 15.9155 15.9155 0 0 1 0 -31.831"/>
        <path class="progress" id="progressPath" stroke-dasharray="100, 100"
          d="M18 2.0845
          a 15.9155 15.9155 0 0 1 0 31.831
          a 15.9155 15.9155 0 0 1 0 -31.831"/>
      </svg>
      <span class="countdown-text">${timeLeft}s</span>
    </span>
  `;

  const progress = button.querySelector('#progressPath');
  const countdownText = button.querySelector('.countdown-text');

  const timer = setInterval(() => {
    timeLeft--;
    const percent = (timeLeft / duration) * 100;
    progress.style.strokeDasharray = `${percent}, 100`;
    countdownText.textContent = `${timeLeft}s`;

    // 🔥 Change glow speed dynamically
    progress.classList.remove('slow-glow', 'medium-glow', 'fast-glow');
    if (timeLeft <= 10) progress.classList.add('fast-glow');
    else if (timeLeft <= 30) progress.classList.add('medium-glow');
    else progress.classList.add('slow-glow');

    if (timeLeft <= 0) {
      clearInterval(timer);
      button.disabled = false;
      button.classList.remove('cooldown-active');
      button.innerHTML = `<span class="btn-text">Request Verification Code</span>`;
    }
  }, 1000);
}

// 🔸 Request verification code via AJAX
requestForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (requestBtn.disabled) return;

  requestBtn.disabled = true;
  requestBtn.innerHTML = `<span class="sending-text">Sending...</span>`;
  loader.classList.add('active');

  const formData = new FormData(requestForm);
  formData.append('request_code', '1');

  try {
    const response = await fetch('', { method: 'POST', body: formData });
    const result = (await response.text()).trim();
    loader.classList.remove('active');

    if (result === 'OK') {
      verifyModal.classList.add('active'); // show modal and keep page open
      startCountdown(requestBtn, 60); // start 60s cooldown
    } else {
      alert('❌ Something went wrong. Please try again.');
      requestBtn.disabled = false;
      requestBtn.innerHTML = `<span class="btn-text">Request Verification Code</span>`;
    }
  } catch (err) {
    loader.classList.remove('active');
    console.error(err);
    alert('⚠️ Network error. Please try again.');
    requestBtn.disabled = false;
    requestBtn.innerHTML = `<span class="btn-text">Request Verification Code</span>`;
  }
});

// 🔸 Modal closing behavior
verifyModal?.addEventListener('click', e => {
  if (e.target === verifyModal) verifyModal.classList.remove('active');
});
cancelBtn?.addEventListener('click', () => verifyModal.classList.remove('active'));
deleteForm?.addEventListener('submit', () => {
  verifyModal.classList.remove('active');
  loader.classList.add('active');
});
</script>

</body>
</html>
