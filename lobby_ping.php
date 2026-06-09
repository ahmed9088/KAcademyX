<?php
// lobby_ping.php - AJAX heartbeat receiver for competition waiting lobby
session_start();
require_once 'forms/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (empty($token)) {
    echo json_encode(['error' => 'Missing token']);
    exit();
}

// Fetch student ID
$user_id = $_SESSION["id"];
$st_res = mysqli_query($conn, "SELECT id, name FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($st_res);
if (!$student) {
    echo json_encode(['error' => 'Student not found']);
    exit();
}
$student_id = $student['id'];
$student_name = $student['name'];

// Fetch test details
$test_stmt = $conn->prepare("SELECT id, start_datetime, lobby_privacy FROM tests WHERE share_token = ?");
$test_stmt->bind_param("s", $token);
$test_stmt->execute();
$test = $test_stmt->get_result()->fetch_assoc();
$test_stmt->close();

if (!$test) {
    echo json_encode(['error' => 'Test not found']);
    exit();
}
$test_id = $test['id'];

// 1. Insert/Update active student in lobby_participants
$ping_stmt = $conn->prepare("INSERT INTO lobby_participants (test_id, student_id, last_ping_at) 
                             VALUES (?, ?, CURRENT_TIMESTAMP) 
                             ON DUPLICATE KEY UPDATE last_ping_at = CURRENT_TIMESTAMP");
$ping_stmt->bind_param("ii", $test_id, $student_id);
$ping_stmt->execute();
$ping_stmt->close();

// 2. Clear out students inactive for more than 12 seconds
$conn->query("DELETE FROM lobby_participants WHERE last_ping_at < DATE_SUB(NOW(), INTERVAL 12 SECOND)");

// 3. Check countdown
$now = new DateTime("now", new DateTimeZone("Asia/Karachi"));
$start_time = new DateTime($test['start_datetime'], new DateTimeZone("Asia/Karachi"));
$remaining_seconds = $start_time->getTimestamp() - $now->getTimestamp();
$started = ($remaining_seconds <= 0);

// 4. Fetch all active participants
$parts_query = "SELECT lp.student_id, s.name, s.user_id FROM lobby_participants lp 
                JOIN students s ON lp.student_id = s.id 
                WHERE lp.test_id = $test_id 
                ORDER BY s.name ASC";
$parts_res = mysqli_query($conn, $parts_query);
$participants = [];

while ($row = mysqli_fetch_assoc($parts_res)) {
    $is_you = ($row['student_id'] == $student_id);
    
    // Mask name if anonymous
    if ($test['lobby_privacy'] == 'Anonymous') {
        $display_name = "Student_" . $row['student_id'];
    } else {
        $display_name = $row['name'];
    }
    
    $participants[] = [
        'name' => $display_name,
        'is_you' => $is_you
    ];
}

echo json_encode([
    'started' => $started,
    'remaining_seconds' => max(0, $remaining_seconds),
    'participants' => $participants,
    'total_count' => count($participants)
]);
exit();
?>
