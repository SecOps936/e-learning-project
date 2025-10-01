<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('db.php');

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$role = strtolower($user['role']);

$courses = [];

if ($role == 'student') {
    $course_sql = "SELECT course_id, course_name, semester FROM courses ORDER BY semester";
    $course_result = $conn->query($course_sql);
    while ($row = $course_result->fetch_assoc()) {
        $courses[$row['semester']][] = $row;
    }
}

if ($role == 'admin') {
    $admin_course_sql = "SELECT course_id, course_name, semester FROM courses ORDER BY semester";
    $admin_course_result = $conn->query($admin_course_sql);
    $all_courses = [];
    while ($row = $admin_course_result->fetch_assoc()) {
        $all_courses[$row['semester']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($role); ?> Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
       body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    background: url('images/csdfe.jpg') no-repeat center center fixed; 
    background-size: cover;
}
        .top-bar {
            display: flex; justify-content: space-between;
            align-items: center; background-color: #3498db;
            color: white; padding: 15px 30px;
            flex-wrap: wrap;
        }

        .top-bar .left, .top-bar .center, .top-bar .right { flex: 1; }
        .top-bar .center { text-align: center; font-weight: bold; font-size: 18px; }
        .top-bar .right { text-align: right; position: relative; }

        .hamburger { font-size: 22px; cursor: pointer; margin-right: 15px; display: inline-block; }
        .sidebar-hidden .sidebar { display: none; }
        .sidebar-hidden .main-content { margin-left: 0 !important; }

        .avatar {
            cursor: pointer; border-radius: 50%;
            width: 35px; height: 35px; display: inline-block; object-fit: cover;
        }

        .dropdown {
            display: none; position: absolute; right: 0; top: 55px;
            background-color: white; border: 1px solid #ddd;
            border-radius: 5px; min-width: 140px; z-index: 999; margin-top: 5px;
        }
        .dropdown a { display: block; padding: 5px 10px; color: #2c3e50; text-decoration: none; margin-top: 2px; }
        .dropdown a:hover { background-color: #f0f0f0; }

        .sidebar {
            float: left; width: 220px; height: calc(100vh - 60px);
            background: #2c3e50; padding: 20px; color: white;
        }
        .sidebar h2 { margin-bottom: 25px; font-size: 20px; color: #ecf0f1; }
        .sidebar a {
            display: block; margin: 10px 0; color: #ecf0f1;
            text-decoration: none; padding: 8px 10px; border-radius: 4px;
        }
        .sidebar a:hover { background-color: #34495e; }

       .main-content {
    margin-left: <?php echo ($role === 'student') ? '220px' : '0'; ?>;
    padding: 30px;
    background: rgba(255, 255, 255, 0.55);  /* ✅ makes content readable */
    border-radius: 12px;
    height: calc(100vh - 60px);
    overflow-y: auto;
}
        .main-content h2 { text-align: center; font-size: 26px; color: #2c3e50; margin-bottom: 25px; }
        .main-content h4 { text-align: center; font-size: 22px; color: #34495e; margin: 20px 0; }

        .course-box {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; margin: 20px 40px; justify-content: center;
        }

        .course-item {
            background-color: white; padding: 15px; border-radius: 6px;
            text-align: center; text-decoration: none; color: #2c3e50;
            font-weight: 500; box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.2s ease-in-out;
        }
        .course-item:hover { transform: translateY(-4px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }

        .admin-box {
            background-color: #ffffff; padding: 25px; border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;
        }

        .admin-action {
            flex: 1 1 280px; padding: 20px;
            background: linear-gradient(135deg, #eaf2f8, #d6eaf8);
            border-left: 6px solid #3498db; border-radius: 10px;
            transition: all 0.3s ease;
        }
        .admin-action:hover { transform: scale(1.03); box-shadow: 0 8px 18px rgba(0,0,0,0.1); }
        .admin-action a { display: block; text-decoration: none; color: #2c3e50; font-weight: bold; margin-top: 10px; }
        .admin-action a:hover { color: #2980b9; }

        .delete-btn {
            display: inline-block; margin-top: 8px;
            padding: 6px 10px; background: #e74c3c; color: #fff;
            text-decoration: none; border-radius: 5px; font-size: 13px;
        }
        .delete-btn:hover { background: #c0392b; }
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById("userDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }
        function toggleSidebar() { document.body.classList.toggle('sidebar-hidden'); }
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

<div class="top-bar">
    <div class="left">
        <span class="hamburger" onclick="toggleSidebar()">&#9776;</span>
        <?php echo ($role == 'admin') ? '👨‍💼 Admin Panel' : 'Academic Year: 2024/2025'; ?>
    </div>
    <div class="center">
        <?php echo ($role == 'admin') ? 'e-CSDFE Admin Dashboard' : 'BSc-CSDFE2'; ?>
    </div>
    <div class="right">
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="avatar" onclick="toggleDropdown()">
        <?php else: ?>
            <div class="avatar" onclick="toggleDropdown()">
                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <div id="userDropdown" class="dropdown">
            <a href="profile.php">My Profile</a>
            <a href="change_password.php">Change Password</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<?php if ($role === 'student'): ?>
    <div class="sidebar">
        <h2>e-CSDFE</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="curriculum.php">📘 Curriculum</a>
        <a href="timetable.php">🗓️ Timetable</a>
        <a href="groups.php">👥 Groups</a>
          <a href="staff.php">👨‍🏫 Staff</a> 
        <a href="register_carry_over.php">♻️ Register Carry Over</a>
    </div>
<?php endif; ?>

<div class="main-content">
<?php if ($role === 'admin'): ?>
    <div class="admin-box">
        <div class="admin-action">➕ Register Course <a href="register_course.php">Add New Course</a></div>
        <div class="admin-action">📄 Upload Course Materials <a href="Upload_Material.php">Upload Material</a></div>
        <div class="admin-action">🧪 Upload Assignments
            <a href="upload_assignment.php?type=lab">Lab Assignment</a>
            <a href="upload_assignment.php?type=individual">Individual Assignment</a>
            <a href="upload_assignment.php?type=group">Group Assignment</a>
        </div>
        <div class="admin-action">📘 Upload Curriculum <a href="upload_curriculum.php">Upload Curriculum</a></div>
        <div class="admin-action">🗓️ Upload Timetable <a href="upload_timetable.php">Upload Timetable</a></div>
        <div class="admin-action">♻️ View Carry-Over Registrations <a href="view_carry_overs.php">See Registered Students</a></div>
        <div class="admin-action">👥 Upload Groups <a href="upload_groups.php">Upload Student Groups</a></div>
        <div class="admin-action">👥 View Groups <a href="view_groups.php">View All Groups</a></div>
        <div class="admin-action">📂 View Assignment <a href="view_assignments.php">View All Submitted Assignments</a></div>
        <div class="admin-action">👩‍🎓👨‍🎓 Register Students <a href="register_students.php">Register Student</a></div>
        <div class="admin-action">👨‍🏫 Manage Staff <a href="upload_staff.php">Upload Staff</a></div> 
    </div>

    <!-- ✅ Show all courses with Delete option -->
    <?php if (!empty($all_courses)): ?>
        <?php foreach ($all_courses as $semester => $semester_courses): ?>
            <h4><?php echo "$semester"; ?></h4>
            <div class="course-box">
                <?php foreach ($semester_courses as $course): ?>
                    <div class="course-item">
                        <?php echo htmlspecialchars($course['course_name']); ?><br>
                        <a class="delete-btn" href="delete_course.php?id=<?php echo $course['course_id']; ?>" onclick="return confirm('Are you sure you want to delete this course?')">Delete</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No courses available yet.</p>
    <?php endif; ?>
<?php elseif ($role === 'student'): ?>
    <?php if (!empty($courses)): ?>
        <?php foreach ($courses as $semester => $semester_courses): ?>
            <h4><?php echo "$semester"; ?></h4>
            <div class="course-box">
                <?php foreach ($semester_courses as $course): ?>
                    <a class="course-item" href="course.php?id=<?php echo $course['course_id']; ?>">
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>You are not registered for any courses yet.</p>
    <?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
