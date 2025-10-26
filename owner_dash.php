<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

include("DBConn.php");

$userId = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// Garage types
$garages = ['vehicle'=>'Vehicles', 'truck'=>'Trucks', 'tractor'=>'Tractors'];
$carsByGarage = [];

// Fetch cars with service info
foreach ($garages as $type => $label) {
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT MIN(s.next_service_date) 
                FROM services s 
                WHERE s.car_id=c.id AND s.next_service_date >= CURDATE()) AS next_service,
               (SELECT s.service_date 
                FROM services s 
                WHERE s.car_id=c.id AND s.service_date IS NOT NULL 
                ORDER BY s.service_date DESC LIMIT 1) AS last_service
        FROM cars c 
        WHERE c.user_id=? AND c.garage_type=?
        ORDER BY c.year DESC
    ");
    $stmt->bind_param("is", $userId, $type);
    $stmt->execute();
    $res = $stmt->get_result();
    $carsByGarage[$type] = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$status = $_GET['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Dashboard | CMTS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#111; color:#f0f0f0; font-family:'Poppins',sans-serif; }
header { background:#1a1a1a; padding:20px 40px; display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #ff4d00; }
header .brand { font-weight:600; font-size:1.5rem; color:#ff4d00; }
header nav a { color:#ccc; margin-left:20px; text-decoration:none; font-weight:500; transition:0.3s; }
header nav a:hover { color:#ffd700; text-shadow: 0 0 8px #ffd700; }
.container-main { padding:30px; max-width:1200px; margin:auto; }
.nav-tabs .nav-link { color:#ccc; }
.nav-tabs .nav-link.active { background:#222; color:#ff4d00; border-color:#ff4d00; }
.tab-content { margin-top:20px; }
.btn-glow { transition:0.3s; }
.btn-glow:hover { box-shadow: 0 0 10px #ffd700, 0 0 20px rgba(255,215,0,0.5); transform: translateY(-2px); }
.card-car { position:relative; background: linear-gradient(135deg, #000000, #1a1a1a); border:2px solid #ffd700; border-radius:16px; padding:15px; transition:0.3s; text-align:center; overflow:hidden; }
.card-car:hover { box-shadow:0 0 20px #ffd700, 0 0 40px rgba(255,215,0,0.5); transform:translateY(-3px); }
.card-car img { max-width:100%; border-radius:10px; margin-bottom:10px; border:2px solid #ffd700; background: rgba(0,0,0,0.6); padding:2px; }
.card-car h5 { color:#ffd700; margin-bottom:5px; font-family:'Lucida Console',monospace; }
.card-car p { font-size:0.9rem; color:#ccc; margin:0; }
.card-overlay { position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); display:flex; justify-content:center; align-items:center; gap:10px; opacity:0; transition:0.3s; border-radius:16px; }
.card-car:hover .card-overlay { opacity:1; }
.card-overlay button, .card-overlay a { padding:6px 12px; border:none; border-radius:6px; font-weight:500; cursor:pointer; transition:0.2s; text-decoration:none; color:#000; }
.btn-edit { background:#ffd700; }
.btn-edit:hover { background:#ffcc00; }
.btn-delete { background:#c0392b; color:#fff; }
.btn-delete:hover { background:#e74c3c; }
.service-info { background: rgba(255,215,0,0.1); padding:8px; margin-top:8px; border-radius:8px; font-size:0.85rem; }
.service-info p { margin:2px 0; }
.service-due { border: 2px solid #ffd700 !important; box-shadow: 0 0 15px #ffd700, 0 0 30px rgba(255,215,0,0.5); }
.modal-content { background-color: #181818; border-radius: 12px; }
.btn-outline-info:hover { background-color: #0dcaf0; color: #000; }
.toast { box-shadow: 0 0 10px rgba(255,255,255,0.1); border-radius: 10px; }
.text-bg-info { background-color: #0dcaf0 !important; color: #000; }
.text-bg-success { background-color: #198754 !important; }
.text-bg-danger { background-color: #dc3545 !important; }
</style>
</head>
<body>
<header>
    <div class="brand">CMTS <span>Owner</span></div>
    <nav>
        <a href="index.php" class="btn-glow">Home</a>
        <a href="logout.php" class="btn-glow">Logout</a>
    </nav>
</header>

<div class="container-main">
<h2>Welcome back, <?php echo $username; ?>👋</h2>

<div class="row mb-4">
<?php foreach($garages as $type => $label): 
    $total = count($carsByGarage[$type]);
    $upcoming = 0;
    foreach($carsByGarage[$type] as $car) {
        if($car['next_service'] && (strtotime($car['next_service']) - strtotime(date('Y-m-d'))) / 86400 <= 7 && (strtotime($car['next_service']) - strtotime(date('Y-m-d'))) >=0)
            $upcoming++;
    }
?>
<div class="col-md-4">
    <div class="card text-center p-3 mb-2" style="background:#222; color:#ffd700; border-radius:12px; box-shadow:0 0 15px rgba(255,215,0,0.3);">
        <h5><?php echo $label; ?></h5>
        <p>Total Cars: <?php echo $total; ?></p>
        <p>Services Due Soon: <?php echo $upcoming; ?></p>
    </div>
</div>
<?php endforeach; ?>
</div>

<ul class="nav nav-tabs" id="garageTab" role="tablist">
<?php $first=true; foreach($garages as $type=>$label): ?>
<li class="nav-item" role="presentation">
    <button class="nav-link <?php if($first) echo 'active'; ?>" id="<?php echo $type; ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo $type; ?>" type="button" role="tab"><?php echo $label; ?></button>
</li>
<?php $first=false; endforeach; ?>
</ul>

<div class="tab-content">
<?php $first=true; foreach($garages as $type=>$label): ?>
<div class="tab-pane fade <?php if($first) echo 'show active'; ?>" id="<?php echo $type; ?>" role="tabpanel">
    <div class="row mt-3">
        <?php if(!empty($carsByGarage[$type])): ?>
            <?php foreach($carsByGarage[$type] as $car):
                $isDue = false;
                if ($car['next_service']) {
                    $daysUntilService = (strtotime($car['next_service']) - strtotime(date('Y-m-d'))) / 86400;
                    if ($daysUntilService <= 7 && $daysUntilService >=0) $isDue = true;
                }
            ?>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-car <?php if($isDue) echo 'service-due'; ?>">
                    <img src="<?php echo !empty($car['car_image']) ? $car['car_image'] : 'default-car.jpg'; ?>" alt="Car Image">
                    <h5><?php echo htmlspecialchars($car['make'].' '.$car['model']); ?></h5>
                    <p>Year: <?php echo $car['year']; ?></p>
                    <p>License: <?php echo htmlspecialchars($car['license_plate']); ?></p>

                    <div class="service-info">
                        <p><strong>Next Service:</strong> <?php echo $car['next_service'] ? date("M j, Y", strtotime($car['next_service'])) : '-'; ?></p>
                        <p><strong>Last Service:</strong> <?php echo $car['last_service'] ? date("M j, Y", strtotime($car['last_service'])) : '-'; ?></p>
                    </div>

                    <div class="card-overlay">
                        <a href="edit_car.php?car_id=<?= urlencode($car['id']); ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $car['id']; ?>">🗑 Delete</button>
                    </div>
                </div>

                <!-- Keep modal immediately after card for each car -->
                <div class="modal fade" id="deleteModal<?= $car['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-white border-secondary">
                            <div class="modal-header">
                                <h5 class="modal-title">Delete <?= htmlspecialchars($car['model']); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form method="post" action="delete_car.php" class="mb-3">
                                    <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['id']); ?>">
                                    <button type="submit" name="request_code" class="btn btn-outline-info w-100">📩 Request Verification Code</button>
                                </form>
                                <hr class="border-secondary my-3">
                                <form method="post" action="delete_car.php">
                                    <input type="hidden" name="car_id" value="<?= htmlspecialchars($car['id']); ?>">
                                    <div class="mb-3">
                                        <label class="form-label text-light">Enter Verification Code</label>
                                        <input type="text" name="verification_code" class="form-control bg-dark text-white border-secondary" placeholder="6-digit code" required>
                                    </div>
                                    <button type="submit" name="delete_car" class="btn btn-danger w-100">🚗 Delete Permanently</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-muted mt-3">No cars in this garage yet.</p>
        <?php endif; ?>
    </div>
</div>
<?php $first=false; endforeach; ?>
</div>

<!-- Toasts & JS -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:2000;">
<?php if($status==='request_sent'): ?><div class="toast align-items-center text-bg-info border-0 show"><div class="d-flex"><div class="toast-body">✅ Request sent!</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div><?php elseif($status==='deleted'): ?><div class="toast align-items-center text-bg-success border-0 show"><div class="d-flex"><div class="toast-body">🚗💨 Car deleted!</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div><?php elseif($status==='invalid_code'): ?><div class="toast align-items-center text-bg-danger border-0 show"><div class="d-flex"><div class="toast-body">❌ Invalid code</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div><?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(()=>{document.querySelectorAll('.toast').forEach(t=>bootstrap.Toast.getOrCreateInstance(t).hide());},5000);
    if(window.location.search.includes('status=deleted')){
        document.querySelectorAll('.modal.show').forEach(m=>bootstrap.Modal.getInstance(m)?.hide());
    }
});
</script>
</body>
</html>
