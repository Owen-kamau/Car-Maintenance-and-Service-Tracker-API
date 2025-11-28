<?php
if (!defined('CMTS_SECURE')) {
    define('CMTS_SECURE', true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMTS System</title>

    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: #0f2027;
            padding: 15px 25px;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-title {
            font-size: 22px;
            color: #00eaff;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 0 10px rgba(0,234,255,0.6);
        }
        .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: #d3d3d3;
            font-size: 16px;
            transition: 0.3s;
        }
        .nav-links a:hover {
            color: #00eaff;
            text-shadow: 0 0 10px rgba(0,234,255,0.7);
        }
    </style>
</head>

<body>
    <div class="navbar">
        <div class="nav-title">CMTS System</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="services.php">Services</a>
            <a href="contact.php">Contact</a>
        </div>
    </div>
