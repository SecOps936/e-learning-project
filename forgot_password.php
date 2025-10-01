<?php
session_start();
include('db.php');

$message = "";

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $identifier = trim($_POST['identifier']);

    // Prevent brute force: limit attempts in session
    if (!isset($_SESSION['reset_attempts'])) {
        $_SESSION['reset_attempts'] = 0;
    }

    if ($_SESSION['reset_attempts'] >= 5) {
        $message = "Too many attempts. Please try again later.";
    } else {
        $sql = "SELECT * FROM users WHERE registration_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        // Generic message to prevent user enumeration
        $message = "If the registration number exists, you will receive a password reset link.";

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];

            // Use session or token to handle reset securely
            $reset_token = bin2hex(random_bytes(16));
            $_SESSION['password_reset_token'] = $reset_token;
            $_SESSION['password_reset_user'] = $user_id;
            $_SESSION['password_reset_time'] = time();

            header("Location: reset_password.php?token=" . $reset_token);
            exit();
        } else {
            $_SESSION['reset_attempts']++;
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            background: url("images/e-CSDFE.jpg") no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .box {
            background-color: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            animation: fadeIn 0.6s ease;
            color: #333;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .box h2 { text-align: center; margin-bottom: 25px; }
        form { display: flex; flex-direction: column; }
        label { font-weight: 500; margin-bottom: 8px; color: #444; }
        input[type="text"] {
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
            margin-bottom: 20px;
            width: 100%;
        }
        input:focus { border-color: #3aaf9f; outline: none; }
        button {
            background-color: #3aaf9f;
            color: white;
            padding: 12px;
            font-size: 1rem;
            width: 100%;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover { background-color: #68d3a4; }
        .message { margin-top: 15px; text-align: center; font-size: 0.9rem; }
        .message a { color: #3aaf9f; text-decoration: none; font-weight: 500; }
        .message a:hover { text-decoration: underline; }
        .error {
            background: #fdecea;
            color: #b71c1c;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            text-align: center;
        }
        @media (max-width: 500px) { .box { padding: 20px; } }
    </style>
</head>
<body>

<div class="box">
    <h2>Forgot Your Password?</h2>

    <?php if (!empty($message)): ?>
        <div class="error"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="identifier">Registration Number:</label>
        <input type="text" name="identifier" id="identifier" required>

        <!-- CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <button type="submit">Reset Password</button>
    </form>

    <div class="message">
        <a href="login.php">Back to Login</a>
    </div>
</div>

</body>
</html>
