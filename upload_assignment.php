<?php
session_start();

// ✅ Only admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

include('db.php');

// ✅ Prepared statement for fetching admin
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$valid_types = ['lab', 'individual', 'group'];
$allowed_extensions = ['pdf', 'doc', 'docx', 'zip'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request.";
    } else {
        $course_id = trim($_POST['course_id'] ?? '');
        $assignment_type = trim($_POST['assignment_type'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');

        if (empty($course_id) || !in_array($assignment_type, $valid_types) || empty($deadline)) {
            $error = "Please provide valid details.";
        } elseif (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === 0) {
            
            $upload_dir = __DIR__ . "/../secure_uploads/assignments/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_info = pathinfo($_FILES['assignment_file']['name']);
            $ext = strtolower($file_info['extension']);

            if (!in_array($ext, $allowed_extensions)) {
                $error = "Only PDF, DOC, DOCX, ZIP files allowed.";
            } else {
                $safe_name = bin2hex(random_bytes(8)) . '.' . $ext;
                $target_path = $upload_dir . $safe_name;

                if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_path)) {
                    $stmt = $conn->prepare("INSERT INTO assignments 
                        (assignment_type, course_id, file_name, file_path, deadline) 
                        VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $assignment_type, $course_id, $file_info['basename'], $safe_name, $deadline);

                    if ($stmt->execute()) {
                        $success = ucfirst($assignment_type) . " assignment uploaded successfully.";
                    } else {
                        $error = "Unexpected database error.";
                    }
                } else {
                    $error = "Failed to move uploaded file. Check permissions.";
                }
            }
        } else {
            $error = "Please upload a valid file.";
        }
    }
}
// ✅ Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Assignment</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: #f2f2f2;
            height: 100vh;
            display: flex;
            flex-direction: column;
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
            height: 60px;
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

        .alert {
            position: fixed;
            top: 60px;
            width: 100%;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            z-index: 999;
            transition: opacity 0.5s ease;
        }

        .alert-success {
            background-color: #2ecc71;
            color: #fff;
        }

        .alert-error {
            background-color: #e74c3c;
            color: #fff;
        }

        .main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 100px;
        }

        .container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }

        h2 {
            text-align: center;
        }

        input[type="file"],
        input[type="text"],
        input[type="datetime-local"],
        select,
        input[type="submit"] {
            width: 100%;
            margin-top: 15px;
            padding: 10px;
        }

        @media (max-width: 600px) {
            .container { width: 95%; }
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

        window.onload = function () {
            const alertBox = document.getElementById('alertMessage');
            if (alertBox) {
                setTimeout(() => {
                    alertBox.style.opacity = '0';
                }, 4000);
            }
        };
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

<!-- Alert Message -->
<?php if ($success || $error): ?>
    <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>" id="alertMessage">
        <?php echo $success ?: $error; ?>
    </div>
<?php endif; ?>

<!-- Main Content -->
<div class="main">
    <div class="container">
        <h2>📤 Upload Assignment</h2>

        <form method="POST" enctype="multipart/form-data">
    <label>Course ID:</label>
    <input type="text" name="course_id" placeholder="Enter Course ID" required>

    <label>Assignment Type:</label>
    <select name="assignment_type" required>
        <option value="">-- Select Type --</option>
        <option value="lab">Lab</option>
        <option value="individual">Individual</option>
        <option value="group">Group</option>
    </select>

    <label>Select File:</label>
    <input type="file" name="assignment_file" required>

    <label>Deadline:</label>
    <input type="datetime-local" name="deadline" required>

    <!-- ✅ Hidden CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <input type="submit" value="Upload">
</form>
    </div>
</div>

</body>
</html>
