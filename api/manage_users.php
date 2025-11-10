<?php
include("../DBConn.php");
header('Content-Type: application/json');

// Helper: read JSON body
$input = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER['REQUEST_METHOD'];

// ✅ 1. GET — fetch users (with optional search)
if ($method === 'GET') {
    $search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";
    $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users 
                            WHERE username LIKE ? OR email LIKE ?
                            ORDER BY id DESC");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($users);
    exit();
}

// ✅ 2. POST — add new user
// POST — Add new user
if ($method === 'POST') {
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $role = trim($input['role'] ?? 'owner');

    if ($username === '' || $email === '' || $password === '') {
        echo json_encode(['status' => 'error', 'message' => 'Username, email, and password are required']);
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $username, $email, $hashed, $role);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'User added successfully']);
    exit();
}

// PUT — Edit user
if ($method === 'PUT') {
    $id = intval($input['id'] ?? 0);
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $role = trim($input['role'] ?? 'owner');

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        exit();
    }

    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $email, $hashed, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $email, $role, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }
    exit();
}

// ✅ 4. DELETE — remove user
if ($method === 'DELETE') {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
        exit();
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
    exit();
}

// 🚫 Fallback for unsupported method
echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
?>
