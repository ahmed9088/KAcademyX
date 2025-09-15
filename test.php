<?php
session_start();
require_once 'forms/db.php'; // Database connection file

// Check if user is logged in - using the same session variable as login.php
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

// Check if user wants to clear the current test session
if (isset($_GET['clear_test']) && $_GET['clear_test'] == '1') {
    unset($_SESSION['test']);
    unset($_SESSION['secure_environment_shown']);
    unset($_SESSION['question_start_time']);
    header('Location: test.php');
    exit();
}

// Get user ID from session
$user_id = $_SESSION["id"];

// Get user information from users table
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

if (!$user) {
    // User not found, redirect to login
    header('Location: forms/login.php');
    exit();
}

// Set session user for navbar display (using name as in login.php)
$_SESSION['user'] = $user['name'];

// Get student information
$student_query = "SELECT * FROM students WHERE user_id = $user_id";
$student_result = mysqli_query($conn, $student_query);
$student = mysqli_fetch_assoc($student_result);

// If student record doesn't exist, create one
if (!$student) {
    $username = mysqli_real_escape_string($conn, $user['username'] ?? '');
    $name = mysqli_real_escape_string($conn, $user['name']);
    $email = mysqli_real_escape_string($conn, $user['email']);
    
    $insert_student = "INSERT INTO students (user_id, username, name, email) 
                      VALUES ($user_id, '$username', '$name', '$email')";
    mysqli_query($conn, $insert_student);
    
    // Get the newly created student record
    $student_result = mysqli_query($conn, $student_query);
    $student = mysqli_fetch_assoc($student_result);
}

// Check if student is already in a test session
if (isset($_SESSION['test'])) {
    // Check if the test session is recent (within the last 24 hours)
    $test_time = isset($_SESSION['test']['start_time']) ? $_SESSION['test']['start_time'] : time();
    $current_time = time();
    $time_diff = $current_time - $test_time;
    
    // If test is completed, redirect to results
    if ($_SESSION['test']['current_question'] >= count($_SESSION['test']['questions'])) {
        header('Location: test_result.php');
        exit();
    }
    
    // If test is in progress and recent (less than 24 hours old), redirect to take test
    if ($time_diff < 86400) { // 86400 seconds = 24 hours
        // Show a notification that there's an existing test session
        $existing_test_category = $_SESSION['test']['category'];
        $existing_test_questions = count($_SESSION['test']['questions']);
        $existing_test_progress = $_SESSION['test']['current_question'];
        
        // Display a message about the existing test session
        $show_existing_test_notice = true;
    } else {
        // Test session is too old, clear it
        unset($_SESSION['test']);
        unset($_SESSION['secure_environment_shown']);
        unset($_SESSION['question_start_time']);
    }
}

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

// Remove the current student from waiting_students (in case they are still there)
$session_id = session_id();
$delete_query = "DELETE FROM waiting_students WHERE session_id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("s", $session_id);
$stmt->execute();
$stmt->close();

// Fetch distinct categories (subjects) from mcq_questions
$categories_query = "SELECT DISTINCT category FROM mcq_questions WHERE category IS NOT NULL ORDER BY category";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
if ($categories_result && mysqli_num_rows($categories_result) > 0) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row['category'];
    }
}

// Get user's test history for tracking
$test_history_query = "SELECT tr.*, 
                        CASE WHEN tr.is_passed = 1 THEN 'Passed' ELSE 'Failed' END as status,
                        CASE WHEN tr.certificate_generated = 1 THEN 'Available' ELSE 'Not Generated' END as certificate_status
                        FROM test_results tr 
                        WHERE tr.user_id = $user_id 
                        ORDER BY tr.test_date DESC 
                        LIMIT 5";
$test_history_result = mysqli_query($conn, $test_history_query);
$test_history = [];
if ($test_history_result && mysqli_num_rows($test_history_result) > 0) {
    while ($row = mysqli_fetch_assoc($test_history_result)) {
        $test_history[] = $row;
    }
}

// Handle test start request
if (isset($_POST['start_test']) && isset($_POST['category'])) {
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    
    // Fetch questions for the selected category
    $questions_query = "SELECT * FROM mcq_questions WHERE category = '$category' ORDER BY RAND() LIMIT 10";
    $questions_result = mysqli_query($conn, $questions_query);
    
    if ($questions_result && mysqli_num_rows($questions_result) > 0) {
        $questions = [];
        while ($row = mysqli_fetch_assoc($questions_result)) {
            $questions[] = $row;
        }
        
        // Initialize test session with name collection step
        $_SESSION['test'] = [
            'category' => $category,
            'questions' => $questions,
            'current_question' => 0,
            'answers' => [],
            'student_id' => $student['id'],
            'user_id' => $user_id,
            'name_collected' => false,
            'test_name' => 'Test in ' . $category,
            'start_time' => time() // Add timestamp to track when test started
        ];
        
        // Mark that secure environment hasn't been shown yet
        $_SESSION['secure_environment_shown'] = false;
        
        // Create entry in user_tests table for tracking - using only existing columns
// Create entry in user_tests table for tracking
$insert_user_test = "INSERT INTO user_tests (user_id, test_id, status) 
                    VALUES ($user_id, NULL, 'Not Started')";
mysqli_query($conn, $insert_user_test);
$_SESSION['test']['user_test_id'] = mysqli_insert_id($conn);
        
        header('Location: take_test.php');
        exit();
    } else {
        $error = "No questions available for this category.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Tests - KAcademyX</title>
  <meta name="description" content="Take MCQ tests in various subjects at KAcademyX">
  <meta name="keywords" content="KAcademyX, tests, MCQ, online examination, Pakistan">
  
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
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/css/glightbox.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" rel="stylesheet">
  
  <!-- Main CSS File -->
  <style>
    :root {
      --primary-color: #4154f1;
      --secondary-color: #7b68ee;
      --accent-color: #00d2ff;
      --dark-color: #0f172a;
      --light-color: #f8fafc;
      --physics-color: #e74c3c;
      --cs-color: #3498db;
      --biology-color: #2ecc71;
      --maths-color: #f39c12;
      --motivation-color: #9b59b6;
      --career-color: #1abc9c;
      --scholarship-color: #e67e22;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      color: var(--dark-color);
      background-color: #ffffff;
      line-height: 1.6;
      overflow-x: hidden;
    }
    
    h1, h2, h3, h4, h5, h6 {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
    }
    
    /* Animated Background */
    .animated-bg {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      opacity: 0.03;
      background-image: url('https://images.unsplash.com/photo-1503676260728-1c00da094a0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }
    
    /* Modern Navbar */
    .navbar {
      transition: all 0.3s ease;
      padding: 15px 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
      z-index: 1000;
    }
    
    .navbar-brand {
      font-weight: 800;
      font-size: 1.8rem;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-family: 'Montserrat', sans-serif;
    }
    
    .navbar-nav .nav-link {
      font-weight: 500;
      margin: 0 10px;
      color: var(--dark-color);
      position: relative;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }
    
    .navbar-nav .nav-link:hover {
      color: var(--primary-color);
    }
    
    .navbar-nav .nav-link.active {
      color: var(--primary-color);
    }
    
    .navbar-nav .nav-link.active::after {
      content: "";
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 100%;
      height: 3px;
      background: var(--primary-color);
      border-radius: 3px;
    }
    
    .btn-getstarted {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      padding: 10px 25px;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
      font-family: 'Poppins', sans-serif;
    }
    
    .btn-getstarted:hover {
      transform: translateY(-3px);
      box-shadow: 0 7px 20px rgba(65, 84, 241, 0.5);
      color: white;
    }
    
    /* Page Title */
    .page-title {
      position: relative;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      padding: 120px 0 60px;
      color: white;
      overflow: hidden;
    }
    
    .page-title::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
      background-size: cover;
      background-position: center;
      opacity: 0.15;
      z-index: 0;
    }
    
    .page-title .heading {
      position: relative;
      z-index: 1;
    }
    
    .page-title h1 {
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 20px;
      font-family: 'Montserrat', sans-serif;
    }
    
    .page-title p {
      font-size: 1.2rem;
      max-width: 800px;
      margin: 0 auto;
      opacity: 0.9;
      font-family: 'Roboto', sans-serif;
    }
    
    .breadcrumbs {
      background: rgba(255, 255, 255, 0.1);
      padding: 15px 0;
      position: relative;
      z-index: 1;
    }
    
    .breadcrumbs ol {
      display: flex;
      justify-content: center;
      list-style: none;
      margin: 0;
      padding: 0;
    }
    
    .breadcrumbs ol li {
      display: flex;
      align-items: center;
    }
    
    .breadcrumbs ol li+li {
      padding-left: 10px;
    }
    
    .breadcrumbs ol li+li::before {
      content: "/";
      padding-right: 10px;
      color: rgba(255, 255, 255, 0.6);
    }
    
    .breadcrumbs ol li a {
      color: rgba(255, 255, 255, 0.8);
      transition: color 0.3s ease;
    }
    
    .breadcrumbs ol li a:hover {
      color: white;
    }
    
    .breadcrumbs ol li.current {
      color: white;
    }
    
    /* Test Section */
    .test-section {
      padding: 100px 0;
      background-color: #f8fafc;
    }
    
    .category-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transition: all 0.4s ease;
      height: 100%;
      border: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
    }
    
    .category-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    
    .category-icon {
      height: 120px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: white;
    }
    
    .physics-icon {
      background: linear-gradient(45deg, var(--physics-color), #c0392b);
    }
    
    .cs-icon {
      background: linear-gradient(45deg, var(--cs-color), #2980b9);
    }
    
    .biology-icon {
      background: linear-gradient(45deg, var(--biology-color), #27ae60);
    }
    
    .maths-icon {
      background: linear-gradient(45deg, var(--maths-color), #d35400);
    }
    
    .motivation-icon {
      background: linear-gradient(45deg, var(--motivation-color), #8e44ad);
    }
    
    .career-icon {
      background: linear-gradient(45deg, var(--career-color), #16a085);
    }
    
    .scholarship-icon {
      background: linear-gradient(45deg, var(--scholarship-color), #d35400);
    }
    
    .category-content {
      padding: 30px;
    }
    
    .category-content h3 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    
    .category-content p {
      color: #64748b;
      margin-bottom: 20px;
      font-family: 'Roboto', sans-serif;
    }
    
    .category-stats {
      display: flex;
      justify-content: space-between;
      margin-bottom: 25px;
    }
    
    .stat-item {
      text-align: center;
    }
    
    .stat-item i {
      font-size: 1.5rem;
      margin-bottom: 5px;
      color: var(--primary-color);
    }
    
    .stat-item span {
      display: block;
      font-size: 0.9rem;
      color: #64748b;
    }
    
    .btn-start-test {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 50px;
      font-weight: 600;
      width: 100%;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
      font-family: 'Poppins', sans-serif;
    }
    
    .btn-start-test:hover {
      transform: translateY(-3px);
      box-shadow: 0 7px 20px rgba(65, 84, 241, 0.5);
      color: white;
    }
    
    /* Test History Section */
    .test-history {
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      padding: 30px;
      margin-top: 50px;
    }
    
    .test-history h3 {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 25px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    
    .test-history-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #f1f5f9;
    }
    
    .test-history-item:last-child {
      border-bottom: none;
    }
    
    .test-info h4 {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark-color);
    }
    
    .test-info p {
      font-size: 0.9rem;
      color: #64748b;
      margin: 0;
    }
    
    .test-result {
      text-align: right;
    }
    
    .test-score {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 5px;
    }
    
    .test-score.passed {
      color: #2ecc71;
    }
    
    .test-score.failed {
      color: #e74c3c;
    }
    
    .test-date {
      font-size: 0.85rem;
      color: #94a3b8;
    }
    
    .certificate-status {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-top: 8px;
    }
    
    .certificate-status.available {
      background-color: rgba(46, 204, 113, 0.1);
      color: #2ecc71;
    }
    
    .certificate-status.not-available {
      background-color: rgba(231, 76, 60, 0.1);
      color: #e74c3c;
    }
    
    .btn-certificate {
      background: linear-gradient(45deg, var(--secondary-color), var(--accent-color));
      color: white;
      border: none;
      padding: 8px 20px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(123, 104, 238, 0.3);
      margin-top: 10px;
    }
    
    .btn-certificate:hover {
      transform: translateY(-2px);
      box-shadow: 0 7px 20px rgba(123, 104, 238, 0.5);
      color: white;
    }
    
    .btn-view-all {
      background: transparent;
      color: var(--primary-color);
      border: 2px solid var(--primary-color);
      padding: 10px 25px;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
      margin-top: 20px;
      display: inline-block;
    }
    
    .btn-view-all:hover {
      background: var(--primary-color);
      color: white;
    }
    
    /* Existing Test Notice */
    .existing-test-notice {
      background: linear-gradient(45deg, #fff3cd, #ffeaa7);
      border: 1px solid #ffeaa7;
      color: #856404;
      padding: 20px;
      border-radius: 15px;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      box-shadow: 0 4px 10px rgba(243, 156, 18, 0.2);
    }
    
    .existing-test-notice i {
      font-size: 1.5rem;
      margin-right: 15px;
      color: var(--warning-color);
    }
    
    .existing-test-notice-content {
      flex: 1;
    }
    
    .existing-test-notice h4 {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--dark-color);
    }
    
    .existing-test-notice p {
      margin-bottom: 15px;
      color: #856404;
    }
    
    .existing-test-notice-buttons {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }
    
    .btn-continue-test {
      background: var(--primary-color);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(65, 84, 241, 0.3);
    }
    
    .btn-continue-test:hover {
      background: #3141c5;
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(65, 84, 241, 0.5);
      color: white;
    }
    
    .btn-clear-test {
      background: white;
      color: var(--dark-color);
      border: 1px solid #e2e8f0;
      padding: 10px 20px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .btn-clear-test:hover {
      background: #f8fafc;
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
      color: var(--dark-color);
    }
    
    .alert-container {
      position: fixed;
      top: 100px;
      right: 20px;
      z-index: 1050;
      max-width: 350px;
    }
    
    .custom-alert {
      padding: 15px 20px;
      border-radius: 10px;
      margin-bottom: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border-left: 4px solid #28a745;
    }
    
    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
      border-left: 4px solid #dc3545;
    }
    
    .alert-info {
      background-color: #d1ecf1;
      color: #0c5460;
      border-left: 4px solid #17a2b8;
    }
    
    .custom-alert i {
      font-size: 1.5rem;
      margin-right: 15px;
    }
    
    .custom-alert .close-btn {
      margin-left: auto;
      background: none;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      opacity: 0.7;
    }
    
    .custom-alert .close-btn:hover {
      opacity: 1;
    }
    
    /* Footer */
    .footer {
      background: var(--dark-color);
      color: #e2e8f0;
      padding: 80px 0 30px;
    }
    
    .footer-about h3 {
      color: white;
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 25px;
      font-family: 'Montserrat', sans-serif;
    }
    
    .footer-links h4 {
      color: white;
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 25px;
      position: relative;
      padding-bottom: 12px;
      font-family: 'Montserrat', sans-serif;
    }
    
    .footer-links h4::after {
      content: "";
      position: absolute;
      left: 0;
      bottom: 0;
      width: 50px;
      height: 3px;
      background: var(--primary-color);
    }
    
    .footer-links ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .footer-links ul li {
      margin-bottom: 12px;
    }
    
    .footer-links ul li a {
      color: #94a3b8;
      transition: all 0.3s ease;
      font-size: 1rem;
      font-family: 'Roboto', sans-serif;
    }
    
    .footer-links ul li a:hover {
      color: white;
      padding-left: 5px;
    }
    
    .footer-bottom {
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      margin-top: 60px;
      padding-top: 30px;
      text-align: center;
      color: #94a3b8;
      font-size: 1rem;
      font-family: 'Roboto', sans-serif;
    }
    
    /* Section Title */
    .section-title {
      text-align: center;
      margin-bottom: 60px;
      position: relative;
    }
    
    .section-title h2 {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--dark-color);
      margin-bottom: 15px;
      position: relative;
      display: inline-block;
      font-family: 'Montserrat', sans-serif;
    }
    
    .section-title h2::after {
      content: "";
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 70px;
      height: 4px;
      background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
      border-radius: 2px;
    }
    
    .section-title p {
      font-size: 1.1rem;
      color: #64748b;
      max-width: 700px;
      margin: 0 auto;
      font-family: 'Roboto', sans-serif;
    }
    
    /* Scroll Top */
    .scroll-top {
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 50px;
      height: 50px;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
      box-shadow: 0 5px 15px rgba(65, 84, 241, 0.4);
      z-index: 100;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    
    .scroll-top.active {
      opacity: 1;
      visibility: visible;
    }
    
    .scroll-top:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(65, 84, 241, 0.6);
    }
    
    /* Responsive Design */
    @media (max-width: 991.98px) {
      .page-title h1 {
        font-size: 2.5rem;
      }
      
      .navbar-nav {
        text-align: center;
        margin-top: 20px;
      }
      
      .navbar-nav .nav-link {
        margin: 8px 0;
      }
      
      .btn-getstarted {
        margin: 20px auto 0;
        display: block;
        width: fit-content;
      }
    }
    
    @media (max-width: 767.98px) {
      .page-title h1 {
        font-size: 2rem;
      }
      
      .section-title h2 {
        font-size: 2rem;
      }
    }
    
    @media (max-width: 575.98px) {
      .page-title h1 {
        font-size: 1.8rem;
      }
      
      .category-content h3 {
        font-size: 1.3rem;
      }
      
      .existing-test-notice {
        flex-direction: column;
      }
      
      .existing-test-notice-buttons {
        width: 100%;
      }
      
      .btn-continue-test, .btn-clear-test {
        flex: 1;
      }
    }
  </style>
</head>
<body>
  <!-- Animated Background -->
  <div class="animated-bg"></div>
  
  <!-- Modern Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">KAcademyX</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="courses.php">Courses</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="instructors.php">Instructors</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="resources.php">Resources</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="test.php">Tests</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="contact.php">Contact</a>
          </li>
          <?php if (isset($_SESSION['user'])): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo htmlspecialchars($_SESSION['user']); ?>
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                <li><a class="dropdown-item" href="my_tests.php">My Tests</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="forms/logout.php">Logout</a></li>
              </ul>
            </li>
          <?php else: ?>
          <?php endif; ?>
        </ul>
        <?php if (isset($_SESSION['user'])): ?>
          <a class="btn-getstarted ms-3" href="courses.php">Explore Courses</a>
        <?php else: ?>
          <a class="btn-getstarted ms-3" href="forms/login.php">Login/Signup</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
  
  <main class="main">
    <!-- Page Title -->
    <div class="page-title" data-aos="fade">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">
              <h1>Subject Tests</h1>
              <p class="mb-0">Test your knowledge with our MCQ tests in various subjects. Each test is timed and designed to challenge your understanding.</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Tests</li>
          </ol>
        </div>
      </nav>
    </div><!-- End Page Title -->
    
    <!-- Test Section -->
    <section id="test" class="test-section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Available Test Categories</h2>
          <p>Select a subject to start your test. Each test contains multiple choice questions with a time limit.</p>
        </div>
        
        <!-- Existing Test Notice -->
        <?php if (isset($show_existing_test_notice)): ?>
        <div class="existing-test-notice animate__animated animate__fadeInUp">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div class="existing-test-notice-content">
            <h4>You have an incomplete test</h4>
            <p>You have an incomplete test in <strong><?php echo htmlspecialchars($existing_test_category); ?></strong> with <?php echo $existing_test_progress; ?> out of <?php echo $existing_test_questions; ?> questions completed.</p>
            <div class="existing-test-notice-buttons">
              <a href="take_test.php" class="btn-continue-test">Continue Test</a>
              <a href="test.php?clear_test=1" class="btn-clear-test">Start New Test</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
          <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row gy-4">
          <?php if (empty($categories)): ?>
            <div class="col-12 text-center">
              <p>No test categories available at the moment.</p>
            </div>
          <?php else: ?>
            <?php foreach ($categories as $category): ?>
              <?php 
                // Get question count for this category
                $count_query = "SELECT COUNT(*) as count FROM mcq_questions WHERE category = '$category'";
                $count_result = mysqli_query($conn, $count_query);
                $count_row = mysqli_fetch_assoc($count_result);
                $question_count = $count_row['count'];
                
                // Determine icon class based on category
                $icon_class = '';
                $icon = '';
                switch(strtolower($category)) {
                  case 'physics':
                    $icon_class = 'physics-icon';
                    $icon = 'bi bi-lightning-charge-fill';
                    break;
                  case 'computer science':
                  case 'cs':
                    $icon_class = 'cs-icon';
                    $icon = 'bi bi-cpu-fill';
                    break;
                  case 'biology':
                    $icon_class = 'biology-icon';
                    $icon = 'bi bi-dna';
                    break;
                  case 'mathematics':
                  case 'maths':
                    $icon_class = 'maths-icon';
                    $icon = 'bi bi-calculator-fill';
                    break;
                  case 'motivation':
                    $icon_class = 'motivation-icon';
                    $icon = 'bi bi-heart-fill';
                    break;
                  case 'career guidance':
                  case 'career':
                    $icon_class = 'career-icon';
                    $icon = 'bi bi-briefcase-fill';
                    break;
                  case 'scholarship':
                    $icon_class = 'scholarship-icon';
                    $icon = 'bi bi-mortarboard-fill';
                    break;
                  default:
                    $icon_class = 'physics-icon';
                    $icon = 'bi bi-book-fill';
                }
              ?>
              <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="category-card">
                  <div class="category-icon <?php echo $icon_class; ?>">
                    <i class="<?php echo $icon; ?>"></i>
                  </div>
                  <div class="category-content">
                    <h3><?php echo htmlspecialchars($category); ?></h3>
                    <p>Test your knowledge in <?php echo htmlspecialchars($category); ?> with our comprehensive MCQ test.</p>
                    <div class="category-stats">
                      <div class="stat-item">
                        <i class="bi bi-question-circle"></i>
                        <span><?php echo $question_count; ?> Questions</span>
                      </div>
                      <div class="stat-item">
                        <i class="bi bi-clock"></i>
                        <span>Per Question Timer</span>
                      </div>
                    </div>
                    <form method="post" action="">
                      <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                      <button type="submit" name="start_test" class="btn-start-test">Start Test</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
        <!-- Test History Section -->
        <div class="test-history" data-aos="fade-up">
          <h3>Your Recent Tests</h3>
          <?php if (empty($test_history)): ?>
            <p>You haven't taken any tests yet. Start by selecting a category above.</p>
          <?php else: ?>
            <?php foreach ($test_history as $test): ?>
              <div class="test-history-item">
                <div class="test-info">
                  <h4><?php echo htmlspecialchars($test['test_name']); ?></h4>
                  <p><?php echo htmlspecialchars($test['category']); ?> • <?php echo $test['total_questions']; ?> questions</p>
                </div>
                <div class="test-result">
                  <div class="test-score <?php echo $test['is_passed'] ? 'passed' : 'failed'; ?>">
                    <?php echo round($test['score'], 1); ?>%
                  </div>
                  <div class="test-date"><?php echo date('M d, Y', strtotime($test['test_date'])); ?></div>
                  <?php if ($test['is_passed']): ?>
                    <span class="certificate-status <?php echo $test['certificate_generated'] ? 'available' : 'not-available'; ?>">
                      <?php echo $test['certificate_generated'] ? 'Certificate Available' : 'Certificate Not Generated'; ?>
                    </span>
                    <?php if ($test['certificate_generated']): ?>
                      <a href="generate_certificate.php?id=<?php echo $test['id']; ?>" class="btn-certificate">Download Certificate</a>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
            <div class="text-center">
              <a href="my_tests.php" class="btn-view-all">View All Tests</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Test Section -->
  </main>
  
  <footer id="footer" class="footer position-relative">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <h3>KAcademyX</h3>
          <p class="mt-3">Pakistan's premier educational platform offering courses in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships.</p>
          <div class="footer-contact pt-3">
            <p>1-Educator Boulevard, Faisal Town</p>
            <p>Lahore, Punjab 54000</p>
            <p class="mt-3"><strong>Phone:</strong> <span>+92 300 1234567</span></p>
            <p><strong>Email:</strong> <span>info@kacademyx.pk</span></p>
          </div>
          <div class="social-links d-flex mt-4">
            <a href=""><i class="bi bi-twitter-x"></i></a>
            <a href=""><i class="bi bi-facebook"></i></a>
            <a href=""><i class="bi bi-instagram"></i></a>
            <a href=""><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Useful Links</h4>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="courses.php">Courses</a></li>
            <li><a href="instructors.php">Instructors</a></li>
            <li><a href="resources.php">Resources</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Our Services</h4>
          <ul>
            <li><a href="courses.php">Online Courses</a></li>
            <li><a href="test.php">Practice Tests</a></li>
            <li><a href="#">Career Guidance</a></li>
            <li><a href="#">Scholarship Assistance</a></li>
            <li><a href="resources.php">Study Resources</a></li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-12 footer-newsletter">
          <h4>Our Newsletter</h4>
          <p>Subscribe to our newsletter and receive the latest updates about courses and educational resources!</p>
          <form action="forms/newsletter.php" method="post" class="php-email-form">
            <div class="newsletter-form">
              <input type="email" name="email" placeholder="Your email address">
              <input type="submit" value="Subscribe">
            </div>
            <div class="loading">Loading</div>
            <div class="error-message"></div>
            <div class="sent-message">Your subscription request has been sent. Thank you!</div>
          </form>
        </div>
      </div>
    </div>
    <div class="container footer-bottom">
      <div class="copyright">
        &copy; Copyright <strong><span>KAcademyX</span></strong>. All Rights Reserved
      </div>
    </div>
  </footer>
  
  <!-- Scroll Top -->
  <a href="#" class="scroll-top" id="scroll-top"><i class="bi bi-arrow-up-short"></i></a>
  
  <!-- Alert Container -->
  <div class="alert-container" id="alert-container"></div>
  
  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/js/glightbox.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  
  <!-- Main JS File -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize AOS
      AOS.init({
        duration: 1000,
        easing: 'ease-in-out',
        once: true,
        mirror: false
      });
      
      // Navbar background on scroll
      window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
          navbar.style.background = 'rgba(255, 255, 255, 0.98)';
          navbar.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.1)';
        } else {
          navbar.style.background = 'rgba(255, 255, 255, 0.95)';
          navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        }
      });
      
      // Smooth scrolling for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            window.scrollTo({
              top: target.offsetTop - 80,
              behavior: 'smooth'
            });
          }
        });
      });
      
      // Scroll top button
      const scrollTop = document.getElementById('scroll-top');
      if (scrollTop) {
        window.addEventListener('scroll', function() {
          if (window.scrollY > 300) {
            scrollTop.classList.add('active');
          } else {
            scrollTop.classList.remove('active');
          }
        });
        
        scrollTop.addEventListener('click', function(e) {
          e.preventDefault();
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        });
      }
      
      // Custom alert system
      window.showAlert = function(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        const alertId = 'alert-' + Date.now();
        
        const alertHtml = `
          <div id="${alertId}" class="custom-alert alert-${type}">
            <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill'}"></i>
            <span>${message}</span>
            <button class="close-btn" onclick="closeAlert('${alertId}')">&times;</button>
          </div>
        `;
        
        alertContainer.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto close after 5 seconds
        setTimeout(() => {
          closeAlert(alertId);
        }, 5000);
      };
      
      window.closeAlert = function(alertId) {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
          alertElement.style.opacity = '0';
          setTimeout(() => {
            alertElement.remove();
          }, 300);
        }
      };
    });
  </script>
</body>
</html>