<?php
// Database connection settings
$servername = "localhost";   // Heidimysql default
$username   = "root";        // MariaDB MySQL user
$password   = "12345";            //set my password
$dbname     = "cmts_db";     // database name

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
