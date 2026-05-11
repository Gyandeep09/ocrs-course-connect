<?php
// fetch_stats.php
$conn = new mysqli("localhost","root","","ocrs_db");
if($conn->connect_error) die("DB Error: ".$conn->connect_error);

// Students: Modified to count only students with an 'Approved' enrollment status.
$students_query = "
    SELECT COUNT(DISTINCT s.student_id) AS total
    FROM students s
    INNER JOIN enrollments e ON s.student_id = e.student_id
    WHERE e.status = 'Approved'
";
$students_result = $conn->query($students_query);
$students = $students_result ? $students_result->fetch_assoc()['total'] : 0;


// Courses
$courses = $conn->query("SELECT COUNT(*) AS total FROM course_records")->fetch_assoc()['total'];

// Instructors: This is still a fallback as per your original code.
// Consider creating an 'instructors' table for an accurate count later.
$instructors = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total']; // temporary fallback

// Awards: Static value as per your original code.
$awards = 15;

echo json_encode([
    "students" => $students,
    "courses" => $courses,
    "instructors" => $instructors,
    "awards" => $awards
]);
?>