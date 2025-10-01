<?php
session_start();
include('db.php');

// Fetch user info
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$role = strtolower($user['role']);

// Redirect if the user is not admin or faculty
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Faculty') {
    header('Location: login.php'); // Redirect if not admin or faculty
}

// Process file upload
if (isset($_POST['submit'])) {
    $course_id = $_POST['course_id'];
    $material_type = $_POST['material_type'];
    $file = $_FILES['material_file'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $upload_dir = "uploads/";

    // Check if the course_id exists in the courses table
    $course_check_sql = "SELECT * FROM courses WHERE course_id = ?";
    $stmt = $conn->prepare($course_check_sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // If the course doesn't exist, show an error
    if ($result->num_rows == 0) {
        echo "<p style='color: red; text-align: bottom;'>Error: Course ID does not exist.</p>";
    } else {
        // Save the file in the uploads directory
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $sql = "INSERT INTO materials(course_id, material_type, file_name, file_path, uploaded_at) 
                    VALUES ('$course_id', '$material_type', '$file_name' , '$upload_dir$file_name', '{$_SESSION['user_id']}')";
            
            if ($conn->query($sql)) {
                echo "<p style='color: green; text-align: bottom;'>Material uploaded successfully!</p>";
            } else {
                echo "<p style='color: red; text-align: bottom;'>Error: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: red; text-align: bottom;'>File upload failed!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Class Material</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Roboto', sans-serif; 
            margin: 0; 
            background: #f2f2f2; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0;
        }

        /* Top Bar styles */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgb(100, 171, 243);
            color: white;
            padding: 15px 30px;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .top-bar .left, .top-bar .center, .top-bar .right {
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

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-top: 80px; /* Added margin to separate from top bar */
        }

        h1 {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 15px;
        }

        label {
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 5px;
            display: block;
            text-align: left;
        }

        input[type="number"], select, input[type="file"] {
            width: 100%;
            padding: 6px 10px; /* Reduced padding */
            margin: 6px 0 12px 0; /* Reduced margin */
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 0.9rem;
            box-sizing: border-box;
        }

        input[type="number"]:focus, select:focus, input[type="file"]:focus {
            outline: none;
            border-color: #3aaf9f;
        }

        button {
            background-color: #3aaf9f;
            color: white;
            font-size: 1rem;
            padding: 10px;
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
            margin-top: 12px;
            color: #333;
        }

        .message a {
            color: #3aaf9f;
            text-decoration: none;
            font-weight: 500;
        }

        .message a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                padding: 12px 15px;
                width: 90%;
            }
            h1 {
                font-size: 1.3rem;
            }
        }
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
    <div class="left">
        <?php echo ($role == 'admin') ? '👨‍💼 Admin Panel' : '📚 Academic Year: 2024/2025'; ?>
    </div>
    <div class="center">
        <?php echo ($role == 'admin') ? 'e-CSDFE Admin Dashboard' : 'BSc-CSDFE2'; ?>
    </div>
    <div class="right">
        <div class="avatar" onclick="toggleDropdown()">
            <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
        </div>
        <div id="userDropdown" class="dropdown">
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<!-- Upload Material Form -->
<div class="container">
    <h1>Upload Class Material</h1>
    
    <form method="POST" enctype="multipart/form-data">
        <label for="course_id">Course ID:</label>
        <input type="number" name="course_id" id="course_id" required>

        <label for="material_type">Material Type:</label>
        <select name="material_type" id="material_type" required>
            <option value="Notes">Notes</option>
            <option value="Slides">Slides</option>
            <option value="Assignments">Assignments</option>
            <option value="Projects">Projects</option>
        </select>

        <label for="material_file">Upload Material:</label>
        <input type="file" name="material_file" id="material_file" required>

        <button type="submit" name="submit">Upload</button>
    </form>

    <div class="message">
    </div>
</div>

</body>
</html>
