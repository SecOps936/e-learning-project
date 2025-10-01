<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = $_GET['id'];

// Fetch user info
$user_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

// Fetch course info
$course_sql = "SELECT * FROM courses WHERE course_id = '$course_id'";
$course_result = $conn->query($course_sql);
$course = $course_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Overview</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f5f7fa;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px;
            background: #3498db;
            color: white;
        }

        .hamburger {
            font-size: 22px;
            cursor: pointer;
            margin-right: 15px;
            display: inline-block;
        }

        .sidebar-hidden .sidebar {
            display: none;
        }

        .sidebar-hidden .main-content {
            margin-left: 0 !important;
        }

        .avatar {
            cursor: pointer;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-block;
            object-fit: cover;
            background: #1abc9c;
            color: white;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
        }
        .sidebar {
            float: left;
            width: 220px;
            height: calc(100vh - 60px);
            background: #2c3e50;
            padding: 20px;
            color: white;
        }
        .sidebar h2 {
            margin-bottom: 25px;
            font-size: 20px;
            color: #ecf0f1;
        }
        .sidebar a {
            display: block;
            margin: 10px 0;
            color: #ecf0f1;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 4px;
        }
        .sidebar a:hover {
            background-color: #34495e;
        }
        .main-content {
            margin-left: 240px;
            padding: 30px;
        }
        .course-info {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .course-info h1 {
            margin: 0 0 10px;
            font-size: 24px;
        }
        .section-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .section-box {
            flex: 1;
            min-width: 200px;
            max-width: 250px;
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.07);
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
            transition: all 0.2s ease;
        }
        .section-box:hover {
            background: #f0f8ff;
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById("userDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }

        function toggleSidebar() {
            document.body.classList.toggle('sidebar-hidden');
        }

        document.addEventListener("click", function(event) {
            const avatar = document.querySelector(".avatar");
            const dropdown = document.getElementById("userDropdown");

            if (!avatar.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = "none";
            }
        });
    </script>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div>
        <!-- ✅ Hamburger Icon Added -->
        <span class="hamburger" onclick="toggleSidebar()">&#9776;</span>
        Academic Year: 2024/2025
    </div>
    <div>BSc-CSDFE2</div>
    <div>
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="avatar" onclick="toggleDropdown()">
        <?php else: ?>
            <div class="avatar" onclick="toggleDropdown()">
                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <h2>e-CSDFE</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="curriculum.php">📘 Curriculum</a>
    <a href="timetable.php">🗓️ Timetable</a>
    <a href="groups.php">👥 Groups</a>
    <a href="register_carry_over.php">♻️ Register Carry Over</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <?php if ($course): ?>
        <div class="course-info">
            <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
            <?php if (!empty($course['description'])): ?>
                <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
            <?php endif; ?>
        </div>

        <div class="section-links">
            <a class="section-box" href="materials.php?course_id=<?php echo $course_id; ?>">
                📁 Course Materials
            </a>
            <a class="section-box" href="individual_assignments.php?course_id=<?php echo $course_id; ?>">
                📝 Individual Assignments
            </a>
            <a class="section-box" href="group_assignments.php?course_id=<?php echo $course_id; ?>">
                👥 Group Assignments
            </a>
            <a class="section-box" href="lab_assignments.php?course_id=<?php echo $course_id; ?>">
                🔬 Lab Assignments
            </a>
        </div>
    <?php else: ?>
        <p>Course not found.</p>
    <?php endif; ?>
</div>
</body>
</html>
