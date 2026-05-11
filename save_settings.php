<?php
require_once 'session_config.php';
require_once 'db_connect.php';

// Only admins can do this
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get the data sent from JavaScript
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['key']) && isset($data['value'])) {
    $key = $data['key'];
    $value_raw = $data['value'];

    // NEW LOGIC: Handle booleans (toggles) and strings (text fields)
    if (is_bool($value_raw)) {
        $value = $value_raw ? '1' : '0';
    } else {
        $value = (string)$value_raw; // Cast to string for text fields
    }

    // Whitelist allowed keys to prevent security issues
    // ADDED new keys to the whitelist
    $allowed_keys = [
        'open_registrations', 
        'maintenance_mode', 
        'email_alerts',
        'institution_name',
        'system_name',
        'institution_address',
        'institution_contact'
    ];

    if (in_array($key, $allowed_keys)) {
        // Use an UPDATE query to change the existing setting
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Settings updated!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid setting key: ' . $key]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data.']);
}
$conn->close();
?>