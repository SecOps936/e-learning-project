<?php
session_start();
include('db.php');

$message = "";

// ✅ Step 1: Check reset token, not user_id
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (!$token) {
    die("Invalid or missing reset token.");
}

// ✅ Step 2: Verify token in DB
$stmt = $conn->prepare("SELECT user_id, token_expiry FROM password_resets WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$resetData = $result->fetch_assoc();

if (!$resetData || strtotime($resetData['token_expiry']) < time()) {
    die("Reset link expired or invalid.");
}
$user_id = $resetData['user_id'];

// ✅ Step 3: Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Step 4: Handle form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF validation failed.");
    }

    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Password policy
    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($new_password) < 8 || 
              !preg_match('/[A-Z]/', $new_password) ||
              !preg_match('/[a-z]/', $new_password) ||
              !preg_match('/[0-9]/', $new_password) ||
              !preg_match('/[\W]/', $new_password)) {
        $message = "Password must be at least 8 characters with uppercase, lowercase, number, and special character.";
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $password_hash, $user_id);

        if ($stmt->execute()) {
            // ✅ Delete used token
            $conn->query("DELETE FROM password_resets WHERE reset_token = '{$token}'");

            $message = "Password has been updated. You can now <a href='login.php'>login</a>.";
        } else {
            $message = "Unexpected error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: url("images/e-CSDFE.jpg") no-repeat center center fixed; /* Background image */
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.3); /* Transparent background */
            backdrop-filter: blur(10px); /* Glass effect with blur */
            padding: 30px;
            border-radius: 15px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            animation: slideFade 0.5s ease;
            color: #333;
        }

        @keyframes slideFade {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        input:focus {
            border-color: #3aaf9f;
            outline: none;
        }

        button {
            background-color: #3aaf9f;
            color: white;
            padding: 12px;
            font-size: 1rem;
            width: 100%;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #68d3a4;
        }

        .message {
            margin-top: 15px;
            text-align: center;
            font-size: 0.95rem;
        }

        .alert {
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }

        a {
            color: #3aaf9f;
            text-decoration: none;
            font-weight: 500;
        }

        a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

<div class="container">
    <h2>Reset Your Password</h2>

    <?php if ($message): ?>
        <div class="alert <?php echo strpos($message, 'updated') !== false ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($user_id && (empty($message) || strpos($message, 'match') !== false || strpos($message, 'Error') !== false)): ?>
    <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="password" name="new_password" placeholder="New Password" required>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="submit">Update Password</button>
</form>
    <?php endif; ?>

    <div class="message">
    </div>
</div>

</body>
</html>
