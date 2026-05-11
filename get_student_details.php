<?php
// OCRS/get_student_details.php

require_once 'session_config.php';

// Ensure an admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Ensure a student ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Student ID not provided']);
    exit;
}

require_once 'db_connect.php'; // Use your existing DB connection

$student_id = (int)$_GET['id'];

// Updated query to fetch all required fields, including new ones and profile picture
$stmt = $conn->prepare("SELECT name, email, phone, age, address, reg_no, profile_picture, previous_qualification, previous_board, previous_percentage, previous_institution FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Student not found']);
    exit;
}

// Set the content type to JSON and output the data
header('Content-Type: application/json');
echo json_encode($student);
?>