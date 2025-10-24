<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

/**
 * Generic function to send email
 * All emails will be no-reply and automated
 */
function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'owen.kamau@strathmore.edu';    // Your SMTP email
        $mail->Password   = 'ijkh ftdk goyg ogez';          // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Set sender as no-reply
        $mail->setFrom('no-reply@yourdomain.com', 'Car Maintenance Tracker'); 
        $mail->addReplyTo('no-reply@yourdomain.com', 'Do Not Reply'); // Cannot reply
        $mail->addAddress($to);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;

        // Append automated message note at the bottom
        $body .= "<br><br><p style='font-size:0.9em; color:#555;'>
                  This is an automated message from Car Maintenance Tracker. Please do not reply.</p>";

        $mail->Body = $body;

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
    ";

    return sendMail($to, $subject, $body);
}

/**
 * Car Registration Template
 */
function sendCarRegistration($to, $ownerName, $make, $model, $year, $license_plate, $garage_type) {
    $subject = "Your Car Registration is Complete! 🚗";

    $body = "
        <div style='font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px;'>
            <div style='max-width: 600px; margin:auto; background: #fff; border-radius: 10px; padding: 20px; border: 2px solid #ff4d00;'>
                <h2 style='color:#ff4d00;'>Hi $ownerName!</h2>
                <p>Thank you for registering your car. Here are the details:</p>
                <table style='width:100%; border-collapse: collapse;'>
                    <tr><td style='padding:10px;'><b>Make:</b></td><td style='padding:10px;'>$make</td></tr>
                    <tr><td style='padding:10px;'><b>Model:</b></td><td style='padding:10px;'>$model</td></tr>
                    <tr><td style='padding:10px;'><b>Year:</b></td><td style='padding:10px;'>$year</td></tr>
                    <tr><td style='padding:10px;'><b>License Plate:</b></td><td style='padding:10px;'>$license_plate</td></tr>
                    <tr><td style='padding:10px;'><b>Garage Type:</b></td><td style='padding:10px;'>".ucfirst($garage_type)."</td></tr>
                </table>
            </div>
        </div>
    ";

    return sendMail($to, $subject, $body);
}
?>
