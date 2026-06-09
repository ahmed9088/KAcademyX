<?php
session_start();
require_once 'forms/db.php';

// Process contact form submission
$success_message = '';
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = mysqli_real_escape_string($conn, $_POST['name']);
    $email   = mysqli_real_escape_string($conn, $_POST['email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $insert_query = "INSERT INTO contact_messages (name, email, subject, message, created_at)
                    VALUES ('$name', '$email', '$subject', '$message', NOW())";

    if (mysqli_query($conn, $insert_query)) {
        $success_message = "Your message has been sent. Thank you!";
    } else {
        $error_message = "Sorry, there was an error sending your message. Please try again later.";
    }
}

$pageTitle = "Contact Us";
$activePage = "contact";
include "includes/header.php";
?>
<main class="main">

  <!-- Page Title Banner -->
  <div class="page-title" data-aos="fade">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <h1>Get In Touch</h1>
          <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Contact Section -->
  <section class="section">
    <div class="container">

      <div class="row gy-5 align-items-start">

        <!-- Contact Info Cards -->
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
          <div class="section-title" style="text-align: left; margin-bottom: 30px;">
            <h2 style="font-size: 1.8rem;">Contact Information</h2>
          </div>

          <?php
          $contact_items = [
            ["bi-geo-alt-fill", "Our Address", "1-Educator Boulevard, Faisal Town", "Lahore, Punjab 54000"],
            ["bi-telephone-fill", "Call Us", "+92 300 1234567", "Mon – Fri: 9am – 6pm"],
            ["bi-envelope-fill", "Email Us", "info@kacademyx.pk", "support@kacademyx.pk"],
          ];
          foreach ($contact_items as $i => $item):
          ?>
          <div class="feature-card d-flex align-items-start gap-3 mb-3"
               data-aos="fade-up"
               data-aos-delay="<?php echo ($i + 1) * 100; ?>"
               style="padding: 20px 24px;">
            <div style="width:44px; height:44px; border-radius:12px; background:rgba(79,70,229,0.1); color:var(--primary-color); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0;">
              <i class="bi <?php echo $item[0]; ?>"></i>
            </div>
            <div>
              <h3 style="font-size:1rem; margin-bottom: 6px;"><?php echo $item[1]; ?></h3>
              <p style="font-size:0.9rem; color:var(--text-secondary); margin:0; line-height: 1.6;"><?php echo $item[2]; ?><br><?php echo $item[3]; ?></p>
            </div>
          </div>
          <?php endforeach; ?>

          <!-- Social Links -->
          <div style="display:flex; gap:10px; margin-top: 20px;" data-aos="fade-up" data-aos-delay="400">
            <?php foreach ([['bi-twitter-x','Twitter'],['bi-facebook','Facebook'],['bi-instagram','Instagram'],['bi-linkedin','LinkedIn']] as $soc): ?>
            <a href="#" aria-label="<?php echo $soc[1]; ?>"
               style="width:40px; height:40px; border-radius:50%; background:rgba(79,70,229,0.06); color:var(--primary-color); display:flex; align-items:center; justify-content:center; font-size:1.1rem; transition: var(--transition-smooth);"
               onmouseover="this.style.background='var(--primary-color)'; this.style.color='#fff';"
               onmouseout="this.style.background='rgba(79,70,229,0.06)'; this.style.color='var(--primary-color)';">
              <i class="bi <?php echo $soc[0]; ?>"></i>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
          <div class="result-header" style="text-align:left; padding: 40px;">
            <h2 style="font-size:1.6rem; color:var(--dark-color); margin-bottom:6px;">Send Us a Message</h2>
            <p style="color:var(--text-secondary); margin-bottom: 28px;">Fill in the form below and we'll get back to you within 24 hours.</p>

            <?php if ($success_message): ?>
              <div class="alert alert-success mb-4" style="background: rgba(16,185,129,0.08); color: #059669; border: 1px solid rgba(16,185,129,0.2); border-radius: 12px; padding: 14px 20px; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
              </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
              <div class="alert alert-danger mb-4" style="background: rgba(239,68,68,0.08); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); border-radius: 12px; padding: 14px 20px; display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
              </div>
            <?php endif; ?>

            <form action="contact.php" method="post">
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label style="font-size:0.85rem; font-weight:600; color:var(--dark-color); display:block; margin-bottom:6px;">Your Name *</label>
                  <input type="text" name="name" class="form-control" placeholder="Ahmed Khan" required
                         style="border-radius:12px; border: 1px solid var(--border-color-darker); padding: 12px 18px; font-size:0.95rem; transition: var(--transition-smooth);">
                </div>
                <div class="col-md-6">
                  <label style="font-size:0.85rem; font-weight:600; color:var(--dark-color); display:block; margin-bottom:6px;">Email Address *</label>
                  <input type="email" name="email" class="form-control" placeholder="ahmed@example.com" required
                         style="border-radius:12px; border: 1px solid var(--border-color-darker); padding: 12px 18px; font-size:0.95rem; transition: var(--transition-smooth);">
                </div>
              </div>
              <div class="mb-3">
                <label style="font-size:0.85rem; font-weight:600; color:var(--dark-color); display:block; margin-bottom:6px;">Subject *</label>
                <input type="text" name="subject" class="form-control" placeholder="How can we help?" required
                       style="border-radius:12px; border: 1px solid var(--border-color-darker); padding: 12px 18px; font-size:0.95rem;">
              </div>
              <div class="mb-4">
                <label style="font-size:0.85rem; font-weight:600; color:var(--dark-color); display:block; margin-bottom:6px;">Message *</label>
                <textarea name="message" class="form-control" placeholder="Write your message here..." rows="5" required
                          style="border-radius:12px; border: 1px solid var(--border-color-darker); padding: 12px 18px; font-size:0.95rem; resize: vertical;"></textarea>
              </div>
              <button type="submit" class="btn-primary-modern" style="border: none; cursor: pointer; width: 100%; font-size: 1rem; padding: 14px 32px;">
                <i class="bi bi-send me-2"></i> Send Message
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </section>

</main>

<style>
  .form-control:focus {
    outline: none;
    border-color: var(--primary-color) !important;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1) !important;
  }
</style>

<?php include "includes/footer.php"; ?>