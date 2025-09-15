<?php
session_start();
require_once 'forms/db.php'; // Database connection file

// Fetch instructors from database
$instructors_query = "SELECT * FROM instructors ORDER BY experience DESC LIMIT 3";
$instructors_result = mysqli_query($conn, $instructors_query);
$instructors = [];
if ($instructors_result && mysqli_num_rows($instructors_result) > 0) {
    while ($row = mysqli_fetch_assoc($instructors_result)) {
        $instructors[] = $row;
    }
}

// Fetch courses count
$courses_count_query = "SELECT COUNT(*) as count FROM courses";
$courses_count_result = mysqli_query($conn, $courses_count_query);
$courses_count = 0;
if ($courses_count_result) {
    $row = mysqli_fetch_assoc($courses_count_result);
    $courses_count = $row['count'];
}

// Fetch students count
$students_count_query = "SELECT COUNT(*) as count FROM students";
$students_count_result = mysqli_query($conn, $students_count_query);
$students_count = 0;
if ($students_count_result) {
    $row = mysqli_fetch_assoc($students_count_result);
    $students_count = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>About - KAcademyX</title>
  <meta name="description" content="Learn about KAcademyX, Pakistan's premier educational platform offering courses in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships">
  <meta name="keywords" content="KAcademyX, education, Pakistan, online learning, physics, computer science, biology, mathematics">
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
    /* About Section */
    .about-section {
      padding: 100px 0;
      background-color: #f8fafc;
    }
    .about-img {
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    .about-img img {
      width: 100%;
      height: auto;
      transition: transform 0.5s ease;
    }
    .about-img:hover img {
      transform: scale(1.03);
    }
    .about-content h3 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    .about-content p {
      color: #64748b;
      margin-bottom: 25px;
      font-size: 1.1rem;
      font-family: 'Roboto', sans-serif;
    }
    .about-content ul {
      list-style: none;
      padding: 0;
      margin: 0 0 30px;
    }
    .about-content ul li {
      display: flex;
      align-items: flex-start;
      margin-bottom: 15px;
      font-size: 1.05rem;
      color: #334155;
    }
    .about-content ul li i {
      color: var(--primary-color);
      margin-right: 15px;
      font-size: 1.2rem;
      margin-top: 5px;
    }
    .about-btn {
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      color: white;
      border: none;
      padding: 14px 32px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 1.05rem;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(65, 84, 241, 0.3);
      display: inline-block;
      font-family: 'Poppins', sans-serif;
    }
    .about-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(65, 84, 241, 0.5);
      color: white;
    }
    /* Mission Vision Section */
    .mission-vision {
      padding: 100px 0;
      background: linear-gradient(to bottom, #f8fafc, #ffffff);
    }
    .mission-vision-item {
      background: white;
      border-radius: 20px;
      padding: 40px;
      height: 100%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transition: all 0.4s ease;
      border: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
      overflow: hidden;
    }
    .mission-vision-item::before {
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
    .mission-vision-item:hover::before {
      transform: scaleY(1);
    }
    .mission-vision-item:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    .mission-vision-item i {
      font-size: 40px;
      margin-bottom: 25px;
      display: block;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .mission-vision-item h3 {
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    .mission-vision-item p {
      color: #64748b;
      font-size: 1.05rem;
      font-family: 'Roboto', sans-serif;
    }
    /* Team Section */
    .team-section {
      padding: 100px 0;
      background-color: #f8fafc;
    }
    .team-member {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      transition: all 0.4s ease;
      height: 100%;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .team-member:hover {
      transform: translateY(-15px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    .team-member img {
      width: 100%;
      height: 280px;
      object-fit: cover;
      transition: all 0.6s ease;
    }
    .team-member:hover img {
      transform: scale(1.05);
    }
    .team-member-content {
      padding: 30px;
    }
    .team-member-content h4 {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 5px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    .team-member-content span {
      display: block;
      color: var(--primary-color);
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 20px;
      font-family: 'Poppins', sans-serif;
    }
    .team-member-content p {
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
    /* Values Section */
    .values-section {
      padding: 100px 0;
      background: linear-gradient(to bottom, #f8fafc, #ffffff);
    }
    .value-item {
      background: white;
      border-radius: 20px;
      padding: 40px 30px;
      height: 100%;
      transition: all 0.4s ease;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
      overflow: hidden;
      text-align: center;
    }
    .value-item::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
      transform: scaleX(0);
      transition: transform 0.3s ease;
      transform-origin: left;
    }
    .value-item:hover::before {
      transform: scaleX(1);
    }
    .value-item:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    .value-item i {
      font-size: 40px;
      margin-bottom: 25px;
      display: block;
      background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .value-item h3 {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 15px;
      color: var(--dark-color);
      font-family: 'Montserrat', sans-serif;
    }
    .value-item p {
      color: #64748b;
      font-size: 1rem;
      font-family: 'Roboto', sans-serif;
    }
    /* Stats Section */
    .stats-section {
      padding: 80px 0;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
    }
    .stats-item {
      text-align: center;
      padding: 20px;
    }
    .stats-item i {
      font-size: 2.5rem;
      margin-bottom: 15px;
      display: block;
    }
    .stats-item h3 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 10px;
    }
    .stats-item p {
      font-size: 1.1rem;
      opacity: 0.9;
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
    }
    @media (max-width: 575.98px) {
      .page-title h1 {
        font-size: 1.8rem;
      }
      
      .about-content h3 {
        font-size: 1.6rem;
      }
      
      .mission-vision-item h3 {
        font-size: 1.4rem;
      }
      
      .team-member-content h4 {
        font-size: 1.2rem;
      }
      
      .value-item h3 {
        font-size: 1.2rem;
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
            <a class="nav-link active" href="about.php">About</a>
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
              <h1>About KAcademyX</h1>
              <p class="mb-0">Empowering Pakistani students with quality education in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships.</p>
            </div>
          </div>
        </div>
      </div>
      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">About Us</li>
          </ol>
        </div>
      </nav>
    </div><!-- End Page Title -->
    
    <!-- About Section -->
    <section id="about" class="about-section">
      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="100">
            <div class="about-img">
              <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80" class="img-fluid" alt="KAcademyX Education">
            </div>
          </div>
          <div class="col-lg-6 order-2 order-lg-1 content" data-aos="fade-up" data-aos-delay="200">
            <h3>Pakistan's Premier Educational Platform</h3>
            <p class="fst-italic">
              KAcademyX is a pioneering educational platform in Pakistan, dedicated to providing high-quality learning experiences to students across the country.
            </p>
            <ul>
              <li><i class="bi bi-check-circle-fill"></i> <span>Expert-led courses in Physics, Computer Science, Biology, and Mathematics</span></li>
              <li><i class="bi bi-check-circle-fill"></i> <span>Comprehensive career guidance and scholarship resources for Pakistani students</span></li>
              <li><i class="bi bi-check-circle-fill"></i> <span>Motivational content to inspire lifelong learning and success</span></li>
              <li><i class="bi bi-check-circle-fill"></i> <span>Flexible learning schedules to fit the lifestyle of Pakistani students</span></li>
            </ul>
            <a href="#team" class="about-btn">Meet Our Team</a>
          </div>
        </div>
      </div>
    </section><!-- /About Section -->
    
    <!-- Stats Section -->
    <section id="stats" class="stats-section">
      <div class="container">
        <div class="row gy-4">
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="stats-item">
              <i class="bi bi-book"></i>
              <h3><?php echo $courses_count; ?>+</h3>
              <p>Courses Available</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="stats-item">
              <i class="bi bi-people"></i>
              <h3><?php echo count($instructors); ?>+</h3>
              <p>Expert Instructors</p>
            </div>
          </div>
          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="stats-item">
              <i class="bi bi-person-check"></i>
              <h3><?php echo $students_count; ?>+</h3>
              <p>Active Students</p>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Stats Section -->
    
    <!-- Mission Vision Section -->
    <section id="mission-vision" class="mission-vision">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Our Mission & Vision</h2>
          <p>Building a brighter future for Pakistan through education</p>
        </div>
        <div class="row gy-4">
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
            <div class="mission-vision-item">
              <i class="bi bi-bullseye"></i>
              <h3>Our Mission</h3>
              <p>To provide accessible, high-quality education to every student in Pakistan, regardless of their background or location. We aim to bridge the educational gap and empower the youth with knowledge and skills that will drive Pakistan's future growth and development.</p>
            </div>
          </div>
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <div class="mission-vision-item">
              <i class="bi bi-eye"></i>
              <h3>Our Vision</h3>
              <p>To become the leading educational platform in Pakistan, recognized for excellence in teaching and learning. We envision a Pakistan where every student has access to world-class education, enabling them to compete globally and contribute to the nation's progress.</p>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Mission Vision Section -->
    
    <!-- Team Section -->
    <section id="team" class="team-section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Our Leadership Team</h2>
          <p>Meet the minds behind KAcademyX</p>
        </div>
        <div class="row">
          <?php if (!empty($instructors)): ?>
            <?php foreach ($instructors as $index => $instructor): ?>
              <div class="col-lg-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                <div class="team-member">
                  <img src="<?php echo !empty($instructor['profile_image']) ? $instructor['profile_image'] : 'https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1887&q=80'; ?>" class="img-fluid" alt="<?php echo htmlspecialchars($instructor['name']); ?>">
                  <div class="team-member-content">
                    <h4><?php echo htmlspecialchars($instructor['name']); ?></h4>
                    <span><?php echo htmlspecialchars($instructor['expertise']); ?></span>
                    <p>
                      <?php echo htmlspecialchars(substr($instructor['bio'], 0, 100)) . '...'; ?>
                    </p>
                    <div class="social-links">
                      <a href=""><i class="bi bi-twitter-x"></i></a>
                      <a href=""><i class="bi bi-linkedin"></i></a>
                      <a href=""><i class="bi bi-globe"></i></a>
                    </div>
                  </div>
                </div>
              </div><!-- End Team Member -->
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12 text-center">
              <p>No instructors found at the moment.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Team Section -->
    
    <!-- Values Section -->
    <section id="values" class="values-section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Our Core Values</h2>
          <p>Principles that guide our work</p>
        </div>
        <div class="row gy-4">
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="value-item">
              <i class="bi bi-book"></i>
              <h3>Excellence</h3>
              <p>We strive for excellence in everything we do, from course content to student support.</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="value-item">
              <i class="bi bi-heart"></i>
              <h3>Integrity</h3>
              <p>We operate with honesty, transparency, and ethical practices in all our interactions.</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="value-item">
              <i class="bi bi-people"></i>
              <h3>Inclusivity</h3>
              <p>We believe in providing equal educational opportunities to all Pakistani students.</p>
            </div>
          </div>
          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="value-item">
              <i class="bi bi-lightbulb"></i>
              <h3>Innovation</h3>
              <p>We embrace new technologies and teaching methods to enhance learning experiences.</p>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Values Section -->
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