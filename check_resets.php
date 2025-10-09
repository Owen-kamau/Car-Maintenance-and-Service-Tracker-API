<?php
// this confirms if the email and password are listed and appears in the DB
include("DBConn.php");
$result = $conn->query("SELECT email, code, expires_at FROM password_resets");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Email: {$row['email']} | Code: {$row['code']} | Expires: {$row['expires_at']}<br>";
    }
} else {
    echo "⚠️ No records found in password_resets table.";
}
?>
