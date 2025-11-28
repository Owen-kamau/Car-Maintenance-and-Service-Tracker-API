<?php
// Security constant for protected includes
if (!defined('CMTS_SECURE')) {
    define('CMTS_SECURE', true);
}
// about.php — CMTS (Updated Layout for Car Feature Showcase)
?>
<?php include_once __DIR__ . '/includes/header.php'; ?>

<style>
    body {
        background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
        color: #f5f5f5;
        font-family: 'Segoe UI', sans-serif;
        overflow-x: hidden;
    }
    .about-container {
        max-width: 1200px;
        margin: auto;
        padding: 60px 30px;
        display: flex;
        flex-direction: column;
        gap: 50px;
    }
    .title {
        text-align: center;
        font-size: 48px;
        font-weight: 900;
        color: #00eaff;
        text-shadow: 0 0 20px rgba(0,234,255,0.8);
        letter-spacing: 1px;
    }
    .subtext {
        text-align: center;
        font-size: 20px;
        color: #d3d3d3;
        margin-bottom: 40px;
    }
    .section-title {
        font-size: 28px;
        margin-top: 40px;
        color: #00c6ff;
        border-left: 6px solid #00c6ff;
        padding-left: 15px;
        position: relative;
    }
    .section-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -5px;
        width: 60px;
        height: 4px;
        background: #ff4d4d;
        border-radius: 2px;
    }
    .story-block {
        background: rgba(255,255,255,0.05);
        border-left: 5px solid #00eaff;
        padding: 25px 30px;
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        transition: transform 0.4s ease, box-shadow 0.4s ease;
    }
    .story-block:hover {
        transform: translateX(20px);
        box-shadow: 0 12px 30px rgba(0,255,255,0.6);
    }
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
    }
    .car-card {
        background: linear-gradient(145deg, #203a43, #2c5364);
        border-radius: 25px;
        text-align: center;
        padding: 30px;
        transition: transform 0.5s ease, box-shadow 0.5s ease;
        cursor: pointer;
    }
    .car-card:hover {
        transform: translateY(-10px) scale(1.05);
        box-shadow: 0 20px 40px rgba(0,255,255,0.5);
    }
    .car-card h3 { font-size: 24px; margin-bottom: 15px; color: #ffdd00; }
    .car-card p { font-size: 16px; color: #f5f5f5; }
    .stats-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }
    .stat-box {
        background: rgba(255,255,255,0.05);
        padding: 30px;
        flex: 1 1 200px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.5);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .stat-box:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,234,255,0.6);
    }
    .stat-number { font-size: 36px; font-weight: 900; color: #00eaff; }
    .footer-note { text-align: center; margin-top: 60px; font-size: 14px; color: #9bd2d6; }
    @media (max-width: 992px) {
        .about-container { padding: 40px 20px; }
        .title { font-size: 38px; }
        .section-title { font-size: 24px; }
    }
</style>

<div class="about-container">
    <h1 class="title">About CMTS</h1>
    <p class="subtext">Car Management & Tracking System </p>

    <h2 class="section-title">Our Brand Story</h2>
    <div class="story-block" data-aos="fade-right">
        <p>CMTS was built from a powerful realization: <strong>car owners and garages clash with transparency and accountability</strong>. <br><br>Our mission is <strong>Smart, secure vehicle management, always ahead.<strong></p>
    </div>

    <h2 class="section-title">Who We Serve</h2>
    <div class="story-block" data-aos="fade-left">
        <ul>
            <li>Car owners seeking transparency and control</li>
            <li>Garages wanting efficiency and professionalism</li>
            <li>Fleet managers requiring accurate oversight</li>
            <li>Mechanics needing streamlined workflows</li>
        </ul>
    </div>

    <h2 class="section-title">Creative Car Feature Showcase</h2>
    <div class="feature-grid" data-aos="fade-up">
        <div class="car-card">
            <h3> Smart Service History</h3>
            <p>Track every repair, oil change, and maintenance automatically with precision and clarity.</p>
        </div>
        <div class="car-card">
            <h3> Secure Car ID Linking</h3>
            <p>Every car has a unique encrypted identifier to ensure complete system security and accuracy.</p>
        </div>
        <div class="car-card">
            <h3> Real-time Optimization</h3>
            <p>System designed for maximum speed, zero downtime, and full reliability under load.</p>
        </div>
        <div class="car-card">
            <h3> Cloud Integration</h3>
            <p>Access your vehicle data securely from anywhere, anytime, across multiple devices.</p>
        </div>
    </div>

    <h2 class="section-title">Ceramic Coating Services</h2>
    <div class="story-block" data-aos="fade-right">
        <p>Enhance your vehicle's protection with our premium ceramic coating services. Our advanced ceramic coatings provide long-lasting shine, superior hydrophobicity, and protection against UV rays, scratches, and environmental contaminants. Ideal for car enthusiasts looking to maintain their vehicle's pristine condition.</p>
    </div>

    <h2 class="section-title">Our Business Model</h2>
    <div class="story-block" data-aos="fade-right">
        <p>CMTS offers a hybrid-access platform: garages get premium features while individual car owners enjoy detailed free access to their service history. Transparency and confidence drive our model.</p>
    </div>

    <p class="footer-note">CMTS — Built for the Maximum</p>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>AOS.init({ duration: 1200, once: true });</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<?php include_once __DIR__ . '/includes/footer.php'; ?>