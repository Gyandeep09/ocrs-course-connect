<?php
require_once 'session_config.php';
include 'db_connect.php';

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$student_id = $_SESSION['student_id'];

// --- 1. Stats Cards ---

// Enrolled = Approved, no grade
$enrolled = $conn->query("
    SELECT COUNT(*) AS total 
    FROM enrollments 
    WHERE student_id = $student_id AND status='Approved' AND (grade IS NULL OR grade = '')
")->fetch_assoc()['total'];

// Pending
$pending = $conn->query("
    SELECT COUNT(*) AS total 
    FROM enrollments 
    WHERE student_id = $student_id AND status='Pending'
")->fetch_assoc()['total'];

// Completed = Approved + has grade (from enrollments)
$completed_enrollments = $conn->query("
    SELECT COUNT(*) AS total 
    FROM enrollments 
    WHERE student_id = $student_id AND status='Approved' AND grade IS NOT NULL
")->fetch_assoc()['total'];

// Completed = from history
$completed_history = $conn->query("
    SELECT COUNT(*) AS total 
    FROM enrollment_history 
    WHERE student_id = $student_id
")->fetch_assoc()['total'];

$completed_total = $completed_enrollments + $completed_history;

// CGPA (combines both tables)
$cgpa_sql = "
    SELECT AVG(gpa) AS final_gpa FROM (
        SELECT (CASE grade 
            WHEN 'A+' THEN 4.0 WHEN 'A' THEN 3.7 WHEN 'B+' THEN 3.3 WHEN 'B' THEN 3.0 
            WHEN 'C+' THEN 2.5 WHEN 'C' THEN 2.0 WHEN 'D' THEN 1.0 WHEN 'F' THEN 0.0 
            ELSE NULL END) AS gpa 
        FROM enrollments 
        WHERE student_id = $student_id AND grade IS NOT NULL AND grade != ''
        
        UNION ALL
        
        SELECT (CASE grade 
            WHEN 'A+' THEN 4.0 WHEN 'A' THEN 3.7 WHEN 'B+' THEN 3.3 WHEN 'B' THEN 3.0 
            WHEN 'C+' THEN 2.5 WHEN 'C' THEN 2.0 WHEN 'D' THEN 1.0 WHEN 'F' THEN 0.0 
            ELSE NULL END) AS gpa 
        FROM enrollment_history 
        WHERE student_id = $student_id AND grade IS NOT NULL AND grade != ''
    ) AS all_grades
";
$cgpa_row = $conn->query($cgpa_sql)->fetch_assoc();
$cgpa = $cgpa_row['final_gpa'] ? round($cgpa_row['final_gpa'], 2) : 0.00;


// --- 2. Recent Activity List (NEW) ---
$activity = [];

// Get last 5 notifications (most recent first)
$notif_result = $conn->query("
    SELECT message, type, created_at 
    FROM notifications 
    WHERE student_id = $student_id 
    ORDER BY created_at DESC 
    LIMIT 5
");

if ($notif_result) {
    while ($row = $notif_result->fetch_assoc()) {
        $title = "Notification";
        if ($row['type'] == 'success') $title = 'Enrollment Approved';
        if ($row['type'] == 'error') $title = 'Enrollment Rejected';
        
        $activity[] = [
            'title' => $title,
            'description' => $row['message'],
            'type' => $row['type'] // 'success', 'error', 'info'
        ];
    }
}

// Get last pending request
$pending_result = $conn->query("
    SELECT c.course_name 
    FROM enrollments e
    JOIN course_records c ON e.course_id = c.id
    WHERE e.student_id = $student_id AND e.status = 'Pending'
    LIMIT 1
");
if ($pending_result && $row = $pending_result->fetch_assoc()) {
     $activity[] = [
        'title' => 'Enrollment Pending',
        'description' => 'Your request for ' . htmlspecialchars($row['course_name']) . ' is awaiting approval.',
        'type' => 'pending'
    ];
}


// --- 3. Send JSON Response ---
header('Content-Type: application/json');
echo json_encode([
    'enrolled' => $enrolled,
    'pending' => $pending,
    'completed' => $completed_total,
    'cgpa' => $cgpa,
    'activity' => $activity // Send the new activity array
]);

$conn->close();
?>