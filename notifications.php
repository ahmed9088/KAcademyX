<?php
session_start();
require_once 'forms/db.php';

// Auth guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

$user_id = $_SESSION["id"];
// Fetch student details
$st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
$st_row = mysqli_fetch_assoc($st_res);
if (!$st_row) {
    header('Location: test.php');
    exit();
}
$student_id = $st_row['id'];

$success_msg = '';
$error_msg = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'mark_read') {
            $notif_id = intval($_POST['notif_id']);
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?");
            $stmt->bind_param("ii", $notif_id, $student_id);
            if ($stmt->execute()) {
                $success_msg = "Notification marked as read.";
            }
            $stmt->close();
        } 
        elseif ($action === 'delete') {
            $notif_id = intval($_POST['notif_id']);
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND student_id = ?");
            $stmt->bind_param("ii", $notif_id, $student_id);
            if ($stmt->execute()) {
                $success_msg = "Notification deleted.";
            }
            $stmt->close();
        } 
        elseif ($action === 'mark_all_read') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $stmt->close();
            $success_msg = "All notifications marked as read.";
        } 
        elseif ($action === 'clear_all') {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $stmt->close();
            $success_msg = "Notification history cleared.";
        }
    }
}

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'unread', 'read'])) {
    $filter = 'all';
}

// Build query
if ($filter === 'unread') {
    $query = "SELECT * FROM notifications WHERE student_id = ? AND is_read = 0 ORDER BY created_at DESC";
} elseif ($filter === 'read') {
    $query = "SELECT * FROM notifications WHERE student_id = ? AND is_read = 1 ORDER BY created_at DESC";
} else {
    $query = "SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$notifications = [];
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

$pageTitle = "Notification Center";
$activePage = "notifications";
include "includes/header.php";
?>

<div style="height: 100px;"></div>

<main class="container py-4">
  <div class="row justify-content-center" data-aos="fade-up">
    <div class="col-lg-10 col-md-12">
      
      <!-- Page Header -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-3">
        <div>
          <h2 class="fw-bold text-dark mb-1"><i class="bi bi-bell-fill text-primary me-2"></i>Notification Center</h2>
          <p class="text-muted mb-0 small">Stay updated on your exam schedules, results, and platform announcements.</p>
        </div>
        
        <?php if (!empty($notifications) || $filter !== 'all'): ?>
          <div class="d-flex gap-2">
            <form method="post" class="d-inline">
              <input type="hidden" name="action" value="mark_all_read">
              <button type="submit" class="btn btn-sm btn-outline-secondary fw-semibold"><i class="bi bi-check-all me-1"></i>Mark All Read</button>
            </form>
            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to clear your entire notification history?');">
              <input type="hidden" name="action" value="clear_all">
              <button type="submit" class="btn btn-sm btn-outline-danger fw-semibold"><i class="bi bi-trash3-fill me-1"></i>Clear All</button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <!-- Action Alerts -->
      <?php if ($success_msg): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-3 mb-4" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Filter Navigation & Container -->
      <div class="card border-0 shadow-sm bg-white overflow-hidden rounded-4">
        
        <!-- Tabs -->
        <div class="card-header bg-white border-bottom border-light px-4 py-3">
          <ul class="nav nav-pills card-header-pills gap-2">
            <li class="nav-item">
              <a class="nav-link px-3 py-2 fw-semibold <?php echo $filter === 'all' ? 'active bg-primary text-white' : 'text-secondary bg-light'; ?>" href="notifications.php?filter=all">
                All Alerts
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link px-3 py-2 fw-semibold <?php echo $filter === 'unread' ? 'active bg-primary text-white' : 'text-secondary bg-light'; ?>" href="notifications.php?filter=unread">
                Unread
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link px-3 py-2 fw-semibold <?php echo $filter === 'read' ? 'active bg-primary text-white' : 'text-secondary bg-light'; ?>" href="notifications.php?filter=read">
                Read
              </a>
            </li>
          </ul>
        </div>

        <!-- Notification List -->
        <div class="card-body p-0">
          <?php if (empty($notifications)): ?>
            <div class="text-center py-5 my-3 text-muted">
              <i class="bi bi-bell-slash text-secondary mb-3" style="font-size: 3.5rem;"></i>
              <h5 class="fw-bold text-dark">No notifications found</h5>
              <p class="small text-muted mb-0">You're all caught up! There are no alerts matching your filter.</p>
            </div>
          <?php else: ?>
            <div class="list-group list-group-flush">
              <?php foreach ($notifications as $notif): ?>
                <?php $is_unread = ($notif['is_read'] == 0); ?>
                <div class="list-group-item px-4 py-4 d-flex justify-content-between align-items-start gap-3 border-bottom border-light <?php echo $is_unread ? 'bg-primary bg-opacity-10' : ''; ?>" style="transition: background-color 0.25s;">
                  
                  <!-- Left side: Notification Content -->
                  <div class="d-flex gap-3 align-items-start flex-grow-1">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mt-1 <?php echo $is_unread ? 'text-primary bg-primary bg-opacity-25' : 'text-secondary bg-light'; ?>" style="width: 42px; height: 42px; font-size: 1.25rem; flex-shrink: 0;">
                      <i class="bi <?php echo $is_unread ? 'bi-bell-fill' : 'bi-bell'; ?>"></i>
                    </div>
                    <div>
                      <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h6 class="fw-bold mb-0 text-dark" style="font-size: 1.05rem;">
                          <?php if (!empty($notif['link'])): ?>
                            <a href="<?php echo $is_unread ? 'click_notification.php?id=' . $notif['id'] : htmlspecialchars($notif['link']); ?>" class="text-decoration-none text-dark text-primary-hover">
                              <i class="bi bi-link-45deg me-1"></i><?php echo htmlspecialchars($notif['title']); ?>
                            </a>
                          <?php else: ?>
                            <?php echo htmlspecialchars($notif['title']); ?>
                          <?php endif; ?>
                        </h6>
                        <?php if ($is_unread): ?>
                          <span class="badge bg-primary rounded-pill small" style="font-size: 0.65rem;">New</span>
                        <?php endif; ?>
                      </div>
                      <p class="text-secondary small mb-2" style="line-height: 1.5; font-size: 0.92rem;"><?php echo htmlspecialchars($notif['message']); ?></p>
                      <span class="text-muted block" style="font-size: 0.78rem;">
                        <i class="bi bi-calendar3 me-1"></i><?php echo date('d M Y \a\t h:i A', strtotime($notif['created_at'])); ?>
                      </span>
                    </div>
                  </div>

                  <!-- Right side: Actions -->
                  <div class="d-flex gap-2 align-items-center">
                    <?php if ($is_unread): ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-light border fw-semibold text-primary" title="Mark as Read">
                          <i class="bi bi-check2"></i> <span class="d-none d-sm-inline ms-1">Read</span>
                        </button>
                      </form>
                    <?php endif; ?>
                    
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this notification?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-light border fw-semibold text-danger" title="Delete Notification">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </form>
                  </div>
                  
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        
      </div>
      
      <!-- Back Link -->
      <div class="mt-4 text-center">
        <a href="test.php" class="btn btn-link text-decoration-none fw-semibold text-indigo"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
      </div>

    </div>
  </div>
</main>

<style>
.text-primary-hover {
  transition: color 0.2s ease;
}
.text-primary-hover:hover {
  color: #4f46e5 !important;
  text-decoration: underline !important;
}
</style>

<?php include "includes/footer.php"; ?>
