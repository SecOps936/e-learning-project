<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check admin role
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || strtolower($user['role']) !== 'admin') {
    echo "Unauthorized access.";
    exit();
}

$message = '';
$invalid_regs = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_number = intval($_POST['group_number']);
    $member_names = $_POST['member_name'];
    $reg_nos = $_POST['registration_number'];

    // ✅ Ensure group exists (insert if new)
    $check_group = $conn->prepare("SELECT * FROM groups WHERE group_number = ?");
    $check_group->bind_param("i", $group_number);
    $check_group->execute();
    $res = $check_group->get_result();

    if ($res->num_rows === 0) {
        $insert_group = $conn->prepare("INSERT INTO groups (group_number) VALUES (?)");
        $insert_group->bind_param("i", $group_number);
        $insert_group->execute();
        $insert_group->close();
    }
    $check_group->close();

    // ✅ Insert or update members
    $insert_stmt = $conn->prepare("
        INSERT INTO group_members (group_number, member_name, registration_number)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE member_name = VALUES(member_name)
    ");

    for ($i = 0; $i < count($member_names); $i++) {
        $name = trim($member_names[$i]);
        $reg_no = strtoupper(trim($reg_nos[$i]));

        // Validate format
        if (preg_match('/^T2[1-9]-03-\d{5}$/', $reg_no)) {
            $insert_stmt->bind_param("iss", $group_number, $name, $reg_no);
            $insert_stmt->execute();
        } else {
            $invalid_regs[] = $reg_no;
        }
    }

    $insert_stmt->close();

    if (!empty($invalid_regs)) {
        $message = "❌ Invalid reg numbers: " . implode(", ", $invalid_regs);
    } else {
        $message = "✅ Group $group_number updated successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Group</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #64abf3;
            color: white;
            padding: 10px 20px;
        }
        .top-bar .left, .top-bar .center, .top-bar .right { flex: 1; }
        .top-bar .center { text-align: center; font-weight: bold; font-size: 18px; }
        .top-bar .right { text-align: right; }
        .avatar {
            cursor: pointer;
            background-color: #3aaf9f;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            color: white;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            margin: 40px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-height: 600px;
            overflow-y: auto;
        }
        h2 {
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: bold;
            color: #333;
            text-align: center;
        }
        label {
            font-size: 14px;
            font-weight: 500;
            color: #555;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }
        .member {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            background: #f9f9f9;
        }
        .remove-btn {
            margin-top: 8px;
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .remove-btn:hover {
            background-color: #c0392b;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            margin-top: 15px;
        }
        button:hover {
            background: #2980b9;
        }
        .message {
            margin-top: 20px;
            font-weight: bold;
            color: green;
        }
        .add-member-btn {
            background-color: #27ae60;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
        }
        .add-member-btn:hover {
            background-color: #2ecc71;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="left">👨‍💼 Admin Panel</div>
    <div class="center">e-CSDFE Admin Dashboard</div>
    <div class="right">
        <div class="avatar" onclick="toggleDropdown()">
            <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
        </div>
    </div>
</div>

<div class="container">
    <h2>👥 Create Student Group</h2>

    <form method="POST">
        <label>Group Number:</label>
        <input type="text" name="group_number" required>

        <div id="members-container">
            <div class="member">
                <label>Member Name:</label>
                <input type="text" name="member_name[]" required>
                <label>Registration Number</label>
                <input type="text" name="registration_number[]" required>
                <button type="button" class="remove-btn" onclick="removeMember(this)">Remove Member</button>
            </div>
        </div>

        <button type="button" class="add-member-btn" onclick="addMember()">+ Add Another Member</button><br><br>
        <button type="submit">Save Group</button>
    </form>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
</div>

<script>
    function toggleDropdown() {
        var dropdown = document.getElementById("userDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    function addMember() {
        const container = document.getElementById("members-container");
        const memberDiv = document.createElement("div");
        memberDiv.className = "member";
        memberDiv.innerHTML = 
            `<label>Member Name:</label>
            <input type="text" name="member_name[]" required>
            <label>Registration Number</label>
            <input type="text" name="registration_number[]" required>
            <button type="button" class="remove-btn" onclick="removeMember(this)">Remove Member</button>`;
        container.appendChild(memberDiv);
    }

    function removeMember(btn) {
        btn.parentElement.remove();
    }
</script>

</body>
</html>
