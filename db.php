<?php
// db.php - Database connection

$servername = "localhost"; // or your server
$username = "heri";        // default username for MySQL
$password = "1234";            // default password for MySQL (empty for XAMPP)
$dbname = "StudyPortal";   // updated database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
