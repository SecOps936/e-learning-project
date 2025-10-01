<?php
session_start();
include('db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";

// Handle update submission securely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    // Validate and sanitize input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $registration_number = trim($_POST['registration_number']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = trim($_POST['gender']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } else {
        // Prepared statement to update profile
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, registration_number=?, email=?, phone=?, gender=? WHERE user_id=?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $registration_number, $email, $phone, $gender, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = "Profile updated successfully.";
    }
}

// Fetch current user data securely
$stmt = $conn->prepare("SELECT first_name, last_name, registration_number, email, phone, gender, profile_picture FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #ecf0f3; color: #333; }
.top-bar { display: flex; justify-content: space-between; align-items: center; background-color: #2980b9; color: #fff; padding: 20px 30px; font-weight: 600; }
.avatar { border-radius: 50%; width: 40px; height: 40px; object-fit: cover; background-color: #1abc9c; color: #fff; text-align: center; line-height: 40px; font-weight: bold; font-size: 18px; cursor: pointer; }
.main-container { display: flex; min-height: calc(100vh - 60px); }
.sidebar { width: 220px; background-color: #2c3e50; padding: 30px 20px; color: #fff; }
.sidebar h2 { margin-bottom: 30px; font-size: 22px; color: #ecf0f1; }
.sidebar a { display: block; color: #ecf0f1; text-decoration: none; padding: 10px 15px; border-radius: 6px; margin-bottom: 12px; transition: background 0.3s; }
.sidebar a:hover { background-color: #34495e; }
.profile-container { flex: 1; padding: 2px 15px 15px; display: flex; justify-content: flex-start; align-items: flex-start; }
.profile-box { background-color: #fff; padding: 40px; border-radius: 10px; width: 100%; height: calc(100vh - 80px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08); overflow-y: auto; position: relative; }
.profile-box h2 { text-align: center; margin-bottom: 30px; font-size: 24px; }
label { display: block; margin-bottom: 6px; font-weight: 600; }
input { width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #ccc; background: #f7f9fc; font-size: 14px; }
.form { width: 100%; }
.edit-button { position: absolute; top: 20px; right: 20px; background-color: #2980b9; color: white; border: none; padding: 8px 14px; border-radius: 5px; cursor: pointer; font-weight: bold; }
.form input[type="submit"] { background-color: #27ae60; color: white; border: none; padding: 10px 18px; border-radius: 5px; font-weight: bold; cursor: pointer; }
.message { color: green; margin-bottom: 15px; text-align: center; font-weight: bold; }
.error { color: red; margin-bottom: 15px; text-align: center; font-weight: bold; }
</style>
<script>
function enableEditing() {
    const inputs = document.querySelectorAll(".form input[type='text'], .form input[type='email']");
    inputs.forEach(input => input.removeAttribute('disabled'));
    document.getElementById('saveButton').style.display = 'inline-block';
}
</script>
</head>
<body>

<div class="top-bar">
    <div>Academic Year: 2024/2025</div>
    <div>BSc-CSDFE2</div>
    <div>
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" class="avatar">
        <?php else: ?>
            <div class="avatar"><?= strtoupper(substr($user['first_name'], 0, 1)) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="main-container">
    <div class="sidebar">
        <h2>e-CSDFE</h2>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="curriculum.php">📘 Curriculum</a>
        <a href="timetable.php">🗓️ Timetable</a>
        <a href="groups.php">👥 Groups</a>
        <a href="register_carry_over.php">♻️ Register Carry Over</a>
    </div>

    <div class="profile-container">
        <div class="profile-box">
            <button class="edit-button" onclick="enableEditing()">Edit</button>
            <h2>My Profile</h2>

            <?php if (!empty($message)) echo "<div class='message'>".htmlspecialchars($message)."</div>"; ?>

            <form method="POST" class="form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <label>First Name:</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" disabled>

                <label>Last Name:</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" disabled>

                <label>Registration Number:</label>
                <input type="text" name="registration_number" value="<?= htmlspecialchars($user['registration_number']) ?>" disabled>

                <label>Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>

                <label>Phone Number:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" disabled>

                <label>Gender:</label>
                <input type="text" name="gender" value="<?= htmlspecialchars($user['gender']) ?>" disabled>

                <input type="submit" name="update_profile" id="saveButton" value="Save Changes" style="display: none;">
            </form>
        </div>
    </div>
</div>

</body>
</html>
