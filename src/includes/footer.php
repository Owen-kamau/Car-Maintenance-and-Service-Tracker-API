<?php
if (!defined('CMTS_SECURE')) {
    die("Unauthorized access");
}
?>
<style>
    .footer {
        background: #203a43;
        padding: 25px;
        margin-top: 50px;
        text-align: center;
        color: #9bd2d6;
        font-size: 14px;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
</style>

<div class="footer">
    CMTS — Car Management & Tracking System<br>
    &copy; <?php echo date('Y'); ?> All Rights Reserved.
</div>

</body>
</html>
