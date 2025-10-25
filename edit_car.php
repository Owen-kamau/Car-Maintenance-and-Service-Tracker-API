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
    inset:0; display:none; 
    align-items:center; 
    justify-content:center; 
    z-index:9999;
    background: linear-gradient(0deg, rgba(0,0,0,0.55), rgba(0,0,0,0.75));
  }
  .loader-card {
    width:320px; 
    padding:22px; 
    border-radius:12px; 
    background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02));
    display:flex; 
    flex-direction:column; 
    gap:14px; align-items:center; 
    border:1px solid rgba(255,255,255,0.04);
    backdrop-filter: blur(6px) saturate(140%);
  }
  /* metallic gear with color-cycle animation */
  .gear {
    width:96px; 
    height:96px; 
    border-radius:50%; 
    position:relative; 
    display:flex; 
    align-items:center; 
    justify-content:center;
    box-shadow: 0 12px 40px rgba(0,0,0,0.6), inset 0 -6px 20px rgba(255,255,255,0.02);
    background: radial-gradient(circle at 30% 30%, #f7f7f7, #cfcfcf 30%, #8c8c8c 70%, #3b3b3b 100%);
    transform-origin:center;
    animation: gearSpin 2s linear infinite, gearTint 6s linear infinite;
  }

  .gear:before, .gear:after {
    content:""; 
    position:absolute; 
    border-radius:2px; 
    background:rgba(0,0,0,0.2);
  }
  .gear:before { 
    width:12px; 
    height:48px; 
    left:22px; 
    top:24px; 
    transform:rotate(25deg); 
    border-radius:3px; 
  }
  .gear:after { 
    width:12px; 
    height:48px; 
    right:22px; 
    top:24px; 
    transform:rotate(-25deg); 
    border-radius:3px; 
  }
  @keyframes gearSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
  }
  /* gearTint cycles through colors each cycle */
  @keyframes gearTint {
    0%   { box-shadow: 0 12px 40px rgba(255,120,40,0.18), inset 0 -6px 20px rgba(255,120,40,0.08); }
    25%  { box-shadow: 0 12px 40px rgba(80,160,255,0.18), inset 0 -6px 20px rgba(80,160,255,0.06); }
    50%  { box-shadow: 0 12px 40px rgba(255,70,100,0.18), inset 0 -6px 20px rgba(255,70,100,0.06); }
    75%  { box-shadow: 0 12px 40px rgba(200,200,220,0.18), inset 0 -6px 20px rgba(200,200,220,0.06); }
    100% { box-shadow: 0 12px 40px rgba(255,120,40,0.18), inset 0 -6px 20px rgba(255,120,40,0.08); }
  }

  .loader-text { 
    color:#ffd8b3; 
    font-family: 'Orbitron', sans-serif; 
    letter-spacing:0.6px; }

  /* responsive */
  @media (max-width:880px) {
    .panel { grid-template-columns: 1fr; }
    .right { order:-1; }
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
      setTimeout(()=>{ window.location.href = 'owner_dash.php'; }, 2600);
    });
  <?php endif; ?>

  // optional: tiny micro-interaction when clicking Save
  const form = document.getElementById('editCarForm');
  form.addEventListener('submit', ()=> {
    // small UX: briefly show a subtle glow on the button
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
      btn.style.transform = 'translateY(-2px)';
      setTimeout(()=>btn.style.transform = '', 250);
    }
  });
</script>

</body>
</html>
