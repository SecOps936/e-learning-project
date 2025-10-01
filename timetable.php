<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('db.php');

// Fetch user info
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$role = strtolower($user['role']);

// Fetch timetables
$timetable_files = [];
$timetable_sql = "SELECT * FROM timetable ORDER BY uploaded_at DESC";
$timetable_result = $conn->query($timetable_sql);
while ($row = $timetable_result->fetch_assoc()) {
    $timetable_files[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🗓️ Timetable</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: #f2f2f2;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #3498db;
            color: white;
            padding: 15px 30px;
        }

        .top-bar .left, .top-bar .center, .top-bar .right { flex: 1; }
        .top-bar .center {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }

        .top-bar .right {
            text-align: right;
            position: relative;
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
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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

        .dropdown a:hover {
            background-color: #f0f0f0;
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
            margin-left: 220px;
            padding: 80px 40px 40px 40px;
            height: calc(100vh - 60px);
            overflow-y: auto;
            background-color: #ecf0f1;
        }

        .timetable-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: auto;
        }

        .timetable-item {
            margin-bottom: 30px;
            padding: 20px;
            background: #eaf2f8;
            border-left: 6px solid #3498db;
            border-radius: 8px;
        }

        .timetable-item a {
            font-weight: bold;
            font-size: 16px;
            color: #2c3e50;
            text-decoration: none;
        }

        .timetable-item a:hover {
            text-decoration: underline;
        }

        .preview-embed {
            margin-top: 15px;
            width: 100%;
            height: 800px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        small {
            color: #555;
        }

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
    </script>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="left">
        <?php echo ($role == 'admin') ? '👨‍💼 Admin Panel' : 'Academic Year: 2024/2025'; ?>
    </div>
    <div class="center">
        <?php echo ($role == 'admin') ? 'e-CSDFE Admin Dashboard' : 'BSc-CSDFE2'; ?>
    </div>
    <div class="right">
        <div style="position: relative;">
            <?php if (!empty($user['profile_picture'])): ?>
                <div class="avatar" onclick="toggleDropdown()">
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
                </div>
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
    <div class="timetable-box">
        <h3>Timetables</h3>

        <?php if (!empty($timetable_files)) : ?>
            <?php foreach ($timetable_files as $file) : ?>
                <div class="timetable-item">
                    <a href="<?php echo htmlspecialchars($file['file_path']); ?>" download target="_blank">
                        📄 <?php echo htmlspecialchars($file['file_name']); ?>
                    </a><br>
                    <small>Uploaded on: <?php echo date('F d, Y', strtotime($file['uploaded_at'])); ?></small>

                    <?php if (strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION)) === 'pdf') : ?>
                        <embed class="preview-embed"
                               src="<?php echo htmlspecialchars($file['file_path']); ?>"
                               type="application/pdf">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No timetables available.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
