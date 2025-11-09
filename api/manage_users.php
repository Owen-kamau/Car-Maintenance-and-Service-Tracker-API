<?php
session_start();
include("../DBConn"); // adjust path

if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='admin'){
    http_response_code(403);
    echo json_encode([]);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Update user
if($input && isset($input['id'])){
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
    $stmt->bind_param("sssi", $input['username'], $input['email'], $input['role'], $input['id']);
    if($stmt->execute()){
        echo json_encode(['success'=>true]);
    }else{
        echo json_encode(['success'=>false]);
    }
    exit();
}

// Delete
if(isset($_GET['delete'])){
    $id=intval($_GET['delete']);
    $stmt=$conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    echo json_encode(['success'=>true]);
    exit();
}

// Toggle role
if(isset($_GET['toggle'])){
    $id=intval($_GET['toggle']);
    $stmt=$conn->prepare("UPDATE users SET role = CASE WHEN role='admin' THEN 'user' ELSE 'admin' END WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    echo json_encode(['success'=>true]);
    exit();
}

// Fetch all users
$result=$conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users=[];
while($row=$result->fetch_assoc()){
    $users[]= [
        'id'=>$row['id'],
        'username'=>$row['username'],
        'email'=>$row['email'],
        'role'=>ucfirst($row['role']),
        'created_at'=>$row['created_at']
    ];
}
echo json_encode($users);
?>
