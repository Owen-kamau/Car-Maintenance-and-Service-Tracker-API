<?php
require 'mail.php'; // uses your existing mail.php file

$to = "David.Ochieng@strathmore.edu"; // change this to your email
$subject = "✅ Test Email from CMTS";
$body = "
<h2>Hi there!</h2>
<p>This is a test email sent from <b>Car Maintenance Tracker</b> using PHPMailer.</p>
";

echo sendMail($to, $subject, $body);
?>
