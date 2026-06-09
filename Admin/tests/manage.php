<?php
include "../db.php";
$pageTitle = "Manage Assessments";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Handle Test Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM tests WHERE id = $id");
    header("Location: manage.php?success=deleted");
    exit();
}

// Fetch all tests with their category and subject names
$tests_query = "SELECT t.*, s.name as subject_name, tc.name as category_name,
               (SELECT COUNT(*) FROM test_questions tq WHERE tq.test_id = t.id) as question_count
               FROM tests t
               JOIN subjects s ON t.subject_id = s.id
               JOIN test_categories tc ON t.category_id = tc.id
               ORDER BY t.created_at DESC";
$tests_res = $conn->query($tests_query);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;"><i class="bi bi-file-earmark-check me-2"></i>Manage Assessments</h1>
    <a href="add_edit.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Create New Test
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php
        switch ($_GET['success']) {
            case 'created': echo "Test successfully created!"; break;
            case 'updated': echo "Test successfully updated!"; break;
            case 'deleted': echo "Test successfully deleted!"; break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Exams & Tests</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title & Info</th>
                        <th>Subject / Class</th>
                        <th>Category</th>
                        <th>Timer Mode & Duration</th>
                        <th>Availability / Schedule</th>
                        <th>Questions</th>
                        <th>Link / Certificate</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tests_res->num_rows == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No assessments created yet. Click "Create New Test" to get started.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($t = $tests_res->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($t['title']); ?></div>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars(substr($t['description'], 0, 60)) . (strlen($t['description']) > 60 ? '...' : ''); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($t['subject_name']); ?></span>
                                    <?php if (!empty($t['class_name'])): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($t['class_name']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($t['category_name']); ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?php echo $t['timer_mode']; ?> Timer</div>
                                    <div class="text-muted small"><?php echo $t['duration_minutes']; ?> Minutes</div>
                                </td>
                                <td>
                                    <?php if ($t['timer_mode'] == 'Fixed'): ?>
                                        <div class="small text-indigo font-weight-bold">
                                            Start: <?php echo date('d M Y h:i A', strtotime($t['start_datetime'])); ?>
                                        </div>
                                        <div class="small text-danger">
                                            End: <?php echo date('d M Y h:i A', strtotime($t['end_datetime'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success">Available Anytime</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-dark rounded-pill"><?php echo $t['question_count']; ?> Qs</span>
                                    <a href="questions.php?test_id=<?php echo $t['id']; ?>" class="btn btn-sm btn-link p-0 d-block small">Manage Qs</a>
                                </td>
                                <td>
                                    <!-- Share Link & Certificate Badge -->
                                    <div class="d-flex flex-column gap-1">
                                        <button class="btn btn-xs btn-outline-info copy-link-btn py-0 px-2" style="font-size: 0.75rem;" 
                                                data-link="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/KAcademyX/test_details.php?token=' . $t['share_token']; ?>">
                                            <i class="bi bi-share me-1"></i> Copy Link
                                        </button>
                                        <?php if ($t['certificate_enabled']): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success" style="width: fit-content;"><i class="bi bi-award me-1"></i>Cert Enabled</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted" style="width: fit-content;">No Cert</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="add_edit.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Assessment">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="manage.php?delete=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Assessment" onclick="return confirm('Delete this test? Students attempts, results and answers will also be deleted!')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy Shareable link to Clipboard
    document.querySelectorAll('.copy-link-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const link = this.getAttribute('data-link');
            navigator.clipboard.writeText(link).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check2"></i> Copied!';
                this.classList.replace('btn-outline-info', 'btn-success');
                this.classList.add('text-white');
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.replace('btn-success', 'btn-outline-info');
                    this.classList.remove('text-white');
                }, 2000);
            });
        });
    });
});
</script>

</div><!-- end col-md-10 content -->
</div><!-- end row -->
</div><!-- end container-fluid -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
