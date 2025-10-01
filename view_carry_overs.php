<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('db.php');

// Fetch user role from Users table
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
$role = strtolower($user['role']);

// Only allow admins
if ($role !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

// ✅ Fetch all carry-over registrations directly from the table
$carry_over_sql = "SELECT * FROM carryover_registrations";
$carry_over_result = $conn->query($carry_over_sql);

$carry_overs = [];
while ($row = $carry_over_result->fetch_assoc()) {
    $carry_overs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Carry-Over Registrations</title>
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
            margin: 0 auto; max-width: 1000px;
            padding: 40px;
            background-color: #ecf0f1; overflow-y: auto;
            height: calc(100vh - 60px);
        }

        .carry-over-list {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .carry-over-table {
            width: 100%; border-collapse: collapse;
        }

        .carry-over-table th, .carry-over-table td {
            padding: 12px; text-align: left; border-bottom: 1px solid #ddd;
        }

        .carry-over-table th {
            background-color: #3498db; color: white;
        }
    </style>
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
    <h3>Carry Overs</h3>

    <div class="carry-over-list">
        <table class="carry-over-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Registration Number</th>
                    <th>Course</th>
                    <!-- Optional: Uncomment if your table includes these -->
                    <!-- <th>Semester</th>
                    <th>Session</th> -->
                </tr>
            </thead>
            <tbody>
                <?php if (empty($carry_overs)): ?>
                    <tr>
                        <td colspan="3">No carryover</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($carry_overs as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($entry['registration_number']); ?></td>
                            <td><?php echo htmlspecialchars($entry['course_name']); ?></td>
                            <!-- Optional: Uncomment if your table includes these -->
                            <!-- <td><?php echo htmlspecialchars($entry['semester']); ?></td>
                            <td><?php echo htmlspecialchars($entry['session']); ?></td> -->
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleDropdown() {
        const dropdown = document.getElementById("userDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }
</script>

</body>
</html>
