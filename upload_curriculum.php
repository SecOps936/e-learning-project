<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('db.php');

// Fetch user info
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();
$role = strtolower($user['role']);

if ($role !== 'admin') {
    die("Access denied.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['curriculum_file'])) {
    $file = $_FILES['curriculum_file'];
    $file_name = basename($file['name']);
    $upload_dir = "uploads/curriculum/";
    $target_path = $upload_dir . time() . "_" . $file_name;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $stmt = $conn->prepare("INSERT INTO curriculum (file_name, file_path, uploaded_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $file_name, $target_path);
        $stmt->execute();
        $message = "✅ Curriculum uploaded successfully!";
    } else {
        $message = "❌ Failed to upload file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Curriculum</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background: #f2f2f2;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgb(100, 171, 243);
            color: white;
            padding: 15px 30px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .top-bar .left,
        .top-bar .center,
        .top-bar .right {
            flex: 1;
        }

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
            background-color: #3aaf9f;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            color: white;
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

        .main-content {
            padding: 100px 20px 40px;
            background-color: #ecf0f1;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .upload-box {
            width: 100%;
            max-width: 600px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        input[type="file"] {
            display: block;
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        button {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #2980b9;
        }

        .message {
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            color: #27ae60;
        }

        .message.error {
            color: red;
        }
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        window.onclick = function (e) {
            if (!e.target.matches('.avatar')) {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown && dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                }
            }
        }
    </script>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="left">👨‍💼 Admin Panel</div>
    <div class="center">e-CSDFE Admin Dashboard</div>
    <div class="right">
        <div class="avatar" onclick="toggleDropdown()">
            <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
        </div>
        <div id="userDropdown" class="dropdown">
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="upload-box">
        <h3>Curriculum</h3>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="curriculum_file" required>
            <button type="submit">Upload Curriculum</button>
        </form>

        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, '❌') !== false ? 'error' : ''; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
