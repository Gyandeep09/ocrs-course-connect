<?php
require_once 'session_config.php';

// Protect this page
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_page.php');
    exit;
}
require_once 'db_connect.php'; 

// --- Fetch System Settings ---
$settings = [];
$settings_result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// --- Fetch recent pending enrollments for dashboard widget ---
$recent_pending_sql = "
    SELECT s.name, c.course_name, e.enroll_date
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN course_records c ON e.course_id = c.id
    WHERE e.status = 'Pending'
    ORDER BY e.enroll_date DESC
    LIMIT 5
";
$recent_pending_result = $conn->query($recent_pending_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* All dashboard styles are preserved */
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

    /* Navbar */
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
        grid-template-columns: 1fr auto 1fr; /* [Left] [Center] [Right] */
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

    /* Dashboard-only Navbar Right */
    .nav-right {
        grid-column: 3 / 4;
        justify-self: end;
        display: flex;
        align-items: center;
        gap: 0.5rem; 
    }
    .icon-button {
        position: relative; background: transparent; border: none;
        color: var(--text-secondary);
        font-size: 20px;
        cursor: pointer; transition: color 0.3s ease;
        width: 40px; 
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .icon-button:hover { 
        color: var(--text-primary); 
        background-color: var(--bg-color);
    }
    .icon-button .badge {
        position: absolute; top: -2px; right: -2px;
        background: var(--accent-red); color: white;
        font-size: 10px; font-weight: 600;
        width: 18px; height: 18px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
    .profile-dropdown { position: relative; }
    .profile-button {
        display: flex; align-items: center; gap: 10px;
        background: transparent; border: none; cursor: pointer;
        padding: 0;
        border-radius: 50%;
    }
    .profile-button img {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
        display: block;
    }
    .profile-button .admin-name { display: none; }
    .profile-button i { display: none; }
    .profile-menu {
        position: absolute; top: 140%; right: 0;
        background: var(--widget-bg); border: 1px solid var(--border-color);
        border-radius: 8px; box-shadow: var(--shadow-lg);
        width: 220px; z-index: 110;
        opacity: 0; visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }
    .profile-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
    .profile-menu-header { padding: 1rem; border-bottom: 1px solid var(--border-color); }
    .profile-menu-header h4 { margin: 0 0 4px; font-size: 15px; }
    .profile-menu-header p { margin: 0; font-size: 13px; color: var(--text-secondary); }
    .profile-menu-list { list-style: none; padding: 0.5rem; }
    .profile-menu-list a {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 6px;
        text-decoration: none; color: var(--text-primary);
        font-size: 14px; transition: all 0.2s ease;
    }
    .profile-menu-list a:hover { background: var(--bg-color); }
    .profile-menu-list a.logout { color: var(--accent-red); }
    .profile-menu-list a.logout:hover { background: rgba(239, 68, 68, 0.1); }
    .profile-menu-list a i {
        width: 16px; text-align: center;
        color: var(--text-secondary);
    }
    .profile-menu-list a.logout i { color: var(--accent-red); }

    /* Mobile Navbar */
    @media (max-width: 1050px) {
        .nav-container, .navbar-container {
            display: flex;
            justify-content: space-between;
        }
        .nav-menu, .navbar-nav { display: none; }
        .nav-right { display: none; }
        .hamburger { display: flex; }
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

    /* Main Content */
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
    .main-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .main-header h1 { font-size: 28px; font-weight: 700; color: var(--text-primary); }
    .quick-actions { display: flex; gap: 12px; }
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
    .action-button.secondary {
        background: var(--widget-bg);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
    }
    .action-button.secondary:hover {
        background: var(--bg-color);
        box-shadow: var(--shadow-md);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .stats-card {
        background: var(--widget-bg);
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .stats-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .stats-card-icon {
        font-size: 24px; width: 50px; height: 50px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff; margin-bottom: 1rem;
    }
    .stats-card-icon.blue { background: linear-gradient(135deg, #3b82f6, #6366f1); }
    .stats-card-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
    .stats-card-icon.yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stats-card-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .stats-card-icon.purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
    .stats-card .card-value { font-size: 2.25rem; font-weight: 700; color: var(--text-primary); line-height: 1; }
    .stats-card .card-label { font-size: 14px; color: var(--text-secondary); font-weight: 500; margin-top: 0.5rem; }
    .stats-card .card-link {
        position: absolute; bottom: 1.5rem; right: 1.5rem; font-size: 18px;
        color: var(--text-secondary); opacity: 0.2; transition: all 0.3s ease;
    }
    .stats-card:hover .card-link { opacity: 1; transform: translateX(-5px); color: var(--accent-blue); }

    /* Dashboard Grid */
    .dashboard-grid-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        grid-template-rows: auto auto;
        gap: 1.5rem;
    }
    .widget-card {
        background: var(--widget-bg);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
    }
    .dashboard-grid-layout .widget-card { margin-bottom: 0; }
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
    }
    .widget-header a { font-size: 14px; color: var(--accent-blue); text-decoration: none; font-weight: 500; }
    .recent-activity { grid-column: 1 / 2; grid-row: 1 / 2; }
    .activity-list { list-style: none; }
    .activity-item {
        display: flex; gap: 1rem; padding: 0.75rem 0;
        border-bottom: 1px solid var(--border-color);
    }
    .activity-item:last-child { border-bottom: none; padding-bottom: 0; }
    .activity-item:first-child { padding-top: 0; }
    .activity-icon {
        font-size: 16px; width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; background: rgba(245, 158, 11, 0.1); color: #d97706;
    }
    .activity-details p { margin: 0; font-size: 15px; font-weight: 500; color: var(--text-primary); }
    .activity-details span { font-size: 13px; color: var(--text-secondary); }
    .activity-details strong { color: var(--text-primary); font-weight: 600; }
    .system-settings { grid-column: 1 / 2; grid-row: 2 / 3; }
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
    .quick-links { grid-column: 2 / 3; grid-row: 1 / 2; }
    .links-list { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .link-button {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);
        text-decoration: none; color: var(--text-primary);
        font-weight: 600; font-size: 14px; transition: all 0.3s ease;
    }
    .link-button:hover {
        border-color: var(--accent-blue); background: rgba(59, 130, 246, 0.05);
        color: var(--accent-blue); transform: translateY(-3px); box-shadow: var(--shadow-md);
    }
    .link-button i { font-size: 20px; margin-bottom: 0.5rem; }
    .analytics-chart { grid-column: 2 / 3; grid-row: 2 / 3; }
    .chart-placeholder {
        width: 100%; height: 200px;
        background: linear-gradient(135deg, var(--bg-color), #e9eef3);
        border-radius: 8px; display: flex; align-items: center; justify-content: center;
        text-align: center; color: var(--text-secondary); font-weight: 500;
        border: 1px dashed var(--border-color);
    }
    .chart-placeholder i { font-size: 24px; margin-bottom: 0.5rem; }

    /* Transitions & Popups */
    .login-transition {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(135deg, #0f172a, #1e293b);
        z-index: 9999; display: flex; align-items: center; justify-content: center;
        opacity: 1; visibility: visible;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .login-transition.fade-out { opacity: 0; visibility: hidden; }
    .transition-content { text-align: center; color: white; }
    .transition-spinner {
        width: 48px; height: 48px;
        border: 3px solid rgba(16, 185, 129, 0.2);
        border-top: 3px solid #10b981;
        border-radius: 50%;
        animation: spin 1s linear infinite; margin: 0 auto 24px;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .transition-text { font-size: 20px; font-weight: 600; margin-bottom: 8px; color: #ffffff; }
    .transition-subtext { font-size: 15px; color: #cbd5e1; }
    #popup-message {
        position: fixed; top: 90px; right: 20px;
        background: linear-gradient(135deg, #059669, #047857);
        color: white; padding: 16px 24px; border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15); z-index: 9999;
        font-size: 15px; font-weight: 500;
        animation: fadeInOut 4s forwards;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateX(100px); }
        15% { opacity: 1; transform: translateX(0); }
        85% { opacity: 1; }
        100% { opacity: 0; transform: translateX(100px); }
    }
    
    /* Responsive Grid */
    @media (max-width: 1200px) {
        .dashboard-grid-layout {
            grid-template-columns: 1fr;
        }
        .recent-activity, .system-settings, .quick-links, .analytics-chart {
            grid-column: 1 / -1;
            grid-row: auto;
        }
    }
    @media (max-width: 768px) {
        .nav-container, .navbar-container { padding: 0 1rem; }
        .main-content { padding: 1.5rem 1rem; }
        .page-header, .main-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        .page-title h1, .main-header h1 { font-size: 24px; }
        .page-actions, .quick-actions { flex-wrap: wrap; }
        .widget-card { padding: 1.5rem; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .profile-button .admin-name { display: none; }
        .stats-grid { grid-template-columns: 1fr; }
    }

    /* --- NEW: Search Modal Styles --- */
    .search-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
        z-index: 2000; display: flex; 
        align-items: flex-start; /* Position modal at the top */
        justify-content: center;
        padding-top: 15vh; /* Push it down from the top */
        opacity: 0; visibility: hidden; transition: all 0.3s ease;
    }
    .search-modal-overlay.visible { opacity: 1; visibility: visible; }
    .search-modal {
        background: var(--widget-bg);
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        width: 90%; max-width: 600px;
        transform: scale(0.95);
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .search-modal-overlay.visible .search-modal { transform: scale(1); }
    .search-bar-wrapper {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
    }
    .search-bar-wrapper i {
        font-size: 20px;
        color: var(--text-secondary);
        margin: 0 1rem;
    }
    .search-modal-input {
        width: 100%;
        border: none;
        outline: none;
        background: transparent;
        color: var(--text-primary);
        font-size: 1.2rem;
        font-weight: 500;
        font-family: "Spartan", sans-serif;
    }
    .search-modal-input::placeholder {
        color: var(--text-secondary);
    }
    .search-results {
        max-height: 40vh;
        overflow-y: auto;
        padding: 0.5rem;
    }
    .search-result-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1.5rem;
        text-decoration: none;
        color: var(--text-secondary);
        border-radius: 8px;
        font-weight: 500;
    }
    .search-result-item i {
        width: 20px;
        text-align: center;
        font-size: 16px;
        color: var(--text-primary);
    }
    .search-result-item:hover,
    .search-result-item.active {
        background: var(--bg-color);
        color: var(--text-primary);
    }
    .search-no-results {
        padding: 2rem;
        text-align: center;
        color: var(--text-secondary);
    }
</style>
</head>
<body>
    <?php if (isset($_SESSION['show_login_transition'])): ?>
    <div class="login-transition" id="loginTransition">
        <div class="transition-content">
            <div class="transition-spinner"></div>
            <div class="transition-text">Welcome Back, Admin!</div>
            <div class="transition-subtext">Loading your dashboard...</div>
        </div>
    </div>
    <?php unset($_SESSION['show_login_transition']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
    <div id="popup-message">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
    <?php endif; ?>

    <nav class="navbar">
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

            <ul class="nav-menu" id="nav-menu">
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
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Reports</span>
                    </a>
                </li>
                
            </ul>
            
            <div class="nav-right">
                <button class="icon-button" id="search-btn" title="Search">
                    <i class="fas fa-search"></i>
                </button>
                
                <button class="icon-button" id="notifications-btn" title="Notifications" onclick="window.location.href='enrollments.php'">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="pending-badge-count">0</span>
                </button>
                
                <div class="profile-dropdown">
                    <button class="profile-button" id="profile-btn" title="Profile">
                        <img src="https://via.placeholder.com/40" alt="Admin">
                    </button>
                    <div class="profile-menu" id="profile-menu">
                        <div class="profile-menu-header">
                            <h4>Administrator</h4>
                            <p>OCRS Main Control</p>
                        </div>
                        <ul class="profile-menu-list">
                            <li><a href="admin_profile.php"><i class="fas fa-user-circle"></i> Profile Settings</a></li>
                            <li><a href="system_settings.php"><i class="fas fa-cog"></i> System Preferences</a></li>
                            <li><a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        
        <div class="main-header">
            <h1>Dashboard Overview</h1>
            <div class="quick-actions">
                <button class="action-button secondary" onclick="window.location.href='course_management.php'"><i class="fas fa-plus"></i> Add Course</button>
                <button class="action-button" onclick="window.location.href='enrollments.php'"><i class="fas fa-tasks"></i> Manage Enrollments</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stats-card" onclick="window.location.href='department.php'">
                <div class="stats-card-icon blue"><i class="fas fa-building"></i></div>
                <div class="card-value" id="departmentsCount">0</div>
                <div class="card-label">Active Departments</div>
                <a href="department.php" class="card-link"><i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="stats-card" onclick="window.location.href='course_management.php'">
                <div class="stats-card-icon green"><i class="fas fa-book"></i></div>
                <div class="card-value" id="coursesCount">0</div>
                <div class="card-label">Available Courses</div>
                <a href="course_management.php" class="card-link"><i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="stats-card" onclick="window.location.href='student_management.php'">
                <div class="stats-card-icon purple"><i class="fas fa-user-graduate"></i></div>
                <div class="card-value" id="studentsCount">0</div>
                <div class="card-label">Active Students</div>
                <a href="student_management.php" class="card-link"><i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="stats-card" onclick="window.location.href='enrollments.php'">
                <div class="stats-card-icon yellow"><i class="fas fa-user-check"></i></div>
                <div class="card-value" id="pendingEnrollmentsCount">0</div>
                <div class="card-label">Pending Enrollments</div>
                <a href="enrollments.php" class="card-link"><i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="stats-card" onclick="window.location.href='reports.php'">
                <div class="stats-card-icon red"><i class="fas fa-chart-line"></i></div>
                <div class="card-value" id="completedCoursesCount">0</div>
                <div class="card-label">Total Reports</div>
                <a href="reports.php" class="card-link"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="dashboard-grid-layout">

            <div class="widget-card recent-activity">
                <div class="widget-header">
                    <h3>Recent Pending Enrollments</h3>
                    <a href="enrollments.php">View All</a>
                </div>
                <ul class="activity-list">
                    <?php if ($recent_pending_result && $recent_pending_result->num_rows > 0): ?>
                        <?php while($row = $recent_pending_result->fetch_assoc()): ?>
                            <li class="activity-item">
                                <div class="activity-icon"><i class="fas fa-hourglass-start"></i></div>
                                <div class="activity-details">
                                    <p>New request from <strong><?= htmlspecialchars($row['name']) ?></strong></p>
                                    <span>For <strong><?= htmlspecialchars($row['course_name']) ?></strong> on <?= (new DateTime($row['enroll_date']))->format('M d, Y') ?></span>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--accent-green);"><i class="fas fa-check-circle"></i></div>
                            <div class="activity-details">
                                <p><strong>All caught up!</strong></p>
                                <span>No pending enrollments right now.</span>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="widget-card system-settings">
                <div class="widget-header">
                    <h3>System Settings</h3>
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

            <div class="widget-card quick-links">
                <div class="widget-header">
                    <h3>Quick Links</h3>
                </div>
                <div class="links-list">
                    <a href="course_management.php" class="link-button"><i class="fas fa-book"></i>Courses</a>
                    <a href="student_management.php" class="link-button"><i class="fas fa-user-graduate"></i>Students</a>
                    <a href="reports.php" class="link-button"><i class="fas fa-chart-line"></i>Reports</a>
                    <a href="system_settings.php" class="link-button"><i class="fas fa-cog"></i>Settings</a>
                </div>
            </div>

            <div class="widget-card analytics-chart">
                <div class="widget-header">
                    <h3>System Analytics</h3>
                </div>
                <div class="chart-placeholder">
                    <div>
                        <i class="fas fa-chart-bar"></i>
                        <p>Enrollment Over Time (Chart)</p>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <div class="search-modal-overlay" id="searchModal">
        <div class="search-modal">
            <div class="search-bar-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" class="search-modal-input" id="searchInput" placeholder="Search for pages, students, or courses...">
            </div>
            <div class="search-results" id="searchResults">
                <div class="search-no-results">Start typing to see results.</div>
            </div>
        </div>
    </div>

<script>
    // --- Start of existing, unchanged scripts ---
    // Login transition effect
    document.addEventListener('DOMContentLoaded', function() {
        const transition = document.getElementById('loginTransition');
        if (transition) {
       setTimeout(() => {
            transition.classList.add('fade-out');
            setTimeout(() => transition.remove(), 300);
        }, 0);
        }
    });

    // Success popup removal
    setTimeout(() => {
        let popup = document.getElementById('popup-message');
        if (popup) {
             popup.style.transition = 'opacity 0.5s ease';
             popup.style.opacity = '0';
             setTimeout(() => popup.remove(), 500);
        }
    }, 4000);

    // Stats Fetching
    fetch('fetch_admin_stats.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('departmentsCount').textContent = data.departments;
            document.getElementById('coursesCount').textContent = data.courses;
            document.getElementById('studentsCount').textContent = data.students;
            document.getElementById('pendingEnrollmentsCount').textContent = data.pendingEnrollments;
            document.getElementById('completedCoursesCount').textContent = data.completedCourses;
            // Update notification badge
            document.getElementById('pending-badge-count').textContent = data.pendingEnrollments;
            if (data.pendingEnrollments > 0) {
                 document.getElementById('notifications-btn').classList.add('fa-beat');
                 document.getElementById('pending-badge-count').style.display = 'flex';
            } else {
                 document.getElementById('pending-badge-count').style.display = 'none';
            }
        })
        .catch(err => console.error('Error fetching admin stats:', err));
    // --- End of existing scripts ---


    /* ================================================================ */
    /* == NEW JavaScript for Navbar, Dropdowns, and Search Modal == */
    /* ================================================================ */

    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. Collapsible Navbar (Hamburger) ---
        const hamburger = document.getElementById('hamburger');
        const navContainer = document.getElementById('nav-container');
        hamburger.addEventListener('click', () => {
            navContainer.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // --- 2. Profile Dropdown ---
        const profileBtn = document.getElementById('profile-btn');
        const profileMenu = document.getElementById('profile-menu');
        if (profileBtn) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });
        }
        
        // --- 3. Search Modal Logic ---
        const searchBtn = document.getElementById('search-btn');
        const searchModal = document.getElementById('searchModal');
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        
        // Define searchable pages
        const searchablePages = [
            { name: 'Dashboard', url: 'admin_dashboard.php', icon: 'fa-tachometer-alt' },
            { name: 'Departments', url: 'department.php', icon: 'fa-building' },
            { name: 'Courses', url: 'course_management.php', icon: 'fa-book' },
            { name: 'Students', url: 'student_management.php', icon: 'fa-user-graduate' },
            { name: 'Enrollments', url: 'enrollments.php', icon: 'fa-user-check' },
            { name: 'Reports', url: 'reports.php', icon: 'fa-chart-line' },
            { name: 'My Profile', url: 'admin_profile.php', icon: 'fa-user-circle' },
            { name: 'System Settings', url: 'system_settings.php', icon: 'fa-cog' }
        ];

        // Open Search Modal
        if(searchBtn) {
            searchBtn.addEventListener('click', () => {
                searchModal.classList.add('visible');
                searchInput.focus();
            });
        }
        
        // Close Search Modal
        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                searchModal.classList.remove('visible');
            }
        });

        // Handle Search Input
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            if (query.length === 0) {
                searchResults.innerHTML = '<div class="search-no-results">Start typing to see results.</div>';
                return;
            }

            const results = searchablePages.filter(page => 
                page.name.toLowerCase().includes(query)
            );

            if (results.length > 0) {
                searchResults.innerHTML = '';
                results.forEach(result => {
                    const item = document.createElement('a');
                    item.href = result.url;
                    item.classList.add('search-result-item');
                    item.innerHTML = `
                        <i class="fas ${result.icon}"></i>
                        <span>${result.name}</span>
                    `;
                    searchResults.appendChild(item);
                });
            } else {
                searchResults.innerHTML = '<div class="search-no-results">No pages found matching your query.</div>';
            }
        });


        // Close dropdown if clicking outside
        document.addEventListener('click', (e) => {
            if (profileMenu && !profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                profileMenu.classList.remove('show');
            }
        });
        
        // --- 4. REMOVED Session Widget Toggle ---
        // The FAB and its logic have been moved to system_settings.php
    });

    /* ================================================================ */
    /* == System Toggles AJAX Script == */
    /* ================================================================ */

    // Function to send the setting update
    function updateSetting(key, value) {
        fetch('save_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ key: key, value: value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                console.log('Setting saved:', key, value);
            } else {
                console.error('Failed to save setting:', data.message);
            }
        })
        .catch(err => console.error('Error:', err));
    }

    // Add event listeners to the toggles
    document.getElementById('reg-toggle').addEventListener('change', function() {
        updateSetting('open_registrations', this.checked);
    });

    document.getElementById('maint-toggle').addEventListener('change', function() {
        updateSetting('maintenance_mode', this.checked);
    });

    document.getElementById('email-toggle').addEventListener('change', function() {
        updateSetting('email_alerts', this.checked);
    });

    /* ================================================================ */
    /* == Navbar Scroll Effect == */
    /* ================================================================ */
    
    let lastScrollTop = 0;
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 70) { 
            navbar.style.top = "-80px"; // Hide navbar
        } else {
            navbar.style.top = "0";
        }
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
    }, false);

</script>
</body>
</html>