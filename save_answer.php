<?php
session_start();
require_once 'forms/db.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION["id"];
// Fetch student ID
$st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($st_res);
if (!$student) {
    echo json_encode(['status' => 'error', 'message' => 'Student record not found']);
    exit();
}
$student_id = $student['id'];

// Get input details
$attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;
$question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
$text_answer = isset($_POST['text_answer']) ? trim($_POST['text_answer']) : '';
$remaining_seconds = isset($_POST['remaining_seconds']) ? intval($_POST['remaining_seconds']) : 0;

// Options could be array or comma-separated string
$selected_options = '';
if (isset($_POST['selected_options'])) {
    if (is_array($_POST['selected_options'])) {
        $clean_opts = array_map('intval', $_POST['selected_options']);
        $selected_options = implode(',', $clean_opts);
    } else {
        $selected_options = trim($_POST['selected_options']);
    }
}

// Verify that this attempt belongs to the logged-in student and is In Progress
$att_stmt = $conn->prepare("SELECT * FROM student_attempts WHERE id = ? AND student_id = ? AND status = 'In Progress'");
$att_stmt->bind_param("ii", $attempt_id, $student_id);
$att_stmt->execute();
$attempt = $att_stmt->get_result()->fetch_assoc();
$att_stmt->close();

if (!$attempt) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or completed attempt']);
    exit();
}

// Update remaining seconds in student_attempts
if ($remaining_seconds >= 0) {
    $upd_stmt = $conn->prepare("UPDATE student_attempts SET remaining_seconds = ? WHERE id = ?");
    $upd_stmt->bind_param("ii", $remaining_seconds, $attempt_id);
    $upd_stmt->execute();
    $upd_stmt->close();
}

if ($question_id <= 0) {
    echo json_encode(['status' => 'success', 'message' => 'Timer updated']);
    exit();
}

// Fetch question details to evaluate correctness
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
$q_stmt->bind_param("i", $question_id);
$q_stmt->execute();
$question = $q_stmt->get_result()->fetch_assoc();
$q_stmt->close();

if (!$question) {
    echo json_encode(['status' => 'error', 'message' => 'Question not found']);
    exit();
}

$is_correct = null;
$points_awarded = 0;
$checked_by_admin = 0;

// Auto-grading logic based on question type
if ($question['question_type'] == 'MCQ_SINGLE') {
    if (!empty($selected_options)) {
        $opt_id = intval($selected_options);
        $opt_res = mysqli_query($conn, "SELECT is_correct FROM question_options WHERE id = $opt_id AND question_id = $question_id");
        if ($opt_row = mysqli_fetch_assoc($opt_res)) {
            $is_correct = $opt_row['is_correct'] ? 1 : 0;
            $points_awarded = $is_correct ? $question['points'] : 0;
        }
    }
} elseif ($question['question_type'] == 'MCQ_MULTIPLE') {
    if (!empty($selected_options)) {
        $student_opt_ids = explode(',', $selected_options);
        sort($student_opt_ids);
        
        // Fetch all correct options for this question
        $correct_opts_res = mysqli_query($conn, "SELECT id FROM question_options WHERE question_id = $question_id AND is_correct = 1");
        $correct_opt_ids = [];
        while ($r = mysqli_fetch_assoc($correct_opts_res)) {
            $correct_opt_ids[] = $r['id'];
        }
        sort($correct_opt_ids);
        
        if ($student_opt_ids == $correct_opt_ids) {
            $is_correct = 1;
            $points_awarded = $question['points'];
        } else {
            $is_correct = 0;
            $points_awarded = 0;
        }
    }
} elseif ($question['question_type'] == 'TRUE_FALSE') {
    if (!empty($selected_options)) {
        $opt_id = intval($selected_options);
        $opt_res = mysqli_query($conn, "SELECT is_correct FROM question_options WHERE id = $opt_id AND question_id = $question_id");
        if ($opt_row = mysqli_fetch_assoc($opt_res)) {
            $is_correct = $opt_row['is_correct'] ? 1 : 0;
            $points_awarded = $is_correct ? $question['points'] : 0;
        }
    }
} elseif ($question['question_type'] == 'FILL_BLANK') {
    if ($text_answer !== '') {
        $correct_fb = strtolower(trim($question['correct_text_answer']));
        $student_fb = strtolower(trim($text_answer));
        if ($correct_fb === $student_fb) {
            $is_correct = 1;
            $points_awarded = $question['points'];
        } else {
            $is_correct = 0;
            $points_awarded = 0;
        }
    }
} else {
    // SHORT_ANSWER, LONG_ANSWER
    // These need manual checking by admin. No auto points awarded.
    $is_correct = null;
    $points_awarded = 0;
    $checked_by_admin = 0;
}

// Check if answer already exists
$ans_check = mysqli_query($conn, "SELECT id FROM student_answers WHERE attempt_id = $attempt_id AND question_id = $question_id");
if (mysqli_num_rows($ans_check) > 0) {
    $stmt = $conn->prepare("UPDATE student_answers 
                            SET selected_option_ids = ?, text_answer = ?, is_correct = ?, points_awarded = ?, checked_by_admin = ? 
                            WHERE attempt_id = ? AND question_id = ?");
    $stmt->bind_param("ssiiiii", $selected_options, $text_answer, $is_correct, $points_awarded, $checked_by_admin, $attempt_id, $question_id);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO student_answers (attempt_id, question_id, selected_option_ids, text_answer, is_correct, points_awarded, checked_by_admin) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissiii", $attempt_id, $question_id, $selected_options, $text_answer, $is_correct, $points_awarded, $checked_by_admin);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['status' => 'success', 'message' => 'Answer saved successfully']);
exit();
?>
