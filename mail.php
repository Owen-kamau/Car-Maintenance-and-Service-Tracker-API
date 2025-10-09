<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'owen.kamau@strathmore.edu';    // Your email
        $mail->Password   = 'ijkh ftdk goyg ogez';       // App password (not normal Gmail password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender & recipient
        $mail->setFrom('owen.kamau@strathmore.edu', 'Car Maintenance Tracker');
        $mail->addAddress($to);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return "✅ Message sent to $to";
    } catch (Exception $e) {
        return "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

/**
 * Service Reminder Template
 */
function sendServiceReminder($to, $ownerName, $carMake, $carModel, $serviceType, $nextServiceDate) {
    $subject = "⏰ Service Reminder: $serviceType for your $carMake $carModel";

    $body = "
        <h2>Hello $ownerName,</h2>
        <p>This is a friendly reminder from <b>Car Maintenance Tracker</b> 🚗.</p>
        <p>Your <b>$carMake $carModel</b> is due for <b>$serviceType</b> on <b>$nextServiceDate</b>.</p>
        <p>Please make sure to schedule your service in time to keep your car in top condition.</p>
        <br>
        <p>Best regards,<br>Car Maintenance Tracker Team</p>
    ";

    return sendMail($to, $subject, $body);
}
?>
