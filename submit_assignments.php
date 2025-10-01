<?php
session_start();
include('db.php');

// ✅ 1. Require login & role check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ 2. CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    $student_id = intval($_SESSION['user_id']);
    $assignment_id = intval($_POST['assignment_id']);

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === 0) {
        $upload_dir = __DIR__ . '/../secure_uploads/submissions/'; // ✅ store outside web root
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // ✅ 3. Restrict file types
        $allowed_extensions = ['pdf', 'doc', 'docx', 'zip'];
        $file_info = pathinfo($_FILES['submission_file']['name']);
        $ext = strtolower($file_info['extension']);

        if (!in_array($ext, $allowed_extensions)) {
            die("Invalid file type. Allowed: PDF, DOC, DOCX, ZIP.");
        }

        // ✅ 4. Secure filename
        $safe_filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $target_path = $upload_dir . $safe_filename;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_path)) {
            // ✅ Store only filename in DB, not full path
            $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_name) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $assignment_id, $student_id, $safe_filename);
            $stmt->execute();

            header("Location: dashboard.php?success=1");
            exit();
        } else {
            die("File upload failed. Please try again.");
        }
    } else {
        die("No valid file uploaded.");
    }
}
?>
