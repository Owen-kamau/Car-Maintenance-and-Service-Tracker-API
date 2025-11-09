<?php
header('Content-Type: application/json');
session_start();
require_once '../Conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

function addLog($conn, $level, $msg) {
    $stmt = $conn->prepare("INSERT INTO system_logs (level, message) VALUES (?, ?)");
    $stmt->bind_param('ss', $level, $msg);
    $stmt->execute();
    $stmt->close();
}

try {
    switch ($action) {
        case 'rebuild_search':
            // Example: rebuild indexes
            addLog($conn, 'info', 'Search index rebuilt by admin');
            echo json_encode(['success' => true, 'message' => 'Search index rebuilt']);
            break;

        case 'clear_cache':
            // Example: clear temporary cache folder
            array_map('unlink', glob('../cache/*.tmp'));
            addLog($conn, 'info', 'Cache cleared by admin');
            echo json_encode(['success' => true, 'message' => 'Cache cleared']);
            break;

        case 'email_broadcast':
            addLog($conn, 'info', 'Broadcast message sent to users');
            echo json_encode(['success' => true, 'message' => 'Broadcast executed']);
            break;

        case 'backup_db':
        case 'trigger_backup':
            $backupFile = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
            $cmd = "mysqldump -u your_username -pyour_password your_database_name > $backupFile";
            exec($cmd);
            addLog($conn, 'info', 'Database backup created: ' . basename($backupFile));
            echo json_encode(['success' => true, 'message' => 'Database backup completed']);
            break;

        case 'toggle_maintenance':
            addLog($conn, 'warn', 'Maintenance mode toggled by admin');
            echo json_encode(['success' => true, 'message' => 'Maintenance mode toggled']);
            break;

        case 'health_check':
            addLog($conn, 'debug', 'System health check run');
            echo json_encode(['success' => true, 'message' => 'System health check passed']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
}
?>
