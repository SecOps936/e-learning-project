<?php
session_start();
include("db.php");

// ✅ Ensure only logged-in admin can access
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch user securely
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || strtolower($user['role']) !== 'admin') {
    echo "Unauthorized access.";
    exit();
}

$message = "";

// ✅ Handle staff upload securely
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars(trim($_POST['name']));
    $course_code = htmlspecialchars(trim($_POST['course_code']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $office = htmlspecialchars(trim($_POST['office']));

    if (!empty($name) && !empty($course_code) && !empty($phone) && !empty($office)) {
        $sql = "INSERT INTO staff (name, course_code, phone, office) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $course_code, $phone, $office);
        if ($stmt->execute()) {
            $message = "✅ Staff added successfully!";
        } else {
            $message = "❌ Error adding staff. Please try again.";
        }
        $stmt->close();
    } else {
        $message = "⚠️ All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Roboto', sans-serif; background: #f5f5f5; }
        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            background-color: #64abf3; color: white; padding: 10px 20px;
        }
        .top-bar .left, .top-bar .center, .top-bar .right { flex: 1; }
        .top-bar .center { text-align: center; font-weight: bold; font-size: 18px; }
        .top-bar .right { text-align: right; }
        .avatar {
            cursor: pointer; background-color: #3aaf9f; border-radius: 50%;
            width: 35px; height: 35px; display: flex; justify-content: center; align-items: center;
            font-size: 16px; color: white;
        }
        .container {
            background: white; padding: 20px; border-radius: 8px;
            max-width: 400px; margin: 40px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 { margin-bottom: 20px; font-size: 22px; font-weight: bold; color: #333; text-align: center; }
        label { font-size: 14px; font-weight: 500; color: #555; display: block; margin-bottom: 6px; }
        input[type="text"] {
            width: 100%; padding: 10px; margin-bottom: 15px;
            border: 1px solid #ccc; border-radius: 5px; font-size: 14px;
        }
        button {
            background: #3498db; color: white; border: none;
            padding: 10px 20px; border-radius: 5px; cursor: pointer;
            font-size: 14px; width: 100%;
        }
        button:hover { background: #2980b9; }
        .message { margin-top: 20px; font-weight: bold; text-align: center; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>

<!-- ✅ Admin Top Bar -->
<div class="top-bar">
    <div class="left">👨‍💼 Admin Panel</div>
    <div class="center">e-CSDFE Admin Dashboard</div>
    <div class="right">
        <div class="avatar"><?php echo strtoupper(substr($user['first_name'], 0, 1)); ?></div>
    </div>
</div>

<!-- ✅ Upload Staff Form -->
<div class="container">
    <h2>👨‍🏫 Add New Staff</h2>

    <form method="POST">
        <label for="name">Staff Name:</label>
        <input type="text" name="name" id="name" required>

        <label for="course_code">Course Code:</label>
        <input type="text" name="course_code" id="course_code" required>

        <label for="phone">Phone Number:</label>
        <input type="text" name="phone" id="phone" required>

        <label for="office">Office:</label>
        <input type="text" name="office" id="office" required>

        <button type="submit">Save Staff</button>
    </form>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, '✅') !== false ? 'success' : 'error'; ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
