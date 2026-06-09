<?php
// public_results.php - Public test results & scoreboard portal
session_start();
require_once 'forms/db.php';

// Resolve test either by share_token or test_id
$test = null;
$test_id = 0;

if (isset($_GET['token']) && !empty(trim($_GET['token']))) {
    $token = trim($_GET['token']);
    $stmt = $conn->prepare("SELECT * FROM tests WHERE share_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($test) {
        $test_id = $test['id'];
    }
} elseif (isset($_GET['test_id']) && intval($_GET['test_id']) > 0) {
    $test_id = intval($_GET['test_id']);
    $stmt = $conn->prepare("SELECT * FROM tests WHERE id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$test) {
    $pageTitle = "Results Not Found";
    include "includes/header.php";
    echo '
    <div style="height: 100px;"></div>
    <div class="container py-5 text-center">
        <div class="card border-0 shadow-lg p-5 rounded-4 bg-white max-width-600 mx-auto">
            <i class="bi bi-exclamation-triangle-fill text-danger display-1 mb-4"></i>
            <h2 class="fw-bold text-dark">Results Not Available</h2>
            <p class="text-muted">The test you are looking for does not exist, or the results have not been calculated yet.</p>
            <a href="test.php" class="btn btn-primary btn-lg mt-3 px-5 rounded-pill fw-bold">Back to Tests</a>
        </div>
    </div>';
    include "includes/footer.php";
    exit();
}

// Fetch viewer student ID if logged in
$viewer_student_id = 0;
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $v_user_id = $_SESSION["id"];
    $v_st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $v_user_id");
    if ($v_st_res && $v_student = mysqli_fetch_assoc($v_st_res)) {
        $viewer_student_id = $v_student['id'];
    }
}

// Calculate Statistics
$stats_query = "SELECT COUNT(DISTINCT student_id) as total_participants,
                       MAX(score) as max_score,
                       AVG(score) as avg_score,
                       AVG(percentage) as avg_percentage
                FROM results
                WHERE test_id = $test_id";
$stats_res = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_res);

$total_participants = $stats['total_participants'] ?? 0;
$highest_score = $stats['max_score'] ?? 0;
$avg_score = $stats['avg_score'] ?? 0;
$avg_percentage = $stats['avg_percentage'] ?? 0;

// Fetch Top 100 scoreboard entries
$scoreboard = [];
$score_stmt = $conn->prepare("SELECT r.*, s.id as student_id, s.name as full_name, u.username 

                             FROM results r 
                             JOIN students s ON r.student_id = s.id 
                             JOIN users u ON s.user_id = u.id 
                             WHERE r.test_id = ? 
                             ORDER BY r.score DESC, r.time_taken_seconds ASC 
                             LIMIT 100");
$score_stmt->bind_param("i", $test_id);
$score_stmt->execute();
$score_res = $score_stmt->get_result();
while ($row = $score_res->fetch_assoc()) {
    $scoreboard[] = $row;
}
$score_stmt->close();

$pageTitle = htmlspecialchars($test['title']) . " - Scoreboard";
include "includes/header.php";
?>

<div style="height: 100px;"></div>

<main class="container py-4">
    <!-- Header Hero Card -->
    <div class="card border-0 shadow-lg bg-gradient-header text-white rounded-4 overflow-hidden mb-4" data-aos="fade-up">
        <div class="card-body p-5 text-center">
            <span class="badge bg-light text-primary mb-3 fw-bold text-uppercase px-3 py-2">🏆 Public Leaderboard</span>
            <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($test['title']); ?></h1>
            <p class="lead opacity-75 mb-0">Official results and top performer standings</p>
            
            <?php if (!empty($test['share_token'])): ?>
                <div class="d-inline-flex align-items-center bg-white bg-opacity-10 rounded-pill p-1 px-3 mt-4">
                    <span class="small me-2"><i class="bi bi-link-45deg me-1"></i>Shareable Link:</span>
                    <input type="text" class="bg-transparent border-0 text-white small" style="outline: none; width: 280px;" id="shareUrl" readonly value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/public_results.php?token=" . $test['share_token']; ?>">
                    <button class="btn btn-sm btn-light rounded-pill px-3 fw-bold" onclick="copyShareUrl()"><i class="bi bi-clipboard me-1"></i>Copy</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overall Statistics Grid -->
    <div class="row g-4 mb-4" data-aos="fade-up" data-aos-delay="100">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white rounded-4 text-center p-4 h-100 transition-all card-hover">
                <div class="icon-shape rounded-circle bg-primary-subtle text-primary mx-auto mb-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h6 class="text-muted fw-bold text-uppercase mb-1 small">Total Participants</h6>
                <h2 class="fw-bold text-dark mb-0"><?php echo $total_participants; ?></h2>
                <small class="text-muted">Students who finished</small>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white rounded-4 text-center p-4 h-100 transition-all card-hover">
                <div class="icon-shape rounded-circle bg-success-subtle text-success mx-auto mb-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="bi bi-award-fill"></i>
                </div>
                <h6 class="text-muted fw-bold text-uppercase mb-1 small">Highest Score</h6>
                <h2 class="fw-bold text-success mb-0"><?php echo $highest_score; ?></h2>
                <small class="text-muted">Top marks achieved</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white rounded-4 text-center p-4 h-100 transition-all card-hover">
                <div class="icon-shape rounded-circle bg-info-subtle text-info mx-auto mb-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h6 class="text-muted fw-bold text-uppercase mb-1 small">Class Average</h6>
                <h2 class="fw-bold text-dark mb-0"><?php echo round($avg_score, 1); ?> <span class="fs-5 text-muted">pts</span></h2>
                <small class="text-muted">Success rate: <strong><?php echo round($avg_percentage, 1); ?>%</strong></small>
            </div>
        </div>
    </div>

    <!-- Leaderboard Scoreboard Table -->
    <div class="card border-0 shadow-lg bg-white rounded-4 overflow-hidden mb-5" data-aos="fade-up" data-aos-delay="200">
        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-list-ol me-2 text-primary"></i>Top standings (up to 100)</h5>
            <?php if ($test['lobby_privacy'] === 'Anonymous'): ?>
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill small"><i class="bi bi-eye-slash-fill me-1"></i> Anonymous Privacy Enabled</span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($scoreboard)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x display-3 mb-3 text-secondary d-block"></i>
                    <h5 class="fw-bold">No Records Found</h5>
                    <p class="small">Nobody has completed this exam yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 80px;">Rank</th>
                                <th>Participant</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Time Taken</th>
                                <th class="text-end pe-4" style="width: 150px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($scoreboard as $row): 
                                $is_viewer = ($row['student_id'] == $viewer_student_id);
                                $row_class = $is_viewer ? 'table-primary border-primary' : '';
                                
                                // Privacy formatting
                                $display_name = "";
                                if ($test['lobby_privacy'] === 'Anonymous') {
                                    $display_name = $is_viewer ? "You" : "Student_" . $row['student_id'];
                                } else {
                                    $display_name = $is_viewer ? "You" : htmlspecialchars($row['full_name'] ?: $row['username']);
                                }

                                $time_sec = $row['time_taken_seconds'];
                                $time_formatted = sprintf('%02d:%02d', floor($time_sec / 60), $time_sec % 60);
                                
                                // Rank styles
                                $rank_badge = "";
                                if ($rank == 1) {
                                    $rank_badge = '<span class="badge bg-warning text-dark fw-bold rounded-pill px-3 py-2"><i class="bi bi-trophy-fill me-1"></i>1st</span>';
                                } elseif ($rank == 2) {
                                    $rank_badge = '<span class="badge bg-secondary text-white fw-bold rounded-pill px-3 py-2"><i class="bi bi-award-fill me-1"></i>2nd</span>';
                                } elseif ($rank == 3) {
                                    $rank_badge = '<span class="badge bg-bronze text-white fw-bold rounded-pill px-3 py-2"><i class="bi bi-award me-1"></i>3rd</span>';
                                } else {
                                    $rank_badge = '<span class="fw-bold text-muted ps-2">#' . $rank . '</span>';
                                }
                            ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td class="ps-4"><?php echo $rank_badge; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle rounded-circle me-3 d-flex align-items-center justify-content-center text-white fw-bold" style="width: 35px; height: 35px; background: <?php echo $is_viewer ? '#0d6efd' : '#6c757d'; ?>;">
                                                <?php echo strtoupper(substr($display_name, 0, 1)); ?>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block">
                                                    <?php echo $display_name; ?> 
                                                    <?php if ($is_viewer): ?>
                                                        <span class="badge bg-primary ms-1">You</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong class="text-indigo"><?php echo $row['score']; ?></strong> <span class="small text-muted">/ <?php echo $row['total_questions']; ?></span></td>
                                    <td><strong><?php echo $row['percentage']; ?>%</strong></td>
                                    <td><i class="bi bi-clock me-1 text-muted"></i><?php echo $time_formatted; ?></td>
                                    <td class="text-end pe-4">
                                        <?php if ($row['is_passed']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-2 fw-semibold"><i class="bi bi-check-circle me-1"></i> Passed</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-2 rounded-2 fw-semibold"><i class="bi bi-x-circle me-1"></i> Failed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.bg-gradient-header {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
}
.bg-bronze {
    background-color: #cd7f32;
}
.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
}
.transition-all {
    transition: all 0.3s ease-in-out;
}
.max-width-600 {
    max-width: 600px;
}
.text-indigo {
    color: #475bb2;
}
</style>

<script>
function copyShareUrl() {
    var copyText = document.getElementById("shareUrl");
    copyText.select();
    copyText.setSelectionRange(0, 99999); /* For mobile devices */
    navigator.clipboard.writeText(copyText.value);
    
    // Alert feedback
    alert("Shareable link copied to clipboard!");
}
</script>

<?php
include "includes/footer.php";
?>
