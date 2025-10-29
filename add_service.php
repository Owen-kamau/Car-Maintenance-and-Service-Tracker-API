<?php
session_start();
include("DBConn.php");

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch cars owned by this user
$query = $conn->prepare("SELECT id, model, license_plate FROM cars WHERE owner_id = ?");
$query->bind_param("i", $userId);
$query->execute();
$result = $query->get_result();
$userCars = $result->fetch_all(MYSQLI_ASSOC);

$query->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Schedule a Service | Vintage Garage</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background-color: #1a1a1a;
    color: #f5f5f5;
    font-family: 'Poppins', sans-serif;
}
.container {
    max-width: 600px;
    margin-top: 70px;
    background: #222;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 0 25px rgba(255, 215, 0, 0.15);
}
h2 {
    color: #ffd700;
    text-align: center;
    margin-bottom: 25px;
}
.form-label {
    color: #ddd;
}
.form-select, .form-control {
    background: #333;
    border: 1px solid #444;
    color: #fff;
}
.form-select:focus, .form-control:focus {
    border-color: #ffd700;
    box-shadow: 0 0 5px #ffd700;
}
.btn-custom {
    background-color: #ffd700;
    color: #111;
    border: none;
    width: 100%;
    font-weight: bold;
    transition: 0.3s;
}
.btn-custom:hover {
    background-color: #e6c200;
    transform: scale(1.03);
}
.register-link {
    color: #ffd700;
    text-decoration: none;
}
.register-link:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="container">
    <h2>Schedule Your Car Service</h2>

    <?php if (!empty($userCars)) : ?>
        <form action="save_service.php" method="POST">
            <div class="mb-3">
                <label for="car_id" class="form-label">Select Your Car</label>
                <select name="car_id" id="car_id" class="form-select" required>
                    <option value="">-- Choose a registered car --</option>
                    <?php foreach ($userCars as $car): ?>
                        <option value="<?= htmlspecialchars($car['id']) ?>">
                            <?= htmlspecialchars($car['model']) ?> - <?= htmlspecialchars($car['license_plate']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="service_type" class="form-label">Service Type</label>
                <select name="service_type" id="service_type" class="form-select" required>
                    <option value="">-- Select Service Type --</option>
                    <option value="General Checkup">General Checkup</option>
                    <option value="Oil Change">Oil Change</option>
                    <option value="Engine Tune-Up">Engine Tune-Up</option>
                    <option value="Tire Replacement">Tire Replacement</option>
                    <option value="Full Restoration">Full Restoration</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="service_date" class="form-label">Preferred Date</label>
                <input type="date" name="service_date" id="service_date" class="form-control" min="<?= date('Y-m-d'); ?>" required>
            </div>

            <button type="submit" class="btn btn-custom">Book Service</button>
        </form>
    <?php else: ?>
        <p class="text-center mt-3">You haven’t registered any car yet.</p>
        <div class="text-center">
            <a href="CarReg.php" class="register-link">Register a car now →</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
