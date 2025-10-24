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
               (SELECT MIN(s.next_service_date) FROM services s WHERE s.car_id=c.id AND s.next_service_date >= CURDATE()) AS next_service,
               (SELECT s.service_date FROM services s WHERE s.car_id=c.id AND s.service_date IS NOT NULL ORDER BY s.service_date DESC LIMIT 1) AS last_service
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Dashboard | CMTS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { 
  background:#111; 
  color:#f0f0f0; 
  font-family:'Poppins',sans-serif; 
}header { 
  background:#1a1a1a; 
  padding:20px 40px; 
  display:flex; 
  justify-content:space-between; 
  align-items:center; 
  border-bottom:2px solid #ff4d00; 
}header .brand { 
  font-weight:600; 
  font-size:1.5rem; 
  color:#ff4d00; 
}header nav a { 
  color:#ccc; 
  margin-left:20px; 
  text-decoration:none; 
  font-weight:500; 
}header nav a:hover { 
  color:#ff6600; 
}.container-main { 
  padding:30px; 
  max-width:1200px; 
  margin:auto; 
}.nav-tabs .nav-link { 
  color:#ccc; 
}.nav-tabs .nav-link.active { 
  background:#222; 
  color:#ff4d00; 
  border-color:#ff4d00; 
}.tab-content { 
  margin-top:20px; 
}.card-car {
    position:relative;
    background:#1c1c1c;
    border:1px solid #333;
    border-radius:14px;
    padding:15px;
    transition:0.3s;
    text-align:center;
    overflow:hidden;
}.card-car:hover { 
  box-shadow:0 0 25px #ff4d00, 0 0 50px rgba(255,77,0,0.5); 
  transform:translateY(-3px); 
}.card-car img { 
  max-width:100%; 
  border-radius:10px; 
  margin-bottom:10px; 
}.card-car h5 { 
  color:#ff4d00; 
  margin-bottom:5px; 
}.card-car p { 
  font-size:0.9rem; 
  color:#ccc; 
  margin:0; 
}.card-overlay {
    position:absolute; 
    top:0; 
    left:0;
    width:100%; 
    height:100%;
    background:rgba(0,0,0,0.7);
    display:flex; 
    justify-content:center; 
    align-items:center;
    gap:10px; opacity:0; 
    transition:0.3s; 
    border-radius:14px;
}.card-car:hover .card-overlay { 
  opacity:1; 
}.card-overlay button {
    padding:6px 12px; 
    border:none; 
    border-radius:6px; 
    font-weight:500; 
    cursor:pointer; 
    transition:0.2s;
}.btn-edit { 
  background:#ff6600; 
  color:#fff; 
}.btn-edit:hover { 
  background:#ff4d00; 
}.btn-delete { 
  background:#c0392b; 
  color:#fff; 
}.btn-delete:hover { 
  background:#e74c3c; 
}.add-car-btn {
   margin-bottom:20px; 
}.service-info { 
  background: rgba(255,77,0,0.05); 
  padding:8px; 
  margin-top:8px; 
  border-radius:8px; 
  font-size:0.85rem; 
}.service-info p { 
  margin:2px 0; 
}.service-due {
    border: 2px solid #ff4d00 !important;
    box-shadow: 0 0 15px #ff4d00, 0 0 30px rgba(255,77,0,0.5);
}

.service-due .service-info p.next {
    color: #ff4d00;
    font-weight: 600;
}
</style>
</head>
<body>
<header>
    <div class="brand">CMTS <span>Owner</span></div>
    <nav>
        <a href="index.php">Home</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container-main">
    <h2>Welcome back, <?php echo $username; ?> 👋</h2>
    <a href="CarReg.php" class="btn btn-warning add-car-btn">➕ Register New Car</a>

    <div class="row mb-4">
    <?php foreach($garages as $type => $label): 
        $total = count($carsByGarage[$type]);
        $upcoming = 0;
        foreach($carsByGarage[$type] as $car) {
            if($car['next_service'] && (strtotime($car['next_service']) - strtotime(date('Y-m-d'))) <= 7*86400) {
                $upcoming++;
            }
        }
    ?>
    <div class="col-md-4">
        <div class="card text-center p-3 mb-2" style="background:#222; color:#ff4d00; border-radius:12px; box-shadow:0 0 15px rgba(255,77,0,0.3);">
            <h5><?php echo $label; ?></h5>
            <p>Total Cars: <?php echo $total; ?></p>
            <p>Services Due Soon: <?php echo $upcoming; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>


    <!-- Garage Tabs -->
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
                        <?php foreach($carsByGarage[$type] as $car): ?>
                          <?php
                          // Check if the next service is within 7 days
                          $isDue = false;
                          if ($car['next_service']) {
                              $daysUntilService = (strtotime($car['next_service']) - strtotime(date('Y-m-d'))) / 86400;
                              if ($daysUntilService <= 7) {
                                $isDue = true;
                              }
                          }
                          ?>
                            <div class="col-md-4 col-sm-6 mb-4">
                                <div class="card-car"> <?php if($isDue) echo 'service-due'; ?>">
                                    <img src="<?php echo !empty($car['car_image']) ? $car['car_image'] : 'default-car.jpg'; ?>" alt="Car Image">
                                    <h5><?php echo htmlspecialchars($car['make'].' '.$car['model']); ?></h5>
                                    <p>Year: <?php echo $car['year']; ?></p>
                                    <p>License: <?php echo htmlspecialchars($car['license_plate']); ?></p>

                                    <div class="service-info">
                                        <p><strong>Next Service:</strong> <?php echo $car['next_service'] ? date("M j, Y", strtotime($car['next_service'])) : '-'; ?></p>
                                        <p><strong>Last Service:</strong> <?php echo $car['last_service'] ? date("M j, Y", strtotime($car['last_service'])) : '-'; ?></p>
                                    </div>

                                    <div class="card-overlay">
                                        <form method="post" action="edit_car.php" style="display:inline;">
                                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                            <button type="submit" class="btn-edit">Edit</button>
                                        </form>
                                        <form method="post" action="delete_car.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this car?');">
                                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
