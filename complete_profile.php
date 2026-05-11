<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complete Your Profile - Course Connect</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: 'Segoe UI', system-ui, sans-serif;
        background: #f0f4f8;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #1e293b;
        overflow: hidden; /* Hide overflowing animations */
    }

    /* Loading Overlay Styles */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(240, 244, 248, 0.95);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 1;
        transition: all 0.5s ease-out;
    }
    
    .loading-overlay.fade-out {
        opacity: 0;
        visibility: hidden;
    }
    
    .loading-content {
        text-align: center;
        color: #1e293b;
    }
    
    .loading-spinner {
        width: 60px;
        height: 60px;
        margin: 0 auto 25px;
        border: 4px solid rgba(16, 185, 129, 0.2);
        border-left: 4px solid #10b981;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .loading-text {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #10b981, #3b82f6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: pulse-text 2s ease-in-out infinite;
    }
    
    .loading-subtext {
        font-size: 14px;
        color: #64748b;
        opacity: 0.8;
    }
    
    @keyframes pulse-text {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .loading-dots {
        display: inline-block;
        position: relative;
        width: 40px;
        height: 20px;
        margin-left: 5px;
    }
    
    .loading-dots span {
        position: absolute;
        top: 50%;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        animation: loading-dots 1.4s infinite ease-in-out both;
    }
    
    .loading-dots span:nth-child(1) { left: 0; animation-delay: -0.32s; }
    .loading-dots span:nth-child(2) { left: 12px; animation-delay: -0.16s; }
    .loading-dots span:nth-child(3) { left: 24px; animation-delay: 0s; }
    
    @keyframes loading-dots {
        0%, 80%, 100% { transform: translateY(-50%) scale(0.8); opacity: 0.5; }
        40% { transform: translateY(-50%) scale(1); opacity: 1; }
    }

    .main-container {
        display: flex;
        width: 100%;
        max-width: 950px;
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
        position: relative;
        z-index: 10;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s ease-out;
    }
    
    .main-container.show {
        opacity: 1;
        transform: translateY(0);
    }
    
    .welcome-section {
        flex: 1;
        background: linear-gradient(160deg, #10b981, #059669);
        color: #ffffff;
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        border-radius: 24px 0 0 24px;
    }
    .welcome-header h1 {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .welcome-header p {
        font-size: 16px;
        opacity: 0.9;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    .feature-list { list-style: none; }
    .feature-list li {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        font-size: 15px;
        animation: fadeInUp 0.5s ease-out backwards;
    }
    .feature-list li:nth-child(2) { animation-delay: 0.1s; }
    .feature-list li:nth-child(3) { animation-delay: 0.2s; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .feature-list li i {
        margin-right: 12px;
        width: 20px;
        animation: bob 3s ease-in-out infinite;
    }
    @keyframes bob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    .form-section {
        flex: 1.2;
        padding: 50px 40px;
    }
    .form-title h3 {
        font-size: 26px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
        position: relative;
        display: inline-block;
        background: linear-gradient(135deg, #10b981, #3b82f6);
        -webkit-background-clip: text;
        color: transparent;
        animation: shine 5s linear infinite;
        background-size: 200% 100%;
    }
    @keyframes shine {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .form-title p { color: #64748b; font-size: 15px; margin-bottom: 30px; }
    .form-group { margin-bottom: 22px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
    .form-group input, .form-group textarea {
        width: 100%; padding: 14px 16px; border: 1px solid #e2e8f0;
        border-radius: 10px; outline: none; font-size: 15px;
        background: #f8fafc; transition: all 0.3s ease;
    }
    .form-group input:focus, .form-group textarea:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    .file-input-label {
        display: flex; align-items: center; justify-content: center; padding: 14px;
        background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 10px; cursor: pointer;
    }
    .submit-button {
        width: 100%; padding: 16px; border: none; border-radius: 10px;
        background: linear-gradient(135deg, #10b981, #059669); color: #ffffff;
        font-size: 16px; font-weight: 600; cursor: pointer;
    }
    .error-message {
        background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
        padding: 14px; border-radius: 10px; margin-bottom: 20px;
    }
    
    /* Paper Airplane Animation */
    .paper-airplane {
        position: absolute;
        font-size: 20px;
        color: #3b82f6;
        opacity: 0.6;
        z-index: 1;
        animation: fly 15s linear infinite;
    }
    .paper-airplane:nth-child(1) { top: 20%; left: -5%; animation-duration: 12s; animation-delay: 0s; }
    .paper-airplane:nth-child(2) { top: 80%; left: -5%; animation-duration: 15s; animation-delay: 5s; }
    .paper-airplane:nth-child(3) { top: 50%; left: -5%; animation-duration: 10s; animation-delay: 8s; }
    @keyframes fly {
        0% { transform: translateX(0) translateY(0) rotate(20deg); opacity: 0.6; }
        20% { transform: translateX(25vw) translateY(-10vh) rotate(10deg); }
        40% { transform: translateX(50vw) translateY(5vh) rotate(25deg); }
        60% { transform: translateX(75vw) translateY(-5vh) rotate(15deg); }
        100% { transform: translateX(110vw) translateY(10vh) rotate(30deg); opacity: 0; }
    }
    /* --- ADD THIS FOR DYNAMIC EFFECTS --- */
    .form-group input:hover, .form-group textarea:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .file-input-label:hover {
        border-color: #10b981;
        background: #f0fdf4;
    }
    .submit-button {
        transition: all 0.3s ease;
    }
    .submit-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -8px rgba(16, 185, 129, 0.5);
    }
    .error-message {
        animation: shake 0.5s ease-in-out;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    /* --- END OF NEW DYNAMIC EFFECTS --- */
</style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <div class="loading-text">
            Setting up your profile
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <div class="loading-subtext">Just a moment while we prepare everything for you</div>
    </div>
</div>

<div class="paper-airplane"><i class="fas fa-paper-plane"></i></div>
<div class="paper-airplane"><i class="fas fa-paper-plane"></i></div>
<div class="paper-airplane"><i class="fas fa-paper-plane"></i></div>

<div class="main-container" id="mainContainer">
    <div class="welcome-section">
        <div class="welcome-header">
            <h1><i class="fas fa-rocket"></i>One Last Step!</h1>
            <p>Welcome to Course Connect! Complete your profile to unlock a personalized learning journey tailored just for you.</p>
        </div>
        <ul class="feature-list">
            <li><i class="fas fa-check-circle"></i>Access personalized courses</li>
            <li><i class="fas fa-check-circle"></i>Track your learning progress</li>
            <li><i class="fas fa-check-circle"></i>Connect with instructors</li>
        </ul>
    </div>
    <div class="form-section">
        <div class="form-title">
            <h3>Complete Your Profile</h3>
            <p>This information helps us tailor your experience.</p>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <form action="profile.php?first_time=1" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="e.g., +91 1234567890" required>
            </div>
            <div class="form-group">
                <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                <textarea id="address" name="address" rows="2" placeholder="Your current address" required></textarea>
            </div>
            <div class="form-group">
                <label for="age"><i class="fas fa-birthday-cake"></i> Age</label>
                <input type="number" id="age" name="age" min="15" max="100" placeholder="e.g., 21" required>
            </div>
           <div class="form-group">
                <label for="profile_picture"><i class="fas fa-camera"></i> Profile Picture (Optional)</label>
                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" style="display:none;">
                <label for="profile_picture" class="file-input-label">
                    <span id="file-name-span"><i class="fas fa-upload"></i> Click to choose a file</span>
                </label>
            </div>
            ```
            <button type="submit" class="submit-button">
                <i class="fas fa-lock-open"></i> Unlock My Dashboard
            </button>
        </form>
    </div>
</div>

<script>
    // Loading animation logic
    document.addEventListener('DOMContentLoaded', function() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        const mainContainer = document.getElementById('mainContainer');
        
        // Show loading for 1.8 seconds, then fade out and show main content
        setTimeout(() => {
            loadingOverlay.classList.add('fade-out');
            mainContainer.classList.add('show');
        }, 1800);
        
        // Remove loading overlay from DOM after fade completes
        setTimeout(() => {
            loadingOverlay.style.display = 'none';
        }, 2300);
    });

  document.getElementById('profile_picture').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        const labelSpan = document.getElementById('file-name-span');
        if (fileName) {
            // Use innerHTML to render the icon
            labelSpan.innerHTML = `<i class="fas fa-check-circle" style="color: #10b981;"></i> ${fileName}`;
        } else {
            labelSpan.innerHTML = '<i class="fas fa-upload"></i> Click to choose a file';
        }
    });
</script>

</body>
</html>