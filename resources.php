<?php
session_start();
require_once 'forms/db.php';

// Fetch resources from database
$resources = [];
$resources_result = $conn->query("SELECT * FROM resources ORDER BY id DESC");
if ($resources_result && $resources_result->num_rows > 0) {
    while ($row = $resources_result->fetch_assoc()) {
        $resources[] = $row;
    }
}
$categories = array_unique(array_column($resources, 'category'));

$pageTitle = "Study Resources";
$activePage = "resources";
include "includes/header.php";
?>
<main class="main">

  <!-- Page Title Banner -->
  <div class="page-title" data-aos="fade">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <h1>Educational Resources</h1>
          <p>Access our comprehensive collection of educational materials in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Resources Section -->
  <section class="section">
    <div class="container">
      <div class="section-title" data-aos="fade-up">
        <h2>Learning Resources</h2>
        <p>Download and access our curated collection of educational materials</p>
      </div>

      <!-- Category Filters -->
      <div class="video-filter" data-aos="fade-up" data-aos-delay="100">
        <button class="filter-btn active" data-filter="all">All Resources</button>
        <?php foreach ($categories as $category): ?>
          <button class="filter-btn" data-filter="<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
            <?php echo htmlspecialchars($category); ?>
          </button>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($resources)): ?>
      <div class="row gy-4" id="resources-grid">
        <?php foreach ($resources as $index => $resource):
          $cat_lower = strtolower($resource['category']);
          // Map category to tag class
          $tag_class_map = [
            'physics'         => 'physics-tag',
            'computer science'=> 'cs-tag',
            'biology'         => 'biology-tag',
            'mathematics'     => 'maths-tag',
            'career guidance' => 'career-tag',
            'scholarships'    => 'scholarship-tag',
          ];
          $tag_class = $tag_class_map[$cat_lower] ?? 'general-tag';
        ?>
        <div class="col-lg-4 col-md-6 d-flex resource-item-container"
             data-category="<?php echo strtolower(str_replace(' ', '-', $resource['category'])); ?>"
             data-aos="fade-up"
             data-aos-delay="<?php echo (($index % 3) + 1) * 100; ?>">
          <div class="video-card" style="width:100%; cursor: default;">
            <!-- Resource Thumbnail -->
            <div class="thumbnail-wrapper">
              <img src="<?php echo htmlspecialchars($resource['image']); ?>"
                   alt="<?php echo htmlspecialchars($resource['title']); ?>"
                   onerror="this.src='https://via.placeholder.com/400x225?text=Resource'">
              <!-- File type badge overlay -->
              <div style="position:absolute; top:12px; right:12px; background: var(--dark-color); color: #fff; font-size:0.75rem; font-weight:700; padding: 4px 10px; border-radius: 8px; text-transform: uppercase;">
                <?php echo htmlspecialchars($resource['type'] ?? 'PDF'); ?>
              </div>
            </div>
            <!-- Resource Content -->
            <div class="video-content">
              <span class="video-category <?php echo $tag_class; ?>"><?php echo htmlspecialchars($resource['category']); ?></span>
              <h3><?php echo htmlspecialchars($resource['title']); ?></h3>
              <p><?php echo htmlspecialchars($resource['description']); ?></p>
              <div class="video-meta">
                <span><i class="bi bi-hdd me-1"></i><?php echo htmlspecialchars($resource['size'] ?? 'N/A'); ?></span>
                <span><i class="bi bi-download me-1"></i><?php echo number_format($resource['downloads'] ?? 0); ?> downloads</span>
              </div>
              <a href="download.php?id=<?php echo $resource['id']; ?>"
                 class="btn-primary-modern"
                 style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 18px; font-size: 0.9rem; padding: 11px 20px;">
                <i class="bi bi-download"></i> Download Resource
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
        <div class="no-data-message" data-aos="fade-up">
          <i class="bi bi-folder" style="font-size: 3rem; color: var(--text-muted); display: block; margin-bottom: 16px;"></i>
          <h3>No Resources Yet</h3>
          <p>Resources are being added. Check back soon for study materials, notes, and more.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

</main>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const resourceItems = document.querySelectorAll('.resource-item-container');

    filterBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        filterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const filter = this.getAttribute('data-filter');
        resourceItems.forEach(item => {
          if (filter === 'all' || item.getAttribute('data-category') === filter) {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        });
      });
    });
  });
</script>

<?php include "includes/footer.php"; ?>