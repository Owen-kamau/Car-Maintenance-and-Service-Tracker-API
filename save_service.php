<?php
session_start();
include("DBConn.php");

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user info including phone number
$userStmt = $conn->prepare("SELECT email, phone_number FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userEmail = $user['email'] ?? 'user@example.com';
$userPhone = $user['phone_number'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carId = $_POST['car_id'] ?? '';
    $serviceType = trim($_POST['service_type'] ?? '');
    $serviceDate = $_POST['service_date'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? null;
    $mileage = $_POST['mileage'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    $nextServiceDate = $_POST['next_service_date'] ?? null;

    // ✅ Validate input
    if (empty($carId) || empty($serviceType) || empty($serviceDate)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }

    $today = date('Y-m-d');
    if ($serviceDate < $today) {
        echo "<script>alert('You cannot schedule a service for a past date.'); window.history.back();</script>";
        exit();
    }

    // ✅ Ensure car belongs to user
    $checkCar = $conn->prepare("SELECT id FROM cars WHERE id = ? AND user_id = ?");
    $checkCar->bind_param("ii", $carId, $userId);
    $checkCar->execute();
    $carResult = $checkCar->get_result();
    if ($carResult->num_rows === 0) {
        echo "<script>alert('Invalid car selection.'); window.history.back();</script>";
        exit();
    }

    // ✅ Prevent duplicate service on same date
    $dup = $conn->prepare("SELECT id FROM services WHERE car_id = ? AND service_date = ?");
    $dup->bind_param("is", $carId, $serviceDate);
    $dup->execute();
    $dupResult = $dup->get_result();
    if ($dupResult->num_rows > 0) {
        echo "<script>alert('A service for this car on that date already exists.'); window.history.back();</script>";
        exit();
    }

    // ✅ Queue check
    $queueCheck = $conn->query("SELECT COUNT(*) AS pending_count FROM services WHERE status = 'Pending'");
    $queueCount = (int)$queueCheck->fetch_assoc()['pending_count'];
    $maxQueue = 10;

    $status = 'Pending';
    $finalDate = $serviceDate;

    // ✅ Suggest auto-reschedule if queue full
    if ($queueCount > $maxQueue) {
        $newDate = date('Y-m-d', strtotime($serviceDate . ' +2 days'));
        echo "<script>
        if (confirm('Our queue is full. Reschedule to $newDate?')) {
            window.location.href='save_service.php?auto_reschedule=1&car_id=$carId&service_type=$serviceType&service_date=$newDate';
        } else {
            alert('Please choose a later date or try again later.');
            window.location.href='add_service.php';
        }
        </script>";
        exit();
    }

    // ✅ Insert service record with reminder interval
    $serviceTimestamp = strtotime($finalDate);
    $hoursUntil = ($serviceTimestamp - time()) / 3600;
    $reminderIntervalHours = ($hoursUntil <= 72) ? 24 : 72; // system auto

    $insert = $conn->prepare("
        INSERT INTO services 
        (car_id, user_id, service_type, service_date, status, name, description, price, mileage, notes, next_service_date, reminder_interval_hours, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $insert->bind_param("iisssssdiis", 
        $carId, $userId, $serviceType, $finalDate, $status,
        $name, $description, $price, $mileage, $notes, $nextServiceDate, $reminderIntervalHours
    );

    if ($insert->execute()) {
        // ✅ Email reminder (mock)
        $subject = "Vintage Garage | Service Scheduled";
        $message = "Hello,\n\nYour {$serviceType} service for car ID {$carId} is scheduled for {$finalDate}.\nWe'll remind you every {$reminderIntervalHours} hours before your appointment.\n\nThank you!";
        // mail($userEmail, $subject, $message, "From: no-reply@vintagegarage.com");

        // ✅ SMS reminder (mock)
        if ($userPhone) {
            // Replace with real SMS API integration
            // Example: sendSMS($userPhone, $message);
        }

        echo "<script>
            alert('Service scheduled successfully! Confirmation sent via email and SMS (mock).');
            window.location.href='my_services.php';
        </script>";
    } else {
        echo "<script>alert('Error scheduling service.'); window.history.back();</script>";
    }

    $insert->close();
    $conn->close();
}

// ✅ Auto-Reschedule flow
elseif (isset($_GET['auto_reschedule'])) {
    $carId = $_GET['car_id'] ?? '';
    $serviceType = $_GET['service_type'] ?? '';
    $serviceDate = $_GET['service_date'] ?? '';
    $userId = $_SESSION['user_id'];

    $insert = $conn->prepare("
        INSERT INTO services (car_id, user_id, service_type, service_date, status, reminder_interval_hours, created_at)
        VALUES (?, ?, ?, ?, 'Pending', 24, NOW())
    ");
    $insert->bind_param("iiss", $carId, $userId, $serviceType, $serviceDate);
    $insert->execute();

    echo "<script>
        alert('Your service has been rescheduled to $serviceDate and confirmed.');
        window.location.href='my_services.php';
    </script>";
}
?>
