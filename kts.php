<?php
// kts.php
session_start();
require_once 'forms/db.php';

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
$user_id      = $is_logged_in ? $_SESSION["id"] : null;
$student      = null;
$quizzes_completed = [];
$total_quizzes     = 0;
$avg_score         = 0;
$passed_count      = 0;
$has_certificate   = false;

if ($is_logged_in) {
    $student_res = $conn->query("SELECT * FROM students WHERE user_id = $user_id");
    if ($student_res && $student_res->num_rows > 0) {
        $student = $student_res->fetch_assoc();
    }

    $student_id = $student['id'] ?? 0;
    
    // Fetch in-progress videos for "Continue Watching"
    $continue_query = "SELECT wh.*, yv.title, yv.thumbnail_url, yv.category 
                       FROM watch_history wh 
                       JOIN youtube_videos yv ON wh.video_id = yv.video_id 
                       WHERE wh.student_id = $student_id 
                       AND wh.last_position > 0 
                       AND wh.last_position < (wh.duration - 10)
                       ORDER BY wh.updated_at DESC LIMIT 3";
    $continue_res = $conn->query($continue_query);
    $continue_watching = [];
    if ($continue_res && $continue_res->num_rows > 0) {
        while ($row = $continue_res->fetch_assoc()) {
            $continue_watching[] = $row;
        }
    }
    
    // Fetch recent study notes
    $recent_notes_query = "SELECT sn.*, yv.title as video_title 
                           FROM student_notes sn 
                           JOIN youtube_videos yv ON sn.video_id = yv.video_id 
                           WHERE sn.student_id = $student_id 
                           ORDER BY sn.created_at DESC LIMIT 4";
    $recent_notes_res = $conn->query($recent_notes_query);
    $recent_notes = [];
    if ($recent_notes_res && $recent_notes_res->num_rows > 0) {
        while ($row = $recent_notes_res->fetch_assoc()) {
            $recent_notes[] = $row;
        }
    }

    $results_res = $conn->query("SELECT * FROM test_results WHERE user_id = $user_id ORDER BY test_date ASC");
    if ($results_res && $results_res->num_rows > 0) {
        $score_sum = 0;
        while ($row = $results_res->fetch_assoc()) {
            $quizzes_completed[] = $row;
            if ($row['is_passed'] == 1)           $passed_count++;
            if ($row['certificate_generated'] == 1) $has_certificate = true;
            $score_sum += $row['score'];
        }
        $total_quizzes = count($quizzes_completed);
        $avg_score     = $total_quizzes > 0 ? round($score_sum / $total_quizzes, 1) : 0;
    }
}

// Upcoming live support classes
$classes_res = $conn->query("SELECT * FROM kts_classes WHERE class_date >= NOW() ORDER BY class_date ASC LIMIT 3");
$live_classes = [];
if ($classes_res && $classes_res->num_rows > 0) {
    while ($row = $classes_res->fetch_assoc()) $live_classes[] = $row;
}

// Leaderboard — top 5 by highest single score (only_full_group_by compliant)
$leaderboard_res = $conn->query(
    "SELECT ANY_VALUE(tr.student_name) as student_name, MAX(tr.score) as max_score, ANY_VALUE(tr.category) as category, MAX(tr.test_date) as last_date
     FROM test_results tr
     GROUP BY tr.user_id
     ORDER BY max_score DESC
     LIMIT 5"
);
$leaderboard = [];
if ($leaderboard_res && $leaderboard_res->num_rows > 0) {
    while ($row = $leaderboard_res->fetch_assoc()) $leaderboard[] = $row;
}

$pageTitle = "KTS — KAcademyX Tracking System";
$activePage = "kts";
include "includes/header.php";
?>

<main class="main">

  <!-- ============================================================
       KTS HERO SECTION
       ============================================================ -->
  <section class="kts-hero">
    <div class="kts-hero-deco kts-deco-1"></div>
    <div class="kts-hero-deco kts-deco-2"></div>
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8" data-aos="fade-up">
          <span class="kts-pill-badge">KAcademyX Tracking System</span>
          <h1 class="kts-hero-title">Learn. Track.<br>Improve. Achieve.</h1>
          <p class="kts-hero-sub">KTS is a structured academic support and performance-tracking initiative designed to help students learn consistently, monitor their progress, identify weak areas, and achieve academic excellence through regular assessments, guided learning, and personalized support.</p>
          <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
            <?php if (!$is_logged_in): ?>
              <a href="forms/login.php" class="btn-primary-modern" style="padding: 14px 36px; font-size: 1rem;">Get Started with KTS</a>
              <a href="#features" class="btn-outline-white" style="font-size: 1rem;">How It Works</a>
            <?php else: ?>
              <a href="#dashboard" class="btn-primary-modern" style="padding: 14px 36px; font-size: 1rem;"><i class="bi bi-speedometer2 me-2"></i>My KTS Dashboard</a>
              <a href="test.php" class="btn-outline-white" style="font-size: 1rem;"><i class="bi bi-pencil-square me-2"></i>Take a Quiz</a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Quick stats row -->
      <div class="row gy-3 mt-5 justify-content-center" data-aos="fade-up" data-aos-delay="200">
        <?php
        $hero_stats = [
          ["bi-file-earmark-check", "Weekly Quizzes", "Every Week"],
          ["bi-trophy-fill",        "Certificates",   "On Passing"],
          ["bi-people-fill",        "Active Students", "& Growing"],
          ["bi-camera-video-fill",  "Live Classes",   "Support Sessions"],
        ];
        foreach ($hero_stats as $hs):
        ?>
        <div class="col-6 col-md-3">
          <div class="kts-hero-stat">
            <i class="bi <?php echo $hs[0]; ?>"></i>
            <div class="kts-hero-stat-label"><?php echo $hs[1]; ?></div>
            <div class="kts-hero-stat-sub"><?php echo $hs[2]; ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============================================================
       KTS FEATURES SECTION
       ============================================================ -->
  <section id="features" class="section" style="background: var(--light-color);">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>KTS Core Initiatives</h2>
        <p>A holistic 360° approach to student tracking, support, and appreciation</p>
      </div>

      <div class="row gy-4">
        <?php
        $initiatives = [
          ["bi-file-earmark-check-fill","#ef4444","rgba(239,68,68,0.08)",   "Weekly Topic Quizzes",    "Topic-based weekly quizzes and tests assess conceptual understanding and strengthen exam preparation.",   ["Regular Assessments","Concept Strengthening","Exam Preparedness"]],
          ["bi-whatsapp",              "#25d366","rgba(37,211,102,0.08)",  "Lectures via WhatsApp",   "Topic-wise video lectures shared through the official KAcademyX WhatsApp Group for on-demand learning.",   ["Learn at Own Pace","Instant Access","Continuous Revision"]],
          ["bi-columns-gap",           "#3b82f6","rgba(59,130,246,0.08)",  "Progress Dashboard",      "Track your learning journey through a dedicated dashboard — consistency, milestones, and achievements.",   ["Visual Performance","Gamified Achievements","Personal Milestones"]],
          ["bi-laptop-fill",           "#f59e0b","rgba(245,158,11,0.08)",  "Online Support Classes",  "Live classes arranged for difficult topics, ensuring focused guidance and continuous improvement.",         ["Focused Support","Clear Doubts","Expert Interaction"]],
          ["bi-trophy-fill",           "#8b5cf6","rgba(139,92,246,0.08)", "Recognition & Rewards",   "Outstanding students earn official KTS certificates, rewards, and public recognition.",                    ["Digital Certificates","Student Leaderboard","Gifts & Rewards"]],
        ];
        $delays = [100, 200, 300, 150, 250];
        foreach ($initiatives as $i => $init):
        ?>
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delays[$i]; ?>">
          <div class="kts-feature-card">
            <div class="kts-feature-icon" style="background:<?php echo $init[2]; ?>; color:<?php echo $init[1]; ?>;">
              <i class="bi <?php echo $init[0]; ?>"></i>
            </div>
            <h3><?php echo $init[3]; ?></h3>
            <p><?php echo $init[4]; ?></p>
            <ul class="kts-check-list">
              <?php foreach ($init[5] as $point): ?>
              <li><i class="bi bi-check-circle-fill"></i> <?php echo $point; ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Last card spans 2 cols on lg -->
        <!-- already rendered above; this is a CTA hint card -->
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
          <div class="kts-feature-card kts-cta-card">
            <div class="kts-feature-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color:#fff;">
              <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h3>Ready to Join KTS?</h3>
            <p>Join hundreds of students already tracking their progress, earning badges, and achieving excellence through KTS.</p>
            <?php if ($is_logged_in): ?>
              <a href="#dashboard" class="btn-primary-modern" style="display:block; text-align:center; margin-top:auto; padding:12px 24px;">Go to Dashboard</a>
            <?php else: ?>
              <a href="forms/login.php" class="btn-primary-modern" style="display:block; text-align:center; margin-top:auto; padding:12px 24px;">Start Now — It's Free</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       KTS STUDENT DASHBOARD
       ============================================================ -->
  <section id="dashboard" class="section">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>My KTS Dashboard</h2>
        <p>Monitor your consistency, complete quizzes, unlock achievements, and access live classes</p>
      </div>

      <?php if (!$is_logged_in): ?>
      <!-- Guest Lock Screen -->
      <div class="kts-lock-screen" data-aos="fade-up">
        <div class="kts-lock-icon"><i class="bi bi-lock-fill"></i></div>
        <h3>Personalized Tracking Locked</h3>
        <p>To view your quiz consistency, unlock progress milestones, and join live support classes, please sign in.</p>
        <a href="forms/login.php" class="btn-primary-modern" style="padding:14px 40px; font-size:1rem;">Login / Sign Up Now</a>
        <!-- Blurred mockup below -->
        <div class="kts-lock-mockup" aria-hidden="true">
          <div class="kts-mock-header">
            <div class="kts-mock-avatar"></div>
            <div>
              <div class="kts-mock-line" style="width:140px;"></div>
              <div class="kts-mock-line" style="width:90px; height:10px; margin-top:6px; opacity:0.5;"></div>
            </div>
          </div>
          <div class="row g-3 mt-3">
            <?php for($m=0;$m<3;$m++): ?>
            <div class="col-4"><div class="kts-mock-card"></div></div>
            <?php endfor; ?>
          </div>
          <div class="kts-mock-line mt-3" style="width:100%; height:80px; border-radius:16px;"></div>
          <div class="kts-mock-line mt-2" style="width:100%; height:60px; border-radius:16px;"></div>
        </div>
      </div>

      <?php else: ?>
      <!-- ── LOGGED IN: Full Dashboard ── -->
      <div class="kts-dashboard" data-aos="fade-up">

        <!-- Dashboard Header -->
        <div class="kts-dash-header">
          <div class="kts-dash-avatar">
            <?php echo strtoupper(substr($student['name'] ?? 'S', 0, 1)); ?>
          </div>
          <div>
            <h3 class="kts-dash-name">Hello, <?php echo htmlspecialchars($student['name'] ?? 'Student'); ?> 👋</h3>
            <p class="kts-dash-meta">Student ID: #<?php echo $student['id'] ?? 'KTS-XXXX'; ?> &nbsp;·&nbsp; KTS Portal Active</p>
          </div>
          <span class="kts-enrolled-badge"><i class="bi bi-mortarboard me-1"></i>KTS Enrolled</span>
        </div>

        <!-- ── STAT STRIP ── -->
        <div class="kts-stat-strip">
          <?php
          $strips = [
            ["bi-journal-check",   $total_quizzes,  "Quizzes Taken",  "var(--primary-color)"],
            ["bi-check-circle",    $passed_count,   "Quizzes Passed", "#10b981"],
            ["bi-graph-up",        $avg_score."%",  "Avg Score",      "#f59e0b"],
            ["bi-patch-check",     $has_certificate ? "✓" : "—", "Certificate", $has_certificate ? "#10b981" : "#94a3b8"],
          ];
          foreach ($strips as $s):
          ?>
          <div class="kts-stat-cell">
            <div class="kts-stat-num" style="color:<?php echo $s[3]; ?>"><?php echo $s[1]; ?></div>
            <div class="kts-stat-lbl"><?php echo $s[2]; ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Continue Watching Panel -->
        <?php if (!empty($continue_watching)): ?>
        <div class="kts-card mb-4 mt-3">
          <div class="kts-card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-play-circle-fill text-primary"></i>
              <h4 class="mb-0">Continue Watching</h4>
            </div>
            <a href="lectures.php" class="text-primary fw-semibold small text-decoration-none">View All Lectures →</a>
          </div>
          <div class="row g-3 p-3">
            <?php foreach ($continue_watching as $cw): 
              $percent = min(100, round(($cw['last_position'] / $cw['duration']) * 100));
              $ts_display = sprintf('%02d:%02d', floor($cw['last_position'] / 60), $cw['last_position'] % 60);
              $dur_display = sprintf('%02d:%02d', floor($cw['duration'] / 60), $cw['duration'] % 60);
            ?>
            <div class="col-md-4">
              <div class="card h-100 border border-light-subtle rounded-3 overflow-hidden bg-light-subtle shadow-sm transition-all" style="transition: all 0.2s;">
                <div class="position-relative text-start">
                  <img src="<?php echo htmlspecialchars($cw['thumbnail_url']); ?>" class="card-img-top" alt="Lecture Thumbnail" style="height: 140px; object-fit: cover;">
                  <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white py-1 px-2 small d-flex justify-content-between">
                    <span>Progress: <?php echo $percent; ?>%</span>
                    <span><?php echo $ts_display; ?> / <?php echo $dur_display; ?></span>
                  </div>
                </div>
                <div class="card-body p-3 d-flex flex-column text-start">
                  <span class="badge bg-secondary-subtle text-secondary align-self-start mb-1" style="font-size: 0.65rem;"><?php echo htmlspecialchars($cw['category']); ?></span>
                  <h6 class="card-title fw-bold text-dark text-truncate small mb-2"><?php echo htmlspecialchars($cw['title']); ?></h6>
                  <a href="watch.php?video_id=<?php echo urlencode($cw['video_id']); ?>" class="btn btn-sm btn-primary mt-auto fw-bold w-100" style="font-size: 0.8rem;">
                    <i class="bi bi-play-fill me-1"></i> Resume Lecture
                  </a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- ── MAIN GRID ── -->
        <div class="kts-dash-grid">

          <!-- LEFT COLUMN -->
          <div class="kts-dash-col">

            <!-- 4-Week Consistency Timeline -->
            <div class="kts-card">
              <div class="kts-card-header">
                <i class="bi bi-calendar-week"></i>
                <h4>4-Week Quiz Consistency</h4>
              </div>
              <div class="kts-weeks-row">
                <?php for ($w = 1; $w <= 4; $w++):
                  $done  = isset($quizzes_completed[$w - 1]);
                  $next  = ($w === $total_quizzes + 1);
                  $q_data = $done ? $quizzes_completed[$w - 1] : null;
                  $wclass = $done ? 'kts-week-done' : ($next ? 'kts-week-next' : 'kts-week-locked');
                ?>
                <div class="kts-week-node <?php echo $wclass; ?>">
                  <div class="kts-week-circle">
                    <?php if ($done): ?><i class="bi bi-check-lg"></i>
                    <?php elseif ($next): ?><i class="bi bi-pencil"></i>
                    <?php else: ?><i class="bi bi-lock"></i><?php endif; ?>
                  </div>
                  <div class="kts-week-label">Week <?php echo $w; ?></div>
                  <div class="kts-week-meta">
                    <?php if ($done): ?>
                      <span style="color:#10b981; font-weight:700;"><?php echo $q_data['score']; ?>%</span><br>
                      <small><?php echo htmlspecialchars($q_data['category']); ?></small>
                    <?php elseif ($next): ?>
                      <a href="test.php" style="color:var(--primary-color); font-weight:600; font-size:0.8rem;">Take Quiz →</a>
                    <?php else: ?>
                      <span style="color:var(--text-muted); font-size:0.8rem;">Locked</span>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endfor; ?>
              </div>
            </div>

            <!-- Badges -->
            <div class="kts-card">
              <div class="kts-card-header">
                <i class="bi bi-shield-fill-check" style="color:#10b981;"></i>
                <h4>My Badges & Milestones</h4>
              </div>
              <?php
              $badges = [
                ["🔥", "Consistent Learner",   "Complete 3+ weekly quizzes",         $total_quizzes >= 3,               "$total_quizzes/3 quizzes done"],
                ["⭐", "KTS Academic Star",     "Maintain 80%+ average quiz score",   ($total_quizzes > 0 && $avg_score >= 80), "{$avg_score}% average"],
                ["🎓", "Certified Achiever",    "Pass an exam & generate certificate", $has_certificate,                 $has_certificate ? "Unlocked!" : "Pass an exam to unlock"],
              ];
              foreach ($badges as $b):
                $unlocked = $b[3];
              ?>
              <div class="kts-badge-row <?php echo $unlocked ? 'kts-badge-unlocked' : ''; ?>">
                <div class="kts-badge-emoji"><?php echo $b[0]; ?></div>
                <div class="kts-badge-info">
                  <h5><?php echo $b[1]; ?></h5>
                  <p><?php echo $b[2]; ?></p>
                  <span class="kts-badge-status <?php echo $unlocked ? 'status-done' : 'status-locked'; ?>">
                    <?php echo $unlocked ? '<i class="bi bi-check-circle-fill"></i> Unlocked' : '<i class="bi bi-lock"></i> ' . $b[4]; ?>
                  </span>
                </div>
              </div>
              <?php endforeach; ?>
            </div>

          </div><!-- end left col -->

          <!-- RIGHT COLUMN -->
          <div class="kts-dash-col">

            <!-- Score Ring -->
            <div class="kts-card kts-score-ring-card">
              <div class="kts-card-header">
                <i class="bi bi-bar-chart-fill"></i>
                <h4>Performance Overview</h4>
              </div>
              <div class="kts-ring-wrap">
                <!-- SVG donut ring -->
                <?php
                $pct = $total_quizzes > 0 ? min(100, round(($passed_count / $total_quizzes) * 100)) : 0;
                $circumference = 2 * M_PI * 54; // r=54
                $dash = ($pct / 100) * $circumference;
                ?>
                <svg class="kts-svg-ring" viewBox="0 0 120 120">
                  <circle cx="60" cy="60" r="54" fill="none" stroke="var(--border-color)" stroke-width="10"/>
                  <circle cx="60" cy="60" r="54" fill="none"
                    stroke="var(--primary-color)" stroke-width="10"
                    stroke-linecap="round"
                    stroke-dasharray="<?php echo round($dash,1); ?> <?php echo round($circumference,1); ?>"
                    transform="rotate(-90 60 60)"/>
                  <text x="60" y="56" text-anchor="middle" class="kts-ring-pct" font-size="18" fill="var(--dark-color)" font-weight="800" font-family="Montserrat"><?php echo $pct; ?>%</text>
                  <text x="60" y="72" text-anchor="middle" class="kts-ring-lbl" font-size="8" fill="var(--text-muted)" font-family="Poppins">Pass Rate</text>
                </svg>
                <div class="kts-ring-detail">
                  <p><?php echo $passed_count; ?> / <?php echo $total_quizzes; ?> quizzes passed</p>
                  <p>Average score: <strong style="color:var(--primary-color);"><?php echo $avg_score; ?>%</strong></p>
                  <?php if ($total_quizzes > 0): ?>
                  <a href="my_tests.php" class="btn-outline-modern" style="padding: 8px 20px; font-size: 0.85rem; margin-top: 8px; display:inline-block;">View Full History</a>
                  <?php else: ?>
                  <a href="test.php" class="btn-primary-modern" style="padding: 8px 20px; font-size: 0.85rem; margin-top: 8px; display:inline-block;">Take First Quiz</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Upcoming Live Classes -->
            <div class="kts-card">
              <div class="kts-card-header">
                <i class="bi bi-calendar-event" style="color:#ef4444;"></i>
                <h4>Upcoming Support Classes</h4>
              </div>
              <?php if (count($live_classes) > 0): ?>
                <?php foreach ($live_classes as $cls):
                  $sub_lower = strtolower($cls['subject']);
                  $subj_color = '#4f46e5';
                  if ($sub_lower === 'physics')          $subj_color = '#ef4444';
                  elseif ($sub_lower === 'computer science') $subj_color = '#3b82f6';
                  elseif ($sub_lower === 'biology')      $subj_color = '#10b981';
                  elseif ($sub_lower === 'mathematics')  $subj_color = '#f59e0b';
                ?>
                <div class="kts-class-card">
                  <span class="kts-class-badge" style="background: rgba(0,0,0,0.05); color:<?php echo $subj_color; ?>; border: 1px solid <?php echo $subj_color; ?>20;">
                    <?php echo htmlspecialchars($cls['subject']); ?>
                  </span>
                  <div class="kts-class-topic"><?php echo htmlspecialchars($cls['topic']); ?></div>
                  <div class="kts-class-date">
                    <i class="bi bi-clock"></i>
                    <?php echo date('D, M j \a\t g:i A', strtotime($cls['class_date'])); ?>
                  </div>
                  <a href="<?php echo htmlspecialchars($cls['join_url']); ?>" class="kts-join-btn" target="_blank">
                    <i class="bi bi-camera-video-fill"></i> Join Zoom
                  </a>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-center py-4">
                  <i class="bi bi-calendar-x" style="font-size:2rem; color:var(--text-muted); display:block; margin-bottom:10px;"></i>
                  <p style="color:var(--text-muted); font-size:0.9rem;">No classes scheduled right now.<br>Check back soon.</p>
                </div>
              <?php endif; ?>
            </div>

            <!-- Recent Study Notes -->
            <div class="kts-card">
              <div class="kts-card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-journal-bookmark-fill text-warning"></i>
                  <h4 class="mb-0">My Recent Notes</h4>
                </div>
              </div>
              <div class="p-3">
                <?php if (empty($recent_notes)): ?>
                  <div class="text-center py-4 text-muted">
                    <i class="bi bi-journal-x fs-2 text-secondary mb-2 d-block"></i>
                    <p class="small mb-0">No study notes taken yet.</p>
                  </div>
                <?php else: ?>
                  <div class="list-group list-group-flush text-start">
                    <?php foreach ($recent_notes as $rn): 
                      $ts = intval($rn['timestamp_seconds']);
                      $ts_formatted = sprintf('%02d:%02d', floor($ts / 60), $ts % 60);
                    ?>
                      <div class="list-group-item px-0 py-3 border-0 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                          <strong class="text-dark text-truncate small d-block" style="max-width: 180px;">
                            <?php echo htmlspecialchars($rn['video_title']); ?>
                          </strong>
                          <a href="watch.php?video_id=<?php echo urlencode($rn['video_id']); ?>&t=<?php echo $ts; ?>" class="btn btn-sm btn-outline-primary fw-bold px-2 py-0" style="font-size: 0.7rem;">
                            <i class="bi bi-play-fill"></i><?php echo $ts_formatted; ?>
                          </a>
                        </div>
                        <p class="text-secondary small mb-1 note-content-text" style="white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($rn['note_text']); ?></p>
                        <small class="text-muted" style="font-size: 0.65rem;"><?php echo date('d M Y, h:i A', strtotime($rn['created_at'])); ?></small>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>

          </div><!-- end right col -->
        </div><!-- end kts-dash-grid -->
      </div><!-- end kts-dashboard -->
      <?php endif; ?>

    </div>
  </section>

  <!-- ============================================================
       JOIN CHANNELS CTA
       ============================================================ -->
  <section class="section" style="background: var(--light-color);">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>Stay Connected</h2>
        <p>Join our community channels for lectures, updates, and recognition</p>
      </div>
      <div class="row gy-4">
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
          <div class="kts-channel-card kts-whatsapp">
            <div class="kts-channel-icon"><i class="bi bi-whatsapp"></i></div>
            <div>
              <h3>KAcademyX WhatsApp Group</h3>
              <p>Get instant access to weekly video lectures, test reminders, worksheets, and study notes. Learn at your own pace inside our student community.</p>
              <a href="https://chat.whatsapp.com/mock-kts-group-invite" class="kts-channel-btn" target="_blank">
                <i class="bi bi-chat-text-fill me-2"></i>Join WhatsApp Group
              </a>
            </div>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
          <div class="kts-channel-card kts-instagram">
            <div class="kts-channel-icon"><i class="bi bi-instagram"></i></div>
            <div>
              <h3>KTS Progress Spotlights</h3>
              <p>We showcase top student consistency, certificates, and achievements on our official social media. Keep learning and get featured!</p>
              <a href="https://instagram.com/kts_kacademyx" class="kts-channel-btn" target="_blank">
                <i class="bi bi-instagram me-2"></i>Follow on Instagram
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       LEADERBOARD / HALL OF FAME
       ============================================================ -->
  <section class="section">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>KTS Hall of Fame</h2>
        <p>Celebrating our top performers and consistent achievers on KAcademyX</p>
      </div>

      <div class="row justify-content-center" data-aos="fade-up">
        <div class="col-lg-7">
          <div class="kts-leaderboard">
            <div class="kts-leaderboard-head">
              <i class="bi bi-award-fill" style="color:#f59e0b;"></i>
              <span>Current KTS Leaderboard</span>
            </div>

            <?php if (count($leaderboard) > 0): ?>
              <?php foreach ($leaderboard as $idx => $row):
                $rank = $idx + 1;
                $rank_colors  = ['#f59e0b','#94a3b8','#ea580c'];
                $rank_bgs     = ['rgba(245,158,11,0.1)','rgba(148,163,184,0.1)','rgba(234,88,12,0.1)'];
                $rank_color   = $rank <= 3 ? $rank_colors[$rank-1] : 'var(--text-muted)';
                $rank_bg      = $rank <= 3 ? $rank_bgs[$rank-1]   : 'rgba(148,163,184,0.06)';
                $medals = ['🥇','🥈','🥉'];
              ?>
              <div class="kts-lb-row" style="<?php echo $rank === 1 ? 'border-left: 3px solid #f59e0b;' : ''; ?>">
                <div class="kts-lb-rank" style="background:<?php echo $rank_bg; ?>; color:<?php echo $rank_color; ?>;">
                  <?php echo $rank <= 3 ? $medals[$rank-1] : $rank; ?>
                </div>
                <div class="kts-lb-info">
                  <strong><?php echo htmlspecialchars($row['student_name']); ?></strong>
                  <span>Best in: <?php echo htmlspecialchars($row['category']); ?></span>
                </div>
                <div class="kts-lb-score" style="color:<?php echo $rank_color; ?>;">
                  <?php echo $row['max_score']; ?>%
                  <span>Top Score</span>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center py-5">
                <i class="bi bi-trophy" style="font-size:3rem; color:var(--text-muted); display:block; margin-bottom:12px;"></i>
                <p style="color:var(--text-muted);">No scores yet. Be the first to appear on the leaderboard!</p>
                <a href="test.php" class="btn-primary-modern" style="margin-top:12px; display:inline-block; padding:10px 28px;">Take a Quiz Now</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php include "includes/footer.php"; ?>
