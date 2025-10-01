<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || !isset($_GET['course_id'])) {
    header('Location: login.php');
    exit();
}

$user_id   = $_SESSION['user_id'];
$course_id = intval($_GET['course_id']);

// ✅ Fetch user info securely
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$is_admin = strtolower($user['role']) === 'admin';

// ✅ Fetch course info
$stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

// ✅ Fetch individual assignments (uploaded by admin)
$stmt = $conn->prepare("SELECT * FROM assignments 
                        WHERE course_id = ? AND assignment_type = 'individual' 
                        ORDER BY uploaded_at DESC LIMIT 1");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

$has_assignment = $assignment !== null;
$is_before_deadline = $has_assignment ? strtotime($assignment['deadline']) > time() : false;

// ✅ Handle student submission securely
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment']) && !$is_admin) {
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === 0) {
        $allowed = ['pdf', 'docx', 'doc', 'zip'];
        $file = $_FILES['assignment_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $upload_message = "<p class='upload-error'>Invalid file type. Only PDF, DOCX, DOC, and ZIP allowed.</p>";
        } else {
            $upload_dir = __DIR__ . "/../secure_uploads/submissions/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $new_name   = $user_id . "_individual_" . time() . "." . $ext;
            $safe_name  = bin2hex(random_bytes(6)) . "_" . $new_name;
            $upload_path = $upload_dir . $safe_name;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $stmt = $conn->prepare("INSERT INTO submision_assignment 
                                        (user_id, course_id, file_name, file_path, uploaded_at) 
                                        VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiss", $user_id, $course_id, $file['name'], $safe_name);

                if ($stmt->execute()) {
                    $upload_message = "<p class='upload-success'>✅ Assignment uploaded successfully!</p>";
                } else {
                    $upload_message = "<p class='upload-error'>❌ Failed to save assignment to database.</p>";
                }
                $stmt->close();
            } else {
                $upload_message = "<p class='upload-error'>❌ Error moving the uploaded file.</p>";
            }
        }
    } else {
        $upload_message = "<p class='upload-error'>❌ No valid file uploaded.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Individual Assignments</title>
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
        .assignments {
            background: #fff; padding: 20px; border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .assignment-item {
            padding: 12px 15px; margin-bottom: 10px;
            background: #f9f9f9; border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .assignment-item a { color: #2c3e50; text-decoration: none; font-weight: bold; }
        .assignment-item small { color: #888; }
        .upload-success { color: green; margin-top: 10px; }
        .upload-error { color: red; margin-top: 10px; }
        .hamburger { font-size: 22px; margin-right: 15px; cursor: pointer; }
    </style>
</head>
<body>

<!-- Top Bar -->
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

<!-- Sidebar -->
<div class="sidebar">
    <h2>e-CSDFE</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="curriculum.php">📘 Curriculum</a>
    <a href="timetable.php">🗓️ Timetable</a>
    <?php if (!$is_admin): ?>
        <a href="groups.php">👥 Groups</a>
        <a href="register_carry_over.php">♻️ Register Carry Over</a>
    <?php endif; ?>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="assignments">
        <h3>Individual Assignments – <?php echo htmlspecialchars($course['course_name']); ?></h3>

        <?php if ($has_assignment): ?>
            <div class="assignment-item">
                <!-- ✅ Secure download -->
                <a href="download.php?id=<?php echo $assignment['id']; ?>">
                    <?php echo htmlspecialchars($assignment['file_name']); ?>
                </a><br>
                <small>Uploaded: <?php echo date("F j, Y", strtotime($assignment['uploaded_at'])); ?></small><br>
                <small>Deadline: <?php echo date("F j, Y, g:i a", strtotime($assignment['deadline'])); ?></small><br>
                <?php if (!$is_before_deadline): ?>
                    <span style="color: red; font-weight: bold;">⛔ Expired</span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>No individual assignments</p>
        <?php endif; ?>

        <!-- ✅ Student upload form -->
        <?php if (!$is_admin && $has_assignment && $is_before_deadline): ?>
            <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                <label for="assignment_file">Submit Assignment</label><br><br>
                <input type="file" name="assignment_file" accept=".pdf,.docx,.doc,.zip" required>
                <br><br>
                <button type="submit" name="submit_assignment">Submit Assignment</button>
            </form>
            <?php echo $upload_message; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
