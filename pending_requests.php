<?php
$conn = new mysqli("localhost", "root", "", "ocrs_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$result = $conn->query("
    SELECT e.id, 
           s.name, 
           c.course_name, 
           e.enroll_date, 
           e.status
    FROM enrollments e
    LEFT JOIN students s ON e.student_id = s.student_id
    LEFT JOIN course_records c ON e.course_id = c.id
    ORDER BY e.id DESC
");
$result = $conn->query($sql);

$pending = [];
while ($row = $result->fetch_assoc()) {
    $pending[] = $row;
}

header('Content-Type: application/json');
echo json_encode($pending);
$conn->close();
?>
