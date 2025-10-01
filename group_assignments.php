<?php
session_start();
include('db.php');

// ✅ Only logged in users
if (!isset($_SESSION['user_id']) || !isset($_GET['course_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id']);

// ✅ Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$is_admin = strtolower($user['role']) === 'admin';

// ✅ Fetch course
$stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

// ✅ Fetch latest group assignment
$stmt = $conn->prepare("SELECT * FROM assignments 
        WHERE course_id = ? AND assignment_type = 'group' 
        ORDER BY uploaded_at DESC 
        LIMIT 1");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

$has_assignment = $assignment !== null;
$is_before_deadline = $has_assignment ? strtotime($assignment['deadline']) > time() : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Group Assignments</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f5f7fa; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; background: #3498db; color: white; }
        .hamburger { font-size: 22px; cursor: pointer; margin-right: 15px; display: inline-block; }
        .sidebar-hidden .sidebar { display: none; }
        .sidebar-hidden .main-content { margin-left: 0 !important; }
        .avatar { cursor: pointer; border-radius: 50%; width: 35px; height: 35px; display: inline-flex; justify-content: center; align-items: center; font-size: 16px; color: white; background-color: #1abc9c; object-fit: cover; }
        .dropdown { display: none; position: absolute; right: 25px; top: 60px; background-color: white; border: 1px solid #ddd; border-radius: 5px; min-width: 120px; z-index: 999; }
        .dropdown a { display: block; padding: 10px 15px; color: #2c3e50; text-decoration: none; }
        .dropdown a:hover { background-color: #f0f0f0; }
        .sidebar { float: left; width: 220px; height: calc(100vh - 60px); background: #2c3e50; padding: 20px; color: white; }
        .sidebar h2 { margin-bottom: 25px; font-size: 20px; color: #ecf0f1; }
        .sidebar a { display: block; margin: 10px 0; color: #ecf0f1; text-decoration: none; padding: 8px 10px; border-radius: 4px; }
        .sidebar a:hover { background-color: #34495e; }
        .main-content { margin-left: 240px; padding: 30px; }
        .assignments { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .assignments h3 { margin-top: 0; }
        .assignment-item { padding: 12px 15px; margin-bottom: 10px; background: #f9f9f9; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.05); }
        .assignment-item a { color: #2c3e50; text-decoration: none; font-weight: bold; }
        .assignment-item small { color: #888; }
        .upload-success { color: green; margin-top: 10px; }
        .upload-error { color: red; margin-top: 10px; }
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

<!-- Top Bar -->
<div class="top-bar">
    <div><span class="hamburger" onclick="toggleSidebar()">&#9776;</span> Academic Year: 2024/2025</div>
    <div>BSc-CSDFE2</div>
    <div style="position: relative;">
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
        <h3>Group Assignments – <?php echo htmlspecialchars($course['course_name']); ?></h3>

        <?php if ($has_assignment): ?>
            <div class="assignment-item">
                <!-- ✅ Secure download link -->
                <a href="download.php?id=<?= (int)$assignment['id'] ?>">
                    <?= htmlspecialchars($assignment['file_name']) ?>
                </a><br>
                <small>Uploaded: <?= date("F j, Y", strtotime($assignment['uploaded_at'])) ?></small><br>
                <small>Deadline: <?= date("F j, Y, g:i a", strtotime($assignment['deadline'])) ?></small><br>
                <?php if (!$is_before_deadline): ?>
                    <span style="color: red; font-weight: bold;">⛔ Expired</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>No group assignments</p>
        <?php endif; ?>

        <?php if (!$is_admin && $has_assignment && $is_before_deadline): ?>
            <!-- Upload Form -->
            <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                <label for="assignment_file">Submit Assignment</label><br><br>
                <input type="file" name="assignment_file" accept=".pdf,.docx,.doc,.zip" required>
                <br><br>
                <button type="submit" name="submit_assignment">Submit Assignment</button>
            </form>

            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['assignment_file'])) {
                $allowed = ['pdf', 'docx', 'doc' , 'zip'];
                $file = $_FILES['assignment_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    echo "<p class='upload-error'>Invalid file type. Only PDF, DOCX, DOC and ZIP allowed.</p>";
                } else {
                    $upload_dir = __DIR__ . "/../secure_uploads/submissions/";
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                    $new_name = $user_id . "_group_" . time() . "." . $ext;
                    $upload_path = $upload_dir . $new_name;

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $stmt = $conn->prepare("INSERT INTO submision_assignment 
                            (user_id, course_id, file_name, file_path, uploaded_at) 
                            VALUES (?, ?, ?, ?, NOW())");
                        $stmt->bind_param("iiss", $user_id, $course_id, $file['name'], $new_name);
                        if ($stmt->execute()) {
                            echo "<p class='upload-success'>Assignment uploaded and saved successfully!</p>";
                        } else {
                            echo "<p class='upload-error'>Failed to save assignment to database.</p>";
                        }
                        $stmt->close();
                    } else {
                        echo "<p class='upload-error'>Error uploading the file.</p>";
                    }
                }
            }
            ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
