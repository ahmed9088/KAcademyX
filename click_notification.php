<?php
session_start();
require_once 'forms/db.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php'); exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION["id"];

$st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
$st_row = mysqli_fetch_assoc($st_res);
if ($st_row && $id > 0) {
    $student_id = $st_row['id'];
    
    // Fetch the notification link
    $notif_res = mysqli_query($conn, "SELECT link FROM notifications WHERE id = $id AND student_id = $student_id");
    if ($notif = mysqli_fetch_assoc($notif_res)) {
        // Mark as read
        mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $id AND student_id = $student_id");
        
        $link = $notif['link'];
        if (!empty($link)) {
            header('Location: ' . $link);
            exit();
        }
    }
}

// Fallback
header('Location: test.php');
exit();
?>
