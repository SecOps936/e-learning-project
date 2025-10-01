<?php
session_start();
include('db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !isset($_GET['course_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id']); // Ensure numeric

// CSRF token for admin actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user info securely
$user_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user) {
    die("User not found.");
}

$is_admin = strtolower($user['role']) === 'admin';

// Fetch course info securely
$course_stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
$course = $course_result->fetch_assoc();
$course_stmt->close();

if (!$course) {
    die("Course not found.");
}

// Fetch materials securely
$material_stmt = $conn->prepare("SELECT file_name, file_path FROM materials WHERE course_id = ? ORDER BY uploaded_at DESC");
$material_stmt->bind_param("i", $course_id);
$material_stmt->execute();
$materials_result = $material_stmt->get_result();
$material_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Materials</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f5f7fa; }
        .top-bar { display: flex; justify-content: space-between; padding: 15px 25px; background: #3498db; color: white; }
        .avatar { cursor: pointer; border-radius: 50%; width: 35px; height: 35px; display: inline-block; object-fit: cover; background: #1abc9c; color: white; text-align: center; line-height: 35px; font-weight: bold; }
        .sidebar { float: left; width: 220px; height: calc(100vh - 60px); background: #2c3e50; padding: 20px; color: white; }
        .sidebar h2 { margin-bottom: 25px; font-size: 20px; color: #ecf0f1; }
        .sidebar a { display: block; margin: 10px 0; color: #ecf0f1; text-decoration: none; padding: 8px 10px; border-radius: 4px; }
        .sidebar a:hover { background-color: #34495e; }
        .main-content { margin-left: 240px; padding: 30px; }
        .materials { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .materials h3 { margin-top: 0; }
        .material-item { padding: 12px 15px; margin-bottom: 10px; background: #f9f9f9; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .material-item a { color: #2c3e50; text-decoration: none; font-weight: bold; }
        .admin-controls { margin: 15px 0; padding: 15px; background: #eaf2f8; border-left: 5px solid #2980b9; }
        .admin-controls a { display: inline-block; margin-right: 10px; padding: 8px 12px; background: #3498db; color: white; border-radius: 5px; text-decoration: none; font-size: 14px; }
        .admin-controls a:hover { background: #2c80b4; }
    </style>
</head>
<body>

<div class="top-bar">
    <div><span style="font-size: 22px; margin-right: 15px; cursor: pointer;">&#9776;</span> Academic Year: 2024/2025</div>
    <div>BSc-CSDFE2</div>
    <div>
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" class="avatar">
        <?php else: ?>
            <div class="avatar"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="sidebar">
    <h2>e-CSDFE</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="curriculum.php">📘 Curriculum</a>
    <a href="timetable.php">🗓️ Timetable</a>
    <?php if (!$is_admin): ?>
        <a href="groups.php">👥 Groups</a>
        <a href="register_carry_over.php">♻️ Register Carry Over</a>
    <?php endif; ?>
    <?php if ($is_admin): ?>
        <a href="admin_panel.php">🛠 Admin Panel</a>
    <?php endif; ?>
</div>

<div class="main-content">
    <div class="materials">
        <h3>Materials – <?= htmlspecialchars($course['course_name']) ?></h3>

        <?php if ($is_admin): ?>
            <div class="admin-controls">
                <strong>Admin Controls:</strong><br><br>
                <a href="register_course.php?csrf_token=<?= $_SESSION['csrf_token'] ?>">➕ Register Course</a>
                <a href="upload_material.php?course_id=<?= $course_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">📄 Upload Material</a>
                <a href="upload_assignment.php?course_id=<?= $course_id ?>&type=lab&csrf_token=<?= $_SESSION['csrf_token'] ?>">🧪 Lab Assignment</a>
                <a href="upload_assignment.php?course_id=<?= $course_id ?>&type=individual&csrf_token=<?= $_SESSION['csrf_token'] ?>">👤 Individual Assignment</a>
                <a href="upload_assignment.php?course_id=<?= $course_id ?>&type=group&csrf_token=<?= $_SESSION['csrf_token'] ?>">👥 Group Assignment</a>
                <a href="upload_curriculum.php?course_id=<?= $course_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">📘 Upload Curriculum</a>
                <a href="upload_timetable.php?course_id=<?= $course_id ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">🗓️ Upload Timetable</a>
            </div>
        <?php endif; ?>

        <?php if ($materials_result->num_rows > 0): ?>
            <?php while($mat = $materials_result->fetch_assoc()): ?>
                <div class="material-item">
                    <?php
                        $file_name = htmlspecialchars($mat['file_name']);
                        $file_path = htmlspecialchars($mat['file_path']);
                        // Only allow files from /materials/ folder
                        $safe_path = preg_match('/^materials\//', $file_path) ? $file_path : '#';
                    ?>
                    <a href="<?= $safe_path ?>" target="_blank"><?= $file_name ?></a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No materials uploaded</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
