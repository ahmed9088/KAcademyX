<?php
session_start();
require_once 'forms/db.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    
    // Fetch student ID
    $user_id = $_SESSION["id"];
    $st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
    if ($student = mysqli_fetch_assoc($st_res)) {
        $student_id = $student['id'];
        // Mark as read
        mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND student_id = $student_id");
    }
}
echo json_encode(['status' => 'success']);
exit();
?>
