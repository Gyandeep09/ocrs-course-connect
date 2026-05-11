<?php
require_once 'session_config.php';
include 'db_connect.php'; // Ensure DB connection is included

if (!isset($_SESSION['student_id'])) {
    header('Location: student_page.php'); // Redirect to your student login page
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student data for navbar and profile card
$stmt = $conn->prepare("SELECT name, email, reg_no, profile_picture, course FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Check if the student is enrolled in any course
$is_enrolled = !empty($student['course']);

// --- FIX #1: Use null coalescing (??) to prevent htmlspecialchars(null) errors ---
$student_name = htmlspecialchars($student['name'] ?? 'N/A');
$student_email = htmlspecialchars($student['email'] ?? 'N/A');
$student_reg_no = htmlspecialchars($student['reg_no'] ?? 'Not Assigned');
$student_course = htmlspecialchars($student['course'] ?? 'Not Enrolled');
// --- END FIX #1 ---

$profile_picture = !empty($student['profile_picture']) ? htmlspecialchars($student['profile_picture']) : 'uploads/default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - Student Dashboard</title>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
            document.body.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* ... [ALL YOUR EXISTING CSS FROM student_dashboard.php] ... */
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
        --accent-purple: #8b5cf6; /* Added for stats card */
        --active-line: #088178; 
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --readonly-bg: #eef2ff; /* For readonly inputs */
        --readonly-color: #4338ca;
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
        grid-column: 1 / 2; /* Column 1 */
        justify-self: start; /* Align to the left */
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
        grid-column: 3 / 4; /* Column 3 */
        justify-self: end; /* Align to the right */
        display: none; /* Hidden on desktop */
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
        grid-column: 2 / 3; /* Column 2 */
        justify-self: center; /* Center it */
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
    .nav-right {
        grid-column: 3 / 4; /* Column 3 */
        justify-self: end; /* Align to the right */
        display: flex;
        align-items: center;
        gap: 0.5rem; 
    }
    .profile-dropdown { position: relative; }
    .profile-button {
        display: flex;
        align-items: center;
        gap: 10px;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 4px; 
        border-radius: 50px; 
        transition: all 0.3s ease;
    }
    .profile-button:hover {
        background-color: var(--bg-color);
    }
    .profile-button img {
        width: 36px; 
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
        display: block;
    }
    .profile-button .student-name {
        font-weight: 600; 
        color: var(--text-primary); 
        font-size: 15px;
        margin-left: 2px;
        margin-right: 4px;
    }
    .profile-button i { 
        color: var(--text-secondary);
        margin-right: 4px;
        transition: transform 0.3s ease;
    }
    .profile-menu.show + .profile-button i {
        transform: rotate(180deg);
    }
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
        .nav-right {
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
            max-height: 500px;
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
    .main-header h1 { 
        font-size: 28px; 
        font-weight: 700; 
        color: var(--text-primary); 
    }
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
        height: fit-content; 
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
    .stats-card:hover { 
        transform: translateY(-5px); 
        box-shadow: var(--shadow-lg); 
    }
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
    .stats-card .card-value { 
        font-size: 2.25rem; 
        font-weight: 700; 
        color: var(--text-primary); 
        line-height: 1; 
    }
    .stats-card .card-label { 
        font-size: 14px; 
        color: var(--text-secondary); 
        font-weight: 500; 
        margin-top: 0.5rem; 
    }
    .stats-card .card-link {
        position: absolute; bottom: 1.5rem; right: 1.5rem; font-size: 18px;
        color: var(--text-secondary); opacity: 0.2; transition: all 0.3s ease;
    }
    .stats-card:hover .card-link { opacity: 1; transform: translateX(-5px); color: var(--accent-blue); }
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
        margin-bottom: 0; 
    }
    .widget-header {
        display: flex;
        justify-content: space-between; /* Added */
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
    .profile-summary { grid-column: 1 / 2; grid-row: 2 / 3; }
    .summary-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 1rem 0; border-bottom: 1px solid var(--border-color);
    }
    .summary-item:last-child { border-bottom: none; }
    .summary-item .label { margin: 0; font-weight: 500; color: var(--text-secondary); }
    .summary-item .value { font-size: 15px; color: var(--text-primary); font-weight: 600;}
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
    .login-transition {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(135deg, #0f172a, #1e293b);
        z-index: 9999; display: flex; align-items: center; justify-content: center;
        opacity: 1; visibility: visible;
        transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .login-transition.fade-out { opacity: 0; visibility: hidden; }
    .transition-content { text-align: center; color: white; }
    .transition-spinner {
        width: 48px; height: 48px;
        border: 3px solid rgba(59, 130, 246, 0.3);
        border-top: 3px solid var(--accent-blue);
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
    .enrollment-ticker-container {
        background: linear-gradient(90deg, #10b981, #059669);
        color: #ffffff;
        padding: 12px 0;
        font-weight: 600;
        font-size: 15px;
        position: fixed;
        top: 70px; /* Position it right below the NEW navbar */
        width: 100%;
        z-index: 999;
        overflow: hidden;
        white-space: nowrap;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .enrollment-ticker-text {
        display: inline-block;
        padding-left: 100%; /* Start the text off-screen */
        animation: marquee-scroll 25s linear infinite;
    }
    .enrollment-ticker-text span {
        margin-right: 50px; /* Space between repeated messages */
    }
    @keyframes marquee-scroll {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-100%); }
    }
    @media (max-width: 1200px) { /* Dashboard grid responsive */
        .dashboard-grid-layout {
            grid-template-columns: 1fr;
        }
        .recent-activity, .profile-summary, .quick-links, .analytics-chart {
            grid-column: 1 / -1;
            grid-row: auto;
        }
    }
    @media (max-width: 768px) {
        body { padding-top: 70px; }
        .main-content {
            margin: 0;
            padding: 1.5rem 1rem;
            border-radius: 0;
        }
        .main-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        .main-header h1 { font-size: 24px; }
        .stats-grid {
            grid-template-columns: 1fr 1fr; /* 2 columns on mobile */
        }
    }
    @media (max-width: 480px) {
        .profile-button .student-name {
            display: none; /* Hide name on small screens */
        }
        .stats-grid {
            grid-template-columns: 1fr; /* 1 column on small mobile */
        }
    }
    html[data-theme="dark"] body {
        --bg-color: #0f172a; /* Slate 900 */
        --navbar-bg: rgba(30, 41, 59, 0.85); /* Slate 800 */
        --widget-bg: #1e293b; /* Slate 800 */
        --text-primary: #f1f5f9; /* Slate 100 */
        --text-secondary: #94a3b8; /* Slate 400 */
        --border-color: #334155; /* Slate 700 */
        --readonly-bg: #334155;
        --readonly-color: #cbd5e1;
    }
    html[data-theme="dark"] table th {
        background: #334155; /* Slate 700 */
        color: var(--text-primary);
    }
    html[data-theme="dark"] table tr:nth-child(even) {
        background: #1e293b; /* Slate 800 */
    }
    html[data-theme="dark"] table tr:hover {
        background: rgba(59, 130, 246, 0.15); /* Brighter blue hover */
    }
    html[data-theme="dark"] .form-group input,
    html[data-theme="dark"] .form-group textarea,
    html[data-theme="dark"] .form-group select,
    html[data-theme="dark"] .search-container input,
    html[data-theme="dark"] .filter-group select {
        background: #334155;
        border-color: #475569;
        color: var(--text-primary);
    }
    html[data-theme="dark"] .form-group input:focus,
    html[data-theme="dark"] .form-group textarea:focus,
    html[data-theme="dark"] .form-group select:focus,
    html[data-theme="dark"] .search-container input:focus,
    html[data-theme="dark"] .filter-group select:focus {
        background: #1e293b;
        border-color: var(--accent-blue);
    }
    html[data-theme="dark"] .form-group input[readonly] {
        background: var(--readonly-bg);
        color: var(--readonly-color);
    }
    html[data-theme="dark"] .icon-button:hover { 
        background-color: #334155;
    }
    html[data-theme="dark"] .profile-button:hover {
        background-color: #334155;
    }
    html[data-theme="dark"] .profile-menu,
    html[data-theme="dark"] .theme-menu {
        background: #1e293b;
        border-color: #334155;
    }
    html[data-theme="dark"] .profile-menu-list a:hover {
        background: #334155;
    }
    html[data-theme="dark"] .password-section {
        background: #0f172a;
        border-color: #334155;
    }
    html[data-theme="dark"] .stats-card,
    html[data-theme="dark"] .widget-card,
    html[data-theme="dark"] .table-container,
    html[data-theme="dark"] .empty-state,
    html[data-theme="dark"] .filter-section,
    html[data-theme="dark"] .department-accordion {
        background: var(--widget-bg);
        border-color: var(--border-color);
    }
    html[data-theme="dark"] .action-button.secondary,
    html[data-theme="dark"] .action-button.filter {
        background: var(--widget-bg);
        color: var(--text-primary);
        border-color: var(--border-color);
    }
    html[data-theme="dark"] .action-button.secondary:hover,
    html[data-theme="dark"] .action-button.filter:hover {
        background: #334155;
    }
    html[data-theme="dark"] .modal-content,
    html[data-theme="dark"] .popup-box {
        background: var(--widget-bg);
    }
    html[data-theme="dark"] .popup-btn.cancel {
        background: #334155;
        color: var(--text-primary);
    }
    html[data-theme="dark"] .popup-btn.cancel:hover {
        background: #475569;
    }
    html[data-theme="dark"] .link-button {
         border-color: var(--border-color);
    }
    html[data-theme="dark"] .link-button:hover {
        border-color: var(--accent-blue); 
        background: rgba(59, 130, 246, 0.05);
        color: var(--accent-blue);
    }
    html[data-theme="dark"] .chart-placeholder {
        background: linear-gradient(135deg, var(--bg-color), #1e293b);
        border-color: var(--border-color);
    }
    html[data-theme="dark"] .activity-list .activity-item {
        border-color: var(--border-color);
    }
    html[data-theme="dark"] .summary-item {
         border-color: var(--border-color);
    }
    html[data-theme="dark"] .message.success { 
        background: #064e3b; 
        color: #d1fae5; 
        border-color: #042f2e;
    }
    html[data-theme="dark"] .message.error { 
        background: #7f1d1d; 
        color: #fecaca; 
        border-color: #450a0a;
    }
    html[data-theme="dark"] .message.warning {
        background: #78350f;
        color: #fef3c7;
        border-color: #451a03;
    }
    html[data-theme="dark"] .course-code {
        background: rgba(59, 130, 246, 0.15);
        color: #93c5fd;
    }
    html[data-theme="dark"] .seats-available.available {
        background: rgba(34, 197, 94, 0.1);
        color: #4ade80;
    }
    html[data-theme="dark"] .seats-available.limited {
        background: rgba(245, 158, 11, 0.1);
        color: #facc15;
    }
    html[data-theme="dark"] .seats-available.full {
        background: rgba(239, 68, 68, 0.1);
        color: #f87171;
    }
    html[data-theme="dark"] .modal-overlay {
        background-color: rgba(15, 23, 42, 0.8);
    }
    
    /* === NEW: Toast Notification Styles (Fix #5) === */
    #toast-container {
        position: fixed;
        top: 90px;
        right: 20px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .toast {
        display: flex;
        align-items: center;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: white;
        font-size: 15px;
        font-weight: 500;
        animation: toastIn 0.5s cubic-bezier(0.21, 1.02, 0.73, 1) forwards;
        opacity: 0;
        transform: translateX(100px);
        min-width: 300px;
        max-width: 400px;
    }
    .toast.success { background: linear-gradient(135deg, #059669, #047857); }
    .toast.error { background: linear-gradient(135deg, #dc2626, #b91c1c); }
    .toast.info { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .toast i {
        font-size: 20px;
        margin-right: 12px;
    }
    .toast-message {
        flex-grow: 1;
    }
    @keyframes toastIn {
        from { opacity: 0; transform: translateX(100px); }
        to { opacity: 1; transform: translateX(0); }
    }
    @keyframes toastOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(100px); }
    }

    html[data-theme="dark"] .toast.success { background: #064e3b; color: #d1fae5; border-color: #042f2e;}
    html[data-theme="dark"] .toast.error { background: #7f1d1d; color: #fecaca; border-color: #450a0a;}
    html[data-theme="dark"] .toast.info { background: #1e40af; color: #dbeafe; border-color: #1e3a8a;}
</style>
</head>
<body>
    <!-- NEW: Toast Container -->
    <div id="toast-container"></div>
    
    <?php if (isset($_SESSION['show_login_transition'])): ?>
    <div class="login-transition" id="loginTransition">
        <div class="transition-content">
            <div class="transition-spinner"></div>
            <div class="transition-text">Welcome!</div>
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

    <nav class="navbar" id="navbar">
        <div class="nav-container" id="nav-container">
            <a href="student_dashboard.php" class="nav-brand">
                <i class="fas fa-graduation-cap"></i>
                <span>Course Connect</span>
            </a>

            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <ul class="navbar-nav" id="navbar-nav">
                <li class="nav-item"><a href="student_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
                <li class="nav-item"><a href="my_courses.php" class="nav-link"><i class="fas fa-book-open"></i><span>My Courses</span></a></li>
                <li class="nav-item"><a href="enroll.php" class="nav-link"><i class="fas fa-plus-circle"></i><span>Enroll</span></a></li>
                <li class="nav-item"><a href="results.php" class="nav-link"><i class="fas fa-chart-bar"></i><span>Results</span></a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user-circle"></i><span>Profile</span></a></li>
            </ul>
            
            <div class="nav-right">
                <div class="profile-dropdown">
                    <button class="profile-button" id="profile-btn" title="Profile">
                        <img src="<?php echo $profile_picture; ?>" alt="Profile">
                        <span class="student-name"><?php echo $student_name; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="profile-menu" id="profile-menu">
                        <div class="profile-menu-header">
                            <h4><?php echo $student_name; ?></h4>
                            <p>Student</p>
                        </div>
                        <ul class="profile-menu-list">
                            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                            <li><a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </nav>
    <?php if (!$is_enrolled): ?>
    <div class="enrollment-ticker-container">
        <div class="enrollment-ticker-text">
            <span><i class="fas fa-bullhorn"></i> A fresh start! The new academic session is here. It's time to chart your course for success. Please head over to the 'Enroll' page to choose your subjects for the upcoming semester. Let's make it a great one! ★ </span>
            <span><i class="fas fa-bullhorn"></i> A fresh start! The new academic session is here. It's time to chart your course for success. Please head over to the 'Enroll' page to choose your subjects for the upcoming semester. Let's make it a great one! ★ </span>
        </div>
    </div>
    <?php endif; ?>

    <main class="main-content">
        
        <div class="main-header">
            <h1>Dashboard Overview</h1>
            <div class="quick-actions">
                <button class="action-button secondary" onclick="window.location.href='my_courses.php'"><i class="fas fa-book-open"></i> My Courses</button>
                <button class="action-button" onclick="window.location.href='enroll.php'"><i class="fas fa-plus-circle"></i> Enroll Now</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stats-card" onclick="window.location.href='my_courses.php'">
                <div class="stats-card-icon blue"><i class="fas fa-book-reader"></i></div>
                <div class="card-value" id="enrolled-count">0</div>
                <div class="card-label">Active Courses</div>
                <a href="my_courses.php" class="card-link"><i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="stats-card">
                <div class="stats-card-icon yellow"><i class="fas fa-clock"></i></div>
                <div class="card-value" id="pending-count">0</div>
                <div class="card-label">Awaiting Approval</div>
            </div>
            
            <div class="stats-card" onclick="window.location.href='results.php'">
                <div class="stats-card-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="card-value" id="completed-count">0</div>
                <div class="card-label">Completed Courses</div>
                <a href="results.php" class="card-link"><i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="stats-card" onclick="window.location.href='results.php'">
                <div class="stats-card-icon purple"><i class="fas fa-star"></i></div>
                <div class="card-value" id="cgpa-value">0.00</div>
                <div class="card-label">Current CGPA</div>
                <a href="results.php" class="card-link"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        
        <div class="dashboard-grid-layout">

            <div class="widget-card recent-activity">
                <div class="widget-header">
                    <h3>My Recent Activity</h3>
                    <a href="my_courses.php">View All</a>
                </div>
                <ul class="activity-list" id="activity-list-container">
                    <!-- Activity items will be loaded here by JS -->
                    <li class="activity-item" id="activity-placeholder">
                        <div class="activity-icon"><i class="fas fa-spinner fa-spin"></i></div>
                        <div class="activity-details">
                            <p><strong>Loading activity...</strong></p>
                            <span>Please wait.</span>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="widget-card profile-summary"> <div class="widget-header">
                    <h3>My Profile</h3>
                    <a href="profile.php">Edit Profile</a>
                </div>
                <div class="summary-item">
                    <span class="label">Name</span>
                    <span class="value"><?php echo $student_name; ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Email</span>
                    <span class="value"><?php echo $student_email; ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Registration No.</span>
                    <span class="value"><?php echo $student_reg_no; ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Current Course</span>
                    <span class="value"><?php echo $student_course; ?></span>
                </div>
            </div>

            <div class="widget-card quick-links">
                <div class="widget-header">
                    <h3>Quick Links</h3>
                </div>
                <div class="links-list">
                    <a href="enroll.php" class="link-button"><i class="fas fa-plus-circle"></i>Enroll</a>
                    <a href="my_courses.php" class="link-button"><i class="fas fa-book-open"></i>My Courses</a>
                    <a href="results.php" class="link-button"><i class="fas fa-chart-bar"></i>Results</a>
                    <a href="profile.php" class="link-button"><i class="fas fa-user-circle"></i>Profile</a>
                </div>
            </div>

            <div class="widget-card analytics-chart">
                <div class="widget-header">
                    <h3>My Grade Distribution</h3>
                </div>
                <div class="chart-placeholder">
                    <div>
                        <i class="fas fa-chart-pie"></i>
                        <p>Grade Analytics (Chart)</p>
                    </div>
                </div>
            </div>

        </div>
        </main>

    <script>
        // Login transition effect
        window.addEventListener('load', function() {
            const transition = document.getElementById('loginTransition');
            if (transition) {
                setTimeout(() => {
                    transition.classList.add('fade-out');
                    setTimeout(() => transition.remove(), 800); 
                }, 1200);
            }
        });

        // Success popup removal
        setTimeout(() => {
            let popup = document.getElementById('popup-message');
            if (popup) popup.remove();
        }, 4000);

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > lastScrollTop && scrollTop > 70) { 
                navbar.style.top = "-80px";
            } else {
                navbar.style.top = "0";
            }
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
        }, false);


        // === NEW Navbar & Profile Dropdown JS ===
        document.addEventListener('DOMContentLoaded', function() {
            
            const hamburger = document.getElementById('hamburger');
            const navContainer = document.getElementById('nav-container');
            if (hamburger) {
                hamburger.addEventListener('click', () => {
                    navContainer.classList.toggle('active');
                    hamburger.classList.toggle('active');
                });
            }

            const profileBtn = document.getElementById('profile-btn');
            const profileMenu = document.getElementById('profile-menu');
            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileMenu.classList.toggle('show');
                });
            }
            document.addEventListener('click', (e) => {
                if (profileMenu && profileBtn && !profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.classList.remove('show');
                }
            });

            // --- NEW: Notification Fetcher (Fix #5) ---
            fetchNotifications();

            // --- MODIFIED: Stats update to also refresh activity ---
            fetchDashboardStats();
            setInterval(fetchDashboardStats, 10000); // Now refreshes stats AND activity
        });

        // === NEW: Notification Function (Fix #5) ===
        function fetchNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(notifications => {
                    if (notifications && notifications.length > 0) {
                        notifications.forEach(notif => {
                            showToast(notif.message, notif.type);
                        });
                    }
                })
                .catch(err => console.error('Error fetching notifications:', err));
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            let iconClass = 'fa-info-circle';
            if (type === 'success') iconClass = 'fa-check-circle';
            if (type === 'error') iconClass = 'fa-exclamation-triangle';

            toast.innerHTML = `
                <i class="fas ${iconClass}"></i>
                <div class="toast-message">${message}</div>
            `;
            
            container.appendChild(toast);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                toast.style.animation = 'toastOut 0.5s ease-out forwards';
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        }

        // === MODIFIED: Real-time stats update (also updates activity) ===
        function fetchDashboardStats() {
            fetch('fetch_dashboard_stats.php') 
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Error from server:', data.error);
                    } else {
                        // Update Stats Cards
                        document.getElementById('enrolled-count').textContent = data.enrolled || 0;
                        document.getElementById('pending-count').textContent = data.pending || 0;
                        document.getElementById('completed-count').textContent = data.completed || 0;
                        document.getElementById('cgpa-value').textContent = data.cgpa || '0.00';

                        // Update Activity List
                        const activityList = document.getElementById('activity-list-container');
                        const placeholder = document.getElementById('activity-placeholder');
                        
                        // Clear old activity
                        activityList.innerHTML = ''; 
                        
                        if (data.activity && data.activity.length > 0) {
                            data.activity.forEach(item => {
                                const li = document.createElement('li');
                                li.className = 'activity-item';
                                
                                let icon = 'fa-info-circle';
                                let style = 'background: rgba(59, 130, 246, 0.1); color: var(--accent-blue);';
                                if(item.type === 'success') {
                                    icon = 'fa-check-circle';
                                    style = 'background: rgba(16, 185, 129, 0.1); color: var(--accent-green);';
                                } else if (item.type === 'error') {
                                    icon = 'fa-times-circle';
                                    style = 'background: rgba(239, 68, 68, 0.1); color: var(--accent-red);';
                                } else if (item.type === 'pending') {
                                    icon = 'fa-hourglass-start';
                                    style = 'background: rgba(245, 158, 11, 0.1); color: #d97706;';
                                }

                                li.innerHTML = `
                                    <div class="activity-icon" style="${style}"><i class="fas ${icon}"></i></div>
                                    <div class="activity-details">
                                        <p><strong>${item.title}</strong></p>
                                        <span>${item.description}</span>
                                    </div>
                                `;
                                activityList.appendChild(li);
                            });
                        } else {
                            // Show a "no activity" message
                            activityList.innerHTML = `
                                <li class="activity-item">
                                    <div class="activity-icon"><i class="fas fa-coffee"></i></div>
                                    <div class="activity-details">
                                        <p><strong>No recent activity</strong></p>
                                        <span>Your recent actions will appear here.</span>
                                    </div>
                                </li>
                            `;
                        }
                    }
                })
                .catch(err => {
                    console.error('Error fetching stats:', err);
                    const activityList = document.getElementById('activity-list-container');
                    activityList.innerHTML = `
                        <li class="activity-item">
                            <div class="activity-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--accent-red);">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="activity-details">
                                <p><strong>Error loading activity</strong></p>
                                <span>Could not connect to the server.</span>
                            </div>
                        </li>
                    `;
                });
        }
    </script>
</body>
</html>