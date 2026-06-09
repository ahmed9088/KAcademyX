<?php
// test_live_stats.php - AJAX endpoint for live competition tracking inside take_test.php
session_start();
require_once 'forms/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;

if ($test_id <= 0 || $attempt_id <= 0) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

$user_id = $_SESSION["id"];
$st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($st_res);
if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit();
}
$student_id = $student['id'];

// 1. Update last_active_at for the current student's attempt to keep them "Online"
$conn->query("UPDATE student_attempts SET last_active_at = CURRENT_TIMESTAMP WHERE id = $attempt_id AND student_id = $student_id");

// 2. Remove student from waiting lobby table if they are still in it
$conn->query("DELETE FROM lobby_participants WHERE student_id = $student_id AND test_id = $test_id");

// 3. Fetch test settings
$test_res = mysqli_query($conn, "SELECT show_live_submissions, lobby_privacy FROM tests WHERE id = $test_id");
$test = mysqli_fetch_assoc($test_res);
$show_ticker = $test ? intval($test['show_live_submissions']) : 0;
$privacy = $test ? $test['lobby_privacy'] : 'Public';

// 4. Calculate Online, Submitted, and In Progress counts
// Online: status = In Progress AND active within last 25 seconds
$online_res = mysqli_query($conn, "SELECT COUNT(*) FROM student_attempts WHERE test_id = $test_id AND status = 'In Progress' AND last_active_at >= DATE_SUB(NOW(), INTERVAL 25 SECOND)");
$online_count = mysqli_fetch_row($online_res)[0];

// Submitted: status = Completed or Auto Submitted
$sub_res = mysqli_query($conn, "SELECT COUNT(*) FROM student_attempts WHERE test_id = $test_id AND status IN ('Completed', 'Auto Submitted')");
$submitted_count = mysqli_fetch_row($sub_res)[0];

// In Progress (Total remaining)
$rem_res = mysqli_query($conn, "SELECT COUNT(*) FROM student_attempts WHERE test_id = $test_id AND status = 'In Progress'");
$remaining_count = mysqli_fetch_row($rem_res)[0];

// 5. Query recent submissions in the last 20 seconds (for slide-in toast notifications)
$recent_subs = [];
if ($show_ticker === 1) {
    $rec_res = mysqli_query($conn, "SELECT sa.id, s.name, sa.completed_at FROM student_attempts sa 
                                    JOIN students s ON sa.student_id = s.id 
                                    WHERE sa.test_id = $test_id 
                                    AND sa.status IN ('Completed', 'Auto Submitted') 
                                    AND sa.completed_at >= DATE_SUB(NOW(), INTERVAL 20 SECOND) 
                                    ORDER BY sa.completed_at DESC");
    while ($row = mysqli_fetch_assoc($rec_res)) {
        if ($privacy == 'Anonymous') {
            $display_name = "Student_" . $row['id']; // use attempt_id or hashed ID for anonymization
        } else {
            $display_name = $row['name'];
        }
        
        $recent_subs[] = [
            'attempt_id' => intval($row['id']),
            'name' => $display_name
        ];
    }
}

echo json_encode([
    'online_count' => max(1, $online_count), // At least current student is online
    'submitted_count' => $submitted_count,
    'remaining_count' => $remaining_count,
    'recent_submissions' => $recent_subs
]);
exit();
?>
