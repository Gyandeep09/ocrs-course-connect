<?php
require_once 'session_config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_page.php');
    exit;
}
$conn = new mysqli("localhost", "root", "", "ocrs_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    if (isset($_POST['add_department'])) {
        $department_name = $_POST['department_name'];
        $department_code = $_POST['department_code'];
        $check_stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_name = ? OR department_code = ?");
        $check_stmt->bind_param("ss", $department_name, $department_code);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "Error: A department with that name or code already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO departments (department_name, department_code) VALUES (?, ?)");
            $stmt->bind_param("ss", $department_name, $department_code);
            $stmt->execute();
            $_SESSION['success_message'] = "Department added successfully!";
        }
        header("Location: department.php");
        exit;
    }
    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['success_message'] = "Department deleted successfully!";
        header("Location: department.php");
        exit;
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        $_SESSION['error_message'] = "Error: A department with that name or code already exists.";
    } else {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "Database error: Could not complete the request.";
    }
    header("Location: department.php");
    exit;
}
$result = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - Departments</title>
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

    /* ============================================
    NEW STATIC GRID NAVBAR (Replaces old navbar logic)
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
    
    /* This applies to BOTH .nav-container and .navbar-container */
    .nav-container, .navbar-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 2rem;
        height: 60px;
        
        /* --- THIS IS THE CORE CHANGE --- */
        display: grid;
        grid-template-columns: 1fr auto 1fr; /* 3-column layout: [Left] [Center] [Right] */
        align-items: center;
        /* --- END OF CORE CHANGE --- */
    }
    
    /* This applies to BOTH .nav-brand and .navbar-brand */
    .nav-brand, .navbar-brand {
        grid-column: 1 / 2; /* Place in Column 1 */
        justify-self: start; /* Align to the left of the column */
        
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

    /* Hamburger (used on all pages for mobile) */
    .hamburger {
        grid-column: 3 / 4; /* Place in Column 3 */
        justify-self: end; /* Align to the right of the column */
        
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
    
    /* This applies to BOTH .nav-menu and .navbar-nav */
    .nav-menu, .navbar-nav {
        grid-column: 2 / 3; /* Place in Column 2 */
        justify-self: center; /* Center it within the column */
        
        display: flex; /* This is for the <li> items inside */
        align-items: center;
        list-style: none;
        gap: 0.5rem;
        transition: all 0.4s ease-out;
    }

    /* Nav Links (Shared) */
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

    /* Right-side content (Dashboard only) */
    .nav-right {
        grid-column: 3 / 4; /* Place in Column 3 */
        justify-self: end; /* Align to the right of the column */
        
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    /* --- Search Bar & Profile (Shared styles, only used by dashboard) --- */
    .search-bar { position: relative; min-width: 300px; }
    .search-bar input {
        width: 100%; padding: 10px 14px 10px 36px;
        border-radius: 8px; border: 1px solid var(--border-color);
        background: #fff; font-size: 15px; transition: all 0.3s ease;
    }
    .search-bar input:focus {
        outline: none; border-color: var(--accent-blue);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .search-bar i {
        position: absolute; left: 12px; top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
    }
    .icon-button {
        position: relative; background: transparent; border: none;
        color: var(--text-secondary); font-size: 20px;
        cursor: pointer; transition: color 0.3s ease;
    }
    .icon-button:hover { color: var(--text-primary); }
    .icon-button .badge {
        position: absolute; top: -5px; right: -8px;
        background: var(--accent-red); color: white;
        font-size: 10px; font-weight: 600;
        width: 18px; height: 18px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
    .profile-dropdown { position: relative; }
    .profile-button {
        display: flex; align-items: center; gap: 10px;
        background: transparent; border: none; cursor: pointer;
    }
    .profile-button img {
        width: 36px; height: 36px; border-radius: 50%;
        object-fit: cover; border: 2px solid var(--border-color);
    }
    .profile-button .admin-name {
        font-weight: 600; color: var(--text-primary); font-size: 15px;
    }
    .profile-button i { color: var(--text-secondary); }
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
    @media (max-width: 1050px) { /* Breakpoint to collapse nav */
        /* Revert container to flex for simple mobile layout */
        .nav-container, .navbar-container {
            display: flex;
            justify-content: space-between;
        }
        
        .nav-menu, .navbar-nav { 
            display: none; /* Hide desktop menu */
            grid-column: auto; /* Reset grid */
            justify-self: auto;
        }
        
        .nav-right {
             display: none; /* Hide desktop profile/search */
             grid-column: auto;
             justify-self: auto;
        }
        
        .hamburger { 
            display: flex; /* Show hamburger */
            grid-column: auto;
            justify-self: auto;
        }

        /* This is the class added by JS to show the mobile menu */
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


    /* ============================================
    ALL OTHER PAGE STYLES
    (Keep all other styles for main content, forms, tables, etc., below this line)
    ============================================ */

    /* ... all other styles from department.php ... */
    /* ... all other styles from course_management.php ... */
    /* ... all other styles from admin_dashboard.php ... */
    
    /* NOTE: To make this truly a single file, you would merge
       all unique styles from the three files below this point.
       For now, this block just contains the shared navbar logic.
       The rest of your original page styles (main-content, etc.)
       should follow this new navbar CSS.
    */
    
    /* ============================================
    Main Content
    ============================================ */
    .main-content {
        padding: 2rem;
        max-width: 1400px; /* Adjusted to 1400px for consistency */
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
    .message.error {
        background: linear-gradient(135deg, #fef2f2, #fecaca);
        color: #991b1b;
        border-color: rgba(239, 68, 68, 0.2);
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    /* Page Actions / Buttons */
    .page-actions { display: flex; gap: 12px; }
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
        height: fit-content; /* Added for consistency */
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
    /* Green button from department.php */
    .action-button.green {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }
    .action-button.green:hover {
        background: linear-gradient(135deg, #059669, #047857);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
    }


    /* Widget/Form Styles */
    .widget-card {
        background: var(--widget-bg);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        margin-bottom: 2rem; /* Added for consistency */
    }
    .widget-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .widget-header h3 {
        color: var(--text-primary);
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }
    .widget-header i {
        color: var(--accent-green);
        font-size: 20px;
        margin-right: 12px;
    }
    .widget-header i.fa-filter { color: var(--accent-blue); } /* From course_management */

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
    .form-group label i {
        color: var(--accent-blue);
        margin-right: 8px;
        font-size: 14px;
    }
    .form-group input,
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
    .form-group input:focus,
    .form-group select:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        background: #fff;
    }

    /* Collapsible Form (from course_management) */
    .collapsible-form {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-out, margin-top 0.5s ease-out;
    }
    .collapsible-form.show {
        margin-top: 1.5rem;
        max-height: 1000px; /* arbitrary high value */
    }
    
    /* ============================================
    UNIQUE STYLES for course_management.php
    ============================================ */
    .course-accordion-list { margin-top: 2rem; }
    .department-accordion {
        background: var(--widget-bg);
        border-radius: 16px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }
    .department-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24px 32px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    .department-header:hover { background: rgba(59, 130, 246, 0.05); }
    .department-header.active { border-bottom: 1px solid var(--border-color); }
    .department-header h3 {
        color: var(--text-primary);
        font-size: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .department-header h3 i { color: var(--accent-blue); font-size: 20px; }
    .department-header .accordion-icon {
        font-size: 18px;
        color: var(--text-secondary);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .department-header.active .accordion-icon { transform: rotate(90deg); }
    .department-panel {
        max-height: 0; 
        overflow-y: auto; 
        transition: max-height 0.4s ease-out;
    }
    .department-panel.active { max-height: 400px; }
    .course-list-container { padding: 0 1.5rem; }
    .course-card { border-bottom: 1px solid var(--border-color); }
    .course-card:last-child { border-bottom: none; }
    .course-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 8px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    .course-card-header:hover { background: rgba(59, 130, 246, 0.03); }
    .course-card-header .course-name { font-size: 17px; font-weight: 600; color: #374151; }
    .course-card-header .accordion-icon {
        font-size: 16px;
        color: var(--text-secondary);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .course-card-header.active .accordion-icon { transform: rotate(90deg); }
    .course-card-body {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-out;
        padding: 0 8px; 
    }
    .course-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
        padding: 1rem 0;
    }
    .detail-item { display: flex; flex-direction: column; gap: 6px; }
    .detail-item .label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
    }
    .detail-item .value { font-size: 15px; font-weight: 500; color: var(--text-primary); }
    .course-code {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: #ffffff;
        padding: 4px 8px; border-radius: 6px;
        font-size: 12px; font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }
    .credits-badge {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        padding: 4px 8px; border-radius: 6px;
        font-size: 12px; font-weight: 600;
        min-width: 40px; text-align: center;
        display: inline-block;
    }
    .seat-badge {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #ffffff;
        padding: 4px 8px; border-radius: 6px;
        font-size: 12px; font-weight: 600;
        min-width: 40px; text-align: center;
        display: inline-block;
    }
    .btn-delete {
        background: rgba(239, 68, 68, 0.1);
        color: var(--accent-red);
        border: none;
        border-radius: 8px;
        width: 36px;
        height: 36px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0.5rem 0 1rem;
    }
    .btn-delete:hover {
        background: var(--accent-red);
        color: #fff;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
        background: var(--widget-bg);
        border-radius: 16px;
        box-shadow: var(--shadow-sm);
        margin-top: 2rem;
    }
    .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; }
    .empty-state h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #475569; }
    .empty-state p { font-size: 15px; }

    /* ============================================
    UNIQUE STYLES for department.php
    ============================================ */
    .table-container {
        background: var(--widget-bg);
        border-radius: 16px;
        overflow-x: auto;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
    }
    .table-header {
        padding: 1.5rem 2rem 1.25rem;
        border-bottom: 1px solid var(--border-color);
    }
    .table-header h3 {
        color: var(--text-primary);
        font-size: 18px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .table-header h3 i { color: var(--accent-blue); font-size: 18px; }
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }
    table th {
        background: #f8fafc;
        color: var(--text-secondary);
        padding: 16px 24px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        border-bottom: 1px solid var(--border-color);
    }
    table td {
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
        color: #475569;
        font-size: 15px;
        font-weight: 500;
    }
    table tr:last-child td { border-bottom: none; }
    table tbody tr { transition: all 0.3s ease; }
    table tbody tr:hover { background: rgba(59, 130, 246, 0.05); }
    /* btn-delete is already defined above */
    
    /* Empty state from department.php (merged with course_management) */
    .table-container .empty-state {
        margin-top: 0;
        box-shadow: none;
    }

    /* ============================================
    UNIQUE STYLES for admin_dashboard.php
    ============================================ */
    .main-header { /* Dashboard-specific header */
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .main-header h1 { font-size: 28px; font-weight: 700; color: var(--text-primary); }
    .quick-actions { display: flex; gap: 12px; }
    
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
    .dashboard-grid-layout .widget-card { margin-bottom: 0; } /* Override default margin */
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

    /* Login Transition */
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
        border: 3px solid rgba(16, 185, 129, 0.2);
        border-top: 3px solid #10b981;
        border-radius: 50%;
        animation: spin 1s linear infinite; margin: 0 auto 24px;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .transition-text { font-size: 20px; font-weight: 600; margin-bottom: 8px; color: #ffffff; }
    .transition-subtext { font-size: 15px; color: #cbd5e1; }

    /* Success Popup */
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

    /* Floating Action Button & Hidden Widget */
    .session-fab-container { position: fixed; bottom: 25px; right: 25px; z-index: 1050; }
    .session-fab {
        width: 56px; height: 56px;
        background: linear-gradient(135deg, var(--accent-blue), #6366f1);
        color: white; border-radius: 50%; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        transition: all 0.3s ease;
    }
    .session-fab:hover {
        transform: translateY(-3px) scale(1.1);
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.5);
    }
    .session-fab i { font-size: 20px; transition: transform 0.3s ease; }
    .session-fab.active i { transform: rotate(135deg); }
    .new-session-widget {
        position: absolute; bottom: 70px; right: 0;
        background: #ffffff; border-radius: 16px; padding: 20px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        border: 1px solid #e2e8f0; width: 380px;
        opacity: 0; visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .new-session-widget.visible { opacity: 1; visibility: visible; transform: translateY(0); }
    .new-session-widget h4 {
        font-size: 16px; font-weight: 600; color: #1e293b;
        margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px;
    }
    .new-session-widget h4 i { color: #10b981; }
    .widget-input-group { display: flex; gap: 10px; }
    .widget-input-group input {
        flex-grow: 1; border: 2px solid #e2e8f0; padding: 10px 14px;
        border-radius: 8px; font-size: 15px; outline: none; transition: all 0.3s ease;
    }
    .widget-input-group input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }
    .widget-input-group button {
        border: none; background: linear-gradient(135deg, #ef4444, #b91c1c);
        color: white; padding: 10px 16px; border-radius: 8px;
        font-weight: 600; font-size: 15px; cursor: pointer;
        transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;
    }
    .widget-input-group button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(239, 68, 68, 0.3);
    }

    /* Confirmation Modal CSS */
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


    /* ============================================
    FINAL RESPONSIVE TWEAKS (Shared)
    ============================================ */
    @media (max-width: 1200px) { /* Dashboard grid responsive */
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
        .form-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .form-grid .action-button { justify-content: center; }
        
        /* Specific tweaks */
        #filterCourseForm .form-grid { grid-template-columns: 1fr; }
        .department-header { padding: 20px 16px; }
        .department-header h3 { font-size: 18px; }
        .course-list-container { padding: 0 8px; }
        .table-header { padding: 1.5rem; }
        table th, table td { padding: 12px 16px; font-size: 14px; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }
    
    @media (max-width: 480px) {
        .profile-button .admin-name { display: none; }
        .stats-grid { grid-template-columns: 1fr; }
    }

</style>
</head>
<body>
    
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
                    <a href="admin_dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="department.php" class="nav-link active">
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
            
            
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-building"></i>
                <h1>Department Management</h1>
            </div>
        </div>
        
       <p class="page-description">
            Manage academic departments in your institution. Add new departments or remove old ones from this panel.
        </p>

        <?php if ($success_message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message error">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="widget-card">
            <div class="widget-header">
                <i class="fas fa-plus-circle"></i>
                <h3>Add New Department</h3>
            </div>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i>Department Name</label>
                        <input type="text" name="department_name" placeholder="Enter department name" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-code"></i>Department Code</label>
                        <input type="text" name="department_code" placeholder="Enter department code" required>
                    </div>
                    
                    <button type="submit" name="add_department" class="action-button">
                        <i class="fas fa-plus"></i>
                        Add Department
                    </button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-list-ul"></i> All Departments</h3>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Department ID</th>
                        <th>Department Name</th>
                        <th>Department Code</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $row['department_id'] ?></strong></td>
                        <td><?= htmlspecialchars($row['department_name']) ?></td>
                        <td><code><?= htmlspecialchars($row['department_code']) ?></code></td>
                        <td style="text-align: center;">
                            <button class="btn-delete" 
                               onclick="showDeleteModal(event, 'department.php?delete=<?= $row['department_id'] ?>')"
                               title="Delete Department">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <h3>No Departments Found</h3>
                <p>Start by adding your first department using the form above.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="deleteModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
      <div style="background-color: #fff; margin: 15% auto; padding: 25px; border-radius: 16px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); max-width: 400px; text-align: center;">
        <h3 style="margin-bottom: 15px; font-size: 22px;">Confirm Deletion</h3>
        <p style="margin-bottom: 25px; color: #475569;">Are you sure you want to delete this department? This action cannot be undone.</p>
        <div style="display: flex; justify-content: center; gap: 15px;">
          <button id="cancelBtn" style="padding: 10px 20px; border-radius: 8px; border: none; background: #e2e8f0; font-weight: 600; cursor: pointer;">Cancel</button>
          <a id="confirmDeleteBtn" href="#" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; background: #ef4444; color: white; font-weight: 600;">Delete</a>
        </div>
      </div>
    </div>

<script>
    // --- START: Navbar Scroll Effect ---
    let lastScrollTop = 0;
    const navbar = document.getElementById('navbar');

    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 70) { 
            // Scrolling Down
            navbar.style.top = "-80px";
        } else {
            // Scrolling Up
            navbar.style.top = "0";
        }
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
    }, false);
    // --- END: Navbar Scroll Effect ---


    // --- START: Collapsible Navbar (Hamburger) ---
    const hamburger = document.getElementById('hamburger');
    const navContainer = document.getElementById('nav-container');

    if (hamburger) {
        hamburger.addEventListener('click', () => {
            // Toggles 'active' on both the hamburger and the nav container
            hamburger.classList.toggle('active');
            navContainer.classList.toggle('active');
        });
    }
    // --- END: Collapsible Navbar ---


    // --- Existing Department.php JavaScript ---
    
    // Active nav link highlight (already in PHP)

    // Form validation and enhancement
    document.querySelector('form').addEventListener('submit', function(e) {
        const departmentName = this.department_name.value.trim();
        const departmentCode = this.department_code.value.trim();
        
        if (!departmentName || !departmentCode) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return;
        }
        
        if (departmentCode.length < 2 || departmentCode.length > 10) {
            e.preventDefault();
            alert('Department code should be between 2-10 characters.');
            return;
        }
    });

    // --- Modal JavaScript (Unchanged) ---
    const deleteModal = document.getElementById('deleteModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    function showDeleteModal(event, deleteUrl) {
        event.preventDefault();
        confirmDeleteBtn.href = deleteUrl;
        deleteModal.style.display = 'block';
    }

    cancelBtn.onclick = function() {
        deleteModal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == deleteModal) {
            deleteModal.style.display = 'none';
        }
    }
</script>
</body>
</html>