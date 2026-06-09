<?php
// lectures.php
session_start();
require_once 'forms/db.php'; // Database connection file

// Fetch videos from the database
$videos = [];
$videos_result = $conn->query("SELECT * FROM youtube_videos ORDER BY published_at DESC");
if ($videos_result && $videos_result->num_rows > 0) {
    while ($row = $videos_result->fetch_assoc()) {
        $videos[] = $row;
    }
}

// Get unique categories for filtering
$categories = array_unique(array_column($videos, 'category'));
?>
<?php
$pageTitle = "Video Lectures";
$activePage = "lectures";
include "includes/header.php";
?>

  <main class="main">
    <!-- Page Title -->
    <div class="page-title" data-aos="fade">
      <div class="container">
        <div class="row justify-content-center text-center">
          <div class="col-lg-8">
            <h1>Video Lectures</h1>
            <p>Deep-dive into academic topics with curated, highly interactive video series and guides.</p>
          </div>
        </div>
      </div>
    </div>


    <!-- Search input -->
    <div class="search-container">
      <i class="bi bi-search search-icon"></i>
      <input type="text" id="videoSearch" class="search-input" placeholder="Search by lecture title or description...">
    </div>

    <!-- Video Hub Section -->
    <section id="videos" class="section" style="padding-top: 20px;">
      <div class="container">
        
        <!-- Categories Filter -->
        <div class="video-filter" data-aos="fade-up" data-aos-delay="100">
          <button class="filter-btn active" data-filter="all">All Lectures</button>
          <?php foreach ($categories as $category): ?>
            <button class="filter-btn" data-filter="<?php echo strtolower(str_replace(' ', '-', $category)); ?>"><?php echo htmlspecialchars($category); ?></button>
          <?php endforeach; ?>
        </div>

        <!-- Videos Grid -->
        <div class="row gy-4" id="videoGrid">
          <?php if (count($videos) > 0): ?>
            <?php foreach ($videos as $index => $video): 
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
              <a href="watch.php?video_id=<?php echo urlencode($video['video_id']); ?>" class="col-lg-4 col-md-6 d-flex align-items-stretch video-item-container text-decoration-none" 
                   data-category="<?php echo strtolower(str_replace(' ', '-', $video['category'])); ?>" 
                   data-title="<?php echo htmlspecialchars(strtolower($video['title'])); ?>"
                   data-desc="<?php echo htmlspecialchars(strtolower($video['description'])); ?>"
                   data-id="<?php echo $video['video_id']; ?>"
                   data-name="<?php echo htmlspecialchars($video['title']); ?>"
                   data-aos="fade-up" 
                   data-aos-delay="<?php echo ($index + 1) * 80; ?>">
                
                <div class="video-card w-100">
                  <div class="thumbnail-wrapper">
                    <img src="<?php echo htmlspecialchars($video['thumbnail_url']); ?>" alt="Lecture Thumbnail">
                    <div class="play-overlay">
                      <div class="play-circle">
                        <i class="bi bi-play-fill"></i>
                      </div>
                    </div>
                  </div>
                  
                  <div class="video-content text-start">
                    <span class="video-category <?php echo $tag_class; ?>"><?php echo htmlspecialchars($video['category']); ?></span>
                    <h3 class="text-dark fw-bold" style="font-size: 1.15rem;"><?php echo htmlspecialchars($video['title']); ?></h3>
                    <p class="text-secondary small"><?php echo htmlspecialchars($video['description']); ?></p>
                    <div class="video-meta text-muted">
                      <span><i class="bi bi-clock me-1"></i> Lecture</span>
                      <span><?php echo date('M j, Y', strtotime($video['published_at'])); ?></span>
                    </div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12 text-center py-5">
              <div class="fs-4 text-muted"><i class="bi bi-camera-video-off fs-1 d-block mb-3"></i>No video lectures found in the database.</div>
              <p class="text-muted">Please log in to the administrator panel and click "Seed Mock Video Hub" or run imports.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <!-- Page-specific video filter & search logic -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Filter and Search states
      const filterBtns = document.querySelectorAll('.filter-btn');
      const searchInput = document.getElementById('videoSearch');
      const videoItems = document.querySelectorAll('.video-item-container');
      
      let currentFilter = 'all';
      let searchQuery = '';

      function applyFilterAndSearch() {
        videoItems.forEach(item => {
          const category = item.getAttribute('data-category');
          const title = item.getAttribute('data-title');
          const desc = item.getAttribute('data-desc');
          
          const matchesFilter = (currentFilter === 'all' || category === currentFilter);
          const matchesSearch = (searchQuery === '' || title.includes(searchQuery) || desc.includes(searchQuery));
          
          if (matchesFilter && matchesSearch) {
            item.style.display = 'flex';
          } else {
            item.style.display = 'none';
          }
        });
      }

      // Filter clicks
      if (filterBtns) {
        filterBtns.forEach(btn => {
          btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');
            applyFilterAndSearch();
          });
        });
      }

      // Search typing
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          searchQuery = this.value.toLowerCase().trim();
          applyFilterAndSearch();
        });
      }
    });
  </script>

<?php include "includes/footer.php"; ?>
