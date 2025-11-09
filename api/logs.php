<?php
header('Content-Type: application/json');
session_start();
require_once '../Conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $logs = [];
    $result = mysqli_query($conn, "SELECT timestamp, level, message FROM system_logs ORDER BY id DESC LIMIT 40");
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = [
                'ts' => $row['timestamp'],
                'level' => $row['level'],
                'msg' => $row['message']
            ];
        }
    } else {
        // fallback empty list
        $logs = [];
    }
    echo json_encode($logs);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
}
?>
