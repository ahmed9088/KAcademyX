<?php
session_start();
require_once 'forms/db.php';

// Auth guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

$user_id = $_SESSION["id"];

// Check if test ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: my_tests.php');
    exit();
}

$test_id = (int)$_GET['id'];

// Get test result from database
try {
    $stmt = $conn->prepare("SELECT * FROM test_results WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $test_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Test not found or doesn't belong to this student
        header('Location: my_tests.php');
        exit();
    }
    
    $test_result = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    // Log error but don't interrupt the flow
    error_log("Error fetching test result: " . $e->getMessage());
    header('Location: my_tests.php');
    exit();
}

// Calculate status
$score = $test_result['score'];
$passing_score = 70; // 70% to pass
$result_status = $score >= $passing_score ? 'Pass' : 'Fail';

$pageTitle  = "Test Detail";
$activePage = "tests";
include "includes/header.php";
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">


<main class="main">
  <!-- Page Title -->
  <div class="page-title text-center" data-aos="fade">
    <div class="container">
      <h1>Test Detail</h1>
      <p>Performance details for the <?php echo htmlspecialchars($test_result['test_name']); ?>.</p>
    </div>
    <nav class="breadcrumbs">
      <div class="container">
        <ol>
          <li><a href="index.php">Home</a></li>
          <li><a href="test.php">Tests</a></li>
          <li><a href="my_tests.php">My History</a></li>
          <li class="current">Details</li>
        </ol>
      </div>
    </nav>
  </div>

  <section class="section">
    <div class="container">
      <div class="result-container mt-0 pt-0">
        <!-- Back Button -->
        <a href="my_tests.php" class="back-button animate__animated animate__fadeInLeft mb-4">
          <i class="bi bi-arrow-left"></i> Back to Test History
        </a>
        
        <!-- Result Header -->
        <div class="result-header animate__animated animate__fadeIn">
          <h1 class="result-title">Test Result</h1>
          <div class="test-category"><?php echo htmlspecialchars($test_result['category']); ?></div>
          <div class="test-date">
            <i class="bi bi-calendar3"></i> <?php echo (new DateTime($test_result['test_date']))->format('F d, Y'); ?>
          </div>
      
      <div class="score-display">
        <div class="score-circle-container">
          <div class="score-circle">
            <div class="score-inner">
              <div class="score-value"><?php echo $score; ?>%</div>
              <div class="score-label">SCORE</div>
            </div>
          </div>
        </div>
        
        <div class="score-details">
          <div class="score-text"><?php echo $test_result['correct_answers']; ?> out of <?php echo $test_result['total_questions']; ?> correct</div>
          <div class="score-message">
            <?php 
            if ($score >= 90) {
                echo "Outstanding! You've demonstrated exceptional knowledge of this subject.";
            } elseif ($score >= 80) {
                echo "Excellent work! You've mastered this topic.";
            } elseif ($score >= 70) {
                echo "Good job! You have a solid understanding.";
            } elseif ($score >= 60) {
                echo "Not bad, but there's room for improvement.";
            } elseif ($score >= 50) {
                echo "You passed, but consider reviewing the material again.";
            } else {
                echo "Keep studying and try again. You can do it!";
            }
            ?>
          </div>
          <div class="result-badge <?php echo $result_status === 'Pass' ? 'pass' : 'fail'; ?>">
            <?php echo $result_status === 'Pass' ? '<i class="bi bi-check-circle-fill"></i> ' : '<i class="bi bi-x-circle-fill"></i> ' ?>
            <?php echo $result_status; ?>
          </div>
        </div>
      </div>
      
      <div class="performance-summary">
        <div class="performance-item animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
          <div class="performance-icon">
            <i class="bi bi-trophy-fill"></i>
          </div>
          <div class="performance-value"><?php echo $score; ?>%</div>
          <div class="performance-label">Overall Score</div>
        </div>
        <div class="performance-item animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
          <div class="performance-icon">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="performance-value"><?php echo $test_result['correct_answers']; ?>/<?php echo $test_result['total_questions']; ?></div>
          <div class="performance-label">Correct Answers</div>
        </div>
        <div class="performance-item animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
          <div class="performance-icon">
            <i class="bi bi-x-circle-fill"></i>
          </div>
          <div class="performance-value"><?php echo $test_result['total_questions'] - $test_result['correct_answers']; ?></div>
          <div class="performance-label">Incorrect Answers</div>
        </div>
        <div class="performance-item animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
          <div class="performance-icon">
            <i class="bi bi-flag-fill"></i>
          </div>
          <div class="performance-value">70%</div>
          <div class="performance-label">Passing Score</div>
        </div>
      </div>
    </div>
    
    <div class="action-buttons animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
      <a href="test.php" class="btn-action btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Tests
      </a>
      <a href="my_tests.php" class="btn-action btn-primary">
        <i class="bi bi-list-ul"></i> My Test History
      </a>
      <button onclick="window.print()" class="btn-action btn-success">
        <i class="bi bi-printer"></i> Print Results
      </button>
    </div>
      </div>
    </div>
  </section>
</main>

<?php include "includes/footer.php"; ?>