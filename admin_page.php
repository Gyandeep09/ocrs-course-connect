<?php
require_once 'session_config.php';
require_once 'db_connect.php'; 

// Define the master admin key required for registration
define('MASTER_ADMIN_KEY', 'iamadmin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- START: LOGIN LOGIC ---
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['error'] = 'Please enter email and password.';
        } else {
            $stmt = $mysqli->prepare('SELECT admin_id, name, password FROM admins WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($admin_id, $name, $hash);

            if ($stmt->fetch() && password_verify($password, $hash)) {
                session_regenerate_id(true);
                unset($_SESSION['student_id']);
                unset($_SESSION['student_name']);
                $_SESSION['admin_id'] = $admin_id;
                $_SESSION['admin_name'] = $name;
                $_SESSION['show_login_transition'] = true;
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = 'Invalid credentials.';
            }
            $stmt->close();
        }
        // If login fails, redirect back
        header("Location: admin_page.php");
        exit();
    }
    // --- END: LOGIN LOGIC ---


    // --- START: RE-INSERTED REGISTRATION LOGIC ---
    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $admin_key = $_POST['admin_key'] ?? '';
        // Validation checks
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $_SESSION['error'] = 'Please fill all registration fields.';
        } elseif ($admin_key !== MASTER_ADMIN_KEY) {
    $_SESSION['error'] = 'Invalid Admin Key. Registration denied.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm_password) {
            $_SESSION['error'] = 'Passwords do not match.';
        } else {
            // Check if email already exists
       // --- START: MODIFIED EMAIL CHECK ---
            // Check if email already exists in admins table
            $stmt_admin = $mysqli->prepare("SELECT admin_id FROM admins WHERE email = ?");
            $stmt_admin->bind_param("s", $email);
            $stmt_admin->execute();
            $stmt_admin->store_result();
            $admin_exists = $stmt_admin->num_rows > 0;
            $stmt_admin->close();

            // Check if email already exists in students table
            $stmt_student = $mysqli->prepare("SELECT student_id FROM students WHERE email = ?");
            $stmt_student->bind_param("s", $email);
            $stmt_student->execute();
            $stmt_student->store_result();
            $student_exists = $stmt_student->num_rows > 0;
            $stmt_student->close();

            if ($admin_exists) {
                $_SESSION['error'] = 'An admin account with this email already exists.';
            } elseif ($student_exists) {
                $_SESSION['error'] = 'This email is already registered as a student. Please use a different email.';
            } else {
                // Hash the password and insert the new admin
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $mysqli->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sss", $name, $email, $hashed_password);

                if ($insert_stmt->execute()) {
                    $_SESSION['success'] = 'Admin registration successful! You can now log in.';
                } else {
                    $_SESSION['error'] = 'Registration failed. Please try again later.';
                }
                $insert_stmt->close();
            }
            // --- END: MODIFIED EMAIL CHECK ---
        }
        // Redirect back to the admin page to show the success/error message
        header("Location: admin_page.php");
        exit();
    }
    // --- END: RE-INSERTED REGISTRATION LOGIC ---
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>OCRS - Admin Portal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-color: #047857; /* Deep Emerald Green */
        --secondary-color: #10B981; /* Vibrant Green */
        --text-dark: #111827;
        --text-light: #9CA3AF;
        --bg-light: #F3F4F6;
        --card-bg: #ffffff;
        --border-color: #D1D5DB;
    }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--bg-light);
        color: var(--text-dark);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        overflow: hidden;
        background-image: radial-gradient(var(--border-color) 1px, transparent 1px);
        background-size: 20px 20px;
    }
    .login-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding: 40px;
    }
    .login-container {
        display: flex;
        width: 100%;
        max-width: 1000px;
        min-height: 600px;
        background: rgba(255, 255, 255, 0.65);
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
        overflow: hidden;
        position: relative;
        animation: pulse-glow 5s infinite ease-in-out;
        opacity: 0;
        transform: translateY(30px);
        animation: fadeInUp 0.8s 0.3s ease-out forwards, pulse-glow 5s 1s infinite ease-in-out;
    }
    .login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('images/admin-illustration.png');
        background-size: cover;
        background-position: center;
        opacity: 0.15;
        z-index: 0;
        border-radius: 24px;
    }
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @keyframes pulse-glow {
        0% { box-shadow: 0 25px 50px -12px rgba(109, 40, 217, 0.1), 0 0 0 0px rgba(109, 40, 217, 0.2); }
        50% { box-shadow: 0 25px 50px -12px rgba(109, 40, 217, 0.15), 0 0 0 10px rgba(109, 40, 217, 0); }
        100% { box-shadow: 0 25px 50px -12px rgba(109, 40, 217, 0.1), 0 0 0 0px rgba(109, 40, 217, 0); }
    }
    .login-illustration {
        flex: 1;
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: linear-gradient(160deg, var(--primary-color), var(--secondary-color));
        color: white;
        text-align: left;
        z-index: 1;
    }
    .illustration-content {
        animation: float-in 1s ease-out 0.8s forwards;
        opacity: 1;
    }
    .login-illustration h1 {
        font-size: 28px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 15px;
    }
    .login-illustration p {
        font-size: 16px;
        opacity: 0.9;
    }
    .login-form-area {
        flex: 1;
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        z-index: 1;
    }
    .form-inner-container {
        position: relative;
        width: 100%;
        height: 450px;
        perspective: 1000px;
    }
    #loginForm, #registerForm {
        position: absolute;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        transition: transform 0.6s ease-in-out, opacity 0.4s ease-in-out;
    }
    #loginForm { transform: rotateY(0deg); }
    #registerForm { transform: rotateY(180deg); }
    .form-inner-container.flipped #loginForm { transform: rotateY(-180deg); }
    .form-inner-container.flipped #registerForm { transform: rotateY(0deg); }
    .login-form-area h2 {
        text-align: center;
        font-size: 26px;
        font-weight: 600;
        margin-bottom: 25px;
        color: var(--text-dark);
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group input {
        width: 100%;
        padding: 14px 18px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        background: var(--bg-light);
        color: var(--text-dark);
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
    }
    .form-group input::placeholder {
        color: var(--text-light);
    }
    .form-group input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(109, 40, 217, 0.1);
        background-color: white;
    }
    .submit-btn {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: #fff;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px -6px rgba(109, 40, 217, 0.5);
    }
    .submit-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px -8px rgba(109, 40, 217, 0.6);
    }
    .toggle-link {
        display: block;
        text-align: center;
        margin-top: 25px;
        font-size: 14px;
        color: var(--text-light);
        cursor: pointer;
    }
    .toggle-link:hover {
        color: var(--primary-color);
        text-decoration: underline;
    }
    .msg {
        padding: 14px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-size: 14px;
        font-weight: 500;
        animation: slideDown 0.4s ease-out;
    }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .msg.success { background: #dcfce7; color: #166534; }
    .msg.error { background: #fee2e2; color: #991b1b; }
    .home-link {
        position: absolute;
        top: 25px;
        left: 25px;
    }
</style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-container">
        <div class="login-illustration">
            <div class="illustration-content">
                <h1>Welcome to the Admin Portal</h1>
                <p>Your central command for system oversight. Manage users, content, and settings to ensure a seamless learning experience.</p>
            </div>
        </div>
        <div class="login-form-area">
            <a href="index.php" class="home-link" title="Back to Home"><i class="fas fa-arrow-left"></i></a>
            <div class="form-inner-container" id="form-flipper">
                
                <div id="loginForm">
                    <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
                    <?php if(!empty($_SESSION['success'])): ?>
                        <div class="msg success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <?php if(!empty($_SESSION['error'])): ?>
                        <div class="msg error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    <form method="post" action="admin_page.php">
                        <input type="hidden" name="action" value="login" />
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Admin Email" required />
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Password" required />
                        </div>
                        <button type="submit" class="submit-btn">Sign In</button>
                    </form>
                    <span class="toggle-link" onclick="toggleForm()">Need an admin account? Register</span>
                </div>

                <div id="registerForm">
                    <h2><i class="fas fa-user-plus"></i> Create Admin Account</h2>
                    <form method="post" action="admin_page.php">
                        <input type="hidden" name="action" value="register" />
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Full Name" required />
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Admin Email" required />
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Password (min 6 chars)" required />
                        </div>
                        <div class="form-group">
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required />
                        </div>
                        <div class="form-group">
                            <input type="password" name="admin_key" placeholder="Admin Key" required />
                        </div>
                        <button type="submit" class="submit-btn">Create Account</button>
                    </form>
                    <span class="toggle-link" onclick="toggleForm()">Already have an account? Login</span>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function toggleForm() {
        const flipper = document.getElementById('form-flipper');
        flipper.classList.toggle('flipped');
    }
</script>

</body>
</html>