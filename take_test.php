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

// Check if student has completed the waiting period
if (!isset($_SESSION['waiting_start_time'])) {
    // Student hasn't started waiting yet, redirect to start test
    header('Location: start_test.php');
    exit();
}

// Calculate elapsed waiting time
$elapsed_time = time() - $_SESSION['waiting_start_time'];
$waiting_time_limit = 300; // 5 minutes in seconds

// If waiting time is not up yet, redirect back to waiting room
if ($elapsed_time < $waiting_time_limit) {
    header('Location: waiting_room.php');
    exit();
}

// Remove the student from waiting_students if still there
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

// Check if test session exists
if (!isset($_SESSION['test']) || empty($_SESSION['test'])) {
    header('Location: test.php');
    exit();
}

$test = $_SESSION['test'];
$current_question_index = isset($test['current_question']) ? $test['current_question'] : 0;

// Check if questions array exists and is not empty
if (!isset($test['questions']) || empty($test['questions'])) {
    // Test session is invalid, redirect to test.php
    unset($_SESSION['test']);
    header('Location: test.php');
    exit();
}

$questions = $test['questions'];
$total_questions = count($questions);

// Check if test is completed
if ($current_question_index >= $total_questions) {
    header('Location: test_result.php');
    exit();
}

// Handle name collection form submission
if (isset($_POST['submit_name']) && isset($_POST['student_name'])) {
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    
    // Update test session with collected name
    $_SESSION['test']['student_name'] = $student_name;
    $_SESSION['test']['name_collected'] = true;
    
    // Update user_tests table to mark test as in progress
    if (isset($_SESSION['test']['user_test_id'])) {
        $update_query = "UPDATE user_tests SET status = 'In Progress', last_accessed = NOW() WHERE id = " . (int)$_SESSION['test']['user_test_id'];
        mysqli_query($conn, $update_query);
    }
    
    // Redirect to refresh the page and start the test
    header('Location: take_test.php');
    exit();
}

// Check if student name has been collected
if (!isset($_SESSION['test']['name_collected']) || !$_SESSION['test']['name_collected']) {
    // Show name collection form
    include 'name_collection.php';
    exit();
}

// Get current question
$current_question = $questions[$current_question_index];

// Initialize question timer if not set
if (!isset($_SESSION['question_start_time'])) {
    $_SESSION['question_start_time'] = time();
}

// Calculate remaining time for current question
$elapsed_time = time() - $_SESSION['question_start_time'];
$question_time_limit = isset($current_question['time_limit']) ? (int)$current_question['time_limit'] : 60; // Default to 60 seconds if not set
$remaining_time = max(0, $question_time_limit - $elapsed_time);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (isset($_POST['answer'])) {
        // Save answer
        $test['answers'][$current_question['id']] = $_POST['answer'];
        $_SESSION['test'] = $test;
        
        // Reset question timer for next question
        unset($_SESSION['question_start_time']);
        
        // Move to next question
        $test['current_question']++;
        $_SESSION['test'] = $test;
        
        // Update user_tests table
        if (isset($_SESSION['test']['user_test_id'])) {
            $update_query = "UPDATE user_tests SET status = 'In Progress', last_accessed = NOW() WHERE id = " . (int)$_SESSION['test']['user_test_id'];
            mysqli_query($conn, $update_query);
        }
        
        // Check if test is completed
        if ($test['current_question'] >= $total_questions) {
            echo json_encode(['completed' => true]);
            exit();
        } else {
            // Get next question data
            $next_question = $questions[$test['current_question']];
            $next_index = $test['current_question'];
            
            // Check if next question is marked for review
            $isMarked = isset($test['review_questions']) && isset($test['review_questions'][$next_question['id']]);
            
            echo json_encode([
                'success' => true,
                'nextQuestion' => [
                    'id' => $next_question['id'],
                    'question' => $next_question['question'],
                    'option_a' => $next_question['option_a'],
                    'option_b' => $next_question['option_b'],
                    'option_c' => $next_question['option_c'],
                    'option_d' => $next_question['option_d'],
                    'time_limit' => isset($next_question['time_limit']) ? (int)$next_question['time_limit'] : 60,
                    'index' => $next_index,
                    'total' => $total_questions,
                    'isMarked' => $isMarked,
                    'currentAnswer' => isset($test['answers'][$next_question['id']]) ? $test['answers'][$next_question['id']] : ''
                ],
                'progress' => ($next_index / $total_questions) * 100
            ]);
            exit();
        }
    } elseif (isset($_POST['previous'])) {
        // Handle previous button
        if ($test['current_question'] > 0) {
            // Reset question timer for previous question
            unset($_SESSION['question_start_time']);
            
            $test['current_question']--;
            $_SESSION['test'] = $test;
            
            // Update user_tests table
            if (isset($_SESSION['test']['user_test_id'])) {
                $update_query = "UPDATE user_tests SET status = 'In Progress', last_accessed = NOW() WHERE id = " . (int)$_SESSION['test']['user_test_id'];
                mysqli_query($conn, $update_query);
            }
            
            // Get previous question data
            $prev_question = $questions[$test['current_question']];
            $prev_index = $test['current_question'];
            
            // Check if previous question is marked for review
            $isMarked = isset($test['review_questions']) && isset($test['review_questions'][$prev_question['id']]);
            
            echo json_encode([
                'success' => true,
                'nextQuestion' => [
                    'id' => $prev_question['id'],
                    'question' => $prev_question['question'],
                    'option_a' => $prev_question['option_a'],
                    'option_b' => $prev_question['option_b'],
                    'option_c' => $prev_question['option_c'],
                    'option_d' => $prev_question['option_d'],
                    'time_limit' => isset($prev_question['time_limit']) ? (int)$prev_question['time_limit'] : 60,
                    'index' => $prev_index,
                    'total' => $total_questions,
                    'isMarked' => $isMarked,
                    'currentAnswer' => isset($test['answers'][$prev_question['id']]) ? $test['answers'][$prev_question['id']] : ''
                ],
                'progress' => ($prev_index / $total_questions) * 100
            ]);
            exit();
        }
    } elseif (isset($_POST['mark_review'])) {
        // Mark question for review
        if (!isset($test['review_questions'])) {
            $test['review_questions'] = [];
        }
        $test['review_questions'][$current_question['id']] = true;
        $_SESSION['test'] = $test;
        
        // Reset question timer for next question
        unset($_SESSION['question_start_time']);
        
        // Move to next question
        $test['current_question']++;
        $_SESSION['test'] = $test;
        
        // Update user_tests table
        if (isset($_SESSION['test']['user_test_id'])) {
            $update_query = "UPDATE user_tests SET status = 'In Progress', last_accessed = NOW() WHERE id = " . (int)$_SESSION['test']['user_test_id'];
            mysqli_query($conn, $update_query);
        }
        
        // Check if test is completed
        if ($test['current_question'] >= $total_questions) {
            echo json_encode(['completed' => true]);
            exit();
        } else {
            // Get next question data
            $next_question = $questions[$test['current_question']];
            $next_index = $test['current_question'];
            
            // Check if next question is marked for review
            $isMarked = isset($test['review_questions']) && isset($test['review_questions'][$next_question['id']]);
            
            echo json_encode([
                'success' => true,
                'nextQuestion' => [
                    'id' => $next_question['id'],
                    'question' => $next_question['question'],
                    'option_a' => $next_question['option_a'],
                    'option_b' => $next_question['option_b'],
                    'option_c' => $next_question['option_c'],
                    'option_d' => $next_question['option_d'],
                    'time_limit' => isset($next_question['time_limit']) ? (int)$next_question['time_limit'] : 60,
                    'index' => $next_index,
                    'total' => $total_questions,
                    'isMarked' => $isMarked,
                    'currentAnswer' => isset($test['answers'][$next_question['id']]) ? $test['answers'][$next_question['id']] : ''
                ],
                'progress' => ($next_index / $total_questions) * 100
            ]);
            exit();
        }
    } elseif (isset($_POST['navigate_to'])) {
        // Navigate to specific question
        $target_index = (int)$_POST['navigate_to'];
        if ($target_index >= 0 && $target_index < $total_questions) {
            // Save current answer if provided
            if (isset($_POST['current_answer'])) {
                $test['answers'][$current_question['id']] = $_POST['current_answer'];
            }
            
            // Update marked status if provided
            if (isset($_POST['current_marked'])) {
                if ($_POST['current_marked'] === 'true') {
                    if (!isset($test['review_questions'])) {
                        $test['review_questions'] = [];
                    }
                    $test['review_questions'][$current_question['id']] = true;
                } else {
                    if (isset($test['review_questions']) && isset($test['review_questions'][$current_question['id']])) {
                        unset($test['review_questions'][$current_question['id']]);
                    }
                }
            }
            
            // Reset question timer
            unset($_SESSION['question_start_time']);
            
            // Update current question index
            $test['current_question'] = $target_index;
            $_SESSION['test'] = $test;
            
            // Update user_tests table
            if (isset($_SESSION['test']['user_test_id'])) {
                $update_query = "UPDATE user_tests SET status = 'In Progress', last_accessed = NOW() WHERE id = " . (int)$_SESSION['test']['user_test_id'];
                mysqli_query($conn, $update_query);
            }
            
            // Get target question data
            $target_question = $questions[$target_index];
            
            // Check if target question is marked for review
            $isMarked = isset($test['review_questions']) && isset($test['review_questions'][$target_question['id']]);
            
            echo json_encode([
                'success' => true,
                'nextQuestion' => [
                    'id' => $target_question['id'],
                    'question' => $target_question['question'],
                    'option_a' => $target_question['option_a'],
                    'option_b' => $target_question['option_b'],
                    'option_c' => $target_question['option_c'],
                    'option_d' => $target_question['option_d'],
                    'time_limit' => isset($target_question['time_limit']) ? (int)$target_question['time_limit'] : 60,
                    'index' => $target_index,
                    'total' => $total_questions,
                    'isMarked' => $isMarked,
                    'currentAnswer' => isset($test['answers'][$target_question['id']]) ? $test['answers'][$target_question['id']] : ''
                ],
                'progress' => ($target_index / $total_questions) * 100
            ]);
            exit();
        }
    } elseif (isset($_POST['submit_test'])) {
        // Submit the entire test
        $_SESSION['test']['completed'] = true;
        $_SESSION['test']['end_time'] = time();
        
        // Calculate score
        $correct_answers = 0;
        foreach ($questions as $question) {
            if (isset($test['answers'][$question['id']]) && $test['answers'][$question['id']] === $question['correct_answer']) {
                $correct_answers++;
            }
        }
        
        $score_percentage = ($correct_answers / $total_questions) * 100;
        $is_passed = $score_percentage >= 70; // 70% is passing score
        
        // Save test results to database
        $student_name = mysqli_real_escape_string($conn, $_SESSION['test']['student_name']);
        $test_name = mysqli_real_escape_string($conn, $_SESSION['test']['test_name']);
        $category = mysqli_real_escape_string($conn, $_SESSION['test']['category']);
        $answers_json = json_encode($test['answers']);
        
        $insert_result = "INSERT INTO test_results (
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
        ) VALUES (
            " . (int)$user_id . ", 
            '$student_name', 
            '" . (isset($test['student_id']) ? (int)$test['student_id'] : 0) . "', 
            0, 
            '$test_name', 
            '$category', 
            " . (int)$total_questions . ", 
            " . (int)$correct_answers . ", 
            " . (float)$score_percentage . ", 
            " . ($is_passed ? 1 : 0) . ", 
            '$answers_json', 
            NOW()
        )";
        
        mysqli_query($conn, $insert_result);
        $test_result_id = mysqli_insert_id($conn);
        
        // Update user_tests table
        if (isset($_SESSION['test']['user_test_id'])) {
            $status = $is_passed ? 'Completed' : 'Failed';
            $update_query = "UPDATE user_tests SET status = '$status', last_accessed = NOW() WHERE id = " . (int)$_SESSION['test']['user_test_id'];
            mysqli_query($conn, $update_query);
        }
        
        // Store test result ID in session for certificate generation
        $_SESSION['test_result_id'] = $test_result_id;
        
        echo json_encode(['completed' => true]);
        exit();
    }
    
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Check if time is up for current question
if ($remaining_time <= 0) {
    // Auto-submit with no answer if time is up
    $test['answers'][$current_question['id']] = '';
    $_SESSION['test'] = $test;
    
    // Reset question timer for next question
    unset($_SESSION['question_start_time']);
    
    // Move to next question
    $test['current_question']++;
    $_SESSION['test'] = $test;
    
    // Update user_tests table
    if (isset($_SESSION['test']['user_test_id'])) {
        $update_query = "UPDATE user_tests SET status = 'In Progress', last_accessed = NOW() WHERE id = " . (int)$_SESSION['test']['user_test_id'];
        mysqli_query($conn, $update_query);
    }
    
    // Check if test is completed
    if ($test['current_question'] >= $total_questions) {
        header('Location: test_result.php');
        exit();
    } else {
        // Redirect to next question
        header('Location: take_test.php');
        exit();
    }
}

// Check if secure environment has been shown
$showSecureEnvironment = !isset($_SESSION['secure_environment_shown']) || !$_SESSION['secure_environment_shown'];
if ($showSecureEnvironment) {
    // Mark that secure environment has been shown
    $_SESSION['secure_environment_shown'] = true;
}

// Check if question is marked for review
$isMarked = isset($test['review_questions']) && isset($test['review_questions'][$current_question['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Take Test - KAcademyX</title>
  <meta name="description" content="Take MCQ test at KAcademyX">
  <meta name="keywords" content="KAcademyX, test, MCQ, online examination">
  
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
      --danger-color: #e74c3c;
      --warning-color: #f39c12;
      --success-color: #2ecc71;
      --review-color: #9b59b6;
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
    }
    
    h1, h2, h3, h4, h5, h6 {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
    }
    
    /* Test Container */
    .test-container {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
      position: relative;
    }
    
    /* Test Header */
    .test-header {
      background: white;
      padding: 15px 0;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      position: sticky;
      top: 0;
      z-index: 100;
      border-bottom: 3px solid var(--primary-color);
    }
    
    .test-header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .test-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--dark-color);
      margin: 0;
      display: flex;
      align-items: center;
    }
    
    .test-title i {
      margin-right: 10px;
      color: var(--primary-color);
    }
    
    .student-info {
      display: flex;
      align-items: center;
      background: var(--light-color);
      padding: 8px 15px;
      border-radius: 50px;
      font-weight: 600;
      color: var(--dark-color);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .student-info i {
      margin-right: 8px;
      color: var(--primary-color);
    }
    
    .test-timer {
      display: flex;
      align-items: center;
      background: var(--primary-color);
      color: white;
      padding: 8px 20px;
      border-radius: 50px;
      font-weight: 600;
      box-shadow: 0 4px 10px rgba(65, 84, 241, 0.3);
      transition: all 0.3s ease;
      margin-left: 15px;
    }
    
    .test-timer:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(65, 84, 241, 0.4);
    }
    
    .test-timer i {
      margin-right: 8px;
    }
    
    .timer-warning {
      background: var(--danger-color) !important;
      animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    
    /* Test Progress */
    .test-progress {
      padding: 20px 0;
      background: white;
      margin-bottom: 20px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .progress {
      height: 12px;
      border-radius: 10px;
      background: #e2e8f0;
      overflow: visible;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .progress-bar {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      border-radius: 10px;
      position: relative;
      transition: width 0.6s ease;
    }
    
    .progress-bar::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(255,255,255,0.2) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0.2) 75%, transparent 75%, transparent);
      background-size: 30px 30px;
      animation: progress-animation 2s linear infinite;
    }
    
    @keyframes progress-animation {
      0% { background-position: 0 0; }
      100% { background-position: 30px 30px; }
    }
    
    .progress-info {
      display: flex;
      justify-content: space-between;
      margin-top: 10px;
      font-size: 0.9rem;
      color: #64748b;
    }
    
    /* Main Content Area */
    .main-content {
      flex: 1;
      overflow-y: auto;
      padding-bottom: 30px;
    }
    
    /* Question Card */
    .question-card {
      background: white;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      margin-bottom: 20px;
      border: 1px solid #e2e8f0;
      transition: all 0.3s ease;
      position: relative;
    }
    
    .question-card:hover {
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .question-number {
      display: inline-block;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      text-align: center;
      line-height: 40px;
      font-weight: 700;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(65, 84, 241, 0.3);
    }
    
    .question-text {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 25px;
      color: var(--dark-color);
      line-height: 1.5;
    }
    
    .options-container {
      margin-bottom: 25px;
    }
    
    .option-item {
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 15px;
      padding: 18px 20px;
      margin-bottom: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
    }
    
    .option-item::before {
      content: "";
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(65, 84, 241, 0.1), transparent);
      transition: left 0.5s ease;
    }
    
    .option-item:hover::before {
      left: 100%;
    }
    
    .option-item:hover {
      border-color: var(--primary-color);
      background: rgba(65, 84, 241, 0.05);
      transform: translateY(-2px);
    }
    
    .option-item input[type="radio"] {
      margin-right: 15px;
      transform: scale(1.3);
      accent-color: var(--primary-color);
      position: relative;
      z-index: 2;
    }
    
    .option-item label {
      cursor: pointer;
      margin: 0;
      font-weight: 500;
      position: relative;
      z-index: 2;
    }
    
    .option-item.selected {
      border-color: var(--primary-color);
      background: rgba(65, 84, 241, 0.1);
      box-shadow: 0 4px 12px rgba(65, 84, 241, 0.15);
    }
    
    /* Navigation Buttons */
    .test-navigation {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
      position: sticky;
      bottom: 0;
      background: rgba(255, 255, 255, 0.9);
      padding: 15px 0;
      border-top: 1px solid #e2e8f0;
      backdrop-filter: blur(5px);
      z-index: 50;
    }
    
    .btn-nav {
      padding: 12px 30px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-nav i {
      margin: 0 8px;
    }
    
    .btn-prev {
      background: #e2e8f0;
      color: var(--dark-color);
    }
    
    .btn-prev:hover {
      background: #cbd5e1;
      transform: translateY(-2px);
    }
    
    .btn-next {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
    }
    
    .btn-next:hover {
      transform: translateY(-3px);
      box-shadow: 0 7px 20px rgba(65, 84, 241, 0.5);
      color: white;
    }
    
    .btn-next:disabled {
      background: #cbd5e1;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .btn-review {
      background: linear-gradient(45deg, var(--review-color), #8e44ad);
      color: white;
      box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3);
      margin-right: 10px;
    }
    
    .btn-review:hover {
      transform: translateY(-3px);
      box-shadow: 0 7px 20px rgba(155, 89, 182, 0.5);
      color: white;
    }
    
    /* Anti-cheating Notice */
    .anti-cheating {
      background: linear-gradient(45deg, #fff3cd, #ffeaa7);
      border: 1px solid #ffeaa7;
      color: #856404;
      padding: 15px 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      box-shadow: 0 4px 10px rgba(243, 156, 18, 0.2);
    }
    
    .anti-cheating i {
      font-size: 1.5rem;
      margin-right: 15px;
      color: var(--warning-color);
    }
    
    /* Fullscreen Overlay */
    .fullscreen-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.9);
      z-index: 9999;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
    }
    
    .warning-icon {
      font-size: 4rem;
      color: var(--danger-color);
      margin-bottom: 20px;
      animation: bounce 1s infinite;
    }
    
    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-20px); }
    }
    
    .warning-text {
      font-size: 1.5rem;
      margin-bottom: 30px;
      text-align: center;
      max-width: 600px;
      line-height: 1.5;
    }
    
    .btn-return {
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 12px 30px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
    }
    
    .btn-return:hover {
      background: #3141c5;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(65, 84, 241, 0.5);
    }
    
    /* Lock Screen */
    .lock-screen {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #1a1a2e, #16213e);
      z-index: 10000;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
      text-align: center;
      padding: 20px;
    }
    
    .lock-screen h1 {
      font-size: 2.5rem;
      margin-bottom: 20px;
      background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .lock-screen p {
      font-size: 1.2rem;
      max-width: 600px;
      margin-bottom: 30px;
      line-height: 1.6;
    }
    
    .btn-start {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.2rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
    }
    
    .btn-start:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(65, 84, 241, 0.5);
    }
    
    .lock-icon {
      font-size: 5rem;
      color: var(--primary-color);
      margin-bottom: 30px;
      animation: pulse 2s infinite;
    }
    
    /* Question Timer */
    .question-timer {
      position: absolute;
      top: 20px;
      right: 20px;
      background: rgba(0, 0, 0, 0.7);
      color: white;
      padding: 10px 15px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      z-index: 10;
    }
    
    .question-timer i {
      margin-right: 8px;
      color: var(--primary-color);
    }
    
    .question-timer.warning {
      background: rgba(231, 76, 60, 0.9);
      animation: pulse 1.5s infinite;
    }
    
    /* Question Palette */
    .question-palette {
      position: fixed;
      top: 50%;
      right: 20px;
      transform: translateY(-50%);
      background: white;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
      z-index: 50;
      max-height: 80vh;
      overflow-y: auto;
      display: none;
    }
    
    .question-palette-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .question-palette-title {
      font-weight: 700;
      color: var(--dark-color);
      margin: 0;
    }
    
    .question-palette-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #64748b;
    }
    
    .question-palette-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 10px;
    }
    
    .question-palette-item {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      background: #f1f5f9;
      color: var(--dark-color);
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .question-palette-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .question-palette-item.current {
      background: var(--primary-color);
      color: white;
      box-shadow: 0 4px 8px rgba(65, 84, 241, 0.3);
    }
    
    .question-palette-item.answered {
      background: var(--success-color);
      color: white;
    }
    
    .question-palette-item.marked {
      background: var(--review-color);
      color: white;
    }
    
    .palette-toggle {
      position: fixed;
      top: 50%;
      right: 0;
      transform: translateY(-50%);
      background: var(--primary-color);
      color: white;
      width: 40px;
      height: 40px;
      border-radius: 10px 0 0 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 50;
      box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    /* Mark for Review */
    .mark-review-container {
      display: flex;
      align-items: center;
      margin-top: 15px;
    }
    
    .mark-review-checkbox {
      margin-right: 10px;
      transform: scale(1.3);
      accent-color: var(--review-color);
    }
    
    .mark-review-label {
      font-weight: 500;
      color: var(--review-color);
    }
    
    /* Loading Overlay */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }
    
    .spinner {
      width: 50px;
      height: 50px;
      border: 5px solid rgba(65, 84, 241, 0.2);
      border-radius: 50%;
      border-top: 5px solid var(--primary-color);
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Confirmation Modal */
    .submit-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10000;
    }
    
    .submit-modal-content {
      background: white;
      border-radius: 15px;
      padding: 30px;
      max-width: 500px;
      width: 90%;
      text-align: center;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .submit-modal-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--dark-color);
    }
    
    .submit-modal-text {
      margin-bottom: 25px;
      color: #64748b;
    }
    
    .submit-modal-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
    }
    
    .btn-cancel {
      background: #e2e8f0;
      color: var(--dark-color);
      padding: 10px 25px;
      border-radius: 50px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .btn-cancel:hover {
      background: #cbd5e1;
    }
    
    .btn-confirm {
      background: var(--primary-color);
      color: white;
      padding: 10px 25px;
      border-radius: 50px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .btn-confirm:hover {
      background: #3141c5;
    }
    
    /* Responsive Design */
    @media (max-width: 767.98px) {
      .test-header .container {
        flex-direction: column;
        text-align: center;
      }
      
      .test-title {
        margin-bottom: 15px;
      }
      
      .student-info {
        margin-bottom: 10px;
      }
      
      .question-card {
        padding: 20px;
      }
      
      .question-text {
        font-size: 1.1rem;
      }
      
      .test-navigation {
        flex-direction: column;
        gap: 15px;
      }
      
      .btn-nav {
        width: 100%;
      }
      
      .lock-screen h1 {
        font-size: 2rem;
      }
      
      .lock-screen p {
        font-size: 1rem;
      }
      
      .question-timer {
        position: static;
        margin-bottom: 15px;
        justify-content: center;
      }
      
      .question-palette {
        right: 10px;
        left: 10px;
        width: auto;
      }
      
      .question-palette-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }
  </style>
</head>
<body>
  <?php if ($showSecureEnvironment): ?>
  <!-- Lock Screen - Only shown once -->
  <div class="lock-screen" id="lock-screen">
    <i class="bi bi-lock-fill lock-icon"></i>
    <h1>Secure Test Environment</h1>
    <p>You're about to enter a secure test environment. Once started, you must remain in this window. Switching tabs or attempting to leave will terminate your test.</p>
    <p><strong>Test Guidelines:</strong></p>
    <ul style="text-align: left; max-width: 500px; margin: 0 auto 20px;">
      <li>Do not refresh the page or navigate away</li>
      <li>Do not use any external resources</li>
      <li>Do not right-click or try to open developer tools</li>
      <li>Answer all questions within the time limit</li>
    </ul>
    <button class="btn-start" id="start-test-btn">Start Test Now</button>
  </div>
  <?php endif; ?>
  
  <div class="test-container" id="test-container" <?php echo $showSecureEnvironment ? 'style="display: none;"' : ''; ?>>
    <!-- Test Header -->
    <header class="test-header">
      <div class="container">
        <h1 class="test-title">
          <i class="bi bi-pencil-square"></i>
          <?php echo htmlspecialchars($test['category']); ?> Test
        </h1>
        <div class="d-flex align-items-center">
          <div class="student-info">
            <i class="bi bi-person-circle"></i>
            <?php echo htmlspecialchars($_SESSION['test']['student_name']); ?>
          </div>
          <div class="test-timer" id="timer">
            <i class="bi bi-clock-fill"></i>
            <span id="time-remaining"><?php echo gmdate("i:s", $remaining_time); ?></span>
          </div>
        </div>
      </div>
    </header>
    
    <!-- Test Progress -->
    <div class="test-progress">
      <div class="container">
        <div class="progress">
          <div class="progress-bar animate__animated animate__fadeInRight" role="progressbar" style="width: <?php echo ($current_question_index / $total_questions) * 100; ?>%" aria-valuenow="<?php echo ($current_question_index / $total_questions) * 100; ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="progress-info">
          <span>Question <?php echo $current_question_index + 1; ?> of <?php echo $total_questions; ?></span>
          <span><?php echo round(($current_question_index / $total_questions) * 100); ?>% Complete</span>
        </div>
      </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
      <div class="container mb-4">
        <!-- Anti-cheating Notice -->
        <div class="anti-cheating animate__animated animate__fadeIn">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div>
            <strong>Warning:</strong> Do not refresh the page, navigate away, or use external resources during the test. Such actions may result in automatic submission or disqualification.
          </div>
        </div>
        
        <!-- Question Card -->
        <div class="question-card animate__animated animate__fadeInUp">
          <div class="question-timer <?php echo $remaining_time < 10 ? 'warning' : ''; ?>" id="question-timer">
            <i class="bi bi-hourglass-split"></i>
            <span id="question-time-remaining"><?php echo $remaining_time; ?>s</span>
          </div>
          
          <div class="question-number"><?php echo $current_question_index + 1; ?></div>
          <div class="question-text"><?php echo htmlspecialchars($current_question['question']); ?></div>
          
          <form method="post" action="" id="test-form">
            <input type="hidden" name="ajax" value="true">
            <div class="options-container">
              <div class="option-item" onclick="selectOption(this, 'A')">
                <input type="radio" id="option_a" name="answer" value="A" <?php echo (isset($test['answers'][$current_question['id']]) && $test['answers'][$current_question['id']] === 'A') ? 'checked' : ''; ?>>
                <label for="option_a"><?php echo htmlspecialchars($current_question['option_a']); ?></label>
              </div>
              <div class="option-item" onclick="selectOption(this, 'B')">
                <input type="radio" id="option_b" name="answer" value="B" <?php echo (isset($test['answers'][$current_question['id']]) && $test['answers'][$current_question['id']] === 'B') ? 'checked' : ''; ?>>
                <label for="option_b"><?php echo htmlspecialchars($current_question['option_b']); ?></label>
              </div>
              <div class="option-item" onclick="selectOption(this, 'C')">
                <input type="radio" id="option_c" name="answer" value="C" <?php echo (isset($test['answers'][$current_question['id']]) && $test['answers'][$current_question['id']] === 'C') ? 'checked' : ''; ?>>
                <label for="option_c"><?php echo htmlspecialchars($current_question['option_c']); ?></label>
              </div>
              <div class="option-item" onclick="selectOption(this, 'D')">
                <input type="radio" id="option_d" name="answer" value="D" <?php echo (isset($test['answers'][$current_question['id']]) && $test['answers'][$current_question['id']] === 'D') ? 'checked' : ''; ?>>
                <label for="option_d"><?php echo htmlspecialchars($current_question['option_d']); ?></label>
              </div>
            </div>
            
            <div class="mark-review-container">
              <input type="checkbox" id="mark_review" name="mark_review" class="mark-review-checkbox" <?php echo $isMarked ? 'checked' : ''; ?>>
              <label for="mark_review" class="mark-review-label">Mark for review</label>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Navigation Buttons - Moved outside container to make it sticky -->
      <div class="test-navigation">
        <div class="container">
          <div class="d-flex justify-content-between">
            <button type="button" name="previous" class="btn-nav btn-prev" <?php echo $current_question_index === 0 ? 'disabled' : ''; ?> onclick="navigateQuestion('previous')">
              <i class="bi bi-arrow-left"></i> Previous
            </button>
            <div>
              <button type="button" name="mark_review" class="btn-nav btn-review" onclick="navigateQuestion('mark_review')">
                Mark & Next <i class="bi bi-bookmark-plus"></i>
              </button>
              <button type="button" name="next" class="btn-nav btn-next" id="next-btn" onclick="handleNextButtonClick()">
                <?php echo $current_question_index === $total_questions - 1 ? 'Submit Test' : 'Next Question'; ?>
                <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loading-overlay" style="display: none;">
    <div class="spinner"></div>
  </div>
  
  <!-- Question Palette Toggle -->
  <div class="palette-toggle" id="palette-toggle">
    <i class="bi bi-grid-3x3-gap"></i>
  </div>
  
  <!-- Question Palette -->
  <div class="question-palette" id="question-palette">
    <div class="question-palette-header">
      <h3 class="question-palette-title">Question Palette</h3>
      <button class="question-palette-close" id="palette-close">&times;</button>
    </div>
    <div class="question-palette-grid">
      <?php for ($i = 0; $i < $total_questions; $i++): 
        $isAnswered = isset($test['answers'][$questions[$i]['id']]) && $test['answers'][$questions[$i]['id']] !== '';
        $isMarked = isset($test['review_questions']) && isset($test['review_questions'][$questions[$i]['id']]);
        $isCurrent = $i === $current_question_index;
      ?>
        <div class="question-palette-item <?php echo $isCurrent ? 'current' : ''; ?> <?php echo $isAnswered ? 'answered' : ''; ?> <?php echo $isMarked ? 'marked' : ''; ?>" 
             data-question="<?php echo $i + 1; ?>" data-index="<?php echo $i; ?>" onclick="navigateToQuestion(<?php echo $i; ?>)">
          <?php echo $i + 1; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
  
  <!-- Submit Confirmation Modal -->
  <div class="submit-modal" id="submit-modal" style="display: none;">
    <div class="submit-modal-content">
      <h3 class="submit-modal-title">Submit Test</h3>
      <p class="submit-modal-text">Are you sure you want to submit your test? You will not be able to make any changes after submission.</p>
      <div class="submit-modal-buttons">
        <button class="btn-cancel" onclick="closeSubmitModal()">Cancel</button>
        <button class="btn-confirm" onclick="submitTest()">Submit Test</button>
      </div>
    </div>
  </div>
  
  <!-- Fullscreen Overlay for Cheating Detection -->
  <div class="fullscreen-overlay" id="cheating-overlay" style="display: none;">
    <i class="bi bi-exclamation-triangle-fill warning-icon"></i>
    <div class="warning-text">
      <strong>Test Violation Detected!</strong><br>
      You have attempted to navigate away from the test or refresh the page. This action has been recorded.
    </div>
    <button class="btn-return" onclick="returnToTest()">Return to Test</button>
  </div>
  
  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Main JS File -->
  <script>
    // Test environment variables
    let testStarted = false;
    let timeRemaining = <?php echo $remaining_time; ?>;
    const timerElement = document.getElementById('timer');
    const timeRemainingElement = document.getElementById('time-remaining');
    const questionTimerElement = document.getElementById('question-timer');
    const questionTimeRemainingElement = document.getElementById('question-time-remaining');
    let violationCount = 0;
    let allowNavigation = false;
    let timerInterval;
    let currentQuestionIndex = <?php echo $current_question_index; ?>;
    let totalQuestions = <?php echo $total_questions; ?>;
    
    <?php if ($showSecureEnvironment): ?>
    // Start test button - only shown once
    document.getElementById('start-test-btn').addEventListener('click', function() {
      // Request fullscreen first
      const requestFullscreen = (element) => {
        if (element.requestFullscreen) {
          return element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
          return element.webkitRequestFullscreen();
        } else if (element.msRequestFullscreen) {
          return element.msRequestFullscreen();
        }
        return Promise.reject(new Error('Fullscreen API is not supported'));
      };
      
      // Request fullscreen and then proceed
      requestFullscreen(document.documentElement)
        .then(() => {
          // Hide lock screen and show test
          document.getElementById('lock-screen').style.display = 'none';
          document.getElementById('test-container').style.display = 'flex';
          
          // Start test
          testStarted = true;
          startQuestionTimer();
          
          // Start monitoring user activity
          startActivityMonitoring();
        })
        .catch(err => {
          console.error('Fullscreen error:', err);
          // Still proceed even if fullscreen fails
          document.getElementById('lock-screen').style.display = 'none';
          document.getElementById('test-container').style.display = 'flex';
          
          // Start test
          testStarted = true;
          startQuestionTimer();
          
          // Start monitoring user activity
          startActivityMonitoring();
        });
    });
    <?php else: ?>
    // If lock screen was already shown, start the test directly
    document.addEventListener('DOMContentLoaded', function() {
      // Try to enter fullscreen (if not already)
      if (!document.fullscreenElement) {
        const requestFullscreen = (element) => {
          if (element.requestFullscreen) {
            return element.requestFullscreen();
          } else if (element.webkitRequestFullscreen) {
            return element.webkitRequestFullscreen();
          } else if (element.msRequestFullscreen) {
            return element.msRequestFullscreen();
          }
          return Promise.reject(new Error('Fullscreen API is not supported'));
        };
        
        requestFullscreen(document.documentElement).catch(err => {
          console.error('Fullscreen error:', err);
        });
      }
      
      // Start test
      testStarted = true;
      startQuestionTimer();
      
      // Start monitoring user activity
      startActivityMonitoring();
    });
    <?php endif; ?>
    
    // Question timer functionality
    function startQuestionTimer() {
      timerInterval = setInterval(() => {
        timeRemaining--;
        
        if (timeRemaining <= 0) {
          clearInterval(timerInterval);
          // Auto submit when time is up
          allowNavigation = true;
          submitAnswer();
        }
        
        // Update timer display
        questionTimeRemainingElement.textContent = timeRemaining + 's';
        
        // Add warning class when less than 10 seconds remain
        if (timeRemaining < 10) {
          questionTimerElement.classList.add('warning');
        }
      }, 1000);
    }
    
    // Option selection
    function selectOption(element, value) {
      // Remove selected class from all options
      document.querySelectorAll('.option-item').forEach(item => {
        item.classList.remove('selected');
      });
      
      // Add selected class to clicked option
      element.classList.add('selected');
      
      // Check the radio button
      document.getElementById(`option_${value.toLowerCase()}`).checked = true;
    }
    
    // Initialize selected option if already answered
    document.addEventListener('DOMContentLoaded', () => {
      const selectedOption = document.querySelector('input[name="answer"]:checked');
      if (selectedOption) {
        const optionItem = selectedOption.closest('.option-item');
        optionItem.classList.add('selected');
      }
      
      // Setup question palette toggle
      document.getElementById('palette-toggle').addEventListener('click', () => {
        document.getElementById('question-palette').style.display = 'block';
      });
      
      document.getElementById('palette-close').addEventListener('click', () => {
        document.getElementById('question-palette').style.display = 'none';
      });
    });
    
    // Navigate to specific question
    function navigateToQuestion(index) {
      // Save current answer and marked status
      const currentAnswer = document.querySelector('input[name="answer"]:checked')?.value || '';
      const isMarked = document.getElementById('mark_review').checked;
      
      // Show loading overlay
      document.getElementById('loading-overlay').style.display = 'flex';
      
      // Create form data
      const formData = new FormData();
      formData.append('ajax', 'true');
      formData.append('navigate_to', index);
      formData.append('current_answer', currentAnswer);
      formData.append('current_marked', isMarked);
      
      // Send AJAX request
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          updateQuestion(data.nextQuestion, data.progress);
          document.getElementById('loading-overlay').style.display = 'none';
        } else {
          alert('Error navigating to question. Please try again.');
          document.getElementById('loading-overlay').style.display = 'none';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error navigating to question. Please try again.');
        document.getElementById('loading-overlay').style.display = 'none';
      });
    }
    
    // Start monitoring user activity
    function startActivityMonitoring() {
      // Monitor user inactivity
      let inactivityTime = 0;
      const inactivityInterval = setInterval(() => {
        inactivityTime += 1;
        if (inactivityTime > 300) { // 5 minutes of inactivity
          alert('You have been inactive for too long. Your test will be submitted.');
          allowNavigation = true;
          submitAnswer();
        }
      }, 1000);
      
      // Reset inactivity timer on user activity
      document.addEventListener('mousemove', () => {
        inactivityTime = 0;
      });
      
      document.addEventListener('keypress', () => {
        inactivityTime = 0;
      });
    }
    
    // Handle next button click
    function handleNextButtonClick() {
      if (currentQuestionIndex === totalQuestions - 1) {
        // Show confirmation modal for test submission
        document.getElementById('submit-modal').style.display = 'flex';
      } else {
        // Navigate to next question
        navigateQuestion('next');
      }
    }
    
    // Close submit modal
    function closeSubmitModal() {
      document.getElementById('submit-modal').style.display = 'none';
    }
    
    // Submit the entire test
    function submitTest() {
      // Show loading overlay
      document.getElementById('loading-overlay').style.display = 'flex';
      document.getElementById('submit-modal').style.display = 'none';
      
      // Create form data
      const formData = new FormData();
      formData.append('ajax', 'true');
      formData.append('submit_test', 'true');
      
      // Send AJAX request
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.completed) {
          // Test is completed, redirect to results
          allowNavigation = true;
          window.location.href = 'test_result.php';
        } else {
          alert('Error submitting test. Please try again.');
          document.getElementById('loading-overlay').style.display = 'none';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error submitting test. Please try again.');
        document.getElementById('loading-overlay').style.display = 'none';
      });
    }
    
    // Submit answer via AJAX
    function submitAnswer(action = 'next') {
      const formData = new FormData(document.getElementById('test-form'));
      formData.append(action, 'true');
      
      // Show loading overlay
      document.getElementById('loading-overlay').style.display = 'flex';
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.completed) {
          // Test is completed, redirect to results
          allowNavigation = true;
          window.location.href = 'test_result.php';
        } else if (data.success) {
          // Update the question without reloading the page
          updateQuestion(data.nextQuestion, data.progress);
          document.getElementById('loading-overlay').style.display = 'none';
        } else {
          alert('Error submitting answer. Please try again.');
          document.getElementById('loading-overlay').style.display = 'none';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error submitting answer. Please try again.');
        document.getElementById('loading-overlay').style.display = 'none';
      });
    }
    
    // Update question content without reloading the page
    function updateQuestion(questionData, progress) {
      // Update question number and text
      document.querySelector('.question-number').textContent = questionData.index + 1;
      document.querySelector('.question-text').textContent = questionData.question;
      
      // Update options
      const options = ['a', 'b', 'c', 'd'];
      options.forEach(opt => {
        const optionElement = document.getElementById(`option_${opt}`);
        const labelElement = optionElement.nextElementSibling;
        labelElement.textContent = questionData[`option_${opt}`];
        
        // Reset selection
        const optionItem = optionElement.closest('.option-item');
        optionItem.classList.remove('selected');
        
        // Check if this option was previously selected
        if (questionData.currentAnswer === opt.toUpperCase()) {
          optionElement.checked = true;
          optionItem.classList.add('selected');
        } else {
          optionElement.checked = false;
        }
      });
      
      // Update progress bar
      document.querySelector('.progress-bar').style.width = progress + '%';
      document.querySelector('.progress-bar').setAttribute('aria-valuenow', progress);
      document.querySelector('.progress-info span:first-child').textContent = `Question ${questionData.index + 1} of ${questionData.total}`;
      document.querySelector('.progress-info span:last-child').textContent = `${Math.round(progress)}% Complete`;
      
      // Update the "Mark for review" checkbox
      document.getElementById('mark_review').checked = questionData.isMarked;
      
      // Update the question palette
      updateQuestionPalette(questionData.index);
      
      // Reset and start the timer for the new question
      clearInterval(timerInterval);
      timeRemaining = questionData.time_limit;
      questionTimeRemainingElement.textContent = timeRemaining + 's';
      questionTimerElement.classList.remove('warning');
      startQuestionTimer();
      
      // Update the next button text
      const nextBtn = document.getElementById('next-btn');
      if (questionData.index === questionData.total - 1) {
        nextBtn.innerHTML = 'Submit Test <i class="bi bi-arrow-right"></i>';
      } else {
        nextBtn.innerHTML = 'Next Question <i class="bi bi-arrow-right"></i>';
      }
      
      // Update the previous button state
      const prevBtn = document.querySelector('.btn-prev');
      if (questionData.index === 0) {
        prevBtn.disabled = true;
      } else {
        prevBtn.disabled = false;
      }
      
      // Update current question index
      currentQuestionIndex = questionData.index;
      
      // Scroll to top of question card
      document.querySelector('.question-card').scrollIntoView({ behavior: 'smooth' });
    }
    
    // Update question palette to reflect current state
    function updateQuestionPalette(currentIndex) {
      const paletteItems = document.querySelectorAll('.question-palette-item');
      
      paletteItems.forEach((item, index) => {
        // Remove all state classes
        item.classList.remove('current', 'answered', 'marked');
        
        // Add appropriate classes based on state
        if (index === currentIndex) {
          item.classList.add('current');
        }
        
        // We don't have direct access to answered/marked state for all questions
        // So we'll keep the existing classes and just update the current one
      });
    }
    
    // Navigate between questions
    function navigateQuestion(action) {
      if (action === 'previous') {
        // For previous, we need to reload the page
        allowNavigation = true;
        const form = document.createElement('form');
        form.method = 'post';
        form.action = '';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'previous';
        input.value = 'true';
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      } else {
        // For next and mark_review, use AJAX
        submitAnswer(action);
      }
    }
    
    // Allow form submission without warning
    document.getElementById('test-form').addEventListener('submit', function(e) {
      e.preventDefault();
      allowNavigation = true;
      submitAnswer('next');
    });
    
    // Prevent going back using browser back button
    history.pushState(null, null, document.URL);
    window.addEventListener('popstate', function() {
      history.pushState(null, null, document.URL);
      if (testStarted) showCheatingWarning();
    });
    
    // Detect page visibility changes (tab switching)
    document.addEventListener('visibilitychange', () => {
      if (testStarted && document.hidden) {
        showCheatingWarning();
      }
    });
    
    // Detect window blur (switching to another window)
    window.addEventListener('blur', () => {
      setTimeout(() => {
        if (testStarted && document.hidden) {
          showCheatingWarning();
        }
      }, 1000);
    });
    
    // Detect fullscreen change
    document.addEventListener('fullscreenchange', () => {
      if (testStarted && !document.fullscreenElement) {
        showCheatingWarning();
      }
    });
    
    // Show cheating warning
    function showCheatingWarning() {
      violationCount++;
      document.getElementById('cheating-overlay').style.display = 'flex';
      
      // After 3 violations, automatically submit the test
      if (violationCount >= 3) {
        alert('Multiple violations detected. Your test is being submitted.');
        allowNavigation = true;
        submitTest();
      }
    }
    
    // Return to test function
    function returnToTest() {
      document.getElementById('cheating-overlay').style.display = 'none';
      window.focus();
      
      // Try to re-enter fullscreen
      if (document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(err => {
          console.error(`Error attempting to re-enable fullscreen: ${err.message}`);
        });
      } else if (document.documentElement.webkitRequestFullscreen) { /* Safari */
        document.documentElement.webkitRequestFullscreen();
      } else if (document.documentElement.msRequestFullscreen) { /* IE11 */
        document.documentElement.msRequestFullscreen();
      }
    }
    
    // Prevent right-click to discourage cheating
    document.addEventListener('contextmenu', (e) => {
      e.preventDefault();
    });
    
    // Disable copy-paste
    document.addEventListener('keydown', (e) => {
      // Disable Ctrl+C, Ctrl+X, Ctrl+V
      if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'x' || e.key === 'v')) {
        e.preventDefault();
      }
      
      // Disable F12 (Developer Tools)
      if (e.key === 'F12') {
        e.preventDefault();
      }
      
      // Disable Alt+Tab
      if (e.altKey && e.key === 'Tab') {
        e.preventDefault();
      }
    });
    
    // Prevent print screen
    document.addEventListener('keyup', (e) => {
      if (e.key === 'PrintScreen') {
        navigator.clipboard.writeText('');
        alert('Screenshots are disabled during the test.');
      }
    });
    
    // Handle beforeunload event - only show warning when not allowing navigation
    window.addEventListener('beforeunload', (e) => {
      if (testStarted && !allowNavigation) {
        e.preventDefault();
        e.returnValue = '';
        return '';
      }
    });
    
    // Prevent window from being closed
    window.addEventListener('unload', () => {
      if (testStarted && !allowNavigation) {
        // Submit the test using fetch with keepalive
        const formData = new FormData(document.getElementById('test-form'));
        formData.append('auto_submit', 'true');
        
        navigator.sendBeacon('submit_test.php', formData);
      }
    });
  </script>
</body>
</html>