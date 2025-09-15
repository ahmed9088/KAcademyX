<?php
session_start();
require_once 'forms/db.php';

// Check if student has registered
if (!isset($_SESSION['student_name'])) {
    header('Location: start_test.php');
    exit();
}

// Check if test ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: my_tests.php');
    exit();
}

$test_id = $_GET['id'];
$student_name = $_SESSION['student_name'];

// Get test result from database
try {
    $stmt = $conn->prepare("SELECT * FROM test_results WHERE id = ? AND student_name = ?");
    $stmt->bind_param("is", $test_id, $student_name);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Result - KAcademyX</title>
  <meta name="description" content="Test results at KAcademyX">
  <meta name="keywords" content="KAcademyX, test results, MCQ">
  
  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  
  <!-- Main CSS File -->
  <style>
    :root {
      --primary-color: #4154f1;
      --secondary-color: #7b68ee;
      --accent-color: #00d2ff;
      --dark-color: #0f172a;
      --light-color: #f8fafc;
      --success-color: #2ecc71;
      --danger-color: #e74c3c;
      --warning-color: #f39c12;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      color: var(--dark-color);
      background: linear-gradient(135deg, #f8fafc 0%, #e6f7ff 100%);
      line-height: 1.6;
      min-height: 100vh;
      padding: 20px 0;
    }
    
    h1, h2, h3, h4, h5, h6 {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
    }
    
    /* Result Container */
    .result-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 20px;
    }
    
    /* Back Button */
    .back-button {
      display: inline-flex;
      align-items: center;
      margin-bottom: 30px;
      padding: 10px 20px;
      background: white;
      border-radius: 50px;
      font-weight: 500;
      color: var(--dark-color);
      text-decoration: none;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }
    
    .back-button i {
      margin-right: 8px;
    }
    
    .back-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      color: var(--dark-color);
    }
    
    /* Result Header */
    .result-header {
      background: white;
      border-radius: 20px;
      padding: 40px;
      margin-bottom: 30px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .result-header::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    }
    
    .result-title {
      font-size: 2.5rem;
      margin-bottom: 20px;
      color: var(--dark-color);
      position: relative;
      display: inline-block;
    }
    
    .result-title::after {
      content: "";
      position: absolute;
      bottom: -10px;
      left: 25%;
      width: 50%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      border-radius: 2px;
    }
    
    .test-category {
      display: inline-block;
      padding: 8px 20px;
      background: rgba(65, 84, 241, 0.1);
      color: var(--primary-color);
      border-radius: 50px;
      font-weight: 600;
      margin-bottom: 30px;
    }
    
    .test-date {
      display: inline-block;
      padding: 8px 20px;
      background: rgba(52, 152, 219, 0.1);
      color: var(--info-color);
      border-radius: 50px;
      font-weight: 600;
      margin-bottom: 30px;
      margin-left: 15px;
    }
    
    .score-display {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 30px;
      flex-wrap: wrap;
      gap: 40px;
    }
    
    .score-circle-container {
      position: relative;
    }
    
    .score-circle {
      width: 180px;
      height: 180px;
      border-radius: 50%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      position: relative;
      background: conic-gradient(var(--primary-color) 0%, var(--primary-color) <?php echo $score; ?>%, #e2e8f0 <?php echo $score; ?>%, #e2e8f0 100%);
      box-shadow: 0 10px 25px rgba(65, 84, 241, 0.2);
    }
    
    .score-inner {
      width: 160px;
      height: 160px;
      border-radius: 50%;
      background: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      position: relative;
    }
    
    .score-value {
      font-size: 3rem;
      font-weight: 800;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .score-label {
      font-size: 1rem;
      color: #64748b;
      font-weight: 600;
      margin-top: 5px;
    }
    
    .score-details {
      text-align: left;
    }
    
    .score-text {
      font-size: 1.8rem;
      margin-bottom: 15px;
      font-weight: 700;
      color: var(--dark-color);
    }
    
    .score-message {
      font-size: 1.2rem;
      color: #64748b;
      max-width: 400px;
      line-height: 1.6;
    }
    
    .result-badge {
      display: inline-block;
      padding: 10px 25px;
      border-radius: 50px;
      font-weight: 600;
      margin-top: 20px;
      font-size: 1.1rem;
      background: <?php echo $result_status === 'Pass' ? 'rgba(46, 204, 113, 0.1)' : 'rgba(231, 76, 60, 0.1)'; ?>;
      color: <?php echo $result_status === 'Pass' ? 'var(--success-color)' : 'var(--danger-color)'; ?>;
      border: 1px solid <?php echo $result_status === 'Pass' ? 'var(--success-color)' : 'var(--danger-color)'; ?>;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    /* Performance Summary */
    .performance-summary {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin: 30px 0;
      flex-wrap: wrap;
    }
    
    .performance-item {
      background: white;
      padding: 25px;
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      flex: 1;
      min-width: 180px;
      transition: all 0.3s ease;
    }
    
    .performance-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .performance-icon {
      font-size: 2rem;
      margin-bottom: 15px;
      color: var(--primary-color);
    }
    
    .performance-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 5px;
    }
    
    .performance-label {
      font-size: 1rem;
      color: #64748b;
      font-weight: 500;
    }
    
    /* Action Buttons */
    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 40px;
      flex-wrap: wrap;
    }
    
    .btn-action {
      padding: 15px 35px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      display: flex;
      align-items: center;
      text-decoration: none;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .btn-action i {
      margin-right: 10px;
      font-size: 1.2rem;
    }
    
    .btn-primary {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
    }
    
    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(65, 84, 241, 0.3);
      color: white;
    }
    
    .btn-secondary {
      background: white;
      color: var(--dark-color);
      border: 1px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
      background: #f8fafc;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      color: var(--dark-color);
    }
    
    .btn-success {
      background: var(--success-color);
      color: white;
    }
    
    .btn-success:hover {
      background: #27ae60;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(46, 204, 113, 0.3);
    }
    
    /* Responsive Design */
    @media (max-width: 767.98px) {
      .result-title {
        font-size: 2rem;
      }
      
      .score-display {
        flex-direction: column;
        align-items: center;
      }
      
      .score-details {
        text-align: center;
      }
      
      .score-circle {
        width: 150px;
        height: 150px;
      }
      
      .score-inner {
        width: 130px;
        height: 130px;
      }
      
      .score-value {
        font-size: 2.5rem;
      }
      
      .performance-summary {
        flex-direction: column;
      }
      
      .action-buttons {
        flex-direction: column;
        align-items: center;
      }
      
      .btn-action {
        width: 100%;
        justify-content: center;
      }
    }
    
    /* Print Styles */
    @media print {
      body {
        background: white;
        padding: 0;
      }
      
      .result-header {
        box-shadow: none;
        border: 1px solid #ddd;
      }
      
      .back-button, .btn-action {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="result-container">
    <!-- Back Button -->
    <a href="my_tests.php" class="back-button animate__animated animate__fadeInLeft">
      <i class="bi bi-arrow-left"></i> Back to Test History
    </a>
    
    <!-- Result Header -->
    <div class="result-header animate__animated animate__fadeIn">
      <h1 class="result-title">Test Result</h1>
      <div class="test-category"><?php echo htmlspecialchars($test_result['category']); ?></div>
      <div class="test-date">
        <i class="bi bi-calendar3"></i> <?php echo (new DateTime($test_result['date_taken']))->format('F d, Y'); ?>
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
          <div class="result-badge">
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
  
  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>