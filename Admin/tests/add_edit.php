<?php
include "../db.php";

$test_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$test = null;

if ($test_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tests WHERE id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$test) {
        header("Location: manage.php");
        exit();
    }
}

// Generate random share token for new tests
function generateShareToken($conn) {
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    do {
        $token = "";
        for ($i = 0; $i < 6; $i++) {
            $token .= $chars[rand(0, strlen($chars) - 1)];
        }
        $res = $conn->query("SELECT id FROM tests WHERE share_token = '$token'");
    } while ($res && $res->num_rows > 0);
    return $token;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject_id = intval($_POST['subject_id']);
    $chapter_id = !empty($_POST['chapter_id']) ? intval($_POST['chapter_id']) : null;
    $class_name = trim($_POST['class_name']);
    $category_id = intval($_POST['category_id']);
    $difficulty_level = $_POST['difficulty_level'];
    $duration_minutes = intval($_POST['duration_minutes']);
    $timer_mode = $_POST['timer_mode'];
    
    // Schedule details
    $start_datetime = !empty($_POST['start_datetime']) ? $_POST['start_datetime'] : null;
    $end_datetime = !empty($_POST['end_datetime']) ? $_POST['end_datetime'] : null;
    
    $late_join_allowed = isset($_POST['late_join_allowed']) ? 1 : 0;
    $late_join_cutoff_minutes = !empty($_POST['late_join_cutoff_minutes']) ? intval($_POST['late_join_cutoff_minutes']) : 0;
    $show_live_status = isset($_POST['show_live_status']) ? 1 : 0;
    $passing_marks = intval($_POST['passing_marks']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $certificate_enabled = isset($_POST['certificate_enabled']) ? 1 : 0;
    $results_published = isset($_POST['results_published']) ? 1 : 0;
    
    // New fields
    $lobby_privacy = $_POST['lobby_privacy'] ?? 'Public';
    $allow_review = isset($_POST['allow_review']) ? 1 : 0;
    $show_live_submissions = isset($_POST['show_live_submissions']) ? 1 : 0;
    
    if ($test_id > 0) {
        // Update Test
        $query = "UPDATE tests SET 
                    title = ?, description = ?, subject_id = ?, chapter_id = ?, class_name = ?, 
                    category_id = ?, difficulty_level = ?, timer_mode = ?, duration_minutes = ?, 
                    start_datetime = ?, end_datetime = ?, late_join_allowed = ?, late_join_cutoff_minutes = ?, 
                    show_live_status = ?, passing_marks = ?, is_public = ?, certificate_enabled = ?, results_published = ?,
                    lobby_privacy = ?, allow_review = ?, show_live_submissions = ?
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssiisississiiiiiiisiii",
            $title, $description, $subject_id, $chapter_id, $class_name,
            $category_id, $difficulty_level, $timer_mode, $duration_minutes,
            $start_datetime, $end_datetime, $late_join_allowed, $late_join_cutoff_minutes,
            $show_live_status, $passing_marks, $is_public, $certificate_enabled, $results_published,
            $lobby_privacy, $allow_review, $show_live_submissions,
            $test_id
        );
        $stmt->execute();
        $stmt->close();
        header("Location: manage.php?success=updated");
        exit();
    } else {
        // Create Test
        $share_token = generateShareToken($conn);
        $query = "INSERT INTO tests (
                    title, description, subject_id, chapter_id, class_name, 
                    category_id, difficulty_level, timer_mode, duration_minutes, 
                    start_datetime, end_datetime, late_join_allowed, late_join_cutoff_minutes, 
                    show_live_status, passing_marks, is_public, share_token, certificate_enabled, results_published,
                    lobby_privacy, allow_review, show_live_submissions
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "ssiisississiiiiisiisii",
            $title, $description, $subject_id, $chapter_id, $class_name,
            $category_id, $difficulty_level, $timer_mode, $duration_minutes,
            $start_datetime, $end_datetime, $late_join_allowed, $late_join_cutoff_minutes,
            $show_live_status, $passing_marks, $is_public, $share_token, $certificate_enabled, $results_published,
            $lobby_privacy, $allow_review, $show_live_submissions
        );
        $stmt->execute();
        $stmt->close();
        header("Location: manage.php?success=created");
        exit();
    }
}

$pageTitle = ($test_id > 0) ? "Edit Assessment" : "Create Assessment";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Fetch subjects and categories
$subjects_res = $conn->query("SELECT * FROM subjects ORDER BY name");
$categories_res = $conn->query("SELECT * FROM test_categories ORDER BY id");
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;">
        <i class="bi bi-file-earmark-plus me-2"></i><?php echo $pageTitle; ?>
    </h1>
    <a href="manage.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-lg-9 col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Assessment Configuration Form</h6>
            </div>
            <div class="card-body">
                <form method="POST" id="testForm">
                    <!-- Basic Info -->
                    <div class="mb-4">
                        <h5 class="text-dark border-bottom pb-2 mb-3">1. Basic Information</h5>
                        <div class="mb-3">
                            <label for="title" class="form-label">Test Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="title" name="title" required 
                                   value="<?php echo $test ? htmlspecialchars($test['title']) : ''; ?>" placeholder="e.g. Weekly Physics Quiz - Mechanics">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description / Instructions</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Provide test instructions, syllabus details..."><?php echo $test ? htmlspecialchars($test['description']) : ''; ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="subject_id" class="form-label">Subject <span class="text-danger">*</span></label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">-- Choose Subject --</option>
                                    <?php while ($s = $subjects_res->fetch_assoc()): ?>
                                        <option value="<?php echo $s['id']; ?>" <?php echo ($test && $test['subject_id'] == $s['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="chapter_id" class="form-label">Chapter</label>
                                <select class="form-select" id="chapter_id" name="chapter_id">
                                    <option value="">-- Choose Chapter (Optional) --</option>
                                    <!-- Populated dynamically -->
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="class_name" class="form-label">Class / Syllabus Grade</label>
                                <input type="text" class="form-control" id="class_name" name="class_name" 
                                       value="<?php echo $test ? htmlspecialchars($test['class_name']) : ''; ?>" placeholder="e.g. Class 10, Self-Learning">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Test Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <?php while ($c = $categories_res->fetch_assoc()): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo ($test && $test['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="difficulty_level" class="form-label">Difficulty Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="difficulty_level" name="difficulty_level" required>
                                    <option value="Easy" <?php echo ($test && $test['difficulty_level'] == 'Easy') ? 'selected' : ''; ?>>Easy</option>
                                    <option value="Medium" <?php echo (!$test || $test['difficulty_level'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="Hard" <?php echo ($test && $test['difficulty_level'] == 'Hard') ? 'selected' : ''; ?>>Hard</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Timing settings -->
                    <div class="mb-4">
                        <h5 class="text-dark border-bottom pb-2 mb-3">2. Timing & Scheduler</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="timer_mode" class="form-label">Timer Mode <span class="text-danger">*</span></label>
                                <select class="form-select" id="timer_mode" name="timer_mode" required>
                                    <option value="Individual" <?php echo (!$test || $test['timer_mode'] == 'Individual') ? 'selected' : ''; ?>>Individual Timer (Practice Mode)</option>
                                    <option value="Fixed" <?php echo ($test && $test['timer_mode'] == 'Fixed') ? 'selected' : ''; ?>>Fixed Global Timer (Scheduled Quiz)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="duration_minutes" class="form-label">Duration (Minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" min="1" max="300" required 
                                       value="<?php echo $test ? $test['duration_minutes'] : 30; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="passing_marks" class="form-label">Passing Marks (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="passing_marks" name="passing_marks" min="0" max="100" required 
                                       value="<?php echo $test ? $test['passing_marks'] : 40; ?>">
                            </div>
                        </div>

                        <!-- Schedule settings (Conditionally Shown for Fixed Timer) -->
                        <div class="row" id="scheduleSettings" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label for="start_datetime" class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="start_datetime" name="start_datetime" 
                                       value="<?php echo ($test && $test['start_datetime']) ? date('Y-m-d\TH:i', strtotime($test['start_datetime'])) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_datetime" class="form-label">End Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime" 
                                       value="<?php echo ($test && $test['end_datetime']) ? date('Y-m-d\TH:i', strtotime($test['end_datetime'])) : ''; ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="late_join_allowed" name="late_join_allowed" value="1"
                                                   <?php echo (!$test || $test['late_join_allowed']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-bold" for="late_join_allowed">Allow Late Join</label>
                                        </div>
                                        <div class="mb-0" id="cutoffGroup">
                                            <label for="late_join_cutoff_minutes" class="form-label small">Late Join Cutoff (Minutes after start time)</label>
                                            <input type="number" class="form-control form-control-sm" style="width: 120px;" id="late_join_cutoff_minutes" name="late_join_cutoff_minutes" min="1" 
                                                   value="<?php echo $test ? $test['late_join_cutoff_minutes'] : 5; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Options & Extra Settings -->
                    <div class="mb-4">
                        <h5 class="text-dark border-bottom pb-2 mb-3">3. Accessibility, Privacy & Live Features</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                                           <?php echo (!$test || $test['is_public']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="is_public">Public Link (Anyone with link can access)</label>
                                </div>
                                <div class="text-muted small mb-3">If disabled, only authenticated/registered students can take the exam.</div>
                                
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="show_live_status" name="show_live_status" value="1"
                                           <?php echo (!$test || $test['show_live_status']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="show_live_status">Show Live Status</label>
                                </div>
                                <div class="text-muted small mb-3">Show students their live remaining time and status.</div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="show_live_submissions" name="show_live_submissions" value="1"
                                           <?php echo ($test && $test['show_live_submissions']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="show_live_submissions">Show Live Submissions Ticker</label>
                                </div>
                                <div class="text-muted small mb-3">Notify active test takers when someone submits.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="certificate_enabled" name="certificate_enabled" value="1"
                                           <?php echo ($test && $test['certificate_enabled']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="certificate_enabled">Enable Certificates</label>
                                </div>
                                <div class="text-muted small mb-3">Generate automated PDF completion certificates for students who pass this assessment.</div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="results_published" name="results_published" value="1"
                                           <?php echo (!$test || $test['results_published']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="results_published">Publish Results Instantly</label>
                                </div>
                                <div class="text-muted small mb-3">Instantly publish grade analytics to students upon submission.</div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="allow_review" name="allow_review" value="1"
                                           <?php echo (!$test || $test['allow_review']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="allow_review">Allow Question Review</label>
                                </div>
                                <div class="text-muted small mb-3">Allow students to review correct/wrong answers and explanations after test completion.</div>
                            </div>

                            <div class="col-12 mb-3 mt-2">
                                <div class="card bg-light border-0">
                                    <div class="card-body py-3">
                                        <label class="form-label fw-bold mb-2">Lobby Participant Privacy</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="lobby_privacy" id="privacy_public" value="Public" 
                                                       <?php echo (!$test || $test['lobby_privacy'] == 'Public') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="privacy_public">Public (Show real usernames to all waiting lobby participants)</label>
                                            </div>
                                            <div class="form-check form-check-inline ms-3">
                                                <input class="form-check-input" type="radio" name="lobby_privacy" id="privacy_anonymous" value="Anonymous" 
                                                       <?php echo ($test && $test['lobby_privacy'] == 'Anonymous') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="privacy_anonymous">Anonymous (Mask usernames as Student_123, Student_456, etc.)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="manage.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Save Assessment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectSelect = document.getElementById('subject_id');
    const chapterSelect = document.getElementById('chapter_id');
    const timerModeSelect = document.getElementById('timer_mode');
    const scheduleSettings = document.getElementById('scheduleSettings');
    const lateJoinCheckbox = document.getElementById('late_join_allowed');
    const cutoffGroup = document.getElementById('cutoffGroup');
    
    const selectedChapterId = <?php echo ($test && $test['chapter_id']) ? intval($test['chapter_id']) : 0; ?>;

    // Load chapters dynamically when subject changes
    function loadChapters(subjectId, callback) {
        if (!subjectId) {
            chapterSelect.innerHTML = '<option value="">-- Choose Chapter (Optional) --</option>';
            return;
        }
        chapterSelect.innerHTML = '<option value="">Loading...</option>';
        fetch('get_chapters_ajax.php?subject_id=' + subjectId)
            .then(res => res.json())
            .then(data => {
                let html = '<option value="">-- Choose Chapter (Optional) --</option>';
                data.forEach(chap => {
                    html += `<option value="${chap.id}" ${chap.id === selectedChapterId ? 'selected' : ''}>${chap.name}</option>`;
                });
                chapterSelect.innerHTML = html;
                if (callback) callback();
            });
    }

    subjectSelect.addEventListener('change', function() {
        loadChapters(this.value);
    });

    // Initial load
    if (subjectSelect.value) {
        loadChapters(subjectSelect.value);
    }

    // Toggle schedule settings display
    function toggleScheduleDisplay() {
        if (timerModeSelect.value === 'Fixed') {
            scheduleSettings.style.display = 'flex';
            document.getElementById('start_datetime').setAttribute('required', 'required');
            document.getElementById('end_datetime').setAttribute('required', 'required');
        } else {
            scheduleSettings.style.display = 'none';
            document.getElementById('start_datetime').removeAttribute('required');
            document.getElementById('end_datetime').removeAttribute('required');
        }
    }

    timerModeSelect.addEventListener('change', toggleScheduleDisplay);
    toggleScheduleDisplay(); // Initial load

    // Toggle cutoff display
    function toggleCutoffDisplay() {
        cutoffGroup.style.display = lateJoinCheckbox.checked ? 'block' : 'none';
    }
    lateJoinCheckbox.addEventListener('change', toggleCutoffDisplay);
    toggleCutoffDisplay(); // Initial load
});
</script>

</div><!-- end col-md-10 content -->
</div><!-- end row -->
</div><!-- end container-fluid -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
