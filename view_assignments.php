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

if ($role !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Fetch submitted assignments
$query = "
    SELECT sa.submission_id, sa.file_name, sa.file_path, sa.uploaded_at, 
           u.first_name, u.last_name, c.course_name
    FROM submision_assignment sa
    JOIN users u ON sa.user_id = u.user_id
    JOIN courses c ON sa.course_id = c.course_id
    ORDER BY sa.uploaded_at DESC
";

$results = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Assignments</title>
    <style>
        body { font-family: 'Roboto', sans-serif; margin: 0; background: #f9f9f9; }
        .top-bar {
            display: flex; justify-content: space-between;
            align-items: center; background-color: #3498db;
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
            border-radius: 5px; min-width: 150px; z-index: 999;
        }

        .dropdown a {
            display: block; padding: 10px 15px;
            color: #2c3e50; text-decoration: none;
        }

        .dropdown a:hover { background-color: #f0f0f0; }

        h2 { color: #2c3e50; margin: 30px; }

        table {
            width: calc(100% - 60px); margin: 20px 30px;
            border-collapse: collapse; background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px 15px; border: 1px solid #ddd;
        }

        th {
            background-color: #3498db;
            color: white;
            text-align: left;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        a.download-link {
            color: #2980b9;
            text-decoration: none;
        }

        a.download-link:hover {
            text-decoration: underline;
        }
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

<!-- Admin Top Bar -->
<div class="top-bar">
    <div class="left">👨‍💼 Admin Panel</div>
    <div class="center">e-CSDFE Admin Dashboard</div>
    <div class="right">
        <div class="avatar" onclick="toggleDropdown()">
            <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
        </div>
        <div id="userDropdown" class="dropdown">
            <a href="change_password.php">Change Password</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<h2>📂 Submitted Assignments</h2>

<table>
    <thead>
        <tr>
            <th>Student</th>
            <th>Course</th>
            <th>File</th>
            <th>Submitted On</th>
            <th>Download</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($results->num_rows > 0): ?>
            <?php while ($row = $results->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                    <td><?php echo date('F j, Y, g:i a', strtotime($row['uploaded_at'])); ?></td>
                    <td><a class="download-link" href="<?php echo htmlspecialchars($row['file_path']); ?>" download>Download</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No assignments submitted yet.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
