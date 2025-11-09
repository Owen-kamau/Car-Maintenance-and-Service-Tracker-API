<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CarMaintenance</title>

<style>
@import url('https://fonts.googleapis.com/css2?family=Edu+SA+Hand:wght@500&display=swap');

body {
    font-family: 'Edu SA Hand', cursive;
    background: linear-gradient(135deg, #fff0f5, #ffe6f0, #ffd6e8);
    margin: 0;
    padding: 0;
    display: flex;
    height: 100vh;
}

/* Sidebar Section */
.sidebar {
    width: 35%;
    background: #fffafa;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 30px;
    box-shadow: 3px 0 10px rgba(255, 182, 193, 0.3);
    text-align: center;
        z-index: 999;
    position:relative;
}

.sidebar h1 {
    color: #d63384;
    font-size: 2.2em;
    margin-bottom: 10px;
}

.sidebar p {
    color: #5e3a50;
    font-size: 1.1em;
    margin-bottom: 25px;
}

.nav-links {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.nav-links a {
    text-decoration: none;
    color: white;
    background: linear-gradient(90deg, #ff9eb8, #ffb6c1, #ffcce0);
    padding: 12px 30px;
    border-radius: 25px;
    font-size: 1.1em;
    font-weight: bold;
    transition: 0.3s;
}

.nav-links a:hover {
    background: linear-gradient(90deg, #ff7ca3, #ff94b6, #ffb6c1);
    transform: translateY(-2px);
}

/* Slideshow Section */
 
.slideshow-container {
    width: 65%;
    position: absolute;
    right: 0;
    top: 0;
    height: 100vh;
    overflow: hidden;
    pointer-events: none; /* allow sidebar buttons to be clickable */
    z-index: 1;
}


.mySlides {
    display: none;
    height: 100vh;
    width: 100%;
}

.mySlides img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Fading animation */
.fade {
    animation-name: fade;
    animation-duration: 2s;
}
@keyframes fade {
    from {opacity: .4}
    to {opacity: 1}
}

/* Responsive */
@media (max-width: 900px) {
    body {
        flex-direction: column;
    }
    .sidebar, .slideshow-container {
        width: 100%;
        height: auto;
    }
    .mySlides {
        height: 250px;
    }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h1>🚗 CarMaintenance</h1>
    <p>Keep your car running smoothly with style — track, manage, and maintain effortlessly.</p>
    <div class="nav-links">
        <a href="index.php">🏠 Home</a>
        <a href="login.php">🔑 Login</a>
        <a href="signup.php">📝 Sign Up</a>
    </div>
</div>

<!-- Slideshow -->
<div class="slideshow-container">
    <div class="mySlides fade">
        <img src="https://images.unsplash.com/photo-1541447271487-0963b1e4c6d0?auto=format&fit=crop&w=1500&q=80" alt="Car 1">
    </div>
    <div class="mySlides fade">
        <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=1500&q=80" alt="Car 2">
    </div>
    <div class="mySlides fade">
        <img src="https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?auto=format&fit=crop&w=1500&q=80" alt="Car 3">
    </div>
</div>

<script>
// Simple working slideshow
let slideIndex = 0;
showSlides();

function showSlides() {
    let slides = document.getElementsByClassName("mySlides");
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";  
    }
    slideIndex++;
    if (slideIndex > slides.length) {slideIndex = 1}    
    slides[slideIndex-1].style.display = "block";  
    setTimeout(showSlides, 3500); // Change every 3.5 seconds
}
</script>

</body>
</html>
