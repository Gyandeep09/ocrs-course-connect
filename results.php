<?php
require_once 'session_config.php';
if (!isset($_SESSION['student_id'])) {
    header('Location: student_page.php');
    exit;
}

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "ocrs_db";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch student info for navbar
$student_id = $_SESSION['student_id'];
$stmt_student = $conn->prepare("SELECT name, profile_picture FROM students WHERE student_id = ?");
$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();
$student = $result_student->fetch_assoc();
$stmt_student->close();

$student_name = htmlspecialchars($student['name']);
$profile_picture = !empty($student['profile_picture']) ? htmlspecialchars($student['profile_picture']) : 'uploads/default.png';

// --- START: FINALIZED PHP LOGIC ---

// Step 1: Fetch CURRENTLY enrolled courses
$sql_current = "SELECT c.course_code, c.course_name, c.department, c.semester, e.grade
                FROM enrollments e
                JOIN course_records c ON e.course_id = c.id
                WHERE e.student_id = ? AND e.status = 'Approved'";
$stmt_current = $conn->prepare($sql_current);
$stmt_current->bind_param("i", $student_id);
$stmt_current->execute();
$current_results = $stmt_current->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_current->close();

// Step 2: Fetch ARCHIVED courses from history
$sql_history = "SELECT c.course_code, c.course_name, h.grade, h.session_year
                FROM enrollment_history h
                JOIN course_records c ON h.course_id = c.id
                WHERE h.student_id = ?
                ORDER BY h.session_year DESC, c.course_name ASC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $student_id);
$stmt_history->execute();
$history_results = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();

// Step 3: Combine all results for CGPA calculation
$all_results_for_stats = array_merge($current_results, $history_results);

$completed_courses = 0;
$total_gpa_points = 0;
$grade_to_gpa = [
    'A+' => 4.0, 'A' => 3.7, 'B+' => 3.3, 'B' => 3.0,
    'C+' => 2.5, 'C' => 2.0, 'D' => 1.0, 'F' => 0.0
];

foreach ($all_results_for_stats as $row) {
    if (!empty($row['grade']) && isset($grade_to_gpa[$row['grade']])) {
        $completed_courses++;
        $total_gpa_points += $grade_to_gpa[$row['grade']];
    }
}

$cgpa = ($completed_courses > 0) ? ($total_gpa_points / $completed_courses) : 0;
$total_credits_earned = $completed_courses * 3; // Assuming 3 credits per course

$conn->close();
// --- END: FINALIZED PHP LOGIC ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - Results</title>
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
    /* ============================================
    ADMIN MASTER STYLESHEET (UNIFIED)
    ============================================
    */
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
        --accent-purple: #8b5cf6;
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
        min-height: 100vh;
        padding-top: 70px; /* Space for fixed navbar */
    }

    /* ============================================
    UNIFIED 3-COLUMN GRID NAVBAR
    ============================================ */
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

    /* ============================================
    STUDENT Profile / Right Side
    ============================================ */
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

    /* ============================================
    RESPONSIVE STYLES (Mobile Menu)
    ============================================ */
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


    /* ============================================
    ALL OTHER PAGE STYLES
    ============================================ */

    /* Main Content (Admin Theme) */
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
    
    .page-description {
        color: var(--text-secondary);
        font-size: 16px;
        margin-bottom: 2rem;
        line-height: 1.6;
        max-width: 70ch;
    }

    /* Stats Grid (From Admin Theme) */
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

    /* Section Header */
    h3.section-title {
        font-size: 22px;
        font-weight: 600;
        color: var(--text-primary);
        margin-top: 2.5rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    /* Table container */
    .table-container {
        background: var(--widget-bg);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 15px;
    }

    table th,
    table td {
        padding: 16px 18px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    table th {
        background: #f8fafc;
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    table tr:last-child td {
        border-bottom: none;
    }
    
    table tr:hover {
         background: rgba(59, 130, 246, 0.05);
    }

    /* Course Code Styling */
    .course-code {
        font-family: 'Monaco', 'Menlo', monospace;
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
    }

    /* Grade Styling */
    .grade {
        display: inline-block;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 14px;
        text-align: center;
        min-width: 50px;
        color: #fff;
    }
    .grade.A, .grade.A-plus {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    .grade.B, .grade.B-plus {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }
    .grade.C, .grade.C-plus {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }
    .grade.D, .grade.F {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    .grade.pending {
        background: linear-gradient(135deg, #94a3b8, #64748b);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 40px;
        color: #64748b;
    }
    .empty-state i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 24px;
        display: block;
    }
    .empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 12px;
    }
    .empty-state p {
        font-size: 15px;
        line-height: 1.6;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        body { padding-top: 70px; }
        .main-content {
            margin: 0;
            padding: 1.5rem 1rem;
            border-radius: 0;
        }
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        .page-title h1 { font-size: 24px; }
        .table-container { overflow-x: auto; }
        table { min-width: 600px; }
        table th, table td { padding: 16px 12px; font-size: 14px; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
/* ============================================
    NEW: Dark Mode Theme (CORRECTED)
    ============================================ */

    /* --- NEW: Dark Mode Variables --- */
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
    
    /* Apply dark mode to table */
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
    
    /* Apply dark mode to forms */
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
    
    /* Apply dark mode to other components */
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
    
    /* Dark mode for other dashboard components */
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
    
    /* Dark mode for messages */
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

    /* Dark mode for course page elements */
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
    
    /* Dark mode for modals */
    html[data-theme="dark"] .modal-overlay {
        background-color: rgba(15, 23, 42, 0.8);
    }
    
</style>
</head>
<body>
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
                <li class="nav-item">
                    <a href="student_dashboard.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="my_courses.php" class="nav-link">
                        <i class="fas fa-book-open"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="enroll.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Enroll</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="results.php" class="nav-link active">
                        <i class="fas fa-chart-bar"></i>
                        <span>Results</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
            
            

        </div>
    </nav>
    <div class="main-content">
        <div class="page-header">
             <div class="page-title">
                <i class="fas fa-chart-bar"></i>
                <h1>Academic Results</h1>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-card-icon purple">
                    <i class="fas fa-star"></i>
                </div>
                <div class="card-value"><?= number_format($cgpa, 2); ?></div>
                <div class="card-label">Overall CGPA</div>
            </div>
            <div class="stats-card">
                <div class="stats-card-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-value"><?= $completed_courses; ?></div>
                <div class="card-label">Courses Completed</div>
            </div>
            <div class="stats-card">
                <div class="stats-card-icon blue">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="card-value"><?= $total_credits_earned; ?></div>
                <div class="card-label">Credits Earned</div>
            </div>
            <div class="stats-card">
                <div class="stats-card-icon yellow">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="card-value"><?= count($current_results); ?></div>
                <div class="card-label">Currently Enrolled</div>
            </div>
        </div>
        
        <h3 class="section-title">Current Session</h3>
        <div class="table-container">
            <?php if (!empty($current_results)): ?>
                <table>
                    <thead><tr><th>Course Code</th><th>Course Name</th><th>Grade</th></tr></thead>
                    <tbody>
                        <?php foreach ($current_results as $row): ?>
                            <tr>
                                <td><span class="course-code"><?= htmlspecialchars($row['course_code']) ?></span></td>
                                <td><?= htmlspecialchars($row['course_name']) ?></td>
                                <td><span class="grade <?= strtolower(str_replace(['+','-'],['plus',''],$row['grade'] ?? 'pending')) ?>"><?= htmlspecialchars($row['grade'] ?? 'Pending') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i><h3>No Active Enrollments</h3>
                    <p>You are not enrolled in any courses for the current session.</p>
                </div>
            <?php endif; ?>
        </div>

        <h3 class="section-title">Course History</h3>
        <div class="table-container">
            <?php if (!empty($history_results)): ?>
                <table>
                    <thead><tr><th>Session</th><th>Course Code</th><th>Course Name</th><th>Grade</th></tr></thead>
                    <tbody>
                        <?php foreach ($history_results as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['session_year']) ?></strong></td>
                                <td><span class="course-code"><?= htmlspecialchars($row['course_code']) ?></span></td>
                                <td><?= htmlspecialchars($row['course_name']) ?></td>
                                <td><span class="grade <?= strtolower(str_replace(['+','-'],['plus',''],$row['grade'] ?? '')) ?>"><?= htmlspecialchars($row['grade'] ?? 'N/A') ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i><h3>No Completed Course History</h3>
                    <p>Your course history will appear here after you complete a session.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // === NEW Navbar & Profile Dropdown JS ===
        document.addEventListener("DOMContentLoaded", function() {
            // --- 1. Collapsible Navbar (Hamburger) ---
            const hamburger = document.getElementById('hamburger');
            const navContainer = document.getElementById('nav-container');
            if (hamburger) {
                hamburger.addEventListener('click', () => {
                    navContainer.classList.toggle('active');
                    hamburger.classList.toggle('active');
                });
            }

            // --- 2. Profile Dropdown ---
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
            
            // --- 3. Navbar Scroll Effect ---
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
        });
    </script>
</body>
</html>