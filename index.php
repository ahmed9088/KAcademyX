<?php session_start(); ?>
<?php 
// Include database connection
include "admin/db.php";
// Check if database connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// Define base URL for the site
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/";
// Fetch stats from the database with error handling
$total_students = 0;
$total_instructors = 0;
$total_courses = 0;
$total_mcqs = 0;
try {
    $students_result = $conn->query("SELECT COUNT(*) AS count FROM students");
    if ($students_result) {
        $total_students = $students_result->fetch_assoc()['count'];
    }
    
    $instructors_result = $conn->query("SELECT COUNT(*) AS count FROM instructors");
    if ($instructors_result) {
        $total_instructors = $instructors_result->fetch_assoc()['count'];
    }
    
    $courses_result = $conn->query("SELECT COUNT(*) AS count FROM courses");
    if ($courses_result) {
        $total_courses = $courses_result->fetch_assoc()['count'];
    }
    
    $mcqs_result = $conn->query("SELECT COUNT(*) AS count FROM mcq_questions");
    if ($mcqs_result) {
        $total_mcqs = $mcqs_result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    // Handle database query errors
    error_log("Database query error: " . $e->getMessage());
}
// Fetch courses from database
$courses_query = "SELECT courses.*, instructors.name as instructor_name, instructors.expertise, instructors.profile_image as instructor_image
                 FROM courses 
                 LEFT JOIN instructors ON courses.instructor_id = instructors.id 
                 ORDER BY courses.created_at DESC LIMIT 6";
$courses_result = $conn->query($courses_query);
// Fetch instructors from database
$instructors_query = "SELECT * FROM instructors ORDER BY created_at DESC LIMIT 3";
$instructors_result = $conn->query($instructors_query);
// Fetch recent students for the dashboard with avatar
$recent_students_result = $conn->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 10");
// Check if subjects table exists and has data
$subjects_table_exists = $conn->query("SHOW TABLES LIKE 'subjects'")->num_rows > 0;
$subjects_result = null;
$use_db_subjects = false;
if ($subjects_table_exists) {
    $subjects_result = $conn->query("SELECT * FROM subjects ORDER BY id LIMIT 7");
    if ($subjects_result && $subjects_result->num_rows > 0) {
        $use_db_subjects = true;
    }
}
// Default subjects if database doesn't have any
$default_subjects = [
    ['name' => 'Physics', 'icon' => 'bi-atom', 'description' => 'Explore the fundamental principles that govern our universe, from quantum mechanics to astrophysics.', 'color' => 'physics'],
    ['name' => 'Computer Science', 'icon' => 'bi-cpu', 'description' => 'Master programming, algorithms, and cutting-edge technologies shaping the digital world.', 'color' => 'cs'],
    ['name' => 'Biology', 'icon' => 'bi-dna', 'description' => 'Discover the science of life, from molecular biology to ecosystems and evolution.', 'color' => 'biology'],
    ['name' => 'Mathematics', 'icon' => 'bi-calculator', 'description' => 'Develop problem-solving skills through algebra, calculus, statistics, and more.', 'color' => 'maths'],
    ['name' => 'Motivation', 'icon' => 'bi-lightbulb', 'description' => 'Unlock your potential with strategies for personal growth and achievement.', 'color' => 'motivation'],
    ['name' => 'Career Guidance', 'icon' => 'bi-briefcase', 'description' => 'Navigate your professional journey with expert advice and planning resources.', 'color' => 'career'],
    ['name' => 'Scholarships', 'icon' => 'bi-award', 'description' => 'Access financial aid opportunities to support your educational goals.', 'color' => 'scholarship']
];
// Function to check if a file exists and return a valid path
function getImagePath($path, $defaultPath) {
    global $baseUrl;
    
    if (!empty($path)) {
        // Check if it's an absolute URL
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // Handle relative paths - adjust based on your server configuration
        $relativePath = ltrim($path, '/');
        
        // Check if file exists with different case variations (for case-sensitive servers)
        $possiblePaths = [
            $relativePath,
            str_replace('Admin/', 'admin/', $relativePath),
            str_replace('Admin/', '', $relativePath),
            'admin/' . $relativePath
        ];
        
        foreach ($possiblePaths as $testPath) {
            if (file_exists($testPath)) {
                return $baseUrl . $testPath;
            }
        }
    }
    return $defaultPath;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>KAcademyX - Premier Educational Platform</title>
  <meta name="description" content="KAcademyX - Premier educational platform offering courses in Physics, Computer Science, Biology, Maths, Motivation, Career Guidance, and Scholarships">
  <meta name="keywords" content="education, physics, computer science, biology, mathematics, career guidance, scholarships, online learning">
  <!-- Favicons - Using CDN fallback to avoid 404 errors -->
  <link rel="icon" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/images/favicon.ico">
  <link rel="apple-touch-icon" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/images/apple-touch-icon.png">
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
    /* Full Page Hero Section */
    .hero {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      overflow: hidden;
    }
    .hero::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('https://img.freepik.com/free-vector/flat-background-world-teacher-s-day-celebration_23-2150722546.jpg?semt=ais_incoming&w=740&q=80');
      background-size: cover;
      background-position: center;
      opacity: 0.15;
      z-index: 0;
    }
    .hero-content {
      position: relative;
      z-index: 1;
      width: 100%;
    }
    .hero h1 {
      font-size: 4.5rem;
      font-weight: 800;
      margin-bottom: 1.5rem;
      background: linear-gradient(90deg, #ffffff, #a5b4fc);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      letter-spacing: -1px;
      line-height: 1.2;
      font-family: 'Montserrat', sans-serif;
      text-align: center;
    }
    .hero p {
      font-size: 1.5rem;
      color: #e2e8f0;
      max-width: 800px;
      margin: 0 auto 2rem;
      font-family: 'Roboto', sans-serif;
      font-weight: 300;
      text-align: center;
    }
    .hero-buttons {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      justify-content: center;
    }
    .btn-primary-modern {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      padding: 16px 36px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(65, 84, 241, 0.4);
      font-family: 'Poppins', sans-serif;
    }
    .btn-primary-modern:hover {
      transform: translateY(-3px);
      box-shadow: 0 7px 20px rgba(65, 84, 241, 0.6);
      background: linear-gradient(45deg, #3141c5, #6a5acd);
      color: white;
    }
    .btn-outline-modern {
      background: transparent;
      color: white;
      border: 2px solid rgba(255, 255, 255, 0.5);
      padding: 14px 34px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }
    .btn-outline-modern:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: white;
      color: white;
    }
    .hero-decoration {
      position: absolute;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(65, 84, 241, 0.3) 0%, rgba(65, 84, 241, 0) 70%);
      z-index: 0;
      animation: float 6s ease-in-out infinite;
    }
    @keyframes float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
      100% { transform: translateY(0px); }
    }
    .decoration-1 {
      top: -100px;
      right: -100px;
      width: 400px;
      height: 400px;
      animation-delay: 0s;
    }
    .decoration-2 {
      bottom: -150px;
      left: -100px;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(123, 104, 238, 0.2) 0%, rgba(123, 104, 238, 0) 70%);
      animation-delay: 1s;
    }
    .decoration-3 {
      top: 50%;
      right: 10%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(0, 210, 255, 0.15) 0%, rgba(0, 210, 255, 0) 70%);
      animation-delay: 2s;
    }
    
    /* Stats Section */
    .stats-section {
      padding: 80px 0;
      background-color: #f8fafc;
      position: relative;
    }
    .stats-section::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100px;
      background: linear-gradient(to bottom, #ffffff, #f8fafc);
      z-index: 1;
    }
    .stats-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s ease;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .stats-card:hover {
      transform: translateY(-15px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    .stats-icon {
      font-size: 3rem;
      margin-bottom: 20px;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .stats-number {
      font-size: 3rem;
      font-weight: 800;
      color: var(--dark-color);
      margin-bottom: 10px;
      font-family: 'Montserrat', sans-serif;
    }
    .stats-label {
      font-size: 1.2rem;
      color: #64748b;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
    }
    
    /* Subject Categories */
    .subjects-section {
      padding: 100px 0;
      background-color: #f8fafc;
      position: relative;
    }
    .subjects-section::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100px;
      background: linear-gradient(to bottom, #ffffff, #f8fafc);
      z-index: 1;
    }
    .subject-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s ease;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      height: 100%;
      display: flex;
      flex-direction: column;
      position: relative;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .subject-card:hover {
      transform: translateY(-15px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    .subject-icon {
      height: 140px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3.5rem;
      color: white;
      position: relative;
      overflow: hidden;
    }
    .subject-icon::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0) 100%);
    }
    .physics-icon {
      background: linear-gradient(135deg, var(--physics-color), #c0392b);
    }
    .cs-icon {
      background: linear-gradient(135deg, var(--cs-color), #2980b9);
    }
    .biology-icon {
      background: linear-gradient(135deg, var(--biology-color), #27ae60);
    }
    .maths-icon {
      background: linear-gradient(135deg, var(--maths-color), #d35400);
    }
    .motivation-icon {
      background: linear-gradient(135deg, var(--motivation-color), #8e44ad);
    }
    .career-icon {
      background: linear-gradient(135deg, var(--career-color), #16a085);
    }
    .scholarship-icon {
      background: linear-gradient(135deg, var(--scholarship-color), #d35400);
    }
    .subject-content {
      padding: 30px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    .subject-content h3 {
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    .subject-content p {
      color: #64748b;
      margin-bottom: 25px;
      flex-grow: 1;
      font-size: 1rem;
      font-family: 'Roboto', sans-serif;
    }
    .subject-link {
      color: var(--primary-color);
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      transition: all 0.3s ease;
      font-size: 1rem;
      font-family: 'Poppins', sans-serif;
    }
    .subject-link:hover {
      transform: translateX(5px);
      color: var(--secondary-color);
    }
    /* Features Section */
    .features-section {
      padding: 100px 0;
      background: linear-gradient(to bottom, #f8fafc, #ffffff);
    }
    .features-item {
      background: white;
      border-radius: 20px;
      padding: 40px 30px;
      height: 100%;
      transition: all 0.4s ease;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
      overflow: hidden;
    }
    .features-item::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
      background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
      transform: scaleY(0);
      transition: transform 0.3s ease;
      transform-origin: bottom;
    }
    .features-item:hover::before {
      transform: scaleY(1);
    }
    .features-item:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    .features-item i {
      font-size: 40px;
      margin-bottom: 25px;
      display: block;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .features-item h3 {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    /* Courses Section */
    .courses-section {
      padding: 100px 0;
      background-color: #f8fafc;
      position: relative;
    }
    .courses-section::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100px;
      background: linear-gradient(to bottom, #ffffff, #f8fafc);
      z-index: 1;
    }
    .course-item {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      transition: all 0.4s ease;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      height: 100%;
      display: flex;
      flex-direction: column;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .course-item:hover {
      transform: translateY(-15px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    .course-item img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      transition: all 0.6s ease;
    }
    .course-item:hover img {
      transform: scale(1.05);
    }
    .course-content {
      padding: 30px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    .course-category {
      display: inline-block;
      padding: 6px 14px;
      border-radius: 30px;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 15px;
      letter-spacing: 0.5px;
      font-family: 'Poppins', sans-serif;
    }
    .physics-category {
      background: rgba(231, 76, 60, 0.1);
      color: var(--physics-color);
    }
    .cs-category {
      background: rgba(52, 152, 219, 0.1);
      color: var(--cs-color);
    }
    .biology-category {
      background: rgba(46, 204, 113, 0.1);
      color: var(--biology-color);
    }
    .maths-category {
      background: rgba(243, 156, 18, 0.1);
      color: var(--maths-color);
    }
    .motivation-category {
      background: rgba(155, 89, 182, 0.1);
      color: var(--motivation-color);
    }
    .career-category {
      background: rgba(26, 188, 156, 0.1);
      color: var(--career-color);
    }
    .scholarship-category {
      background: rgba(230, 126, 34, 0.1);
      color: var(--scholarship-color);
    }
    .course-content h3 {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    .course-content p {
      color: #64748b;
      margin-bottom: 25px;
      flex-grow: 1;
      font-size: 1rem;
      font-family: 'Roboto', sans-serif;
    }
    .coming-soon-badge {
      background: linear-gradient(45deg, #ff6b6b, #ffa500);
      color: white;
      padding: 8px 16px;
      border-radius: 30px;
      font-weight: 600;
      font-size: 0.9rem;
      display: inline-block;
      animation: pulse 2s infinite;
      font-family: 'Poppins', sans-serif;
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    .trainer {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-top: 25px;
    }
    .trainer img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #f1f5f9;
    }
    .trainer-info {
      flex-grow: 1;
    }
    .trainer-info h4 {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 0;
      color: var(--dark-color);
      font-family: 'Poppins', sans-serif;
    }
    .trainer-info span {
      font-size: 0.85rem;
      color: #64748b;
      font-family: 'Roboto', sans-serif;
    }
    /* Instructors Section */
    .instructors-section {
      padding: 100px 0;
      background: linear-gradient(to bottom, #f8fafc, #ffffff);
    }
    .member {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transition: all 0.4s ease;
      height: 100%;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .member:hover {
      transform: translateY(-15px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    .member img {
      width: 100%;
      height: 280px;
      object-fit: cover;
      transition: all 0.6s ease;
    }
    .member:hover img {
      transform: scale(1.05);
    }
    .member-content {
      padding: 30px;
    }
    .member-content h4 {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 5px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    .member-content span {
      display: block;
      color: var(--primary-color);
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 20px;
      font-family: 'Poppins', sans-serif;
    }
    .member-content p {
      color: #64748b;
      margin-bottom: 25px;
      font-size: 1rem;
      font-family: 'Roboto', sans-serif;
    }
    .social-links a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(65, 84, 241, 0.1);
      color: var(--primary-color);
      margin-right: 10px;
      transition: all 0.3s ease;
    }
    .social-links a:hover {
      background: var(--primary-color);
      color: white;
      transform: translateY(-3px);
    }
    /* Quiz Section before footer */
    .quiz-section {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      padding: 120px 0;
      color: white;
      position: relative;
      overflow: hidden;
    }
    .quiz-section::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
      background-size: cover;
      background-position: center;
      opacity: 0.1;
      z-index: 0;
    }
    .quiz-content {
      position: relative;
      z-index: 1;
      text-align: center;
      max-width: 800px;
      margin: 0 auto;
    }
    .quiz-content h2 {
      font-size: 3rem;
      font-weight: 800;
      margin-bottom: 25px;
      background: linear-gradient(90deg, #ffffff, #e2e8f0);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      font-family: 'Montserrat', sans-serif;
    }
    .quiz-content p {
      font-size: 1.3rem;
      margin-bottom: 40px;
      opacity: 0.9;
      font-family: 'Roboto', sans-serif;
      font-weight: 300;
    }
    .quiz-btn {
      background: white;
      color: var(--primary-color);
      border: none;
      padding: 18px 45px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 1.2rem;
      transition: all 0.3s ease;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      display: inline-flex;
      align-items: center;
      gap: 12px;
      font-family: 'Poppins', sans-serif;
    }
    .quiz-btn:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      color: var(--primary-color);
    }
    /* Footer */
    .footer {
      background: var(--dark-color);
      color: #e2e8f0;
      padding: 100px 0 50px;
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
    .footer-newsletter h4 {
      color: white;
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 25px;
      font-family: 'Montserrat', sans-serif;
    }
    .footer-newsletter p {
      color: #94a3b8;
      margin-bottom: 25px;
      font-size: 1rem;
      font-family: 'Roboto', sans-serif;
    }
    .newsletter-form {
      display: flex;
      gap: 15px;
    }
    .newsletter-form input {
      flex-grow: 1;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 50px;
      padding: 15px 25px;
      color: white;
      font-size: 1rem;
      font-family: 'Roboto', sans-serif;
    }
    .newsletter-form input::placeholder {
      color: #94a3b8;
    }
    .newsletter-form input:focus {
      outline: none;
      border-color: var(--primary-color);
      background: rgba(255, 255, 255, 0.15);
    }
    .newsletter-form button {
      background: var(--primary-color);
      border: none;
      border-radius: 50px;
      padding: 15px 30px;
      color: white;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }
    .newsletter-form button:hover {
      background: #3141c5;
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
    /* Animations */
    .fade-in-up {
      animation: fadeInUp 0.8s ease forwards;
      opacity: 0;
    }
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .delay-1 {
      animation-delay: 0.2s;
    }
    .delay-2 {
      animation-delay: 0.4s;
    }
    .delay-3 {
      animation-delay: 0.6s;
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
    /* Responsive Design using Bootstrap classes */
    @media (max-width: 991.98px) {
      .hero h1 {
        font-size: 3rem;
      }
      
      .hero p {
        font-size: 1.2rem;
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
      .hero h1 {
        font-size: 2.5rem;
      }
      
      .hero p {
        font-size: 1.1rem;
      }
      
      .hero-buttons {
        justify-content: center;
      }
      
      .quiz-content h2 {
        font-size: 2.2rem;
      }
      
      .quiz-content p {
        font-size: 1.1rem;
      }
      
      .section-title h2 {
        font-size: 2rem;
      }
    }
    @media (max-width: 575.98px) {
      .hero h1 {
        font-size: 2rem;
      }
      
      .hero p {
        font-size: 1rem;
      }
      
      .btn-primary-modern, .btn-outline-modern {
        padding: 12px 24px;
        font-size: 0.9rem;
      }
      
      .subject-content h3 {
        font-size: 1.3rem;
      }
      
      .course-content h3 {
        font-size: 1.2rem;
      }
      
      .member-content h4 {
        font-size: 1.2rem;
      }
      
      .quiz-content h2 {
        font-size: 1.8rem;
      }
      
      .quiz-btn {
        padding: 14px 30px;
        font-size: 1rem;
      }
    }
    
    /* No data message styling */
    .no-data-message {
      text-align: center;
      padding: 40px 20px;
      background: rgba(65, 84, 241, 0.05);
      border-radius: 15px;
      margin: 20px 0;
    }
    .no-data-message h3 {
      color: var(--primary-color);
      margin-bottom: 15px;
    }
    .no-data-message p {
      color: #64748b;
      font-size: 1.1rem;
    }
    
    /* Image error handling */
    .img-error {
      background-color: #f8f9fa;
      border: 1px dashed #dee2e6;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #6c757d;
    }
    
    /* Student Marquee Styles */
    .student-marquee {
      background: linear-gradient(to right, rgba(65, 84, 241, 0.05), rgba(123, 104, 238, 0.05));
      border-radius: 15px;
      padding: 20px 0;
      margin: 30px 0;
      overflow: hidden;
      position: relative;
    }
    
    .student-marquee::before,
    .student-marquee::after {
      content: "";
      position: absolute;
      top: 0;
      width: 100px;
      height: 100%;
      z-index: 2;
    }
    
    .student-marquee::before {
      left: 0;
      background: linear-gradient(to right, #f8fafc, transparent);
    }
    
    .student-marquee::after {
      right: 0;
      background: linear-gradient(to left, #f8fafc, transparent);
    }
    
    .marquee-container {
      overflow: hidden;
      position: relative;
    }
    
    .marquee-content {
      display: flex;
      animation: marquee 30s linear infinite;
    }
    
    @keyframes marquee {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }
    
    .student-item {
      flex: 0 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 0 20px;
      text-align: center;
      transition: transform 0.3s ease;
    }
    
    .student-item:hover {
      transform: scale(1.05);
    }
    
    .student-avatar {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .student-name {
      margin-top: 8px;
      font-weight: 600;
      color: var(--dark-color);
      font-size: 0.9rem;
      max-width: 100px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .student-joined {
      font-size: 0.75rem;
      color: #64748b;
      margin-top: 4px;
    }
    
    /* Pause marquee on hover */
    .marquee-container:hover .marquee-content {
      animation-play-state: paused;
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
            <a class="nav-link active" href="index.php">Home</a>
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
            <a class="nav-link" href="test.php">Tests</a>
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
    <!-- Full Page Hero Section -->
    <section id="hero" class="hero section">
      <div class="hero-decoration decoration-1"></div>
      <div class="hero-decoration decoration-2"></div>
      <div class="hero-decoration decoration-3"></div>
      <div class="container">
        <div class="hero-content">
          <div class="row justify-content-center">
            <div class="col-lg-10 text-center">
              <h1 class="fade-in-up">Welcome to KAcademyX</h1>
              <p class="fade-in-up delay-1">Empowering learners with quality education in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships</p>
              <div class="hero-buttons fade-in-up delay-2">
                <?php if (isset($_SESSION['user'])): ?>
                  <a href="#subjects" class="btn-primary-modern">Explore Subjects</a>
                  <a href="#courses" class="btn-outline-modern">View Courses</a>
                <?php else: ?>
                  <a href="forms/login.php" class="btn-primary-modern">Login to Explore</a>
                  <a href="forms/signup.php" class="btn-outline-modern">Create Account</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Hero Section -->
    
    <!-- Stats Section -->
    <section id="stats" class="stats-section section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Our Impact</h2>
          <p>Numbers that speak volumes about our educational platform</p>
        </div>
        <div class="row gy-4">
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="stats-card">
              <div class="stats-icon">
                <i class="bi bi-people-fill"></i>
              </div>
              <div class="stats-content">
                <div class="stats-number"><?php echo $total_students; ?></div>
                <div class="stats-label">Students</div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="stats-card">
              <div class="stats-icon">
                <i class="bi bi-person-badge-fill"></i>
              </div>
              <div class="stats-content">
                <div class="stats-number"><?php echo $total_instructors; ?></div>
                <div class="stats-label">Instructors</div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="stats-card">
              <div class="stats-icon">
                <i class="bi bi-book-fill"></i>
              </div>
              <div class="stats-content">
                <div class="stats-number"><?php echo $total_courses; ?></div>
                <div class="stats-label">Courses</div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="stats-card">
              <div class="stats-icon">
                <i class="bi bi-question-circle-fill"></i>
              </div>
              <div class="stats-content">
                <div class="stats-number"><?php echo $total_mcqs; ?></div>
                <div class="stats-label">MCQs</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Stats Section -->
    
    <!-- Recent Students Marquee Section -->
    <section id="recent-students" class="features-section section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Recent Students</h2>
          <p>Meet our latest community members</p>
        </div>
        
        <?php if ($recent_students_result && $recent_students_result->num_rows > 0): ?>
          <div class="student-marquee">
            <div class="marquee-container">
              <div class="marquee-content">
                <?php 
                // Fetch all students and duplicate them for seamless scrolling
                $students = [];
                while($student = $recent_students_result->fetch_assoc()) {
                    $students[] = $student;
                }
                
                // Duplicate the array for seamless looping
                $duplicated_students = array_merge($students, $students);
                
                foreach($duplicated_students as $student): 
                ?>
                  <div class="student-item">
                    <?php 
                    // Get avatar path from students table
                    $avatar = !empty($student['avatar']) ? getImagePath($student['avatar'], 'https://via.placeholder.com/70?text=No+Image') : 'https://via.placeholder.com/70?text=No+Image';
                    ?>
                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($student['username']); ?>" class="student-avatar">
                    <div class="student-name"><?php echo htmlspecialchars($student['username']); ?></div>
                    <div class="student-joined"><?php echo date('M j', strtotime($student['created_at'])); ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="no-data-message">
            <h3>No Students Yet</h3>
            <p>Be the first to join our learning community! Sign up today to start your educational journey.</p>
          </div>
        <?php endif; ?>
      </div>
    </section><!-- /Recent Students Marquee Section -->
    
    <!-- Subject Categories Section -->
    <section id="subjects" class="subjects-section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Our Subject Areas</h2>
          <p>Explore our comprehensive range of educational disciplines</p>
        </div>
        <div class="row gy-4">
          <?php if ($use_db_subjects): ?>
            <?php while($subject = $subjects_result->fetch_assoc()): ?>
              <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="subject-card">
                  <div class="subject-icon <?php echo $subject['color']; ?>-icon">
                    <i class="bi <?php echo $subject['icon']; ?>"></i>
                  </div>
                  <div class="subject-content">
                    <h3><?php echo htmlspecialchars($subject['name']); ?></h3>
                    <p><?php echo htmlspecialchars($subject['description']); ?></p>
                    <a href="#" class="subject-link">Learn More <i class="bi bi-arrow-right"></i></a>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <?php foreach($default_subjects as $index => $subject): ?>
              <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                <div class="subject-card">
                  <div class="subject-icon <?php echo $subject['color']; ?>-icon">
                    <i class="bi <?php echo $subject['icon']; ?>"></i>
                  </div>
                  <div class="subject-content">
                    <h3><?php echo $subject['name']; ?></h3>
                    <p><?php echo $subject['description']; ?></p>
                    <a href="#" class="subject-link">Learn More <i class="bi bi-arrow-right"></i></a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Subject Categories Section -->
    
    <!-- About Section -->
    <section id="about" class="about section">
      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="100">
            <img src="https://images.unsplash.com/photo-1521791136064-7986c2920216?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80" class="img-fluid rounded-4 shadow-lg" alt="About KAcademyX">
          </div>
          <div class="col-lg-6 order-2 order-lg-1 content" data-aos="fade-up" data-aos-delay="200">
            <div class="section-title text-start" data-aos="fade-up">
              <h2>About KAcademyX</h2>
            </div>
            <p class="fst-italic fs-5">
              KAcademyX is a premier educational platform dedicated to providing high-quality learning experiences across multiple disciplines.
            </p>
            <ul class="list-unstyled mt-4">
              <li class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill text-primary me-3 fs-4"></i>
                <span>Expert-led courses in Physics, Computer Science, Biology, and Mathematics</span>
              </li>
              <li class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill text-primary me-3 fs-4"></i>
                <span>Comprehensive career guidance and scholarship resources</span>
              </li>
              <li class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill text-primary me-3 fs-4"></i>
                <span>Motivational content to inspire lifelong learning</span>
              </li>
              <li class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill text-primary me-3 fs-4"></i>
                <span>Flexible learning schedules to fit your lifestyle</span>
              </li>
            </ul>
            <a href="#" class="btn btn-primary mt-3 rounded-pill px-4 py-2">
              <span>Learn More</span>
              <i class="bi bi-arrow-right ms-2"></i>
            </a>
          </div>
        </div>
      </div>
    </section><!-- /About Section -->
    
    <!-- Features Section -->
    <section id="features" class="features-section section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Why Choose KAcademyX</h2>
          <p>Our platform offers unique advantages for your learning journey</p>
        </div>
        <div class="row gy-4">
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="features-item">
              <i class="bi bi-book"></i>
              <h3>Comprehensive Curriculum</h3>
              <p>Covering Physics, CS, Biology, Maths, and more with expertly designed courses</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="features-item">
              <i class="bi bi-people"></i>
              <h3>Expert Instructors</h3>
              <p>Learn from industry professionals and academic leaders in their fields</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="features-item">
              <i class="bi bi-briefcase"></i>
              <h3>Career Support</h3>
              <p>Get guidance on career paths and scholarship opportunities</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="features-item">
              <i class="bi bi-clock-history"></i>
              <h3>Flexible Learning</h3>
              <p>Study at your own pace with 24/7 access to all course materials</p>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Features Section -->
    
    <!-- Courses Section -->
    <section id="courses" class="courses-section section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Featured Courses</h2>
          <p>Explore our most popular educational offerings</p>
        </div>
        <div class="row">
          <?php if ($courses_result && $courses_result->num_rows > 0): ?>
            <?php while($course = $courses_result->fetch_assoc()): ?>
              <div class="col-lg-4 col-md-6 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="100">
                <div class="course-item">
                  <img src="https://images.unsplash.com/photo-1635070041078-e363dbe005cb?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80" class="img-fluid" alt="Course Image">
                  <div class="course-content">
                    <span class="course-category physics-category"><?php echo htmlspecialchars($course['title']); ?></span>
                    <h3><a href="course-details.php?id=<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></a></h3>
                    <p class="description"><?php echo substr(htmlspecialchars($course['description']), 0, 100) . '...'; ?></p>
                    <div class="trainer">
                      <?php 
                      $instructorImage = !empty($course['instructor_image']) ? getImagePath($course['instructor_image'], 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80') : 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1170&q=80';
                      ?>
                      <img src="<?php echo htmlspecialchars($instructorImage); ?>" class="img-fluid" alt="Instructor">
                      <div class="trainer-info">
                        <h4><?php echo htmlspecialchars($course['instructor_name']); ?></h4>
                        <span><?php echo htmlspecialchars($course['expertise']); ?></span>
                      </div>
                    </div>
                    <div class="mt-3">
                      <span class="coming-soon-badge">Available Now</span>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="no-data-message">
                <h3>No Courses Available</h3>
                <p>We're currently working on adding new courses. Please check back later or contact us for more information.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Courses Section -->
    
    <!-- Instructors Section -->
    <section id="instructors" class="instructors-section section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Our Instructors</h2>
          <p>Meet Our Expert Educators</p>
        </div>
        <div class="row">
          <?php if ($instructors_result && $instructors_result->num_rows > 0): ?>
            <?php while($instructor = $instructors_result->fetch_assoc()): ?>
              <div class="col-lg-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="100">
                <div class="member">
                  <?php 
                  $profileImage = !empty($instructor['profile_image']) ? getImagePath($instructor['profile_image'], 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1887&q=80') : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1887&q=80';
                  ?>
                  <img src="<?php echo htmlspecialchars($profileImage); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($instructor['name']); ?>">
                  <div class="member-content">
                    <h4><?php echo htmlspecialchars($instructor['name']); ?></h4>
                    <span><?php echo htmlspecialchars($instructor['expertise']); ?></span>
                    <p>
                      <?php 
                      if (!empty($instructor['bio'])) {
                          echo substr(htmlspecialchars($instructor['bio']), 0, 150) . '...';
                      } else {
                          echo "Expert instructor with extensive experience in " . htmlspecialchars($instructor['expertise']) . ".";
                      }
                      ?>
                    </p>
                    <div class="social-links">
                      <a href=""><i class="bi bi-twitter-x"></i></a>
                      <a href=""><i class="bi bi-linkedin"></i></a>
                      <a href=""><i class="bi bi-globe"></i></a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="no-data-message">
                <h3>No Instructors Available</h3>
                <p>We're currently recruiting new instructors. Please check back later to meet our expert educators.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Instructors Section -->
    
    <!-- Quiz Section before footer -->
    <section id="quiz" class="quiz-section">
      <div class="quiz-content">
        <h2>Test Your Knowledge</h2>
        <p>Challenge yourself with our interactive tests and assess your understanding across various subjects.</p>
        <?php if ($total_mcqs > 0): ?>
          <a href="test.php" class="quiz-btn">
            <i class="bi bi-play-circle"></i> Start Test
          </a>
        <?php else: ?>
          <div class="alert alert-light d-inline-block">
            <i class="bi bi-info-circle me-2"></i> Tests will be available soon. Please check back later.
          </div>
        <?php endif; ?>
      </div>
    </section><!-- /Quiz Section -->
  </main>
  
  <footer id="footer" class="footer position-relative">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <h3>KAcademyX</h3>
          <p class="mt-3">Premier educational platform offering courses in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships.</p>
          <div class="footer-contact pt-3">
            <p>123 Education Boulevard</p>
            <p>Learning City, LC 54321</p>
            <p class="mt-3"><strong>Phone:</strong> <span>+1 2345 67890</span></p>
            <p><strong>Email:</strong> <span>info@kacademyx.com</span></p>
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
            <li><a href="#">Home</a></li>
            <li><a href="#">About Us</a></li>
            <li><a href="#">Courses</a></li>
            <li><a href="#">Instructors</a></li>
            <li><a href="#">Resources</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Our Services</h4>
          <ul>
            <li><a href="#">Online Courses</a></li>
            <li><a href="#">Certification Programs</a></li>
            <li><a href="#">Career Guidance</a></li>
            <li><a href="#">Scholarship Assistance</a></li>
            <li><a href="#">Study Resources</a></li>
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
  
  <!-- Preloader -->
  <div id="preloader"></div>
  
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
      
      // Handle image loading errors
      document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
          // If image fails to load, show a placeholder or error message
          if (!this.classList.contains('img-error')) {
            this.classList.add('img-error');
            this.src = 'https://via.placeholder.com/400x300?text=Image+Not+Available';
            this.alt = 'Image not available';
          }
        });
      });
    });
  </script>
</body>
</html>