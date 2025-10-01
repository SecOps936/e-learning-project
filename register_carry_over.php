<?php
session_start();
include('db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user securely using prepared statement
$stmt = $conn->prepare("SELECT first_name, last_name, registration_number, profile_picture FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$full_name = $user['first_name'] . ' ' . $user['last_name'];
$registration_number = $user['registration_number'];
$profilePic = !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : null;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    // Sanitize input
    $course_name = trim($_POST['course_name']);
    $full_name_post = trim($_POST['full_name']);
    $registration_number_post = trim($_POST['registration_number']);

    // Input validation
    if ($registration_number_post !== $registration_number) {
        $message = "Incorrect credentials";
        $message_type = 'error';
    } elseif (strtolower($full_name_post) !== strtolower($full_name)) {
        $message = "Incorrect credentials";
        $message_type = 'error';
    } elseif (!preg_match('/^T2[1-9]\d*-03-\d{5}$/', $registration_number_post)) {
        $message = "Incorrect registration number format";
        $message_type = 'error';
    } elseif (!preg_match('/^[A-Za-z0-9\s\-]+$/', $course_name)) {
        $message = "Invalid course name format";
        $message_type = 'error';
    } else {
        // Prepared statement to insert
        $stmt = $conn->prepare("INSERT INTO carryover_registrations (full_name, registration_number, course_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $full_name_post, $registration_number_post, $course_name);
        if ($stmt->execute()) {
            $message = "Registered successfully!";
            $message_type = 'success';
        } else {
            $message = "Error: Could not register. Please try again.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register Carry Over</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; }
body { font-family: 'Roboto', sans-serif; margin: 0; background: #f2f2f2; }

.top-bar { display: flex; justify-content: space-between; align-items: center; background-color: #3498db; color: white; padding: 15px 30px; }
.top-bar .left, .top-bar .center, .top-bar .right { flex: 1; }
.top-bar .center { text-align: center; font-weight: bold; font-size: 18px; }
.top-bar .right { text-align: right; position: relative; }

.avatar { cursor: pointer; border-radius: 50%; width: 35px; height: 35px; display: inline-flex; justify-content: center; align-items: center; font-size: 16px; color: white; background-color: #3aaf9f; object-fit: cover; }
.dropdown { display: none; position: absolute; right: 0; top: 55px; background-color: white; border: 1px solid #ddd; border-radius: 5px; min-width: 120px; z-index: 999; }
.dropdown a { display: block; padding: 10px 15px; color: #2c3e50; text-decoration: none; }
.dropdown a:hover { background-color: #f0f0f0; }

.sidebar { float: left; width: 220px; height: calc(100vh - 60px); background: #2c3e50; padding: 20px; color: white; }
.sidebar h2 { margin-bottom: 25px; font-size: 20px; color: #ecf0f1; }
.sidebar a { display: block; margin: 10px 0; color: #ecf0f1; text-decoration: none; padding: 8px 10px; border-radius: 4px; }
.sidebar a:hover { background-color: #34495e; }

.main-content { margin-left: 220px; height: calc(100vh - 60px); background-color: #ecf0f1; display: flex; justify-content: center; align-items: center; }
.form-container { width: 100%; max-width: 600px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

input[type="text"] { width: 100%; padding: 12px; margin: 10px 0 20px 0; border: 1px solid #ccc; border-radius: 5px; }
input[readonly] { background-color: #e9ecef; }

button { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #2980b9; }

.message { margin: 15px 0; padding: 10px; border-radius: 4px; }
.message.success { background: #d4edda; border-left: 6px solid #28a745; color: #155724; }
.message.error { background: #f8d7da; border-left: 6px solid #dc3545; color: #721c24; }
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

<div class="top-bar">
    <div class="left">Academic Year: 2024/2025</div>
    <div class="center">BSc-CSDFE2</div>
    <div class="right">
        <?php if ($profilePic): ?>
            <img src="<?= $profilePic ?>" class="avatar" onclick="toggleDropdown()" alt="Profile Picture">
        <?php else: ?>
            <div class="avatar" onclick="toggleDropdown()"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <div id="userDropdown" class="dropdown">
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="sidebar">
    <h2>e-CSDFE</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="curriculum.php">📘 Curriculum</a>
    <a href="timetable.php">🗓️ Timetable</a>
    <a href="groups.php">👥 Groups</a>
    <a href="register_carry_over.php">♻️ Register Carry Over</a>
</div>

<div class="main-content">
    <div class="form-container">
        <h2>Register Carry Over</h2>
        <?php if (!empty($message)): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <label for="full_name">Student Name</label>
            <input type="text" name="full_name" id="full_name" placeholder="Enter your name" required>

            <label for="registration_number">Registration Number</label>
            <input type="text" name="registration_number" id="registration_number" placeholder="T2x-03-yyyyy" required>

            <label for="course_name">Course Name</label>
            <input type="text" name="course_name" id="course_name" placeholder="e.g. CP222" required>

            <button type="submit">Submit</button>
        </form>
    </div>
</div>

</body>
</html>
