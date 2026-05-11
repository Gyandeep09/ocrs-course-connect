<?php
require_once 'session_config.php';

// Protect this page
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_page.php');
    exit;
}

$conn = new mysqli("localhost", "root", "", "ocrs_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Count pending for sidebar badge ---
$pending_count = 0;
$pending_result = $conn->query("SELECT COUNT(*) AS cnt FROM enrollments WHERE status='Pending'");
if ($pending_result && $pending_row = $pending_result->fetch_assoc()) {
    $pending_count = (int)$pending_row['cnt'];
}

// Helper for redirect
function redirect_with_msg($msg) {
    $_SESSION['admin_message'] = $msg;
    header("Location: enrollments.php");
    exit;
}

// --- MODIFIED: Approve request ---
if (isset($_GET['approve'])) {
    $enroll_id = (int)$_GET['approve'];

    $stmt = $conn->prepare("SELECT student_id, course_id FROM enrollments WHERE id=?");
    $stmt->bind_param("i", $enroll_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result_row = $result->fetch_assoc()) {
        $stmt->close();
        redirect_with_msg("Enrollment not found.");
    }
    $student_id = $result_row['student_id'];
    $course_id = $result_row['course_id'];
    $stmt->close();

    // Block if student already has another approved enrollment
    $stmt = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id=? AND status='Approved' AND id<>?");
    $stmt->bind_param("ii", $student_id, $enroll_id);
    $stmt->execute();
    $stmt->bind_result($alreadyApproved);
    $stmt->fetch();
    $stmt->close();

    if ($alreadyApproved > 0) {
        redirect_with_msg("Cannot approve: student already enrolled in another course.");
    }

    // Check seats
    $stmt = $conn->prepare("
        SELECT c.course_name, c.seat_limit - (SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND status='Approved') AS seats_left
        FROM course_records c
        WHERE c.id = ?
    ");
    $stmt->bind_param("ii", $course_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $seats_left = $result['seats_left'];
    $course_name = $result['course_name'];
    $stmt->close();

    if ($seats_left <= 0) {
        redirect_with_msg("Cannot approve: course is full.");
    }

    // Approve enrollment
    $stmt = $conn->prepare("UPDATE enrollments SET status='Approved' WHERE id=?");
    $stmt->bind_param("i", $enroll_id);
    $stmt->execute();
    $stmt->close();

    // Auto-update student's profile
    $update_stmt = $conn->prepare("
        UPDATE students s JOIN course_records c ON c.id = ?
        SET s.department = c.department, s.semester = c.semester, s.course = c.course_name
        WHERE s.student_id = ?
    ");
    $update_stmt->bind_param("ii", $course_id, $student_id);
    $update_stmt->execute();
    $update_stmt->close();

    // --- NEW: Send notification to student (Fix #5) ---
    $message = "Congratulations! Your enrollment for '" . $conn->real_escape_string($course_name) . "' has been approved.";
    $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'success')");
    $notif_stmt->bind_param("is", $student_id, $message);
    $notif_stmt->execute();
    $notif_stmt->close();
    // --- End Notification ---
    
    redirect_with_msg("Enrollment approved.");
}

// --- MODIFIED: Reject request ---
if (isset($_GET['reject'])) {
    $enroll_id = (int)$_GET['reject'];

    // --- NEW: Get student_id and course_name for notification (Fix #5) ---
    $stmt = $conn->prepare("
        SELECT e.student_id, c.course_name 
        FROM enrollments e 
        JOIN course_records c ON e.course_id = c.id 
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $enroll_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result_row = $result->fetch_assoc()) {
        $student_id = $result_row['student_id'];
        $course_name = $result_row['course_name'];
        $stmt->close();

        // Update status to Rejected
        $stmt = $conn->prepare("UPDATE enrollments SET status='Rejected' WHERE id=?");
        $stmt->bind_param("i", $enroll_id);
        $stmt->execute();
        $stmt->close();

        // Send notification
        $message = "Your enrollment application for '" . $conn->real_escape_string($course_name) . "' was not approved. Please contact an administrator for details.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message, type) VALUES (?, ?, 'error')");
        $notif_stmt->bind_param("is", $student_id, $message);
        $notif_stmt->execute();
        $notif_stmt->close();
    } else {
        $stmt->close();
    }
    // --- End Notification ---
    
    redirect_with_msg("Enrollment rejected.");
}

// --- Delete Enrollment ---
if (isset($_GET['delete'])) {
    $enroll_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM enrollments WHERE id=?");
    $stmt->bind_param("i", $enroll_id);
    $stmt->execute();
    $stmt->close();
    redirect_with_msg("Enrollment deleted.");
}

// --- Main table query (MODIFIED to get course_id) ---
$result = $conn->query("
    SELECT 
        e.id AS enrollment_id,
        s.student_id,
        c.id AS course_id,
        s.name AS student_name,
        s.email,
        c.department AS department_name,
        c.semester AS semester_name,
        c.course_name,
        e.status,
        e.enroll_date
    FROM enrollments e
    LEFT JOIN students s ON e.student_id = s.student_id
    LEFT JOIN course_records c ON e.course_id = c.id
    ORDER BY e.status = 'Pending' DESC, e.id DESC
");

// Get session message
$msg = $_SESSION['admin_message'] ?? null;
unset($_SESSION['admin_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OCRS - Enrollments</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ... [Your existing CSS] ... */
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
    .header-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem; /* Was 30px */
        position: relative;
        padding-bottom: 1.5rem; /* Was 24px */
        border-bottom: 2px solid var(--border-color); /* Used theme variable */
    }
    .main-content h1 {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-primary); /* Used theme variable */
        margin: 0;
        display: flex;
        align-items: center;
        gap: 15px;
        letter-spacing: -0.025em;
    }
    .main-content h1 i {
        color: var(--accent-blue); /* Used theme variable */
        font-size: 32px;
    }
    .message {
        padding: 16px 20px; border-radius: 12px;
        font-size: 15px; font-weight: 500;
        border: 1px solid transparent;
        margin-bottom: 24px;
        animation: slideIn 0.4s ease-out;
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        color: #065f46;
        border-color: rgba(34, 197, 94, 0.2);
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
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
    table th, table td {
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
    table tr:last-child td { border-bottom: none; }
    table tr:hover { background: rgba(59, 130, 246, 0.05); }
    .pill {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #ffffff;
    }
    .pill.pending {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);
    }
    .pill.approved {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    .pill.rejected {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    .action-buttons { display: flex; align-items: center; gap: 10px; }
    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%; /* Circular buttons */
        text-decoration: none;
        color: #ffffff;
        font-size: 14px;
        transition: all 0.3s ease;
        box-shadow: var(--shadow-sm);
        border: none;
        cursor: pointer;
    }
    .btn-icon:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .btn-icon.btn-view { background: var(--accent-blue); }
    .btn-icon.btn-approve { background: var(--accent-green); }
    .btn-icon.btn-reject { background: var(--accent-yellow); }
    .btn-icon.btn-delete { background: var(--accent-red); }
    @media (max-width: 1200px) {
        .main-content { max-width: 95%; margin: 15px auto; padding: 32px 24px; }
    }
    @media (max-width: 768px) {
        body { padding-top: 70px; }
        .main-content { margin: 10px; padding: 24px 16px; border-radius: 12px; }
        .main-content h1 { font-size: 24px; }
        .table-container { overflow-x: auto; }
    }
    .pending-badge {
        position: absolute; top: 2px; right: 5px; background: #ef4444;
        color: #ffffff; font-size: 11px; font-weight: 600;
        height: 18px; width: 18px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid var(--navbar-bg); 
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    
    /* --- NEW: Modal Styles --- */
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
        width: 90%; max-width: 800px; /* Wider modal */
        box-shadow: var(--shadow-lg);
        transform: scale(0.95); opacity: 0;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .modal-overlay.show .modal-content { transform: scale(1); opacity: 1; }
    .modal-header { 
        display: flex; justify-content: space-between; align-items: center; 
        margin-bottom: 20px; border-bottom: 1px solid var(--border-color);
        padding-bottom: 1rem;
    }
    .modal-header h2 { font-size: 24px; color: var(--text-primary); font-weight: 700; }
    .close-btn { 
        background: none; border: none; font-size: 28px; cursor: pointer; 
        color: var(--text-secondary); transition: color 0.2s, transform 0.2s; 
    }
    .close-btn:hover { color: var(--text-primary); transform: rotate(90deg); }

    #modalSpinner {
        display: none; text-align: center; padding: 40px;
    }
    #modalSpinner i {
        font-size: 40px; color: var(--accent-blue);
        animation: spin 1.5s linear infinite;
    }
    #modalSpinner p { margin-top: 15px; font-weight: 500; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* NEW: Modal Grid Layout */
    .modal-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }
    @media (max-width: 700px) {
        .modal-grid { grid-template-columns: 1fr; }
    }
    .modal-section {
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
    }
    .modal-section h3 {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-primary);
        padding-bottom: 10px;
        margin-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center; gap: 8px;
    }
    .detail-grid {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 12px 10px;
        align-items: center;
    }
    .detail-grid .label {
        font-weight: 600;
        color: var(--text-secondary);
        font-size: 14px;
        text-align: right;
    }
    .detail-grid .value {
        font-weight: 500;
        color: var(--text-primary);
        font-size: 15px;
        word-break: break-word;
    }
    .detail-grid .value img {
        width: 80px; height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
    }
    
    /* NEW: Comparison Ticks/Crosses */
    .comparison-icon {
        font-size: 1.2em;
        margin-right: 8px;
        vertical-align: middle;
    }
    .icon-success { color: var(--accent-green); }
    .icon-error { color: var(--accent-red); }
    
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
            <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
            <li class="nav-item"><a href="department.php" class="nav-link"><i class="fas fa-building"></i><span>Departments</span></a></li>
            <li class="nav-item"><a href="course_management.php" class="nav-link"><i class="fas fa-book"></i><span>Courses</span></a></li>
            <li class="nav-item"><a href="student_management.php" class="nav-link"><i class="fas fa-user-graduate"></i><span>Students</span></a></li>
            <li class="nav-item">
                <a href="enrollments.php" class="nav-link active">
                    <i class="fas fa-user-check"></i>
                    <span>Enrollments</span>
                    <?php if ($pending_count > 0): ?>
                      <span class="pending-badge"><?= $pending_count ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-line"></i><span>Reports</span></a></li>
        </ul>
        
        </div>
  </nav>

  <div class="main-content">
    <div class="header-section">
      <h1><i class="fas fa-user-check"></i> Enrollment Management</h1>
    </div>

    <?php if (!empty($msg)): ?>
      <div class="message"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="table-container">
      <table class="enroll-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Course</th>
            <th>Status</th>
            <th style="width:180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['student_name'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($row['course_name'] ?? 'N/A') ?></td>
              <td>
                <?php if ($row['status'] === 'Pending'): ?>
                  <span class="pill pending">Pending</span>
                <?php elseif ($row['status'] === 'Approved'): ?>
                  <span class="pill approved">Approved</span>
                <?php else: ?>
                  <span class="pill rejected">Rejected</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="action-buttons">
                    <a class="btn-icon btn-view" title="View Student Details" href="#" onclick="viewStudentDetails(<?= (int)($row['student_id'] ?? 0) ?>, <?= (int)($row['course_id'] ?? 0) ?>); return false;">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php if ($row['status'] === 'Pending'): ?>
                        <a class="btn-icon btn-approve" title="Approve" href="enrollments.php?approve=<?= (int)$row['enrollment_id'] ?>">
                            <i class="fas fa-check"></i>
                        </a>
                        <a class="btn-icon btn-reject" title="Reject" href="enrollments.php?reject=<?= (int)$row['enrollment_id'] ?>">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                    <a class="btn-icon btn-delete" title="Delete" href="enrollments.php?delete=<?= (int)$row['enrollment_id'] ?>" onclick="showDeleteModal(event, 'enrollments.php?delete=<?= (int)$row['enrollment_id'] ?>')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

<div id="deleteModal" class="modal-overlay" style="padding-top: 15vh;">
  <div class="modal-content" style="max-width: 400px; text-align: center;">
    <h3 style="margin-bottom: 15px; font-size: 22px;">Confirm Deletion</h3>
    <p style="margin-bottom: 25px; color: #475569;">Are you sure you want to delete this enrollment record? This action cannot be undone.</p>
    <div style="display: flex; justify-content: center; gap: 15px;">
      <button id="cancelBtn" class="action-button secondary" style="width: 120px; justify-content: center;">Cancel</button>
      <a id="confirmDeleteBtn" href="#" class="action-button" style="background: var(--accent-red); width: 120px; justify-content: center; text-decoration: none;">Delete</a>
    </div>
  </div>
</div>

<!-- MODIFIED: Details Modal -->
<div id="studentDetailsModal" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">
        <h2 id="modal-title">Student Eligibility Details</h2>
        <button onclick="closeDetailsModal()" class="close-btn">&times;</button>
    </div>
    
    <div id="modalSpinner">
        <i class="fas fa-spinner"></i>
        <p>Loading details...</p>
    </div>

    <div id="modalContent" style="display:none;">
        <div class="modal-grid">
            <div class="modal-section" id="student-info-box">
                <h3><i class="fas fa-user-graduate" style="color: var(--accent-blue);"></i>Student Info</h3>
                <div class="detail-grid">
                    <span class="label">Photo:</span>
                    <div class="value"><img id="student_photo" src="uploads/default.png" alt="Profile"></div>
                    <span class="label">Name:</span>
                    <div class="value" id="student_name"></div>
                    <span class="label">Email:</span>
                    <div class="value" id="student_email"></div>
                    <span class="label">Phone:</span>
                    <div class="value" id="student_phone"></div>
                    <span class="label">Reg No:</span>
                    <div class="value" id="student_reg"></div>
                    <span class="label">Board:</span>
                    <div class="value" id="student_board"></div>
                    <span class="label">Institution:</span>
                    <div class="value" id="student_institution"></div>
                </div>
            </div>

            <div class="modal-section" id="comparison-box">
                <h3><i class="fas fa-tasks" style="color: var(--accent-green);"></i>Eligibility Check</h3>
                <div class="detail-grid">
                    <span class="label">Course:</span>
                    <div class="value" id="course_name"></div>
                    
                    <span class="label">Min %:</span>
                    <div class="value" id="compare_perc"></div>
                    
                    <span class="label">Min Qual:</span>
                    <div class="value" id="compare_qual"></div>
                    
                    <span class="label">Req Subjects:</span>
                    <div class="value" id="compare_subj"></div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>


  <script>
    // --- Navbar Scroll & Hamburger ---
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

    // --- Delete Modal ---
    const deleteModal = document.getElementById('deleteModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    function showDeleteModal(event, deleteUrl) {
        event.preventDefault();
        event.stopPropagation();
        confirmDeleteBtn.href = deleteUrl;
        deleteModal.classList.add('show');
    }
    if(cancelBtn) {
        cancelBtn.onclick = () => deleteModal.classList.remove('show');
    }

    // --- MODIFIED: Details Modal (Fix #4) ---
    const detailsModal = document.getElementById('studentDetailsModal');
    const modalContent = document.getElementById('modalContent');
    const modalSpinner = document.getElementById('modalSpinner');

    async function viewStudentDetails(studentId, courseId) {
        if (!studentId || !courseId) {
            alert('Error: Invalid student or course ID.');
            return;
        }

        // Show modal and spinner
        detailsModal.classList.add('show');
        modalContent.style.display = 'none';
        modalSpinner.style.display = 'block';

        try {
            // Fetch the comparison data
            const response = await fetch(`get_eligibility_comparison.php?student_id=${studentId}&course_id=${courseId}`);
            if (!response.ok) throw new Error('Network response was not ok.');
            
            const data = await response.json();
            if (data.error) throw new Error(data.error);

            // 1. Populate Student Info
            document.getElementById('student_photo').src = data.student.profile_picture || 'uploads/default.png';
            document.getElementById('student_name').textContent = data.student.name || 'N/A';
            document.getElementById('student_email').textContent = data.student.email || 'N/A';
            document.getElementById('student_phone').textContent = data.student.phone || 'N/A';
            document.getElementById('student_reg').textContent = data.student.reg_no || 'N/A';
            document.getElementById('student_board').textContent = data.student.previous_board || 'N/A';
                document.getElementById('student_institution').textContent = data.student.previous_institution || 'N/A';

            // 2. Populate Comparison Info
            document.getElementById('course_name').textContent = data.course.course_name || 'N/A';
            
            // Percentage
            document.getElementById('compare_perc').innerHTML = `
                ${data.comparison.percentage_met 
                    ? '<i class="fas fa-check-circle comparison-icon icon-success"></i>' 
                    : '<i class="fas fa-times-circle comparison-icon icon-error"></i>'}
                Student has <strong>${data.student.previous_percentage || '0'}%</strong> (Required: ${data.course.minimum_percentage || '0'}%)
            `;

            // Qualification
            document.getElementById('compare_qual').innerHTML = `
                ${data.comparison.qualification_met 
                    ? '<i class="fas fa-check-circle comparison-icon icon-success"></i>' 
                    : '<i class="fas fa-times-circle comparison-icon icon-error"></i>'}
                Student has <strong>${data.student.previous_qualification || 'N/A'}</strong> (Required: ${data.course.required_qualification || 'N/A'})
            `;

            // Subjects
            document.getElementById('compare_subj').innerHTML = `
                ${data.comparison.subjects_met 
                    ? '<i class="fas fa-check-circle comparison-icon icon-success"></i>' 
                    : '<i class="fas fa-times-circle comparison-icon icon-error"></i>'}
                Student has <strong>"${data.student.previous_subjects || 'None'}"</strong> (Required: "${data.course.required_subjects || 'None'}")
            `;
            
            // Show content
            modalSpinner.style.display = 'none';
            modalContent.style.display = 'block';

        } catch (error) {
            modalSpinner.innerHTML = `<p style="color: var(--accent-red);">Failed to load details: ${error.message}</p>`;
        }
    }

    function closeDetailsModal() {
        detailsModal.classList.remove('show');
    }

    // Close modal if user clicks outside
    window.onclick = function(event) {
        if (event.target == deleteModal) {
            deleteModal.classList.remove('show');
        }
        if (event.target == detailsModal) {
            closeDetailsModal();
        }
    }
    
    // Auto-hide success message
    const messageElement = document.querySelector('.message');
    if (messageElement) {
        setTimeout(() => {
            messageElement.style.transition = 'opacity 0.5s ease';
            messageElement.style.opacity = '0';
            setTimeout(() => messageElement.remove(), 500);
        }, 5000);
    }
  </script>
</body>
</html>