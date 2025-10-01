<?php
session_start();
include('db.php');

// ✅ Allow only logged-in students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$assignment_id = intval($_GET['id']);

// ✅ Fetch assignment info
$stmt = $conn->prepare("SELECT file_name, file_path FROM assignments WHERE id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File not found.");
}

$assignment = $result->fetch_assoc();

$upload_dir = __DIR__ . "/../secure_uploads/assignments/";
$file_path = $upload_dir . $assignment['file_path'];

if (!file_exists($file_path)) {
    die("File not found on server.");
}

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($assignment['file_name']) . "\"");
header("Expires: 0");
header("Cache-Control: must-revalidate");
header("Pragma: public");
header("Content-Length: " . filesize($file_path));

readfile($file_path);
exit();
?>
