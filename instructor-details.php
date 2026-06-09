<?php
session_start();
require_once 'forms/db.php';

$instructor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($instructor_id <= 0) {
    header('Location: instructors.php');
    exit;
}

$instructor_result = mysqli_query($conn, "SELECT * FROM instructors WHERE id = $instructor_id");
$instructor = null;
if ($instructor_result && mysqli_num_rows($instructor_result) > 0) {
    $instructor = mysqli_fetch_assoc($instructor_result);
} else {
    header('Location: instructors.php');
    exit;
}

// Fetch related lectures
$subject = strtok($instructor['expertise'], ' ');
$lectures_result = mysqli_query($conn, "SELECT * FROM youtube_videos WHERE category LIKE '%$subject%' ORDER BY published_at DESC LIMIT 6");
$lectures = [];
if ($lectures_result && mysqli_num_rows($lectures_result) > 0) {
    while ($row = mysqli_fetch_assoc($lectures_result)) {
        $lectures[] = $row;
    }
}

$pageTitle = $instructor['name'];
$activePage = "instructors";
include "includes/header.php";

$profile_image = !empty($instructor['profile_image'])
    ? getImagePath($instructor['profile_image'], 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80')
    : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
$experience   = !empty($instructor['experience']) ? $instructor['experience'] . ' years experience' : '5+ years experience';
$qualification = !empty($instructor['qualification']) ? htmlspecialchars($instructor['qualification']) : 'Expert Educator';
?>
<main class="main">

  <!-- Page Title Banner -->
  <div class="page-title" data-aos="fade">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <h1><?php echo htmlspecialchars($instructor['name']); ?></h1>
          <p><?php echo htmlspecialchars($instructor['expertise']); ?> Instructor at KAcademyX</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Instructor Profile Section -->
  <section class="section">
    <div class="container">
      <div class="row gy-5 align-items-start">

        <!-- Photo + Social -->
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
          <div class="member" style="text-align: center;">
            <img src="<?php echo $profile_image; ?>" alt="<?php echo htmlspecialchars($instructor['name']); ?>" style="height:300px; width:100%; object-fit:cover;">
            <div class="member-content">
              <h4><?php echo htmlspecialchars($instructor['name']); ?></h4>
              <span><?php echo htmlspecialchars($instructor['expertise']); ?></span>

              <!-- Quick meta info -->
              <div style="display:flex; flex-direction:column; gap:10px; margin:16px 0; text-align:left;">
                <div style="display:flex; align-items:center; gap:10px; font-size:0.9rem; color:var(--text-secondary);">
                  <i class="bi bi-briefcase" style="color:var(--primary-color); width:18px;"></i>
                  <?php echo $experience; ?>
                </div>
                <div style="display:flex; align-items:center; gap:10px; font-size:0.9rem; color:var(--text-secondary);">
                  <i class="bi bi-mortarboard" style="color:var(--primary-color); width:18px;"></i>
                  <?php echo $qualification; ?>
                </div>
                <?php if (!empty($instructor['email'])): ?>
                <div style="display:flex; align-items:center; gap:10px; font-size:0.9rem; color:var(--text-secondary);">
                  <i class="bi bi-envelope" style="color:var(--primary-color); width:18px;"></i>
                  <?php echo htmlspecialchars($instructor['email']); ?>
                </div>
                <?php endif; ?>
              </div>

              <div class="social-links">
                <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                <a href="#" aria-label="Website"><i class="bi bi-globe"></i></a>
              </div>
            </div>
          </div>
        </div>

        <!-- Bio + Stats -->
        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
          <div class="result-header" style="text-align:left; padding:36px 40px; margin-bottom: 24px;">
            <span style="background:rgba(79,70,229,0.08); color:var(--primary-color); font-size:0.8rem; font-weight:700; padding:5px 14px; border-radius:50px; display:inline-block; margin-bottom:18px; text-transform:uppercase; letter-spacing:0.5px;">About the Instructor</span>
            <h2 style="font-size:1.6rem; color:var(--dark-color); margin-bottom:16px;"><?php echo htmlspecialchars($instructor['name']); ?></h2>
            <div style="color:var(--text-secondary); line-height:1.9; font-size:0.98rem;">
              <?php echo nl2br(htmlspecialchars($instructor['bio'])); ?>
            </div>
          </div>

          <!-- Stats Row -->
          <div class="row gy-3">
            <div class="col-md-4">
              <div class="stats-card">
                <div class="stats-icon"><i class="bi bi-play-btn-fill"></i></div>
                <div class="stats-number"><?php echo count($lectures); ?>+</div>
                <div class="stats-label">Related Lectures</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="stats-card">
                <div class="stats-icon"><i class="bi bi-briefcase-fill"></i></div>
                <div class="stats-number"><?php echo $instructor['experience'] ?? '5'; ?>+</div>
                <div class="stats-label">Years Experience</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="stats-card">
                <div class="stats-icon"><i class="bi bi-mortarboard-fill"></i></div>
                <div class="stats-number"><i class="bi bi-check-circle-fill" style="font-size:1.4rem;"></i></div>
                <div class="stats-label">Qualified Expert</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Related Lectures Section -->
  <?php if (!empty($lectures)): ?>
  <section class="section" style="background: var(--light-color);">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>Lectures in <?php echo htmlspecialchars(strtok($instructor['expertise'], ' ')); ?></h2>
        <p>Explore video lectures in <?php echo htmlspecialchars($instructor['name']); ?>'s field of expertise</p>
      </div>
      <div class="row gy-4">
        <?php foreach ($lectures as $index => $video):
          $cat_lower = strtolower($video['category']);
          $tag_map = [
            'physics' => 'physics-tag', 'computer science' => 'cs-tag',
            'biology' => 'biology-tag', 'mathematics' => 'maths-tag',
            'career guidance' => 'career-tag', 'scholarships' => 'scholarship-tag', 'motivation' => 'motivation-tag'
          ];
          $tag_class = $tag_map[$cat_lower] ?? 'general-tag';
        ?>
        <div class="col-lg-4 col-md-6 d-flex video-item-container"
             data-id="<?php echo htmlspecialchars($video['video_id']); ?>"
             data-name="<?php echo htmlspecialchars($video['title']); ?>"
             data-aos="fade-up"
             data-aos-delay="<?php echo ($index + 1) * 80; ?>">
          <div class="video-card" style="width:100%;">
            <div class="thumbnail-wrapper">
              <img src="<?php echo htmlspecialchars($video['thumbnail_url']); ?>" alt="Lecture Thumbnail">
              <div class="play-overlay">
                <div class="play-circle"><i class="bi bi-play-fill"></i></div>
              </div>
            </div>
            <div class="video-content">
              <span class="video-category <?php echo $tag_class; ?>"><?php echo htmlspecialchars($video['category']); ?></span>
              <h3><?php echo htmlspecialchars($video['title']); ?></h3>
              <p><?php echo htmlspecialchars(substr($video['description'], 0, 120)) . '...'; ?></p>
              <div class="video-meta">
                <span><i class="bi bi-camera-video me-1"></i>Lecture</span>
                <span><?php echo date('M j, Y', strtotime($video['published_at'])); ?></span>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Back to Instructors CTA -->
  <section class="section" style="padding: 40px 0;">
    <div class="container text-center">
      <a href="instructors.php" class="back-button" style="display:inline-flex;">
        <i class="bi bi-arrow-left"></i> Back to All Instructors
      </a>
    </div>
  </section>

</main>
<?php include "includes/footer.php"; ?>