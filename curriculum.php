<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('db.php');

$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();
$role = strtolower($user['role']);

$curriculum_files = [];
$sql = "SELECT * FROM curriculum ORDER BY uploaded_at DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $curriculum_files[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📘 Curriculum</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: #f2f2f2;
        }

        /* ✅ Sidebar toggle class */
        .sidebar-hidden .sidebar {
            display: none;
        }

        .sidebar-hidden .main-content {
            margin-left: 0 !important;
        }

        .top-bar {
            display: flex; justify-content: space-between;
            align-items: center; background-color: #3498db;
            color: white; padding: 15px 30px;
        }

        .top-bar .left, .top-bar .center, .top-bar .right { flex: 1; }
        .top-bar .center { text-align: center; font-weight: bold; font-size: 18px; }
        .top-bar .right { text-align: right; position: relative; }

        /* ✅ Hamburger icon style */
        .hamburger {
            font-size: 22px;
            cursor: pointer;
            margin-right: 15px;
            display: inline-block;
        }

        .avatar {
            cursor: pointer;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            color: white;
            background-color: #3aaf9f;
            object-fit: cover;
        }

        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 55px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 120px;
            z-index: 999;
        }

        .dropdown a {
            display: block;
            padding: 10px 15px;
            color: #2c3e50;
            text-decoration: none;
        }

        .dropdown a:hover { background-color: #f0f0f0; }

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
            margin-left: <?php echo ($role === 'student') ? '200px' : '0'; ?>;
            padding: 80px 40px 40px 40px;
            height: calc(100vh - 60px);
            overflow-y: auto;
            background-color: #ecf0f1;
        }

        .curriculum-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: auto;
        }

        .file-list { list-style: none; padding: 0; }
        .file-item {
            background: #eaf2f8;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 5px solid #3498db;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .file-info { flex: 1 1 70%; }

        .file-info a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
            font-size: 16px;
        }

        .file-info a:hover { text-decoration: underline; }

        .download-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .download-btn:hover { background-color: #2980b9; }

        small { color: #555; }

        .main-content::-webkit-scrollbar {
            width: 10px;
        }

        .main-content::-webkit-scrollbar-thumb {
            background-color: #3498db;
            border-radius: 10px;
        }
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById("userDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }

        document.addEventListener("click", function(event) {
            const avatar = document.querySelector(".avatar");
            const dropdown = document.getElementById("userDropdown");

            if (!avatar.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = "none";
            }
        });

        // ✅ Toggle sidebar visibility
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-hidden');
        }
    </script>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="left">
        <!-- ✅ Hamburger Icon -->
        <span class="hamburger" onclick="toggleSidebar()">&#9776;</span>
        <?php echo ($role == 'admin') ? '👨‍💼 Admin Panel' : 'Academic Year: 2024/2025'; ?>
    </div>
    <div class="center">
        <?php echo ($role == 'admin') ? 'e-CSDFE Admin Dashboard' : 'BSc-CSDFE2'; ?>
    </div>
    <div class="right">
    <?php if (!empty($user['profile_picture'])): ?>
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="avatar" onclick="toggleDropdown()" style="object-fit: cover;">
    <?php else: ?>
        <div class="avatar" onclick="toggleDropdown()">
            <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
        </div>
    <?php endif; ?>
    <div id="userDropdown" class="dropdown">
        <a href="logout.php">Logout</a>
    </div>
</div>

</div>

<?php if ($role === 'student'): ?>
<!-- Sidebar -->
<div class="sidebar">
    <h2>e-CSDFE</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="curriculum.php">📘 Curriculum</a>
    <a href="timetable.php">🗓️ Timetable</a>
    <a href="groups.php">👥 Groups</a>
    <a href="register_carry_over.php">♻️ Register Carry Over</a>
</div>
<?php endif; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="curriculum-box">
        <h3>Curriculum</h3>

        <?php if (!empty($curriculum_files)) : ?>
            <ul class="file-list">
                <?php foreach ($curriculum_files as $file) : ?>
                    <li class="file-item">
                        <div class="file-info">
                            📄 <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank">
                                <?php echo htmlspecialchars($file['file_name']); ?>
                            </a><br>
                            <small>Uploaded on: <?php echo date('F d, Y', strtotime($file['uploaded_at'])); ?></small>
                        </div>
                        <div>
                            <a class="download-btn" href="<?php echo htmlspecialchars($file['file_path']); ?>" download>
                                ⬇️ Download
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>No curriculum files uploaded yet.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
