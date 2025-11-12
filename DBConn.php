<?php
// Database connection settings
$servername = "localhost";      // Server name
$username   = "root";      // MySQL user (must use mysql_native_password)
$password   = "6350";  // Your MySQL password
$dbname     = "cmts_db";        // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Database Connection failed: " . $conn->connect_error);
}
// else {
//     echo "✅ Connected successfully"; // Uncomment to test
// }
?>
