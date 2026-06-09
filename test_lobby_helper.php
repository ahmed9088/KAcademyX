<?php
session_start();
// Set student name if not set
if (!isset($_SESSION['student_name'])) {
    $_SESSION['student_name'] = "Test Student";
}
// Set start time to 290 seconds ago (10 seconds left of 300)
$_SESSION['waiting_start_time'] = time() - 290;
echo "Lobby waiting timer set to 10 seconds remaining. Redirecting to waiting room...";
header("Refresh: 2; url=waiting_room.php");
?>
