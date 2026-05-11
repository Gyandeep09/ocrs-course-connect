<?php
require_once 'session_config.php';
require_once 'db_connect.php'; // Ensure you have a db_connect.php

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$student_id = $_SESSION['student_id'];

// 1. Fetch unread notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE student_id = ? AND is_read = 0 ORDER BY created_at ASC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 2. Mark them as read
if (!empty($notifications)) {
    $ids = implode(',', array_column($notifications, 'id'));
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id IN ($ids)");
}

// 3. Return them
header('Content-Type: application/json');
echo json_encode($notifications);

$conn->close();
?>