<?php
require_once 'session_config.php';
require_once 'db_connect.php';

// Protect this page
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_page.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($name) || empty($email)) {
        $error = "Name and Email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if password is being updated
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $error = "Password must be at least 6 characters.";
            } elseif ($password !== $confirm_password) {
                $error = "Passwords do not match.";
            } else {
                // Password is valid, update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ?, password = ? WHERE admin_id = ?");
                $stmt->bind_param("sssi", $name, $email, $hashed_password, $admin_id);
            }
        } else {
            // No new password, update only name and email
            $stmt = $conn->prepare("UPDATE admins SET name = ?, email = ? WHERE admin_id = ?");
            $stmt->bind_param("ssi", $name, $email, $admin_id);
        }

        // Execute the query if no error
        if (empty($error)) {
            if ($stmt->execute()) {
                $_SESSION['admin_name'] = $name; // Update session name
                $message = "Profile updated successfully!";
            } else {
                $error = "Error updating profile. The email might already be in use.";
            }
            $stmt->close();
        }
    }
}

// Fetch current admin data
$stmt = $conn->prepare("SELECT name, email FROM admins WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_name, $admin_email);
$stmt->fetch();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - OCRS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* A simplified version of your dashboard styles */
        /* You should link to your main stylesheet */
        <?php 
           // In a real project, you would link to the CSS file.
           // For this example, I'm pasting essential styles.
        ?>
        :root {
            --bg-color: #f4f7fa; 
            --widget-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-color); padding: 2rem; }
        .main-content { max-width: 800px; margin: 2rem auto; }
        .widget-card {
            background: var(--widget-bg); border-radius: 16px;
            padding: 2rem; box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        .widget-header { margin-bottom: 1.5rem; }
        .widget-header h3 { font-size: 20px; font-weight: 600; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block; font-weight: 600;
            margin-bottom: 8px; color: var(--text-primary);
        }
        .form-group input {
            width: 100%; padding: 14px 16px; border: 2px solid var(--border-color);
            border-radius: 10px; font-size: 15px; background: #f8fafc;
        }
        .form-group input:focus {
            outline: none; border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); background: #fff;
        }
        .action-button {
            background: var(--accent-blue); color: #ffffff; border: none;
            padding: 12px 20px; border-radius: 10px; font-size: 15px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .msg { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .msg.success { background: #dcfce7; color: #166534; }
        .msg.error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    
    <main class="main-content">
        <div class="widget-card">
            <div class="widget-header">
                <h3>Admin Profile Settings</h3>
            </div>

            <?php if ($message): ?><div class="msg success"><?php echo $message; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg error"><?php echo $error; ?></div><?php endif; ?>

            <form method="POST" action="admin_profile.php">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                </div>
                <hr style="border:none; border-top:1px solid var(--border-color); margin: 1.5rem 0;">
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">Leave password fields blank to keep your current password.</p>
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="New Password (min 6 chars)">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password">
                </div>
                <button type="submit" class="action-button">Save Changes</button>
            </form>
        </div>
        <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--accent-blue);">Back to Dashboard</a>
    </main>

</body>
</html>