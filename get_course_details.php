<?php
require_once 'session_config.php';
require_once 'db_connect.php';

// Only admins can access this
if (!isset($_SESSION['admin_id'])) {
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

// Fetch the course data
$stmt = $conn->prepare("SELECT * FROM course_records WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$stmt->close();

if (!$course) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Course not found']);
    exit;
}

// Set header and return data as JSON
header('Content-Type: application/json');
echo json_encode($course);

$conn->close();
?>