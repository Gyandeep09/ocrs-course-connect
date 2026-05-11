<?php
require_once 'session_config.php';

// Protect this page
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_page.php');
    exit;
}
require_once 'db_connect.php'; 

// --- Fetch All System Settings ---
$settings = [];
$settings_result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// --- MOVED: Handle Start New Session Action ---
// This logic is moved from admin_dashboard.php and now checks for POST
if (isset($_POST['action']) && $_POST['action'] === 'start_new_session') {
    $session_year = isset($_POST['session']) ? $_POST['session'] : date('Y');

    // Archive all APPROVED courses
    $archive_stmt = $conn->prepare("
        INSERT INTO enrollment_history (student_id, course_id, grade, session_year)
        SELECT student_id, course_id, grade, ?
        FROM enrollments
        WHERE status = 'Approved'
    ");
    $archive_stmt->bind_param("s", $session_year);
    $archive_stmt->execute();
    $archive_stmt->close();

    // Now, clear the tables for the new session
    $conn->query("TRUNCATE TABLE enrollments");
    $conn->query("UPDATE students SET course = NULL, department = NULL, semester = NULL");

    $_SESSION['success'] = "The " . htmlspecialchars($session_year) . " session has started! Student histories have been updated.";
    header('Location: system_settings.php'); // Redirect back to this page
    exit;
}

// --- Fetch pending count for navbar ---
$pending_count = 0;
$pending_result = $conn->query("SELECT COUNT(*) AS cnt FROM enrollments WHERE status='Pending'");
if ($pending_result && $pending_row = $pending_result->fetch_assoc()) {
    $pending_count = (int)$pending_row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - System Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* Copied all styles from admin_dashboard.php for 100% consistency */
    :root {
        --bg-color: #f4f7fa; 
        --navbar-bg: rgba(255, 255, 255, 0.85); 
        --widget-bg: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --accent-blue: #3b82f6;
        --accent-green: #10b981;
        --accent-red: #ef4444;
        --accent-yellow: #f59e0b;
        --active-line: #088178; 
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    body {
        font-family: "Spartan", sans-serif;
        background: var(--bg-color);
        color: var(--text-primary);
        margin: 0;
        padding-top: 70px; /* Space for fixed navbar */
    }

    /* Navbar Styles (from department.php for consistency) */
    .navbar {
        background: var(--navbar-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 0.5rem 0;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 20px rgba(0,0,0,0.07);
        border-bottom: 1px solid var(--border-color);
        transition: top 0.3s ease-out;
    }
    .nav-container, .navbar-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 2rem;
        height: 60px;
        display: grid;
        grid-template-columns: 1fr auto 1fr; /* 3-column layout */
        align-items: center;
    }
    .nav-brand, .navbar-brand {
        grid-column: 1 / 2;
        justify-self: start;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--text-primary);
        font-size: 22px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .nav-brand i, .navbar-brand i { color: var(--accent-blue); font-size: 26px; }
    .hamburger {
        grid-column: 3 / 4;
        justify-self: end;
        display: none; 
        flex-direction: column;
        cursor: pointer;
        padding: 10px;
        z-index: 1001;
    }
    .hamburger span {
        width: 25px; height: 3px;
        background: var(--text-primary);
        margin: 3px 0; transition: 0.3s;
    }
    .nav-menu, .navbar-nav {
        grid-column: 2 / 3;
        justify-self: center;
        display: flex; 
        align-items: center;
        list-style: none;
        gap: 0.5rem;
        transition: all 0.4s ease-out;
        margin: 0; 
    }
    .nav-item { position: relative; }
    .nav-link {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 16px;
        text-decoration: none;
        color: var(--text-secondary);
        font-weight: 600; 
        transition: all 0.3s;
        border-radius: 8px;
        position: relative;
        background: transparent; 
        white-space: nowrap;
    }
    .nav-link:hover { color: var(--active-line); background: transparent; }
    .nav-link.active { color: var(--active-line); background: transparent; }
    .nav-link.active::after,
    .nav-link:hover::after {
        content: "";
        width: 40%; height: 2px;
        background: var(--active-line);
        position: absolute;
        bottom: 0; left: 50%;
        transform: translateX(-50%);
        transition: width 0.3s ease;
    }
    .nav-link i { font-size: 16px; }
    .pending-badge {
        position: absolute;
        top: 2px;
        right: 5px;
        background: #ef4444;
        color: #ffffff;
        font-size: 11px;
        font-weight: 600;
        height: 18px;
        width: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--navbar-bg); 
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    @media (max-width: 1050px) { 
        .nav-container, .navbar-container {
            display: flex;
            justify-content: space-between;
        }
        .nav-menu, .navbar-nav { 
            display: none; 
            grid-column: auto; 
            justify-self: auto;
        }
        .hamburger { 
            display: flex; 
            grid-column: auto;
            justify-self: auto;
        }
        .nav-container.active .navbar-nav,
        .nav-container.active .nav-menu {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: absolute;
            top: 70px;
            left: 0;
            width: 100%;
            background: var(--navbar-bg);
            box-shadow: var(--shadow-md);
            padding: 1rem;
            animation: slideDown 0.3s ease-out forwards;
        }
        .nav-container.active .navbar-nav .nav-link,
        .nav-container.active .nav-menu .nav-link {
            width: 100%;
            padding: 12px;
        }
        .nav-container.active .navbar-nav .nav-link.active::after,
        .nav-container.active .navbar-nav .nav-link:hover::after,
        .nav-container.active .nav-menu .nav-link.active::after,
        .nav-container.active .nav-menu .nav-link:hover::after {
            width: 30px; 
            left: 16px; 
            transform: translateX(0); 
            bottom: 4px;
        }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    }
    
    /* Main Content Styles */
    .main-content {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeInUp 0.6s ease-out;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid var(--border-color);
    }
    .page-title { display: flex; align-items: center; }
    .page-title h1 {
        color: var(--text-primary);
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .page-title i {
        font-size: 32px;
        color: var(--accent-blue);
        margin-right: 16px;
    }
    .message {
        padding: 16px 20px; border-radius: 12px;
        font-size: 15px; font-weight: 500;
        border: 1px solid transparent;
        margin-bottom: 24px;
        animation: slideIn 0.4s ease-out;
    }
    .message.success {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        color: #065f46;
        border-color: rgba(34, 197, 94, 0.2);
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    /* Buttons */
    .action-button {
        background: var(--accent-blue);
        color: #ffffff;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .action-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
    }
    .action-button.danger {
        background: linear-gradient(135deg, var(--accent-red), #b91c1c);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    .action-button.danger:hover {
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
    }

    /* Widget/Form Styles */
    .widget-card {
        background: var(--widget-bg);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        margin-bottom: 2rem;
    }
    .widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .widget-header h3 {
        color: var(--text-primary);
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .widget-header h3 i {
        color: var(--accent-blue);
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        align-items: end;
    }
    .form-group { display: flex; flex-direction: column; }
    .form-group label {
        color: #374151;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
    }
    .form-group input,
    .form-group textarea,
    .form-group select {
        padding: 14px 16px;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        outline: none;
        font-size: 15px;
        font-family: "Spartan", sans-serif;
        background: #f8fafc;
        color: var(--text-primary);
        transition: all 0.3s ease;
    }
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        background: #fff;
    }

    /* Dashboard Grid Layout */
    .dashboard-grid-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        grid-template-rows: auto auto;
        gap: 1.5rem;
    }
    .dashboard-grid-layout .widget-card { margin-bottom: 0; }
    
    /* System Settings Toggles */
    .system-settings { grid-column: 1 / 2; grid-row: 1 / 2; }
    .setting-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 1rem 0; border-bottom: 1px solid var(--border-color);
    }
    .setting-item:last-child { border-bottom: none; }
    .setting-item p { margin: 0; font-weight: 500; }
    .setting-item span { font-size: 13px; color: var(--text-secondary); }
    .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc; transition: .4s; border-radius: 24px;
    }
    .slider:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background-color: white;
        transition: .4s; border-radius: 50%;
    }
    input:checked + .slider { background-color: var(--accent-green); }
    input:checked + .slider:before { transform: translateX(20px); }
    
    /* General Settings */
    .general-settings { grid-column: 1 / 2; grid-row: 2 / 3; }
    
    /* Academic Session */
    .academic-session { grid-column: 2 / 3; grid-row: 1 / 3; }
    .academic-session p {
        font-size: 15px;
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }
    .academic-session strong {
        color: var(--accent-red);
        font-weight: 700;
    }

    #new-session-form .form-group {
        margin-bottom: 1.5rem;
    }

    /* Confirmation Modal */
    .confirm-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
        z-index: 2000; display: flex; align-items: center; justify-content: center;
        opacity: 0; visibility: hidden; transition: all 0.3s ease;
    }
    .confirm-modal-overlay.visible { opacity: 1; visibility: visible; }
    .confirm-modal {
        background: #ffffff; padding: 32px; border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        width: 90%; max-width: 480px; text-align: center;
        transform: scale(0.95); transition: all 0.3s ease;
    }
    .confirm-modal-overlay.visible .confirm-modal { transform: scale(1); }
    .confirm-modal h2 {
        font-size: 22px; color: #b91c1c; margin-bottom: 12px;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .confirm-modal p {
        font-size: 16px; color: #475569; line-height: 1.6; margin-bottom: 24px;
    }
    .confirm-modal-actions { display: flex; justify-content: center; gap: 12px; }
    .confirm-btn {
        border: none; padding: 12px 24px; border-radius: 8px;
        font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
    }
    .btn-cancel { background: #e2e8f0; color: #475569; }
    .btn-cancel:hover { background: #cbd5e1; }
    .btn-confirm-final { background: #b91c1c; color: white; }
    .btn-confirm-final:hover { background: #991b1b; transform: translateY(-2px); }

    /* Responsive */
    @media (max-width: 1200px) {
        .dashboard-grid-layout {
            grid-template-columns: 1fr;
        }
        .system-settings, .general-settings, .academic-session {
            grid-column: 1 / -1;
            grid-row: auto;
        }
    }
    @media (max-width: 768px) {
        .nav-container, .navbar-container { padding: 0 1rem; }
        .main-content { padding: 1.5rem 1rem; }
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        .page-title h1 { font-size: 24px; }
        .widget-card { padding: 1.5rem; }
        .form-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }

</style>
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
    <div id="popup-message" class="message success">
        <i class="fas fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
    <?php endif; ?>

    <nav class="navbar" id="navbar">
        <div class="nav-container" id="nav-container">
            <a href="admin_dashboard.php" class="nav-brand">
                <i class="fas fa-graduation-cap"></i>
                <span>Course Connect</span>
            </a>
            
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <ul class="navbar-nav" id="navbar-nav">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="department.php" class="nav-link">
                        <i class="fas fa-building"></i>
                        <span>Departments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="course_management.php" class="nav-link">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="student_management.php" class="nav-link">
                        <i class="fas fa-user-graduate"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="enrollments.php" class="nav-link">
                        <i class="fas fa-user-check"></i>
                        <span>Enrollments</span>
                        <?php if ($pending_count > 0): ?>
                            <span class="pending-badge"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-cog"></i>
                <h1>System Settings</h1>
            </div>
        </div>

        <div class="dashboard-grid-layout">

            <div class="system-settings widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-toggle-on"></i> System Behavior</h3>
                </div>
                <div class="setting-item">
                    <div>
                        <p>Open Registrations</p>
                        <span>Allow new students to create accounts</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="reg-toggle" 
                               <?php echo ($settings['open_registrations'] == '1') ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-item">
                    <div>
                        <p>Maintenance Mode</p>
                        <span>Temporarily disable student login</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="maint-toggle"
                               <?php echo ($settings['maintenance_mode'] == '1') ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="setting-item">
                    <div>
                        <p>Enable Email Alerts</p>
                        <span>Send notifications for new pending requests</span>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="email-toggle"
                               <?php echo ($settings['email_alerts'] == '1') ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="general-settings widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-info-circle"></i> General Information</h3>
                </div>
                <form id="general-settings-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Institution Name</label>
                            <input type="text" name="institution_name" value="<?php echo htmlspecialchars($settings['institution_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>System Name</label>
                            <input type="text" name="system_name" value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Address</label>
                            <textarea name="institution_address" rows="2"><?php echo htmlspecialchars($settings['institution_address'] ?? ''); ?></textarea>
                        </div>
                         <div class="form-group">
                            <label>Contact Info (Email/Phone)</label>
                            <input type="text" name="institution_contact" value="<?php echo htmlspecialchars($settings['institution_contact'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="button" id="save-general-settings" class="action-button">
                        <i class="fas fa-save"></i> Save General Settings
                    </button>
                </form>
            </div>

            <div class="academic-session widget-card">
                <div class="widget-header">
                    <h3><i class="fas fa-calendar-alt"></i> Academic Session</h3>
                </div>
                <p>
                    Start a new academic session. This will archive all current student enrollments into their history and <strong>permanently delete</strong> all pending/active enrollments to clear the system for a new term.
                </p>
                <p>
                    <strong>This action cannot be undone.</strong>
                </p>
                <form method="POST" action="system_settings.php" id="new-session-form">
                    <input type="hidden" name="action" value="start_new_session">
                    <div class="form-group">
                        <label>New Session Year</label>
                        <input type="text" name="session" id="session-year-input" placeholder="Enter session year (e.g., 2025-26)" required>
                    </div>
                    <button type="button" id="start-session-btn" class="action-button danger">
                        <i class="fas fa-play-circle"></i> Start New Session
                    </button>
                </form>
            </div>

        </div>

    </main>
    
    <div class="confirm-modal-overlay" id="confirmModal">
        <div class="confirm-modal">
            <h2 id="modalTitle"></h2>
            <p id="modalText"></p>
            <div class="confirm-modal-actions">
                <button class="confirm-btn btn-cancel" onclick="closeNewSessionModal()">Cancel</button>
                <button class="confirm-btn btn-confirm-final" id="confirmActionButton"></button>
            </div>
        </div>
    </div>

<script>
    // --- START: Navbar Scroll & Hamburger ---
    let lastScrollTop = 0;
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        if (scrollTop > lastScrollTop && scrollTop > 70) { 
            navbar.style.top = "-80px";
        } else {
            navbar.style.top = "0";
        }
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
    }, false);

    const hamburger = document.getElementById('hamburger');
    const navContainer = document.getElementById('nav-container');
    if (hamburger) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navContainer.classList.toggle('active');
        });
    }
    // --- END: Navbar Scroll & Hamburger ---

    // Success popup removal
    setTimeout(() => {
        let popup = document.getElementById('popup-message');
        if (popup) {
             popup.style.transition = 'opacity 0.5s ease';
             popup.style.opacity = '0';
             setTimeout(() => popup.remove(), 500);
        }
    }, 4000);

    /* ================================================================ */
    /* == System Toggles AJAX Script == */
    /* ================================================================ */

    // Function to send the setting update
    function updateSetting(key, value) {
        return fetch('save_settings.php', { // Return the fetch promise
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ key: key, value: value })
        })
        .then(response => response.json());
    }

    // Add event listeners to the toggles
    document.getElementById('reg-toggle').addEventListener('change', function() {
        updateSetting('open_registrations', this.checked)
            .then(data => console.log('Registration Toggle:', data.status))
            .catch(err => console.error('Error:', err));
    });

    document.getElementById('maint-toggle').addEventListener('change', function() {
        updateSetting('maintenance_mode', this.checked)
            .then(data => console.log('Maintenance Toggle:', data.status))
            .catch(err => console.error('Error:', err));
    });

    document.getElementById('email-toggle').addEventListener('change', function() {
        updateSetting('email_alerts', this.checked)
            .then(data => console.log('Email Toggle:', data.status))
            .catch(err => console.error('Error:', err));
    });

    /* ================================================================ */
    /* == General Settings Save Script == */
    /* ================================================================ */
    document.getElementById('save-general-settings').addEventListener('click', function(e) {
        const btn = e.target;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;

        const form = document.getElementById('general-settings-form');
        const inputs = form.querySelectorAll('input, textarea');
        const promises = [];

        inputs.forEach(input => {
            promises.push(updateSetting(input.name, input.value));
        });

        Promise.all(promises)
            .then(results => {
                console.log('All settings saved:', results);
                btn.innerHTML = '<i class="fas fa-check"></i> Saved!';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-save"></i> Save General Settings';
                    btn.disabled = false;
                }, 2000);
            })
            .catch(err => {
                console.error('Failed to save settings:', err);
                btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                 setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-save"></i> Save General Settings';
                    btn.disabled = false;
                }, 3000);
            });
    });

    /* ================================================================ */
    /* == MOVED: New Session Modal Script == */
    /* ================================================================ */
    const confirmModal = document.getElementById('confirmModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalText = document.getElementById('modalText');
    const confirmActionButton = document.getElementById('confirmActionButton');
    const sessionForm = document.getElementById('new-session-form');

    document.getElementById('start-session-btn').addEventListener('click', function() {
        const sessionInput = document.getElementById('session-year-input');
        const sessionYear = sessionInput.value.trim();

        if (!sessionYear) {
            alert('Please enter a session year first.');
            sessionInput.focus();
            return;
        }
        
        modalTitle.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Start ${sessionYear} Session?`;
        modalText.innerHTML = `You are about to start the <strong>${sessionYear}</strong> session. This will archive all approved enrollments and <strong style="color: #b91c1c;">PERMANENTLY DELETE</strong> all pending enrollments.`;
        
        confirmActionButton.onclick = () => showFinalWarning(sessionYear);
        confirmActionButton.innerHTML = 'Yes, Continue';
        
        confirmModal.classList.add('visible');
    });

    function showFinalWarning(sessionYear) {
        modalTitle.innerHTML = `<i class="fas fa-skull-crossbones"></i> FINAL WARNING`;
        modalText.innerHTML = `This action cannot be undone. All active enrollment data will be wiped to begin session <strong>${sessionYear}</strong>. Are you absolutely sure?`;
        
        confirmActionButton.onclick = () => {
            // Submit the form
            sessionForm.submit();
        };
        confirmActionButton.innerHTML = 'Confirm & Start Session';
    }

    function closeNewSessionModal() {
        confirmModal.classList.remove('visible');
    }

</script>
</body>
</html>