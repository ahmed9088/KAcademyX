<?php
session_start();
require_once 'forms/db.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

// Get user ID from session
$user_id = $_SESSION["id"];

// Check if test session exists
if (!isset($_SESSION['test'])) {
    header('Location: test.php');
    exit();
}

// Remove the student from waiting_students if still there
if (isset($_SESSION['student_name'])) {
    $session_id = session_id();
    try {
        $delete_query = "DELETE FROM waiting_students WHERE session_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Log error but don't interrupt the flow
        error_log("Error removing from waiting_students: " . $e->getMessage());
    }
}

// Get test data
$test = $_SESSION['test'];
$questions = $test['questions'];
$answers = isset($test['answers']) ? $test['answers'] : [];
$total_questions = count($questions);

// Calculate score
$correct_answers = 0;
foreach ($questions as $question) {
    if (isset($answers[$question['id']]) && $answers[$question['id']] === $question['correct_answer']) {
        $correct_answers++;
    }
}
$score = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100) : 0;
$passing_score = 70; // 70% to pass
$is_passed = $score >= $passing_score;

// Get student name from test session
$student_name = isset($test['student_name']) ? $test['student_name'] : $_SESSION['user']['name'];
$test_name = isset($test['test_name']) ? $test['test_name'] : 'Test in ' . $test['category'];
$category = $test['category'];

// Convert answers array to JSON for storage
$answers_json = json_encode($answers);

// Check if test result already exists in session (from take_test.php submission)
if (isset($_SESSION['test_result_id'])) {
    $test_result_id = $_SESSION['test_result_id'];
    unset($_SESSION['test_result_id']);
} else {
    // Save test result to database
    try {
        $insert_query = "INSERT INTO test_results (
            user_id, 
            student_name, 
            student_id, 
            test_id, 
            test_name, 
            category, 
            total_questions, 
            correct_answers, 
            score, 
            is_passed, 
            answers, 
            test_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_query);
        $student_id_value = isset($test['student_id']) ? $test['student_id'] : '';
        $test_id_value = 0; // We don't have a specific test ID, using 0 as placeholder
        
        $stmt->bind_param(
            "isisiiiiiis", 
            $user_id, 
            $student_name, 
            $student_id_value, 
            $test_id_value, 
            $test_name, 
            $category, 
            $total_questions, 
            $correct_answers, 
            $score, 
            $is_passed, 
            $answers_json
        );
        
        $stmt->execute();
        $test_result_id = $stmt->insert_id;
        $stmt->close();
        
        // Update user_tests table with completion status
        if (isset($test['user_test_id'])) {
            $status = $is_passed ? 'Completed' : 'Failed';
            $update_query = "UPDATE user_tests SET status = ?, last_accessed = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $status, $test['user_test_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Generate certificate if test is passed
        if ($is_passed) {
            $certificate_url = "certificates/certificate_{$test_result_id}.pdf";
            $insert_cert_query = "INSERT INTO certificates (test_result_id, certificate_url) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_cert_query);
            $stmt->bind_param("is", $test_result_id, $certificate_url);
            $stmt->execute();
            $stmt->close();
            
            // Update test_results to mark certificate as generated
            $update_query = "UPDATE test_results SET certificate_generated = 1 WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $test_result_id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Log error but don't interrupt the flow
        error_log("Error saving test result: " . $e->getMessage());
        $test_result_id = null;
    }
}

// Clear test session
unset($_SESSION['test']);
unset($_SESSION['secure_environment_shown']);
unset($_SESSION['question_start_time']);
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
      --info-color: #3498db;
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
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
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
      font-size: 2.8rem;
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
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.03); }
      100% { transform: scale(1); }
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
      background: <?php echo $is_passed ? 'rgba(46, 204, 113, 0.1)' : 'rgba(231, 76, 60, 0.1)'; ?>;
      color: <?php echo $is_passed ? 'var(--success-color)' : 'var(--danger-color)'; ?>;
      border: 1px solid <?php echo $is_passed ? 'var(--success-color)' : 'var(--danger-color)'; ?>;
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
    
    /* Certificate Section */
    .certificate-section {
      background: white;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
      text-align: center;
    }
    
    .certificate-preview {
      max-width: 800px;
      margin: 0 auto;
      border: 2px dashed #e2e8f0;
      border-radius: 15px;
      padding: 30px;
      background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
    }
    
    .certificate-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--dark-color);
      margin-bottom: 20px;
    }
    
    .certificate-subtitle {
      font-size: 1.2rem;
      color: #64748b;
      margin-bottom: 30px;
    }
    
    .certificate-badge {
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      margin: 0 auto 30px;
      box-shadow: 0 10px 25px rgba(65, 84, 241, 0.3);
    }
    
    .certificate-badge i {
      font-size: 3rem;
      color: white;
    }
    
    .certificate-text {
      font-size: 1.1rem;
      color: #64748b;
      margin-bottom: 30px;
      line-height: 1.6;
    }
    
    /* Questions Review */
    .questions-review {
      background: white;
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
    }
    
    .section-title {
      font-size: 2rem;
      margin-bottom: 30px;
      color: var(--dark-color);
      display: flex;
      align-items: center;
      position: relative;
    }
    
    .section-title i {
      margin-right: 15px;
      color: var(--primary-color);
      font-size: 1.8rem;
    }
    
    .section-title::after {
      content: "";
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 80px;
      height: 3px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      border-radius: 2px;
    }
    
    .question-card {
      border: 1px solid #e2e8f0;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 25px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .question-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: <?php echo $is_passed ? 'var(--success-color)' : 'var(--danger-color)'; ?>;
      opacity: 0.7;
    }
    
    .question-card:hover {
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      transform: translateY(-3px);
    }
    
    .question-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    
    .question-number {
      background: var(--primary-color);
      color: white;
      width: 35px;
      height: 35px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: 600;
      font-size: 1rem;
      box-shadow: 0 5px 10px rgba(65, 84, 241, 0.3);
    }
    
    .question-status {
      font-weight: 600;
      padding: 8px 20px;
      border-radius: 50px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
    }
    
    .question-status i {
      margin-right: 5px;
    }
    
    .status-correct {
      background: rgba(46, 204, 113, 0.1);
      color: var(--success-color);
    }
    
    .status-incorrect {
      background: rgba(231, 76, 60, 0.1);
      color: var(--danger-color);
    }
    
    .status-unanswered {
      background: rgba(243, 156, 18, 0.1);
      color: var(--warning-color);
    }
    
    .question-text {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 25px;
      color: var(--dark-color);
      line-height: 1.5;
    }
    
    .options-list {
      margin-bottom: 25px;
    }
    
    .option-item {
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      transition: all 0.2s ease;
    }
    
    .option-marker {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: 600;
      margin-right: 15px;
      flex-shrink: 0;
    }
    
    .option-text {
      flex-grow: 1;
      font-size: 1.05rem;
    }
    
    .option-default {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
    }
    
    .option-user {
      background: rgba(65, 84, 241, 0.05);
      border: 1px solid var(--primary-color);
    }
    
    .option-correct {
      background: rgba(46, 204, 113, 0.05);
      border: 1px solid var(--success-color);
    }
    
    .option-incorrect {
      background: rgba(231, 76, 60, 0.05);
      border: 1px solid var(--danger-color);
    }
    
    .marker-default {
      background: #e2e8f0;
      color: var(--dark-color);
    }
    
    .marker-user {
      background: var(--primary-color);
      color: white;
    }
    
    .marker-correct {
      background: var(--success-color);
      color: white;
    }
    
    .marker-incorrect {
      background: var(--danger-color);
      color: white;
    }
    
    .answer-feedback {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 20px;
      border-top: 1px solid #e2e8f0;
      margin-top: 15px;
      flex-wrap: wrap;
      gap: 15px;
    }
    
    .user-answer, .correct-answer {
      display: flex;
      align-items: center;
      background: #f8fafc;
      padding: 12px 15px;
      border-radius: 10px;
      flex: 1;
      min-width: 250px;
    }
    
    .answer-label {
      font-weight: 600;
      margin-right: 10px;
      color: #64748b;
      white-space: nowrap;
    }
    
    .answer-value {
      font-weight: 600;
    }
    
    .user-answer {
      border-left: 4px solid var(--primary-color);
    }
    
    .user-answer .answer-value {
      color: var(--primary-color);
    }
    
    .correct-answer {
      border-left: 4px solid var(--success-color);
    }
    
    .correct-answer .answer-value {
      color: var(--success-color);
    }
    
    .explanation {
      margin-top: 20px;
      padding: 20px;
      background: #f8fafc;
      border-radius: 12px;
      border-left: 4px solid var(--info-color);
    }
    
    .explanation-title {
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--dark-color);
      display: flex;
      align-items: center;
    }
    
    .explanation-title i {
      margin-right: 10px;
      color: var(--info-color);
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
      
      .question-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .question-status {
        margin-top: 10px;
      }
      
      .answer-feedback {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .correct-answer {
        margin-top: 10px;
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
      
      .result-header, .questions-review, .certificate-section {
        box-shadow: none;
        border: 1px solid #ddd;
      }
      
      .btn-action {
        display: none;
      }
      
      .certificate-preview {
        border: 1px solid #ddd;
      }
    }
  </style>
</head>
<body>
  <div class="result-container">
    <!-- Result Header -->
    <div class="result-header animate__animated animate__fadeIn">
      <h1 class="result-title">Test Results</h1>
      <div class="test-category"><?php echo htmlspecialchars($test_name); ?></div>
      
      <div class="score-display">
        <div class="score-circle-container">
          <div class="score-circle">
            <div class="score-inner">
              <div class="score-value">0%</div>
              <div class="score-label">SCORE</div>
            </div>
          </div>
        </div>
        
        <div class="score-details">
          <div class="score-text"><?php echo $correct_answers; ?> out of <?php echo $total_questions; ?> correct</div>
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
          <div class="result-badge animate__animated animate__bounceIn">
            <?php echo $is_passed ? '<i class="bi bi-check-circle-fill"></i> ' : '<i class="bi bi-x-circle-fill"></i> ' ?>
            <?php echo $is_passed ? 'Pass' : 'Fail'; ?>
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
          <div class="performance-value"><?php echo $correct_answers; ?>/<?php echo $total_questions; ?></div>
          <div class="performance-label">Correct Answers</div>
        </div>
        <div class="performance-item animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
          <div class="performance-icon">
            <i class="bi bi-x-circle-fill"></i>
          </div>
          <div class="performance-value"><?php echo $total_questions - $correct_answers; ?></div>
          <div class="performance-label">Incorrect Answers</div>
        </div>
        <div class="performance-item animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
          <div class="performance-icon">
            <i class="bi bi-flag-fill"></i>
          </div>
          <div class="performance-value"><?php echo $passing_score; ?>%</div>
          <div class="performance-label">Passing Score</div>
        </div>
      </div>
    </div>
    
    <!-- Certificate Section - Only shown if passed -->
    <?php if ($is_passed && $test_result_id): ?>
    <div class="certificate-section animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
      <h2 class="section-title">
        <i class="bi bi-award-fill"></i> Certificate of Completion
      </h2>
      
      <div class="certificate-preview">
        <div class="certificate-badge">
          <i class="bi bi-patch-check-fill"></i>
        </div>
        <h3 class="certificate-title">Congratulations!</h3>
        <p class="certificate-subtitle">You have successfully passed the <?php echo htmlspecialchars($test_name); ?></p>
        <p class="certificate-text">
          Your dedication and hard work have paid off. You've demonstrated a solid understanding of the subject matter and achieved a score of <?php echo $score; ?>%. Keep up the excellent work!
        </p>
        <a href="generate_certificate.php?id=<?php echo $test_result_id; ?>" class="btn-action btn-primary" target="_blank">
          <i class="bi bi-download"></i> Download Certificate
        </a>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Questions Review -->
    <div class="questions-review animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
      <h2 class="section-title">
        <i class="bi bi-list-check"></i> Question Review
      </h2>
      
      <?php foreach ($questions as $index => $question): 
        $question_id = $question['id'];
        $user_answer = isset($answers[$question_id]) ? $answers[$question_id] : '';
        $is_correct = ($user_answer === $question['correct_answer']);
        $status_class = $is_correct ? 'status-correct' : ($user_answer ? 'status-incorrect' : 'status-unanswered');
        $status_text = $is_correct ? 'Correct' : ($user_answer ? 'Incorrect' : 'Unanswered');
        $status_icon = $is_correct ? 'bi-check-circle-fill' : ($user_answer ? 'bi-x-circle-fill' : 'bi-question-circle-fill');
        
        // Determine which options to highlight
        $options = ['A', 'B', 'C', 'D'];
      ?>
      <div class="question-card animate__animated animate__fadeInUp" style="animation-delay: <?php echo 0.7 + ($index * 0.1); ?>s">
        <div class="question-header">
          <div class="question-number"><?php echo $index + 1; ?></div>
          <div class="question-status <?php echo $status_class; ?>">
            <i class="bi <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
          </div>
        </div>
        
        <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
        
        <div class="options-list">
          <?php foreach ($options as $option): 
            $option_key = 'option_' . strtolower($option);
            $option_class = 'option-default';
            $marker_class = 'marker-default';
            
            if ($option === $question['correct_answer']) {
              $option_class = 'option-correct';
              $marker_class = 'marker-correct';
            }
            
            if ($option === $user_answer) {
              if ($option === $question['correct_answer']) {
                $option_class = 'option-correct';
                $marker_class = 'marker-correct';
              } else {
                $option_class = 'option-incorrect';
                $marker_class = 'marker-incorrect';
              }
            }
          ?>
          <div class="option-item <?php echo $option_class; ?>">
            <div class="option-marker <?php echo $marker_class; ?>"><?php echo $option; ?></div>
            <div class="option-text"><?php echo htmlspecialchars($question[$option_key]); ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        
        <div class="answer-feedback">
          <div class="user-answer">
            <span class="answer-label">Your Answer:</span>
            <span class="answer-value">
              <?php 
              if ($user_answer && in_array($user_answer, ['A', 'B', 'C', 'D'])) {
                  $option_key = 'option_' . strtolower($user_answer);
                  echo $user_answer . '. ' . htmlspecialchars($question[$option_key]);
              } else {
                echo 'Not answered';
              }
              ?>
            </span>
          </div>
          
          <div class="correct-answer">
            <span class="answer-label">Correct Answer:</span>
            <span class="answer-value">
              <?php 
              $correct_answer = $question['correct_answer'];
              if ($correct_answer && in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
                  $option_key = 'option_' . strtolower($correct_answer);
                  echo $correct_answer . '. ' . htmlspecialchars($question[$option_key]);
              } else {
                  echo 'Not available';
              }
              ?>
            </span>
          </div>
        </div>
        
        <?php if (!empty($question['explanation'])): ?>
        <div class="explanation">
          <div class="explanation-title">
            <i class="bi bi-info-circle-fill"></i> Explanation:
          </div>
          <?php echo htmlspecialchars($question['explanation']); ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    
    <div class="action-buttons animate__animated animate__fadeInUp" style="animation-delay: 1.5s">
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
  <script>
    // Add animation to score display
    document.addEventListener('DOMContentLoaded', function() {
      const scoreValue = document.querySelector('.score-value');
      if (scoreValue) {
        let currentScore = 0;
        const targetScore = <?php echo $score; ?>;
        const duration = 2000; // 2 seconds
        const steps = 60;
        const increment = targetScore / steps;
        const stepTime = duration / steps;
        
        const timer = setInterval(() => {
          currentScore += increment;
          if (currentScore >= targetScore) {
            currentScore = targetScore;
            clearInterval(timer);
          }
          scoreValue.textContent = Math.round(currentScore) + '%';
        }, stepTime);
      }
    });
  </script>
</body>
</html>