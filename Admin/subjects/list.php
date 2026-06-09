<?php
include "../db.php";
$pageTitle = "Manage Subjects & Chapters";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Handle Subject Add
if (isset($_POST['add_subject'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        $stmt->close();
        header("Location: list.php?success=subject_added");
        exit();
    }
}

// Handle Subject Edit
if (isset($_POST['edit_subject'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE subjects SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: list.php?success=subject_updated");
        exit();
    }
}

// Handle Subject Delete
if (isset($_GET['delete_subject'])) {
    $id = intval($_GET['delete_subject']);
    $conn->query("DELETE FROM subjects WHERE id = $id");
    header("Location: list.php?success=subject_deleted");
    exit();
}

// Handle Chapter Add
if (isset($_POST['add_chapter'])) {
    $subject_id = intval($_POST['subject_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if (!empty($name) && $subject_id > 0) {
        $stmt = $conn->prepare("INSERT INTO chapters (subject_id, name, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $subject_id, $name, $description);
        $stmt->execute();
        $stmt->close();
        header("Location: list.php?success=chapter_added");
        exit();
    }
}

// Handle Chapter Edit
if (isset($_POST['edit_chapter'])) {
    $id = intval($_POST['id']);
    $subject_id = intval($_POST['subject_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    if (!empty($name) && $subject_id > 0) {
        $stmt = $conn->prepare("UPDATE chapters SET subject_id = ?, name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("issi", $subject_id, $name, $description, $id);
        $stmt->execute();
        $stmt->close();
        header("Location: list.php?success=chapter_updated");
        exit();
    }
}

// Handle Chapter Delete
if (isset($_GET['delete_chapter'])) {
    $id = intval($_GET['delete_chapter']);
    $conn->query("DELETE FROM chapters WHERE id = $id");
    header("Location: list.php?success=chapter_deleted");
    exit();
}

// Fetch all subjects
$subjects_result = $conn->query("SELECT * FROM subjects ORDER BY name");
$subjects = [];
while ($row = $subjects_result->fetch_assoc()) {
    // Fetch chapters for each subject
    $subj_id = $row['id'];
    $chapters_res = $conn->query("SELECT * FROM chapters WHERE subject_id = $subj_id ORDER BY name");
    $chapters = [];
    while ($chap = $chapters_res->fetch_assoc()) {
        $chapters[] = $chap;
    }
    $row['chapters'] = $chapters;
    $subjects[] = $row;
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;"><i class="bi bi-tags me-2"></i>Subjects & Chapters</h1>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="bi bi-plus-lg me-1"></i> Add Subject
        </button>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addChapterModal">
            <i class="bi bi-plus-lg me-1"></i> Add Chapter
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php
        switch ($_GET['success']) {
            case 'subject_added': echo "Subject successfully added!"; break;
            case 'subject_updated': echo "Subject successfully updated!"; break;
            case 'subject_deleted': echo "Subject successfully deleted!"; break;
            case 'chapter_added': echo "Chapter successfully added!"; break;
            case 'chapter_updated': echo "Chapter successfully updated!"; break;
            case 'chapter_deleted': echo "Chapter successfully deleted!"; break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (empty($subjects)): ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-folder-x fs-1 text-muted"></i>
            <h4 class="mt-3 text-muted">No Subjects Found</h4>
            <p class="text-muted">Get started by creating your first subject.</p>
        </div>
    <?php else: ?>
        <?php foreach ($subjects as $s): ?>
            <div class="col-xl-6 col-lg-12 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h5 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($s['name']); ?></h5>
                            <small class="text-muted"><?php echo htmlspecialchars($s['description']); ?></small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-menu-item edit-subject-btn" href="#" 
                                       data-id="<?php echo $s['id']; ?>" 
                                       data-name="<?php echo htmlspecialchars($s['name']); ?>" 
                                       data-description="<?php echo htmlspecialchars($s['description']); ?>"
                                       data-bs-toggle="modal" data-bs-target="#editSubjectModal"><i class="bi bi-pencil me-2"></i>Edit Subject</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-menu-item text-danger" href="list.php?delete_subject=<?php echo $s['id']; ?>" onclick="return confirm('Are you sure? This will delete the subject and ALL its chapters!')"><i class="bi bi-trash me-2"></i>Delete</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted mb-3 font-weight-bold">Chapters (<?php echo count($s['chapters']); ?>)</h6>
                        <?php if (empty($s['chapters'])): ?>
                            <div class="text-center py-3 text-muted bg-light rounded">
                                <i class="bi bi-info-circle me-1"></i> No chapters added yet.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($s['chapters'] as $c): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($c['name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($c['description']); ?></small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary edit-chapter-btn"
                                                    data-id="<?php echo $c['id']; ?>"
                                                    data-subject-id="<?php echo $s['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($c['name']); ?>"
                                                    data-description="<?php echo htmlspecialchars($c['description']); ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editChapterModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="list.php?delete_chapter=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this chapter?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="list.php" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add New Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="sub_name" class="form-label">Subject Name</label>
                    <input type="text" class="form-control" id="sub_name" name="name" required placeholder="e.g. Chemistry">
                </div>
                <div class="mb-3">
                    <label for="sub_desc" class="form-label">Description</label>
                    <textarea class="form-control" id="sub_desc" name="description" rows="3" placeholder="Brief details about the subject..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_subject" class="btn btn-primary">Save Subject</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="list.php" class="modal-content">
            <input type="hidden" id="edit_sub_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="edit_sub_name" class="form-label">Subject Name</label>
                    <input type="text" class="form-control" id="edit_sub_name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="edit_sub_desc" class="form-label">Description</label>
                    <textarea class="form-control" id="edit_sub_desc" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_subject" class="btn btn-primary">Update Subject</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Chapter Modal -->
<div class="modal fade" id="addChapterModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="list.php" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add New Chapter</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="chap_subject_id" class="form-label">Select Subject</label>
                    <select class="form-select" id="chap_subject_id" name="subject_id" required>
                        <option value="">-- Choose Subject --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="chap_name" class="form-label">Chapter Name</label>
                    <input type="text" class="form-control" id="chap_name" name="name" required placeholder="e.g. Thermodynamics">
                </div>
                <div class="mb-3">
                    <label for="chap_desc" class="form-label">Description</label>
                    <textarea class="form-control" id="chap_desc" name="description" rows="3" placeholder="Brief details about the chapter..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_chapter" class="btn btn-primary">Save Chapter</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Chapter Modal -->
<div class="modal fade" id="editChapterModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="list.php" class="modal-content">
            <input type="hidden" id="edit_chap_id" name="id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Chapter</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="edit_chap_subject_id" class="form-label">Select Subject</label>
                    <select class="form-select" id="edit_chap_subject_id" name="subject_id" required>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_chap_name" class="form-label">Chapter Name</label>
                    <input type="text" class="form-control" id="edit_chap_name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="edit_chap_desc" class="form-label">Description</label>
                    <textarea class="form-control" id="edit_chap_desc" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_chapter" class="btn btn-primary">Update Chapter</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Subject Edit handler
    document.querySelectorAll('.edit-subject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_sub_id').value = this.getAttribute('data-id');
            document.getElementById('edit_sub_name').value = this.getAttribute('data-name');
            document.getElementById('edit_sub_desc').value = this.getAttribute('data-description');
        });
    });

    // Chapter Edit handler
    document.querySelectorAll('.edit-chapter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_chap_id').value = this.getAttribute('data-id');
            document.getElementById('edit_chap_subject_id').value = this.getAttribute('data-subject-id');
            document.getElementById('edit_chap_name').value = this.getAttribute('data-name');
            document.getElementById('edit_chap_desc').value = this.getAttribute('data-description');
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
