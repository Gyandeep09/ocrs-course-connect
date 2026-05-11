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

$student_name = htmlspecialchars($student['name'] ?? 'Student');
$profile_picture = !empty($student['profile_picture']) ? htmlspecialchars($student['profile_picture']) : 'uploads/default.png';

$message = "";

// --- Global Enrollment Check ---
$has_active_enrollment = false;
$check_existing_global = $conn->prepare("SELECT 1 FROM enrollments WHERE student_id=? AND status IN ('Pending', 'Approved')");
$check_existing_global->bind_param("i", $student_id);
$check_existing_global->execute();
$result_existing_global = $check_existing_global->get_result();
if ($result_existing_global->num_rows > 0) {
    $has_active_enrollment = true;
}
$check_existing_global->close();


// --- MODIFIED: Handle enrollment request ---
if (isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);
    
    // Get student's submitted data
    $prev_qual_input = trim($_POST['previous_qualification'] ?? '');
    $prev_qual_other = trim($_POST['previous_qualification_other'] ?? '');
    
    // If 'Others' was selected, use the text input value
    $prev_qual = ($prev_qual_input === 'Others') ? $prev_qual_other : $prev_qual_input;
    
    $prev_perc = !empty($_POST['previous_percentage']) ? floatval($_POST['previous_percentage']) : NULL;
    $prev_subj = trim($_POST['previous_subjects'] ?? ''); // New subjects field
    $prev_board = trim($_POST['previous_board'] ?? ''); // ADD THIS
    $prev_inst = trim($_POST['previous_institution'] ?? ''); // ADD THIS

    // Save eligibility data to the student's record
    // MODIFIED: Added previous_subjects
   $update_stmt = $conn->prepare("UPDATE students SET 
                                        previous_qualification=?, 
                                        previous_percentage=?, 
                                        previous_subjects=?,
                                        previous_board=?, 
                                        previous_institution=?
                                    WHERE student_id=?");
   $update_stmt->bind_param("sdsssi", $prev_qual, $prev_perc, $prev_subj, $prev_board, $prev_inst, $student_id);
    $update_stmt->execute();
    $update_stmt->close();


    if ($has_active_enrollment) {
        $message = "You can only enroll in one course at a time.";
    } else {
        // Check seat availability
        $seat_check = $conn->prepare("
            SELECT seat_limit - (SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND status = 'Approved') AS seats_left
            FROM course_records 
            WHERE id = ?");
        $seat_check->bind_param("ii", $course_id, $course_id);
        $seat_check->execute();
        $seat_result = $seat_check->get_result()->fetch_assoc();

        if ($seat_result['seats_left'] <= 0) {
            $message = "This course is full. No seats available.";
        } else {
            // Insert enrollment request
            $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, enroll_date, status) VALUES (?, ?, NOW(), 'Pending')");
            $stmt->bind_param("ii", $student_id, $course_id);
            $message = $stmt->execute() ? "Enrollment request sent successfully!" : "Error sending request.";
            $stmt->close();
            
            if (strpos($message, 'successfully') !== false) {
                $has_active_enrollment = true; // Refresh status
            }
        }
        $seat_check->close();
    }
}

// --- MODIFIED: Fetch courses, now including eligibility data for the JS ---
$dept_filter = isset($_GET['dept']) ? $_GET['dept'] : '';
$sem_filter = isset($_GET['sem']) ? $_GET['sem'] : ''; 
$sql = "
    SELECT 
        c.id, 
        c.course_code, 
        c.course_name, 
        c.department, 
        c.semester, 
        c.seat_limit,
        (SELECT COUNT(*) FROM enrollments e2 WHERE e2.course_id = c.id AND e2.status = 'Approved') AS enrolled_count,
        e.status AS enrollment_status
    FROM course_records c
    LEFT JOIN enrollments e 
        ON e.course_id = c.id AND e.student_id = ?
    WHERE (? = '' OR c.department = ?) AND (? = '' OR c.semester = ?)
    ORDER BY c.department, c.course_code
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $student_id, $dept_filter, $dept_filter, $sem_filter, $sem_filter);
$stmt->execute();
$courses = $stmt->get_result();

$courses_by_department = [];
if ($courses->num_rows > 0) {
    while ($row = $courses->fetch_assoc()) {
        $courses_by_department[$row['department']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - Enroll</title>
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
    /* ... [ALL YOUR EXISTING CSS FROM enroll.php (Root, Body, Navbar, Page Content, Messages, Filters)] ... */
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
        --readonly-bg: #eef2ff; /* For readonly inputs */
        --readonly-color: #4338ca;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    body {
        font-family: "Inter", "Spartan", sans-serif; /* Added Inter as primary */
        background: var(--bg-color);
        color: var(--text-primary);
        margin: 0;
        min-height: 100vh;
        padding-top: 70px; /* Space for fixed navbar */
    }

    /* ============================================
    UNIFIED 3-COLUMN GRID NAVBAR (Unchanged)
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
    
    /* Nav Right (Profile) */
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

    /* Mobile Nav */
    @media (max-width: 1050px) { 
        .nav-container, .navbar-container { display: flex; justify-content: space-between; }
        .nav-menu, .navbar-nav { display: none; grid-column: auto; justify-self: auto; }
        .nav-right { display: none; grid-column: auto; justify-self: auto; }
        .hamburger { display: flex; grid-column: auto; justify-self: auto; }
        .nav-container.active .navbar-nav,
        .nav-container.active .nav-menu {
            display: flex; flex-direction: column; align-items: flex-start;
            position: absolute; top: 70px; left: 0; width: 100%;
            background: var(--navbar-bg); box-shadow: var(--shadow-md);
            padding: 1rem; animation: slideDown 0.3s ease-out forwards; max-height: 500px;
        }
        .nav-container.active .navbar-nav .nav-link,
        .nav-container.active .nav-menu .nav-link { width: 100%; padding: 12px; }
        .nav-container.active .navbar-nav .nav-link.active::after,
        .nav-container.active .navbar-nav .nav-link:hover::after,
        .nav-container.active .nav-menu .nav-link.active::after,
        .nav-container.active .nav-menu .nav-link:hover::after {
            width: 30px; left: 16px; transform: translateX(0); bottom: 4px;
        }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    }

    /* Page Content */
    .content-wrapper {
        padding: 2rem; max-width: 1400px; margin: 0 auto;
        font-size: 16px; animation: fadeInUp 0.6s ease-out;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .page-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1.5rem; padding-bottom: 1.5rem;
        border-bottom: 2px solid var(--border-color);
    }
    .page-title { display: flex; align-items: center; }
    .page-header h1 {
        color: var(--text-primary); font-size: 28px; font-weight: 700;
        letter-spacing: -0.025em; margin: 0;
    }
    .page-header i { font-size: 32px; color: var(--accent-blue); margin-right: 16px; }
    .page-description {
        color: var(--text-secondary); font-size: 16px;
        margin-bottom: 2rem; line-height: 1.6; max-width: 70ch;
    }
    .page-actions { display: flex; gap: 12px; }
    .action-button {
        background: var(--accent-blue); color: #ffffff; border: none;
        padding: 12px 20px; border-radius: 10px; font-size: 15px; font-weight: 600;
        cursor: pointer; transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        display: flex; align-items: center; gap: 8px; text-decoration: none;
    }
    .action-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
    }
    .action-button.secondary {
        background: var(--widget-bg); color: var(--text-primary);
        border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);
    }
    .action-button.secondary:hover { background: var(--bg-color); box-shadow: var(--shadow-md); }

    /* Messages */
    .message {
        margin-bottom: 24px; padding: 16px 20px; border-radius: 12px;
        font-size: 15px; font-weight: 500; border: 1px solid transparent;
        animation: slideIn 0.4s ease-out;
    }
    .message.success {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        color: #065f46; border-color: rgba(34, 197, 94, 0.2);
    }
    .message.error {
        background: linear-gradient(135deg, #fef2f2, #fecaca);
        color: #991b1b; border-color: rgba(239, 68, 68, 0.2);
    }
    .message.warning {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        color: #92400e; border-color: rgba(245, 158, 11, 0.2);
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    /* Filter Form */
    .collapsible-form {
        max-height: 0; overflow: hidden;
        transition: max-height 0.4s ease-out, margin-bottom 0.4s ease-out;
    }
    .collapsible-form.show { margin-bottom: 24px; }
    .filter-section {
        background: var(--widget-bg); border-radius: 16px; padding: 2rem;
        box-shadow: var(--shadow-md); border: 1px solid var(--border-color);
    }
    .filter-section form {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px; align-items: end;
    }
    .filter-group { display: flex; flex-direction: column; }
    .filter-group label {
        color: #374151; font-size: 15px; font-weight: 600;
        margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
    }
    .filter-group label i { color: var(--accent-blue); font-size: 14px; }
    .filter-group select {
        padding: 14px 16px; border: 2px solid var(--border-color);
        border-radius: 10px; outline: none; font-size: 15px;
        font-family: "Inter", "Spartan", sans-serif; background: #f8fafc;
        color: var(--text-primary); transition: all 0.3s ease; appearance: auto;
    }
    .filter-group select:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); background: #fff;
    }

    /* ============================================
       NEW UI: DEPARTMENT GRID & SIDE PANEL
    ============================================ */

    .course-code {
        font-family: 'Monaco', 'Menlo', monospace; background: rgba(59, 130, 246, 0.1);
        color: #3b82f6; padding: 4px 8px; border-radius: 6px;
        font-weight: 600; font-size: 14px; display: inline-block;
    }
    .seats-available {
        font-weight: 600; padding: 4px 8px; border-radius: 6px;
        font-size: 14px; display: inline-block;
    }
    .seats-available.available { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
    .seats-available.limited { background: rgba(245, 158, 11, 0.1); color: #d97706; }
    .seats-available.full { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
    
    .btn-enroll {
        background: linear-gradient(135deg, #3b82f6, #6366f1); color: white;
        border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;
        font-size: 14px; font-weight: 600; transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); text-transform: uppercase;
        letter-spacing: 0.025em; display: inline-flex; align-items: center; gap: 8px; 
    }
    .btn-enroll:hover {
        transform: translateY(-2px); box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }
    .btn-enroll:disabled {
        background: linear-gradient(135deg, #94a3b8, #64748b);
        cursor: not-allowed; transform: none; box-shadow: none;
    }
    .btn-enroll.pending {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }
    .btn-enroll.approved {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    .btn-enroll.rejected {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* Department Card Grid */
    .dept-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        margin-top: 2rem;
    }
    .dept-card {
        background: var(--widget-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        text-align: left;
    }
    .dept-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
        border-color: var(--accent-blue);
    }
    .dept-card .card-icon {
        font-size: 28px;
        color: var(--accent-blue);
        background: rgba(59, 130, 246, 0.1);
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    .dept-card h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 8px 0;
    }
    .dept-card p {
        font-size: 15px;
        color: var(--text-secondary);
        margin: 0 0 24px 0;
        flex-grow: 1;
    }
    .dept-card .view-courses-link {
        font-size: 15px;
        font-weight: 600;
        color: var(--accent-blue);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: gap 0.3s ease;
    }
    .dept-card:hover .view-courses-link {
        gap: 12px;
    }

    /* Empty State */
    .empty-state {
        text-align: center; padding: 60px 40px; color: var(--text-secondary);
        background: var(--widget-bg); border-radius: 16px; box-shadow: var(--shadow-sm);
        grid-column: 1 / -1; /* Span full grid if it's the only item */
    }
    .empty-state i {
        font-size: 48px; color: #cbd5e1; margin-bottom: 24px; display: block;
    }
    .empty-state h3 {
        font-size: 24px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;
    }
    .empty-state p { font-size: 16px; line-height: 1.6; margin-bottom: 24px; }

    /* Side Panel */
    .side-panel-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px);
        z-index: 1500;
        opacity: 0; visibility: hidden;
        transition: opacity 0.4s ease, visibility 0.4s;
    }
    .side-panel-overlay.show {
        opacity: 1; visibility: visible;
    }
    .side-panel {
        position: fixed;
        top: 0; right: -450px; /* Start off-screen */
        width: 100%; max-width: 450px;
        height: 100%;
        background: var(--widget-bg);
        box-shadow: -10px 0 30px rgba(0,0,0,0.1);
        z-index: 1501;
        display: flex;
        flex-direction: column;
        transition: right 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .side-panel.show {
        right: 0;
    }
    .side-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24px;
        border-bottom: 1px solid var(--border-color);
        flex-shrink: 0;
    }
    .side-panel-header h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }
    .side-panel-body {
        padding: 24px;
        overflow-y: auto;
        flex-grow: 1;
    }
    
    /* Course Item in Panel */
    .panel-course-item {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        transition: all 0.3s ease;
    }
    .panel-course-item:hover {
        background: var(--bg-color);
        border-color: var(--border-color);
    }
    .panel-course-item h4 {
        font-size: 17px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 4px 0;
    }
    .panel-course-item .panel-course-code {
        font-size: 14px;
        color: var(--text-secondary);
        margin-bottom: 16px;
    }
    .panel-course-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
    }
    .panel-course-details .seats-available {
        font-size: 14px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        body { padding-top: 70px; }
        .content-wrapper { margin: 0; padding: 1.5rem 1rem; border-radius: 0; }
        .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        .page-header h1 { font-size: 24px; }
        .filter-section form { grid-template-columns: 1fr; }
        .dept-grid { grid-template-columns: 1fr; } /* Stack cards on mobile */
        .side-panel { max-width: 100vw; right: -100vw; } /* Full width panel */
    }

    /* Modal Styles */
    .modal-overlay {
        display: flex; align-items: flex-start; justify-content: center;
        position: fixed; z-index: 2000; left: 0; top: 0;
        width: 100%; height: 100%; overflow-y: auto; 
        padding-top: 5vh; padding-bottom: 5vh;
        background-color: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        opacity: 0; visibility: hidden;
        transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.4s;
    }
    .modal-overlay.show { opacity: 1; visibility: visible; }
   .modal-content {
        background: var(--widget-bg); padding: 35px; border-radius: 16px;
        width: 90%; 
        max-width: 650px; /* MODIFIED: Reverted to wider modal */
        box-shadow: var(--shadow-lg);
        transform: scale(0.95); opacity: 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-overlay.show .modal-content { transform: scale(1); opacity: 1; }
    .modal-header { 
        display: flex; justify-content: space-between; align-items: center; 
        margin-bottom: 10px; border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }
    .modal-header h2 { font-size: 24px; color: var(--text-primary); font-weight: 700; }
    .modal-content p { color: var(--text-secondary); margin-bottom: 20px; font-size: 16px; }
    .close-btn { 
        background: none; border: none; font-size: 28px; cursor: pointer; 
        color: var(--text-secondary); transition: color 0.2s, transform 0.2s; 
    }
    .close-btn:hover { color: var(--text-primary); transform: rotate(90deg); }
    
    /* NEW: Modal Spinner */
    #modalSpinner {
        display: none; text-align: center; padding: 40px;
    }
    #modalSpinner i {
        font-size: 40px; color: var(--accent-blue);
        animation: spin 1.5s linear infinite;
    }
    #modalSpinner p {
        margin-top: 15px; font-weight: 500;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* NEW: Modal Form Content */
    #modalFormContent {
        display: block; /* Show by default, hide loading */
    }

    /* MODIFIED: Requirements vs Inputs Split */
    .modal-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr; /* MODIFIED: Back to side-by-side */
        gap: 30px; /* MODIFIED: Restored gap */
        margin-bottom: 20px;
    }
    
.requirements-box {
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        /* MODIFIED: Removed margin-bottom */
    }
    .requirements-box h4 {
        display: flex; align-items: center; gap: 8px;
        color: var(--text-primary); margin-bottom: 15px;
    }
    .req-item { margin-bottom: 12px; }
    .req-item .label {
        font-size: 13px; font-weight: 600; color: var(--text-secondary);
        text-transform: uppercase; margin-bottom: 4px;
    }
    .req-item .value {
        font-size: 15px; font-weight: 600; color: var(--text-primary);
    }

    /* Form Group */
    .form-group { margin-bottom: 20px; position: relative; }
    .form-group label {
        display: block; font-weight: 600; font-size: 14px;
        margin-bottom: 8px; color: #475569;
    }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select {
        width: 100%; padding: 14px; border: 2px solid var(--border-color);
        border-radius: 10px; font-size: 15px;
        font-family: "Inter", "Spartan", sans-serif;
        transition: all 0.3s ease;
        background: var(--bg-color);
    }
    .form-group input:focus, .form-group select:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        outline: none; background: var(--widget-bg);
    }
    /* NEW: 'Others' input field */
    .form-group input[name="previous_qualification_other"] {
        display: none;
        margin-top: 10px;
    }

    /* NEW: Validation Styles */
    .validation-message {
        display: none;
        font-size: 13px;
        font-weight: 500;
        margin-top: 6px;
    }
    .validation-message.error { display: block; color: var(--accent-red); }
    .validation-message.success { display: block; color: var(--accent-green); }
    
    .form-group input.invalid, .form-group select.invalid {
        border-color: var(--accent-red);
        background: #fff6f6;
    }
    .form-group input.invalid:focus, .form-group select.invalid:focus {
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }
    .form-group input.valid, .form-group select.valid {
        border-color: var(--accent-green);
        background: #f0fdf4;
    }
    .form-group input.valid:focus, .form-group select.valid:focus {
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    /* NEW: Checkbox Group */
    .checkbox-group {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .checkbox-item input {
        width: 18px; height: 18px;
        accent-color: var(--accent-blue);
    }
    .checkbox-item label {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-secondary);
    }
    .checkbox-item label.invalid {
        color: var(--accent-red);
        font-weight: 600;
    }

    /* NEW: Main Modal Error */
    #modal-error-message {
        display: none;
        padding: 14px;
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 20px;
        text-align: center;
    }

    /* Modal Submit Button */
    .modal-submit-btn {
        width: 100%; padding: 15px; border: none; border-radius: 10px;
        background: var(--accent-blue); color: #fff; font-size: 16px; 
        font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        margin-top: 25px; /* MODIFIED: Added gap */
    }
    .modal-submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
    }
    .modal-submit-btn:disabled {
        background: var(--text-secondary);
        box-shadow: none;
        cursor: not-allowed;
    }
    .btn-spinner {
        width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3);
        border-top-color: #fff; border-radius: 50%;
        animation: spin 1s linear infinite; display: none;
    }
    .modal-submit-btn.loading .btn-spinner { display: block; }
    .modal-submit-btn.loading .btn-text { display: none; }


    /* Dark Mode */
    html[data-theme="dark"] body {
        --bg-color: #0f172a; --navbar-bg: rgba(30, 41, 59, 0.85); --widget-bg: #1e293b;
        --text-primary: #f1f5f9; --text-secondary: #94a3b8; --border-color: #334155;
        --readonly-bg: #334155; --readonly-color: #cbd5e1;
    }
    html[data-theme="dark"] .form-group input,
    html[data-theme="dark"] .form-group select,
    html[data-theme="dark"] .filter-group select {
        background: #334155; border-color: #475569; color: var(--text-primary);
    }
    html[data-theme="dark"] .form-group input:focus,
    html[data-theme="dark"] .form-group select:focus,
    html[data-theme="dark"] .filter-group select:focus {
        background: #1e293b; border-color: var(--accent-blue);
    }
    html[data-theme="dark"] .profile-button:hover { background-color: #334155; }
    html[data-theme="dark"] .profile-menu { background: #1e293b; border-color: #334155; }
    html[data-theme="dark"] .profile-menu-list a:hover { background: #334155; }
    html[data-theme="dark"] .stats-card,
    html[data-theme="dark"] .widget-card,
    html[data-theme="dark"] .table-container,
    html[data-theme="dark"] .empty-state,
    html[data-theme="dark"] .filter-section,
    html[data-theme="dark"] .dept-card {
        background: var(--widget-bg); border-color: var(--border-color);
    }
    html[data-theme="dark"] .action-button.secondary {
        background: var(--widget-bg); color: var(--text-primary); border-color: var(--border-color);
    }
    html[data-theme="dark"] .action-button.secondary:hover { background: #334155; }
    html[data-theme="dark"] .modal-content { background: var(--widget-bg); }
    html[data-theme="dark"] .message.success { background: #064e3b; color: #d1fae5; border-color: #042f2e; }
    html[data-theme="dark"] .message.error { background: #7f1d1d; color: #fecaca; border-color: #450a0a; }
    html[data-theme="dark"] .message.warning { background: #78350f; color: #fef3c7; border-color: #451a03; }
    html[data-theme="dark"] .course-code { background: rgba(59, 130, 246, 0.15); color: #93c5fd; }
    html[data-theme="dark"] .seats-available.available { background: rgba(34, 197, 94, 0.1); color: #4ade80; }
    html[data-theme="dark"] .seats-available.limited { background: rgba(245, 158, 11, 0.1); color: #facc15; }
    html[data-theme="dark"] .seats-available.full { background: rgba(239, 68, 68, 0.1); color: #f87171; }
    html[data-theme="dark"] .modal-overlay { background-color: rgba(15, 23, 42, 0.8); }
    /* NEW Dark mode validation */
    html[data-theme="dark"] .requirements-box { background: #0f172a; border-color: #334155; }
    html[data-theme="dark"] .form-group input.invalid { background: #450a0a; }
    html[data-theme="dark"] .form-group input.valid { background: #064e3b; }
    html[data-theme="dark"] #modal-error-message { background: #7f1d1d; color: #fecaca; border-color: #450a0a; }
    html[data-theme="dark"] .panel-course-item:hover { background: var(--bg-color); }
    html[data-theme="dark"] .side-panel { background: var(--widget-bg); }
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
                <li class="nav-item"><a href="student_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
                <li class="nav-item"><a href="my_courses.php" class="nav-link"><i class="fas fa-book-open"></i><span>My Courses</span></a></li>
                <li class="nav-item"><a href="enroll.php" class="nav-link active"><i class="fas fa-plus-circle"></i><span>Enroll</span></a></li>
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
    <div class="content-wrapper">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-plus-circle"></i>
                <h1>Course Enrollment</h1>
            </div>
            <div class="page-actions">
                <button class="action-button secondary" id="toggleFilterFormBtn">
                    <i class="fas fa-filter"></i>
                    Filter Courses
                </button>
            </div>
        </div>
        
        <p class="page-description">
            Browse available courses by department. Click on a department to see its courses and enroll.
        </p>

        <?php if ($message): ?>
            <div class="message <?php echo (strpos($message, 'successfully') !== false) ? 'success' : (strpos($message, 'full') !== false ? 'error' : 'warning'); ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="collapsible-form" id="filterCourseForm">
            <div class="filter-section"> <form method="get">
                    <div class="filter-group">
                        <label for="dept"><i class="fas fa-building"></i>Filter by Department</label>
                        <select name="dept" id="dept" onchange="this.form.submit()">
                            <option value="">-- All Departments --</option>
                            <?php
                            $departments = $conn->query("SELECT DISTINCT department FROM course_records ORDER BY department");
                            while ($d = $departments->fetch_assoc()) {
                                $selected = ($dept_filter == $d['department']) ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($d['department'])."' $selected>".htmlspecialchars($d['department'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="sem"><i class="fas fa-calendar-alt"></i>Filter by Semester</label>
                        <select name="sem" id="sem" onchange="this.form.submit()">
                            <option value="">-- All Semesters --</option>
                            <?php
                            $semesters = $conn->query("SELECT DISTINCT semester FROM course_records ORDER BY semester");
                            while ($s = $semesters->fetch_assoc()) {
                                $selected = ($sem_filter == $s['semester']) ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($s['semester'])."' $selected>".htmlspecialchars($s['semester'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="dept-grid">
            <?php if (!empty($courses_by_department)): ?>
                <?php foreach ($courses_by_department as $department => $courses_in_dept): ?>
                    <button class="dept-card" data-dept-target="<?php echo htmlspecialchars($department); ?>">
                        <i class="fas fa-building card-icon"></i>
                        <h3><?php echo htmlspecialchars($department); ?></h3>
                        <p><?php echo count($courses_in_dept); ?> Courses Available</p>
                        <span class="view-courses-link">View Courses <i class="fas fa-arrow-right"></i></span>
                    </button>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No Courses Found</h3>
                    <p>No courses match your current filter criteria. Try selecting a different department or browse all courses.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="course-panel-data" style="display: none;">
        <?php foreach ($courses_by_department as $department => $courses_in_dept): ?>
            <div id="dept-<?php echo htmlspecialchars($department); ?>">
                <?php foreach ($courses_in_dept as $course): ?>
                    <?php 
                        $available = $course['seat_limit'] - $course['enrolled_count']; 
                        $is_full = $available <= 0;
                        $status = $course['enrollment_status'];
                        $seat_class = $is_full ? 'full' : ($available <= 5 ? 'limited' : 'available');
                    ?>
                    <div class="panel-course-item">
                        <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p class="panel-course-code">
                            <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span> &bull; 
                            Semester <?php echo htmlspecialchars($course['semester']); ?>
                        </p>
                        <div class="panel-course-details">
                            <span class="seats-available <?= $seat_class ?>">
                                <?= $available ?> / <?= $course['seat_limit'] ?> Seats
                            </span>
                            
                            <?php if ($status === 'Pending'): ?>
                                <button class="btn-enroll pending" disabled><i class="fas fa-clock"></i> Request Sent</button>
                            <?php elseif ($status === 'Approved'): ?>
                                <button class="btn-enroll approved" disabled><i class="fas fa-check"></i> Enrolled</button>
                            <?php elseif ($status === 'Rejected'): ?>
                                <button class="btn-enroll rejected" disabled><i class="fas fa-times"></i> Rejected</button>
                            <?php elseif ($has_active_enrollment): ?>
                                <button class="btn-enroll" disabled><i class="fas fa-lock"></i> Enrolled Elsewhere</button>
                            <?php else: ?>
                                <button type="button" class="btn-enroll" <?= $is_full ? 'disabled' : '' ?> onclick="openEligibilityModal(<?= $course['id'] ?>)">
                                    <?php if ($is_full): ?>
                                        <i class="fas fa-ban"></i> Full
                                    <?php else: ?>
                                        <i class="fas fa-plus"></i> Enroll
                                    <?php endif; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>


    <div id="courseSidePanelOverlay" class="side-panel-overlay"></div>
    <div id="courseSidePanel" class="side-panel">
        <div class="side-panel-header">
            <h2 id="panelDeptName">Department Name</h2>
            <button id="closePanelBtn" class="close-btn">&times;</button>
        </div>
        <div class="side-panel-body" id="panelCourseList">
            </div>
    </div>


    <div id="eligibilityModal" class="modal-overlay">
      <div class="modal-content">
        <div id="modalSpinner">
            <i class="fas fa-spinner"></i>
            <p>Loading course requirements...</p>
        </div>

        <div id="modalFormContent">
            <div class="modal-header">
              <h2 id="modalCourseName">Eligibility Check</h2>
              <button onclick="closeEligibilityModal()" class="close-btn">&times;</button>
            </div>
            <p>Please confirm you meet the requirements and provide your qualifications below.</p>
            
            <form id="enrollForm" method="post" onsubmit="return handleFormSubmit(event)">
              <input type="hidden" name="course_id" id="modal_course_id">

              <div class="modal-grid">
                  <div class="requirements-box">
                      <h4><i class="fas fa-tasks" style="color: var(--accent-blue);"></i>Course Requirements</h4>
                      <div class="req-item">
                          <div class="label">Min. Percentage</div>
                          <div class="value" id="req_perc">N/A</div>
                      </div>
                      <div class="req-item">
                          <div class="label">Min. Qualification</div>
                          <div class="value" id="req_qual">N/A</div>
                      </div>
                      <div class="req-item">
                          <div class="label">Required Subjects</div>
                          <div class="value" id="req_subj">N/A</div>
                      </div>
                  </div>

                  <div class="inputs-box">
                      <h4><i class="fas fa-user-check" style="color: var(--accent-green);"></i>Your Qualifications</h4>
                      <div class="form-group">
                        <label for="previous_percentage">Your Percentage (%)</label>
                        <input type="number" name="previous_percentage" id="previous_percentage" step="0.01" min="0" max="100" placeholder="e.g., 75.5" required>
                        <span class="validation-message" id="perc_msg"></span>
                      </div>
                      <div class="form-group">
                        <label for="previous_qualification">Your Qualification</label>
                        <select name="previous_qualification" id="previous_qualification" required onchange="toggleOtherStudentQual(this)">
                            <option value="" disabled selected>Select your qualification</option>
                            <option value="High School">High School</option>
                            <option value="Higher Secondary">Higher Secondary</option>
                            <option value="Bachelors Degree">Bachelors Degree</option>
                            <option value="Masters Degree">Masters Degree</option>
                            <option value="Others">Others</option>
                        </select>
                        <input type="text" name="previous_qualification_other" id="previous_qualification_other" placeholder="Please specify">
                        <span class="validation-message" id="qual_msg"></span>
                      </div>
                      <div class="form-group">
                        <label for="previous_subjects">Your Subjects</label>
                        <input type="text" name="previous_subjects" id="previous_subjects" placeholder="e.g., Physics, Maths, Chemistry" required>
                        <span class="validation-message" id="subj_msg"></span>
                      </div>
                      <div class="form-group">
                        <label for="previous_board">Board / University</label>
                        <input type="text" name="previous_board" id="previous_board" placeholder="e.g., AHSEC, CBSE, Dibrugarh University" required>
                        <span class="validation-message" id="board_msg"></span>
                      </div>
                      <div class="form-group">
                        <label for="previous_institution">Institution Name</label>
                        <input type="text" name="previous_institution" id="previous_institution" placeholder="e.g., Your College/School Name" required>
                        <span class="validation-message" id="inst_msg"></span>
                      </div>
                  </div>
              </div>

              <div class="checkbox-group">
                  <div class="checkbox-item">
                      <input type="checkbox" id="check_correct" name="check_correct" required>
                      <label for="check_correct" id="label_correct">I declare the information above is correct.</label>
                  </div>
                  <div class="checkbox-item">
                      <input type="checkbox" id="check_terms" name="check_terms" required>
                      <label for="check_terms" id="label_terms">I accept the terms and conditions.</label>
                  </div>
              </div>

              <div id="modal-error-message"></div>
              
              <button type="submit" class="modal-submit-btn" id="modal_submit_btn" disabled>
                 <span class="btn-text">Submit & Enroll</span>
                 <div class="btn-spinner"></div>
              </button>
            </form>
        </div>
      </div>
    </div>
    
   <script>
        // --- Navbar Scripts (Unchanged) ---
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
            const urlParams = new URLSearchParams(window.location.search);
            const department = urlParams.get('dept');
            const semester = urlParams.get('sem');
            if (department) document.getElementById('dept').value = department;
            if (semester) document.getElementById('sem').value = semester;
            if (department || semester) {
                const filterPanel = document.getElementById('filterCourseForm');
                filterPanel.classList.add('show');
                filterPanel.style.maxHeight = filterPanel.scrollHeight + 'px';
            }
        });

        function closePanel(panel, isForm = false) {
            if (!panel) return;
            if (isForm) {
                panel.classList.remove('show'); panel.style.maxHeight = null;
            } else {
                panel.classList.remove('active'); panel.style.maxHeight = null;
            }
        }
        function togglePanel(panel, isForm = false) {
            if (!panel) return;
            if (isForm) {
                if (panel.style.maxHeight) {
                    panel.classList.remove('show'); panel.style.maxHeight = null;
                } else {
                    panel.classList.add('show'); panel.style.maxHeight = panel.scrollHeight + 'px';
                }
            } else {
                if (panel.style.maxHeight) {
                    panel.classList.remove('active'); panel.style.maxHeight = null;
                } else {
                    panel.classList.add('active'); panel.style.maxHeight = panel.scrollHeight + 'px';
                }
            }
        }
        const filterFormBtn = document.getElementById('toggleFilterFormBtn');
        const filterFormPanel = document.getElementById('filterCourseForm');
        if (filterFormBtn) {
            filterFormBtn.addEventListener('click', () => togglePanel(filterFormPanel, true));
        }

        // --- NEW: Side Panel Logic ---
        const panelOverlay = document.getElementById('courseSidePanelOverlay');
        const panel = document.getElementById('courseSidePanel');
        const panelDeptName = document.getElementById('panelDeptName');
        const panelCourseList = document.getElementById('panelCourseList');
        const closePanelBtn = document.getElementById('closePanelBtn');
        const deptCards = document.querySelectorAll('.dept-card');

        deptCards.forEach(card => {
            card.addEventListener('click', () => {
                const deptName = card.dataset.deptTarget;
                // Find the hidden div with the pre-rendered HTML
                const courseDataContainer = document.getElementById('dept-' + deptName);
                
                if (courseDataContainer) {
                    panelDeptName.textContent = deptName;
                    panelCourseList.innerHTML = courseDataContainer.innerHTML;
                    
                    panel.classList.add('show');
                    panelOverlay.classList.add('show');
                } else {
                    console.error('No course data found for ' + deptName);
                }
            });
        });

        function closeCoursePanel() {
            panel.classList.remove('show');
            panelOverlay.classList.remove('show');
        }

        if(closePanelBtn) closePanelBtn.addEventListener('click', closeCoursePanel);
        if(panelOverlay) panelOverlay.addEventListener('click', closeCoursePanel);


        // --- NEW: Advanced Modal & Validation Scripts (Unchanged) ---

        const eligibilityModal = document.getElementById('eligibilityModal');
        const modalCourseIdInput = document.getElementById('modal_course_id');
        const enrollForm = document.getElementById('enrollForm');
        const submitBtn = document.getElementById('modal_submit_btn');
        const modalSpinner = document.getElementById('modalSpinner');
        const modalFormContent = document.getElementById('modalFormContent');
        const modalCourseName = document.getElementById('modalCourseName');
        const modalErrorMsg = document.getElementById('modal-error-message');

        // Input Fields
        const percInput = document.getElementById('previous_percentage');
        const qualSelect = document.getElementById('previous_qualification');
        const qualOtherInput = document.getElementById('previous_qualification_other');
        const subjInput = document.getElementById('previous_subjects');
        const boardInput = document.getElementById('previous_board'); // New
        const instInput = document.getElementById('previous_institution'); // New
        const checkCorrect = document.getElementById('check_correct');
        const checkTerms = document.getElementById('check_terms');

        // Validation Message Spans
        const percMsg = document.getElementById('perc_msg');
        const qualMsg = document.getElementById('qual_msg');
        const subjMsg = document.getElementById('subj_msg');
        const boardMsg = document.getElementById('board_msg'); // New
        const instMsg = document.getElementById('inst_msg'); // New
        const labelCorrect = document.getElementById('label_correct');
        const labelTerms = document.getElementById('label_terms');

        // Course requirements (will be filled by fetch)
        let courseReqs = {
            min_perc: 0,
            req_qual: '',
            req_subj: ''
        };

        // --- 1. Open/Close Modal ---
        async function openEligibilityModal(courseId) {
            eligibilityModal.classList.add('show');
            modalCourseIdInput.value = courseId;
            
            enrollForm.reset();
            modalFormContent.style.display = 'none';
            modalSpinner.style.display = 'block';
            resetValidationStyles();
            
            try {
                const response = await fetch(`get_course_eligibility.php?id=${courseId}`);
                if (!response.ok) throw new Error('Network error');
                
                const data = await response.json();
                if (data.error) throw new Error(data.error);

                courseReqs.min_perc = parseFloat(data.course_requirements.minimum_percentage) || 0;
                courseReqs.req_qual = data.course_requirements.required_qualification.toLowerCase().trim();
                courseReqs.req_subj = data.course_requirements.required_subjects ? data.course_requirements.required_subjects.toLowerCase().trim() : '';

                modalCourseName.textContent = data.course_requirements.course_name;
                document.getElementById('req_perc').textContent = `${courseReqs.min_perc}%`;
                document.getElementById('req_qual').textContent = data.course_requirements.required_qualification;
                document.getElementById('req_subj').textContent = data.course_requirements.required_subjects || 'None';

                // Pre-fill student data
                if(data.student_data) {
                    percInput.value = data.student_data.previous_percentage || '';
                    subjInput.value = data.student_data.previous_subjects || '';
                    boardInput.value = data.student_data.previous_board || ''; // New
                    instInput.value = data.student_data.previous_institution || ''; // New
                    
                    const studentQual = data.student_data.previous_qualification || '';
                    const standardOptions = Array.from(qualSelect.options).map(opt => opt.value);
                    if (standardOptions.includes(studentQual)) {
                        qualSelect.value = studentQual;
                    } else if (studentQual) {
                        qualSelect.value = 'Others';
                        qualOtherInput.value = studentQual;
                        qualOtherInput.style.display = 'block';
                    }
                }
                
                modalSpinner.style.display = 'none';
                modalFormContent.style.display = 'block';

                validateAllFields(); // Run validation on pre-filled data

            } catch (error) {
                console.error('Failed to load eligibility:', error);
                modalSpinner.innerHTML = `<p style="color: var(--accent-red);">Error: ${error.message}. Please close and try again.</p>`;
            }
        }

        function closeEligibilityModal() {
            eligibilityModal.classList.remove('show');
        }

        function toggleOtherStudentQual(selectElement) {
            if (selectElement.value === 'Others') {
                qualOtherInput.style.display = 'block';
            } else {
                qualOtherInput.style.display = 'none';
            }
            validateAllFields();
        }

        // --- 2. Validation Logic ---

        // NEW: Smarter validation state function
        function setValidationState(inputEl, msgEl, state, message) {
            inputEl.classList.remove('valid', 'invalid');
            msgEl.classList.remove('success', 'error');
            
            if (state === 'success') {
                inputEl.classList.add('valid');
                msgEl.classList.add('success');
                msgEl.textContent = message;
                return true;
            } else if (state === 'error') {
                inputEl.classList.add('invalid');
                msgEl.classList.add('error');
                msgEl.textContent = message;
                return false;
            } else { // 'neutral' state (for empty required fields)
                msgEl.textContent = message;
                return false;
            }
        }

        function validatePercentage() {
            const studentPercStr = percInput.value.trim();
            if (studentPercStr === '') {
                return setValidationState(percInput, percMsg, 'neutral', 'Percentage is required.');
            }
            // Logic for words/symbols: extract first number
            const studentPerc = parseFloat(studentPercStr.match(/(\d*\.?\d+)/)?.[0]);
            
            if (isNaN(studentPerc)) {
                return setValidationState(percInput, percMsg, 'error', 'Please enter a valid number.');
            }
            if (studentPerc >= courseReqs.min_perc) {
                return setValidationState(percInput, percMsg, 'success', 'Requirement met.');
            } else {
                return setValidationState(percInput, percMsg, 'error', `Minimum ${courseReqs.min_perc}% not met.`);
            }
        }

        function validateQualification() {
            let studentQual = qualSelect.value;
            if (studentQual === '') {
                 return setValidationState(qualSelect, qualMsg, 'neutral', 'Qualification is required.');
            }
            if (studentQual === 'Others') {
                studentQual = qualOtherInput.value.toLowerCase().trim();
                if (studentQual === '') {
                    return setValidationState(qualSelect, qualMsg, 'error', 'Please specify your qualification.');
                }
            }
            
            studentQual = studentQual.toLowerCase().trim();

            if (courseReqs.req_qual === 'others' || studentQual === courseReqs.req_qual) {
                return setValidationState(qualSelect, qualMsg, 'success', 'Requirement met.');
            } else {
                return setValidationState(qualSelect, qualMsg, 'error', `Course requires ${courseReqs.req_qual}.`);
            }
        }

        function validateSubjects() {
            if (!courseReqs.req_subj) {
                return setValidationState(subjInput, subjMsg, 'success', 'N/A');
            }
            
            const studentSubjects = subjInput.value.toLowerCase().trim();
            if (!studentSubjects) {
                return setValidationState(subjInput, subjMsg, 'neutral', 'Subjects are required.');
            }
            
            const requiredSubjects = courseReqs.req_subj.split(',').map(s => s.trim()).filter(s => s);
            let allMet = true;
            let missing = [];

            for (const reqSub of requiredSubjects) {
                if (studentSubjects.indexOf(reqSub) === -1) { // Looser check (indexOf)
                    allMet = false;
                    missing.push(reqSub);
                }
            }

            if (allMet) {
                return setValidationState(subjInput, subjMsg, 'success', 'All required subjects found.');
            } else {
                return setValidationState(subjInput, subjMsg, 'error', `Missing: ${missing.join(', ')}`);
            }
        }

        // NEW: Validation for Board/Inst
        function validateBoard() {
            if (boardInput.value.trim() === '') {
                return setValidationState(boardInput, boardMsg, 'neutral', 'Board/University is required.');
            }
            return setValidationState(boardInput, boardMsg, 'success', 'OK');
        }

        function validateInstitution() {
            if (instInput.value.trim() === '') {
                return setValidationState(instInput, instMsg, 'neutral', 'Institution is required.');
            }
            return setValidationState(instInput, instMsg, 'success', 'OK');
        }

        function validateCheckboxes() {
            let allChecked = true;
            if (!checkCorrect.checked) {
                labelCorrect.classList.add('invalid');
                allChecked = false;
            } else {
                labelCorrect.classList.remove('invalid');
            }
            if (!checkTerms.checked) {
                labelTerms.classList.add('invalid');
                allChecked = false;
            } else {
                labelTerms.classList.remove('invalid');
            }
            return allChecked;
        }

        function resetValidationStyles() {
            [percInput, qualSelect, subjInput, boardInput, instInput].forEach(el => el.classList.remove('valid', 'invalid'));
            [percMsg, qualMsg, subjMsg, boardMsg, instMsg].forEach(el => {
                el.textContent = '';
                el.classList.remove('success', 'error');
            });
            [labelCorrect, labelTerms].forEach(el => el.classList.remove('invalid'));
            modalErrorMsg.style.display = 'none';
            submitBtn.disabled = true;
        }

        // --- 3. Live Validation & Submission ---

        function validateAllFields() {
            const percValid = validatePercentage();
            const qualValid = validateQualification();
            const subjValid = validateSubjects();
            const boardValid = validateBoard();
            const instValid = validateInstitution();
            const checksValid = validateCheckboxes();

            // All must be true to enable submit
            const allValid = percValid && qualValid && subjValid && boardValid && instValid && checksValid;
            submitBtn.disabled = !allValid;
            
            if(allValid) {
                modalErrorMsg.style.display = 'none';
            }
            
            return allValid;
        }

        // Add live listeners
        [percInput, qualSelect, qualOtherInput, subjInput, boardInput, instInput, checkCorrect, checkTerms].forEach(el => {
            el.addEventListener('input', validateAllFields);
            el.addEventListener('change', validateAllFields);
        });

        // Final check on submit
        function handleFormSubmit(event) {
            event.preventDefault(); // Always stop the form first
            
            if (validateAllFields()) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                modalErrorMsg.style.display = 'none';
                
                // Manually submit the form
                enrollForm.submit(); 
            } else {
                // Show main error message
                modalErrorMsg.textContent = 'Please fix the errors above and check all boxes to continue.';
                modalErrorMsg.style.display = 'block';
                return false; // Stop submission
            }
        }
        
        window.addEventListener('click', function(event) {
            if (event.target == eligibilityModal) {
                closeEligibilityModal();
            }
        });
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>