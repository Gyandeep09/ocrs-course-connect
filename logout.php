<?php
// OCRS/logout.php
require_once 'session_config.php';

// --- START: CORRECTED LOGIC ---

// 1. Set the redirect path to index.php for all users.
$redirect_page = 'index.php';

// 2. Prevent the browser from caching this page to avoid issues.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 3. Now, completely clear and destroy the session.
session_unset();
session_destroy();

// --- END: CORRECTED LOGIC ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logging Out...</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Fullscreen overlay */
.logout-transition {
    position: fixed; top: 0; left: 0;
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #fff; z-index: 9999;
    opacity: 1; visibility: visible;
    transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}
.logout-transition.fade-out { opacity: 0; visibility: hidden; }
/* Spinner */
.transition-spinner {
    width: 60px; height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.2);
    border-top: 4px solid #3b82f6; /* Changed color for better visibility */
    border-radius: 50%;
    animation: spin 1s linear infinite; margin-bottom: 20px;
}
@keyframes spin { to { transform: rotate(360deg); } }
/* Text */
.transition-text { font-size: 20px; font-weight: 600; margin-bottom: 8px; letter-spacing: 0.5px; }
.transition-subtext { font-size: 15px; opacity: 0.8; }
</style>
</head>
<body>

<div class="logout-transition" id="logoutTransition">
    <div class="transition-spinner"></div>
    <div class="transition-text"><i class="fas fa-sign-out-alt"></i> Logging you out</div>
    <div class="transition-subtext">Redirecting securely...</div>
</div>

<script>
window.addEventListener('load', () => {
    const logoutTransition = document.getElementById('logoutTransition');
    setTimeout(() => {
        logoutTransition.classList.add('fade-out');
        setTimeout(() => {
            // Use the PHP variable to redirect reliably
            window.location.href = "<?php echo $redirect_page; ?>";
        }, 800);
    }, 1500);
});
</script>

</body>
</html>