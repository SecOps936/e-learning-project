<?php
session_start();

// Ensure only Admin can access this page
if ($_SESSION['role'] != 'Admin') {
    header('Location: login.php'); // Redirect if not Admin
    exit();
}

include('db.php');

// Fetch admin user info for top bar
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

if (!$user || strtolower($user['role']) !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle course registration
if (isset($_POST['submit'])) {
    $course_name = $_POST['course_name'];
    $course_description = $_POST['course_description'];
    $semester = $_POST['semester'];

    $sql = "INSERT INTO courses (course_name, course_description, semester) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $course_name, $course_description, $semester);

    if ($stmt->execute()) {
        $course_id = $stmt->insert_id;
        $success_message = "Course registered successfully! Course ID: $course_id";
    } else {
        $error_message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register New Course</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; margin: 0; background: #f2f2f2; }

        .top-bar {
            display: flex; justify-content: space-between;
            align-items: center; background-color: rgb(100, 171, 243);
            color: white; padding: 15px 30px;
        }

        .top-bar .left, .top-bar .center, .top-bar .right { flex: 1; }
        .top-bar .center { text-align: center; font-weight: bold; font-size: 18px; }
        .top-bar .right { text-align: right; position: relative; }

        .avatar {
            cursor: pointer; background-color: #3aaf9f;
            border-radius: 50%; width: 35px; height: 35px;
            display: inline-flex; justify-content: center; align-items: center;
            font-size: 16px; color: white;
        }

        .dropdown {
            display: none; position: absolute; right: 0; top: 55px;
            background-color: white; border: 1px solid #ddd;
            border-radius: 5px; min-width: 120px; z-index: 999;
        }

        .dropdown a {
            display: block; padding: 10px 15px;
            color: #2c3e50; text-decoration: none;
        }

        .dropdown a:hover { background-color: #f0f0f0; }

        .main-content {
            margin: 0 auto; max-width: 600px;
            padding: 40px;
            background-color: #ecf0f1;
            min-height: calc(100vh - 60px);
        }

        .form-container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }

        label {
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 5px;
            display: block;
            text-align: left;
        }

        input[type="text"], textarea, select {
            width: 100%;
            padding: 8px;
            margin: 8px 0 15px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 0.9rem;
        }

        input[type="text"]:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #3aaf9f;
        }

        button {
            background-color: #3aaf9f;
            color: white;
            font-size: 1rem;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #6fd4db;
        }

        .message {
            margin-top: 15px;
            color: #333;
            text-align: center;
        }

        .alert {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        window.onclick = function(e) {
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
    <div class="form-container">
        <h1>Register New Course</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php elseif (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="course_name">Course Name:</label>
            <input type="text" name="course_name" id="course_name" required>

            <label for="course_description">Course Description:</label>
            <textarea name="course_description" id="course_description" rows="4" required></textarea>

            <label for="semester">Semester:</label>
            <select name="semester" id="semester" required>
                <option value="">Select Semester</option>
                <option value="Semester 1">Semester 1</option>
                <option value="Semester 2">Semester 2</option>
            </select>

            <button type="submit" name="submit">Register Course</button>
        </form>
    </div>
</div>

</body>
</html>
