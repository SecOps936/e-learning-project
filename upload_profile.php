<?php
session_start();
include('db.php');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $target_dir = "uploads/profile_pictures/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

    $filename = basename($_FILES["profile_picture"]["name"]);
    $target_file = $target_dir . time() . "_" . $filename;

    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($imageFileType, $allowed_types)) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $stmt->bind_param("si", $target_file, $user_id);
            $stmt->execute();
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Failed to upload.";
        }
    } else {
        echo "Invalid file type.";
    }
}
?>
