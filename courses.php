<?php
session_start();
require_once 'forms/db.php'; // Database connection file

// Fetch courses with instructor information
$courses_query = "SELECT c.*, i.name as instructor_name, i.expertise as instructor_expertise, i.profile_image as instructor_image 
                 FROM courses c 
                 LEFT JOIN instructors i ON c.instructor_id = i.id 
                 ORDER BY c.created_at DESC";
$courses_result = mysqli_query($conn, $courses_query);
$courses = [];
if ($courses_result && mysqli_num_rows($courses_result) > 0) {
    while ($row = mysqli_fetch_assoc($courses_result)) {
        $courses[] = $row;
    }
}

// Get unique categories for filtering
$categories = [];
foreach ($courses as $course) {
    // Infer category from course title (in a real app, you'd have a category field)
    $title_lower = strtolower($course['title']);
    if (strpos($title_lower, 'physics') !== false || strpos($title_lower, 'quantum') !== false || strpos($title_lower, 'astrophysics') !== false) {
        $category = 'Physics';
    } elseif (strpos($title_lower, 'computer') !== false || strpos($title_lower, 'machine') !== false || strpos($title_lower, 'web') !== false || strpos($title_lower, 'programming') !== false) {
        $category = 'Computer Science';
    } elseif (strpos($title_lower, 'biology') !== false || strpos($title_lower, 'genetics') !== false || strpos($title_lower, 'biotechnology') !== false) {
        $category = 'Biology';
    } elseif (strpos($title_lower, 'mathematics') !== false || strpos($title_lower, 'calculus') !== false || strpos($title_lower, 'algebra') !== false) {
        $category = 'Mathematics';
    } elseif (strpos($title_lower, 'career') !== false || strpos($title_lower, 'guidance') !== false || strpos($title_lower, 'planning') !== false) {
        $category = 'Career Guidance';
    } elseif (strpos($title_lower, 'scholarship') !== false || strpos($title_lower, 'financial') !== false) {
        $category = 'Scholarships';
    } else {
        $category = 'Other';
    }
    
    $course['category'] = $category;
    if (!in_array($category, $categories)) {
        $categories[] = $category;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Courses - KAcademyX</title>
  <meta name="description" content="Explore courses in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships at KAcademyX, Pakistan's premier educational platform">
  <meta name="keywords" content="courses, education, Pakistan, physics, computer science, biology, mathematics, career guidance, scholarships">
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
      background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80');
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
      margin-bottom: 30px;
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
    .course-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: auto;
    }
    .course-price {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--primary-color);
      font-family: 'Poppins', sans-serif;
    }
    .course-rating {
      display: flex;
      align-items: center;
      color: #f39c12;
      font-size: 0.9rem;
      font-family: 'Poppins', sans-serif;
    }
    .course-rating i {
      margin-right: 5px;
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
    .course-stats {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-top: 15px;
      font-size: 0.9rem;
      color: #64748b;
    }
    .course-stats span {
      display: flex;
      align-items: center;
      gap: 5px;
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
    /* Course Filter */
    .course-filter {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 40px;
      justify-content: center;
    }
    .filter-btn {
      background: white;
      border: 1px solid #e2e8f0;
      color: var(--dark-color);
      padding: 10px 25px;
      border-radius: 50px;
      font-weight: 500;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }
    .filter-btn:hover, .filter-btn.active {
      background: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
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
      
      .course-filter {
        justify-content: flex-start;
      }
    }
    @media (max-width: 575.98px) {
      .page-title h1 {
        font-size: 1.8rem;
      }
      
      .course-content h3 {
        font-size: 1.2rem;
      }
      
      .trainer-info h4 {
        font-size: 0.9rem;
      }
      
      .course-stats {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
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
            <a class="nav-link active" href="courses.php">Courses</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="instructors.php">Instructors</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="resources.php">Resources</a>
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
              <h1>Courses</h1>
              <p class="mb-0">Explore our comprehensive range of courses designed for Pakistani students in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships.</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Courses</li>
          </ol>
        </div>
      </nav>
    </div><!-- End Page Title -->
    
    <!-- Courses Section -->
    <section id="courses" class="courses-section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Our Courses</h2>
          <p>Discover our expertly designed courses tailored for Pakistani students</p>
        </div>
        
        <!-- Course Filter -->
        <div class="course-filter" data-aos="fade-up" data-aos-delay="100">
          <button class="filter-btn active" data-filter="all">All Courses</button>
          <?php foreach ($categories as $category): ?>
            <button class="filter-btn" data-filter="<?php echo strtolower(str_replace(' ', '-', $category)); ?>"><?php echo $category; ?></button>
          <?php endforeach; ?>
        </div>
        
        <div class="row">
          <?php if (!empty($courses)): ?>
            <?php foreach ($courses as $index => $course): ?>
              <?php
                // Determine category class
                $category_lower = strtolower($course['category']);
                $category_class = '';
                if ($category_lower == 'physics') {
                  $category_class = 'physics-category';
                } elseif ($category_lower == 'computer science') {
                  $category_class = 'cs-category';
                } elseif ($category_lower == 'biology') {
                  $category_class = 'biology-category';
                } elseif ($category_lower == 'mathematics') {
                  $category_class = 'maths-category';
                } elseif ($category_lower == 'career guidance') {
                  $category_class = 'career-category';
                } elseif ($category_lower == 'scholarships') {
                  $category_class = 'scholarship-category';
                } else {
                  $category_class = 'motivation-category';
                }
                
                // Generate a random price between 3000 and 10000 if not set
                $price = isset($course['price']) ? $course['price'] : 'Rs. ' . rand(3000, 10000);
                
                // Generate a random rating between 4.0 and 5.0
                $rating = number_format(mt_rand(40, 50) / 10, 1);
                
                // Generate random student count
                $student_count = rand(100, 500);
                
                // Generate random duration in weeks
                $duration = rand(4, 12) . ' weeks';
                
                // Generate random instructor image if not available
                $instructor_image = !empty($course['instructor_image']) ? $course['instructor_image'] : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1887&q=80';
                
                // Generate random course image if not available
                $course_image = !empty($course['image']) ? $course['image'] : 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80';
                
                // Randomly decide if course is coming soon
                $coming_soon = rand(0, 10) > 7; // 30% chance of being coming soon
              ?>
              <div class="col-lg-4 col-md-6 d-flex align-items-stretch course-item-container" data-category="<?php echo strtolower(str_replace(' ', '-', $course['category'])); ?>" data-aos="zoom-in" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                <div class="course-item">
                  <img src="<?php echo $course_image; ?>" class="img-fluid" alt="<?php echo htmlspecialchars($course['title']); ?>">
                  <div class="course-content">
                    <span class="course-category <?php echo $category_class; ?>"><?php echo $course['category']; ?></span>
                    <h3><a href="course-details.php?id=<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></a></h3>
                    <p class="description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                    <div class="course-meta">
                      <div class="course-price"><?php echo $price; ?></div>
                      <div class="course-rating">
                        <i class="bi bi-star-fill"></i> <?php echo $rating; ?>
                      </div>
                    </div>
                    <div class="trainer">
                      <img src="<?php echo $instructor_image; ?>" class="img-fluid" alt="<?php echo htmlspecialchars($course['instructor_name']); ?>">
                      <div class="trainer-info">
                        <h4><?php echo htmlspecialchars($course['instructor_name']); ?></h4>
                        <span><?php echo htmlspecialchars($course['instructor_expertise']); ?></span>
                      </div>
                    </div>
                    <div class="course-stats">
                      <span><i class="bi bi-clock"></i> <?php echo $duration; ?></span>
                      <span><i class="bi bi-person"></i> <?php echo $student_count; ?> students</span>
                    </div>
                    <?php if ($coming_soon): ?>
                      <div class="mt-3">
                        <span class="coming-soon-badge">Coming Soon</span>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div> <!-- End Course Item-->
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12 text-center">
              <p>No courses found at the moment. Please check back later.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Courses Section -->
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
            <li><a href="#">Certification Programs</a></li>
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
      
      // Course filter functionality
      const filterBtns = document.querySelectorAll('.filter-btn');
      const courseItems = document.querySelectorAll('.course-item-container');
      
      filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
          // Remove active class from all buttons
          filterBtns.forEach(b => b.classList.remove('active'));
          // Add active class to clicked button
          this.classList.add('active');
          
          const filter = this.getAttribute('data-filter');
          
          courseItems.forEach(item => {
            if (filter === 'all' || item.getAttribute('data-category') === filter) {
              item.style.display = 'flex';
            } else {
              item.style.display = 'none';
            }
          });
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
    });
  </script>
</body>
</html>