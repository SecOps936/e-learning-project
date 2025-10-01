<?php
session_start();
include("db.php");

// ✅ Ensure only logged-in students can access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch user info securely
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$is_admin = strtolower($user['role']) === 'admin';

// ✅ Fetch staff members
$stmt = $conn->prepare("SELECT * FROM staff ORDER BY name ASC");
$stmt->execute();
$staff_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Our Staff</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f5f7fa;
        }
        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 25px; background: #3498db; color: white;
        }
        .avatar {
            background: #1abc9c; border-radius: 50%; width: 35px; height: 35px;
            display: flex; align-items: center; justify-content: center; font-weight: bold;
        }
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
        .main-content { margin-left: 240px; padding: 30px; }

        .staff-table {
            width: 100%; border-collapse: collapse;
            background: #fff; border-radius: 12px; overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .staff-table th, .staff-table td {
            padding: 12px 15px; border: 1px solid #ddd; text-align: center;
        }
        .staff-table th {
            background: #3498db; color: white; font-weight: bold;
        }
        .staff-table tr:nth-child(even) { background: #f9f9f9; }
        .staff-table tr:hover { background: #f1f1f1; }
        h2 { color: #2c3e50; margin-bottom: 20px; }
        .hamburger { font-size: 22px; margin-right: 15px; cursor: pointer; }
    </style>
</head>
<body>

<!-- ✅ Top Bar -->
<div class="top-bar">
    <div><span class="hamburger">&#9776;</span> Academic Year: 2024/2025</div>
    <div>BSc-CSDFE2</div>
    <div>
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="avatar">
        <?php else: ?>
            <div class="avatar"><?php echo strtoupper(substr($user['first_name'], 0, 1)); ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- ✅ Sidebar -->
<div class="sidebar">
    <h2>e-CSDFE</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="curriculum.php">📘 Curriculum</a>
    <a href="timetable.php">🗓️ Timetable</a>
    <?php if (!$is_admin): ?>
        <a href="groups.php">👥 Groups</a>
        <a href="staff.php">👨‍🏫 Staff</a>
        <a href="register_carry_over.php">♻️ Register Carry Over</a>
    <?php endif; ?>
</div>

<!-- ✅ Main Content -->
<div class="main-content">
    <h2>Staff</h2>
    <table class="staff-table">
        <tr>
            <th>Name</th>
            <th>Course Code</th>
            <th>Phone</th>
            <th>Office</th>
        </tr>
        <?php while ($row = $staff_result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
            <td><?php echo htmlspecialchars($row['phone']); ?></td>
            <td><?php echo htmlspecialchars($row['office']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
