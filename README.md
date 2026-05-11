# Course Connect — OCRS

> **Online Course Registration System** · A full-stack PHP + MySQL web application built as a 5th semester academic project.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=flat&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)

---

## 🧩 What It Does

Course Connect is a web-based platform where students can browse and enroll in courses, and administrators can manage the entire system — students, courses, departments, results, and reports.

| Role | Capabilities |
|---|---|
| **Student** | Register · Browse courses · Enroll · View results · Manage profile |
| **Admin** | Manage students · Manage courses · View enrollments · Generate reports · System settings |

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Backend | PHP (MySQLi) |
| Database | MySQL |
| Local Server | WAMP (Windows) / MAMP (macOS) / XAMPP |
| Icons | Font Awesome 6 |
| Fonts | Google Fonts — Inter, Poppins |

---

## 📁 Project Structure

```
OCRS/
├── index.php                  # Landing page (Course Connect homepage)
├── student_page.php           # Student login / register
├── admin_page.php             # Admin login / register
├── student_dashboard.php      # Student dashboard
├── admin_dashboard.php        # Admin dashboard
├── course_management.php      # Admin: Add / edit / delete courses
├── department.php             # Admin: Department management
├── enrollments.php            # Admin: View all enrollments
├── enroll.php                 # Student: Browse & enroll in courses
├── my_courses.php             # Student: View enrolled courses
├── results.php                # Student: View results
├── reports.php                # Admin: Reports & analytics
├── student_management.php     # Admin: Manage students
├── system_settings.php        # Admin: System-wide settings
├── profile.php                # Student profile page
├── admin_profile.php          # Admin profile page
├── complete_profile.php       # Profile completion flow
├── db_connect.php             # Database connection (configure locally)
├── session_config.php         # Custom file-based session handler
├── logout.php                 # Logout handler
├── fetch_*.php                # AJAX data endpoints
├── get_*.php                  # Course / eligibility helpers
├── images/                    # Static images (course thumbnails, logos)
├── uploads/                   # User uploaded files (excluded from Git)
└── sessions/                  # Session files (excluded from Git)
```

---

## ⚙️ Local Setup

### Prerequisites
- [WAMP](https://www.wampserver.com/) (Windows) · [MAMP](https://www.mamp.info/) (macOS) · [XAMPP](https://www.apachefriends.org/) (any OS)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Steps

**1. Clone the repo**
```bash
git clone https://github.com/Gyandeep09/ocrs-course-connect.git
cd ocrs-course-connect
```

**2. Place in web root**
- WAMP → `C:/wamp64/www/OCRS/`
- MAMP → `/Applications/MAMP/htdocs/OCRS/`
- XAMPP → `C:/xampp/htdocs/OCRS/`

**3. Create the database**

Open phpMyAdmin (`http://localhost/phpmyadmin`) and:
1. Create a new database named `ocrs_db` (collation: `utf8mb4_unicode_ci`)
2. Import `ocrs_db.sql` from this repo

**4. Configure database connection**

Open `db_connect.php` and update if needed:
```php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';           // WAMP default is empty; MAMP default is 'root'
$DB_NAME = 'ocrs_db';
```

**5. Run the project**

Visit: `http://localhost/OCRS/`

---

## 🔐 Default Access

After importing the database, create your admin account via the **Admin → Register** tab on the login page using any Admin Key set in your database.

---

## 📸 Pages Overview

| Page | Description |
|---|---|
| `/` — `index.php` | Public landing page with course listings and stats |
| `/student_page.php` | Student login & registration |
| `/admin_page.php` | Admin login & registration |
| `/student_dashboard.php` | Student home — enrolled courses, notifications |
| `/admin_dashboard.php` | Admin home — system stats, recent activity |
| `/enroll.php` | Course catalog with enrollment and eligibility check |
| `/my_courses.php` | Student's enrolled courses |
| `/results.php` | Student's academic results |
| `/reports.php` | Admin reports — enrollment trends, department stats |

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).
