<?php
session_start();
require_once 'forms/db.php';

$token = $_GET['token'] ?? '';

if (isset($_SESSION['student_name'])) {
    header('Location: waiting_room.php' . ($token ? '?token=' . urlencode($token) : '')); exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name'] ?? '');
    if (strlen($student_name) < 2) {
        $error = "Please enter a valid full name (at least 2 characters).";
    } else {
        $_SESSION['student_name'] = $student_name;
        header('Location: waiting_room.php' . ($token ? '?token=' . urlencode($token) : '')); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Join Exam Lobby — KAcademyX</title>
  <meta name="description" content="Register and join the synchronized exam lobby at KAcademyX">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link href="assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body class="centered-page-body">

  <div class="centered-card-wrap">

    <!-- Back Link -->
    <a href="test.php" class="cpc-back-link">
      <i class="bi bi-arrow-left"></i> Back to Tests
    </a>

    <div class="centered-card">
      <!-- Brand Header -->
      <div class="cpc-brand">
        <div class="cpc-brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <div>
          <div class="cpc-brand-name">KAcademyX</div>
          <div class="cpc-brand-sub">Online Examination Platform</div>
        </div>
      </div>

      <h1 class="cpc-title">Join Exam Lobby</h1>
      <p class="cpc-desc">Enter your full name to register in the 5-minute waiting lobby before the test begins.</p>

      <!-- Info Badges -->
      <div class="cpc-info-row">
        <div class="cpc-info-badge">
          <i class="bi bi-clock-fill"></i>
          <span>5-Min Wait</span>
        </div>
        <div class="cpc-info-badge">
          <i class="bi bi-people-fill"></i>
          <span>Synced Lobby</span>
        </div>
        <div class="cpc-info-badge">
          <i class="bi bi-shield-check-fill"></i>
          <span>Secure Exam</span>
        </div>
      </div>

      <?php if ($error): ?>
      <div class="cpc-error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
      <?php endif; ?>

      <!-- Registration Form -->
      <form method="post" action="" id="start-form">
        <div class="cpc-field">
          <label for="student_name">Your Full Name</label>
          <div class="cpc-input-wrap">
            <i class="bi bi-person-fill cpc-input-icon"></i>
            <input type="text" id="student_name" name="student_name"
                   placeholder="e.g. Ahmed Saffar"
                   autocomplete="name"
                   value="<?php echo isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : ''; ?>"
                   required>
          </div>
          <div class="cpc-hint">This name will appear on your certificate if you pass.</div>
        </div>

        <button type="submit" class="cpc-submit-btn" id="submit-btn">
          <i class="bi bi-arrow-right-circle-fill me-2"></i>
          Join Waiting Room
        </button>
      </form>

      <!-- Notes -->
      <div class="cpc-notes">
        <div class="cpc-note-item"><i class="bi bi-dot"></i> Wait up to 5 minutes in the lobby with other students</div>
        <div class="cpc-note-item"><i class="bi bi-dot"></i> Test starts automatically after the countdown</div>
        <div class="cpc-note-item"><i class="bi bi-dot"></i> Stable internet connection is required</div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('start-form').addEventListener('submit', function() {
      const btn = document.getElementById('submit-btn');
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Joining...';
      btn.disabled = true;
    });
  </script>
</body>
</html>