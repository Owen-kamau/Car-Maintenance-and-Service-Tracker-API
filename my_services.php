<?php
session_start();
include("DBConn.php");

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

// Fetch user's services
$query = "
    SELECT s.id, s.service_type, s.service_date, s.status, s.created_at,
           c.model AS car_model, c.license_plate
    FROM services s
    JOIN cars c ON s.car_id = c.id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Services | CMTS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: #f8f9fa;
    }
    .container {
        margin-top: 70px;
    }
    .card {
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.08);
    }
    .status-badge {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.85em;
        font-weight: 600;
    }
    .status-pending {
        background: #ffeeba;
        color: #856404;
    }
    .status-completed {
        background: #d4edda;
        color: #155724;
    }
    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }
</style>
</head>
<body>

<div class="container">
    <h3 class="text-center mb-4">Hello, <?= $username ?> 👋</h3>
    <h4 class="text-center mb-4">Your Scheduled Car Services</h4>

    <?php if (empty($services)): ?>
        <div class="alert alert-info text-center">
            You haven’t scheduled any services yet. <a href="add_service.php" class="alert-link">Schedule one now</a>.
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <?php foreach ($services as $service): ?>
                    <div class="card mb-3">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1"><?= htmlspecialchars($service['car_model']) ?> (<?= htmlspecialchars($service['license_plate']) ?>)</h5>
                                <p class="mb-0"><strong>Service:</strong> <?= htmlspecialchars($service['service_type']) ?></p>
                                <p class="mb-0"><strong>Date:</strong> <?= htmlspecialchars($service['service_date']) ?></p>
                                <small class="text-muted">Scheduled on <?= htmlspecialchars($service['created_at']) ?></small>
                            </div>
                            <div>
                                <?php
                                    $statusClass = match (strtolower($service['status'])) {
                                        'pending' => 'status-pending',
                                        'completed' => 'status-completed',
                                        'cancelled' => 'status-cancelled',
                                        default => 'status-pending'
                                    };
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($service['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
