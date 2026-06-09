<?php
session_start();
require_once 'forms/db.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

$user_id = $_SESSION["id"];

$user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_result);
if (!$user) {
    header('Location: forms/login.php');
    exit();
}

$student_result = mysqli_query($conn, "SELECT * FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($student_result);

$badges = [];
if ($student) {
    $student_id = $student['id'];
    $badges_res = mysqli_query($conn, "SELECT sb.*, t.title as test_title FROM student_badges sb LEFT JOIN tests t ON sb.test_id = t.id WHERE sb.student_id = $student_id ORDER BY sb.awarded_at DESC");
    if ($badges_res) {
        while ($b = mysqli_fetch_assoc($badges_res)) {
            $badges[] = $b;
        }
    }
}

$total_tests = 0;
$average_score = 0;
$highest_score = 0;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_tests, AVG(score) as avg_score, MAX(score) as max_score FROM test_results WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats_res = $stmt->get_result()->fetch_assoc();
    $total_tests   = intval($stats_res['total_tests']);
    $average_score = $stats_res['avg_score'] ? round(floatval($stats_res['avg_score'])) : 0;
    $highest_score = $stats_res['max_score'] ? round(floatval($stats_res['max_score'])) : 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching user stats: " . $e->getMessage());
}

$pageTitle = "My Profile";
$activePage = "profile";
include "includes/header.php";
?>

<main class="main standalone-page" style="padding-top: 100px; padding-bottom: 80px;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8" data-aos="fade-up">

        <!-- Back Link -->
        <a href="index.php" class="back-button mb-4">
          <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <!-- Profile Header Card -->
        <div class="result-header mb-4" style="text-align: left; padding: 36px 40px;">
          <div class="d-flex align-items-center gap-4 mb-4 pb-4" style="border-bottom: 1px solid var(--border-color);">
            <?php
              $avatar_url = !empty($student['avatar'])
                ? getImagePath($student['avatar'], 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80')
                : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80';
            ?>
            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="avatar"
                 class="rounded-circle"
                 style="width:90px; height:90px; object-fit:cover; border: 3px solid var(--border-color-darker); flex-shrink:0;">
            <div>
              <h2 style="font-size:1.8rem; color:var(--dark-color); margin-bottom: 4px;"><?php echo htmlspecialchars($user['name']); ?></h2>
              <p style="color:var(--text-muted); font-size:0.9rem; margin: 0;">
                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
              </p>
              <p style="color:var(--text-muted); font-size:0.9rem; margin: 4px 0 0;">
                <i class="bi bi-calendar3 me-1"></i>Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
              </p>
            </div>
          </div>

          <!-- Performance Stats -->
          <h4 style="font-size:1.1rem; font-weight:700; color:var(--dark-color); margin-bottom:18px;">Academic Performance</h4>
          <div class="performance-summary" style="justify-content: flex-start; gap: 16px;">
            <div class="performance-item">
              <div class="performance-icon"><i class="bi bi-journal-check"></i></div>
              <div class="performance-value"><?php echo $total_tests; ?></div>
              <div class="performance-label">Tests Taken</div>
            </div>
            <div class="performance-item">
              <div class="performance-icon" style="color: var(--biology-color);"><i class="bi bi-graph-up"></i></div>
              <div class="performance-value" style="color: var(--biology-color);"><?php echo $average_score; ?>%</div>
              <div class="performance-label">Average Score</div>
            </div>
            <div class="performance-item">
              <div class="performance-icon" style="color: var(--maths-color);"><i class="bi bi-trophy"></i></div>
              <div class="performance-value" style="color: var(--maths-color);"><?php echo $highest_score; ?>%</div>
              <div class="performance-label">Best Score</div>
            </div>
          </div>
        </div>

        <!-- Account Details Card -->
        <div class="tracker-card mb-4">
          <h4>Account Details</h4>
          <div class="row g-3">
            <div class="col-sm-6">
              <label style="font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:6px;">Username</label>
              <div style="background:var(--light-color); border:1px solid var(--border-color); border-radius:10px; padding:10px 16px; color:var(--dark-color); font-weight:500;">
                <?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?>
              </div>
            </div>
            <div class="col-sm-6">
              <label style="font-size:0.8rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:6px;">Full Name</label>
              <div style="background:var(--light-color); border:1px solid var(--border-color); border-radius:10px; padding:10px 16px; color:var(--dark-color); font-weight:500;">
                <?php echo htmlspecialchars($user['name']); ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Achievements & Badges Card -->
        <div class="tracker-card mb-4">
          <h4 class="mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>My Achievements & Badges (<?php echo count($badges); ?>)</h4>
          <?php if (empty($badges)): ?>
            <div class="text-center py-4 bg-light rounded-3 text-muted">
              <i class="bi bi-award fs-1 mb-2 d-block text-secondary opacity-50"></i>
              <p class="mb-0 small">No badges earned yet. Complete tests and achieve high ranks to unlock badges!</p>
            </div>
          <?php else: ?>
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
                <div class="col-md-6 col-12">
                  <div class="d-flex align-items-center p-3 rounded-3 border bg-light h-100 badge-item-hover" style="transition: all 0.2s;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 <?php echo $badge_color; ?>" style="width: 45px; height: 45px; font-size: 1.3rem; flex-shrink:0;">
                      <i class="bi <?php echo $badge_icon; ?>"></i>
                    </div>
                    <div>
                      <h6 class="fw-bold mb-0 text-dark" style="font-size:0.95rem;"><?php echo htmlspecialchars($b['badge_name']); ?></h6>
                      <small class="text-muted d-block" style="font-size:0.8rem;"><?php echo htmlspecialchars($b['description']); ?></small>
                      <?php if (!empty($b['test_title'])): ?>
                        <small class="text-primary d-block fw-semibold" style="font-size:0.75rem;"><i class="bi bi-card-checklist me-1"></i><?php echo htmlspecialchars($b['test_title']); ?></small>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <style>
        .badge-item-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            background-color: #f8f9fa !important;
            border-color: #dee2e6 !important;
        }
        </style>

        <!-- Actions -->
        <div class="action-buttons" style="justify-content: flex-start;">
          <a href="my_tests.php" class="btn-action btn-view">
            <i class="bi bi-clock-history"></i> Test History
          </a>
          <a href="kts.php" class="btn-action btn-print">
            <i class="bi bi-bar-chart-line"></i> KTS Dashboard
          </a>
          <a href="forms/logout.php" class="btn-action" style="background: rgba(239,68,68,0.08); color: #dc2626;">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </div>

      </div>
    </div>
  </div>
</main>

<?php include "includes/footer.php"; ?>
