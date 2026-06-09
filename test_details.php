<?php
session_start();
require_once 'forms/db.php';

// Auth guard for private tests
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (empty($token)) {
    header("Location: test.php");
    exit();
}

// Fetch test details
$stmt = $conn->prepare("SELECT t.*, s.name as subject_name, tc.name as category_name,
                       (SELECT COUNT(*) FROM test_questions WHERE test_id = t.id) as question_count
                       FROM tests t
                       JOIN subjects s ON t.subject_id = s.id
                       JOIN test_categories tc ON t.category_id = tc.id
                       WHERE t.share_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$test = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$test) {
    die("<h3>Assessment Not Found. Please check the URL code and try again.</h3>");
}

// If test is private, require login
if (!$test['is_public']) {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: forms/login.php");
        exit();
    }
}

// If student is logged in, fetch their details to manage attempts
$student_id = 0;
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $user_id = $_SESSION["id"];
    $st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
    if ($st_row = mysqli_fetch_assoc($st_res)) {
        $student_id = $st_row['id'];
    }
}

$now = new DateTime("now", new DateTimeZone("Asia/Karachi"));
$start_time = $test['start_datetime'] ? new DateTime($test['start_datetime'], new DateTimeZone("Asia/Karachi")) : null;
$end_time = $test['end_datetime'] ? new DateTime($test['end_datetime'], new DateTimeZone("Asia/Karachi")) : null;

$is_active = true;
$error_message = "";

// 1. Block repeated attempts if already completed/submitted
if ($student_id > 0) {
    $done_check = mysqli_query($conn, "SELECT id FROM student_attempts WHERE test_id = {$test['id']} AND student_id = $student_id AND status IN ('Completed', 'Auto Submitted')");
    if ($done_check && mysqli_num_rows($done_check) > 0) {
        $done_row = mysqli_fetch_assoc($done_check);
        $is_active = false;
        $error_message = "<strong>You have already attempted and completed this exam.</strong><br><br>
                          <a href='test_result.php?attempt_id=" . $done_row['id'] . "' class='btn btn-primary text-white px-4 fw-bold mt-2'>
                              <i class='bi bi-file-earmark-bar-graph me-1'></i> View My Results
                          </a>";
    }
}

// 2. Time Scheduling checks (redirects to lobby if within 60 minutes of start time)
if ($is_active && $test['timer_mode'] == 'Fixed') {
    if ($now < $start_time) {
        $lobby_open_time = clone $start_time;
        $lobby_open_time->modify('-60 minutes');
        if ($now >= $lobby_open_time) {
            // Lobby is open, redirect to Waiting Lobby
            header("Location: test_lobby.php?token=" . $test['share_token']);
            exit();
        } else {
            $is_active = false;
            $interval = $now->diff($start_time);
            $starts_in_str = $interval->format('%d Days %h Hours %i Minutes');
            $error_message = "This test has not started yet. Waiting lobby will open 60 minutes before start time.<br><strong>Starts In:</strong> $starts_in_str";
        }
    } elseif ($now > $end_time) {
        $is_active = false;
        $error_message = "This Test Has Ended / Expired. No entry allowed.";
    } else {
        // Checking late join cutoff
        if (!$test['late_join_allowed']) {
            $cutoff_limit = clone $start_time;
            $cutoff_limit->modify('+' . $test['late_join_cutoff_minutes'] . ' minutes');
            if ($now > $cutoff_limit) {
                $is_active = false;
                $error_message = "Entry denied: The late-join window for this test closed at " . $cutoff_limit->format('h:i A') . ".";
            }
        }
    }
}

// 3. Handle Auto Start Parameter (used by waiting lobby auto-redirect)
if (isset($_GET['auto_start']) && $_GET['auto_start'] == 1 && $is_active && $student_id > 0) {
    $att_check = mysqli_query($conn, "SELECT id FROM student_attempts WHERE test_id = {$test['id']} AND student_id = $student_id AND status = 'In Progress' ORDER BY id DESC LIMIT 1");
    if ($att_row = mysqli_fetch_assoc($att_check)) {
        header("Location: take_test.php?attempt_id=" . $att_row['id']);
        exit();
    }
    
    // Calculate remaining seconds
    $rem_sec = $test['duration_minutes'] * 60;
    if ($test['timer_mode'] == 'Fixed') {
        $glob_end_timestamp = $end_time->getTimestamp();
        $now_timestamp = $now->getTimestamp();
        $rem_sec = $glob_end_timestamp - $now_timestamp;
        $max_sec = $test['duration_minutes'] * 60;
        if ($rem_sec > $max_sec) $rem_sec = $max_sec;
    }
    
    $now_db = $now->format('Y-m-d H:i:s');
    $ins_query = "INSERT INTO student_attempts (student_id, test_id, started_at, status, remaining_seconds) 
                  VALUES (?, ?, ?, 'In Progress', ?)";
    $stmt = $conn->prepare($ins_query);
    $stmt->bind_param("iisi", $student_id, $test['id'], $now_db, $rem_sec);
    $stmt->execute();
    $attempt_id = $stmt->insert_id;
    $stmt->close();
    
    header("Location: take_test.php?attempt_id=$attempt_id");
    exit();
}

// 4. Handle Start Test Submission
if (isset($_POST['start_test'])) {
    if (!$is_active) {
        die("Error: " . $error_message);
    }
    
    // Require registration/login if they got here as guest
    if ($student_id <= 0) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: forms/login.php");
        exit();
    }
    
    // Check if there is an active (In Progress) attempt
    $att_check = mysqli_query($conn, "SELECT id FROM student_attempts WHERE test_id = {$test['id']} AND student_id = $student_id AND status = 'In Progress' ORDER BY id DESC LIMIT 1");
    if ($att_row = mysqli_fetch_assoc($att_check)) {
        // Redirect to resume
        header("Location: take_test.php?attempt_id=" . $att_row['id']);
        exit();
    }
    
    // Calculate initial remaining seconds
    $rem_sec = $test['duration_minutes'] * 60;
    if ($test['timer_mode'] == 'Fixed') {
        // Ends globally at end_time
        $glob_end_timestamp = $end_time->getTimestamp();
        $now_timestamp = $now->getTimestamp();
        $rem_sec = $glob_end_timestamp - $now_timestamp;
        
        // Cap it at duration
        $max_sec = $test['duration_minutes'] * 60;
        if ($rem_sec > $max_sec) $rem_sec = $max_sec;
    }
    
    // Create new attempt
    $now_db = $now->format('Y-m-d H:i:s');
    $ins_query = "INSERT INTO student_attempts (student_id, test_id, started_at, status, remaining_seconds) 
                  VALUES (?, ?, ?, 'In Progress', ?)";
    $stmt = $conn->prepare($ins_query);
    $stmt->bind_param("iisi", $student_id, $test['id'], $now_db, $rem_sec);
    $stmt->execute();
    $attempt_id = $conn->insert_id;
    $stmt->close();
    
    header("Location: take_test.php?attempt_id=$attempt_id");
    exit();
}

$pageTitle = htmlspecialchars($test['title']) . " - Details";
include "includes/header.php";
?>

<div style="height: 100px;"></div>

<main class="container py-4">
    <div class="row justify-content-center" data-aos="fade-up">
        <div class="col-lg-8 col-md-10">
            <div class="card border-0 shadow-lg bg-white overflow-hidden rounded-4">
                <div class="card-header bg-gradient-indigo text-white p-4 text-center">
                    <span class="badge bg-light text-primary mb-2 fw-bold text-uppercase"><?php echo htmlspecialchars($test['category_name']); ?></span>
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($test['title']); ?></h2>
                    <p class="mb-0 text-light opacity-75">Subject: <?php echo htmlspecialchars($test['subject_name']); ?></p>
                </div>
                
                <div class="card-body p-4">
                    <!-- Test Meta Info Icons Grid -->
                    <div class="row text-center mb-4 g-2">
                        <div class="col-md-4 col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <i class="bi bi-question-circle text-primary fs-3 d-block mb-1"></i>
                                <span class="d-block small text-muted text-uppercase fw-semibold">Questions</span>
                                <h5 class="fw-bold mb-0 text-dark"><?php echo $test['question_count']; ?></h5>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <i class="bi bi-clock-history text-primary fs-3 d-block mb-1"></i>
                                <span class="d-block small text-muted text-uppercase fw-semibold">Duration</span>
                                <h5 class="fw-bold mb-0 text-dark"><?php echo $test['duration_minutes']; ?> Mins</h5>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <i class="bi bi-award text-primary fs-3 d-block mb-1"></i>
                                <span class="d-block small text-muted text-uppercase fw-semibold">Passing Marks</span>
                                <h5 class="fw-bold mb-0 text-danger"><?php echo $test['passing_marks']; ?>%</h5>
                            </div>
                        </div>
                    </div>

                    <!-- Timing and Scheduling detail block -->
                    <div class="card bg-light border-0 mb-4 rounded-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-calendar-event me-2 text-primary"></i>Assessment Timing Details</h6>
                            <ul class="list-unstyled mb-0 small text-muted">
                                <li class="mb-2 d-flex justify-content-between">
                                    <span>Timer Mode:</span>
                                    <strong class="text-dark"><?php echo $test['timer_mode']; ?> Timer</strong>
                                </li>
                                <li class="mb-2 d-flex justify-content-between">
                                    <span>Estimated Completion Time:</span>
                                    <strong class="text-dark"><?php echo $test['duration_minutes']; ?> Minutes</strong>
                                </li>
                                <?php if ($test['timer_mode'] == 'Fixed'): ?>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span>Start Time Window:</span>
                                        <strong class="text-dark"><?php echo date('d M Y h:i A', strtotime($test['start_datetime'])); ?></strong>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span>End Time Window:</span>
                                        <strong class="text-dark"><?php echo date('d M Y h:i A', strtotime($test['end_datetime'])); ?></strong>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="mb-4">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Exam Instructions</h5>
                        <div class="text-muted" style="line-height: 1.6;">
                            <?php if (!empty($test['description'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($test['description'])); ?></p>
                            <?php else: ?>
                                <p>Standard KAcademyX exam rules apply. Please read the following instructions before starting:</p>
                            <?php endif; ?>
                            <ul class="small">
                                <li>Ensure a stable internet connection before launching the test.</li>
                                <li>The timer runs in real-time. Do not close the window or navigate away.</li>
                                <li>All answers are **automatically saved** in real-time. You will not lose progress.</li>
                                <li>The system will **automatically submit** your test when the timer reaches zero.</li>
                                <li>If you attempt to cheat or leave the tab repeatedly, your attempt may be flagged.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Launch form -->
                    <form method="POST">
                        <?php if ($is_active): ?>
                            <button type="submit" name="start_test" class="btn btn-primary btn-lg w-100 fw-bold py-3 rounded-3 shadow-sm">
                                <i class="bi bi-play-fill me-1"></i> Start Assessment
                            </button>
                        <?php else: ?>
                            <div class="alert alert-danger text-center p-3 rounded-3">
                                <i class="bi bi-exclamation-triangle-fill fs-3 d-block mb-2 text-danger"></i>
                                <?php echo $error_message; ?>
                            </div>
                            <button class="btn btn-secondary btn-lg w-100 disabled" disabled>Launch Unavailable</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.bg-gradient-indigo {
    background: linear-gradient(135deg, #475bb2, #2f3b75);
}
</style>

<?php
include "includes/footer.php";
?>
