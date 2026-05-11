<?php
require_once 'session_config.php';

// Protect this page: if the admin is not logged in, redirect to the login page.
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_page.php');
    exit;
}
// student_management.php
$conn = new mysqli("localhost", "root", "", "ocrs_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch all students
$result = $conn->query("
    SELECT s.student_id, s.name, s.reg_no, s.email, s.department, s.semester, s.phone, s.course, s.address, s.age, s.profile_picture
    FROM students s
    INNER JOIN enrollments e ON s.student_id = e.student_id
    WHERE e.status = 'Approved'
    ORDER BY s.name ASC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OCRS - Students</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ============================================
    ADMIN MASTER STYLESHEET (UNIFIED)
    (Copied from department.php)
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
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    body {
        font-family: "Spartan", sans-serif; /* Changed from Segoe UI for consistency */
        background: var(--bg-color); /* Changed from gradient for consistency */
        color: var(--text-primary);
        margin: 0;
        min-height: 100vh;
        padding-top: 70px; /* Space for fixed navbar */
    }

    /* ============================================
    NEW STATIC GRID NAVBAR (from department.php)
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
    
    /* This applies to BOTH .nav-container and .navbar-container */
    .nav-container, .navbar-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 2rem;
        height: 60px;
        
        /* --- THIS IS THE CORE CHANGE --- */
        display: grid;
        grid-template-columns: 1fr auto 1fr; /* 3-column layout: [Left] [Center] [Right] */
        align-items: center;
        /* --- END OF CORE CHANGE --- */
    }
    
    /* This applies to BOTH .nav-brand and .navbar-brand */
    .nav-brand, .navbar-brand {
        grid-column: 1 / 2; /* Place in Column 1 */
        justify-self: start; /* Align to the left of the column */
        
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

    /* Hamburger (used on all pages for mobile) */
    .hamburger {
        grid-column: 3 / 4; /* Place in Column 3 */
        justify-self: end; /* Align to the right of the column */
        
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
    
    /* This applies to BOTH .nav-menu and .navbar-nav */
    .nav-menu, .navbar-nav {
        grid-column: 2 / 3; /* Place in Column 2 */
        justify-self: center; /* Center it within the column */
        
        display: flex; /* This is for the <li> items inside */
        align-items: center;
        list-style: none;
        gap: 0.5rem;
        transition: all 0.4s ease-out;
        margin: 0; /* Remove auto margin */
    }

    /* Nav Links (Shared) */
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
    RESPONSIVE STYLES (Mobile Menu)
    ============================================ */
    @media (max-width: 1050px) { /* Breakpoint to collapse nav */
        /* Revert container to flex for simple mobile layout */
        .nav-container, .navbar-container {
            display: flex;
            justify-content: space-between;
        }
        
        .nav-menu, .navbar-nav { 
            display: none; /* Hide desktop menu */
            grid-column: auto; /* Reset grid */
            justify-self: auto;
        }
        
        .hamburger { 
            display: flex; /* Show hamburger */
            grid-column: auto;
            justify-self: auto;
        }

        /* This is the class added by JS to show the mobile menu */
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
    STYLES from student_management.php
    ============================================ */
    
    /* Main Content */
    /* --- THIS IS THE KEY CHANGE --- */
    .main-content {
        padding: 2rem; /* Changed from 40px */
        max-width: 1400px;
        margin: 0 auto;
        /* REMOVED background, backdrop-filter, border-radius, margin-top, box-shadow, border */
        font-size: 16px;
        animation: fadeInUp 0.6s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .header-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      position: relative;
      padding-bottom: 24px;
      border-bottom: 2px solid var(--border-color); /* Used theme variable */
    }

    .main-content h1 {
      font-size: 28px;
      font-weight: 700;
      color: var(--text-primary); /* Used theme variable */
      margin: 0;
      display: flex;
      align-items: center;
      gap: 15px;
      letter-spacing: -0.025em;
    }

    .main-content h1 i {
      color: var(--accent-blue); /* Used theme variable */
      font-size: 32px;
    }

    /* Search bar */
    .search-container {
      position: relative;
    }

    .search-container input {
      width: 350px;
      padding: 12px 16px 12px 48px;
      border: 2px solid var(--border-color); /* Used theme variable */
      border-radius: 12px;
      outline: none;
      font-size: 15px;
      background: var(--widget-bg); /* Used theme variable */
      color: var(--text-primary); /* Used theme variable */
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      /* backdrop-filter removed */
    }

    .search-container input:focus {
      border-color: var(--accent-blue); /* Used theme variable */
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
      transform: translateY(-1px);
      background: var(--widget-bg); /* Used theme variable */
    }

    .search-container i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-secondary); /* Used theme variable */
      font-size: 16px;
    }

    /* Stats bar */
    .stats-bar {
      display: flex;
      gap: 20px;
      margin-bottom: 30px;
      position: relative;
    }

    .stat-item {
      background: var(--widget-bg); /* Used theme variable */
      padding: 20px 25px;
      border-radius: 16px;
      box-shadow: var(--shadow-sm); /* Used theme variable */
      border: 1px solid var(--border-color); /* Used theme variable */
      transition: all 0.3s ease;
      min-width: 120px;
    }

    .stat-item:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md); /* Used theme variable */
    }

    .stat-number {
      font-size: 24px;
      font-weight: 700;
      color: var(--accent-blue); /* Used theme variable */
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 14px;
      color: var(--text-secondary); /* Used theme variable */
      font-weight: 500;
    }

    /* Cards container */
    .card-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(520px, 1fr));
      gap: 25px;
      margin-top: 10px;
      max-width: 100%;
    }

    /* Student card */
    .student-card {
      background: var(--widget-bg); /* Used theme variable */
      border-radius: 20px;
      box-shadow: var(--shadow-md); /* Used theme variable */
      border: 1px solid var(--border-color); /* Used theme variable */
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: visible; /* CHANGED */
      position: relative;
      isolation: isolate; /* ADDED */
    }

    .student-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #3b82f6, #6366f1, #8b5cf6);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.3s ease;
    }

    .student-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg); /* Used theme variable */
    }

    .student-card:hover::before {
      transform: scaleX(1);
    }
    
    /* ADDED to fix z-index issue */
    .student-card.dropdown-open {
      z-index: 20;
    }

    .student-info {
      display: flex;
      align-items: flex-start;
      gap: 20px;
      padding: 25px;
      position: relative;
      min-height: 120px;
    }

    .profile-container {
      position: relative;
    }

    .student-info img {
      width: 70px;
      height: 70px;
      border-radius: 18px;
      object-fit: cover;
      border: 3px solid var(--border-color); /* Used theme variable */
      transition: all 0.3s ease;
      box-shadow: var(--shadow-sm); /* Used theme variable */
    }

    .student-card:hover .student-info img {
      border-color: var(--accent-blue); /* Used theme variable */
      transform: scale(1.05);
    }

    .student-details {
      flex: 1;
      min-width: 0;
      padding-top: 5px;
    }

    .student-details .name {
      font-weight: 700;
      font-size: 20px;
      color: var(--text-primary); /* Used theme variable */
      margin-bottom: 8px;
      line-height: 1.2;
    }

    .student-meta {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-top: 8px;
    }

    .student-meta span {
      font-size: 14px;
      color: var(--text-secondary); /* Used theme variable */
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .student-meta span i {
      width: 16px;
      color: var(--accent-blue); /* Used theme variable */
    }

    /* Dropdown toggle */
    .dropdown-toggle {
      background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 12px 16px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
      align-self: flex-start;
      margin-top: 5px;
      min-width: 120px;
      justify-content: center;
    }

    .dropdown-toggle:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
    }

    .dropdown-icon {
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dropdown-icon.rotate {
      transform: rotate(180deg);
    }

    /* Enhanced dropdown content styles */
    .dropdown-content {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      z-index: 10;
      max-height: 0;
      overflow: hidden;
      opacity: 0;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      background: #f8fafc; /* Simplified background */
      border: 1px solid var(--border-color); /* Used theme variable */
      border-top: none;
      border-radius: 0 0 20px 20px;
      padding: 0 25px;
      transform: translateY(-10px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .dropdown-content.show {
      max-height: 400px;
      opacity: 1;
      padding: 25px;
      transform: translateY(0);
    }

    .dropdown-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 16px;
    }

    .detail-item {
      background: var(--widget-bg); /* Used theme variable */
      padding: 16px 20px;
      border-radius: 12px;
      border: 1px solid var(--border-color); /* Used theme variable */
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .detail-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      transform: scaleY(0);
      transition: transform 0.3s ease;
    }

    .detail-item:hover {
      transform: translateX(5px);
      box-shadow: var(--shadow-md); /* Used theme variable */
    }

    .detail-item:hover::before {
      transform: scaleY(1);
    }

    .detail-label {
      font-size: 12px;
      font-weight: 600;
      color: var(--accent-blue); /* Used theme variable */
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }

    .detail-value {
      font-size: 15px;
      font-weight: 600;
      color: var(--text-primary); /* Used theme variable */
      line-height: 1.4;
    }

    /* Animation for card entrance */
    .student-card {
      animation: slideInUp 0.6s ease-out;
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Responsive adjustments */
    @media (max-width: 1400px) {
      .card-container {
        grid-template-columns: 1fr;
        max-width: 800px;
        margin: 0 auto;
      }
    }

    @media (max-width: 1200px) {
      .main-content {
        max-width: 95%;
        margin: 15px auto;
        padding: 32px 24px;
      }
      
      .search-container input {
        width: 300px;
      }
      
      .student-info {
        flex-direction: column;
        align-items: flex-start;
        min-height: auto;
      }
      
      .dropdown-toggle {
        align-self: flex-end;
        margin-top: 15px;
      }
      
      .header-section {
        flex-direction: column;
        gap: 20px;
        align-items: flex-start;
      }
      
      .search-container input {
        width: 100%;
      }
    }

    @media (max-width: 768px) {
      /* Mobile navbar styles are already defined above */
      
      body {
        padding-top: 70px; /* Revert to default padding */
      }
      
      .main-content {
        margin: 10px;
        padding: 24px 16px;
        border-radius: 12px;
      }
      
      .main-content h1 {
        font-size: 24px;
      }
    }

    @media (min-width: 1400px) {
      .navbar-container {
        max-width: 1600px;
      }
      
      .main-content {
        max-width: 1600px;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar" id="navbar">
    <div class="nav-container" id="nav-container">
        <a href="admin_dashboard.php" class="nav-brand">
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
                <a href="admin_dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="department.php" class="nav-link">
                    <i class="fas fa-building"></i>
                    <span>Departments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="course_management.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>Courses</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="student_management.php" class="nav-link active">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="enrollments.php" class="nav-link">
                    <i class="fas fa-user-check"></i>
                    <span>Enrollments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
        
        </div>
  </nav>

  <div class="main-content">
    <div class="header-section">
      <h1><i class="fas fa-user-graduate"></i> Student Management</h1>
      <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="studentSearch" placeholder="Search students by name or registration number...">
      </div>
    </div>

    <div class="stats-bar">
      <div class="stat-item">
        <div class="stat-number" id="totalStudents">0</div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="stat-item">
        <div class="stat-number" id="visibleStudents">0</div>
        <div class="stat-label">Showing</div>
      </div>
    </div>

    <div class="card-container" id="cardContainer">
      <?php while ($row = $result->fetch_assoc()): ?>
      <div class="student-card" 
     data-name="<?= strtolower($row['name'] ?? '') ?>" 
     data-reg="<?= strtolower($row['reg_no'] ?? '') ?>"
     data-department="<?= strtolower($row['department'] ?? '') ?>">

        <div class="student-info">
          <div class="profile-container">
            <img src="<?= !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : 'https://via.placeholder.com/70' ?>" alt="Profile Picture">
          </div>
          <div class="student-details">
            <div class="name"><?= htmlspecialchars($row['name'] ?? 'N/A') ?></div>
            <div class="student-meta">
              <span><i class="fas fa-id-card"></i> <?= htmlspecialchars($row['reg_no'] ?? 'N/A') ?></span>
              <span><i class="fas fa-building"></i> <?= htmlspecialchars($row['department'] ?? 'N/A') ?></span>
              <span><i class="fas fa-layer-group"></i> Semester <?= htmlspecialchars($row['semester'] ?? 'N/A') ?></span>
            </div>
          </div>
          <button class="dropdown-toggle" onclick="toggleCard(this)">
            <span>View Details</span>
            <i class="fas fa-chevron-down dropdown-icon"></i>
          </button>

        </div>

        <div class="dropdown-content">
          <div class="dropdown-grid">
            <div class="detail-item">
              <div class="detail-label">Email Address</div>
              <div class="detail-value"><?= htmlspecialchars($row['email'] ?? 'Not provided') ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Phone Number</div>
              <div class="detail-value"><?= htmlspecialchars($row['phone'] ?? 'Not provided') ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Course Enrolled</div>
              <div class="detail-value"><?= htmlspecialchars($row['course'] ?? 'Not specified') ?></div>
            </div>
            <div class="detail-item">
              <div class="detail-label">Age</div>
              <div class="detail-value"><?= htmlspecialchars($row['age'] ?? 'Not provided') ?> years</div>
            </div>
            <div class="detail-item" style="grid-column: 1 / -1;">
              <div class="detail-label">Home Address</div>
              <div class="detail-value"><?= htmlspecialchars($row['address'] ?? 'Address not provided') ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

<script>
    // --- START: Navbar Scroll Effect (from department.php) ---
    let lastScrollTop = 0;
    const navbar = document.getElementById('navbar');

    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 70) { 
            // Scrolling Down
            navbar.style.top = "-80px";
        } else {
            // Scrolling Up
            navbar.style.top = "0";
        }
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; 
    }, false);
    // --- END: Navbar Scroll Effect ---


    // --- START: Collapsible Navbar (Hamburger) (from department.php) ---
    const hamburger = document.getElementById('hamburger');
    const navContainer = document.getElementById('nav-container');

    if (hamburger) {
        hamburger.addEventListener('click', () => {
            // Toggles 'active' on both the hamburger and the nav container
            hamburger.classList.toggle('active');
            navContainer.classList.toggle('active');
        });
    }
    // --- END: Collapsible Navbar ---


    // --- START: Original student_management.php JavaScript ---
    
    // Close all cards function
    function closeAllCards() {
      document.querySelectorAll('.student-card').forEach(card => {
        const dropdown = card.querySelector('.dropdown-content');
        const toggleButton = card.querySelector('.dropdown-toggle');
        const icon = toggleButton ? toggleButton.querySelector('.dropdown-icon') : null;
        const textSpan = toggleButton ? toggleButton.querySelector('span') : null;
        
        dropdown.classList.remove('show');
        dropdown.style.maxHeight = '0';
        dropdown.style.opacity = '0';
        dropdown.style.padding = '0 25px';
        card.classList.remove('dropdown-open'); // Remove z-index class
        
        if (icon) icon.classList.remove('rotate');
        if (textSpan) textSpan.textContent = 'View Details';
      });
    }

    // Initialize proper state on page load
    document.addEventListener('DOMContentLoaded', function() {
      updateStats();
      
      // Ensure all cards start in closed state
      closeAllCards();
      
      // Stagger card animations
      const cards = document.querySelectorAll('.student-card');
      cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
      });
    });

    // Toggle dropdown with smooth animations
    function toggleCard(button) {
      const clickedCard = button.closest('.student-card');
      const clickedDropdown = clickedCard.querySelector('.dropdown-content');
      const clickedIcon = button.querySelector('.dropdown-icon');
      const clickedTextSpan = button.querySelector('span');
      const isClickedCardOpen = clickedDropdown.classList.contains('show');

      // First, close ALL cards and remove dropdown-open class
      document.querySelectorAll('.student-card').forEach(card => {
        if (card === clickedCard) return; // Don't close the one we're working on yet

        const dropdown = card.querySelector('.dropdown-content');
        const toggleButton = card.querySelector('.dropdown-toggle');
        const icon = toggleButton ? toggleButton.querySelector('.dropdown-icon') : null;
        const textSpan = toggleButton ? toggleButton.querySelector('span') : null;
        
        // Force close other dropdowns
        dropdown.classList.remove('show');
        dropdown.style.maxHeight = '0';
        dropdown.style.opacity = '0';
        dropdown.style.padding = '0 25px';
        card.classList.remove('dropdown-open');
        
        if (icon) icon.classList.remove('rotate');
        if (textSpan) textSpan.textContent = 'View Details';
      });

      // Now, toggle the clicked card
      if (!isClickedCardOpen) {
          clickedDropdown.classList.add('show');
          clickedDropdown.style.maxHeight = '400px'; // Set to a value large enough
          clickedDropdown.style.opacity = '1';
          clickedDropdown.style.padding = '25px';
          clickedCard.classList.add('dropdown-open'); // Add z-index class
          
          if (clickedIcon) clickedIcon.classList.add('rotate');
          if (clickedTextSpan) clickedTextSpan.textContent = 'Hide Details';
      } else {
          // This else block was missing, it's needed to close the card
          clickedDropdown.classList.remove('show');
          clickedDropdown.style.maxHeight = '0';
          clickedDropdown.style.opacity = '0';
          clickedDropdown.style.padding = '0 25px';
          clickedCard.classList.remove('dropdown-open');
          
          if (clickedIcon) clickedIcon.classList.remove('rotate');
          if (clickedTextSpan) clickedTextSpan.textContent = 'View Details';
      }
    }

    // Enhanced search with debouncing
    let searchTimeout;
    document.getElementById('studentSearch').addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        const filter = this.value.toLowerCase();
        const cards = document.querySelectorAll('.student-card');
        let visibleCount = 0;

        cards.forEach((card, index) => {
          const name = card.dataset.name;
          const reg = card.dataset.reg;
          const department = card.dataset.department;
          const isVisible = name.includes(filter) || reg.includes(filter) || department.includes(filter);
          
          if (isVisible) {
            card.style.display = 'block';
            card.style.animationDelay = `${visibleCount * 0.05}s`;
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });

        updateVisibleStats(visibleCount);
      }, 300);
    });

    // Update statistics
    function updateStats() {
      const totalCards = document.querySelectorAll('.student-card').length;
      document.getElementById('totalStudents').textContent = totalCards;
      updateVisibleStats(totalCards);
    }

    function updateVisibleStats(count) {
      document.getElementById('visibleStudents').textContent = count;
    }

    // Smooth scroll for better UX
    document.addEventListener('click', function(e) {
      if (e.target.closest('.dropdown-toggle')) {
        setTimeout(() => {
          const card = e.target.closest('.student-card');
          const dropdown = card.querySelector('.dropdown-content');
          if (dropdown.classList.contains('show')) {
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
        }, 100);
      }
    });
    // --- END: Original student_management.php JavaScript ---
</script>
</body>
</html>