<?php
session_start();
require_once 'forms/db.php';

$token = $_GET['token'] ?? '';

// Try to auto-populate student name if logged in
if (!isset($_SESSION['student_name'])) {
    if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
        $user_id = $_SESSION["id"];
        $st_res = $conn->query("SELECT name FROM students WHERE user_id = " . intval($user_id));
        if ($st_row = $st_res->fetch_assoc()) {
            $_SESSION['student_name'] = $st_row['name'];
        }
    }
}

if (!isset($_SESSION['student_name'])) {
    header('Location: start_test.php' . ($token ? '?token=' . urlencode($token) : ''));
    exit();
}

if (!$token) {
    header('Location: test.php');
    exit();
}

// Clean up old waiting students
$conn->query("DELETE FROM waiting_students WHERE joined_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

// Find test by share token
$test_stmt = $conn->prepare("SELECT id, title, start_datetime, lobby_privacy FROM tests WHERE share_token = ?");
$test_stmt->bind_param("s", $token);
$test_stmt->execute();
$test_row = $test_stmt->get_result()->fetch_assoc();
$test_stmt->close();

if (!$test_row) {
    header('Location: test.php');
    exit();
}

$test_id = $test_row['id'];
$test_title = $test_row['title'];
$lobby_privacy = $test_row['lobby_privacy'] ?? 'Public';

// Verify if the student has already completed this test to block access
$student_id = 0;
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $user_id = $_SESSION["id"];
    $st_res = $conn->query("SELECT id FROM students WHERE user_id = " . intval($user_id));
    if ($st_row = $st_res->fetch_assoc()) {
        $student_id = $st_row['id'];
    }
} else {
    $st_res = $conn->prepare("SELECT id FROM students WHERE name = ?");
    $st_res->bind_param("s", $_SESSION['student_name']);
    $st_res->execute();
    if ($st_row = $st_res->get_result()->fetch_assoc()) {
         $student_id = $st_row['id'];
    }
    $st_res->close();
}

if ($student_id > 0) {
    $done_check = $conn->prepare("SELECT id FROM student_attempts WHERE test_id = ? AND student_id = ? AND status IN ('Completed', 'Auto Submitted')");
    $done_check->bind_param("ii", $test_id, $student_id);
    $done_check->execute();
    $already_done = $done_check->get_result()->num_rows > 0;
    $done_check->close();
    
    if ($already_done) {
        header('Location: test_details.php?token=' . urlencode($token));
        exit();
    }
}

// Insert/update student in waiting list for this test
$session_id = session_id();
$student_name = $_SESSION['student_name'];
$insert_stmt = $conn->prepare("INSERT INTO waiting_students (test_id, name, session_id, joined_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE joined_at = NOW(), test_id = VALUES(test_id), name = VALUES(name)");
$insert_stmt->bind_param("iss", $test_id, $student_name, $session_id);
$insert_stmt->execute();
$insert_stmt->close();

// Compute countdown based on actual start_datetime
$now = new DateTime("now", new DateTimeZone("Asia/Karachi"));
$start_time = new DateTime($test_row['start_datetime'], new DateTimeZone("Asia/Karachi"));

if ($now < $start_time) {
    $remaining_time = $start_time->getTimestamp() - $now->getTimestamp();
} else {
    $remaining_time = 0;
}

if (!isset($_SESSION['lobby_total_time_' . $test_id])) {
    $_SESSION['lobby_total_time_' . $test_id] = max(300, $remaining_time);
}
$lobby_total_time = $_SESSION['lobby_total_time_' . $test_id];

if ($remaining_time <= 0) {
    $d = $conn->prepare("DELETE FROM waiting_students WHERE session_id = ?");
    $d->bind_param("s", $session_id);
    $d->execute();
    $d->close();
    header('Location: test_details.php?token=' . urlencode($token) . '&auto_start=1');
    exit();
}

// Get waiting students for this test
$students_stmt = $conn->prepare("SELECT id, name, session_id FROM waiting_students WHERE test_id = ? ORDER BY joined_at");
$students_stmt->bind_param("i", $test_id);
$students_stmt->execute();
$students_res = $students_stmt->get_result();
$waiting_students = [];
while ($r = $students_res->fetch_assoc()) {
    $is_you = ($r['session_id'] === session_id());
    if ($is_you) {
        $display_name = $r['name'];
    } else {
        $display_name = ($lobby_privacy === 'Anonymous') ? "Student_" . $r['id'] : $r['name'];
    }
    $waiting_students[] = [
        'name' => $display_name,
        'is_you' => $is_you
    ];
}
$students_stmt->close();
$student_count = count($waiting_students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Waiting Room — KAcademyX</title>
  <meta name="description" content="Waiting room for the synchronized MCQ exam lobby at KAcademyX">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link href="assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body class="wr-body">

  <div class="wr-wrap">

    <!-- Left Panel: Countdown -->
    <div class="wr-left">
      <div class="wr-brand">
        <i class="bi bi-mortarboard-fill"></i>
        <span>KAcademyX</span>
      </div>

      <div class="wr-hero-text">
        <h1 class="text-truncate px-2" style="max-width: 400px;"><?php echo htmlspecialchars($test_title); ?></h1>
        <p>Get ready! Your test begins when the countdown reaches zero.</p>
      </div>

      <!-- SVG Countdown Ring -->
      <div class="wr-ring-wrap">
        <svg class="wr-ring" viewBox="0 0 200 200">
          <circle class="wr-ring-bg"   cx="100" cy="100" r="82" />
          <circle class="wr-ring-prog" cx="100" cy="100" r="82"
                  id="ring-progress"
                  stroke-dasharray="515"
                  stroke-dashoffset="<?php echo round(515 * ($remaining_time / $lobby_total_time)); ?>" />
        </svg>
        <div class="wr-ring-label">
          <div class="wr-timer" id="wr-timer"><?php echo ($remaining_time >= 3600 ? gmdate("H:i:s", $remaining_time) : gmdate("i:s", $remaining_time)); ?></div>
          <div class="wr-timer-sub">remaining</div>
        </div>
      </div>

      <!-- Student Badge -->
      <div class="wr-you-badge">
        <i class="bi bi-person-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
        <span class="wr-you-tag">You</span>
      </div>

      <!-- Instructions -->
      <div class="wr-instructions">
        <div class="wr-inst-title"><i class="bi bi-info-circle-fill"></i> Before You Start</div>
        <ul>
          <li>Ensure a <strong>stable internet connection</strong></li>
          <li>Once started, you <strong>cannot leave</strong> the test</li>
          <li>Each question has a <strong>per-question timer</strong></li>
          <li>Do <strong>not refresh</strong> the page</li>
          <li>Results are shown immediately after submission</li>
        </ul>
      </div>
    </div>

    <!-- Right Panel: Student List -->
    <div class="wr-right">
      <div class="wr-list-header">
        <h2><i class="bi bi-people-fill me-2"></i>Students in Lobby</h2>
        <span class="wr-count-badge"><?php echo $student_count; ?> joined</span>
      </div>

      <!-- Search & Sort Controls -->
      <div class="px-4 py-3 border-bottom border-secondary border-opacity-10 d-flex gap-2" style="background: rgba(255,255,255,0.02);">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-transparent border-secondary border-opacity-25 text-muted"><i class="bi bi-search"></i></span>
          <input type="text" id="student-search" class="form-control bg-transparent border-secondary border-opacity-25 text-white" placeholder="Search participant..." style="font-size: 0.85rem;">
        </div>
        <select id="student-sort" class="form-select form-select-sm bg-transparent border-secondary border-opacity-25 text-white w-auto" style="font-size: 0.85rem;">
          <option value="joined" class="bg-dark text-white">Joined Time</option>
          <option value="alpha" class="bg-dark text-white">Alphabetical</option>
        </select>
      </div>

      <div class="wr-student-list" id="wr-student-list">
        <?php if (empty($waiting_students)): ?>
        <div class="wr-no-students">
          <i class="bi bi-hourglass-split"></i>
          <p>Waiting for students to join…</p>
        </div>
        <?php else: ?>
        <?php foreach ($waiting_students as $idx => $s):
          $is_you = $s['is_you'];
          $name = $s['name'];
        ?>
        <div class="wr-student-item <?php echo $is_you ? 'wr-student-you' : ''; ?>"
             data-name="<?php echo htmlspecialchars($name); ?>"
             data-you="<?php echo $is_you ? '1' : '0'; ?>">
          <div class="wr-student-avatar"><?php echo strtoupper(mb_substr($name, 0, 1)); ?></div>
          <div class="wr-student-name">
            <?php echo htmlspecialchars($name); ?>
            <?php if ($is_you): ?>
            <span class="wr-you-pill">You</span>
            <?php endif; ?>
          </div>
          <div class="wr-student-status"><i class="bi bi-circle-fill"></i> Ready</div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="wr-footer-note">
        <i class="bi bi-arrow-clockwise"></i>
        Student list refreshes every 5 seconds
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const TOTAL_TIME  = <?php echo $lobby_total_time; ?>;
    const FULL_DASH   = 515;
    let timeLeft      = <?php echo $remaining_time; ?>;
    const TEST_TOKEN  = <?php echo json_encode($token); ?>;
    const timerEl     = document.getElementById('wr-timer');
    const ringEl      = document.getElementById('ring-progress');

    function pad(n) { return String(n).padStart(2, '0'); }
    function formatTime(seconds) {
      const h = Math.floor(seconds / 3600);
      const m = Math.floor((seconds % 3600) / 60);
      const s = seconds % 60;
      if (h > 0) {
        return String(h).padStart(2, '0') + ':' + pad(m) + ':' + pad(s);
      } else {
        return pad(m) + ':' + pad(s);
      }
    }

    const countdown = setInterval(() => {
      timeLeft--;
      if (timeLeft <= 0) {
        clearInterval(countdown);
        window.location.href = 'test_details.php?token=' + encodeURIComponent(TEST_TOKEN) + '&auto_start=1';
        return;
      }
      timerEl.textContent = formatTime(timeLeft);

      // Update ring
      const offset = Math.round(FULL_DASH * (timeLeft / TOTAL_TIME));
      ringEl.setAttribute('stroke-dashoffset', offset);

      // Warning pulse when < 60s
      if (timeLeft < 60) timerEl.classList.add('wr-timer-warn');
    }, 1000);

    // Client-side search and sort
    let rawStudents = [];

    function parseStudentsFromDOM() {
      const container = document.getElementById('wr-student-list');
      const items = container.querySelectorAll('.wr-student-item');
      rawStudents = Array.from(items).map((item, index) => {
        return {
          element: item.cloneNode(true),
          name: item.getAttribute('data-name') || '',
          isYou: item.getAttribute('data-you') === '1',
          index: index
        };
      });
    }

    function renderStudents() {
      const searchVal = document.getElementById('student-search').value.toLowerCase();
      const sortVal = document.getElementById('student-sort').value;
      const container = document.getElementById('wr-student-list');

      // Filter
      let filtered = rawStudents.filter(s => {
        return s.name.toLowerCase().includes(searchVal);
      });

      // Sort
      if (sortVal === 'alpha') {
        filtered.sort((a, b) => a.name.localeCompare(b.name));
      } else {
        // Original order
        filtered.sort((a, b) => a.index - b.index);
      }

      // Clear container and append
      container.innerHTML = '';
      if (filtered.length === 0) {
        const noResults = document.createElement('div');
        noResults.className = 'wr-no-students';
        noResults.innerHTML = '<i class="bi bi-search fs-3 mb-2"></i><p>No students found</p>';
        container.appendChild(noResults);
      } else {
        filtered.forEach(s => {
          container.appendChild(s.element);
        });
      }
    }

    document.getElementById('student-search').addEventListener('input', renderStudents);
    document.getElementById('student-sort').addEventListener('change', renderStudents);

    // Initial setup
    parseStudentsFromDOM();
    renderStudents();

    // Refresh student list silently every 5 seconds
    setInterval(() => {
      fetch(window.location.href)
        .then(r => r.text())
        .then(html => {
          const parser  = new DOMParser();
          const doc     = parser.parseFromString(html, 'text/html');
          const newList = doc.getElementById('wr-student-list');
          if (newList) {
            // Update the count badge
            const newCountBadge = doc.querySelector('.wr-count-badge');
            if (newCountBadge) {
              document.querySelector('.wr-count-badge').innerHTML = newCountBadge.innerHTML;
            }
            // Parse new items from the fetched DOM
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = newList.innerHTML;
            const items = tempDiv.querySelectorAll('.wr-student-item');
            rawStudents = Array.from(items).map((item, index) => {
              return {
                element: item,
                name: item.getAttribute('data-name') || '',
                isYou: item.getAttribute('data-you') === '1',
                index: index
              };
            });
            renderStudents();
          }
        })
        .catch(() => {});
    }, 5000);
  </script>
</body>
</html>