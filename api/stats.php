<?php
header('Content-Type: application/json');
session_start();
require_once '../Conn.php';

// Ensure authorized access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Active users (example: from users table)
    $resUsers = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE status='active'");
    $rowUsers = mysqli_fetch_assoc($resUsers);

    // Total cars
    $resCars = mysqli_query($conn, "SELECT COUNT(*) AS total FROM cars");
    $rowCars = mysqli_fetch_assoc($resCars);

    // Pending requests (e.g., from service_requests table)
    $resRequests = mysqli_query($conn, "SELECT COUNT(*) AS total FROM service_requests WHERE status='pending'");
    $rowRequests = mysqli_fetch_assoc($resRequests);

    // Average service time (if you have start_time / end_time columns)
    $resTime = mysqli_query($conn, "SELECT AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) AS avg_time FROM services WHERE end_time IS NOT NULL");
    $rowTime = mysqli_fetch_assoc($resTime);

    echo json_encode([
        'users' => intval($rowUsers['total'] ?? 0),
        'cars' => intval($rowCars['total'] ?? 0),
        'requests' => intval($rowRequests['total'] ?? 0),
        'avg_service_time' => round(floatval($rowTime['avg_time'] ?? 0), 1)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
}
?>
