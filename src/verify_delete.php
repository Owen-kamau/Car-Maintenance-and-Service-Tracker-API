<?php
session_start();
include("DBConn.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$adminName = htmlspecialchars($_SESSION['username']);

// Handle approval
if (isset($_POST['approve'])) {
    $requestId = $_POST['request_id'];
    
    // Fetch request details
    $stmt = $conn->prepare("
        SELECT dr.*, u.username, u.email, c.model, c.license_plate
        FROM delete_requests dr
        JOIN users u ON dr.user_id = u.id
        JOIN cars c ON dr.car_id = c.id
        WHERE dr.id = ?
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();

    if ($req) {
        $code = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Update verification code & expiry
        $stmt = $conn->prepare("UPDATE delete_requests SET verification_code = ?, expires_at = ?, status = 'pending' WHERE id = ?");
        $stmt->bind_param("ssi", $code, $expiry, $requestId);
        $stmt->execute();

        // Send email to owner
        // Send email to owner
$subject = "CMTS Car Deletion Verification Code";

$message = "
<html>
  <body style='background:#111; font-family:Segoe UI, sans-serif; color:#eee; padding:30px;'>
    <div style='max-width:600px; margin:auto; background:#1c1c1c; border:1px solid #444; border-radius:12px; box-shadow:0 0 12px rgba(255,140,0,0.3);'>
      <div style='background:linear-gradient(145deg,#2b2b2b,#1a1a1a); border-bottom:1px solid #333; padding:20px 25px; border-radius:12px 12px 0 0;'>
        <h2 style='color:#ffb84d; text-align:center; letter-spacing:1px; margin:0;'>🚗 CMTS Car Deletion Request</h2>
      </div>
      <div style='padding:25px;'>
        <p>Hi <strong style='color:#ffd700;'>{$req['username']}</strong>,</p>
        <p>Your request to delete the car <strong>{$req['model']}</strong> (Reg: <strong>{$req['license_plate']}</strong>) has been verified by the Admin <strong>{$adminName}</strong>.</p>
        <p style='margin-top:15px;'>Here’s your one-time verification code (valid for <strong>10 minutes</strong>):</p>
        <div style='text-align:center; margin:25px 0;'>
          <span style='display:inline-block; font-size:28px; font-weight:bold; letter-spacing:6px; color:#fff; background:#ff7b00; padding:14px 28px; border-radius:8px; text-shadow:1px 1px 4px #000;'>
            {$code}
          </span>
        </div>
        <p>If this code doesn’t work or has expired, click below to retry:</p>
        <p style='text-align:center; margin:20px 0;'>
          <a href='http://localhost/CMTS/delete_car.php?id={$req['car_id']}' 
             style='color:#fff; background:#cc5200; padding:12px 20px; border-radius:6px; text-decoration:none; font-weight:bold;'>
             Retry Deletion
          </a>
        </p>
        <p style='font-size:13px; color:#bbb; margin-top:20px;'>⚙️ This is an automated message from Car Maintenance & Tracking System (CMTS). Please do not reply.</p>
      </div>
    </div>
  </body>
</html>
";

mail($req['email'], $subject, $message, "Content-Type: text/html; charset=UTF-8");

$msg = "✅ Code sent successfully to {$req['email']}.";


    } else {
        $msg = "❌ Request not found.";
    }
}

// Fetch pending delete requests
$requests = $conn->query("
    SELECT dr.*, u.username, u.email, c.car_model, c.car_reg_no
    FROM delete_requests dr
    JOIN users u ON dr.user_id = u.id
    JOIN cars c ON dr.car_id = c.id
    WHERE dr.status = 'pending'
    ORDER BY dr.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Delete Requests | CMTS Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
<style>
body {
  font-family: 'Orbitron', sans-serif;
  background: linear-gradient(135deg, #1c1c1c, #000);
  color: #ffb347;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 30px;
}
h1 {
  color: #ffa500;
  margin-bottom: 25px;
}
.table-container {
  background: #2c2c2c;
  border: 2px solid #b87333;
  box-shadow: 0 0 20px rgba(255,140,0,0.3);
  border-radius: 12px;
  overflow: hidden;
  width: 85%;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th, td {
  padding: 12px;
  border-bottom: 1px solid #b87333;
  text-align: center;
}
th {
  background: #3c3c3c;
  color: #ffd8b3;
}
tr:hover {
  background: rgba(255,165,0,0.05);
}
button {
  background: linear-gradient(90deg, #cc5500, #8b0000);
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  color: #fff;
  font-weight: bold;
  cursor: pointer;
  transition: 0.3s;
}
button:hover {
  transform: scale(1.05);
  background: linear-gradient(90deg, #ff6600, #b22222);
}
.message {
  margin: 15px;
  color: #ffcc70;
}
</style>
</head>
<body>
<h1>Pending Deletion Requests</h1>
<?php if(isset($msg)): ?>
  <div class="message"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<div class="table-container">
  <table>
    <tr>
      <th>ID</th>
      <th>Owner</th>
      <th>Email</th>
      <th>Car Model</th>
      <th>Reg No</th>
      <th>Action</th>
    </tr>
    <?php while($row = $requests->fetch_assoc()): ?>
    <tr>
      <td><?= $row['id'] ?></td>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= htmlspecialchars($row['email']) ?></td>
      <td><?= htmlspecialchars($row['model']) ?></td>
      <td><?= htmlspecialchars($row['license_plate']) ?></td>
      <td>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
          <button type="submit" name="approve">✅ Verify & Send Code</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>
</body>
</html>
