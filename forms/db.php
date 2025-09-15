<?php
$host = "localhost";
$user = "root";   // MySQL username
$pass = "";       // MySQL password
$dbname = "kacademyx";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
