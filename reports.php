<?php
require_once 'session_config.php';

// Protect this page: if the admin is not logged in, redirect to the login page.
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_page.php');
    exit;
}
// reports.php

// --- DB connection ---
$conn = new mysqli("localhost", "root", "", "ocrs_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Set mysqli to throw exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // --- Count pending for sidebar badge (to keep UI consistent) ---
    $pending_count = 0;
    $pending_result = $conn->query("SELECT COUNT(*) AS cnt FROM enrollments WHERE status='Pending'");
    if ($pending_result && $pending_row = $pending_result->fetch_assoc()) {
        $pending_count = (int)$pending_row['cnt'];
    }

    // --- NEW: Fetch data for filter dropdowns ---
    
    // Fetch Departments
    $filter_departments = [];
    $dept_sql = "SELECT DISTINCT c.department 
                 FROM enrollments e 
                 JOIN course_records c ON e.course_id = c.id 
                 WHERE e.status = 'Approved' AND c.department IS NOT NULL AND c.department != '' 
                 ORDER BY c.department ASC";
    $dept_result = $conn->query($dept_sql);
    if($dept_result) {
        while($dept = $dept_result->fetch_assoc()) { $filter_departments[] = $dept['department']; }
    }

    // Fetch Courses
    $filter_courses = [];
    $course_sql = "SELECT DISTINCT c.course_name, c.course_code 
                   FROM enrollments e 
                   JOIN course_records c ON e.course_id = c.id 
                   WHERE e.status = 'Approved' 
                   ORDER BY c.course_name ASC";
    $course_result = $conn->query($course_sql);
    if($course_result) {
        while($course = $course_result->fetch_assoc()) { $filter_courses[] = $course; }
    }

    // Fetch Semesters
    $filter_semesters = [];
    $sem_sql = "SELECT DISTINCT c.semester 
                FROM enrollments e 
                JOIN course_records c ON e.course_id = c.id 
                WHERE e.status = 'Approved' AND c.semester IS NOT NULL AND c.semester != '' 
                ORDER BY c.semester ASC";
    $sem_result = $conn->query($sem_sql);
    if($sem_result) {
        while($sem = $sem_result->fetch_assoc()) { $filter_semesters[] = $sem['semester']; }
    }
    
    // --- Get filter parameters from URL ---
    $dept_filter = $_GET['department'] ?? '';
    $course_filter = $_GET['course_code'] ?? '';
    $sem_filter = $_GET['semester'] ?? '';

    // --- Fetch report rows (students + courses + enrollment date) ---
    $sql = "
        SELECT
            e.id,
            s.name,
            s.student_id,
            c.course_name,
            c.course_code,
            COALESCE(c.department, s.department, '') AS department,
            COALESCE(c.semester, s.semester, '') AS semester,
            e.enroll_date
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN course_records c  ON e.course_id = c.id
        WHERE e.status = 'Approved'
    ";
    
    $params = [];
    $types = "";

    if (!empty($dept_filter)) {
        $sql .= " AND c.department = ?";
        $params[] = $dept_filter;
        $types .= "s";
    }
    if (!empty($course_filter)) {
        $sql .= " AND c.course_code = ?";
        $params[] = $course_filter;
        $types .= "s";
    }
    if (!empty($sem_filter)) {
        $sql .= " AND c.semester = ?";
        $params[] = $sem_filter;
        $types .= "s";
    }

    $sql .= " ORDER BY e.enroll_date DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $error = null;

} catch (mysqli_sql_exception $e) {
    $result = false;
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - Reports</title>
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
        min-height: 100vh;
        padding-top: 70px; /* Space for fixed navbar */
    }

    /* ============================================
    NEW STATIC GRID NAVBAR
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
        grid-template-columns: 1fr auto 1fr; /* 3-column layout */
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
    
    /* Pending Badge for Navbar */
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
    NEW STYLES for reports.php
    ============================================ */
    
    /* Main Content */
    .main-content {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
        animation: fadeInUp 0.6s ease-out;
        /* background, shadow, etc. removed to match other pages */
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
        margin-top: 8px; /* Added margin */
        line-height: 1.6;
        max-width: 70ch;
    }
    
    /* Error messages */
    .message.error {
        margin: 20px 0;
        padding: 16px 20px;
        border-radius: 12px;
        border: 1px solid var(--accent-red);
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        color: #991b1b;
        font-size: 15px;
        font-weight: 500;
        animation: slideInDown 0.4s ease-out;
    }

    @keyframes slideInDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Table container */
    .table-container {
        background: var(--widget-bg);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        margin-top: 20px;
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
    
    table td i {
        margin-right: 8px;
        color: var(--text-secondary);
        width: 16px;
        text-align: center;
    }
    
    /* --- Styles for Filter Card --- */

    /* Action Button (from course_management) */
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

    /* Widget Card (master style) */
    .widget-card {
        background: var(--widget-bg);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
    }
    
    /* Filter Form */
    .filter-form {
        /* This is now a widget-card */
        margin-top: 1.5rem; /* Was 24px */
        display: none; /* Hidden by default */
    }

    .form-title {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem; /* Was 24px */
    }
    .form-title h3 {
        color: var(--text-primary);
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }
    .form-title i {
        color: var(--accent-blue);
        font-size: 20px;
        margin-right: 12px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        align-items: end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

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

    .form-group select {
        padding: 14px 16px;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        outline: none;
        font-size: 15px;
        font-family: "Spartan", sans-serif; /* Match theme */
        background: #f8fafc; /* Match theme */
        color: var(--text-primary);
        transition: all 0.3s ease;
    }

    .form-group select:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        background: #fff;
    }

    .filter-btn {
        /* This now uses .action-button styles */
        width: 100%;
        justify-content: center; /* Center text/icon */
    }

    .filter-btn.clear {
        background: var(--text-secondary); /* Use theme color */
        box-shadow: 0 4px 12px rgba(100, 116, 139, 0.2);
    }
    .filter-btn.clear:hover {
        background: var(--text-primary);
        box-shadow: 0 8px 20px rgba(100, 116, 139, 0.3);
    }
    
    /* --- END: Filter Card Styles --- */

    /* Responsive adjustments */
    @media (max-width: 768px) {
        body { padding-top: 70px; }
        .main-content {
            margin: 10px;
            padding: 24px 16px;
            border-radius: 12px;
        }
        .main-content h1 { font-size: 24px; }
        .table-container { overflow-x: auto; }
        .form-grid {
            grid-template-columns: 1fr; /* Stack filters on mobile */
        }
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
                    <a href="reports.php" class="nav-link active">
                        <i class="fas fa-chart-line"></i>
                        <span>Reports</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="main-content">
        <div class="page-header"> <div class="page-title"> <h1><i class="fas fa-chart-line"></i> Enrollment Reports</h1>
            </div>
            </div>
        
        <p class="page-description">
            View and filter all approved student enrollments. Use the filter button to narrow your results by department, course, or semester.
        </p>

        <button id="filterToggleBtn" class="action-button secondary">
            <i class="fas fa-filter"></i> Filter Reports
        </button>

        <div class="filter-form widget-card" id="filterReportCard">
            <div class="form-title">
                <i class="fas fa-filter"></i>
                <h3>Filter Reports</h3>
            </div>
            <form method="GET" action="reports.php" id="filterForm">
                <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i>Department</label>
                        <select name="department" id="departmentFilter">
                            <option value="">All Departments</option>
                            <?php foreach ($filter_departments as $dept_name): ?>
                                <option value="<?= htmlspecialchars($dept_name); ?>" <?= ($dept_filter == $dept_name) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-book"></i>Course</label>
                        <select name="course_code" id="courseFilter">
                            <option value="">All Courses</option>
                            <?php foreach ($filter_courses as $course): ?>
                                <option value="<?= htmlspecialchars($course['course_code']); ?>" <?= ($course_filter == $course['course_code']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['course_name']); ?> (<?= htmlspecialchars($course['course_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i>Semester</label>
                        <select name="semester" id="semesterFilter">
                            <option value="">All Semesters</option>
                            <?php foreach ($filter_semesters as $sem_name): ?>
                                <option value="<?= htmlspecialchars($sem_name); ?>" <?= ($sem_filter == $sem_name) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sem_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>&nbsp;</label> 
                        <button type="submit" class="action-button filter-btn"><i class="fas fa-search"></i> Filter</button>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <a href="reports.php" class="action-button filter-btn clear"><i class="fas fa-times"></i> Clear</a>
                    </div>
                </div>
            </form>
        </div>


        <?php if ($error): ?>
            <div class="message error" style="margin-top: 24px;">
                <i class="fas fa-exclamation-triangle"></i> <strong>Database Error:</strong> <?= htmlspecialchars($error) ?>. Please ensure tables exist and column names match.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Course</th>
                            <th>Course Code</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Enroll Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows === 0): ?>
                            <tr><td colspan="8" style="text-align:center; padding: 20px; color: #64748b;"><i class="fas fa-info-circle"></i> No approved enrollments found matching your criteria.</td></tr>
                        <?php elseif ($result): ?>
                            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                                    <td><?= htmlspecialchars($row['course_code']) ?></td>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td><?= htmlspecialchars($row['semester']) ?></td>
                                    <td><?= (new DateTime($row['enroll_date']))->format('Y-m-d H:i') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
    
    <script>
    // --- START: Navbar Scroll Effect ---
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
    // --- END: Navbar Scroll Effect ---


    // --- START: Collapsible Navbar (Hamburger) ---
    const hamburger = document.getElementById('hamburger');
    const navContainer = document.getElementById('nav-container');

    if (hamburger) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navContainer.classList.toggle('active');
        });
    }
    // --- END: Collapsible Navbar ---


    // --- START: Page-Specific Scripts ---
    document.addEventListener('DOMContentLoaded', function() {
        // --- Filter Toggle Script ---
        const filterBtn = document.getElementById('filterToggleBtn');
        const filterCard = document.getElementById('filterReportCard');
        
        filterBtn.addEventListener('click', () => {
            const isHidden = filterCard.style.display === 'none' || filterCard.style.display === '';
            filterCard.style.display = isHidden ? 'block' : 'none';
        });

        // Check URL params to see if we should show the card on load
        const urlParams = new URLSearchParams(window.location.search);
        const department = urlParams.get('department');
        const course = urlParams.get('course_code');
        const semester = urlParams.get('semester');

        if (department || course || semester) {
            filterCard.style.display = 'block';
        }

        // --- Pending Count Script ---
        function checkPendingCount(){
            fetch('pending_count.php') // Assuming this file exists
                .then(r => r.text())
                .then(cnt => {
                    const count = parseInt(cnt || "0");
                    const badge = document.querySelector(".pending-badge");
                    if (badge) {
                        badge.innerText = count > 0 ? count : "";
                        badge.style.display = count > 0 ? "flex" : "none";
                    }
                })
                .catch(err => console.error('Error fetching pending count:', err));
        }
        setInterval(checkPendingCount, 10000);
        checkPendingCount(); // Initial check
    });
    </script>
</body>
</html>