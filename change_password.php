<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $sql = "SELECT password_hash FROM users WHERE user_id = '$user_id'";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();

    if ($user && password_verify($current, $user['password_hash'])) {
        if ($new === $confirm) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $update = "UPDATE users SET password_hash = '$hashed' WHERE user_id = '$user_id'";
            if ($conn->query($update)) {
                $message = "Password updated successfully!";
            } else {
                $message = "Error updating password.";
            }
        } else {
            $message = "New passwords do not match.";
        }
    } else {
        $message = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password</title>
    <style>
        body { font-family: Roboto, sans-serif; background: #f5f5f5; padding: 50px; }
        .container { max-width: 400px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input[type="password"], input[type="submit"] {
            width: 100%; padding: 10px; margin: 10px 0;
            border-radius: 4px; border: 1px solid #ccc;
        }
        input[type="submit"] {
            background-color: #3498db; color: white;
            border: none; cursor: pointer;
        }
        input[type="submit"]:hover { background-color: #2980b9; }
        .message { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Password</h2>
        <?php if ($message): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="current_password" placeholder="Current Password" required>
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <input type="submit" value="Update Password">
        </form>
    </div>
</body>
</html>
