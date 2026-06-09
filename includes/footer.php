  <footer id="footer" class="footer position-relative">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <h3>KAcademyX</h3>
          <p class="mt-3">Premier educational platform offering video lectures in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships.</p>
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
            <li><a href="lectures.php">Lectures</a></li>
            <li><a href="resources.php">Resources</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Our Services</h4>
          <ul>
            <li><a href="lectures.php">Video Lectures</a></li>
            <li><a href="resources.php">Study Resources</a></li>
            <li><a href="test.php">Online Tests</a></li>
          </ul>
        </div>
        <div class="col-lg-4 col-md-12 footer-newsletter">
          <h4>Our Newsletter</h4>
          <p>Subscribe to our newsletter and receive the latest updates about lectures and educational resources!</p>
          <form action="forms/newsletter.php" method="post" class="php-email-form">
            <div class="newsletter-form">
              <input type="email" name="email" placeholder="Your email address" required>
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

  <!-- Scroll Top -->
  <a href="#" class="scroll-top" id="scroll-top"><i class="bi bi-arrow-up-short"></i></a>
  
  <!-- Preloader -->
  <div id="preloader"></div>
  
  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Main JS Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      
      // Navbar background scroll effect
      const navbar = document.querySelector('.navbar');
      if (navbar) {
        const checkScroll = () => {
          if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 5px 20px rgba(0, 0, 0, 0.1)';
          } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
          }
        };
        window.addEventListener('scroll', checkScroll);
        checkScroll(); // Initial check
      }
      
      // Smooth scrolling for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          const href = this.getAttribute('href');
          if (href === '#') return;
          e.preventDefault();
          const target = document.querySelector(href);
          if (target) {
            window.scrollTo({
              top: target.offsetTop - 80,
              behavior: 'smooth'
            });
          }
        });
      });
      
      // Scroll to top button
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
      
      // Preloader removal
      const preloader = document.getElementById('preloader');
      if (preloader) {
        window.addEventListener('load', function() {
          preloader.remove();
        });
        // Fallback preloader removal after 1.5s
        setTimeout(() => {
          if (preloader) preloader.remove();
        }, 1500);
      }
      
      // Modal Video Player triggers (safe for index and lectures pages)
      const playerModal = document.getElementById('playerModal');
      const closeModal = document.getElementById('closeModal');
      const playerFrame = document.getElementById('videoPlayerFrame');
      const modalTitle = document.getElementById('modalVideoTitle');
      const modalDesc = document.getElementById('modalVideoDesc');
      const videoItems = document.querySelectorAll('.video-item-container');

      if (playerModal && playerFrame) {
        videoItems.forEach(item => {
          item.addEventListener('click', function() {
            const videoId = this.getAttribute('data-id');
            const title = this.getAttribute('data-name');
            const descParagraph = this.querySelector('p');
            const desc = descParagraph ? descParagraph.innerText : '';

            playerFrame.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
            if (modalTitle) modalTitle.innerText = title;
            if (modalDesc) modalDesc.innerText = desc;

            playerModal.classList.add('active');
            document.body.style.overflow = 'hidden';
          });
        });

        const closeVideoPlayer = () => {
          playerModal.classList.remove('active');
          playerFrame.src = '';
          document.body.style.overflow = '';
        };

        if (closeModal) {
          closeModal.addEventListener('click', closeVideoPlayer);
        }
        playerModal.addEventListener('click', function(e) {
          if (e.target === playerModal) {
            closeVideoPlayer();
          }
        });
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && playerModal.classList.contains('active')) {
            closeVideoPlayer();
          }
        });
      }

      // Handle image loading errors (broken image fallback)
      document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
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
