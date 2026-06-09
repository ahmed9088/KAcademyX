<?php session_start(); ?>
<?php 
// Include database connection
include "Admin/db.php";
// Check if database connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// Define base URL for the site dynamically including any subdirectory
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$projectRoot = str_replace('\\', '/', __DIR__);
$subDir = '';
if (strpos($projectRoot, $docRoot) === 0) {
    $subDir = substr($projectRoot, strlen($docRoot));
}
$subDir = trim($subDir, '/');
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/" . ($subDir ? $subDir . '/' : '');
// Fetch stats from the database with error handling
$total_students = 0;
$total_instructors = 0;
$total_lectures = 0;
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
    
    $lectures_result = $conn->query("SELECT COUNT(*) AS count FROM youtube_videos");
    if ($lectures_result) {
        $total_lectures = $lectures_result->fetch_assoc()['count'];
    }
    
    $mcqs_result = $conn->query("SELECT COUNT(*) AS count FROM mcq_questions");
    if ($mcqs_result) {
        $total_mcqs = $mcqs_result->fetch_assoc()['count'];
    }
} catch (Exception $e) {
    // Handle database query errors
    error_log("Database query error: " . $e->getMessage());
}
// Fetch lectures from database
$lectures_query = "SELECT * FROM youtube_videos ORDER BY published_at DESC LIMIT 6";
$lectures_result = $conn->query($lectures_query);
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
?>
<?php
$pageTitle = "Home";
$activePage = "home";
include "includes/header.php";
?>
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
                  <a href="#lectures" class="btn-outline-modern">View Lectures</a>
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
                <i class="bi bi-play-btn-fill"></i>
              </div>
              <div class="stats-content">
                <div class="stats-number"><?php echo $total_lectures; ?></div>
                <div class="stats-label">Lectures</div>
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
            <?php
            $subject_mappings = [
                'physics' => ['icon' => 'bi-atom', 'color' => 'physics'],
                'computer science' => ['icon' => 'bi-cpu', 'color' => 'cs'],
                'biology' => ['icon' => 'bi-dna', 'color' => 'biology'],
                'mathematics' => ['icon' => 'bi-calculator', 'color' => 'maths'],
                'motivation' => ['icon' => 'bi-lightbulb', 'color' => 'motivation'],
                'career guidance' => ['icon' => 'bi-briefcase', 'color' => 'career'],
                'scholarships' => ['icon' => 'bi-award', 'color' => 'scholarship']
            ];
            while($subject = $subjects_result->fetch_assoc()):
                $sub_key = strtolower($subject['name']);
                $mapping = $subject_mappings[$sub_key] ?? ['icon' => 'bi-book', 'color' => 'general'];
                $icon = $mapping['icon'];
                $color = $mapping['color'];
            ?>
              <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="subject-card">
                  <div class="subject-icon <?php echo $color; ?>-icon">
                    <i class="bi <?php echo $icon; ?>"></i>
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
    
    <!-- Lectures Section -->
    <section id="lectures" class="courses-section section">
      <div class="container">
        <div class="section-title" data-aos="fade-up">
          <h2>Featured Video Lectures</h2>
          <p>Explore our latest educational and interactive video guides</p>
        </div>
        <div class="row gy-4">
          <?php if ($lectures_result && $lectures_result->num_rows > 0): ?>
            <?php 
            $lect_index = 0;
            while($video = $lectures_result->fetch_assoc()): 
              $cat_lower = strtolower($video['category']);
              $tag_class = 'general-tag';
              if ($cat_lower == 'physics') $tag_class = 'physics-tag';
              elseif ($cat_lower == 'computer science') $tag_class = 'cs-tag';
              elseif ($cat_lower == 'biology') $tag_class = 'biology-tag';
              elseif ($cat_lower == 'mathematics') $tag_class = 'maths-tag';
              elseif ($cat_lower == 'career guidance') $tag_class = 'career-tag';
              elseif ($cat_lower == 'scholarships') $tag_class = 'scholarship-tag';
              elseif ($cat_lower == 'motivation') $tag_class = 'motivation-tag';
            ?>
              <div class="col-lg-4 col-md-6 d-flex align-items-stretch video-item-container" 
                   data-id="<?php echo $video['video_id']; ?>"
                   data-name="<?php echo htmlspecialchars($video['title']); ?>"
                   data-aos="zoom-in" data-aos-delay="<?php echo ($lect_index + 1) * 100; ?>">
                <div class="video-card">
                  <div class="thumbnail-wrapper">
                    <img src="<?php echo htmlspecialchars($video['thumbnail_url']); ?>" alt="Lecture Thumbnail">
                    <div class="play-overlay">
                      <div class="play-circle">
                        <i class="bi bi-play-fill"></i>
                      </div>
                    </div>
                  </div>
                  <div class="video-content">
                    <span class="video-category <?php echo $tag_class; ?>"><?php echo htmlspecialchars($video['category']); ?></span>
                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                    <p><?php echo htmlspecialchars($video['description']); ?></p>
                    <div class="video-meta">
                      <span><i class="bi bi-clock me-1"></i> Lecture</span>
                      <span><?php echo date('M j, Y', strtotime($video['published_at'])); ?></span>
                    </div>
                  </div>
                </div>
              </div>
            <?php 
              $lect_index++;
            endwhile; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="no-data-message">
                <h3>No Video Lectures Available</h3>
                <p>We are currently working on adding new video lectures. Please check back later.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section><!-- /Lectures Section -->
    
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
  <!-- Premium Modal Video Player -->
  <div class="player-modal" id="playerModal">
    <button class="close-modal-btn" id="closeModal"><i class="bi bi-x-lg"></i></button>
    <div class="modal-container">
      <div class="video-wrapper">
        <iframe id="videoPlayerFrame" src="" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
      </div>
      <div class="modal-details">
        <h2 id="modalVideoTitle">Lecture</h2>
        <p id="modalVideoDesc">Loading description...</p>
      </div>
    </div>
  </div>

  </main>
  
<?php include "includes/footer.php"; ?>