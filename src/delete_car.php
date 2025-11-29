<?php
session_start();
include("DBConn.php");
include("mail.php"); // Your existing mail sending script

// Only owners can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = $error = "";

// Handle code request
if (isset($_POST['request_code'])) {
    $car_id = (int)($_POST['car_id'] ?? 0);

    // Check car ownership
    $stmt = $conn->prepare("SELECT license_plate FROM cars WHERE id=? AND user_id=? AND is_deleted=0");
    $stmt->bind_param("ii", $car_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $error = "Car not found or already deleted.";
    } else {
        $car = $res->fetch_assoc();
        $license_plate = $car['license_plate'];

        // Check if a pending code already exists for this car in the last 5 mins
        $stmt2 = $conn->prepare("SELECT id FROM delete_requests WHERE car_id=? AND user_id=? AND status='pending' AND created_at > (NOW() - INTERVAL 5 MINUTE)");
        $stmt2->bind_param("ii", $car_id, $user_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();

        if ($res2->num_rows > 0) {
            $error = "A verification code was recently sent. Please wait a few minutes before requesting again.";
        } else {
            // Generate unique 4-digit code based on car_id + time
            $verification_code = substr(str_pad(rand(1000,9999),4,'0',STR_PAD_LEFT),0,4);

            // Insert into delete_requests
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmt3 = $conn->prepare("INSERT INTO delete_requests (user_id, car_id, verification_code, expires_at, status) VALUES (?,?,?,?, 'pending')");
            $stmt3->bind_param("iiss", $user_id, $car_id, $verification_code, $expires_at);
            $stmt3->execute();
            $stmt3->close();

            // Fetch owner's email
            $stmt_email = $conn->prepare("SELECT email FROM users WHERE id=? LIMIT 1");
            $stmt_email->bind_param("i",$user_id);
            $stmt_email->execute();
            $res_email = $stmt_email->get_result();
            $owner = $res_email->fetch_assoc();
            $stmt_email->close();

            $to = $owner['email'];
            $subject = "Car Deletion Verification Code";
            $message = "Hello,\n\nYour verification code to delete the car with license plate {$license_plate} is: {$verification_code}\n\nIt expires in 15 minutes.";
            
            if(sendMail($to,$subject,$message)) { // use your mail.php function
                $success = "Verification code sent to your email. Check your inbox.";
            } else {
                $error = "Failed to send email. Try again later.";
            }
        }
    }
}

// Handle deletion attempt
if (isset($_POST['delete_car'])) {
    $car_id = (int)($_POST['car_id'] ?? 0);
    $input_code = trim($_POST['verification_code'] ?? '');

    // Fetch latest unused pending request
    $stmt = $conn->prepare("SELECT id, expires_at, status, used FROM delete_requests WHERE car_id=? AND user_id=? AND verification_code=? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("iis", $car_id, $user_id, $input_code);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $error = "Invalid verification code.";
    } else {
        $request = $res->fetch_assoc();
        if ($request['used'] || $request['status']=='used') {
            $error = "This code has already been used.";
        } elseif (strtotime($request['expires_at']) < time()) {
            $error = "Verification code expired.";
            $stmt_exp = $conn->prepare("UPDATE delete_requests SET status='expired' WHERE id=?");
            $stmt_exp->bind_param("i",$request['id']);
            $stmt_exp->execute();
            $stmt_exp->close();
        } else {
            // Mark request as used
            $stmt_upd = $conn->prepare("UPDATE delete_requests SET status='used', used=1 WHERE id=?");
            $stmt_upd->bind_param("i",$request['id']);
            $stmt_upd->execute();
            $stmt_upd->close();

            // Soft-delete the car
            $stmt_del = $conn->prepare("UPDATE cars SET is_deleted=1, deleted_at=NOW(), deleted_by=? WHERE id=? AND user_id=?");
            $stmt_del->bind_param("iii",$user_id,$car_id,$user_id);
            $stmt_del->execute();
            $stmt_del->close();

            $success = "Car has been marked as deleted. It will no longer appear in your dashboard.";
        }
       // Mark success and redirect to dashboard
       header("Location: owner_dash.php?status=deleted");
       exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Car</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#111; color:#ffd700; font-family:sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.card { background:#222; padding:30px; border-radius:12px; width:400px; text-align:center; box-shadow:0 0 15px rgba(255,215,0,0.3); }
input, button { margin:10px 0; width:100%; }
input { padding:10px; border-radius:6px; border:none; background:#333; color:#fff; }
button { background:#ffcc00; color:#111; border:none; padding:12px; font-weight:bold; border-radius:6px; cursor:pointer; }
button:disabled { opacity:0.6; cursor:not-allowed; }
p.success { background:#2e7d32; color:#fff; padding:10px; border-radius:6px; }
p.error { background:#c62828; color:#fff; padding:10px; border-radius:6px; }
</style>
</head>
<body>
<div class="card">
<h3>Delete Car</h3>

<?php if($success) echo "<p class='success'>$success</p>"; ?>
<?php if($error) echo "<p class='error'>$error</p>"; ?>

<form method="post">
    <input type="hidden" name="car_id" value="<?= htmlspecialchars($_POST['car_id'] ?? ''); ?>">
    <button type="submit" name="request_code">📩 Request Verification Code</button>
</form>

<form method="post">
    <input type="hidden" name="car_id" value="<?= htmlspecialchars($_POST['car_id'] ?? ''); ?>">
    <input type="text" name="verification_code" placeholder="Enter Verification Code" required>
    <button type="submit" name="delete_car">🚗 Delete Permanently</button>
</form>

</div>
</body>
</html>
