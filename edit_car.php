<?php
session_start();
include("DBConn.php");
include("mail.php");

//Always get car_id first suing GET and POST
$car_id = $_GET['car_id'] ?? $_POST['car_id'] ?? null;
$carId = (int)$car_id;

//check if it is valid
if ($carId <= 0) {
    echo "<div style='color:red; text-align:center; margin-top:20px;'>❌ No car selected for editing.</div>";
    exit();
}

// Access control: owner only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    echo "<script>setTimeout(()=>{window.location.href = 'index.php'}, 600);</script>";
    exit();
}


$userId   = (int) $_SESSION['user_id'];
$userEmail = $_SESSION['email'] ?? '';
$userName  = $_SESSION['username'] ?? 'Owner';

$success = "";
$error = "";

/* -----------------------------
   Fetch existing car and verify owner
   ----------------------------- */
$sql = "SELECT id, user_id, make, model, year, license_plate, garage_type, car_image FROM cars WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $carId);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();
$stmt->close();

if (!$car) {
    // not found
    header("Location: owner_dash.php");
    exit();
}

// Ensure this owner owns the car
if ((int)$car['user_id'] !== $userId) {
    header("Location: owner_dash.php");
    exit();
}

// Populate form defaults
$make = $car['make'];
$model = $car['model'];
$year = $car['year'];
$license_plate = $car['license_plate'];
$garage_type = $car['garage_type'];
$current_image = $car['car_image'];

# -----------------------------
# Handle POST (update)
# -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize incoming
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    $license_plate = trim($_POST['license_plate'] ?? '');
    $garage_type = $_POST['garage_type'] ?? $garage_type;

    // Basic validation
    if ($make === '' || $model === '' || $year <= 1900 || $license_plate === '') {
        $error = "Please fill in all required fields with valid values.";
    } else {
        // Image upload handling: keep old image if no new
        $car_image = $current_image;
        if (!empty($_FILES['car_image']['name'])) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            // sanitize filename
            $file_name = time() . "_" . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($_FILES['car_image']['name']));
            $target_file = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = ["jpg","jpeg","png","gif"];

            if (!in_array($imageFileType, $allowed_types)) {
                $error = "Only JPG, PNG, and GIF files are allowed.";
            } else {
                if (move_uploaded_file($_FILES['car_image']['tmp_name'], $target_file)) {
                    // optionally delete old file (uncomment to enable)
                    // if ($current_image && file_exists($current_image)) unlink($current_image);
                    $car_image = $target_file;
                } else {
                    $error = "Error uploading image. Please try again.";
                }
            }
        }

        // If no error, update DB
        if (empty($error)) {
            $updateSql = "UPDATE cars SET make=?, model=?, year=?, license_plate=?, garage_type=?, car_image=? WHERE id=? AND user_id=?";
            $upd = $conn->prepare($updateSql);
            $upd->bind_param("ssisssii", $make, $model, $year, $license_plate, $garage_type, $car_image, $carId, $userId);

            if ($upd->execute()) {
                $success = "✅ Car updated successfully.";

                // Prepare and send no-reply confirmation email to owner
                $subject = "Your car details were updated — Car Maintenance Tracker";
                $body = "
                    <div style='font-family: Georgia, serif; background:#f7f7f7; padding:16px;'>
                        <div style='max-width:600px;margin:auto;background:#fff;border-radius:8px;padding:18px;border:1px solid #e6e6e6;'>
                            <h2 style='color:#2b2b2b;font-family:Georgia,serif;'>Hi " . htmlspecialchars($userName) . ",</h2>
                            <p>Your car's details were successfully updated on Car Maintenance Tracker. Here are the current details:</p>
                            <table style='width:100%;border-collapse:collapse;font-family:Arial, sans-serif;'>
                                <tr><td style='padding:8px;'><strong>Make:</strong></td><td style='padding:8px;'>" . htmlspecialchars($make) . "</td></tr>
                                <tr><td style='padding:8px;'><strong>Model:</strong></td><td style='padding:8px;'>" . htmlspecialchars($model) . "</td></tr>
                                <tr><td style='padding:8px;'><strong>Year:</strong></td><td style='padding:8px;'>" . htmlspecialchars($year) . "</td></tr>
                                <tr><td style='padding:8px;'><strong>License Plate:</strong></td><td style='padding:8px;'>" . htmlspecialchars($license_plate) . "</td></tr>
                                <tr><td style='padding:8px;'><strong>Garage Type:</strong></td><td style='padding:8px;'>" . htmlspecialchars(ucfirst($garage_type)) . "</td></tr>
                            </table>
                            <p style='font-size:0.9em;color:#666;margin-top:12px;'>⚙️ This is an automated message from Car Maintenance Tracker. Please do not reply.</p>
                        </div>
                    </div>
                ";

                // sendMail defined in mail.php
                $mailResult = sendMail($userEmail, $subject, $body);

                // append mail status to success message
                $success .= "<br>📧 " . htmlspecialchars($mailResult);

                // update current_image variable used for display
                $current_image = $car_image;

            } else {
                $error = "Database update failed: " . htmlspecialchars($upd->error);
            }
            $upd->close();
        }
    }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Edit Car — Owner Panel</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">

<style>
  :root{
    --bg1: #0b1220;
    --glass: rgba(255,255,255,0.06);
    --accent: linear-gradient(90deg,#ff8a00,#ff4d00);
    --muted: rgba(255,255,255,0.6);
    --card-grad: linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
  }
  *{box-sizing:border-box}
  body{
    margin:0;
    min-height:100vh;
    font-family: 'Poppins', sans-serif;
    background: radial-gradient(1200px 800px at 10% 10%, #0f1622 0%, var(--bg1) 40%, #020406 100%);
    color: #fff;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px;
  }

  .panel {
    width:920px;
    max-width:95%;
    background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border-radius:14px;
    box-shadow: 0 12px 40px rgba(2,6,12,0.7);
    display:grid;
    grid-template-columns: 1fr 420px;
    gap:24px;
    padding:26px;
    backdrop-filter: blur(8px) saturate(140%);
    border: 1px solid rgba(255,255,255,0.04);
  }

  .left {
    padding:18px;
  }

  .title {
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:12px;
  }
  .logo {
    width:58px;
    height:58px;
    border-radius:10px;
    background: linear-gradient(135deg,#1f2a38 0%, #0e1620 100%);
    display:flex;
    align-items:center;
    justify-content:center;
    font-family: 'Orbitron', sans-serif;
    color:#ffb366;
    font-weight:700;
    font-size:20px; 
    box-shadow: inset 0 -6px 18px rgba(255,255,255,0.02);
  }
  h1 { 
    margin:0;
    font-size:20px;
    color:#ffd8b3;
    letter-spacing:0.6px;
  }
  .form-row { 
    display:flex; 
    gap:12px; 
    margin-bottom:12px; 
  }
  .field {
    flex:1;
    display:flex;
    flex-direction:column;
  }
  label {
    font-size:0.82rem;
    color:var(--muted);
    margin-bottom:6px;
  }
  input[type="text"], input[type="number"], select {
    background: var(--glass);
    border: 1px solid rgba(255,255,255,0.06);
    padding:12px 14px;border-radius:10px;color:#fff;
    outline:none;font-size:0.95rem;
    transition: box-shadow .18s, border-color .18s, transform .12s;
  }
  input:focus, select:focus {
    box-shadow: 0 8px 26px rgba(0,0,0,0.6), 0 0 12px rgba(255,77,0,0.06);
    border-color: rgba(255,77,0,0.85);
    transform: translateY(-1px);
  }
  .file-input {
    padding:8px; 
    border-radius:10px; 
    background: rgba(255,255,255,0.02);
    border: 1px dashed rgba(255,255,255,0.04);
    color:var(--muted);
  }
  .actions { 
    display:flex; 
    gap:12px; 
    margin-top:10px; 
    align-items:center;}
  .btn {
    background: var(--accent);
    padding:10px 18px;
    border-radius:10px;
    color:#fff;
    border:none;
    font-weight:600; 
    cursor:pointer; 
    box-shadow: 0 8px 20px rgba(255,77,0,0.12);
    transition: 
    transform .12s, 
    box-shadow .12s;
  }
  .btn:hover{ 
    transform: translateY(-3px); 
    box-shadow:0 20px 40px rgba(255,77,0,0.12); 
  }
  .btn.secondary {
    background: transparent; 
    border:1px solid rgba(255,255,255,0.06); 
    color:var(--muted);
  }
  /* right column: preview panel */
  .right {
    padding:18px;
    border-radius:10px;
    background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border: 1px solid rgba(255,255,255,0.03);
    display:flex;
    flex-direction:column;
    gap:12px; 
    align-items:center;
  }
  .car-preview {
    width:100%; 
    height:240px; 
    border-radius:10px; 
    overflow:hidden; 
    position:relative;
    background: linear-gradient(180deg, rgba(6,18,30,0.7), rgba(10,14,18,0.8));
    display:flex;
    align-items:center;
    justify-content:center;
    border: 1px solid rgba(255,255,255,0.03);
  }
  .car-preview img { 
    max-width:100%; 
    max-height:100%; 
    object-fit:contain; 
    display:block; 
    filter: drop-shadow(0 12px 24px rgba(0,0,0,0.6)); 
  }
  .meta { 
    width:100%; 
    padding:12px; 
    text-align:left; 
    color:var(--muted); 
    font-size:0.95rem; 
  }
  /* message box */
  .message { 
    margin-top:12px; 
    padding:12px 14px; 
    border-radius:10px; 
    font-family:Georgia,serif; 
  }
  .message.success { 
    background: rgba(240,230,210,0.12); 
    color:#ffdcb6; 
    border:1px solid rgba(255,160,60,0.12); 
  }
  .message.error { 
    background: rgba(255,220,220,0.06); 
    color:#ffb3b3; border:1px solid rgba(255,80,80,0.06); 
  }
  footer.hint { 
    margin-top:10px; 
    color:var(--muted); 
    font-size:0.85rem; 
    text-align:center; 
    grid-column:1/-1; 
  }
  /* loader overlay (hidden by default) */
  .loader-overlay {
    position: fixed;
    inset: 0;
    background: radial-gradient(circle at center, rgba(245,245,245,0.95), rgba(255,255,255,0.8));
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.5s ease;
  }
  .loader-card {
    background: linear-gradient(145deg, #f8f8f8, #e3e3e3);
    border-radius: 14px;
    padding: 40px 50px;
    text-align: center;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    position: relative;
    overflow: hidden;
  }
  /* metallic gear with color-cycle animation */
  .gears {
    position: relative;
    width: 140px;
    height: 100px;
    margin: 0 auto 25px;
    
  }
  .gear {
    position: absolute;
    border-radius: 50%;
    box-sizing: border-box;
    background: radial-gradient(circle at 30% 30%, #fffbe6 5%, #ffd700 40%, #b8860b 80%, #6b5200 100%);
    border: 3px solid #e6c300;
    box-shadow:
      inset 0 0 8px rgba(255,255,255,0.4),
      inset 0 0 20px rgba(255,255,255,0.2),
      0 0 6px rgba(255,223,0,0.3);
    animation-timing-function: linear;
  }
  .gear::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 14px;
    height: 14px;
    background: radial-gradient(circle, #222 10%, #444 60%, #111 100%);
    border-radius: 50%;
    box-shadow: inset 0 0 4px rgba(255,255,255,0.2);
  }
  .gear1 { 
    width: 60px;
    height: 560px;
    top: 30px;
    left: 0;
    animation: rotateClockwise 2.5s linear infinite;
  }
  .gear2 { 
    width: 80px;
    height: 680px;
    top: 15px;
    left: 55px;
    animation: rotateCounter 3.5s linear infinite;
  }
  .gear3 {
    width: 50px;
    height: 50px;
    top: 50px;
    left: 110px;
    animation: rotateClockwise 2.1s linear infinite;
  }
  .gear::after {
    content: "";
    position: absolute;
    top: 0;
    left: -75%;
    width: 50%;
    height: 100%;
    background: linear-gradient(
      120deg,
      rgba(255,255,255,0.2) 0%,
      rgba(255,255,255,0.6) 50%,
      rgba(255,255,255,0.2) 100%
    );transform: skewX(-20deg);
      animation: shineMove 3s infinite ease-in-out;
  }
  @keyframes shineMove {
    0% { left: -75%; }
    50% { left: 125%; }
    100% { left: -75%; }
  }
  @keyframes rotateClockwise {
    from { transform: rotate(0deg); }
    to { transform: rotate(-360deg); }
  }
  @keyframes rotateCounter {
    from { transform: rotate(360deg); }
    to { transform: rotate(0deg); }
  }

  .loader-text { 
    margin-top: 16px;
    font-size: 1.15rem;
    font-weight: 600;
    color: #cda433;
    font-family: 'Orbitron', sans-serif;
    letter-spacing: 0.8px;
    text-shadow: 0 0 4px rgba(0,0,0,0.3);
    animation: textPulse 1.5s ease-in-out infinite;
  }

  /* responsive */
@keyframes textPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(1.03); }
}
</style>
</head>
<body>

<div class="panel">
  <div class="left">
    <div class="title">
      <div class="logo">CM</div>
      <div>
        <h1>Edit Car</h1>
        <div style="color:var(--muted);font-size:0.9rem">Tune your car details — luxury dashboard editor</div>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" id="editCarForm">
      <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['id']); ?>">
      <div class="form-row">
        <div class="field">
          <label>Make</label>
          <input type="text" name="make" value="<?= htmlspecialchars($car['make']); ?>" required>
        </div>
        <div class="field">
          <label>Model</label>
          <input type="text" name="model" value="<?= htmlspecialchars($car['model']); ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="field">
          <label>Year</label>
          <input type="number" name="year" value="<?= htmlspecialchars($car['year']); ?>" min="1900" max="2100" required>
        </div>
        <div class="field">
          <label>License Plate</label>
          <input type="text" name="license_plate" value="<?= htmlspecialchars($car['license_plate']); ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="field">
          <label>Garage Type</label>
          <select name="garage_type" required>
            <option value="vehicle" <?php if($garage_type==='vehicle') echo 'selected'; ?>>Normal Vehicle</option>
            <option value="truck" <?php if($garage_type==='truck') echo 'selected'; ?>>Truck</option>
            <option value="tractor" <?php if($garage_type==='tractor') echo 'selected'; ?>>Tractor</option>
          </select>
        </div>

        <div class="field">
          <label>Replace Car Image (optional)</label>
          <div class="file-input">
            <input type="file" name="car_image" accept="image/*">
          </div>
        </div>
      </div>

      <div class="actions">
        <button type="submit" class="btn">Save Changes</button>
        <a class="btn secondary" href="owner_dash.php">Back to Dashboard</a>
      </div>
    </form>

    <?php if (!empty($success)): ?>
      <div class="message success" id="successMsg"><?php echo $success; ?></div>
    <?php endif; ?>

    <footer class="hint">Make sure your admission number is not required here — only on coursework uploads.</footer>
  </div>

  <div class="right">
    <div class="car-preview" id="preview">
      <?php if (!empty($current_image) && file_exists($current_image)): ?>
        <img id="carImagePreview" src="<?php echo htmlspecialchars($current_image); ?>" alt="Car image">
      <?php else: ?>
        <div style="color:var(--muted);font-size:1rem;">No image available</div>
      <?php endif; ?>
    </div>

    <div class="meta">
      <div><strong>Owner:</strong> <?php echo htmlspecialchars($userName); ?></div>
      <div><strong>Plate:</strong> <?php echo htmlspecialchars($license_plate); ?></div>
      <div style="margin-top:8px;color:var(--muted);font-size:0.9rem">Preview updates live — click Save to persist.</div>
    </div>
  </div>
</div>

<!-- loader overlay -->
<div class="loader-overlay" id="loaderOverlay" aria-hidden="true">
  <div class="loader-card">
    <div class="gears">
      <div class="gear gear1"></div>
      <div class="gear gear2"></div>
      <div class="gear gear3"></div>
    </div>
    <div class="loader-text">Calibrating your dashboard...</div>
  </div>
</div>

<script>
  // live image preview
  const fileInput = document.querySelector('input[name="car_image"]');
  const previewImg = document.getElementById('carImagePreview');
  fileInput?.addEventListener('change', (e)=>{
    const f = e.target.files[0];
    if (!f) return;
    const url = URL.createObjectURL(f);
    if (previewImg) previewImg.src = url;
    else {
      const container = document.getElementById('preview');
      container.innerHTML = '<img id="carImagePreview" src="'+url+'" alt="Car image" style="max-width:100%;max-height:100%;object-fit:contain;">';
    }
  });

  // on successful server-side update, show loader & redirect
  <?php if (!empty($success)): ?>
    document.addEventListener('DOMContentLoaded', ()=> {
      const overlay = document.getElementById('loaderOverlay');
      overlay.style.display = 'flex';
      // keep loader visible for a few cycles to show color changes (6s)
      setTimeout(()=>{ window.location.href = 'owner_dash.php'; }, 6000);
    });
  <?php endif; ?>

  // optional: tiny micro-interaction when clicking Save
  const form = document.getElementById('editCarForm');
  form.addEventListener('submit', ()=> {
    // small UX: briefly show a subtle glow on the button
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
      btn.style.transform = 'translateY(-2px)';
      setTimeout(()=>btn.style.transform = '', 5000);
    }
  });
  window.addEventListener("load", function() {
  const loader = document.getElementById("loaderOverlay");
  if (loader) {
    loader.style.opacity = "0";
    loader.style.transition = "opacity 0.5s ease";
    setTimeout(() => loader.style.display = "none", 5000);
  }
});
function showLoader(message = "Calibrating your dashboard...") {
  const loader = document.getElementById("loaderOverlay");
  const text = loader.querySelector(".loader-text");
  text.textContent = message;
  loader.style.display = "flex";
  loader.style.opacity = "1";
}

function hideLoader() {
  const loader = document.getElementById("loaderOverlay");
  loader.style.opacity = "0";
  setTimeout(() => loader.style.display = "none", 5000);
}

// Automatically hide when page finishes loading
window.addEventListener("load", hideLoader);

// Show loader before redirecting or submitting
document.addEventListener("DOMContentLoaded", () => {
  const editForm = document.querySelector("form");
  if (editForm) {
    editForm.addEventListener("submit", () => {
      showLoader("Updating your car details...");
    });
  }
});
document.addEventListener("DOMContentLoaded", () => {
  const loader = document.getElementById("loaderOverlay");
  
  // Keep loader visible for at least 2.5 seconds
  setTimeout(() => {
    loader.style.opacity = "0";
    setTimeout(() => {
      loader.style.display = "none";
    }, 600); // match fade transition
  }, 5000); // ⏱️ adjust this number to control how long the loader stays (ms)
});
</script>

</body>
</html>
