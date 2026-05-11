<?php
require_once 'db_connect.php';

// Total Departments (no change)
$departments = $conn->query("SELECT COUNT(*) AS total FROM departments")->fetch_assoc()['total'];

// Total Courses (no change)
$courses = $conn->query("SELECT COUNT(*) AS total FROM course_records")->fetch_assoc()['total'];

// --- MODIFIED: Total Students ---
// This query now counts unique students with at least one 'Approved' enrollment,
// matching the logic from student_management.php.
$students_query = "
    SELECT COUNT(DISTINCT s.student_id) AS total
    FROM students s
    INNER JOIN enrollments e ON s.student_id = e.student_id
    WHERE e.status = 'Approved'
";
$students_result = $conn->query($students_query);
$students = $students_result ? $students_result->fetch_assoc()['total'] : 0; // Ensures it shows 0 if no students are found

// Pending Enrollments (no change)
$pendingEnrollments = $conn->query("SELECT COUNT(*) AS total FROM enrollments WHERE status='Pending'")->fetch_assoc()['total'];

// --- MODIFIED: System Reports Metric ---
// The value for the "System Reports" card is now set to be the same as the new student count.
// In your dashboard, this corresponds to the 'completedCourses' key.
$completedCourses = $students;

// Average CGPA (no change)
$cgpaRow = $conn->query("
    SELECT AVG(CASE grade 
        WHEN 'A+' THEN 4.0 
        WHEN 'A'  THEN 3.7 
        WHEN 'B+' THEN 3.3 
        WHEN 'B'  THEN 3.0 
        WHEN 'C+' THEN 2.5 
        WHEN 'C'  THEN 2.0 
        WHEN 'D'  THEN 1.0 
        WHEN 'F'  THEN 0.0 
        ELSE NULL END) AS gpa
    FROM enrollments
    WHERE grade IS NOT NULL
")->fetch_assoc();

$cgpa = $cgpaRow['gpa'];
$cgpa = is_null($cgpa) ? 0 : round($cgpa, 2);

// Send JSON
echo json_encode([
    "departments" => $departments,
    "courses" => $courses,
    "students" => $students,
    "pendingEnrollments" => $pendingEnrollments,
    "completedCourses" => $completedCourses, // This key now sends the approved student count
    "averageCgpa" => $cgpa
]);
?>