<?php
require_once 'session_config.php';
require_once 'db_connect.php'; // Ensure you have a db_connect.php

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$student_id = (int)($_GET['student_id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);

if ($student_id === 0 || $course_id === 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

// 1. Fetch student's submitted data
$stmt_student = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();
$student_result = $stmt_student->get_result();
$student = $student_result->fetch_assoc();
$stmt_student->close();

if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit;
}

// 2. Fetch course requirements
$stmt_course = $conn->prepare("SELECT * FROM course_records WHERE id = ?");
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$course_result = $stmt_course->get_result();
$course = $course_result->fetch_assoc();
$stmt_course->close();

if (!$course) {
    echo json_encode(['error' => 'Course not found']);
    exit;
}

// 3. Perform comparison logic
$comparison = [];
$student_perc = (float)($student['previous_percentage'] ?? 0);
$course_perc = (float)($course['minimum_percentage'] ?? 0);
$comparison['percentage_met'] = $student_perc >= $course_perc;

$student_qual = trim(strtolower($student['previous_qualification'] ?? ''));
$course_qual = trim(strtolower($course['required_qualification'] ?? ''));
if ($course_qual === 'others' || empty($course_qual)) {
    $comparison['qualification_met'] = true; 
} else {
    $comparison['qualification_met'] = $student_qual === $course_qual;
}

$student_subs = strtolower($student['previous_subjects'] ?? '');
$course_subs_str = strtolower($course['required_subjects'] ?? '');
$all_subjects_met = true;

if (!empty($course_subs_str)) {
    $required_subjects = array_map('trim', explode(',', $course_subs_str));
    foreach ($required_subjects as $req_sub) {
        if (!empty($req_sub) && strpos($student_subs, $req_sub) === false) {
            $all_subjects_met = false;
            break;
        }
    }
}
$comparison['subjects_met'] = $all_subjects_met;


// 4. Send JSON response
header('Content-Type: application/json');
echo json_encode([
    'student' => $student,
    'course' => $course,
    'comparison' => $comparison
]);

$conn->close();
?>