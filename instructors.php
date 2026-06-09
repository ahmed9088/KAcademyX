<?php
session_start();
require_once 'forms/db.php';

// Fetch instructors from database
$instructors_query = "SELECT * FROM instructors ORDER BY experience DESC";
$instructors_result = mysqli_query($conn, $instructors_query);
$instructors = [];
if ($instructors_result && mysqli_num_rows($instructors_result) > 0) {
    while ($row = mysqli_fetch_assoc($instructors_result)) {
        $instructors[] = $row;
    }
}

$pageTitle = "Our Instructors";
$activePage = "instructors";
include "includes/header.php";
?>
<main class="main">

  <!-- Page Title Banner -->
  <div class="page-title" data-aos="fade">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <h1>Our Instructors</h1>
          <p>Meet our team of expert educators dedicated to providing quality education to Pakistani students.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Instructors Section -->
  <section class="section">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>Expert Educators</h2>
        <p>Learn from the best educators in Pakistan</p>
      </div>

      <div class="row gy-4">
        <?php if (!empty($instructors)): ?>
          <?php foreach ($instructors as $index => $instructor): ?>
            <?php
              $profile_image = !empty($instructor['profile_image'])
                ? getImagePath($instructor['profile_image'], 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80')
                : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
              $experience = !empty($instructor['experience']) ? $instructor['experience'] . ' years' : '5+ years';
              $qualification = !empty($instructor['qualification']) ? htmlspecialchars($instructor['qualification']) : 'Expert Educator';
            ?>
            <div class="col-lg-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
              <div class="member" style="width:100%;">
                <img src="<?php echo $profile_image; ?>" alt="<?php echo htmlspecialchars($instructor['name']); ?>">
                <div class="member-content">
                  <h4><?php echo htmlspecialchars($instructor['name']); ?></h4>
                  <span><?php echo htmlspecialchars($instructor['expertise']); ?></span>
                  <p><?php echo htmlspecialchars(substr($instructor['bio'], 0, 120)) . '...'; ?></p>

                  <!-- Instructor Meta Badges -->
                  <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
                    <span style="font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 5px;">
                      <i class="bi bi-briefcase" style="color: var(--primary-color);"></i>
                      <?php echo $experience; ?>
                    </span>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 5px;">
                      <i class="bi bi-mortarboard" style="color: var(--primary-color);"></i>
                      <?php echo $qualification; ?>
                    </span>
                  </div>

                  <div class="social-links" style="margin-bottom: 16px;">
                    <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" aria-label="Website"><i class="bi bi-globe"></i></a>
                  </div>
                  <a href="instructor-details.php?id=<?php echo $instructor['id']; ?>" class="btn-primary-modern" style="font-size: 0.9rem; padding: 10px 22px; display: inline-block; text-align: center;">View Profile</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="no-data-message">
              <i class="bi bi-people" style="font-size: 3rem; color: var(--text-muted); display: block; margin-bottom: 16px;"></i>
              <h3>No Instructors Found</h3>
              <p>Instructor profiles are being updated. Please check back soon.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <div class="quiz-section">
    <div class="quiz-content">
      <h2>Want to Join Our Team?</h2>
      <p>We are always looking for passionate educators to join the KAcademyX family and help shape the future of education in Pakistan.</p>
      <a href="contact.php" class="quiz-btn"><i class="bi bi-envelope"></i> Get In Touch</a>
    </div>
  </div>

</main>
<?php include "includes/footer.php"; ?>