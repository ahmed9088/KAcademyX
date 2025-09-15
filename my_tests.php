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
// Get user information from users table
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);
if (!$user) {
    // User not found, redirect to login
    header('Location: forms/login.php');
    exit();
}
// Get user's name
$user_name = $user['name'];

// Get test results for the user
try {
    $stmt = $conn->prepare("SELECT * FROM test_results WHERE user_id = ? ORDER BY test_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $test_results = [];
    
    while ($row = $result->fetch_assoc()) {
        $test_results[] = $row;
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Log error but don't interrupt the flow
    error_log("Error fetching test results: " . $e->getMessage());
    $test_results = [];
}

// Calculate statistics
$total_tests = count($test_results);
$total_questions = 0;
$total_correct = 0;
$average_score = 0;
$highest_score = 0;
$passed_tests = 0;
$certificates_generated = 0;

foreach ($test_results as $test) {
    $total_questions += $test['total_questions'];
    $total_correct += $test['correct_answers'];
    
    if ($test['score'] > $highest_score) {
        $highest_score = $test['score'];
    }
    
    if ($test['is_passed'] == 1) {
        $passed_tests++;
        
        if ($test['certificate_generated'] == 1) {
            $certificates_generated++;
        }
    }
}

if ($total_tests > 0) {
    $average_score = round(($total_correct / $total_questions) * 100);
    $pass_rate = round(($passed_tests / $total_tests) * 100);
    $certificate_rate = $total_tests > 0 ? round(($certificates_generated / $passed_tests) * 100) : 0;
} else {
    $pass_rate = 0;
    $certificate_rate = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Test History - KAcademyX</title>
  <meta name="description" content="View your test history at KAcademyX">
  <meta name="keywords" content="KAcademyX, test history, MCQ results">
  
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
    
    /* Test History Container */
    .test-history-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    
    /* Page Header */
    .page-header {
      background: white;
      border-radius: 20px;
      padding: 40px;
      margin-bottom: 30px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: hidden;
    }
    
    .page-header::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    }
    
    .page-title {
      font-size: 2.5rem;
      margin-bottom: 15px;
      color: var(--dark-color);
      position: relative;
      display: inline-block;
    }
    
    .page-title::after {
      content: "";
      position: absolute;
      bottom: -10px;
      left: 0;
      width: 80px;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      border-radius: 2px;
    }
    
    .page-subtitle {
      font-size: 1.2rem;
      color: #64748b;
      margin-bottom: 30px;
      max-width: 700px;
    }
    
    .student-info {
      display: flex;
      align-items: center;
      background: rgba(65, 84, 241, 0.1);
      padding: 15px 25px;
      border-radius: 15px;
      margin-bottom: 30px;
      border-left: 4px solid var(--primary-color);
    }
    
    .student-info i {
      font-size: 1.5rem;
      margin-right: 15px;
      color: var(--primary-color);
    }
    
    .student-name {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--dark-color);
    }
    
    /* Stats Summary */
    .stats-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .stat-card {
      background: white;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
    }
    
    .stat-icon {
      font-size: 2rem;
      margin-bottom: 15px;
      color: var(--primary-color);
    }
    
    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--dark-color);
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 1rem;
      color: #64748b;
      font-weight: 500;
    }
    
    /* Test Results Table */
    .test-results-section {
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
    
    .table-container {
      overflow-x: auto;
    }
    
    .test-results-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    .test-results-table th {
      background: rgba(65, 84, 241, 0.1);
      color: var(--dark-color);
      font-weight: 600;
      text-align: left;
      padding: 15px;
      border-bottom: 2px solid var(--primary-color);
    }
    
    .test-results-table td {
      padding: 15px;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .test-results-table tr:hover {
      background: rgba(65, 84, 241, 0.02);
    }
    
    .test-results-table tr:last-child td {
      border-bottom: none;
    }
    
    .result-badge {
      display: inline-block;
      padding: 6px 15px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.85rem;
    }
    
    .badge-pass {
      background: rgba(46, 204, 113, 0.1);
      color: var(--success-color);
    }
    
    .badge-fail {
      background: rgba(231, 76, 60, 0.1);
      color: var(--danger-color);
    }
    
    .score-circle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: 700;
      font-size: 0.9rem;
      color: white;
      position: relative;
    }
    
    .score-high {
      background: var(--success-color);
    }
    
    .score-medium {
      background: var(--warning-color);
    }
    
    .score-low {
      background: var(--danger-color);
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
    }
    
    .btn-action {
      padding: 8px 15px;
      border-radius: 8px;
      font-weight: 500;
      font-size: 0.9rem;
      transition: all 0.2s ease;
      border: none;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      display: inline-flex;
      align-items: center;
      text-decoration: none;
    }
    
    .btn-action i {
      margin-right: 5px;
      font-size: 1rem;
    }
    
    .btn-view {
      background: rgba(65, 84, 241, 0.1);
      color: var(--primary-color);
    }
    
    .btn-view:hover {
      background: rgba(65, 84, 241, 0.2);
    }
    
    .btn-print {
      background: rgba(52, 152, 219, 0.1);
      color: var(--info-color);
    }
    
    .btn-print:hover {
      background: rgba(52, 152, 219, 0.2);
    }
    
    .btn-certificate {
      background: rgba(123, 104, 238, 0.1);
      color: var(--secondary-color);
    }
    
    .btn-certificate:hover {
      background: rgba(123, 104, 238, 0.2);
    }
    
    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
    }
    
    .empty-icon {
      font-size: 4rem;
      color: #cbd5e1;
      margin-bottom: 20px;
    }
    
    .empty-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--dark-color);
      margin-bottom: 15px;
    }
    
    .empty-text {
      font-size: 1.1rem;
      color: #64748b;
      max-width: 500px;
      margin: 0 auto 30px;
    }
    
    .btn-primary {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 12px 30px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      display: inline-flex;
      align-items: center;
      box-shadow: 0 5px 15px rgba(65, 84, 241, 0.3);
    }
    
    .btn-primary i {
      margin-right: 10px;
    }
    
    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(65, 84, 241, 0.5);
      color: white;
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
    
    /* Certificate Badge */
    .certificate-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 8px;
    }
    
    .certificate-available {
      background: rgba(46, 204, 113, 0.1);
      color: var(--success-color);
    }
    
    .certificate-unavailable {
      background: rgba(231, 76, 60, 0.1);
      color: var(--danger-color);
    }
    
    /* Responsive Design */
    @media (max-width: 767.98px) {
      .page-title {
        font-size: 2rem;
      }
      
      .stats-summary {
        grid-template-columns: 1fr;
      }
      
      .test-results-table {
        font-size: 0.9rem;
      }
      
      .test-results-table th,
      .test-results-table td {
        padding: 10px;
      }
      
      .action-buttons {
        flex-direction: column;
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
      
      .page-header, .test-results-section {
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
  <div class="test-history-container">
    <!-- Back Button -->
    <a href="test.php" class="back-button animate__animated animate__fadeInLeft">
      <i class="bi bi-arrow-left"></i> Back to Tests
    </a>
    
    <!-- Page Header -->
    <div class="page-header animate__animated animate__fadeIn">
      <h1 class="page-title">My Test History</h1>
      <p class="page-subtitle">Track your progress and view all your previous test results</p>
      
      <div class="student-info">
        <i class="bi bi-person-circle"></i>
        <div class="student-name"><?php echo htmlspecialchars($user_name); ?></div>
      </div>
      
      <!-- Stats Summary -->
      <div class="stats-summary">
        <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
          <div class="stat-icon">
            <i class="bi bi-clipboard-data-fill"></i>
          </div>
          <div class="stat-value"><?php echo $total_tests; ?></div>
          <div class="stat-label">Total Tests</div>
        </div>
        
        <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
          <div class="stat-icon">
            <i class="bi bi-percent"></i>
          </div>
          <div class="stat-value"><?php echo $average_score; ?>%</div>
          <div class="stat-label">Average Score</div>
        </div>
        
        <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
          <div class="stat-icon">
            <i class="bi bi-trophy-fill"></i>
          </div>
          <div class="stat-value"><?php echo $highest_score; ?>%</div>
          <div class="stat-label">Highest Score</div>
        </div>
        
        <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
          <div class="stat-icon">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="stat-value"><?php echo $pass_rate; ?>%</div>
          <div class="stat-label">Pass Rate</div>
        </div>
        
        <?php if ($passed_tests > 0): ?>
        <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
          <div class="stat-icon">
            <i class="bi bi-award-fill"></i>
          </div>
          <div class="stat-value"><?php echo $certificate_rate; ?>%</div>
          <div class="stat-label">Certificate Rate</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Test Results Section -->
    <div class="test-results-section animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
      <h2 class="section-title">
        <i class="bi bi-table"></i> Test Results
      </h2>
      
      <?php if (count($test_results) > 0): ?>
      <div class="table-container">
        <table class="test-results-table">
          <thead>
            <tr>
              <th>Test Name</th>
              <th>Category</th>
              <th>Date Taken</th>
              <th>Score</th>
              <th>Result</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($test_results as $index => $test): 
              $score = $test['score'];
              $status = $test['is_passed'] ? 'Pass' : 'Fail';
              $status_class = $test['is_passed'] ? 'badge-pass' : 'badge-fail';
              
              // Determine score circle class
              if ($score >= 80) {
                $score_class = 'score-high';
              } elseif ($score >= 60) {
                $score_class = 'score-medium';
              } else {
                $score_class = 'score-low';
              }
              
              // Format date
              $date = new DateTime($test['test_date']);
              $formatted_date = $date->format('M d, Y');
            ?>
            <tr class="animate__animated animate__fadeInUp" style="animation-delay: <?php echo 0.7 + ($index * 0.1); ?>s">
              <td><?php echo htmlspecialchars($test['test_name']); ?></td>
              <td><?php echo htmlspecialchars($test['category']); ?></td>
              <td><?php echo $formatted_date; ?></td>
              <td>
                <div class="score-circle <?php echo $score_class; ?>">
                  <?php echo $score; ?>%
                </div>
              </td>
              <td>
                <span class="result-badge <?php echo $status_class; ?>">
                  <?php echo $status; ?>
                </span>
                <?php if ($test['is_passed']): ?>
                  <span class="certificate-badge <?php echo $test['certificate_generated'] ? 'certificate-available' : 'certificate-unavailable'; ?>">
                    <i class="bi <?php echo $test['certificate_generated'] ? 'bi-patch-check-fill' : 'bi-patch-exclamation-fill'; ?>"></i>
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <div class="action-buttons">
                  <a href="test_result.php?id=<?php echo $test['id']; ?>" class="btn-action btn-view">
                    <i class="bi bi-eye-fill"></i> View
                  </a>
                  <a href="print_test_result.php?id=<?php echo $test['id']; ?>" class="btn-action btn-print" target="_blank">
                    <i class="bi bi-printer-fill"></i> Print
                  </a>
                  <?php if ($test['is_passed'] && $test['certificate_generated']): ?>
                    <a href="generate_certificate.php?id=<?php echo $test['id']; ?>" class="btn-action btn-certificate" target="_blank">
                      <i class="bi bi-award-fill"></i> Certificate
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <!-- Empty State -->
      <div class="empty-state animate__animated animate__fadeIn">
        <div class="empty-icon">
          <i class="bi bi-clipboard-x"></i>
        </div>
        <h3 class="empty-title">No Test History Yet</h3>
        <p class="empty-text">You haven't taken any tests yet. Start by taking a test to see your results here.</p>
        <a href="test.php" class="btn-primary">
          <i class="bi bi-pencil-square"></i> Take a Test
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
  
  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>