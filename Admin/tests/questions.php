<?php
include "../db.php";

$test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
if ($test_id <= 0) {
    header("Location: manage.php");
    exit();
}

// Fetch test details
$test_stmt = $conn->prepare("SELECT t.*, s.name as subject_name FROM tests t JOIN subjects s ON t.subject_id = s.id WHERE t.id = ?");
$test_stmt->bind_param("i", $test_id);
$test_stmt->execute();
$test = $test_stmt->get_result()->fetch_assoc();
$test_stmt->close();

if (!$test) {
    header("Location: manage.php");
    exit();
}

// Handle Add Question to Test
if (isset($_GET['add_q'])) {
    $q_id = intval($_GET['add_q']);
    // Check if not already added
    $check = $conn->query("SELECT id FROM test_questions WHERE test_id = $test_id AND question_id = $q_id");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO test_questions (test_id, question_id, sort_order) VALUES ($test_id, $q_id, 99)");
    }
    header("Location: questions.php?test_id=$test_id&success=added");
    exit();
}

// Handle Remove Question from Test
if (isset($_GET['remove_q'])) {
    $q_id = intval($_GET['remove_q']);
    $conn->query("DELETE FROM test_questions WHERE test_id = $test_id AND question_id = $q_id");
    header("Location: questions.php?test_id=$test_id&success=removed");
    exit();
}

// Handle Reorder/Update Sort Order
if (isset($_POST['update_sorting'])) {
    $orders = $_POST['sort_order']; // array of test_questions_id => order_val
    foreach ($orders as $tq_id => $val) {
        $stmt = $conn->prepare("UPDATE test_questions SET sort_order = ? WHERE id = ?");
        $ord = intval($val);
        $tq = intval($tq_id);
        $stmt->bind_param("ii", $ord, $tq);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: questions.php?test_id=$test_id&success=sorted");
    exit();
}

// Fetch current questions in this test
$current_qs_query = "SELECT tq.id as tq_id, tq.sort_order, q.*
                     FROM test_questions tq
                     JOIN questions q ON tq.question_id = q.id
                     WHERE tq.test_id = $test_id
                     ORDER BY tq.sort_order ASC, tq.id ASC";
$current_qs_res = $conn->query($current_qs_query);
$current_qs = [];
$current_q_ids = [0];
while ($row = $current_qs_res->fetch_assoc()) {
    $current_qs[] = $row;
    $current_q_ids[] = $row['id'];
}

// Fetch available questions from Question Bank for this subject (that are not already in the test)
$current_q_ids_str = implode(',', $current_q_ids);
$subject_id = $test['subject_id'];
$available_qs_query = "SELECT q.*, c.name as chapter_name
                       FROM questions q
                       LEFT JOIN chapters c ON q.chapter_id = c.id
                       WHERE q.subject_id = $subject_id AND q.id NOT IN ($current_q_ids_str)
                       ORDER BY q.created_at DESC";
$available_qs_res = $conn->query($available_qs_query);

$pageTitle = "Configure Test Questions";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php";
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;"><i class="bi bi-file-earmark-check me-2"></i>Configure Questions</h1>
        <small class="text-muted">Test: <strong><?php echo htmlspecialchars($test['title']); ?></strong> (Subject: <?php echo htmlspecialchars($test['subject_name']); ?>)</small>
    </div>
    <a href="manage.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Tests
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php
        switch ($_GET['success']) {
            case 'added': echo "Question successfully added to test!"; break;
            case 'removed': echo "Question successfully removed from test!"; break;
            case 'sorted': echo "Sorting order successfully updated!"; break;
        }
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Left Panel: Questions in the Test -->
    <div class="col-xl-7 col-lg-12 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-list-ol me-2"></i>Test Questions (<?php echo count($current_qs); ?>)</h6>
                <?php if (!empty($current_qs)): ?>
                    <button type="submit" form="sortingForm" name="update_sorting" class="btn btn-sm btn-success">
                        <i class="bi bi-arrow-down-up me-1"></i> Save Order
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($current_qs)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-info-circle fs-1 mb-2 d-block"></i>
                        No questions in this test yet. Add some from the right panel.
                    </div>
                <?php else: ?>
                    <form method="POST" id="sortingForm">
                        <div class="list-group">
                            <?php foreach ($current_qs as $index => $q): ?>
                                <div class="list-group-item d-flex align-items-center gap-3 py-3">
                                    <!-- Sort Order Input -->
                                    <div style="width: 70px;">
                                        <input type="number" class="form-control form-control-sm text-center" 
                                               name="sort_order[<?php echo $q['tq_id']; ?>]" 
                                               value="<?php echo $q['sort_order'] == 99 ? ($index + 1) : $q['sort_order']; ?>" required>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small mb-1">
                                            <span class="badge bg-secondary"><?php echo str_replace('_', ' ', $q['question_type']); ?></span>
                                            <span class="badge bg-light text-dark border"><?php echo $q['points']; ?> Pt</span>
                                        </div>
                                        <div class="fw-semibold text-dark mb-1"><?php echo htmlspecialchars($q['question_text']); ?></div>
                                    </div>
                                    <div>
                                        <a href="questions.php?test_id=<?php echo $test_id; ?>&remove_q=<?php echo $q['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" title="Remove question from test">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Panel: Available Questions in Question Bank -->
    <div class="col-xl-5 col-lg-12 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light py-3">
                <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-journal-plus me-2"></i>Subject Question Bank</h6>
            </div>
            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                <?php if ($available_qs_res->num_rows == 0): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                        No other questions found in the Question Bank for this subject.
                        <a href="../questions/bank.php" class="btn btn-sm btn-primary mt-2">Go to Question Bank</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php while ($q = $available_qs_res->fetch_assoc()): ?>
                            <div class="list-group-item py-3 px-0">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <div class="small mb-1">
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($q['chapter_name'] ?? 'General'); ?></span>
                                            <span class="badge bg-secondary"><?php echo str_replace('_', ' ', $q['question_type']); ?></span>
                                        </div>
                                        <div class="fw-normal text-dark mb-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($q['question_text']); ?></div>
                                    </div>
                                    <a href="questions.php?test_id=<?php echo $test_id; ?>&add_q=<?php echo $q['id']; ?>" 
                                       class="btn btn-sm btn-primary py-1 px-2">
                                        <i class="bi bi-plus-lg"></i> Add
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
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
