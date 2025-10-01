<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$role = strtolower($user['role']);

if ($role !== 'admin') {
    echo "Access denied.";
    exit();
}

// Fetch all unique group numbers
$groups_sql = "SELECT DISTINCT group_number FROM groups ORDER BY group_number";
$groups_result = $conn->query($groups_sql);

$groups = [];
while ($row = $groups_result->fetch_assoc()) {
    $group_number = $row['group_number'];
    $groups[$group_number] = [];

    // Get members of this group
    $members_sql = "SELECT member_name, registration_number 
                    FROM group_members 
                    WHERE group_number = '$group_number'
                    ORDER BY member_name";
    $members_result = $conn->query($members_sql);

    while ($member = $members_result->fetch_assoc()) {
        $groups[$group_number][] = $member;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Groups</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; margin: 0; background:rgb(240, 236, 236); }

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
            margin: 0 auto; max-width: 1000px;
            padding: 40px;
            background-color:rgb(236, 239, 240);
            min-height: calc(100vh - 60px);
        }

        h2 {
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
