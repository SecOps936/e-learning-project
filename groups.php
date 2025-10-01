<?php
session_start();
include('db.php');

// ------------------ SECURITY ------------------

// 1. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// 2. Use prepared statements to prevent SQL Injection
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Invalid session user
    session_destroy();
    header('Location: login.php');
    exit();
}

// 3. Check user role
$role = strtolower($user['role']);
if ($role !== 'student') {
    echo "Access denied.";
    exit();
}

// Set profile picture or default
$profilePic = !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : 'default.png';

// 4. Fetch groups using prepared statements to avoid SQL injection
$groups = [];
$groups_stmt = $conn->prepare("SELECT DISTINCT group_number FROM groups ORDER BY group_number");
$groups_stmt->execute();
$groups_result = $groups_stmt->get_result();

while ($row = $groups_result->fetch_assoc()) {
    $group_number = $row['group_number'];
    $groups[$group_number] = [];

    $members_stmt = $conn->prepare("SELECT member_name, registration_number FROM group_members WHERE group_number = ? ORDER BY member_name");
    $members_stmt->bind_param("s", $group_number);
    $members_stmt->execute();
    $members_result = $members_stmt->get_result();

    while ($member = $members_result->fetch_assoc()) {
        $groups[$group_number][] = $member;
    }
    $members_stmt->close();
}
$groups_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Group</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; margin: 0; background: rgb(240, 236, 236); }

        .top-bar {
            display: flex; justify-content: space-between;
            align-items: center; background-color: rgb(100, 171, 243);
            color: white; padding: 15px 30px;
        }

        .top-bar .left, .top-bar .center, .top-bar .right { flex: 1; }
        .top-bar .center { text-align: center; font-weight: bold; font-size: 18px; }
        .top-bar .right { text-align: right; position: relative; }

        .avatar {
            cursor: pointer;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            color: white;
            background-color: #3aaf9f;
            object-fit: cover;
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

        .dropdown a:hover { background-color: #f0f0f0; }

        .sidebar {
            float: left;
            width: 220px;
            height: calc(100vh - 60px);
            background: #2c3e50;
            padding: 20px;
            color: white;
        }

        .sidebar h2 {
            margin-bottom: 25px;
            font-size: 20px;
            color: #ecf0f1;
        }

        .sidebar a {
            display: block;
            margin: 10px 0;
            color: #ecf0f1;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 4px;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .main-content {
            margin-left: 220px;
            padding: 40px;
            background-color: #ecf0f1;
            height: calc(100vh - 60px);
            overflow-y: auto;
        }

        h3 {
            color: #2c3e50;
            margin-bottom: 30px;
        }

        .group-box {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            padding: 25px;
        }

        .group-header {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #34495e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #3498db;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .back-link:hover {
            background-color: #2980b9;
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

<!-- Top Bar -->
<div class="top-bar">
    <div class="left">Academic Year: 2024/2025</div>
    <div class="center">BSc-CSDFE2</div>
    <div class="right">
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($profilePic); ?>" class="avatar" onclick="toggleDropdown()" alt="Profile">
        <?php else: ?>
            <div class="avatar" onclick="toggleDropdown()">
                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <div id="userDropdown" class="dropdown">
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<!-- Sidebar for Student -->
<div class="sidebar">
    <h2>e-CSDFE</h2>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="curriculum.php">📘 Curriculum</a>
    <a href="timetable.php">🗓️ Timetable</a>
    <a href="groups.php">👥 Groups</a>
    <a href="register_carry_over.php">♻️ Register Carry Over</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <h3>Groups</h3>
    <?php if (!empty($groups)): ?>
        <?php foreach ($groups as $group_number => $members): ?>
            <div class="group-box">
                <div class="group-header">👥 Group Number: <?php echo htmlspecialchars($group_number); ?></div>
                <?php if (!empty($members)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Registration Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['registration_number']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No members found in this group.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No groups found.</p>
    <?php endif; ?>
</div>

</body>
</html>
