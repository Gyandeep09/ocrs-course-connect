<?php
require_once 'session_config.php';
// ... [PHP logic from line 2 to 142 remains completely unchanged] ...
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
    // FIXED: Uncommented this line
    if (isset($_POST['add_course'])) {
        $course_name = $_POST['course_name'];
        $course_code = $_POST['course_code'];
        $department = $_POST['department'];
        $semester = $_POST['semester'];
        $credits = $_POST['credits'];
        $seat_limit = $_POST['seat_limit'];
        // New eligibility fields
        $min_perc = $_POST['minimum_percentage'];
        $req_qual = $_POST['required_qualification'];
        $req_subj = !empty($_POST['required_subjects']) ? trim($_POST['required_subjects']) : NULL;

        $check_stmt = $conn->prepare("SELECT id FROM course_records WHERE course_name = ? OR course_code = ?");
        $check_stmt->bind_param("ss", $course_name, $course_code);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "Error: A course with that name or code already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO course_records (
                                        course_name, course_code, department, semester, credits, seat_limit, 
                                        minimum_percentage, required_qualification, required_subjects
                                    ) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiidss", 
                $course_name, $course_code, $department, $semester, $credits, $seat_limit,
                $min_perc, $req_qual, $req_subj
            );
            $stmt->execute();
            $_SESSION['success_message'] = "Course added successfully!";
        }
        header("Location: course_management.php");
        exit;
    }

    // FIXED: Uncommented this line
    if (isset($_POST['edit_course'])) {
        // Get all form fields
        $course_id = (int)$_POST['edit_course_id'];
        $course_name = $_POST['edit_course_name'];
        $course_code = $_POST['edit_course_code'];
        $department = $_POST['edit_department'];
        $semester = $_POST['edit_semester'];
        $credits = (int)$_POST['edit_credits'];
        $seat_limit = (int)$_POST['edit_seat_limit'];
        $min_perc = (float)$_POST['edit_minimum_percentage'];
        $req_qual = $_POST['edit_required_qualification'];
        $req_subj = !empty($_POST['edit_required_subjects']) ? trim($_POST['edit_required_subjects']) : NULL;

        // Check for duplicate course code/name *excluding* the current course
        $check_stmt = $conn->prepare("SELECT id FROM course_records WHERE (course_name = ? OR course_code = ?) AND id != ?");
        $check_stmt->bind_param("ssi", $course_name, $course_code, $course_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "Error: Another course with that name or code already exists.";
        } else {
            // No conflict, proceed with update
            $stmt = $conn->prepare("UPDATE course_records SET 
                                        course_name = ?, 
                                        course_code = ?, 
                                        department = ?, 
                                        semester = ?, 
                                        credits = ?, 
                                        seat_limit = ?, 
                                        minimum_percentage = ?, 
                                        required_qualification = ?, 
                                        required_subjects = ?
                                    WHERE id = ?");
            $stmt->bind_param("ssssiidssi",
                $course_name, $course_code, $department, $semester, $credits, $seat_limit,
                $min_perc, $req_qual, $req_subj,
                $course_id
            );
            $stmt->execute();
            $_SESSION['success_message'] = "Course updated successfully!";
        }
        header("Location: course_management.php");
        exit;
    }


    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM course_records WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['success_message'] = "Course deleted successfully!";
        header("Location: course_management.php");
        exit;
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        $_SESSION['error_message'] = "Error: A course with that name or code already exists.";
    } else {
        error_log($e->getMessage());
        $_SESSION['error_message'] = "Database error: Could not complete the request. " . $e->getMessage();
    }
    header("Location: course_management.php");
    exit;
}

// Fetch all courses for display
$department_filter = $_GET['department'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$sql = "SELECT * FROM course_records"; // We fetch all fields now
$where_clauses = [];
$params = [];
$types = "";
if (!empty($department_filter)) {
    $where_clauses[] = "department = ?";
    $params[] = $department_filter;
    $types .= "s";
}
if (!empty($semester_filter)) {
    $where_clauses[] = "semester = ?";
    $params[] = $semester_filter;
    $types .= "s";
}
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY department ASC, semester ASC, course_name ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$courses_by_department = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses_by_department[$row['department']][] = $row;
    }
}
$departments = [];
$dept_result = $conn->query("SELECT department_name FROM departments ORDER BY department_name ASC");
if ($dept_result) {
    while($dept = $dept_result->fetch_assoc()) {
        $departments[] = $dept['department_name'];
    }
}
$semesters = [];
$semester_result = $conn->query("SELECT DISTINCT semester FROM course_records ORDER BY semester ASC");
if ($semester_result) {
    while($sem = $semester_result->fetch_assoc()) {
        $semesters[] = $sem['semester'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - Course Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* ... [All CSS styles remain unchanged] ... */
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
        font-family: "Inter", "Spartan", sans-serif;
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
    .action-button.green {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    }
    .action-button.green:hover {
        background: linear-gradient(135deg, #059669, #047857);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
    }
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
    .widget-header i.fa-filter { color: var(--accent-blue); }
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
    .form-group select,
    .form-group textarea {
        padding: 14px 16px;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        outline: none;
        font-size: 15px;
        font-family: "Inter", "Spartan", sans-serif;
        background: #f8fafc;
        color: var(--text-primary);
        transition: all 0.3s ease;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        background: #fff;
    }
    .form-group input[name="required_qualification_other"],
    .form-group input[name="edit_required_qualification_other"] {
        display: none; /* Hide by default */
        margin-top: 10px;
    }
    .collapsible-form {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-out, margin-top 0.5s ease-out;
    }
    .collapsible-form.show {
        margin-top: 0; /* Removed margin-top for filter form */
        max-height: 1000px; 
    }
    #addCourseForm.show {
        margin-top: 1.5rem;
    }
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
    .empty-state {
        text-align: center; padding: 60px 40px; color: var(--text-secondary);
        background: var(--widget-bg); border-radius: 16px; box-shadow: var(--shadow-sm);
        grid-column: 1 / -1; /* Span full grid if it's the only item */
        margin-top: 2rem;
    }
    .empty-state i {
        font-size: 48px; color: #cbd5e1; margin-bottom: 24px; display: block;
    }
    .empty-state h3 {
        font-size: 24px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;
    }
    .empty-state p { font-size: 16px; line-height: 1.6; margin-bottom: 24px; }
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
    .course-code {
        font-family: 'Monaco', 'Menlo', monospace; background: rgba(59, 130, 246, 0.1);
        color: #3b82f6; padding: 4px 8px; border-radius: 6px;
        font-weight: 600; font-size: 14px; display: inline-block;
    }
    .panel-course-details {
        display: flex;
        justify-content: space-between; /* Pushes buttons to the right */
        align-items: center;
        gap: 16px;
    }
    .course-actions {
        display: flex;
        gap: 10px;
    }
    .btn-icon-action {
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
    }
    .btn-icon-action.edit {
        background: rgba(59, 130, 246, 0.1);
        color: var(--accent-blue);
    }
    .btn-icon-action:hover {
        background: var(--accent-red);
        color: #fff;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    .btn-icon-action.edit:hover {
        background: var(--accent-blue);
        color: #fff;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    .modal-overlay {
        display: flex; 
        align-items: flex-start; /* Aligns modal to top */
        justify-content: center;
        position: fixed; z-index: 2000; left: 0; top: 0;
        width: 100%; height: 100%;
        overflow-y: auto; 
        padding-top: 5vh; /* 5% from top */
        padding-bottom: 5vh;
        background-color: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.4s;
    }
    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    .modal-content {
        background: var(--widget-bg); 
        padding: 35px; 
        border-radius: 16px;
        width: 90%; 
        max-width: 900px; /* Widened for more fields */
        box-shadow: var(--shadow-lg);
        transform: scale(0.95);
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-overlay.show .modal-content {
        transform: scale(1);
        opacity: 1;
    }
    .modal-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px; 
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }
    .modal-header h2 { 
        font-size: 24px; 
        color: var(--text-primary); 
        font-weight: 700; 
    }
    .close-btn { 
        background: none; 
        border: none; 
        font-size: 28px; 
        cursor: pointer; 
        color: var(--text-secondary); 
        transition: color 0.2s, transform 0.2s; 
    }
    .close-btn:hover { 
        color: var(--text-primary); 
        transform: rotate(90deg); 
    }
    .modal-submit-btn {
        width: 100%; 
        padding: 15px; 
        border: none; 
        border-radius: 10px;
        background: var(--accent-blue);
        color: #fff; 
        font-size: 16px; 
        font-weight: 600;
        cursor: pointer; 
        transition: all 0.3s ease;
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 8px;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        margin-top: 25px; /* MODIFIED: Added this line */
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
    @media (max-width: 768px) {
        .main-content { padding: 1.5rem 1rem; }
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        .page-title h1 { font-size: 24px; }
        .page-actions { flex-wrap: wrap; }
        
        .widget-card { padding: 1.5rem; }
        .form-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .form-grid .action-button { justify-content: center; }
        
        #filterCourseForm .form-grid { grid-template-columns: 1fr; }
        .dept-grid { grid-template-columns: 1fr; } /* Stack cards on mobile */
        .side-panel { max-width: 100vw; right: -100vw; } /* Full width panel */
    }
</style>
</head>
<body>
    <nav class="navbar" id="navbar">
        <div class="nav-container" id="nav-container">
            <a href="admin_dashboard.php" class="navbar-brand">
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
                    <a href="course_management.php" class="nav-link active">
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
                <i class="fas fa-book"></i>
                <h1>Course Management</h1>
            </div>
            <div class="page-actions">
                <button class="action-button secondary" id="toggleFilterFormBtn">
                    <i class="fas fa-filter"></i>
                    <span>Filter Courses</span>
                </button>
                <button class="action-button" id="toggleAddFormBtn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New Course</span>
                </button>
            </div>
        </div>
        
        <p class="page-description">
            Add, remove, and manage all academic courses available for enrollment. Use the filters to narrow your search.
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

        <div class="collapsible-form" id="addCourseForm">
            <div class="widget-card">
                <div class="widget-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add New Course</h3>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="add_course" value="1">
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group">
                            <label><i class="fas fa-book-open"></i>Course Name</label>
                            <input type="text" name="course_name" placeholder="Enter course name" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-code"></i>Course Code</label>
                            <input type="text" name="course_code" placeholder="e.g., CS101" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i>Department</label>
                            <select name="department" required>
                                <option value="" disabled selected>Select Department</option>
                                <?php foreach ($departments as $dept_name): ?>
                                    <option value="<?= htmlspecialchars($dept_name); ?>">
                                        <?= htmlspecialchars($dept_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i>Semester</label>
                            <input type="text" name="semester" placeholder="e.g., 1st, 2nd" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-star"></i>Credits</label>
                            <input type="number" name="credits" placeholder="e.g., 3" min="1" max="10" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i>Seat Limit</label>
                            <input type="number" name="seat_limit" placeholder="e.g., 50" min="1" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-percentage"></i>Minimum Percentage</label>
                            <input type="number" name="minimum_percentage" placeholder="e.g., 60.00" step="0.01" min="0" max="100" value="60.00">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-graduation-cap"></i>Required Qualification</label>
                            <select name="required_qualification" onchange="toggleOtherQualification(this, 'required_qualification_other')">
                                <option value="High School">High School</option>
                                <option value="Higher Secondary" selected>Higher Secondary</option>
                                <option value="Bachelors Degree">Bachelors Degree</option>
                                <option value="Masters Degree">Masters Degree</option>
                                <option value="Others">Others</option>
                            </select>
                            <input type="text" name="required_qualification_other" id="required_qualification_other" placeholder="Please specify qualification" style="display:none; margin-top: 10px;">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-book-reader"></i>Required Subjects</label>
                            <input type="text" name="required_subjects" placeholder="e.g., Physics, Maths (comma-sep)">
                        </div>

                        <div class="form-group" style="grid-column: 1 / -1;">
                            <button type="submit" class="action-button">
                                <i class="fas fa-plus"></i>
                                Add Course
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="collapsible-form" id="filterCourseForm">
            <div class="widget-card">
                <div class="widget-header">
                    <i class="fas fa-filter"></i>
                    <h3>Filter Courses</h3>
                </div>
                <form method="GET" action="course_management.php" id="filterForm">
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr auto auto; align-items: end;">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i>By Department</label>
                            <select name="department" id="departmentFilter">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept_name): ?>
                                    <option value="<?= htmlspecialchars($dept_name); ?>" <?= ($department_filter == $dept_name) ? 'selected' : '' ?>><?= htmlspecialchars($dept_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i>By Semester</label>
                            <select name="semester" id="semesterFilter">
                                <option value="">All Semesters</option>
                                <?php foreach ($semesters as $sem_name): ?>
                                    <option value="<?= htmlspecialchars($sem_name); ?>" <?= ($semester_filter == $sem_name) ? 'selected' : '' ?>><?= htmlspecialchars($sem_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="action-button"><i class="fas fa-search"></i> Filter</button>
                        </div>
                        <div class="form-group">
                            <a href="course_management.php" class="action-button secondary" style="text-decoration: none; justify-content: center;"><i class="fas fa-times"></i> Clear</a>
                        </div>
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
                        <p><?php echo count($courses_in_dept); ?> Courses</p>
                        <span class="view-courses-link">Manage Courses <i class="fas fa-arrow-right"></i></span>
                    </button>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No Courses Found</h3>
                    <p>No courses match your filter criteria, or no courses have been added yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="course-panel-data" style="display: none;">
        <?php foreach ($courses_by_department as $department => $courses_in_dept): ?>
            <div id="dept-<?php echo htmlspecialchars($department); ?>">
                <?php foreach ($courses_in_dept as $course): ?>
                    <div class="panel-course-item">
                        <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p class="panel-course-code">
                            <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span> &bull; 
                            Semester <?php echo htmlspecialchars($course['semester']); ?>
                        </p>
                        <div class="panel-course-details">
                            <div></div> 
                            
                            <div class="course-actions">
                                <button 
                                    class="btn-icon-action edit" 
                                    title="Edit Course"
                                    onclick="openEditModal(<?= $course['id'] ?>)">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button 
                                    class="btn-icon-action" 
                                    title="Delete Course"
                                    onclick="showDeleteModal(event, 'course_management.php?delete=<?= $course['id'] ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
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


    <div id="deleteModal" class="modal-overlay" style="padding-top: 15vh;">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <h3 style="margin-bottom: 15px; font-size: 22px;">Confirm Deletion</h3>
            <p style="margin-bottom: 25px; color: #475569;">Are you sure you want to delete this course? This action cannot be undone.</p>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button id="cancelBtn" class="action-button secondary" style="width: 120px; justify-content: center;">Cancel</button>
                <a id="confirmDeleteBtn" href="#" class="action-button" style="background: var(--accent-red); width: 120px; justify-content: center; text-decoration: none;">Delete</a>
            </div>
        </div>
    </div>

    <div id="editCourseModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Edit Course</h2>
          <button onclick="closeEditModal()" class="close-btn">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="edit_course" value="1">
            <input type="hidden" name="edit_course_id" id="edit_course_id">
            
            <div id="editModalSpinner" style="display: none; text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: var(--accent-blue);"></i>
                <p style="margin-top: 15px;">Loading course data...</p>
            </div>

            <div id="editModalFormContent">
                <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                    <div class="form-group">
                        <label><i class="fas fa-book-open"></i>Course Name</label>
                        <input type="text" name="edit_course_name" id="edit_course_name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-code"></i>Course Code</label>
                        <input type="text" name="edit_course_code" id="edit_course_code" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-building"></i>Department</label>
                        <select name="edit_department" id="edit_department" required>
                            <option value="" disabled>Select Department</option>
                            <?php foreach ($departments as $dept_name): ?>
                                <option value="<?= htmlspecialchars($dept_name); ?>">
                                    <?= htmlspecialchars($dept_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i>Semester</label>
                        <input type="text" name="edit_semester" id="edit_semester" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-star"></i>Credits</label>
                        <input type="number" name="edit_credits" id="edit_credits" min="1" max="10" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-users"></i>Seat Limit</label>
                        <input type="number" name="edit_seat_limit" id="edit_seat_limit" min="1" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-percentage"></i>Minimum Percentage</label>
                        <input type="number" name="edit_minimum_percentage" id="edit_minimum_percentage" step="0.01" min="0" max="100">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i>Required Qualification</label>
                        <select name="edit_required_qualification" id="edit_required_qualification" onchange="toggleOtherQualification(this, 'edit_required_qualification_other')">
                            <option value="High School">High School</option>
                            <option value="Higher Secondary">Higher Secondary</option>
                            <option value="Bachelors Degree">Bachelors Degree</option>
                            <option value="Masters Degree">Masters Degree</option>
                            <option value="Others">Others</option>
                        </select>
                        <input type="text" name="edit_required_qualification_other" id="edit_required_qualification_other" placeholder="Please specify qualification">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-book-reader"></i>Required Subjects</label>
                        <input type="text" name="edit_required_subjects" id="edit_required_subjects" placeholder="e.g., Physics, Maths (comma-sep)">
                    </div>
                </div>
                <button type="submit" class="modal-submit-btn">
                    <i class="fas fa-save"></i>
                    <span class="btn-text">Save Changes</span>
                </button>
            </div>
        </form>
      </div>
    </div>


<script>
    // ... [The entire <script> section remains unchanged] ...
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
    function toggleOtherQualification(selectElement, otherInputId) {
        const otherInput = document.getElementById(otherInputId);
        if (selectElement.value === 'Others') {
            otherInput.style.display = 'block';
            otherInput.required = true;
        } else {
            otherInput.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const department = urlParams.get('department');
        const semester = urlParams.get('semester');

        if (department) {
            document.getElementById('departmentFilter').value = department;
        }
        if (semester) {
            document.getElementById('semesterFilter').value = semester;
        }
        if (department || semester) {
            const filterForm = document.getElementById('filterCourseForm');
            filterForm.classList.add('show');
            filterForm.style.maxHeight = filterForm.scrollHeight + 'px';
        }
    });
    function closePanel(panel, isForm = false) {
        if (!panel) return;
        if (isForm) {
            panel.classList.remove('show');
            panel.style.maxHeight = null;
        } else {
            panel.classList.remove('active');
            panel.style.maxHeight = null;
        }
    }
    function togglePanel(panel, isForm = false) {
        if (!panel) return;
        if (isForm) {
            if (panel.style.maxHeight) {
                panel.classList.remove('show');
                panel.style.maxHeight = null;
            } else {
                panel.classList.add('show');
                panel.style.maxHeight = panel.scrollHeight + 'px';
            }
        }
    }
    const addFormBtn = document.getElementById('toggleAddFormBtn');
    const addFormPanel = document.getElementById('addCourseForm');
    const filterFormBtn = document.getElementById('toggleFilterFormBtn');
    const filterFormPanel = document.getElementById('filterCourseForm');

    addFormBtn.addEventListener('click', () => {
        closePanel(filterFormPanel, true); // Close the other panel
        togglePanel(addFormPanel, true); // Toggle this panel
    });

    filterFormBtn.addEventListener('click', () => {
        closePanel(addFormPanel, true); // Close the other panel
        togglePanel(filterFormPanel, true); // Toggle this panel
    });
    const panelOverlay = document.getElementById('courseSidePanelOverlay');
    const panel = document.getElementById('courseSidePanel');
    const panelDeptName = document.getElementById('panelDeptName');
    const panelCourseList = document.getElementById('panelCourseList');
    const closePanelBtn = document.getElementById('closePanelBtn');
    const deptCards = document.querySelectorAll('.dept-card');

    deptCards.forEach(card => {
        card.addEventListener('click', () => {
            const deptName = card.dataset.deptTarget;
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
    const deleteModal = document.getElementById('deleteModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    function showDeleteModal(event, deleteUrl) {
        event.preventDefault();
        event.stopPropagation();
        confirmDeleteBtn.href = deleteUrl;
        deleteModal.classList.add('show');
    }

    cancelBtn.onclick = function() {
        deleteModal.classList.remove('show');
    }
    const editModal = document.getElementById('editCourseModal');
    const editModalSpinner = document.getElementById('editModalSpinner');
    const editModalFormContent = document.getElementById('editModalFormContent');
    const editOtherQualInput = document.getElementById('edit_required_qualification_other');

    async function openEditModal(courseId) {
        editModal.classList.add('show');
        editModalFormContent.style.display = 'none';
        editModalSpinner.style.display = 'block';
        editOtherQualInput.style.display = 'none'; // Reset 'Others' field

        try {
            const response = await fetch(`get_course_details.php?id=${courseId}`);
            if (!response.ok) {
                throw new Error('Failed to fetch course data.');
            }
            const course = await response.json();

            document.getElementById('edit_course_id').value = course.id;
            document.getElementById('edit_course_name').value = course.course_name;
            document.getElementById('edit_course_code').value = course.course_code;
            document.getElementById('edit_department').value = course.department;
            document.getElementById('edit_semester').value = course.semester;
            document.getElementById('edit_credits').value = course.credits;
            document.getElementById('edit_seat_limit').value = course.seat_limit;
            document.getElementById('edit_minimum_percentage').value = course.minimum_percentage;
            document.getElementById('edit_required_subjects').value = course.required_subjects;
            
            const qualSelect = document.getElementById('edit_required_qualification');
            const qualValue = course.required_qualification;
            
            const standardOptions = Array.from(qualSelect.options).map(opt => opt.value);
            if (standardOptions.includes(qualValue)) {
                qualSelect.value = qualValue;
                editOtherQualInput.style.display = 'none';
                editOtherQualInput.value = '';
            } else {
                qualSelect.value = 'Others';
                editOtherQualInput.style.display = 'block';
                editOtherQualInput.value = qualValue;
            }

            editModalFormContent.style.display = 'block';
            editModalSpinner.style.display = 'none';

        } catch (error) {
            console.error(error);
            editModalSpinner.innerHTML = '<p style="color: var(--accent-red);">Error loading data. Please close and try again.</p>';
        }
    }

    function closeEditModal() {
        editModal.classList.remove('show');
    }

    window.onclick = function(event) {
        if (event.target == deleteModal) {
            deleteModal.classList.remove('show');
        }
        if (event.target == editModal) {
            closeEditModal();
        }
    }

</script>
</body>
</html>