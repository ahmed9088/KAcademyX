<?php
session_start();
require_once 'forms/db.php';

// Auth guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: forms/login.php");
    exit();
}

$user_id = $_SESSION["id"];
$st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($st_res);
if (!$student) {
    die("Student not found.");
}
$student_id = $student['id'];

$attempt_id = isset($_REQUEST['attempt_id']) ? intval($_REQUEST['attempt_id']) : 0;
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'submit'; // 'submit' or 'timeout'

// Verify attempt is In Progress
$att_stmt = $conn->prepare("SELECT * FROM student_attempts WHERE id = ? AND student_id = ? AND status = 'In Progress'");
$att_stmt->bind_param("ii", $attempt_id, $student_id);
$att_stmt->execute();
$attempt = $att_stmt->get_result()->fetch_assoc();
$att_stmt->close();

if (!$attempt) {
    // Attempt already finished or invalid
    header("Location: test_result.php?attempt_id=$attempt_id");
    exit();
}

$now = new DateTime("now", new DateTimeZone("Asia/Karachi"));
$now_db = $now->format('Y-m-d H:i:s');
$status = ($mode == 'timeout') ? 'Auto Submitted' : 'Completed';

// Update attempt state
$upd_stmt = $conn->prepare("UPDATE student_attempts SET status = ?, completed_at = ?, remaining_seconds = 0 WHERE id = ?");
$upd_stmt->bind_param("ssi", $status, $now_db, $attempt_id);
$upd_stmt->execute();
$upd_stmt->close();

$test_id = $attempt['test_id'];

// Get test details
$test_stmt = $conn->prepare("SELECT * FROM tests WHERE id = ?");
$test_stmt->bind_param("i", $test_id);
$test_stmt->execute();
$test = $test_stmt->get_result()->fetch_assoc();
$test_stmt->close();

// Check if test has ANY manual questions (SHORT_ANSWER or LONG_ANSWER).
// If it has manual questions, we cannot instantly publish the final results or mark them as passed/failed until graded!
$manual_qs_res = mysqli_query($conn, "SELECT COUNT(*) FROM test_questions tq 
                                      JOIN questions q ON tq.question_id = q.id 
                                      WHERE tq.test_id = $test_id AND q.question_type IN ('SHORT_ANSWER', 'LONG_ANSWER')");
$manual_qs_count = mysqli_fetch_row($manual_qs_res)[0];

// Calculate scores for auto-gradable questions
// Total questions
$total_qs_res = mysqli_query($conn, "SELECT COUNT(*) FROM test_questions WHERE test_id = $test_id");
$total_questions = mysqli_fetch_row($total_qs_res)[0];

// Max points
$max_pts_res = mysqli_query($conn, "SELECT SUM(q.points) FROM test_questions tq JOIN questions q ON tq.question_id = q.id WHERE tq.test_id = $test_id");
$max_points = mysqli_fetch_row($max_pts_res)[0] ?: 1;

// Fetch all student answers
$answers_res = mysqli_query($conn, "SELECT sa.*, q.points as max_points FROM student_answers sa 
                                    JOIN questions q ON sa.question_id = q.id 
                                    WHERE sa.attempt_id = $attempt_id");

$correct_answers = 0;
$wrong_answers = 0;
$skipped_questions = 0;
$total_earned_score = 0;

$answers_map = [];
while ($ans = $answers_res->fetch_assoc()) {
    $answers_map[$ans['question_id']] = $ans;
}

// Ensure all questions have an answer row (even if skipped)
$all_test_qs_res = mysqli_query($conn, "SELECT question_id FROM test_questions WHERE test_id = $test_id");
while($tq = mysqli_fetch_assoc($all_test_qs_res)) {
    $q_id = $tq['question_id'];
    if (!isset($answers_map[$q_id])) {
        // Insert empty skip answer
        mysqli_query($conn, "INSERT INTO student_answers (attempt_id, question_id, selected_option_ids, text_answer, is_correct, points_awarded) 
                             VALUES ($attempt_id, $q_id, '', '', 0, 0)");
        $skipped_questions++;
    } else {
        $ans = $answers_map[$q_id];
        $is_skipped = empty($ans['selected_option_ids']) && empty($ans['text_answer']);
        if ($is_skipped) {
            $skipped_questions++;
        } else {
            if ($ans['is_correct'] == 1) {
                $correct_answers++;
            } else {
                $wrong_answers++;
            }
        }
        $total_earned_score += $ans['points_awarded'];
    }
}

// Compute percentage
$percentage = round(($total_earned_score / $max_points) * 100, 2);
$is_passed = ($percentage >= $test['passing_marks']) ? 1 : 0;
$time_taken = strtotime($now_db) - strtotime($attempt['started_at']);
if ($time_taken < 0) $time_taken = 0;

// Save results
$stmt = $conn->prepare("INSERT INTO results (attempt_id, student_id, test_id, total_questions, correct_answers, wrong_answers, skipped_questions, score, percentage, time_taken_seconds, is_passed) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiiiiidii", $attempt_id, $student_id, $test_id, $total_questions, $correct_answers, $wrong_answers, $skipped_questions, $total_earned_score, $percentage, $time_taken, $is_passed);
$stmt->execute();
$result_id = $conn->insert_id;
$stmt->close();

// Update Leaderboard entry & award badges (If it does not contain manual questions)
if ($manual_qs_count == 0) {
    require_once 'includes/badge_helper.php';
    award_badges($conn, $student_id, $test_id, $attempt_id);

    // Auto generate certificate if passed and enabled
    if ($is_passed && $test['certificate_enabled']) {
        $verification_code = 'CERT-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $conn->prepare("INSERT INTO certificates (result_id, student_id, test_id, verification_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $result_id, $student_id, $test_id, $verification_code);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect to results page
header("Location: test_result.php?attempt_id=$attempt_id");
exit();
?>
