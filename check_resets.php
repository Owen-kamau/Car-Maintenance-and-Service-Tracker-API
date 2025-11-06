 <?php
include("DBConn.php");

echo '<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Records</title>
    <style>
        body {
            font-family: "Edu SA Hand", cursive;
            background: linear-gradient(135deg, #fce4ec, #f8bbd0, #f48fb1);
            color: #3b302a;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px;
        }
        .container {
            background: #fff;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 80%;
            max-width: 700px;
        }
        h2 {
            color: #d81b60;
            margin-bottom: 20px;
            text-align: center;
        }
        .record {
            background: #fce4ec;
            border-left: 5px solid #ec407a;
            padding: 10px;
            margin: 8px 0;
            border-radius: 8px;
        }
        .no-records {
            color: #c2185b;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
<h2>Password Reset Records</h2>';

$result = $conn->query("SELECT email, code, expires_at FROM password_resets");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='record'>
                <strong>Email:</strong> {$row['email']}<br>
                <strong>Code:</strong> {$row['code']}<br>
                <strong>Expires:</strong> {$row['expires_at']}
              </div>";
    }
} else {
    echo "<p class='no-records'>⚠️ No records found in password_resets table.</p>";
}

echo '</div></body></html>';
?>
