<?php
include "../db.php";
$pageTitle = "Notifications Portal";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php";

$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_notification'])) {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $target = $_POST['target'] ?? 'all';
        
        if (empty($title) || empty($message)) {
            $error = "Title and message are required.";
        } else {
            if ($target === 'all') {
                $students_res = $conn->query("SELECT id FROM students");
                if ($students_res && $students_res->num_rows > 0) {
                    $insert_stmt = $conn->prepare("INSERT INTO notifications (student_id, title, message, is_read) VALUES (?, ?, ?, 0)");
                    while ($st = $students_res->fetch_assoc()) {
                        $insert_stmt->bind_param("iss", $st['id'], $title, $message);
                        $insert_stmt->execute();
                    }
                    $insert_stmt->close();
                    $success = "Notification successfully sent to all students.";
                } else {
                    $error = "No students found to send notifications to.";
                }
            } else {
                $student_id = intval($target);
                $insert_stmt = $conn->prepare("INSERT INTO notifications (student_id, title, message, is_read) VALUES (?, ?, ?, 0)");
                $insert_stmt->bind_param("iss", $student_id, $title, $message);
                if ($insert_stmt->execute()) {
                    $success = "Notification successfully sent to student.";
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $insert_stmt->close();
            }
        }
    } 
    elseif (isset($_POST['delete_notification'])) {
        $title = $_POST['del_title'] ?? '';
        $message = $_POST['del_message'] ?? '';
        $created_at = $_POST['del_created_at'] ?? '';
        
        $delete_stmt = $conn->prepare("DELETE FROM notifications WHERE title = ? AND message = ? AND created_at = ?");
        $delete_stmt->bind_param("sss", $title, $message, $created_at);
        if ($delete_stmt->execute()) {
            $success = "Sent notification(s) successfully deleted/revoked.";
        } else {
            $error = "Database error: " . $conn->error;
        }
        $delete_stmt->close();
    }
}

// Fetch all students for the dropdown selection
$students_query = "SELECT id, name, username FROM students ORDER BY name ASC";
$students_res = $conn->query($students_query);
$all_students = [];
if ($students_res) {
    while ($row = $students_res->fetch_assoc()) {
        $all_students[] = $row;
    }
}

// Fetch past sent notifications grouped by title, message, and created_at
$sent_query = "SELECT title, message, created_at, COUNT(student_id) as recipient_count 
               FROM notifications 
               GROUP BY title, message, created_at 
               ORDER BY created_at DESC LIMIT 30";
$sent_res = $conn->query($sent_query);
$sent_notifications = [];
if ($sent_res) {
    while ($row = $sent_res->fetch_assoc()) {
        $sent_notifications[] = $row;
    }
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;"><i class="bi bi-bell me-2 text-primary"></i>Broadcast Center</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Compose Notification Column -->
    <div class="col-lg-5 col-md-12 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-header bg-white border-bottom border-light p-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-send-fill me-2 text-primary"></i>Compose Alert</h5>
            </div>
            <div class="card-body p-4">
                <form method="post" action="">
                    <!-- Target Student -->
                    <div class="mb-3">
                        <label for="target" class="form-label fw-semibold text-secondary">Target Recipient(s)</label>
                        <select class="form-select" id="target" name="target" required>
                            <option value="all">Broadcast to All Students</option>
                            <?php foreach ($all_students as $st): ?>
                                <option value="<?php echo $st['id']; ?>">
                                    <?php echo htmlspecialchars($st['name'] . ' (@' . $st['username'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold text-secondary">Alert Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Schedule Update, Result Published" required>
                    </div>

                    <!-- Message -->
                    <div class="mb-4">
                        <label for="message" class="form-label fw-semibold text-secondary">Message Content</label>
                        <textarea class="form-control" id="message" name="message" rows="4" placeholder="Write notification message..." required></textarea>
                    </div>

                    <button type="submit" name="send_notification" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-send-fill me-2"></i> Send Notification
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification History Column -->
    <div class="col-lg-7 col-md-12 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
            <div class="card-header bg-white border-bottom border-light p-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-secondary"></i>Broadcast History</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($sent_notifications)): ?>
                    <div class="text-center py-5 my-3 text-muted">
                        <i class="bi bi-bell-slash fs-1 text-secondary mb-3 d-block"></i>
                        <p class="mb-0 small">No broadcast history found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3 border-0">Alert Details</th>
                                    <th class="py-3 border-0 text-center">Recipients</th>
                                    <th class="py-3 border-0">Date Sent</th>
                                    <th class="px-4 py-3 border-0 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sent_notifications as $notif): ?>
                                    <tr>
                                        <td class="px-4 py-3">
                                            <strong class="text-dark d-block"><?php echo htmlspecialchars($notif['title']); ?></strong>
                                            <span class="text-muted text-truncate d-inline-block small" style="max-width: 250px;">
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="badge rounded-pill bg-light text-primary border border-primary-subtle px-3">
                                                <?php echo $notif['recipient_count']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-muted small">
                                            <?php echo date('d M Y, h:i A', strtotime($notif['created_at'])); ?>
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <form method="post" class="d-inline" onsubmit="return confirm('Revoke this notification? It will delete the notification from all recipient list.');">
                                                <input type="hidden" name="del_title" value="<?php echo htmlspecialchars($notif['title']); ?>">
                                                <input type="hidden" name="del_message" value="<?php echo htmlspecialchars($notif['message']); ?>">
                                                <input type="hidden" name="del_created_at" value="<?php echo $notif['created_at']; ?>">
                                                <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" title="Revoke Notification">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div><!-- end col-md-10 content -->
</div><!-- end row -->
</div><!-- end container-fluid -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
