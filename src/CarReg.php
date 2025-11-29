<?php
session_start();
include("DBConn.php");
include("mail.php");

// Only owners can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

// CSRF / one-time form token
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Session vars
$userEmail = $_SESSION['email'] ?? '';
$userName  = $_SESSION['username'] ?? 'Owner';
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF protection
    if (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        die("Duplicate submission detected. Please go back.");
    }
    unset($_SESSION['form_token']); // destroy token

    $user_id = $_SESSION['user_id'];
    $make = trim($_POST['make']);
    $model = trim($_POST['model']);
    $year = intval($_POST['year']);
    $license_plate = trim($_POST['license_plate']);
    $garage_type = $_POST['garage_type'];
    $car_image = null;

    // Allowed garage types
    $allowed_garage_types = ['vehicle','truck','tractor'];
    if (!in_array($garage_type, $allowed_garage_types)) {
        $error = "❌ Invalid vehicle type selected.";
    }

    // Prevent duplicate license plates
    if (empty($error)) {
        $check = $conn->prepare("SELECT id FROM cars WHERE license_plate = ?");
        $check->bind_param("s", $license_plate);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            $error = "❌ This license plate is already registered.";
        }
    }

    // Handle image upload
    if (empty($error) && !empty($_FILES['car_image']['name'])) {
        $target_dir = __DIR__ . "/secure_uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);

        $fileTmpPath = $_FILES["car_image"]["tmp_name"];
        $fileSize = $_FILES["car_image"]["size"];
        $allowed_exts = ["jpg","jpeg","png"];
        $allowed_mimes = ["image/jpeg","image/png"];
        $originalName = $_FILES["car_image"]["name"];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Validate extension
        if (!in_array($ext, $allowed_exts)) {
            $error = "Invalid image extension. Only JPEG or PNG allowed.";
        }
        // Validate MIME
        elseif (!in_array(mime_content_type($fileTmpPath), $allowed_mimes)) {
            $error = "Invalid file type. Please upload a real image.";
        }
        // Validate size (max 2MB)
        elseif ($fileSize > 2 * 1024 * 1024) {
            $error = "File too large. Max 2MB allowed.";
        }
        else {
            // Resize image to max 1200x800 while maintaining aspect ratio
            $maxWidth = 1200;
            $maxHeight = 800;

            $imgInfo = getimagesize($fileTmpPath);
            if ($imgInfo === false) {
                $error = "Uploaded file is not a valid image.";
            } else {
                list($width, $height) = $imgInfo;
                $ratio = min($maxWidth/$width, $maxHeight/$height, 1); // do not upscale
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                if ($imgInfo[2] == IMAGETYPE_JPEG) {
                    $src_image = imagecreatefromjpeg($fileTmpPath);
                } elseif ($imgInfo[2] == IMAGETYPE_PNG) {
                    $src_image = imagecreatefrompng($fileTmpPath);
                } else {
                    $error = "Unsupported image type.";
                }

                if (empty($error) && $src_image) {
                    $dst_image = imagecreatetruecolor($newWidth, $newHeight);

                    // Preserve transparency for PNG
                    if ($imgInfo[2] == IMAGETYPE_PNG) {
                        imagealphablending($dst_image, false);
                        imagesavealpha($dst_image, true);
                    }

                    imagecopyresampled($dst_image, $src_image, 0,0,0,0, $newWidth, $newHeight, $width, $height);

                    $newName = bin2hex(random_bytes(8)) . "." . $ext;
                    $target_file = $target_dir . $newName;

                    if ($imgInfo[2] == IMAGETYPE_JPEG) {
                        imagejpeg($dst_image, $target_file, 90);
                    } else {
                        imagepng($dst_image, $target_file, 6);
                    }

                    ($src_image);
                    ($dst_image);

                    $car_image = $newName; // save only filename
                }
            }
        }
    }

    // Insert into DB
    if (empty($error)) {
        $sql = "INSERT INTO cars (user_id, make, model, year, license_plate, garage_type, car_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ississs", $user_id, $make, $model, $year, $license_plate, $garage_type, $car_image);

        if ($stmt->execute()) {
            // Send email
            $subject = "Your Vehicle Registration is Complete! 🚗";
            $body = "
                <div style='font-family: Georgia, serif; background: #f9f9f9; padding: 20px;'>
                    <div style='max-width: 600px; margin:auto; background: #fff; border-radius: 10px; padding: 20px; border: 2px solid #ff4d00;'>
                        <h2 style='color:#ff4d00;'>Hi $userName!</h2>
                        <p>Your vehicle registration is complete. Details:</p>
                        <table style='width:100%; border-collapse: collapse;'>
                            <tr><td><b>Make:</b></td><td>$make</td></tr>
                            <tr><td><b>Model:</b></td><td>$model</td></tr>
                            <tr><td><b>Year:</b></td><td>$year</td></tr>
                            <tr><td><b>License Plate:</b></td><td>$license_plate</td></tr>
                            <tr><td><b>Type:</b></td><td>".ucfirst($garage_type)."</td></tr>
                        </table>
                        <p style='margin-top:20px; font-size:0.9em; color:#555;'>⚙ This is an automated message. Do not reply.</p>
                    </div>
                </div>";

            sendMail($userEmail, $subject, $body);

            // Success → redirect to dashboard
            echo "<script>
                alert('✔ Vehicle Registered successfully!');
                window.location.href='owner_dash.php?status=registered';
            </script>";
            exit;
        } else {
            $error = "❌ Database Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register Vehicle | My Garage</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
body { 
    background: #111; 
    color: #f0f0f0; 
    font-family:'Poppins', sans-serif; 
}
.card { 
    max-width: 500px; 
    margin:50px auto; 
    background:#1e1e1e; 
    border:2px solid #ff4d00; 
    border-radius:15px; 
    padding:30px;
}
h2 { 
    color:#ff4d00; 
    text-align:center; 
    margin-bottom:20px; 
}
.form-control { 
    background:#333; 
    color:#fff; 
    border:none; 
    border-radius:8px;
    margin-bottom:15px; 
}
.form-control:focus { 
    outline:2px solid #ff4d00; 
}
#car-preview { 
    display:none; 
    max-width:100%; 
    margin-top:10px; 
    border-radius:10px; 
}
button { 
    background:#ff4d00; 
    color:#fff; 
    border:none; 
    border-radius:8px; 
    padding:10px 20px; 
    font-weight:bold; 
    width:100%; }
button:hover {
    background:#ff6600; 
    transform:scale(1.05); 
    transition:0.3s; 
}
.toast-container { 
    position: fixed; 
    top:20px; 
    right:20px; 
    z-index:10000; 
}
.toast { 
    border-radius:10px; 
    padding:15px 20px; 
    color:#fff; 
    margin-bottom:10px; 
}
.toast-success { 
    background:#28a745; 
}
.toast-error { 
    background:#dc3545; 
}
.loader-overlay { 
    position:fixed; 
    top:0; left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.8);
    display:none;
    align-items:center;
    justify-content:center; 
    z-index:9999; 
}
.gear { 
    width:80px;
    height:80px;
    border:10px solid #ff4d00;
    border-top:10px solid #2a2a2a;
    border-radius:50%;
    animation:spin 1.8s linear infinite; 
}
@keyframes spin { 
    0%{transform:rotate(0deg);}
    100%{transform:rotate(360deg);} 
    }
</style>
</head>
<body>
<div class="card">
    <h2>Register Your Vehicle</h2>
    <form id="regForm" method="post" enctype="multipart/form-data" onsubmit="disableSubmitAndShowLoader(event)">
        <input class="form-control" type="text" name="make" placeholder="Make" required>
        <input class="form-control" type="text" name="model" placeholder="Model" required>
        <input class="form-control" type="number" name="year" placeholder="Year" min="1918" max="2100" required>
        <input class="form-control" type="text" name="license_plate" placeholder="License Plate" required>

        <select class="form-control" name="garage_type" required>
            <option value="vehicle">Car</option>
            <option value="truck">Truck</option>
            <option value="tractor">Tractor</option>
        </select>

        <input class="form-control" type="file" name="car_image" accept="image/*" onchange="previewImage(event)">
        <img id="car-preview" alt="Vehicle Preview" style="max-width:100%; margin-top:10px; display:none; border:1px solid #ccc; border-radius:5px;">

        <input type="hidden" name="form_token" value="<?php echo isset($_SESSION['form_token']) ? htmlspecialchars($_SESSION['form_token']) : ''; ?>">
        <button id="regBtn" type="submit">Register Vehicle</button>
    </form>
    <p class="mt-2 text-center">
        <a href="#" onclick="window.location.href='<?php echo $_SESSION['role']; ?>_dash.php'; return false;">⬅ Back to Dashboard</a>
    </p>
</div>

<div class="toast-container">
    <?php if($success) echo "<div class='toast toast-success'>$success</div>"; ?>
    <?php if($error) echo "<div class='toast toast-error'>$error</div>"; ?>
</div>

<div class="loader-overlay" id="gear-loader">
    <div class="text-center">
        <div class="gear"></div>
        <p class="mt-3" style="color:#ffb366;font-family:Georgia, serif;">Revving up your dashboard...</p>
    </div>
</div>

<script>
function previewImage(event) {
    const preview = document.getElementById('car-preview');
    const file = event.target.files[0];
    if (!file) {
        preview.style.display = 'none';
        return;
    }

    // Use FileReader to preview locally
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    }
    reader.readAsDataURL(file);
}

function disableSubmitAndShowLoader(event) {
    const btn = document.getElementById('regBtn');
    btn.disabled = true;
    document.getElementById('gear-loader').style.display = 'flex';
}
</script>

</body>
</html>