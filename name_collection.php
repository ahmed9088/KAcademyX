<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'forms/db.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php'); exit();
}
if (!isset($_SESSION['test'])) {
    header('Location: test.php'); exit();
}

$user_id     = $_SESSION["id"];
$user_result = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user        = mysqli_fetch_assoc($user_result);

$test             = $_SESSION['test'];
$category         = $test['category'];
$total_questions  = count($test['questions']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Confirm Name — KAcademyX</title>
  <meta name="description" content="Confirm your name before starting the test at KAcademyX">
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link href="assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body class="centered-page-body">

  <div class="centered-card-wrap">

    <a href="test.php" class="cpc-back-link">
      <i class="bi bi-arrow-left"></i> Back to Tests
    </a>

    <div class="centered-card">
      <!-- Icon Header -->
      <div class="cpc-icon-header">
        <div class="cpc-icon-circle"><i class="bi bi-person-badge-fill"></i></div>
      </div>

      <h1 class="cpc-title">Confirm Your Name</h1>
      <p class="cpc-desc">This name will appear on your certificate if you pass the test. Make sure it is spelled correctly.</p>

      <!-- Test Summary Info -->
      <div class="cpc-test-summary">
        <div class="cpc-test-info-item">
          <div class="cpc-test-info-icon"><i class="bi bi-book-fill"></i></div>
          <div class="cpc-test-info-text">
            <div class="cpc-test-info-label">Subject</div>
            <div class="cpc-test-info-value"><?php echo htmlspecialchars($category); ?></div>
          </div>
        </div>
        <div class="cpc-test-info-item">
          <div class="cpc-test-info-icon"><i class="bi bi-question-circle-fill"></i></div>
          <div class="cpc-test-info-text">
            <div class="cpc-test-info-label">Questions</div>
            <div class="cpc-test-info-value"><?php echo $total_questions; ?> MCQs</div>
          </div>
        </div>
        <div class="cpc-test-info-item">
          <div class="cpc-test-info-icon"><i class="bi bi-clock-fill"></i></div>
          <div class="cpc-test-info-text">
            <div class="cpc-test-info-label">Time Limit</div>
            <div class="cpc-test-info-value">Per-question timer</div>
          </div>
        </div>
      </div>

      <!-- Name Form -->
      <form method="post" action="take_test.php" id="name-form">
        <div class="cpc-field">
          <label for="student_name">Your Full Name</label>
          <div class="cpc-input-wrap">
            <i class="bi bi-person-fill cpc-input-icon"></i>
            <input type="text" id="student_name" name="student_name"
                   value="<?php echo htmlspecialchars($user['name']); ?>"
                   placeholder="Enter your full name"
                   required autofocus>
          </div>
          <div class="cpc-hint">Name used on certificates — spell it correctly!</div>
        </div>

        <!-- Instructions -->
        <div class="cpc-instructions">
          <div class="cpc-inst-title"><i class="bi bi-shield-check-fill"></i> Test Guidelines</div>
          <ul>
            <li>Do not refresh or navigate away during the test</li>
            <li>Do not right-click or open developer tools</li>
            <li>Use no external resources during the exam</li>
            <li>Answer within the time limit for each question</li>
          </ul>
        </div>

        <button type="submit" name="submit_name" class="cpc-submit-btn" id="submit-btn">
          <i class="bi bi-play-circle-fill me-2"></i>
          Begin Test
        </button>
      </form>
    </div>
  </div>

  <script>
    document.getElementById('name-form').addEventListener('submit', function(e) {
      const name = document.getElementById('student_name').value.trim();
      if (name.length < 2) {
        e.preventDefault();
        alert('Please enter a valid name (at least 2 characters).');
        return;
      }
      const btn = document.getElementById('submit-btn');
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Starting...';
      btn.disabled = true;
    });
    document.getElementById('student_name').focus();
  </script>
</body>
</html>