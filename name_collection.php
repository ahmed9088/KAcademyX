<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'forms/db.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

// Check if test session exists
if (!isset($_SESSION['test'])) {
    header('Location: test.php');
    exit();
}

// Get user information for pre-filling
$user_id = $_SESSION["id"];
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get test information
$test = $_SESSION['test'];
$category = $test['category'];
$total_questions = count($test['questions']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Enter Your Name - KAcademyX</title>
  <meta name="description" content="Enter your name before starting the test at KAcademyX">
  
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
      display: flex;
      flex-direction: column;
    }
    
    h1, h2, h3, h4, h5, h6 {
      font-family: 'Montserrat', sans-serif;
      font-weight: 700;
    }
    
    /* Container */
    .name-collection-container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    /* Form Card */
    .form-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      padding: 40px;
      max-width: 500px;
      width: 100%;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .form-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    }
    
    .form-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .form-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 10px 20px rgba(65, 84, 241, 0.3);
    }
    
    .form-icon i {
      font-size: 2.5rem;
      color: white;
    }
    
    .form-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--dark-color);
      margin-bottom: 10px;
    }
    
    .form-subtitle {
      color: #64748b;
      font-size: 1rem;
    }
    
    /* Form Elements */
    .form-group {
      margin-bottom: 25px;
    }
    
    .form-label {
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--dark-color);
      display: block;
    }
    
    .form-control {
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      padding: 15px 20px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #f8fafc;
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(65, 84, 241, 0.1);
      background: white;
    }
    
    .form-hint {
      font-size: 0.85rem;
      color: #64748b;
      margin-top: 5px;
    }
    
    /* Test Info */
    .test-info {
      background: #f8fafc;
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
      border: 1px solid #e2e8f0;
    }
    
    .test-info-item {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .test-info-item:last-child {
      margin-bottom: 0;
    }
    
    .test-info-icon {
      width: 40px;
      height: 40px;
      background: rgba(65, 84, 241, 0.1);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      color: var(--primary-color);
    }
    
    .test-info-text {
      flex: 1;
    }
    
    .test-info-label {
      font-weight: 600;
      color: var(--dark-color);
      font-size: 0.9rem;
    }
    
    .test-info-value {
      color: #64748b;
      font-size: 0.85rem;
    }
    
    /* Submit Button */
    .btn-submit {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      padding: 15px 30px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.1rem;
      width: 100%;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(65, 84, 241, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(65, 84, 241, 0.5);
      color: white;
    }
    
    .btn-submit i {
      margin-left: 10px;
    }
    
    /* Instructions */
    .instructions {
      background: linear-gradient(45deg, #f0f9ff, #e0f2fe);
      border: 1px solid #bae6fd;
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .instructions-title {
      font-weight: 700;
      color: var(--dark-color);
      margin-bottom: 15px;
      display: flex;
      align-items: center;
    }
    
    .instructions-title i {
      margin-right: 10px;
      color: var(--primary-color);
    }
    
    .instructions-list {
      list-style: none;
      padding: 0;
    }
    
    .instructions-list li {
      margin-bottom: 10px;
      padding-left: 25px;
      position: relative;
      color: #64748b;
      font-size: 0.95rem;
    }
    
    .instructions-list li::before {
      content: "✓";
      position: absolute;
      left: 0;
      color: var(--success-color);
      font-weight: bold;
    }
    
    /* Back Button */
    .btn-back {
      background: #e2e8f0;
      color: var(--dark-color);
      border: none;
      padding: 12px 25px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 20px;
      display: inline-flex;
      align-items: center;
    }
    
    .btn-back:hover {
      background: #cbd5e1;
      transform: translateY(-2px);
    }
    
    .btn-back i {
      margin-right: 8px;
    }
    
    /* Responsive Design */
    @media (max-width: 575.98px) {
      .form-card {
        padding: 30px 20px;
      }
      
      .form-title {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="name-collection-container">
    <div class="form-card animate__animated animate__fadeInUp">
      <div class="form-header">
        <div class="form-icon">
          <i class="bi bi-person-badge"></i>
        </div>
        <h1 class="form-title">Enter Your Name</h1>
        <p class="form-subtitle">Please provide your name before starting the test</p>
      </div>
      
      <div class="test-info">
        <div class="test-info-item">
          <div class="test-info-icon">
            <i class="bi bi-book"></i>
          </div>
          <div class="test-info-text">
            <div class="test-info-label">Test Category</div>
            <div class="test-info-value"><?php echo htmlspecialchars($category); ?></div>
          </div>
        </div>
        <div class="test-info-item">
          <div class="test-info-icon">
            <i class="bi bi-question-circle"></i>
          </div>
          <div class="test-info-text">
            <div class="test-info-label">Number of Questions</div>
            <div class="test-info-value"><?php echo $total_questions; ?> questions</div>
          </div>
        </div>
        <div class="test-info-item">
          <div class="test-info-icon">
            <i class="bi bi-clock"></i>
          </div>
          <div class="test-info-text">
            <div class="test-info-label">Time Limit</div>
            <div class="test-info-value">30 seconds per question</div>
          </div>
        </div>
      </div>
      
      <div class="instructions">
        <h3 class="instructions-title">
          <i class="bi bi-info-circle"></i>
          Test Instructions
        </h3>
        <ul class="instructions-list">
          <li>Enter your full name as you want it to appear on the certificate</li>
          <li>Make sure your name is spelled correctly</li>
          <li>Once submitted, you cannot change your name during the test</li>
          <li>Ensure you have a stable internet connection</li>
          <li>Do not refresh the page or navigate away during the test</li>
        </ul>
      </div>
      
      <form method="post" action="take_test.php">
        <div class="form-group">
          <label for="student_name" class="form-label">Your Full Name</label>
          <input type="text" id="student_name" name="student_name" class="form-control" 
                 value="<?php echo htmlspecialchars($user['name']); ?>" 
                 placeholder="Enter your full name" required>
          <div class="form-hint">This name will be used on your certificate if you pass the test</div>
        </div>
        
        <button type="submit" name="submit_name" class="btn-submit">
          Start Test <i class="bi bi-arrow-right-circle"></i>
        </button>
      </form>
      
      <div class="text-center">
        <a href="test.php" class="btn-back">
          <i class="bi bi-arrow-left"></i> Back to Test Selection
        </a>
      </div>
    </div>
  </div>
  
  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.js"></script>
  
  <!-- Main JS File -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Focus on the name input field
      document.getElementById('student_name').focus();
      
      // Add animation to form elements
      const formElements = document.querySelectorAll('.form-control, .btn-submit');
      formElements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
        element.classList.add('animate__animated', 'animate__fadeInUp');
      });
      
      // Form validation
      const form = document.querySelector('form');
      const nameInput = document.getElementById('student_name');
      
      form.addEventListener('submit', function(e) {
        const name = nameInput.value.trim();
        
        // Basic validation
        if (name.length < 2) {
          e.preventDefault();
          alert('Please enter a valid name (at least 2 characters)');
          return;
        }
        
        // Check for numbers in the name (basic check)
        if (/\d/.test(name)) {
          e.preventDefault();
          if (confirm('Your name contains numbers. Are you sure this is correct?')) {
            form.submit();
          }
          return;
        }
        
        // Show loading state
        const submitBtn = document.querySelector('.btn-submit');
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Starting Test...';
        submitBtn.disabled = true;
      });
    });
  </script>
</body>
</html>