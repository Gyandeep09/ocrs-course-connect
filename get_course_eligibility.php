<?php
require_once 'session_config.php';
require_once 'db_connect.php'; // Your db_connect.php

// Only students can access this
if (!isset($_SESSION['student_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Ensure an ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No course ID provided']);
    exit;
}

$course_id = (int)$_GET['id'];
$student_id = $_SESSION['student_id'];

// 1. Fetch Course Requirements
$stmt_course = $conn->prepare("SELECT course_name, minimum_percentage, required_qualification, required_subjects FROM course_records WHERE id = ?");
$stmt_course->bind_param("i", $course_id);
$stmt_course->execute();
$result_course = $stmt_course->get_result();
$course = $result_course->fetch_assoc();
$stmt_course->close();

if (!$course) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Course not found']);
    exit;
}

// 2. Fetch Student's Current Data (to pre-fill the form)
$stmt_student = $conn->prepare("SELECT previous_qualification, previous_percentage, previous_subjects, previous_board, previous_institution FROM students WHERE student_id = ?");
$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();
$student = $result_student->fetch_assoc();
$stmt_student->close();

// Set header and return all data as JSON
header('Content-Type: application/json');
echo json_encode([
    'course_requirements' => $course,
    'student_data' => $student
]);

$conn->close();
?>