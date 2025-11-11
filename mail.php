<?php
// Enable error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mail->Username = getenv('MAIL_USER');
$mail->Password = getenv('MAIL_PASS');


require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

/**
 * Generic function to send emails — all emails are no-reply and automated
 */
function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // ✅ SMTP Configuration (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        $mail->Username   = 'owen.kamau@strathmore.edu';  // your Gmail
        $mail->Password   = 'ijkh ftdk goyg ogez';        // your app password

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // ✅ Sender Info (No-Reply enforced)
        $mail->setFrom('no-reply@cmts-system.com', 'Car Maintenance Tracker');
        $mail->addReplyTo('no-reply@cmts-system.com', 'Do Not Reply');
        $mail->addAddress($to);

        // ✅ Email format
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;

        // ✅ Add footer automatically to all outgoing system emails
        $body .= "
        <hr style='border: none; border-top: 1px dashed #aaa; margin: 25px 0;'>
        <p style='font-size: 0.85em; color: #666; font-family: Arial, sans-serif;'>
            ⚙️ This is an automated message from <b>Car Maintenance Tracker</b>. 
            <br>Please do not reply to this email.
        </p>
        ";

        $mail->Body = $body;
        $mail->AltBody = "This is an automated message from Car Maintenance Tracker. Please do not reply.";

        $mail->send();
        return "✅ Email successfully sent to $to";
    } catch (Exception $e) {
        return "❌ Email failed to send. Error: " . htmlspecialchars($mail->ErrorInfo);
    }
}

/**
 * 🔧 Service Reminder Template
 */
function sendServiceReminder($to, $ownerName, $carMake, $carModel, $serviceType, $nextServiceDate) {
    $subject = "⏰ Service Reminder: $serviceType for your $carMake $carModel";

    $body = "
    <div style='font-family: Georgia, serif; background: #f9f9f9; padding: 20px;'>
        <div style='max-width: 600px; margin:auto; background: #fff; border-radius: 10px; padding: 20px; border: 2px solid #ff4d00;'>
            <h2 style='color:#ff4d00;'>Hi $ownerName,</h2>
            <p>This is a friendly reminder from <b>Car Maintenance Tracker</b> 🚗</p>
            <p>Your <b>$carMake $carModel</b> is due for <b>$serviceType</b> on <b>$nextServiceDate</b>.</p>
            <p>Please schedule your service in time to keep your car running smoothly.</p>
        </div>
    </div>
    ";

    return sendMail($to, $subject, $body);
}

/**
 * 🚗 Car Registration Template
 */
function sendCarRegistration($to, $ownerName, $make, $model, $year, $license_plate, $garage_type) {
    $subject = "Your Car Registration is Complete! 🚗";

    $body = "
    <div style='font-family: Georgia, serif; background: #f9f9f9; padding: 20px;'>
        <div style='max-width: 600px; margin:auto; background: #fff; border-radius: 10px; padding: 20px; border: 2px solid #ff4d00;'>
            <h2 style='color:#ff4d00;'>Hi $ownerName!</h2>
            <p>Thank you for registering your car. Here are your vehicle details:</p>
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
