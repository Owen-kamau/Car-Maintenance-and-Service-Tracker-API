<?php
include("DBConn.php");

// ====== CONFIGURATION ======
$maxQueue = 10; // threshold for heavy workload
$reminderWindow = 3; // days ahead (72 hours)

// ====== GET QUEUE SIZE ======
$queueResult = $conn->query("SELECT COUNT(*) AS pending_count FROM services WHERE status = 'Pending'");
$queueCount = $queueResult->fetch_assoc()['pending_count'] ?? 0;

// ====== GET UPCOMING SERVICES ======
$query = $conn->prepare("
    SELECT s.id, s.user_id, s.car_id, s.service_type, s.service_date, 
           u.email, c.model, c.license_plate
    FROM services s
    JOIN users u ON s.user_id = u.id
    JOIN cars c ON s.car_id = c.id
    WHERE s.status = 'Pending'
      AND s.service_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
");
$query->bind_param("i", $reminderWindow);
$query->execute();
$result = $query->get_result();

// ====== EXIT IF NONE FOUND ======
if ($result->num_rows === 0) {
    file_put_contents("cron_log.txt", date("Y-m-d H:i:s") . " - No reminders needed\n", FILE_APPEND);
    $conn->close();
    exit();
}

while ($row = $result->fetch_assoc()) {
    $email = $row['email'];
    $model = $row['model'];
    $plate = $row['license_plate'];
    $serviceType = $row['service_type'];
    $serviceDate = $row['service_date'];

    // ====== Base Message ======
    $subject = "Vintage Garage | Service Reminder";
    $message = "
        Hello,
        This is a reminder that your *{$serviceType}* for your *{$model} ({$plate})*
        is scheduled for *{$serviceDate}*.

        Please confirm if this schedule still works for you.

        If you wish to reschedule, log in to your Vintage Garage account or contact our team.

        Thank you for choosing Vintage Garage!
    ";

    // ====== Add Queue Advisory ======
    if ($queueCount > $maxQueue) {
        $message .= "\n\n⚠️ Note: Our service queue is currently very full ({$queueCount} pending services).
If your booking is not urgent, we recommend moving your appointment a few days forward for faster turnaround.
Otherwise, we’ll prioritize based on urgency and confirm by email.";
    }

    // ====== (Mock) Email Sending ======
    // mail($email, $subject, $message, "From: no-reply@vintagegarage.com");
    echo "Reminder prepared for {$email} ({$model} - {$plate}) on {$serviceDate}\n";

    // Optional: Update DB to mark reminder sent
    $update = $conn->prepare("UPDATE services SET last_reminder = NOW() WHERE id = ?");
    $update->bind_param("i", $row['id']);
    $update->execute();
    $update->close();
}

// ====== Logging ======
$logMessage = date("Y-m-d H:i:s") . " - {$result->num_rows} reminders processed. Queue: {$queueCount}\n";
file_put_contents("cron_log.txt", $logMessage, FILE_APPEND);

$conn->close();
?>
