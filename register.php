<?php
// -------------------- DEBUG MODE (development only) --------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// -------------------- SESSION CONFIG --------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    // set secure => false if testing on localhost (http://)
    'secure' => isset($_SERVER['HTTPS']), 
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// -------------------- DB CONNECTION --------------------
include('db.php');
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// -------------------- CSRF TOKEN --------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// -------------------- HANDLE FORM SUBMISSION --------------------
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF CHECK
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    // Sanitize Inputs
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $registration_number = htmlspecialchars(trim($_POST['registration_number']));
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $email = strtolower(trim($_POST['email']));
    $gender = $_POST['gender'];

    // Validation
    if (!preg_match('/^T2[1-9]-03-\d{5}$/', $registration_number)) {
        $error = "Invalid registration number format (Expected: T2X-03-YYYYY).";
    } elseif (!preg_match('/^\+255\d{9}$/', $phone)) {
        $error = "Phone must start with +255 and be 13 digits total.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (empty($gender)) {
        $error = "Gender is required.";
    } elseif (strlen($password) < 8 || 
              !preg_match('/[A-Z]/', $password) ||
              !preg_match('/[a-z]/', $password) ||
              !preg_match('/[0-9]/', $password) ||
              !preg_match('/[\W]/', $password)) {
        $error = "Password must be at least 8 chars and include upper, lower, number & special char.";
    } else {
        // Check if student exists in students table
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE first_name = ? AND last_name = ? AND registration_number = ?");
        if ($stmt === false) {
            die("SQL error (students check): " . $conn->error);
        }
        $stmt->bind_param("sss", $first_name, $last_name, $registration_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Registration failed: Student not found in records.";
        } else {
            // Check if user already registered
            $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE registration_number = ?");
            if ($stmt_check === false) {
                die("SQL error (users check): " . $conn->error);
            }
            $stmt_check->bind_param("s", $registration_number);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $error = "This student is already registered.";
            } else {
                // Insert new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
               $row = $result->fetch_assoc();
$student_id = $row['student_id'];

$stmt_insert = $conn->prepare("INSERT INTO users 
    (student_id, first_name, last_name, registration_number, password_hash, phone, email, gender, role) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Student')");
$stmt_insert->bind_param("isssssss", $student_id, $first_name, $last_name, $registration_number, $password_hash, $phone, $email, $gender);

                if ($stmt_insert->execute()) {
                    $success = "✅ Registration successful! Please log in.";
                    // Redirect after success
                    echo "<script>alert('Registration successful! Please log in.'); window.location='login.php';</script>";
                    exit;
                } else {
                    $error = "Unexpected error: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Student Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: url("images/e-CSDFE.jpg") no-repeat center center fixed;
            background-size: cover;
            margin: 0; padding: 0; height: 100vh;
            display: flex; justify-content: center; align-items: center;
        }
        .form-wrapper {
            background-color: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 8px;
            width: 100%; max-width: 340px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            color: #333; text-align: center;
        }
        h1 { margin-bottom: 12px; color: #222; font-size: 1.4rem; }
        label { display: block; margin-top: 4px; margin-bottom: 2px; text-align: left; }
        input, select {
            width: 100%; padding: 6px; margin-bottom: 8px;
            border-radius: 6px; border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            background-color: #3aaf9f; color: white;
            font-size: 1rem; padding: 8px;
            border-radius: 6px; border: none; cursor: pointer; width: 100%;
        }
        button:hover { background-color: #319e8e; }
        .message { margin-top: 10px; font-size: 0.85rem; }
        .message a { color: #3aaf9f; text-decoration: none; }
        .message a:hover { text-decoration: underline; }
        .error { color: red; font-size: 0.9rem; margin-bottom: 10px; }
        .success { color: green; font-size: 0.9rem; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="form-wrapper">
    <h1>Register Account</h1>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" id="first_name" required>

        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" id="last_name" required>

        <label for="registration_number">Registration Number:</label>
        <input type="text" name="registration_number" id="registration_number" placeholder="T2X-03-YYYYY" required>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>

        <label for="phone">Phone Number:</label>
        <input type="text" name="phone" id="phone" placeholder="+255XXXXXXXXX" required>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>

        <label for="gender">Gender:</label>
        <select name="gender" id="gender" required>
            <option value="">Select Gender</option>
            <option>Male</option>
            <option>Female</option>
        </select>
        
        <button type="submit" name="submit">Register</button>
    </form>

    <div class="message">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>
</body>
</html>
