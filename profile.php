<?php
require_once 'session_config.php';
if (!isset($_SESSION['student_id'])) {
    header('Location: student_page.php');
    exit;
}

require_once 'db_connect.php';
$student_id = $_SESSION['student_id'];
$is_first_time = isset($_GET['first_time']); // Check if this is the first-time setup

$success_message = "";
$error_message = "";

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // SCENARIO 1: FIRST-TIME PROFILE COMPLETION (This logic seems to be for a different page, 'complete_profile.php')
    if ($is_first_time) {
        // This logic is retained but seems to belong to 'complete_profile.php'
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $age = !empty($_POST['age']) ? intval($_POST['age']) : NULL;
        $profile_picture = null; 

        if (empty($phone) || empty($address) || empty($age)) {
            $_SESSION['error_message'] = "Phone, Address, and Age are required. Please complete your profile.";
            header('Location: complete_profile.php'); // This redirects to a different page
            exit;
        }

        if (!empty($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/profile_pics/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $file_name = time() . "_" . basename($_FILES["profile_picture"]["name"]);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $profile_picture = $target_file; 
            } else {
                 $_SESSION['warning_message'] = "Profile saved, but profile picture upload failed. You can add it later.";
            }
        }
        
        $stmt = $mysqli->prepare("UPDATE students SET phone=?, address=?, age=?, profile_picture=?, profile_completed=1 WHERE student_id=?");
        $stmt->bind_param("ssisi", $phone, $address, $age, $profile_picture, $student_id);

        if ($stmt->execute()) {
            if(isset($_SESSION['warning_message'])) {
                 $_SESSION['success'] = $_SESSION['warning_message'];
                 unset($_SESSION['warning_message']);
            } else {
                 $_SESSION['success'] = "Welcome! Your profile is now complete.";
            }
            header("Location: student_dashboard.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Database error. Could not save profile.";
            header('Location: complete_profile.php');
            exit;
        }
        $stmt->close();
    } 
    // SCENARIO 2: REGULAR PROFILE UPDATE (This is the correct logic for profile.php)
    else {
        // Fetch current student data to get current password hash
        $fetch_stmt = $mysqli->prepare("SELECT password, profile_picture FROM students WHERE student_id = ?");
        $fetch_stmt->bind_param("i", $student_id);
        $fetch_stmt->execute();
        $current_student_data = $fetch_stmt->get_result()->fetch_assoc();
        $fetch_stmt->close();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $age = !empty($_POST['age']) ? intval($_POST['age']) : NULL;
        
        $current_password = trim($_POST['current_password'] ?? '');
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $password_change = false;

        $profile_picture_path = $current_student_data['profile_picture']; // Keep old pic by default
        if (!empty($_FILES['profile_picture']['name'])) {
            $target_dir = "uploads/profile_pics/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $file_name = time() . "_" . basename($_FILES["profile_picture"]["name"]);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $profile_picture_path = $target_file;
            } else {
                $error_message = "Failed to upload new profile picture.";
            }
        }
        
        if (empty($name) || empty($email)) {
            $error_message = "Name and Email cannot be empty.";
        }
        
        if (!empty($current_password) && empty($error_message)) {
            if (password_verify($current_password, $current_student_data['password'])) {
                if ($new_password === $confirm_password && strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_change = true;
                } else {
                    $error_message = "New passwords must match and be at least 6 characters.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
        
        if (empty($error_message)) {
            if ($password_change) {
                $update_stmt = $mysqli->prepare("UPDATE students SET name=?, email=?, phone=?, address=?, age=?, profile_picture=?, password=? WHERE student_id=?");
                $update_stmt->bind_param("ssssissi", $name, $email, $phone, $address, $age, $profile_picture_path, $hashed_password, $student_id);
            } else {
                $update_stmt = $mysqli->prepare("UPDATE students SET name=?, email=?, phone=?, address=?, age=?, profile_picture=? WHERE student_id=?");
                $update_stmt->bind_param("ssssisi", $name, $email, $phone, $address, $age, $profile_picture_path, $student_id);
            }

            if ($update_stmt->execute()) {
                $success_message = $password_change ? "Profile and password updated successfully!" : "Profile updated successfully!";
                $_SESSION['name'] = $name; // Update session name
            } else {
                $error_message = "Error updating profile.";
            }
            $update_stmt->close();
        }
    }
}

// AUTO-GENERATE REGISTRATION NUMBER if missing
$check_reg_stmt = $mysqli->prepare("SELECT reg_no FROM students WHERE student_id = ?");
$check_reg_stmt->bind_param("i", $student_id);
$check_reg_stmt->execute();
$reg_row = $check_reg_stmt->get_result()->fetch_assoc();
$check_reg_stmt->close();
if (empty($reg_row['reg_no'])) {
    $year = date("Y");
    $seq_stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM students WHERE reg_no LIKE ?");
    $like_pattern = "%$year%";
    $seq_stmt->bind_param("s", $like_pattern);
    $seq_stmt->execute();
    $count_data = $seq_stmt->get_result()->fetch_assoc();
    $seq_stmt->close();
    $sequence = str_pad(($count_data['total'] + 1), 4, '0', STR_PAD_LEFT);
    $new_reg_no = "REG$year-" . $sequence;
    $update_reg_stmt = $mysqli->prepare("UPDATE students SET reg_no=? WHERE student_id=?");
    $update_reg_stmt->bind_param("si", $new_reg_no, $student_id);
    $update_reg_stmt->execute();
    $update_reg_stmt->close();
}

// FETCH updated student info for display
$stmt = $mysqli->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$student_name = htmlspecialchars($student['name']);
$profile_picture_display = !empty($student['profile_picture']) ? htmlspecialchars($student['profile_picture']) : 'uploads/default.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCRS - My Profile</title>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
            document.body.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* ============================================
    ADMIN MASTER STYLESHEET (UNIFIED)
    ============================================
    */
    :root {
        --bg-color: #f4f7fa; 
        --navbar-bg: rgba(255, 255, 255, 0.85); 
        --widget-bg: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --accent-blue: #3b82f6;
        --accent-green: #10b981;
        --accent-red: #ef4444;
        --accent-yellow: #f59e0b;
        --active-line: #088178; 
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --readonly-bg: #eef2ff; /* For readonly inputs */
        --readonly-color: #4338ca;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    body {
        font-family: "Spartan", sans-serif;
        background: var(--bg-color);
        color: var(--text-primary);
        margin: 0;
        min-height: 100vh;
        padding-top: 70px; /* Space for fixed navbar */
    }

    /* ============================================
    UNIFIED 3-COLUMN GRID NAVBAR
    ============================================ */
    .navbar {
        background: var(--navbar-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 0.5rem 0;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 20px rgba(0,0,0,0.07);
        border-bottom: 1px solid var(--border-color);
        transition: top 0.3s ease-out;
    }
    
    .nav-container, .navbar-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 2rem;
        height: 60px;
        display: grid;
        grid-template-columns: 1fr auto 1fr; /* [Left] [Center] [Right] */
        align-items: center;
    }
    
    .nav-brand, .navbar-brand {
        grid-column: 1 / 2; /* Column 1 */
        justify-self: start; /* Align to the left */
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--text-primary);
        font-size: 22px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .nav-brand i, .navbar-brand i { color: var(--accent-blue); font-size: 26px; }

    .hamburger {
        grid-column: 3 / 4; /* Column 3 */
        justify-self: end; /* Align to the right */
        display: none; /* Hidden on desktop */
        flex-direction: column;
        cursor: pointer;
        padding: 10px;
        z-index: 1001;
    }
    .hamburger span {
        width: 25px; height: 3px;
        background: var(--text-primary);
        margin: 3px 0; transition: 0.3s;
    }
    
    .nav-menu, .navbar-nav {
        grid-column: 2 / 3; /* Column 2 */
        justify-self: center; /* Center it */
        display: flex; 
        align-items: center;
        list-style: none;
        gap: 0.5rem;
        transition: all 0.4s ease-out;
        margin: 0; 
    }

    .nav-item { position: relative; }
    .nav-link {
        display: flex; align-items: center; gap: 8px;
        padding: 10px 16px;
        text-decoration: none;
        color: var(--text-secondary);
        font-weight: 600; 
        transition: all 0.3s;
        border-radius: 8px;
        position: relative;
        background: transparent; 
        white-space: nowrap;
    }
    .nav-link:hover { color: var(--active-line); background: transparent; }
    .nav-link.active { color: var(--active-line); background: transparent; }
    .nav-link.active::after,
    .nav-link:hover::after {
        content: "";
        width: 40%; height: 2px;
        background: var(--active-line);
        position: absolute;
        bottom: 0; left: 50%;
        transform: translateX(-50%);
        transition: width 0.3s ease;
    }
    .nav-link i { font-size: 16px; }

    /* ============================================
    STUDENT Profile / Right Side
    ============================================ */
    .nav-right {
        grid-column: 3 / 4; /* Column 3 */
        justify-self: end; /* Align to the right */
        display: flex;
        align-items: center;
        gap: 0.5rem; 
    }
    
    /* --- NEW: Theme Toggle Button --- */
    .icon-button {
        position: relative; background: transparent; border: none;
        color: var(--text-secondary);
        font-size: 20px; /* Icon size */
        cursor: pointer; transition: color 0.3s ease;
        width: 40px; /* Set fixed width */
        height: 40px; /* Set fixed height */
        border-radius: 50%; /* Make it round */
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .icon-button:hover { 
        color: var(--text-primary); 
        background-color: var(--bg-color);
    }
    
    .theme-dropdown { position: relative; }
    .theme-menu {
        position: absolute; top: 140%; right: 0;
        background: var(--widget-bg); border: 1px solid var(--border-color);
        border-radius: 8px; box-shadow: var(--shadow-lg);
        width: 220px; z-index: 110;
        opacity: 0; visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }
    .theme-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
    .theme-menu-header { padding: 1rem; border-bottom: 1px solid var(--border-color); }
    .theme-menu-header h4 { margin: 0; font-size: 15px; }
    .theme-menu-list { list-style: none; padding: 1rem; }
    .theme-menu-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 500;
    }

    /* --- Toggle Switch --- */
    .toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc; transition: .4s; border-radius: 24px;
    }
    .slider:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background-color: white;
        transition: .4s; border-radius: 50%;
    }
    input:checked + .slider { background-color: var(--accent-blue); }
    input:checked + .slider:before { transform: translateX(20px); }
    /* --- END: Theme Toggle --- */
   
    .profile-dropdown { position: relative; }
    .profile-button {
        display: flex;
        align-items: center;
        gap: 10px;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 4px; 
        border-radius: 50px; 
        transition: all 0.3s ease;
    }
    .profile-button:hover {
        background-color: var(--bg-color);
    }
    
    .profile-button img {
        width: 36px; 
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
        display: block;
    }
    .profile-button .student-name {
        font-weight: 600; 
        color: var(--text-primary); 
        font-size: 15px;
        margin-left: 2px;
        margin-right: 4px;
    }
    .profile-button i { 
        color: var(--text-secondary);
        margin-right: 4px;
        transition: transform 0.3s ease;
    }
    
    .profile-menu.show + .profile-button i {
        transform: rotate(180deg);
    }

    .profile-menu {
        position: absolute; top: 140%; right: 0;
        background: var(--widget-bg); border: 1px solid var(--border-color);
        border-radius: 8px; box-shadow: var(--shadow-lg);
        width: 220px; z-index: 110;
        opacity: 0; visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }
    .profile-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
    .profile-menu-header { padding: 1rem; border-bottom: 1px solid var(--border-color); }
    .profile-menu-header h4 { margin: 0 0 4px; font-size: 15px; }
    .profile-menu-header p { margin: 0; font-size: 13px; color: var(--text-secondary); }
    .profile-menu-list { list-style: none; padding: 0.5rem; }
    .profile-menu-list a {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; border-radius: 6px;
        text-decoration: none; color: var(--text-primary);
        font-size: 14px; transition: all 0.2s ease;
    }
    .profile-menu-list a:hover { background: var(--bg-color); }
    .profile-menu-list a.logout { color: var(--accent-red); }
    .profile-menu-list a.logout:hover { background: rgba(239, 68, 68, 0.1); }
    .profile-menu-list a i {
        width: 16px; text-align: center;
        color: var(--text-secondary);
    }
    .profile-menu-list a.logout i { color: var(--accent-red); }

    /* ============================================
    RESPONSIVE STYLES (Mobile Menu)
    ============================================ */
    @media (max-width: 1050px) { 
        .nav-container, .navbar-container {
            display: flex;
            justify-content: space-between;
        }
        .nav-menu, .navbar-nav { 
            display: none; 
            grid-column: auto; 
            justify-self: auto;
        }
        .nav-right {
             display: none; 
             grid-column: auto;
             justify-self: auto;
        }
        .hamburger { 
            display: flex; 
            grid-column: auto;
            justify-self: auto;
        }
        .nav-container.active .navbar-nav,
        .nav-container.active .nav-menu {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: absolute;
            top: 70px;
            left: 0;
            width: 100%;
            background: var(--navbar-bg);
            box-shadow: var(--shadow-md);
            padding: 1rem;
            animation: slideDown 0.3s ease-out forwards;
            max-height: 500px;
        }
        .nav-container.active .navbar-nav .nav-link,
        .nav-container.active .nav-menu .nav-link {
            width: 100%;
            padding: 12px;
        }
        .nav-container.active .navbar-nav .nav-link.active::after,
        .nav-container.active .navbar-nav .nav-link:hover::after,
        .nav-container.active .nav-menu .nav-link.active::after,
        .nav-container.active .nav-menu .nav-link:hover::after {
            width: 30px; 
            left: 16px; 
            transform: translateX(0); 
            bottom: 4px;
        }
        .hamburger.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    }


    /* ============================================
    ALL OTHER PAGE STYLES
    ============================================ */

    /* Main Content (Admin Theme) */
    .main-content {
        padding: 2rem;
        max-width: 1500px; /* <-- WIDENED for landscape layout */
        margin: 0 auto;
        animation: fadeInUp 0.6s ease-out;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Re-styled .complete-profile to .widget-card */
    .widget-card {
        background: var(--widget-bg);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        margin-top: 20px;
    }
    
    .page-header {
        display: flex;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid var(--border-color);
    }
    .page-header h1 {
        color: var(--text-primary);
        font-size: 28px;
        font-weight: 700;
    }
    .page-header i {
        font-size: 32px;
        color: var(--accent-blue);
        margin-right: 16px;
    }
    
    /* Profile Picture */
    .profile-picture-section { 
        text-align: center; 
        margin-bottom: 2rem; 
    }
    .current-picture { 
        width: 120px; 
        height: 120px; 
        border-radius: 50%; 
        object-fit: cover; 
        border: 4px solid var(--widget-bg); /* Changed border */
        box-shadow: 0 0 0 4px var(--accent-blue); /* Added outer ring */
        transition: transform 0.3s ease;
    }
    .current-picture:hover {
        transform: scale(1.05);
    }
    
    /* Form Grid (Admin Theme) */
    /* --- THIS IS THE FIX --- */
    .form-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px; 
        margin-bottom: 24px; 
    }
    /* --- END FIX --- */
    
    .form-group.full-width { 
        grid-column: 1 / -1; 
    }
    .form-group label { 
        display: block; 
        font-weight: 600; 
        margin-bottom: 8px; 
        color: #374151; 
        font-size: 15px; 
    }
    .form-group input, .form-group textarea { 
        width: 100%; 
        padding: 14px 16px; 
        border: 2px solid var(--border-color);
        border-radius: 10px;
        outline: none;
        font-size: 15px;
        font-family: "Spartan", sans-serif;
        background: #f8fafc;
        color: var(--text-primary);
        transition: all 0.3s ease;
    }
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .form-group input:focus, .form-group textarea:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        background: #fff;
    }
    
    .form-group input[readonly] { 
        background: var(--readonly-bg); 
        color: var(--readonly-color); 
        font-weight: 500; 
        cursor: not-allowed; 
        border-color: var(--border-color);
    }
    
    /* Password Section (Styled as a sub-widget) */
    .password-section { 
        background: var(--bg-color); 
        padding: 24px; 
        border-radius: 12px; 
        margin: 24px 0; 
        border: 1px solid var(--border-color); 
    }
    .password-section h3 { 
        color: var(--text-primary); 
        font-size: 18px; 
        margin-bottom: 16px; 
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .password-section h3 i {
        color: var(--accent-blue);
    }
    
    /* Messages */
    .message { 
        margin-bottom: 24px; 
        padding: 16px 20px; 
        border-radius: 12px; 
        font-size: 15px; 
        font-weight: 500; 
        border: 1px solid transparent;
        animation: slideIn 0.5s ease-out forwards;
    }
    .message.success { 
        background: #dcfce7; 
        color: #166534; 
        border-color: #bbf7d0;
    }
    .message.error { 
        background: #fee2e2; 
        color: #991b1b; 
        border-color: #fecaca;
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    /* Button (Admin Theme) */
    .btn { 
        background: var(--accent-blue); 
        color: white; 
        padding: 14px 28px; 
        border: none; 
        border-radius: 10px; 
        font-size: 16px; 
        font-weight: 600;
        cursor: pointer; 
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }
    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -8px rgba(59, 130, 246, 0.4);
    }
    
    /* --- NEW: Dark Mode Variables --- */
    body[data-theme="dark"] {
        --bg-color: #0f172a; /* Slate 900 */
        --navbar-bg: rgba(30, 41, 59, 0.85); /* Slate 800 */
        --widget-bg: #1e293b; /* Slate 800 */
        --text-primary: #f1f5f9; /* Slate 100 */
        --text-secondary: #94a3b8; /* Slate 400 */
        --border-color: #334155; /* Slate 700 */
        --readonly-bg: #334155;
        --readonly-color: #cbd5e1;
    }
    
    /* Apply dark mode to table */
    body[data-theme="dark"] table th {
        background: #334155; /* Slate 700 */
        color: var(--text-primary);
    }
    body[data-theme="dark"] table tr:nth-child(even) {
        background: #1e293b; /* Slate 800 */
    }
    body[data-theme="dark"] table tr:hover {
        background: rgba(59, 130, 246, 0.15); /* Brighter blue hover */
    }
    
    /* Apply dark mode to forms */
    body[data-theme="dark"] .form-group input,
    body[data-theme="dark"] .form-group textarea,
    body[data-theme="dark"] .form-group select,
    body[data-theme="dark"] .search-container input {
        background: #334155;
        border-color: #475569;
        color: var(--text-primary);
    }
    body[data-theme="dark"] .form-group input:focus,
    body[data-theme="dark"] .form-group textarea:focus,
    body[data-theme="dark"] .form-group select:focus,
    body[data-theme="dark"] .search-container input:focus {
        background: #1e293b;
        border-color: var(--accent-blue);
    }
    body[data-theme="dark"] .form-group input[readonly] {
        background: var(--readonly-bg);
        color: var(--readonly-color);
    }
    
    /* Apply dark mode to other components */
    body[data-theme="dark"] .icon-button:hover { 
        background-color: #334155;
    }
    body[data-theme="dark"] .profile-button:hover {
        background-color: #334155;
    }
    body[data-theme="dark"] .profile-menu,
    body[data-theme="dark"] .theme-menu {
        background: #1e293b;
        border-color: #334155;
    }
    body[data-theme="dark"] .profile-menu-list a:hover {
        background: #334155;
    }
    body[data-theme="dark"] .password-section {
        background: #0f172a;
        border-color: #334155;
    }
    body[data-theme="dark"] .stats-card,
    body[data-theme="dark"] .widget-card,
    body[data-theme="dark"] .table-container,
    body[data-theme="dark"] .empty-state {
        background: var(--widget-bg);
        border-color: var(--border-color);
    }
    body[data-theme="dark"] .action-button.secondary,
    body[data-theme="dark"] .action-button.filter {
        background: var(--widget-bg);
        color: var(--text-primary);
        border-color: var(--border-color);
    }
    body[data-theme="dark"] .action-button.secondary:hover,
    body[data-theme="dark"] .action-button.filter:hover {
        background: #334155;
    }
    body[data-theme="dark"] .modal-content,
    body[data-theme="dark"] .popup-box {
        background: var(--widget-bg);
    }
    body[data-theme="dark"] .popup-btn.cancel {
        background: #334155;
        color: var(--text-primary);
    }
    body[data-theme="dark"] .popup-btn.cancel:hover {
        background: #475569;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        body { padding-top: 70px; }
        .main-content {
            margin: 0;
            padding: 1.5rem 1rem;
            border-radius: 0;
        }
        .widget-card {
            padding: 1.5rem;
        }
        .page-header h1 { font-size: 24px; }
        .form-grid {
            grid-template-columns: 1fr; /* Stack on mobile */
        }
    }
    @media (max-width: 480px) {
        .profile-button .student-name {
            display: none; /* Hide name on small screens */
        }
    }
    </style>
</head>
<body>
    
    <nav class="navbar" id="navbar">
        <div class="nav-container" id="nav-container">
            <a href="student_dashboard.php" class="nav-brand">
                <i class="fas fa-graduation-cap"></i>
                <span>Course Connect</span>
            </a>

            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <ul class="navbar-nav" id="navbar-nav">
                <li class="nav-item">
                    <a href="student_dashboard.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="my_courses.php" class="nav-link">
                        <i class="fas fa-book-open"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="enroll.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Enroll</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="results.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Results</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link active">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
            
            <div class="nav-right">
                <div class="theme-dropdown">
                    <button class="icon-button" id="theme-btn" title="Settings">
                        <i class="fas fa-cog"></i>
                    </button>
                    <div class="theme-menu" id="theme-menu">
                        <div class="theme-menu-header">
                            <h4>Theme Settings</h4>
                        </div>
                        <ul class="theme-menu-list">
                            <li>
                                <span>Dark Mode</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="theme-toggle-switch">
                                    <span class="slider"></span>
                                </label>
                            </li>
                        </ul>
                    </div>
                </div>
            
                <div class="profile-dropdown">
                    <button class="profile-button" id="profile-btn" title="Profile">
                        <img src="<?php echo $profile_picture_display; ?>" alt="Profile">
                        <span class="student-name"><?php echo $student_name; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="profile-menu" id="profile-menu">
                        
                        <ul class="profile-menu-list">
                            <li><a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </nav>
    <div class="main-content">
        <div class="widget-card">
            <div class="page-header"><i class="fas fa-user-circle"></i><h1>My Profile</h1></div>
            
            <?php if ($success_message): ?><div class="message success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="message error"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
            
            <form method="POST" action="profile.php" enctype="multipart/form-data">
                <div class="profile-picture-section">
                    <img src="<?= $profile_picture_display ?>" alt="Current Profile Picture" class="current-picture" id="profilePreview">
                </div>
                
                <div class="form-grid">
                    <div class="form-group"><label>Registration Number</label><input type="text" value="<?= htmlspecialchars($student['reg_no'] ?? '') ?>" readonly></div>
                    <div class="form-group"><label>Full Name *</label><input type="text" name="name" value="<?= htmlspecialchars($student['name'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Email Address *</label><input type="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" required></div>
                    
                    <div class="form-group"><label>Enrolled Course</label><input type="text" value="<?= htmlspecialchars($student['course'] ?? 'Not Enrolled') ?>" readonly></div>
                    <div class="form-group"><label>Department</label><input type="text" value="<?= htmlspecialchars($student['department'] ?? 'Not Assigned') ?>" readonly></div>
                    <div class="form-group"><label>Semester</label><input type="text" value="<?= htmlspecialchars($student['semester'] ?? 'Not Assigned') ?>" readonly></div>
                    
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>"></div>
                    <div class="form-group"><label>Age</label><input type="number" name="age" value="<?= htmlspecialchars($student['age'] ?? '') ?>"></div>
                    
                    <div class="form-group full-width"><label>Address</label><textarea name="address" rows="3"><?= htmlspecialchars($student['address'] ?? '') ?></textarea></div>
                   
                    <div class="form-group full-width">
                        <label>Update Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/*" id="profilePictureInput" style="padding: 12px; background: #f8fafc; width: 100%;">
                    </div>
                </div>

                <div class="password-section">
                    <h3><i class="fas fa-lock"></i> Change Password</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Current Password</label><input type="password" name="current_password"></div>
                        <div class="form-group"><label>New Password (min 6 chars)</label><input type="password" name="new_password"></div>
                        <div class="form-group full-width"><label>Confirm New Password</label><input type="password" name="confirm_password"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn"><i class="fas fa-save"></i> Update Profile</button>
            </form>
        </div>
    </div>

    <script>
        // === NEW THEME TOGGLE SCRIPT ===
        document.addEventListener("DOMContentLoaded", function() {
            const themeBtn = document.getElementById('theme-btn');
            const themeMenu = document.getElementById('theme-menu');
            const themeToggle = document.getElementById('theme-toggle-switch');
            const profileBtn = document.getElementById('profile-btn');
            const profileMenu = document.getElementById('profile-menu');
            
            // Function to apply the theme
            function applyTheme(theme) {
                if (theme === 'dark') {
                    document.body.setAttribute('data-theme', 'dark');
                    if(themeToggle) themeToggle.checked = true;
                } else {
                    document.body.setAttribute('data-theme', 'light');
                    if(themeToggle) themeToggle.checked = false;
                }
            }
            
            // 1. Toggle Theme Menu
            if (themeBtn) {
                themeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileMenu?.classList.remove('show');
                    themeMenu.classList.toggle('show');
                });
            }
            
            // 2. Handle Toggle Switch Change
            if (themeToggle) {
                themeToggle.addEventListener('change', () => {
                    if (themeToggle.checked) {
                        localStorage.setItem('theme', 'dark');
                        applyTheme('dark');
                    } else {
                        localStorage.setItem('theme', 'light');
                        applyTheme('light');
                    }
                });
            }
            
            // 3. Toggle Profile Menu
            if (profileBtn) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    themeMenu?.classList.remove('show');
                    profileMenu.classList.toggle('show');
                });
            }

            // 4. Close menus when clicking outside
            document.addEventListener('click', (e) => {
                if (themeMenu && themeBtn && !themeBtn.contains(e.target) && !themeMenu.contains(e.target)) {
                    themeMenu.classList.remove('show');
                }
                if (profileMenu && profileBtn && !profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.classList.remove('show');
                }
            });
            
            // 5. Set initial state of the toggle on page load
            const currentTheme = localStorage.getItem('theme') || 'light';
            applyTheme(currentTheme);

            // --- 6. Live Profile Picture Preview ---
            const profilePicInput = document.getElementById('profilePictureInput');
            if (profilePicInput) {
                profilePicInput.addEventListener('change', function(event) {
                    const [file] = event.target.files;
                    if (file) {
                        const previewImage = document.getElementById('profilePreview');
                        previewImage.src = URL.createObjectURL(file);
                    }
                });
            }
            
            // --- 7. Navbar Scroll Effect ---
            let lastScrollTop = 0;
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', function() {
                let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                if (scrollTop > lastScrollTop && scrollTop > 70) { 
                    navbar.style.top = "-80px";
                } else {
                    navbar.style.top = "0";
                }
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
            }, false);
        });
    </script>
</body>
</html>