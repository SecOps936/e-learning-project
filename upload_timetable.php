<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

include('db.php');

// Fetch user info
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['timetable_file']) && $_FILES['timetable_file']['error'] === 0) {
        $original_name = basename($_FILES['timetable_file']['name']);
        $target_dir = "uploads/";
        $unique_name = time() . "_" . $original_name;
        $target_file = $target_dir . $unique_name;

        if (move_uploaded_file($_FILES['timetable_file']['tmp_name'], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO timetable (file_name, file_path) VALUES (?, ?)");
            $stmt->bind_param("ss", $original_name, $target_file);

            if ($stmt->execute()) {
                $success = "✅ Timetable uploaded successfully.";
            } else {
                $error = "❌ Database error: " . $stmt->error;
            }
        } else {
            $error = "❌ Failed to move the uploaded file.";
        }
    } else {
        $error = "❌ Please choose a timetable file to upload.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Timetable</title>
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

        input[type="submit"] {
            background-color: #2980b9;
            color: white;
            padding: 12px;
            border: none;
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .message {
            color: green;
            text-align: center;
            margin-top: 20px;
            font-weight: bold;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 20px;
            font-weight: bold;
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
        <h2>🗓️ Upload Timetable</h2>

        <?php if ($success): ?>
            <p class="message"><?php echo $success; ?></p>
        <?php elseif ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="timetable_file">Select Timetable File:</label>
            <input type="file" name="timetable_file" accept=".pdf,.doc,.docx,.xlsx,.txt" required>

            <input type="submit" value="📤 Upload Timetable">
        </form>
    </div>
</div>

</body>
</html>
