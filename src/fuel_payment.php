<?php
// fuel_payment.php: Handles payment marking for fuel transactions
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit();
}
include('DBConn.php');

if (isset($_POST['fuel_id'])) {
    $fuel_id = intval($_POST['fuel_id']);
    $owner_id = $_SESSION['user_id'];
    // Only allow marking as paid if the transaction belongs to this owner
    $stmt = $conn->prepare('UPDATE fuel_transactions SET paid="yes" WHERE id=? AND owner_id=?');
    $stmt->bind_param('ii', $fuel_id, $owner_id);
    $stmt->execute();
    $stmt->close();
    header('Location: owner_dash.php?status=fuel_paid');
    exit();
}

// If GET request, show unpaid fuel payments for this owner
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Fuel Payments</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body style="background:#181818; color:#fff;">
    <div class="container py-4">
        <h2><i class="bi bi-credit-card"></i> My Fuel Payments</h2>
        <table class="table table-dark table-bordered table-striped mt-3">
            <thead><tr><th>Date</th><th>Car</th><th>Amount</th><th>Cost</th><th>Odometer</th><th>Pay</th></tr></thead>
            <tbody>
            <?php
            $stmt = $conn->prepare('SELECT f.*, c.make, c.model, c.license_plate FROM fuel_transactions f JOIN cars c ON f.car_id=c.id WHERE f.owner_id=? AND f.paid="no" ORDER BY f.transaction_date DESC');
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
                    <td>
                        <form method="post" action="fuel_payment.php" style="display:inline;">
                            <input type="hidden" name="fuel_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning"><i class="bi bi-credit-card"></i> Pay Now</button>
                        </form>
                    </td>
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
