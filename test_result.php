<?php
session_start();
require_once 'forms/db.php';

// Auth guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

$user_id = $_SESSION["id"];
$st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($st_res);
if (!$student) { header('Location: forms/login.php'); exit(); }
$student_id = $student['id'];

$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;
if ($attempt_id <= 0) {
    header("Location: test.php");
    exit();
}

// Fetch attempt details
$att_stmt = $conn->prepare("SELECT sa.*, t.title as test_title, t.passing_marks, t.certificate_enabled, t.share_token, t.lobby_privacy, t.allow_review
                           FROM student_attempts sa
                           JOIN tests t ON sa.test_id = t.id
                           WHERE sa.id = ? AND sa.student_id = ?");
$att_stmt->bind_param("ii", $attempt_id, $student_id);
$att_stmt->execute();
$attempt = $att_stmt->get_result()->fetch_assoc();
$att_stmt->close();

if (!$attempt) {
    header("Location: test.php");
    exit();
}

$test_id = $attempt['test_id'];

// Check if there are unchecked manual grading questions
$manual_check_res = mysqli_query($conn, "SELECT COUNT(*) FROM student_answers sa 
                                        JOIN questions q ON sa.question_id = q.id 
                                        WHERE sa.attempt_id = $attempt_id 
                                        AND q.question_type IN ('SHORT_ANSWER', 'LONG_ANSWER') 
                                        AND sa.checked_by_admin = 0");
$pending_manual_grading = mysqli_fetch_row($manual_check_res)[0] > 0;

// Fetch results details
$results_res = mysqli_query($conn, "SELECT * FROM results WHERE attempt_id = $attempt_id");
$result = mysqli_fetch_assoc($results_res);

$rank = 0;
$certificate = null;

if ($result) {
    // Calculate student rank
    $rank_query = "SELECT COUNT(DISTINCT student_id) as higher_scores FROM results 
                   WHERE test_id = $test_id AND score > (SELECT score FROM results WHERE attempt_id = $attempt_id)";
    $rank_res = mysqli_query($conn, $rank_query);
    $rank = 1;
    if ($rank_row = mysqli_fetch_assoc($rank_res)) {
        $rank += $rank_row['higher_scores'];
    }
    
    // Fetch certificate if generated
    $cert_res = mysqli_query($conn, "SELECT * FROM certificates WHERE result_id = {$result['id']}");
    $certificate = mysqli_fetch_assoc($cert_res);
}

// Format time taken (e.g. 08:25)
$time_taken_seconds = $result ? $result['time_taken_seconds'] : 0;
$time_formatted = sprintf('%02d:%02d', floor($time_taken_seconds / 60), $time_taken_seconds % 60);

// Fetch badges for this attempt
$badges = [];
$badges_res = mysqli_query($conn, "SELECT * FROM student_badges WHERE student_id = $student_id AND test_id = $test_id");
if ($badges_res) {
    while ($b = mysqli_fetch_assoc($badges_res)) {
        $badges[] = $b;
    }
}

// Fetch top performer details
$top_performer = null;
$top_query = "SELECT r.score, r.time_taken_seconds, s.id as student_id, u.username, s.name as full_name





              FROM results r
              JOIN students s ON r.student_id = s.id
              JOIN users u ON s.user_id = u.id
              WHERE r.test_id = $test_id
              ORDER BY r.score DESC, r.time_taken_seconds ASC LIMIT 1";
$top_res = mysqli_query($conn, $top_query);
if ($top_res) {
    $top_performer = mysqli_fetch_assoc($top_res);
}

// Subject Analytics
$subject_analytics = [];
$sub_query = "SELECT q.subject_id, sub.name as subject_name,
                     COUNT(sa.id) as total_questions,
                     SUM(CASE WHEN sa.is_correct = 1 THEN 1 ELSE 0 END) as correct_questions,
                     SUM(q.points) as total_points,
                     SUM(CASE WHEN sa.is_correct = 1 THEN sa.points_awarded ELSE 0 END) as scored_points
              FROM student_answers sa
              JOIN questions q ON sa.question_id = q.id
              JOIN subjects sub ON q.subject_id = sub.id
              WHERE sa.attempt_id = $attempt_id
              GROUP BY q.subject_id, sub.name";
$sub_res = mysqli_query($conn, $sub_query);
if ($sub_res) {
    while ($row = mysqli_fetch_assoc($sub_res)) {
        $subject_analytics[] = $row;
    }
}

// Load questions and answers if review allowed
$questions = [];
$options_by_question = [];
if ($attempt['allow_review'] == 1) {
    $q_stmt = $conn->prepare("SELECT q.*, sa.selected_option_ids, sa.text_answer, sa.is_correct, sa.points_awarded, sa.checked_by_admin
                              FROM test_questions tq
                              JOIN questions q ON tq.question_id = q.id
                              LEFT JOIN student_answers sa ON sa.question_id = q.id AND sa.attempt_id = ?
                              WHERE tq.test_id = ?
                              ORDER BY tq.sort_order ASC, q.id ASC");
    $q_stmt->bind_param("ii", $attempt_id, $test_id);
    $q_stmt->execute();
    $q_res = $q_stmt->get_result();
    while ($row = $q_res->fetch_assoc()) {
        $questions[] = $row;
    }
    $q_stmt->close();

    if (!empty($questions)) {
        $q_ids = array_map(function($q) { return $q['id']; }, $questions);
        $q_ids_str = implode(",", $q_ids);
        if (!empty($q_ids_str)) {
            $opt_res = mysqli_query($conn, "SELECT * FROM question_options WHERE question_id IN ($q_ids_str) ORDER BY id ASC");
            if ($opt_res) {
                while ($o = mysqli_fetch_assoc($opt_res)) {
                    $options_by_question[$o['question_id']][] = $o;
                }
            }
        }
    }
}

$pageTitle = "Assessment Results";
include "includes/header.php";
?>

<div style="height: 100px;"></div>

<main class="container py-4">
    <div class="row justify-content-center" data-aos="fade-up">
        <div class="col-lg-8 col-md-10">
            <div class="card border-0 shadow-lg bg-white overflow-hidden rounded-4">
                <div class="card-header bg-gradient-indigo text-white p-4 text-center">
                    <span class="badge bg-light text-primary mb-2 fw-bold text-uppercase">Assessment Feedback</span>
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($attempt['test_title']); ?></h2>
                    <p class="mb-0 text-light opacity-75">Date: <?php echo date('d M Y h:i A', strtotime($attempt['completed_at'] ?: $attempt['started_at'])); ?></p>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($pending_manual_grading): ?>
                        <!-- Grading Pending Notice -->
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history fs-1 text-warning d-block mb-3"></i>
                            <h4 class="fw-bold text-dark">Grading in Progress</h4>
                            <p class="text-muted col-md-10 mx-auto">This assessment includes short/long answer questions. Your final score, ranks, and certificates will be available here once the instructor grades your answers.</p>
                            <div class="mt-4">
                                <a href="test.php" class="btn btn-primary px-4 fw-bold">Back to Dashboard</a>
                            </div>
                        </div>
                    <?php elseif (!$result): ?>
                        <!-- Error State -->
                        <div class="alert alert-danger text-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Results have not been calculated yet. Please contact support.
                        </div>
                    <?php else: ?>
                        <!-- Score Display -->
                        <div class="text-center mb-5">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light border border-4 border-white shadow-sm mb-3" style="width: 130px; height: 130px;">
                                <div class="text-center">
                                    <h1 class="fw-bold mb-0 text-indigo"><?php echo $result['score']; ?></h1>
                                    <small class="text-muted">/ <?php echo $result['total_questions']; ?> Pts</small>
                                </div>
                            </div>
                            
                            <h3 class="fw-bold mb-1 <?php echo $result['is_passed'] ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $result['is_passed'] ? '<i class="bi bi-check-circle-fill me-1"></i> Passed' : '<i class="bi bi-x-circle-fill me-1"></i> Failed'; ?>
                            </h3>
                            <p class="text-muted mb-0">Passing Grade Required: <strong><?php echo $attempt['passing_marks']; ?>%</strong></p>
                        </div>

                        <!-- Statistics Grid -->
                        <div class="row text-center mb-5 g-3">
                            <div class="col-md-3 col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <span class="d-block small text-muted text-uppercase fw-semibold mb-1">Percentage</span>
                                    <h4 class="fw-bold mb-0 text-dark"><?php echo $result['percentage']; ?>%</h4>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <span class="d-block small text-muted text-uppercase fw-semibold mb-1">Rank Position</span>
                                    <h4 class="fw-bold mb-0 text-indigo">#<?php echo $rank; ?></h4>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <span class="d-block small text-muted text-uppercase fw-semibold mb-1">Time Taken</span>
                                    <h4 class="fw-bold mb-0 text-dark"><?php echo $time_formatted; ?></h4>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <span class="d-block small text-muted text-uppercase fw-semibold mb-1">Accuracy</span>
                                    <h4 class="fw-bold mb-0 text-success"><?php echo $result['total_questions'] > 0 ? round(($result['correct_answers'] / $result['total_questions']) * 100, 1) : 0; ?>%</h4>
                                </div>
                            </div>
                        </div>

                        <!-- Question Counters -->
                        <div class="card bg-light border-0 mb-5 rounded-4">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Response Analytics</h6>
                                <div class="row align-items-center">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Correct Answers:</span>
                                            <strong class="text-success"><?php echo $result['correct_answers']; ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Incorrect Answers:</span>
                                            <strong class="text-danger"><?php echo $result['wrong_answers']; ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Skipped Questions:</span>
                                            <strong class="text-muted"><?php echo $result['skipped_questions']; ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Custom visualization using standard bootstrap classes -->
                                        <div class="progress" style="height: 20px;">
                                            <?php 
                                            $corr_pct = $result['total_questions'] > 0 ? ($result['correct_answers'] / $result['total_questions']) * 100 : 0;
                                            $wrong_pct = $result['total_questions'] > 0 ? ($result['wrong_answers'] / $result['total_questions']) * 100 : 0;
                                            $skip_pct = $result['total_questions'] > 0 ? ($result['skipped_questions'] / $result['total_questions']) * 100 : 0;
                                            ?>
                                            <div class="progress-bar bg-success" style="width: <?php echo $corr_pct; ?>%"><?php echo $result['correct_answers']; ?></div>
                                            <div class="progress-bar bg-danger" style="width: <?php echo $wrong_pct; ?>%"><?php echo $result['wrong_answers']; ?></div>
                                            <div class="progress-bar bg-secondary opacity-50" style="width: <?php echo $skip_pct; ?>%"><?php echo $result['skipped_questions']; ?></div>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted mt-2">
                                            <span>Correct (Green)</span>
                                            <span>Incorrect (Red)</span>
                                            <span>Skipped (Gray)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Items: Certificate & Leaderboard -->
                        <div class="d-flex flex-column gap-3">
                            <?php if ($certificate): ?>
                                <a href="certificate.php?code=<?php echo $certificate['verification_code']; ?>" class="btn btn-success btn-lg w-100 fw-bold py-3 rounded-3 shadow-sm">
                                    <i class="bi bi-award me-1"></i> Download PDF Certificate
                                </a>
                            <?php endif; ?>
                            
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <a href="test.php" class="btn btn-outline-primary btn-lg w-100 fw-bold rounded-3">
                                        Back to Dashboard
                                    </a>
                                </div>
                                <div class="col-sm-6">
                                    <a href="leaderboard.php?test_id=<?php echo $test_id; ?>" class="btn btn-outline-indigo btn-lg w-100 fw-bold rounded-3">
                                        View Test Rankings
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($badges)): ?>
                <!-- Earned Badges Section -->
                <div class="card border-0 shadow-lg bg-white rounded-4 mt-4 overflow-hidden">
                    <div class="card-header bg-gradient-success text-white p-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-trophy-fill me-2"></i>Achievements & Badges Unlocked</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <?php foreach ($badges as $b): ?>
                                <?php 
                                $badge_icon = "bi-award";
                                $badge_color = "text-primary bg-primary-subtle";
                                switch ($b['badge_type']) {
                                    case 'top_10':
                                        $badge_icon = "bi-trophy-fill";
                                        $badge_color = "text-warning bg-warning-subtle";
                                        break;
                                    case 'top_50':
                                        $badge_icon = "bi-star-fill";
                                        $badge_color = "text-info bg-info-subtle";
                                        break;
                                    case 'top_100':
                                        $badge_icon = "bi-award-fill";
                                        $badge_color = "text-secondary bg-secondary-subtle";
                                        break;
                                    case 'perfect_score':
                                        $badge_icon = "bi-gem";
                                        $badge_color = "text-danger bg-danger-subtle";
                                        break;
                                    case 'fastest_finisher':
                                        $badge_icon = "bi-lightning-charge-fill";
                                        $badge_color = "text-warning bg-warning-subtle";
                                        break;
                                    case 'highest_subject_physics':
                                    case 'highest_subject_math':
                                        $badge_icon = "bi-mortarboard-fill";
                                        $badge_color = "text-success bg-success-subtle";
                                        break;
                                }
                                ?>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 rounded-3 border bg-light h-100 badge-card transition-all">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 <?php echo $badge_color; ?>" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                            <i class="bi <?php echo $badge_icon; ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($b['badge_name']); ?></h6>
                                            <p class="small text-muted mb-0"><?php echo htmlspecialchars($b['description']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row mt-4 g-4">
                <!-- Comparison Mode -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-lg bg-white rounded-4 h-100 overflow-hidden">
                        <div class="card-header bg-gradient-primary text-white p-3">
                            <h5 class="fw-bold mb-0"><i class="bi bi-people-fill me-2"></i>You vs. Top Performer</h5>
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-center">
                            <?php if ($top_performer): ?>
                                <?php
                                $is_top_user = ($top_performer['student_id'] == $student_id);
                                $top_name = "N/A";
                                if ($attempt['lobby_privacy'] === 'Anonymous') {
                                    $top_name = $is_top_user ? "You" : "Student_" . $top_performer['student_id'];
                                } else {
                                    $top_name = $is_top_user ? "You" : htmlspecialchars($top_performer['full_name'] ?: $top_performer['username']);
                                }
                                $top_time_formatted = sprintf('%02d:%02d', floor($top_performer['time_taken_seconds'] / 60), $top_performer['time_taken_seconds'] % 60);
                                ?>
                                <?php if ($is_top_user): ?>
                                    <div class="text-center py-3">
                                        <div class="mb-3 text-warning" style="font-size: 3rem;">
                                            <i class="bi bi-crown-fill animated-crown"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark">You are the Top Performer!</h5>
                                        <p class="text-muted small mb-0">You achieved the highest score with the best time in this exam. Excellent work!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-borderless align-middle mb-0">
                                            <thead>
                                                <tr class="text-muted small text-uppercase" style="border-bottom: 1px solid #eee;">
                                                    <th>Metric</th>
                                                    <th>You</th>
                                                    <th>Top Performer</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="fw-semibold text-dark"><i class="bi bi-person me-2"></i>Name</td>
                                                    <td class="text-primary fw-bold">You</td>
                                                    <td class="text-dark fw-bold"><?php echo $top_name; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-dark"><i class="bi bi-award me-2"></i>Score</td>
                                                    <td class="text-indigo fw-bold"><?php echo $result['score']; ?></td>
                                                    <td class="text-success fw-bold"><?php echo $top_performer['score']; ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold text-dark"><i class="bi bi-clock me-2"></i>Time Taken</td>
                                                    <td class="text-muted"><?php echo $time_formatted; ?></td>
                                                    <td class="text-success fw-bold"><?php echo $top_time_formatted; ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-center text-muted my-4">No other completion data available for comparison.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Subject-wise breakdown -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-lg bg-white rounded-4 h-100 overflow-hidden">
                        <div class="card-header bg-gradient-dark-blue text-white p-3">
                            <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Subject Analytics</h5>
                        </div>
                        <div class="card-body p-4 d-flex flex-column justify-content-center">
                            <?php if (!empty($subject_analytics)): ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($subject_analytics as $sub_an): ?>
                                        <?php 
                                        $sub_pct = $sub_an['total_points'] > 0 ? round(($sub_an['scored_points'] / $sub_an['total_points']) * 100, 1) : 0;
                                        $bar_color = "bg-primary";
                                        if ($sub_pct >= 80) $bar_color = "bg-success";
                                        elseif ($sub_pct >= 50) $bar_color = "bg-warning";
                                        else $bar_color = "bg-danger";
                                        ?>
                                        <div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-semibold text-dark"><?php echo htmlspecialchars($sub_an['subject_name']); ?></span>
                                                <span class="small text-muted"><?php echo $sub_an['correct_questions']; ?>/<?php echo $sub_an['total_questions']; ?> correct (<?php echo $sub_pct; ?>%)</span>
                                            </div>
                                            <div class="progress rounded-pill" style="height: 10px;">
                                                <div class="progress-bar rounded-pill <?php echo $bar_color; ?>" style="width: <?php echo $sub_pct; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted my-4">No subject performance details available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($attempt['allow_review'] == 1 && !empty($questions)): ?>
                <!-- Question Review Section -->
                <div class="card border-0 shadow-lg bg-white rounded-4 mt-4 overflow-hidden mb-5">
                    <div class="card-header bg-gradient-indigo text-white p-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-patch-question-fill me-2"></i>Detailed Question Review</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-column gap-4">
                            <?php foreach ($questions as $idx => $q): ?>
                                <?php 
                                $is_correct_status = $q['is_correct'] == 1;
                                $is_skipped = is_null($q['is_correct']) || ($q['selected_option_ids'] === null && $q['text_answer'] === null) || ($q['selected_option_ids'] === '' && $q['text_answer'] === '');
                                $card_border = $is_skipped ? 'border-warning' : ($is_correct_status ? 'border-success' : 'border-danger');
                                $badge_class = $is_skipped ? 'bg-warning text-dark' : ($is_correct_status ? 'bg-success' : 'bg-danger');
                                $badge_text = $is_skipped ? 'Skipped' : ($is_correct_status ? 'Correct' : 'Incorrect');
                                ?>
                                <div class="card border-0 border-start border-4 <?php echo $card_border; ?> bg-light shadow-sm rounded-3 overflow-hidden">
                                    <div class="card-body p-4">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <span class="badge <?php echo $badge_class; ?> px-3 py-2 fw-bold rounded-2">
                                                Q<?php echo $idx + 1; ?>: <?php echo $badge_text; ?> (<?php echo $q['points_awarded'] ?? 0; ?>/<?php echo $q['points']; ?> Pts)
                                            </span>
                                            <span class="badge bg-secondary opacity-75"><?php echo str_replace('_', ' ', $q['question_type']); ?></span>
                                        </div>
                                        
                                        <h5 class="fw-bold text-dark mb-4"><?php echo htmlspecialchars($q['question_text']); ?></h5>
                                        
                                        <!-- Display Answers/Options -->
                                        <?php if (in_array($q['question_type'], ['MCQ_SINGLE', 'MCQ_MULTIPLE', 'TRUE_FALSE'])): ?>
                                            <?php 
                                            $opts = $options_by_question[$q['id']] ?? [];
                                            $selected_ids = !empty($q['selected_option_ids']) ? explode(',', $q['selected_option_ids']) : [];
                                            ?>
                                            <div class="row g-2">
                                                <?php foreach ($opts as $opt): ?>
                                                    <?php 
                                                    $is_selected = in_array($opt['id'], $selected_ids);
                                                    $is_correct_opt = $opt['is_correct'] == 1;
                                                    
                                                    $opt_card_class = "border bg-white text-dark";
                                                    $icon_html = "";
                                                    
                                                    if ($is_selected && $is_correct_opt) {
                                                        $opt_card_class = "border-success bg-success-subtle text-success fw-semibold";
                                                        $icon_html = '<i class="bi bi-check-circle-fill me-2"></i>';
                                                    } elseif ($is_selected && !$is_correct_opt) {
                                                        $opt_card_class = "border-danger bg-danger-subtle text-danger fw-semibold";
                                                        $icon_html = '<i class="bi bi-x-circle-fill me-2"></i>';
                                                    } elseif (!$is_selected && $is_correct_opt) {
                                                        $opt_card_class = "border-success bg-success-subtle text-success opacity-75";
                                                        $icon_html = '<i class="bi bi-check-circle me-2"></i>';
                                                    }
                                                    ?>
                                                    <div class="col-12">
                                                        <div class="p-3 rounded-3 <?php echo $opt_card_class; ?> d-flex align-items-center">
                                                            <div class="me-2"><?php echo $icon_html; ?></div>
                                                            <div><?php echo htmlspecialchars($opt['option_text']); ?></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php elseif ($q['question_type'] == 'FILL_BLANK'): ?>
                                            <div class="p-3 rounded-3 border bg-white mb-2">
                                                <div class="small text-muted mb-1">Your Answer:</div>
                                                <div class="fw-bold <?php echo $is_correct_status ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo htmlspecialchars($q['text_answer'] ?: 'None'); ?>
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-3 border bg-success-subtle text-success-emphasis mb-2">
                                                <div class="small text-success mb-1">Correct Answer:</div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($q['correct_text_answer']); ?></div>
                                            </div>
                                        <?php elseif (in_array($q['question_type'], ['SHORT_ANSWER', 'LONG_ANSWER'])): ?>
                                            <div class="p-3 rounded-3 border bg-white mb-2">
                                                <div class="small text-muted mb-1">Your Submitted Answer:</div>
                                                <div class="text-dark whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($q['text_answer'] ?: 'None')); ?></div>
                                            </div>
                                            <?php if (!empty($q['correct_text_answer'])): ?>
                                                <div class="p-3 rounded-3 border bg-light mb-2">
                                                    <div class="small text-muted mb-1">Suggested Reference Answer:</div>
                                                    <div class="text-muted whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($q['correct_text_answer'])); ?></div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($q['checked_by_admin'] == 0): ?>
                                                <div class="alert alert-warning py-2 small mb-0 mt-2">
                                                    <i class="bi bi-clock-history me-1"></i> Waiting for teacher feedback / manual grading.
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- Explanation Block -->
                                        <?php if (!empty($q['explanation'])): ?>
                                            <div class="mt-3 p-3 rounded-3 bg-info-subtle border-start border-4 border-info">
                                                <div class="fw-bold text-info-emphasis mb-1"><i class="bi bi-info-circle-fill me-2"></i>Explanation</div>
                                                <div class="text-info-emphasis small"><?php echo nl2br(htmlspecialchars($q['explanation'])); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<style>
.bg-gradient-indigo {
    background: linear-gradient(135deg, #475bb2, #2f3b75);
}
.bg-gradient-success {
    background: linear-gradient(135deg, #11998e, #38ef7d);
}
.bg-gradient-primary {
    background: linear-gradient(135deg, #00c6ff, #0072ff);
}
.bg-gradient-dark-blue {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
}
.btn-outline-indigo {
    color: #475bb2;
    border-color: #475bb2;
}
.btn-outline-indigo:hover {
    background-color: #475bb2;
    color: #ffffff;
}
.badge-card {
    transition: all 0.2s ease-in-out;
}
.badge-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
@keyframes crown-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
.animated-crown {
    animation: crown-pulse 2s infinite ease-in-out;
}
</style>

<?php
include "includes/footer.php";
?>