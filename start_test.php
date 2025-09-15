<?php
session_start();
require_once 'forms/db.php';

// If student is already registered, redirect to waiting room
if (isset($_SESSION['student_name'])) {
    header('Location: waiting_room.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name']);
    
    if (!empty($student_name)) {
        // Store student name in session
        $_SESSION['student_name'] = $student_name;
        
        // Clean up old entries first
        $cleanup_query = "DELETE FROM waiting_students WHERE joined_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $conn->query($cleanup_query);
        
        // Add student to waiting room database
        $stmt = $conn->prepare("INSERT INTO waiting_students (name, session_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE joined_at = NOW()");
        $session_id = session_id();
        $stmt->bind_param("ss", $student_name, $session_id);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to waiting room
        header('Location: waiting_room.php');
        exit();
    } else {
        $error = "Please enter your name";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Start Test - KAcademyX</title>
  <meta name="description" content="Register for MCQ test at KAcademyX">
  
  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  
  <style>
    :root {
      --primary-color: #4154f1;
      --secondary-color: #7b68ee;
      --dark-color: #0f172a;
      --light-color: #f8fafc;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #e6f7ff 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--dark-color);
    }
    
    .registration-container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      padding: 40px;
      width: 100%;
      max-width: 500px;
      text-align: center;
    }
    
    .logo {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 10px;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .tagline {
      color: #64748b;
      margin-bottom: 30px;
    }
    
    .form-group {
      margin-bottom: 25px;
      text-align: left;
    }
    
    .form-label {
      font-weight: 600;
      margin-bottom: 10px;
      display: block;
    }
    
    .form-control {
      border-radius: 10px;
      border: 2px solid #e2e8f0;
      padding: 12px 15px;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.25rem rgba(65, 84, 241, 0.25);
    }
    
    .btn-register {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      border-radius: 50px;
      padding: 12px 30px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      box-shadow: 0 4px 15px rgba(65, 84, 241, 0.3);
    }
    
    .btn-register:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(65, 84, 241, 0.5);
    }
    
    .alert {
      border-radius: 10px;
      padding: 12px 15px;
      margin-bottom: 20px;
    }
    
    .icon-container {
      font-size: 4rem;
      color: var(--primary-color);
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div class="registration-container">
    <div class="icon-container">
      <i class="bi bi-person-circle"></i>
    </div>
    <h1 class="logo">KAcademyX</h1>
    <p class="tagline">Online Examination Platform</p>
    
    <?php if (isset($error)): ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>
    
    <form method="post" action="">
      <div class="form-group">
        <label for="student_name" class="form-label">Enter Your Full Name</label>
        <input type="text" class="form-control" id="student_name" name="student_name" required placeholder="John Doe">
      </div>
      
      <button type="submit" class="btn-register">
        <i class="bi bi-arrow-right-circle me-2"></i> Join Waiting Room
      </button>
    </form>
    
    <div class="mt-4 text-muted small">
      <p>After registering, you'll be placed in a waiting room for 5 minutes while other students join.</p>
      <p>The test will start automatically after the waiting period.</p>
    </div>
  </div>
</body>
</html>