<?php
include "../db.php";
$pageTitle = "Question Bank";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Handle Delete Question
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM questions WHERE id = $id");
    header("Location: bank.php?success=deleted");
    exit();
}

// Handle Add/Edit Question Form Submission
if (isset($_POST['save_question'])) {
    $q_id = intval($_POST['question_id']);
    $subject_id = intval($_POST['subject_id']);
    $chapter_id = !empty($_POST['chapter_id']) ? intval($_POST['chapter_id']) : null;
    $question_text = trim($_POST['question_text']);
    $question_type = $_POST['question_type'];
    $difficulty_level = $_POST['difficulty_level'];
    $points = intval($_POST['points']);
    $correct_text_answer = isset($_POST['correct_text_answer']) ? trim($_POST['correct_text_answer']) : null;
    $explanation = isset($_POST['explanation']) ? trim($_POST['explanation']) : null;

    if ($q_id > 0) {
        // Edit Question
        $stmt = $conn->prepare("UPDATE questions SET subject_id = ?, chapter_id = ?, question_text = ?, question_type = ?, difficulty_level = ?, points = ?, correct_text_answer = ?, explanation = ? WHERE id = ?");
        $stmt->bind_param("iisssissi", $subject_id, $chapter_id, $question_text, $question_type, $difficulty_level, $points, $correct_text_answer, $explanation, $q_id);
        $stmt->execute();
        $stmt->close();
        
        // Remove existing options and rewrite
        $conn->query("DELETE FROM question_options WHERE question_id = $q_id");
    } else {
        // Add Question
        $stmt = $conn->prepare("INSERT INTO questions (subject_id, chapter_id, question_text, question_type, difficulty_level, points, correct_text_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssiss", $subject_id, $chapter_id, $question_text, $question_type, $difficulty_level, $points, $correct_text_answer, $explanation);
        $stmt->execute();
        $q_id = $conn->insert_id;
        $stmt->close();
    }

    // Save options based on question type
    if ($question_type == 'MCQ_SINGLE') {
        $opts = $_POST['mcq_single_options'];
        $correct_index = intval($_POST['mcq_single_correct']); // 0, 1, 2, 3
        foreach ($opts as $idx => $opt_val) {
            $is_correct = ($idx === $correct_index) ? 1 : 0;
            $opt_text = trim($opt_val);
            if (!empty($opt_text)) {
                $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $q_id, $opt_text, $is_correct);
                $stmt->execute();
                $stmt->close();
            }
        }
    } elseif ($question_type == 'MCQ_MULTIPLE') {
        $opts = $_POST['mcq_multiple_options'];
        $corrects = isset($_POST['mcq_multiple_corrects']) ? $_POST['mcq_multiple_corrects'] : []; // array of indices
        foreach ($opts as $idx => $opt_val) {
            $is_correct = in_array($idx, $corrects) ? 1 : 0;
            $opt_text = trim($opt_val);
            if (!empty($opt_text)) {
                $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $q_id, $opt_text, $is_correct);
                $stmt->execute();
                $stmt->close();
            }
        }
    } elseif ($question_type == 'TRUE_FALSE') {
        $correct_tf = $_POST['tf_correct']; // 'True' or 'False'
        
        $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, 'True', ?)");
        $c1 = ($correct_tf == 'True') ? 1 : 0;
        $stmt->bind_param("ii", $q_id, $c1);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, 'False', ?)");
        $c2 = ($correct_tf == 'False') ? 1 : 0;
        $stmt->bind_param("ii", $q_id, $c2);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: bank.php?success=saved");
    exit();
}

// Fetch subjects & chapters for filters/forms
$subjects_res = $conn->query("SELECT * FROM subjects ORDER BY name");
$subjects = [];
while ($row = $subjects_res->fetch_assoc()) {
    $subjects[] = $row;
}

// Filtering
$subject_filter = isset($_GET['subj']) ? intval($_GET['subj']) : 0;
$chapter_filter = isset($_GET['chap']) ? intval($_GET['chap']) : 0;
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

$where = "1=1";
if ($subject_filter > 0) $where .= " AND q.subject_id = $subject_filter";
if ($chapter_filter > 0) $where .= " AND q.chapter_id = $chapter_filter";
if (!empty($type_filter)) $where .= " AND q.question_type = '" . mysqli_real_escape_string($conn, $type_filter) . "'";

// Fetch questions
$q_query = "SELECT q.*, s.name as subject_name, c.name as chapter_name
            FROM questions q
            JOIN subjects s ON q.subject_id = s.id
            LEFT JOIN chapters c ON q.chapter_id = c.id
            WHERE $where
            ORDER BY q.created_at DESC";
$q_res = $conn->query($q_query);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;"><i class="bi bi-journal-text me-2"></i>Question Bank</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#questionModal" id="addQuestionBtn">
        <i class="bi bi-plus-lg me-1"></i> Add Question
    </button>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo ($_GET['success'] == 'deleted') ? 'Question successfully deleted!' : 'Question successfully saved!'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="bank.php" class="row align-items-end">
            <div class="col-md-3 mb-2">
                <label for="filter_subj" class="form-label small fw-semibold">Subject</label>
                <select class="form-select" id="filter_subj" name="subj">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo ($subject_filter == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label for="filter_chap" class="form-label small fw-semibold">Chapter</label>
                <select class="form-select" id="filter_chap" name="chap">
                    <option value="">All Chapters</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label for="filter_type" class="form-label small fw-semibold">Question Type</label>
                <select class="form-select" id="filter_type" name="type">
                    <option value="">All Types</option>
                    <option value="MCQ_SINGLE" <?php echo ($type_filter == 'MCQ_SINGLE') ? 'selected' : ''; ?>>Multiple Choice (Single Answer)</option>
                    <option value="MCQ_MULTIPLE" <?php echo ($type_filter == 'MCQ_MULTIPLE') ? 'selected' : ''; ?>>Multiple Selection (Multiple Answers)</option>
                    <option value="TRUE_FALSE" <?php echo ($type_filter == 'TRUE_FALSE') ? 'selected' : ''; ?>>True / False</option>
                    <option value="FILL_BLANK" <?php echo ($type_filter == 'FILL_BLANK') ? 'selected' : ''; ?>>Fill in the Blank</option>
                    <option value="SHORT_ANSWER" <?php echo ($type_filter == 'SHORT_ANSWER') ? 'selected' : ''; ?>>Short Answer</option>
                    <option value="LONG_ANSWER" <?php echo ($type_filter == 'LONG_ANSWER') ? 'selected' : ''; ?>>Long Answer</option>
                </select>
            </div>
            <div class="col-md-3 mb-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="bank.php" class="btn btn-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Questions List -->
<div class="card shadow-sm mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Questions (<?php echo $q_res->num_rows; ?>)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Subject/Chapter</th>
                        <th>Question</th>
                        <th>Type</th>
                        <th>Difficulty</th>
                        <th>Points</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q_res->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                No questions found. Click "Add Question" to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $q_res->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light text-dark border d-block mb-1" style="width: fit-content;">
                                        <?php echo htmlspecialchars($row['subject_name']); ?>
                                    </span>
                                    <span class="small text-muted d-block"><?php echo htmlspecialchars($row['chapter_name'] ?? 'General/No Chapter'); ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars(substr($row['question_text'], 0, 80)) . (strlen($row['question_text']) > 80 ? '...' : ''); ?></div>
                                    <!-- Display Options or answers inline for quick preview -->
                                    <div class="text-muted small mt-1">
                                        <?php 
                                        if (in_array($row['question_type'], ['MCQ_SINGLE', 'MCQ_MULTIPLE', 'TRUE_FALSE'])) {
                                            $q_id = $row['id'];
                                            $opts_res = $conn->query("SELECT * FROM question_options WHERE question_id = $q_id");
                                            $opts_arr = [];
                                            while($o = $opts_res->fetch_assoc()) {
                                                $check = $o['is_correct'] ? '✅' : '⚪';
                                                $opts_arr[] = "$check " . htmlspecialchars($o['option_text']);
                                            }
                                            echo implode(" | ", $opts_arr);
                                        } elseif ($row['question_type'] == 'FILL_BLANK') {
                                            echo "Correct Answer: <strong>" . htmlspecialchars($row['correct_text_answer']) . "</strong>";
                                        } else {
                                            echo "Manual checking grading required";
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo str_replace('_', ' ', $row['question_type']); ?></span>
                                </td>
                                <td>
                                    <span class="badge <?php 
                                        echo ($row['difficulty_level'] == 'Easy') ? 'bg-success' : (($row['difficulty_level'] == 'Medium') ? 'bg-warning text-dark' : 'bg-danger'); 
                                    ?>"><?php echo $row['difficulty_level']; ?></span>
                                </td>
                                <td><?php echo $row['points']; ?></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <!-- Edit button triggers JS population -->
                                        <button class="btn btn-sm btn-outline-primary edit-q-btn" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-subject-id="<?php echo $row['subject_id']; ?>"
                                                data-chapter-id="<?php echo $row['chapter_id']; ?>"
                                                data-text="<?php echo htmlspecialchars($row['question_text']); ?>"
                                                data-type="<?php echo $row['question_type']; ?>"
                                                data-difficulty="<?php echo $row['difficulty_level']; ?>"
                                                data-points="<?php echo $row['points']; ?>"
                                                data-correct-text="<?php echo htmlspecialchars($row['correct_text_answer'] ?? ''); ?>"
                                                data-explanation="<?php echo htmlspecialchars($row['explanation'] ?? ''); ?>"
                                                title="Edit Question">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="bank.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Question" onclick="return confirm('Delete this question?')">
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

<!-- Question Modal (Add/Edit) -->
<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="bank.php" class="modal-content" id="questionForm">
            <input type="hidden" name="question_id" id="modal_question_id" value="0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Add Question</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="modal_subj" class="form-label">Subject *</label>
                        <select class="form-select" id="modal_subj" name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="modal_chap" class="form-label">Chapter</label>
                        <select class="form-select" id="modal_chap" name="chapter_id">
                            <option value="">-- Choose Chapter (Optional) --</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="modal_type" class="form-label">Question Type *</label>
                        <select class="form-select" id="modal_type" name="question_type" required>
                            <option value="MCQ_SINGLE">Multiple Choice (Single Correct Answer)</option>
                            <option value="MCQ_MULTIPLE">Multiple Selection (Multiple Correct Answers)</option>
                            <option value="TRUE_FALSE">True / False</option>
                            <option value="FILL_BLANK">Fill in the Blank</option>
                            <option value="SHORT_ANSWER">Short Answer</option>
                            <option value="LONG_ANSWER">Long Answer</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="modal_diff" class="form-label">Difficulty *</label>
                        <select class="form-select" id="modal_diff" name="difficulty_level" required>
                            <option value="Easy">Easy</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Hard">Hard</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="modal_points" class="form-label">Points / Marks *</label>
                        <input type="number" class="form-control" id="modal_points" name="points" min="1" value="1" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="modal_text" class="form-label">Question Text *</label>
                    <textarea class="form-control" id="modal_text" name="question_text" rows="3" required placeholder="Enter the question here..."></textarea>
                </div>

                <div class="mb-3">
                    <label for="modal_explanation" class="form-label">Explanation (Optional)</label>
                    <textarea class="form-control" id="modal_explanation" name="explanation" rows="2" placeholder="Explain the correct answer..."></textarea>
                </div>

                <!-- DYNAMIC CONFIG SECTIONS -->
                <!-- MCQ Single Answer -->
                <div id="section_MCQ_SINGLE" class="q-config-section">
                    <label class="form-label fw-bold mb-2">Options (Mark correct answer)</label>
                    <div class="row gy-2">
                        <?php for($i=0; $i<4; $i++): ?>
                            <div class="col-12">
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="mcq_single_correct" value="<?php echo $i; ?>" <?php echo $i==0?'checked':''; ?>>
                                    </div>
                                    <input type="text" class="form-control" name="mcq_single_options[]" placeholder="Option <?php echo chr(65 + $i); ?>" <?php echo $i<2?'required':''; ?>>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- MCQ Multiple Answers -->
                <div id="section_MCQ_MULTIPLE" class="q-config-section" style="display: none;">
                    <label class="form-label fw-bold mb-2">Options (Select all correct options)</label>
                    <div class="row gy-2">
                        <?php for($i=0; $i<4; $i++): ?>
                            <div class="col-12">
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="checkbox" name="mcq_multiple_corrects[]" value="<?php echo $i; ?>">
                                    </div>
                                    <input type="text" class="form-control" name="mcq_multiple_options[]" placeholder="Option <?php echo chr(65 + $i); ?>" <?php echo $i<2?'required':''; ?>>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- True / False -->
                <div id="section_TRUE_FALSE" class="q-config-section" style="display: none;">
                    <label class="form-label fw-bold">Select Correct Choice</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tf_correct" id="tf_t" value="True" checked>
                        <label class="form-check-label" for="tf_t">True</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="tf_correct" id="tf_f" value="False">
                        <label class="form-check-label" for="tf_f">False</label>
                    </div>
                </div>

                <!-- Fill in the Blank -->
                <div id="section_FILL_BLANK" class="q-config-section" style="display: none;">
                    <div class="mb-3">
                        <label for="correct_text_answer_fb" class="form-label fw-bold">Correct Text Answer *</label>
                        <input type="text" class="form-control" id="correct_text_answer_fb" name="correct_text_answer" placeholder="Enter the exact correct value">
                    </div>
                </div>
                
                <!-- Short Answer -->
                <div id="section_SHORT_ANSWER" class="q-config-section" style="display: none;">
                    <div class="mb-3">
                        <label for="correct_text_answer_sa" class="form-label fw-bold">Suggested Reference Answer (Optional)</label>
                        <textarea class="form-control" id="correct_text_answer_sa" name="correct_text_answer" rows="2" placeholder="Will be shown during manual grading"></textarea>
                    </div>
                </div>

                <!-- Long Answer -->
                <div id="section_LONG_ANSWER" class="q-config-section" style="display: none;">
                    <div class="alert alert-info py-2 small">
                        <i class="bi bi-info-circle me-1"></i> Long answers do not require predefined answers and will always be manually graded by the admin.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="save_question" class="btn btn-primary">Save Question</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterSubj = document.getElementById('filter_subj');
    const filterChap = document.getElementById('filter_chap');
    const modalSubj = document.getElementById('modal_subj');
    const modalChap = document.getElementById('modal_chap');
    const modalType = document.getElementById('modal_type');
    const qModal = new bootstrap.Modal(document.getElementById('questionModal'));
    const qForm = document.getElementById('questionForm');
    
    const initialSubjFilter = <?php echo $subject_filter; ?>;
    const initialChapFilter = <?php echo $chapter_filter; ?>;

    // Load chapters dynamically for filter
    function loadChapters(subjId, selectEl, selectedId) {
        if (!subjId) {
            selectEl.innerHTML = '<option value="">All Chapters</option>';
            return;
        }
        selectEl.innerHTML = '<option value="">Loading...</option>';
        fetch('../tests/get_chapters_ajax.php?subject_id=' + subjId)
            .then(res => res.json())
            .then(data => {
                let html = selectEl === filterChap ? '<option value="">All Chapters</option>' : '<option value="">-- Choose Chapter (Optional) --</option>';
                data.forEach(chap => {
                    html += `<option value="${chap.id}" ${chap.id === selectedId ? 'selected' : ''}>${chap.name}</option>`;
                });
                selectEl.innerHTML = html;
            });
    }

    filterSubj.addEventListener('change', function() {
        loadChapters(this.value, filterChap, 0);
    });

    modalSubj.addEventListener('change', function() {
        loadChapters(this.value, modalChap, 0);
    });

    // Populate initial filter
    if (initialSubjFilter) {
        loadChapters(initialSubjFilter, filterChap, initialChapFilter);
    }

    // Toggle question config inputs in Modal
    function toggleConfigSections() {
        const selectedType = modalType.value;
        document.querySelectorAll('.q-config-section').forEach(sec => {
            sec.style.display = 'none';
            // Disable nested inputs to prevent submitting fields from hidden sections
            sec.querySelectorAll('input, textarea').forEach(inp => inp.setAttribute('disabled', 'disabled'));
        });
        
        const targetSec = document.getElementById('section_' + selectedType);
        if (targetSec) {
            targetSec.style.display = 'block';
            targetSec.querySelectorAll('input, textarea').forEach(inp => inp.removeAttribute('disabled'));
        }
    }

    modalType.addEventListener('change', toggleConfigSections);

    // Add Question click resets form
    document.getElementById('addQuestionBtn').addEventListener('click', function() {
        document.getElementById('modal_question_id').value = 0;
        document.getElementById('modalTitle').textContent = "Add Question";
        qForm.reset();
        document.getElementById('modal_explanation').value = '';
        modalChap.innerHTML = '<option value="">-- Choose Chapter (Optional) --</option>';
        toggleConfigSections();
    });

    // Edit Question triggers loading and populating details
    document.querySelectorAll('.edit-q-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const subjId = this.getAttribute('data-subject-id');
            const chapId = this.getAttribute('data-chapter-id');
            const text = this.getAttribute('data-text');
            const type = this.getAttribute('data-type');
            const diff = this.getAttribute('data-difficulty');
            const pts = this.getAttribute('data-points');
            const correctText = this.getAttribute('data-correct-text');
            const explanation = this.getAttribute('data-explanation');

            document.getElementById('modal_question_id').value = id;
            document.getElementById('modalTitle').textContent = "Edit Question";
            document.getElementById('modal_text').value = text;
            document.getElementById('modal_explanation').value = explanation;
            document.getElementById('modal_subj').value = subjId;
            document.getElementById('modal_type').value = type;
            document.getElementById('modal_diff').value = diff;
            document.getElementById('modal_points').value = pts;

            // Load chapters for modal
            loadChapters(subjId, modalChap, parseInt(chapId || 0));

            // Set dynamic answers
            toggleConfigSections();
            
            if (type === 'MCQ_SINGLE' || type === 'MCQ_MULTIPLE') {
                // Fetch existing options for this question
                fetch('get_options_ajax.php?question_id=' + id)
                    .then(res => res.json())
                    .then(options => {
                        options.forEach((opt, idx) => {
                            if (type === 'MCQ_SINGLE') {
                                const optInputs = document.getElementsByName('mcq_single_options[]');
                                const radioInputs = document.getElementsByName('mcq_single_correct');
                                if (optInputs[idx]) optInputs[idx].value = opt.option_text;
                                if (opt.is_correct && radioInputs[idx]) radioInputs[idx].checked = true;
                            } else {
                                const optInputs = document.getElementsByName('mcq_multiple_options[]');
                                const checkInputs = document.getElementsByName('mcq_multiple_corrects[]');
                                if (optInputs[idx]) optInputs[idx].value = opt.option_text;
                                if (opt.is_correct && checkInputs[idx]) checkInputs[idx].checked = true;
                            }
                        });
                    });
            } else if (type === 'TRUE_FALSE') {
                fetch('get_options_ajax.php?question_id=' + id)
                    .then(res => res.json())
                    .then(options => {
                        options.forEach(opt => {
                            if (opt.is_correct) {
                                if (opt.option_text === 'True') document.getElementById('tf_t').checked = true;
                                else document.getElementById('tf_f').checked = true;
                            }
                        });
                    });
            } else if (type === 'FILL_BLANK') {
                document.getElementById('correct_text_answer_fb').value = correctText;
            } else if (type === 'SHORT_ANSWER') {
                document.getElementById('correct_text_answer_sa').value = correctText;
            }

            qModal.show();
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
