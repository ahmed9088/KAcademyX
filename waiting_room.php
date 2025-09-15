<?php
session_start();
require_once 'forms/db.php';

// Check if student is registered
if (!isset($_SESSION['student_name'])) {
    header('Location: start_test.php');
    exit();
}

// Clean up old waiting students (older than 1 hour)
$cleanup_query = "DELETE FROM waiting_students WHERE joined_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$conn->query($cleanup_query);

// Check if waiting period has started
if (!isset($_SESSION['waiting_start_time'])) {
    $_SESSION['waiting_start_time'] = time();
}

// Calculate remaining waiting time
$elapsed_time = time() - $_SESSION['waiting_start_time'];
$waiting_time_limit = 300; // 5 minutes in seconds
$remaining_time = max(0, $waiting_time_limit - $elapsed_time);

// If waiting time is up, remove student from waiting_students and redirect to test selection
if ($remaining_time <= 0) {
    // Remove the current student from waiting_students
    $session_id = session_id();
    $delete_query = "DELETE FROM waiting_students WHERE session_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: test.php');
    exit();
}

// Get all waiting students
$students_query = "SELECT name FROM waiting_students ORDER BY joined_at";
$students_result = $conn->query($students_query);
$waiting_students = [];
while ($row = $students_result->fetch_assoc()) {
    $waiting_students[] = $row['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Waiting Room - KAcademyX</title>
  <meta name="description" content="Waiting room for MCQ test at KAcademyX">
  
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
      --success-color: #2ecc71;
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
    
    .waiting-container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      padding: 40px;
      width: 100%;
      max-width: 800px;
    }
    
    .header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .logo {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 10px;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    
    .timer-container {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      border-radius: 15px;
      padding: 20px;
      text-align: center;
      color: white;
      margin-bottom: 30px;
      box-shadow: 0 10px 25px rgba(65, 84, 241, 0.3);
    }
    
    .timer-label {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 10px;
    }
    
    .timer {
      font-size: 3rem;
      font-weight: 800;
    }
    
    .students-container {
      background: var(--light-color);
      border-radius: 15px;
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .students-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .students-title {
      font-size: 1.2rem;
      font-weight: 600;
    }
    
    .students-count {
      background: var(--primary-color);
      color: white;
      border-radius: 50px;
      padding: 5px 15px;
      font-weight: 600;
      font-size: 0.9rem;
    }
    
    .students-list {
      max-height: 300px;
      overflow-y: auto;
    }
    
    .student-item {
      display: flex;
      align-items: center;
      padding: 10px 15px;
      border-radius: 10px;
      margin-bottom: 8px;
      background: white;
      transition: all 0.3s ease;
    }
    
    .student-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .student-icon {
      color: var(--primary-color);
      margin-right: 15px;
      font-size: 1.2rem;
    }
    
    .student-name {
      font-weight: 500;
    }
    
    .current-student {
      background: rgba(65, 84, 241, 0.1);
      border-left: 4px solid var(--primary-color);
    }
    
    .instructions {
      background: rgba(46, 204, 113, 0.1);
      border: 1px solid rgba(46, 204, 113, 0.3);
      border-radius: 15px;
      padding: 20px;
    }
    
    .instructions-title {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--success-color);
      display: flex;
      align-items: center;
    }
    
    .instructions-title i {
      margin-right: 10px;
    }
    
    .instructions-list {
      padding-left: 30px;
    }
    
    .instructions-list li {
      margin-bottom: 8px;
    }
    
    .icon-container {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .icon-container i {
      font-size: 4rem;
      color: var(--primary-color);
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    
    .pulse {
      animation: pulse 2s infinite;
    }
  </style>
</head>
<body>
  <div class="waiting-container">
    <div class="icon-container">
      <i class="bi bi-people-fill pulse"></i>
    </div>
    
    <div class="header">
      <h1 class="logo">KAcademyX</h1>
      <p class="text-muted">Waiting Room for Online Examination</p>
    </div>
    
    <div class="timer-container">
      <div class="timer-label">Test Starting In</div>
      <div class="timer" id="timer"><?php echo gmdate("i:s", $remaining_time); ?></div>
    </div>
    
    <div class="students-container">
      <div class="students-header">
        <div class="students-title">Registered Students</div>
        <div class="students-count"><?php echo count($waiting_students); ?> Students</div>
      </div>
      
      <div class="students-list">
        <?php foreach ($waiting_students as $student): ?>
          <div class="student-item <?php echo ($student === $_SESSION['student_name']) ? 'current-student' : ''; ?>">
            <i class="bi bi-person-circle student-icon"></i>
            <div class="student-name">
              <?php echo htmlspecialchars($student); ?>
              <?php if ($student === $_SESSION['student_name']): ?>
                <span class="badge bg-primary ms-2">You</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <div class="instructions">
      <div class="instructions-title">
        <i class="bi bi-info-circle-fill"></i> Instructions
      </div>
      <ul class="instructions-list">
        <li>Please wait patiently in the waiting room until the test starts.</li>
        <li>The test will begin automatically after the countdown timer reaches zero.</li>
        <li>Make sure you have a stable internet connection before the test starts.</li>
        <li>Once the test begins, you cannot leave the test environment.</li>
        <li>Each question has a specific time limit. Answer within the allotted time.</li>
      </ul>
    </div>
  </div>
  
  <script>
    // Timer functionality
    let timeRemaining = <?php echo $remaining_time; ?>;
    const timerElement = document.getElementById('timer');
    
    const timerInterval = setInterval(() => {
      timeRemaining--;
      
      if (timeRemaining <= 0) {
        clearInterval(timerInterval);
        // Redirect to test selection when time is up
        window.location.href = 'test.php';
      }
      
      // Update timer display
      const minutes = Math.floor(timeRemaining / 60);
      const seconds = timeRemaining % 60;
      timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
    
    // Refresh student list every 5 seconds
    setInterval(() => {
      window.location.reload();
    }, 5000);
  </script>
</body>
</html>