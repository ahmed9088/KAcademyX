<?php
session_start();
require_once 'forms/db.php';

// Fetch instructors from database
$instructors_query = "SELECT * FROM instructors ORDER BY experience DESC LIMIT 3";
$instructors_result = mysqli_query($conn, $instructors_query);
$instructors = [];
if ($instructors_result && mysqli_num_rows($instructors_result) > 0) {
    while ($row = mysqli_fetch_assoc($instructors_result)) {
        $instructors[] = $row;
    }
}

// Fetch lectures count (replaced "courses")
$lectures_count_query = "SELECT COUNT(*) as count FROM youtube_videos";
$lectures_count_result = mysqli_query($conn, $lectures_count_query);
$lectures_count = 0;
if ($lectures_count_result) {
    $row = mysqli_fetch_assoc($lectures_count_result);
    $lectures_count = $row['count'];
}

// Fetch students count
$students_count_query = "SELECT COUNT(*) as count FROM students";
$students_count_result = mysqli_query($conn, $students_count_query);
$students_count = 0;
if ($students_count_result) {
    $row = mysqli_fetch_assoc($students_count_result);
    $students_count = $row['count'];
}

$pageTitle = "About Us";
$activePage = "about";
include "includes/header.php";
?>
<main class="main">

  <!-- Page Title Banner -->
  <div class="page-title" data-aos="fade">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <h1>About KAcademyX</h1>
          <p>Empowering Pakistani students with quality education in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- About Story Section -->
  <section class="section" style="padding-top: 80px;">
    <div class="container">
      <div class="row gy-5 align-items-center">
        <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="100">
          <img src="assets/img/about.png"
               class="img-fluid rounded-3 shadow"
               style="border-radius: 20px !important;"
               alt="KAcademyX Education">
        </div>
        <div class="col-lg-6 order-2 order-lg-1" data-aos="fade-up" data-aos-delay="200">
          <div style="padding-right: 30px;">
            <span style="background: rgba(79,70,229,0.08); color: var(--primary-color); font-weight: 700; font-size: 0.85rem; padding: 6px 16px; border-radius: 50px; display: inline-block; margin-bottom: 20px; letter-spacing: 0.5px; text-transform: uppercase;">Our Story</span>
            <h2 style="font-size: 2.2rem; font-weight: 800; color: var(--dark-color); margin-bottom: 18px; line-height: 1.25;">Pakistan's Premier Educational Platform</h2>
            <p style="color: var(--text-secondary); margin-bottom: 20px; line-height: 1.8;">KAcademyX is a pioneering educational platform in Pakistan, dedicated to providing high-quality learning experiences to students across the country, empowering every learner to reach their full potential.</p>
            <ul style="list-style: none; padding: 0; margin: 0 0 30px;">
              <?php
              $features = [
                "Expert-led lectures in Physics, Computer Science, Biology, and Mathematics",
                "Comprehensive career guidance and scholarship resources for Pakistani students",
                "Motivational content to inspire lifelong learning and success",
                "Flexible learning schedules to fit every student's lifestyle"
              ];
              foreach ($features as $feature):
              ?>
              <li style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px;">
                <span style="width: 24px; height: 24px; border-radius: 50%; background: rgba(79,70,229,0.1); color: var(--primary-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; font-size: 0.8rem;">
                  <i class="bi bi-check-lg"></i>
                </span>
                <span style="color: var(--text-secondary);"><?php echo $feature; ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
            <a href="instructors.php" class="btn-primary-modern">Meet Our Team</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats-section">
    <div class="container">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
          <div class="stats-card">
            <div class="stats-icon"><i class="bi bi-play-btn-fill"></i></div>
            <div class="stats-number"><?php echo $lectures_count; ?>+</div>
            <div class="stats-label">Lectures Available</div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
          <div class="stats-card">
            <div class="stats-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stats-number"><?php echo count($instructors); ?>+</div>
            <div class="stats-label">Expert Instructors</div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
          <div class="stats-card">
            <div class="stats-icon"><i class="bi bi-person-check-fill"></i></div>
            <div class="stats-number"><?php echo $students_count; ?>+</div>
            <div class="stats-label">Active Students</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Mission & Vision -->
  <section class="section" style="background: var(--light-color);">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>Our Mission & Vision</h2>
        <p>Building a brighter future for Pakistan through education</p>
      </div>
      <div class="row gy-4">
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
          <div class="feature-card" style="border-left: 4px solid var(--primary-color);">
            <i class="bi bi-bullseye"></i>
            <h3>Our Mission</h3>
            <p>To provide accessible, high-quality education to every student in Pakistan, regardless of their background or location. We aim to bridge the educational gap and empower the youth with knowledge and skills that will drive Pakistan's future growth and development.</p>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
          <div class="feature-card" style="border-left: 4px solid var(--accent-color);">
            <i class="bi bi-eye" style="color: var(--accent-color);"></i>
            <h3>Our Vision</h3>
            <p>To become the leading educational platform in Pakistan, recognized for excellence in teaching and learning. We envision a Pakistan where every student has access to world-class education, enabling them to compete globally and contribute to the nation's progress.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Team Section -->
  <section class="section" id="team">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>Our Leadership Team</h2>
        <p>Meet the minds behind KAcademyX</p>
      </div>
      <div class="row gy-4">
        <?php if (!empty($instructors)): ?>
          <?php foreach ($instructors as $index => $instructor): ?>
            <div class="col-lg-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
              <div class="member">
                <img src="<?php echo !empty($instructor['profile_image']) ? getImagePath($instructor['profile_image'], 'https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1887&q=80') : 'https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1887&q=80'; ?>"
                     alt="<?php echo htmlspecialchars($instructor['name']); ?>">
                <div class="member-content">
                  <h4><?php echo htmlspecialchars($instructor['name']); ?></h4>
                  <span><?php echo htmlspecialchars($instructor['expertise']); ?></span>
                  <p><?php echo htmlspecialchars(substr($instructor['bio'], 0, 110)) . '...'; ?></p>
                  <div class="social-links">
                    <a href="#"><i class="bi bi-twitter-x"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                    <a href="#"><i class="bi bi-globe"></i></a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="no-data-message">
              <h3>Team Coming Soon</h3>
              <p>Instructor profiles are being updated. Check back shortly.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Core Values Section -->
  <section class="section features-section">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>Our Core Values</h2>
        <p>Principles that guide our work every day</p>
      </div>
      <div class="row gy-4">
        <?php
        $values = [
          ["bi-book", "Excellence", "We strive for excellence in everything we do, from lecture content to student support."],
          ["bi-heart", "Integrity", "We operate with honesty, transparency, and ethical practices in all our interactions."],
          ["bi-people", "Inclusivity", "We believe in providing equal educational opportunities to all Pakistani students."],
          ["bi-lightbulb", "Innovation", "We embrace new technologies and teaching methods to enhance learning experiences."],
        ];
        foreach ($values as $i => $v):
        ?>
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($i + 1) * 100; ?>">
          <div class="features-item">
            <i class="bi <?php echo $v[0]; ?>"></i>
            <h3><?php echo $v[1]; ?></h3>
            <p><?php echo $v[2]; ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</main>
<?php include "includes/footer.php"; ?>