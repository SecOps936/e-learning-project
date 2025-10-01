<?php
session_start();
include('db.php');

// ✅ Allow only logged-in users
if (!isset($_SESSION['user_id']) || !isset($_GET['course_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id']);

// ✅ Get user info securely
$stmt = $conn->prepare("SELECT user_id, first_name, role, profile_picture FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$is_admin = strtolower($user['role']) === 'admin';

// ✅ Get course info
$stmt = $conn->prepare("SELECT course_name FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

// ✅ Get latest lab assignment
$stmt = $conn->prepare("SELECT id, file_name, uploaded_at, deadline 
                        FROM assignments 
                        WHERE course_id = ? AND assignment_type = 'lab' 
                        ORDER BY uploaded_at DESC LIMIT 1");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Assignments</title>
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
            background-color: #1abc9c;
            object-fit: cover;
        }
        .dropdown {
            display: none;
            position: absolute;
            right: 25px;
            top: 60px;
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
            transition: transform 0.3s ease;
        }
        .sidebar.hidden { transform: translateX(-100%); }
        .sidebar h2 { margin-bottom: 25px; font-size: 20px; color: #ecf0f1; }
        .sidebar a {
            display: block;
            margin: 10px 0;
            color: #ecf0f1;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 4px;
        }
        .sidebar a:hover { background-color: #34495e; }
        .main-content { margin-left: 240px; padding: 30px; }
        .assignments {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .assignment-item {
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .assignment-item a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: bold;
        }
        .upload-success { color: green; margin-top: 10px; }
        .upload-error { color: red; margin-top: 10px; }
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            cursor: pointer;
        }
        .hamburger div { height: 4px; background-color: white; border-radius: 2px; }
        @media (max-width: 768px) {
            .hamburger { display: flex; }
            .sidebar { position: fixed; z-index: 1000; transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .sidebar.hidden { transform: translateX(0); }
            .top-bar { justify-content: space-between; }
        }
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById("userDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }
        function toggleSidebar() {
            document.querySelector(".sidebar").classList.toggle("hidden");
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
    <div>Academic Year: 2024/2025</div>
    <div>BSc-CSDFE2</div>
    <div style="position: relative;">
        <div class="hamburger" onclick="toggleSidebar()">
            <div></div><div></div><div></div>
        </div>
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" class="avatar" onclick="toggleDropdown()">
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

<!-- Sidebar -->
<div class="sidebar">
    <h2>e-CSDFE</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="curriculum.php">📘 Curriculum</a>
    <a href="timetable.php">🗓️ Timetable</a>
    <?php if ($is_admin): ?>
        <a href="admin_panel.php">🛠 Admin Panel</a>
    <?php else: ?>
        <a href="groups.php">👥 Groups</a>
        <a href="register_carry_over.php">♻️ Register Carry Over</a>
    <?php endif; ?>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="assignments">
        <h3>Lab Assignments – <?php echo htmlspecialchars($course['course_name']); ?></h3>

        <?php if ($result->num_rows > 0): ?>
            <?php
            $latest_assignment = $result->fetch_assoc();
            $deadline = $latest_assignment['deadline'] ?? null;
            $deadlinePassed = $deadline && strtotime($deadline) < time();
            ?>
            <div class="assignment-item">
                <!-- ✅ Secure download -->
                <a href="download.php?id=<?php echo (int)$latest_assignment['id']; ?>">
                    <?php echo htmlspecialchars($latest_assignment['file_name']); ?>
                </a><br>
                <small>Uploaded: <?php echo htmlspecialchars($latest_assignment['uploaded_at']); ?></small><br>
                <?php if ($deadline): ?>
                    <small>Deadline: <?php echo date('F j, Y H:i', strtotime($deadline)); ?></small><br>
                <?php endif; ?>
            </div>

            <?php if (!$is_admin): ?>
                <?php if (!$deadlinePassed): ?>
                    <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                        <label for="assignment_file">Submit Assignment</label><br><br>
                        <input type="file" name="assignment_file" accept=".pdf,.docx,.doc,.zip" required>
                        <br><br>
                        <button type="submit" name="submit_assignment">Submit Assignment</button>
                    </form>
                <?php else: ?>
                    <p style="color: red; font-weight: bold;">⛔ Expired</p>
                <?php endif; ?>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['assignment_file']) && !$deadlinePassed) {
                    $allowed = ['pdf', 'docx', 'doc', 'zip'];
                    $file = $_FILES['assignment_file'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowed)) {
                        echo "<p class='upload-error'>Invalid file type. Only PDF, DOCX, DOC and ZIP allowed.</p>";
                    } else {
                        $upload_dir = __DIR__ . "/../secure_uploads/submissions/";
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                        $safe_name = $user_id . "_lab_" . time() . "." . $ext;
                        $upload_path = $upload_dir . $safe_name;

                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            $stmt = $conn->prepare("INSERT INTO submision_assignment 
                                (user_id, course_id, file_name, file_path, uploaded_at) 
                                VALUES (?, ?, ?, ?, NOW())");
                            $stmt->bind_param("iiss", $user_id, $course_id, $file['name'], $safe_name);

                            if ($stmt->execute()) {
                                echo "<p class='upload-success'>Assignment uploaded and saved successfully!</p>";
                            } else {
                                echo "<p class='upload-error'>Failed to save to database.</p>";
                            }
                            $stmt->close();
                        } else {
                            echo "<p class='upload-error'>Error uploading the file.</p>";
                        }
                    }
                }
                ?>
            <?php endif; ?>
        <?php else: ?>
            <p>No lab assignments</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
