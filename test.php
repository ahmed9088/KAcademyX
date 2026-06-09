<?php
session_start();
require_once 'forms/db.php';

// Auth guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

$user_id = $_SESSION["id"];
$user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_result);
if (!$user) { header('Location: forms/login.php'); exit(); }

// Fetch/create student record
$student_result = mysqli_query($conn, "SELECT * FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($student_result);
if (!$student) {
    $un = mysqli_real_escape_string($conn, $user['username'] ?? '');
    $nm = mysqli_real_escape_string($conn, $user['name']);
    $em = mysqli_real_escape_string($conn, $user['email']);
    mysqli_query($conn, "INSERT INTO students (user_id, username, name, email) VALUES ($user_id, '$un', '$nm', '$em')");
    $student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE user_id = $user_id"));
}
$student_id = $student['id'];

// --- AUTOMATIC NOTIFICATIONS GENERATOR ---
$now_notif = new DateTime("now", new DateTimeZone("Asia/Karachi"));
$now_notif_str = $now_notif->format('Y-m-d H:i:s');

// 1. Upcoming tests within 24 hours
$upcoming_notif_res = mysqli_query($conn, "SELECT * FROM tests 
                                      WHERE timer_mode = 'Fixed' 
                                      AND start_datetime > '$now_notif_str' 
                                      AND start_datetime <= DATE_ADD('$now_notif_str', INTERVAL 1 DAY)");
if ($upcoming_notif_res) {
    while ($ut = mysqli_fetch_assoc($upcoming_notif_res)) {
        $title = "Upcoming Test Reminder";
        $msg = "The scheduled test \"" . mysqli_real_escape_string($conn, $ut['title']) . "\" starts on " . date('d M Y h:i A', strtotime($ut['start_datetime'])) . ".";
        $link = "waiting_room.php?token=" . urlencode($ut['share_token']);
        $chk = mysqli_query($conn, "SELECT id FROM notifications WHERE student_id = $student_id AND title = '$title' AND message = '$msg'");
        if (mysqli_num_rows($chk) == 0) {
            $title_esc = mysqli_real_escape_string($conn, $title);
            $msg_esc = mysqli_real_escape_string($conn, $msg);
            $link_esc = mysqli_real_escape_string($conn, $link);
            mysqli_query($conn, "INSERT INTO notifications (student_id, title, message, link) VALUES ($student_id, '$title_esc', '$msg_esc', '$link_esc')");
        }
    }
}

// 2. Live tests
$live_notif_res = mysqli_query($conn, "SELECT * FROM tests 
                                 WHERE timer_mode = 'Fixed' 
                                 AND start_datetime <= '$now_notif_str' 
                                 AND end_datetime >= '$now_notif_str'");
if ($live_notif_res) {
    while ($lt = mysqli_fetch_assoc($live_notif_res)) {
        $title = "Test Started Notification";
        $msg = "The scheduled test \"" . mysqli_real_escape_string($conn, $lt['title']) . "\" is now Live! You can join and attempt the exam now.";
        $link = "test_details.php?token=" . urlencode($lt['share_token']);
        $chk = mysqli_query($conn, "SELECT id FROM notifications WHERE student_id = $student_id AND title = '$title' AND message = '$msg'");
        if (mysqli_num_rows($chk) == 0) {
            $title_esc = mysqli_real_escape_string($conn, $title);
            $msg_esc = mysqli_real_escape_string($conn, $msg);
            $link_esc = mysqli_real_escape_string($conn, $link);
            mysqli_query($conn, "INSERT INTO notifications (student_id, title, message, link) VALUES ($student_id, '$title_esc', '$msg_esc', '$link_esc')");
        }
    }
}

// 3. Graded/Published results
$graded_notif_res = mysqli_query($conn, "SELECT r.*, t.title as test_title FROM results r 
                                   JOIN tests t ON r.test_id = t.id 
                                   WHERE r.student_id = $student_id");
if ($graded_notif_res) {
    while ($gr = mysqli_fetch_assoc($graded_notif_res)) {
        $title = "Result Published Notification";
        $msg = "Your results for the test \"" . mysqli_real_escape_string($conn, $gr['test_title']) . "\" have been published. Final score: " . $gr['percentage'] . "%.";
        $link = "test_result.php?attempt_id=" . $gr['attempt_id'];
        $chk = mysqli_query($conn, "SELECT id FROM notifications WHERE student_id = $student_id AND title = '$title' AND message = '$msg'");
        if (mysqli_num_rows($chk) == 0) {
            $title_esc = mysqli_real_escape_string($conn, $title);
            $msg_esc = mysqli_real_escape_string($conn, $msg);
            $link_esc = mysqli_real_escape_string($conn, $link);
            mysqli_query($conn, "INSERT INTO notifications (student_id, title, message, link) VALUES ($student_id, '$title_esc', '$msg_esc', '$link_esc')");
        }
    }
}

// Fetch unread notifications
$notifications_res = mysqli_query($conn, "SELECT * FROM notifications WHERE student_id = $student_id AND is_read = 0 ORDER BY created_at DESC");
$unread_notifications = [];
if ($notifications_res) {
    while ($row = mysqli_fetch_assoc($notifications_res)) {
        $unread_notifications[] = $row;
    }
}

// Fetch read notifications (history)
$read_notifications_res = mysqli_query($conn, "SELECT * FROM notifications WHERE student_id = $student_id AND is_read = 1 ORDER BY created_at DESC LIMIT 5");
$read_notifications = [];
if ($read_notifications_res) {
    while ($row = mysqli_fetch_assoc($read_notifications_res)) {
        $read_notifications[] = $row;
    }
}
// ----------------------------------------

// Fetch student stats for analytics
$attempts_res = mysqli_query($conn, "SELECT COUNT(*) FROM student_attempts WHERE student_id = $student_id AND status IN ('Completed', 'Auto Submitted')");
$total_attempts = $attempts_res ? mysqli_fetch_row($attempts_res)[0] : 0;

$avg_res = mysqli_query($conn, "SELECT AVG(percentage), MAX(percentage) FROM results WHERE student_id = $student_id");
$avg_row = mysqli_fetch_row($avg_res);
$avg_score = $avg_row[0] ? round($avg_row[0], 1) : 0;
$best_score = $avg_row[1] ? round($avg_row[1], 1) : 0;

$acc_res = mysqli_query($conn, "SELECT SUM(correct_answers), SUM(total_questions) FROM results WHERE student_id = $student_id");
$acc_row = mysqli_fetch_row($acc_res);
$accuracy = ($acc_row[1] > 0) ? round(($acc_row[0] / $acc_row[1]) * 100, 1) : 0;

// Fetch badges for recent achievements
$badges = [];
$badges_res = mysqli_query($conn, "SELECT sb.*, t.title as test_title FROM student_badges sb LEFT JOIN tests t ON sb.test_id = t.id WHERE sb.student_id = $student_id ORDER BY sb.awarded_at DESC LIMIT 6");
if ($badges_res) {
    while ($b = mysqli_fetch_assoc($badges_res)) {
        $badges[] = $b;
    }
}

// Fetch subject wise scores
$subj_perf = [];
$subj_perf_res = mysqli_query($conn, "SELECT s.name as subject_name, AVG(r.percentage) as avg_score 
                                     FROM results r 
                                     JOIN tests t ON r.test_id = t.id 
                                     JOIN subjects s ON t.subject_id = s.id 
                                     WHERE r.student_id = $student_id 
                                     GROUP BY t.subject_id, s.name");
if ($subj_perf_res) {
    while ($row = mysqli_fetch_assoc($subj_perf_res)) {
        $subj_perf[] = $row;
    }
}

// Fetch all public tests
$tests_query = "SELECT t.*, s.name as subject_name, tc.name as category_name,
               (SELECT COUNT(*) FROM test_questions WHERE test_id = t.id) as question_count,
               (SELECT id FROM student_attempts WHERE test_id = t.id AND student_id = $student_id ORDER BY id DESC LIMIT 1) as last_attempt_id,
               (SELECT status FROM student_attempts WHERE test_id = t.id AND student_id = $student_id ORDER BY id DESC LIMIT 1) as last_attempt_status
               FROM tests t
               JOIN subjects s ON t.subject_id = s.id
               JOIN test_categories tc ON t.category_id = tc.id
               WHERE t.is_public = 1
               ORDER BY t.created_at DESC";
$tests_res = mysqli_query($conn, $tests_query);
$tests = [];
if ($tests_res) {
    while ($row = mysqli_fetch_assoc($tests_res)) {
        $tests[] = $row;
    }
}

$pageTitle = "Online Examination Portal";
$activePage = "tests";
include "includes/header.php";
?>

<!-- Add spacing for fixed header -->
<div style="height: 100px;"></div>

<main class="container py-4">
    <?php if (!empty($unread_notifications)): ?>
        <div class="row mb-4" data-aos="fade-up" id="notificationsArea">
            <div class="col-12">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bell-fill text-warning me-2 animate-bounce"></i>New Notifications</h5>
                <?php foreach ($unread_notifications as $notif): ?>
                    <div class="alert alert-info border-0 shadow-sm d-flex justify-content-between align-items-center py-3 px-4 mb-2 rounded-3" id="notif-alert-<?php echo $notif['id']; ?>">
                        <a href="click_notification.php?id=<?php echo $notif['id']; ?>" class="text-decoration-none flex-grow-1 d-flex flex-column text-start me-3">
                            <strong class="text-dark d-block text-primary-hover"><i class="bi bi-link-45deg me-1"></i><?php echo htmlspecialchars($notif['title']); ?></strong>
                            <span class="small text-muted"><?php echo htmlspecialchars($notif['message']); ?></span>
                        </a>
                        <button type="button" class="btn-close" aria-label="Close" onclick="dismissNotif(<?php echo $notif['id']; ?>)"></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Student Analytics Dashboard Section -->
    <div class="row mb-5" data-aos="fade-up">
        <div class="col-12">
            <h2 class="fw-bold mb-4 text-dark"><i class="bi bi-speedometer2 me-2 text-primary"></i>My Performance Analytics</h2>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 bg-white">
                <h2 class="fw-bold text-primary mb-1"><?php echo $total_attempts; ?></h2>
                <span class="text-muted small text-uppercase fw-bold">Tests Attempted</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 bg-white">
                <h2 class="fw-bold text-success mb-1"><?php echo $avg_score; ?>%</h2>
                <span class="text-muted small text-uppercase fw-bold">Average Score</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 bg-white">
                <h2 class="fw-bold text-info mb-1"><?php echo $best_score; ?>%</h2>
                <span class="text-muted small text-uppercase fw-bold">Best Score</span>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm text-center py-4 bg-white">
                <h2 class="fw-bold text-warning mb-1"><?php echo $accuracy; ?>%</h2>
                <span class="text-muted small text-uppercase fw-bold">Overall Accuracy</span>
            </div>
        </div>

        <?php if(!empty($badges)): ?>
            <div class="col-12 mt-3">
                <div class="card border-0 shadow-sm p-4 bg-white">
                    <h5 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>My Recent Badges</h5>
                    <div class="row g-2">
                        <?php foreach($badges as $b): ?>
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
                            <div class="col-lg-4 col-md-6">
                                <div class="d-flex align-items-center p-3 rounded-3 border bg-light h-100 badge-card-hover" style="transition: all 0.2s;">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 <?php echo $badge_color; ?>" style="width: 40px; height: 40px; font-size: 1.2rem; flex-shrink:0;">
                                        <i class="bi <?php echo $badge_icon; ?>"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark" style="font-size:0.9rem;"><?php echo htmlspecialchars($b['badge_name']); ?></h6>
                                        <small class="text-muted d-block" style="font-size:0.75rem;"><?php echo htmlspecialchars($b['description']); ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 text-end">
                        <a href="profile.php" class="btn btn-sm btn-link text-indigo text-decoration-none fw-semibold p-0"><i class="bi bi-arrow-right-short me-1"></i>View All Achievements & Profile</a>
                    </div>
                </div>
            </div>
            
            <style>
            .badge-card-hover:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.05);
                background-color: #f8f9fa !important;
            }
            .text-indigo {
                color: #475bb2;
            }
            .text-primary-hover {
                transition: color 0.2s ease;
            }
            .text-primary-hover:hover {
                color: #4f46e5 !important;
                text-decoration: underline !important;
            }
            </style>
        <?php endif; ?>
        
        <?php if(!empty($subj_perf)): ?>
            <div class="col-12 mt-3">
                <div class="card border-0 shadow-sm p-4 bg-white">
                    <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Subject-Wise Accuracy</h5>
                    <div class="row">
                        <?php foreach($subj_perf as $sp): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-semibold"><?php echo htmlspecialchars($sp['subject_name']); ?></span>
                                    <span class="text-muted"><?php echo round($sp['avg_score'], 1); ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $sp['avg_score']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Notification History Panel -->
        <div class="col-12 mt-3" data-aos="fade-up">
            <div class="card border-0 shadow-sm p-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-muted me-2"></i>Recent Notifications History</h5>
                    <a href="notifications.php" class="btn btn-sm btn-outline-primary fw-semibold" style="font-size: 0.8rem;">View All</a>
                </div>
                
                <?php if (empty($read_notifications)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bell-slash fs-3 mb-2 d-block text-secondary"></i>
                        <p class="small mb-0">No past notifications found.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($read_notifications as $notif): ?>
                            <div class="list-group-item px-0 py-3 border-0 border-bottom border-light">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">
                                        <?php if (!empty($notif['link'])): ?>
                                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="text-decoration-none text-dark text-primary-hover">
                                                <i class="bi bi-link-45deg me-1"></i><?php echo htmlspecialchars($notif['title']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($notif['title']); ?>
                                        <?php endif; ?>
                                    </h6>
                                    <span class="text-muted" style="font-size: 0.75rem;">
                                        <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?>
                                    </span>
                                </div>
                                <p class="text-secondary small mb-0"><?php echo htmlspecialchars($notif['message']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Private Access Code Card -->
    <div class="row mb-5" data-aos="fade-up">
        <div class="col-lg-6 col-md-12">
            <div class="card border-0 shadow-sm bg-gradient-indigo text-white p-4">
                <h5 class="fw-bold mb-2"><i class="bi bi-shield-lock me-2"></i>Have a Private Test Code?</h5>
                <p class="small text-light opacity-75">Enter the 6-character private test token shared by your instructor to access private exams.</p>
                <form action="test_details.php" method="GET" class="d-flex gap-2">
                    <input type="text" name="token" class="form-control form-control-lg border-0 bg-white text-dark" placeholder="e.g. A7X29B" maxlength="10" required>
                    <button type="submit" class="btn btn-light btn-lg px-4 fw-bold text-primary">Verify Code</button>
                </form>
            </div>
        </div>
        <div class="col-lg-6 col-md-12 d-flex align-items-center justify-content-end">
            <a href="leaderboard.php" class="btn btn-outline-primary btn-lg"><i class="bi bi-trophy me-2"></i>View Global Leaderboards</a>
        </div>
    </div>

    <!-- Available Assessments List -->
    <div class="row" data-aos="fade-up">
        <div class="col-12 mb-3">
            <h3 class="fw-bold text-dark"><i class="bi bi-journal-check me-2 text-primary"></i>Available Assessments & Exams</h3>
        </div>
        
        <?php if (empty($tests)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-journal-x fs-1 text-muted"></i>
                <h4 class="mt-3 text-muted">No Assessments Available</h4>
                <p class="text-muted">There are currently no active public tests on KAcademyX.</p>
            </div>
        <?php else: ?>
            <?php foreach ($tests as $t): ?>
                <?php
                $now = new DateTime("now", new DateTimeZone("Asia/Karachi"));
                $status = "Available";
                $button_text = "View details";
                $button_class = "btn-primary";
                $starts_in_sec = 0;
                $is_disabled = false;
                $info_badge = "";

                if ($t['timer_mode'] == 'Fixed') {
                    $start_time = new DateTime($t['start_datetime'], new DateTimeZone("Asia/Karachi"));
                    $end_time = new DateTime($t['end_datetime'], new DateTimeZone("Asia/Karachi"));
                    
                    if ($now < $start_time) {
                        $status = "Upcoming";
                        $interval = $now->diff($start_time);
                        
                        $days = $interval->d;
                        $hours = $interval->h;
                        $mins = $interval->i;
                        
                        $starts_in_str = "";
                        if ($days > 0) $starts_in_str .= "$days Days ";
                        if ($hours > 0) $starts_in_str .= "$hours Hours ";
                        $starts_in_str .= "$mins Minutes";

                        $info_badge = "<span class='badge bg-warning text-dark mb-2'><i class='bi bi-clock me-1'></i>Starts In: $starts_in_str</span>";
                        $button_text = "Enter Waiting Lobby";
                        $button_class = "btn-primary";
                        $is_disabled = false;
                    } elseif ($now >= $start_time && $now <= $end_time) {
                        $status = "Live Now";
                        $info_badge = "<span class='badge bg-danger mb-2 blink'><i class='bi bi-record-fill me-1'></i>Live Now</span>";
                        $button_text = "Join Test";
                        $button_class = "btn-success";
                    } else {
                        $status = "Completed";
                        $info_badge = "<span class='badge bg-secondary mb-2'>Ended</span>";
                        
                        if ($t['last_attempt_status'] == 'Completed' || $t['last_attempt_status'] == 'Auto Submitted') {
                            $button_text = "View Result";
                            $button_class = "btn-outline-primary";
                        } else {
                            $button_text = "Test Expired";
                            $button_class = "btn-outline-secondary";
                            $is_disabled = true;
                        }
                    }
                } else {
                    // Individual mode
                    $info_badge = "<span class='badge bg-success mb-2'><i class='bi bi-infinity me-1'></i>Practice Mode</span>";
                    if ($t['last_attempt_status'] == 'Completed' || $t['last_attempt_status'] == 'Auto Submitted') {
                        $button_text = "View Result / Retake";
                        $button_class = "btn-outline-primary";
                    } else {
                        $button_text = "Start Test";
                        $button_class = "btn-primary";
                    }
                }
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100 bg-white">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($t['subject_name']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($t['category_name']); ?></span>
                            </div>
                            
                            <h5 class="card-title fw-bold text-dark mb-2"><?php echo htmlspecialchars($t['title']); ?></h5>
                            <p class="card-text small text-muted flex-grow-1"><?php echo htmlspecialchars($t['description']); ?></p>
                            
                            <hr class="my-3 text-muted opacity-25">
                            
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php echo $info_badge; ?>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="bi bi-clock me-1"></i>Duration:</span>
                                    <span class="fw-semibold"><?php echo $t['duration_minutes']; ?> Mins</span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="bi bi-journal-text me-1"></i>Questions:</span>
                                    <span class="fw-semibold"><?php echo $t['question_count']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="bi bi-award me-1"></i>Passing Marks:</span>
                                    <span class="fw-semibold text-danger"><?php echo $t['passing_marks']; ?>%</span>
                                </div>
                            </div>
                            
                            <?php if ($button_text == "View Result"): ?>
                                <a href="test_result.php?attempt_id=<?php echo $t['last_attempt_id']; ?>" class="btn <?php echo $button_class; ?> w-100 fw-bold">
                                    <?php echo $button_text; ?>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $button_text == 'Enter Waiting Lobby' ? 'waiting_room.php?token=' . $t['share_token'] : 'test_details.php?token=' . $t['share_token']; ?>" class="btn <?php echo $button_class; ?> w-100 fw-bold <?php echo $is_disabled ? 'disabled' : ''; ?>" <?php echo $is_disabled ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>
                                    <?php echo $button_text; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<style>
.blink {
    animation: blinker 1.5s linear infinite;
}
@keyframes blinker {
    50% { opacity: 0; }
}
.bg-gradient-indigo {
    background: linear-gradient(135deg, #475bb2, #2f3b75);
}
.animate-bounce {
    animation: bounce 2s infinite;
}
@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
</style>

<script>
function dismissNotif(id) {
    fetch(`mark_notification_read.php?id=${id}`)
        .then(() => {
            const el = document.getElementById(`notif-alert-${id}`);
            if (el) {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    el.remove();
                    const notifsArea = document.getElementById('notificationsArea');
                    if (notifsArea && notifsArea.querySelectorAll('.alert').length === 0) {
                        notifsArea.remove();
                    }
                }, 300);
            }
        });
}
</script>

<?php
include "includes/footer.php";
?>