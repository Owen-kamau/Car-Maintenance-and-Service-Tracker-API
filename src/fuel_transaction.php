<?php
// fuel_transaction.php: Handles new fuel transaction submissions from owner dashboard
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit();
}
include('DBConn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = intval($_POST['car_id'] ?? 0);
    $owner_id = $_SESSION['user_id'];
    $fuel_amount = floatval($_POST['fuel_amount'] ?? 0);
    $cost = floatval($_POST['cost'] ?? 0);
    $odometer = isset($_POST['odometer']) ? intval($_POST['odometer']) : null;
    // Optionally, add station_id if you want to support station selection

    if ($car_id && $fuel_amount > 0 && $cost > 0) {
        $stmt = $conn->prepare('INSERT INTO fuel_transactions (car_id, owner_id, fuel_amount, cost, odometer, paid) VALUES (?, ?, ?, ?, ?, ?)');
        $paid = 'no';
        $stmt->bind_param('iiddis', $car_id, $owner_id, $fuel_amount, $cost, $odometer, $paid);
        $stmt->execute();
        $stmt->close();
        header('Location: owner_dash.php?status=fuel_added');
        exit();
    }
}
// If GET request, show fuel transactions table for this owner
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Fuel Transactions</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body style="background:#181818; color:#fff;">
    <div class="container py-4">
        <h2><i class="bi bi-fuel-pump-fill"></i> My Fuel Transactions</h2>
        <table class="table table-dark table-bordered table-striped mt-3">
            <thead><tr><th>Date</th><th>Car</th><th>Amount</th><th>Cost</th><th>Odometer</th><th>Paid</th></tr></thead>
            <tbody>
            <?php
            $stmt = $conn->prepare('SELECT f.*, c.make, c.model, c.license_plate FROM fuel_transactions f JOIN cars c ON f.car_id=c.id WHERE f.owner_id=? ORDER BY f.transaction_date DESC');
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= date('M j, Y', strtotime($row['transaction_date'])) ?></td>
                    <td><?= htmlspecialchars($row['make'].' '.$row['model'].' ('.$row['license_plate'].')') ?></td>
                    <td><?= htmlspecialchars($row['fuel_amount']) ?></td>
                    <td><?= htmlspecialchars($row['cost']) ?></td>
                    <td><?= $row['odometer'] ? htmlspecialchars($row['odometer']) : '-' ?></td>
                    <td><?= $row['paid'] === 'yes' ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Yes</span>' : '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle"></i> No</span>' ?></td>
                </tr>
            <?php endwhile; $stmt->close(); ?>
            </tbody>
        </table>
        <a href="owner_dash.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>
    </body>
    </html>
    <?php
    exit();
}
