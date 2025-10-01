<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('db.php');

// Fetch user info
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$role = strtolower($user['role']);

// Check if user is an admin
if ($role !== 'admin') {
    header('Location: dashboard.php'); // Redirect if not admin
    exit();
}

// Handle form submission for registering a new student
if (isset($_POST['submit'])) {
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $registration_number = htmlspecialchars(trim($_POST['registration_number']));
    $created_at = date("Y-m-d H:i:s"); // Current timestamp

    // Check if the registration number is unique
    $stmt_check = $conn->prepare("SELECT * FROM students WHERE registration_number = ?");
    $stmt_check->bind_param("s", $registration_number);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo "<script>alert('Registration number already exists. Try a different one.');</script>";
    } else {
        // Insert new student into the 'students' table
        $stmt_insert = $conn->prepare("INSERT INTO students (first_name, last_name, registration_number, created_at) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $first_name, $last_name, $registration_number, $created_at);

        if ($stmt_insert->execute()) {
            echo "<script>alert('Student registered successfully.'); window.location='register_students.php';</script>";
        } else {
            echo "<script>alert('Registration failed. Please try again.');</script>";
        }

        $stmt_insert->close();
    }
    $stmt_check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Register Students</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background: #f2f2f2;
            height: 100vh;  /* Full height */
            display: grid;
            place-items: center; /* Center content both vertically and horizontally */
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgb(100, 171, 243);
            color: white;
            padding: 15px 30px;
            width: 100%;
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

        /* Main Content (Centered Form) */
        .main-content {
            width: 100%;
            display: flex;
            justify-content: center;  /* Horizontally center content */
            align-items: center;      /* Vertically center content */
            padding: 40px;
            background-color: #ecf0f1;
            height: calc(100vh - 60px); /* Full height minus the top bar */
        }

        /* Form Container */
        .form-container {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 600px; /* Maximum width of the form */
            width: 100%; /* Make the form responsive */
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        button {
            background-color: #3498db;
            color: white;
            font-size: 1rem;
            padding: 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #2980b9;
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
    <div class="left">Admin Panel</div>
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
        <h2>Register New Student</h2>
        <form method="POST" action="">
            <label for="first_name">First Name:</label>
            <input type="text" name="first_name" id="first_name" required>

            <label for="last_name">Last Name:</label>
            <input type="text" name="last_name" id="last_name" required>

            <label for="registration_number">Registration Number:</label>
            <input type="text" name="registration_number" id="registration_number" required>

            <button type="submit" name="submit">Register Student</button>
        </form>
    </div>
</div>

</body>
</html>
