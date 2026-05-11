<?php
$conn = new mysqli("localhost", "root", "", "ocrs_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT COUNT(*) AS cnt FROM enrollments WHERE status='Pending'");
$row = $result->fetch_assoc();
echo $row['cnt'];
$conn->close();
?>
