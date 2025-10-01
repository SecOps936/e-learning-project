<?php
session_start();
include('db.php');

// Ensure only admin can delete
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT role FROM users WHERE user_id = '$user_id'";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

if (strtolower($user['role']) !== 'admin') {
    die("Unauthorized access.");
}

if (isset($_GET['id'])) {
    $course_id = intval($_GET['id']);
    
    // Delete the course
    $delete_sql = "DELETE FROM courses WHERE course_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $course_id);

    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=Course+Deleted+Successfully");
    } else {
        echo "Error deleting course.";
    }
}
?>
