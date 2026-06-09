<?php
session_start();
require_once 'forms/db.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php'); exit();
}

$user_id     = $_SESSION["id"];
$user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user        = mysqli_fetch_assoc($user_result);
if (!$user) { header('Location: forms/login.php'); exit(); }
$_SESSION['user'] = $user['name'];

// Fetch all test results
try {
    $stmt = $conn->prepare("SELECT * FROM test_results WHERE user_id = ? ORDER BY test_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $test_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $test_results = [];
}

// Calculate statistics
$total_tests           = count($test_results);
$total_questions_count = 0;
$total_correct         = 0;
$highest_score         = 0;
$passed_tests          = 0;
$certificates_count    = 0;

foreach ($test_results as $t) {
    $total_questions_count += $t['total_questions'];
    $total_correct         += $t['correct_answers'];
    if ($t['score'] > $highest_score) $highest_score = $t['score'];
    if ($t['is_passed']) {
        $passed_tests++;
        if ($t['certificate_generated']) $certificates_count++;
    }
}

$average_score = $total_questions_count > 0 ? round(($total_correct / $total_questions_count) * 100) : 0;
$pass_rate     = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100) : 0;
$failed_tests  = $total_tests - $passed_tests;

$pageTitle  = "My Test History";
$activePage = "tests";
include "includes/header.php";
?>

<main class="main">

  <!-- Page Title -->
  <div class="page-title" data-aos="fade">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <h1>My Test History</h1>
          <p>Track your academic progress, review past results, and download your certificates.</p>
        </div>
      </div>
    </div>
    <nav class="breadcrumbs">
      <div class="container">
        <ol>
          <li><a href="index.php">Home</a></li>
          <li><a href="test.php">Tests</a></li>
          <li class="current">My History</li>
        </ol>
      </div>
    </nav>
  </div>

  <section class="section">
    <div class="container">

      <!-- ── Stats Strip ── -->
      <div class="myt-stats-strip" data-aos="fade-up">
        <div class="myt-stat-cell">
          <div class="myt-stat-icon" style="background:rgba(79,70,229,0.1);color:#4f46e5;">
            <i class="bi bi-clipboard-data-fill"></i>
          </div>
          <div class="myt-stat-val"><?php echo $total_tests; ?></div>
          <div class="myt-stat-lbl">Total Tests</div>
        </div>
        <div class="myt-stat-cell">
          <div class="myt-stat-icon" style="background:rgba(6,182,212,0.1);color:#06b6d4;">
            <i class="bi bi-percent"></i>
          </div>
          <div class="myt-stat-val"><?php echo $average_score; ?>%</div>
          <div class="myt-stat-lbl">Average Score</div>
        </div>
        <div class="myt-stat-cell">
          <div class="myt-stat-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
            <i class="bi bi-trophy-fill"></i>
          </div>
          <div class="myt-stat-val"><?php echo $highest_score; ?>%</div>
          <div class="myt-stat-lbl">Best Score</div>
        </div>
        <div class="myt-stat-cell">
          <div class="myt-stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="myt-stat-val"><?php echo $pass_rate; ?>%</div>
          <div class="myt-stat-lbl">Pass Rate</div>
        </div>
        <div class="myt-stat-cell">
          <div class="myt-stat-icon" style="background:rgba(251,191,36,0.1);color:#fbbf24;">
            <i class="bi bi-award-fill"></i>
          </div>
          <div class="myt-stat-val"><?php echo $certificates_count; ?></div>
          <div class="myt-stat-lbl">Certificates</div>
        </div>
      </div>

      <!-- ── Results Table ── -->
      <div class="myt-table-wrapper" data-aos="fade-up">
        <div class="myt-table-head">
          <h3><i class="bi bi-table me-2"></i>All Test Results</h3>
          <a href="test.php" class="myt-take-test-btn">
            <i class="bi bi-plus-lg me-1"></i> Take New Test
          </a>
        </div>

        <?php if (empty($test_results)): ?>
        <!-- Empty State -->
        <div class="myt-empty">
          <div class="myt-empty-icon"><i class="bi bi-clipboard-x"></i></div>
          <h3>No Tests Yet</h3>
          <p>You haven't taken any tests yet. Challenge yourself and start your first test now!</p>
          <a href="test.php" class="btn-start-test" style="display:inline-flex;width:auto;padding:12px 32px;">
            <i class="bi bi-pencil-square me-2"></i> Start Your First Test
          </a>
        </div>

        <?php else: ?>
        <div class="myt-table-scroll">
          <table class="myt-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Test Name</th>
                <th>Category</th>
                <th>Date</th>
                <th>Score</th>
                <th>Result</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($test_results as $i => $t):
                $sc = round($t['score'], 1);
                if ($sc >= 80)      $sc_cls = 'sc-high';
                elseif ($sc >= 60)  $sc_cls = 'sc-mid';
                else                $sc_cls = 'sc-low';
                $passed = (bool)$t['is_passed'];
                $cert   = (bool)$t['certificate_generated'];
                $dt     = date('M d, Y', strtotime($t['test_date']));
              ?>
              <tr>
                <td class="myt-num"><?php echo $i + 1; ?></td>
                <td class="myt-name"><?php echo htmlspecialchars($t['test_name']); ?></td>
                <td><span class="myt-cat"><?php echo htmlspecialchars($t['category']); ?></span></td>
                <td class="myt-date"><i class="bi bi-calendar3 me-1"></i><?php echo $dt; ?></td>
                <td>
                  <div class="myt-score-ring <?php echo $sc_cls; ?>">
                    <span><?php echo $sc; ?>%</span>
                  </div>
                </td>
                <td>
                  <span class="myt-result-badge <?php echo $passed ? 'myt-pass' : 'myt-fail'; ?>">
                    <i class="bi <?php echo $passed ? 'bi-check-lg' : 'bi-x-lg'; ?>"></i>
                    <?php echo $passed ? 'Passed' : 'Failed'; ?>
                  </span>
                </td>
                <td>
                  <div class="myt-actions">
                    <?php if ($passed && $cert): ?>
                    <a href="generate_certificate.php?id=<?php echo $t['id']; ?>" class="myt-btn myt-btn-cert" target="_blank" title="Download Certificate">
                      <i class="bi bi-award-fill"></i>
                    </a>
                    <?php endif; ?>
                    <a href="view_test_result.php?id=<?php echo $t['id']; ?>" class="myt-btn myt-btn-view" title="View Details">
                      <i class="bi bi-eye-fill"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── Quick Summary Cards (Pass / Fail) ── -->
      <?php if ($total_tests > 0): ?>
      <div class="row gy-4 mt-2" data-aos="fade-up">
        <div class="col-md-6">
          <div class="myt-mini-card myt-mini-pass">
            <div class="myt-mini-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="myt-mini-body">
              <div class="myt-mini-val"><?php echo $passed_tests; ?> Passed</div>
              <div class="myt-mini-lbl">You passed <?php echo $pass_rate; ?>% of your tests — great work!</div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="myt-mini-card myt-mini-fail">
            <div class="myt-mini-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div class="myt-mini-body">
              <div class="myt-mini-val"><?php echo $failed_tests; ?> Failed</div>
              <div class="myt-mini-lbl">Every failure is a learning opportunity — keep going!</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </section>

</main>

<?php include "includes/footer.php"; ?>