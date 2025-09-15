<?php
include "../db.php";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php"; 
$pageTitle = "Manage MCQs";
// Get statistics for the dashboard
$stats_query = "SELECT 
                    COUNT(*) as total_questions,
                    COUNT(DISTINCT category) as total_categories,
                    COUNT(DISTINCT instructor_id) as total_instructors,
                    COUNT(DISTINCT test_type) as total_test_types
                FROM mcq_questions";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Handle batch delete action
if (isset($_POST['action']) && $_POST['action'] == 'delete_batch') {
    $test_type = $_POST['test_type'];
    $category = $_POST['category'];
    $difficulty_level = $_POST['difficulty_level'];
    $instructor_id = $_POST['instructor_id'];
    
    // Delete all questions in the batch
    $stmt = $conn->prepare("DELETE FROM mcq_questions WHERE test_type = ? AND category = ? AND difficulty_level = ? AND instructor_id = ?");
    $stmt->bind_param("sssi", $test_type, $category, $difficulty_level, $instructor_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: list.php");
    exit();
}

// Handle individual question delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM mcq_questions WHERE id = $id");
    header("Location: list.php");
    exit();
}

// Get distinct batches with filtering
$where_clause = "";
if (isset($_GET['filter_category']) && !empty($_GET['filter_category'])) {
    $where_clause .= " AND category = '" . mysqli_real_escape_string($conn, $_GET['filter_category']) . "'";
}
if (isset($_GET['filter_difficulty']) && !empty($_GET['filter_difficulty'])) {
    $where_clause .= " AND difficulty_level = '" . mysqli_real_escape_string($conn, $_GET['filter_difficulty']) . "'";
}
if (isset($_GET['filter_instructor']) && !empty($_GET['filter_instructor'])) {
    $where_clause .= " AND instructor_id = " . intval($_GET['filter_instructor']);
}

$batch_query = "SELECT test_type, category, difficulty_level, instructor_id, COUNT(*) as question_count 
                FROM mcq_questions 
                WHERE 1=1 $where_clause
                GROUP BY test_type, category, difficulty_level, instructor_id 
                ORDER BY test_type, category, difficulty_level, instructor_id";
$batch_result = $conn->query($batch_query);
$batches = [];
while ($batch_row = $batch_result->fetch_assoc()) {
    // Get questions for this batch
    $question_query = "SELECT * FROM mcq_questions 
                      WHERE test_type = '{$batch_row['test_type']}' 
                      AND category = '{$batch_row['category']}' 
                      AND difficulty_level = '{$batch_row['difficulty_level']}' 
                      AND instructor_id = {$batch_row['instructor_id']}
                      ORDER BY id DESC";
    $question_result = $conn->query($question_query);
    $questions = [];
    while ($question_row = $question_result->fetch_assoc()) {
        $questions[] = $question_row;
    }
    
    $batch_row['questions'] = $questions;
    $batches[] = $batch_row;
}

// Get instructor names for display
$instructors_query = "SELECT id, name FROM instructors";
$instructors_result = $conn->query($instructors_query);
$instructors = [];
while ($row = $instructors_result->fetch_assoc()) {
    $instructors[$row['id']] = $row['name'];
}

// Get unique categories and difficulties for filters
$categories_query = "SELECT DISTINCT category FROM mcq_questions ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

$difficulties_query = "SELECT DISTINCT difficulty_level FROM mcq_questions ORDER BY difficulty_level";
$difficulties_result = $conn->query($difficulties_query);
$difficulties = [];
while ($row = $difficulties_result->fetch_assoc()) {
    $difficulties[] = $row['difficulty_level'];
}
?>
<div class="container-fluid px-4">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">MCQ Question Management</h1>
        <a href="add.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Batch
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Questions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_questions']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_categories']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Instructors</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_instructors']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Test Types</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_test_types']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-filter fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="filterDropdown">
                    <div class="dropdown-header">Filter Options:</div>
                    <a class="dropdown-item" href="list.php">Clear All Filters</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="get" action="list.php">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="filter_category" class="form-label">Category</label>
                        <select class="form-select" id="filter_category" name="filter_category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>" <?php echo (isset($_GET['filter_category']) && $_GET['filter_category'] == $category) ? 'selected' : ''; ?>>
                                    <?php echo $category; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="filter_difficulty" class="form-label">Difficulty Level</label>
                        <select class="form-select" id="filter_difficulty" name="filter_difficulty">
                            <option value="">All Levels</option>
                            <?php foreach ($difficulties as $difficulty): ?>
                                <option value="<?php echo $difficulty; ?>" <?php echo (isset($_GET['filter_difficulty']) && $_GET['filter_difficulty'] == $difficulty) ? 'selected' : ''; ?>>
                                    <?php echo $difficulty; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="filter_instructor" class="form-label">Instructor</label>
                        <select class="form-select" id="filter_instructor" name="filter_instructor">
                            <option value="">All Instructors</option>
                            <?php foreach ($instructors as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo (isset($_GET['filter_instructor']) && $_GET['filter_instructor'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search fa-sm"></i> Apply Filters
                    </button>
                    <a href="list.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-redo fa-sm"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Batches Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">MCQ Question Batches</h6>
            <div>
                <span class="badge bg-primary"><?php echo count($batches); ?> Batches</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($batches)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-500">No question batches found</h5>
                    <p class="text-gray-400">Click "Add New Batch" to create one.</p>
                    <a href="add.php" class="btn btn-primary mt-2">
                        <i class="fas fa-plus fa-sm"></i> Add New Batch
                    </a>
                </div>
            <?php else: ?>
                <div class="accordion" id="batchAccordion">
                    <?php foreach ($batches as $index => $batch): ?>
                    <div class="accordion-item mb-3 shadow-sm">
                        <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                            <button class="accordion-button collapsed bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false" aria-controls="collapse<?php echo $index; ?>">
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <div>
                                        <span class="badge bg-primary me-2"><?php echo $batch['category']; ?></span>
                                        <span class="badge bg-info me-2"><?php echo $batch['test_type']; ?> Questions</span>
                                        <span class="badge bg-warning text-dark me-2"><?php echo $batch['difficulty_level']; ?></span>
                                        <span class="badge bg-secondary me-2"><?php echo $instructors[$batch['instructor_id']]; ?></span>
                                        <span class="badge bg-success"><?php echo $batch['question_count']; ?> Questions</span>
                                    </div>
                                    <div class="batch-actions">
                                        <a href="edit_batch.php?test_type=<?php echo urlencode($batch['test_type']); ?>&category=<?php echo urlencode($batch['category']); ?>&difficulty_level=<?php echo urlencode($batch['difficulty_level']); ?>&instructor_id=<?php echo $batch['instructor_id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i> Edit Batch
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-success add-question-btn me-1" 
                                            data-test-type="<?php echo $batch['test_type']; ?>"
                                            data-category="<?php echo $batch['category']; ?>"
                                            data-difficulty-level="<?php echo $batch['difficulty_level']; ?>"
                                            data-instructor-id="<?php echo $batch['instructor_id']; ?>">
                                            <i class="fas fa-plus"></i> Add Question
                                        </button>
                                        <form method="post" action="list.php" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_batch">
                                            <input type="hidden" name="test_type" value="<?php echo $batch['test_type']; ?>">
                                            <input type="hidden" name="category" value="<?php echo $batch['category']; ?>">
                                            <input type="hidden" name="difficulty_level" value="<?php echo $batch['difficulty_level']; ?>">
                                            <input type="hidden" name="instructor_id" value="<?php echo $batch['instructor_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this entire batch? This will delete all <?php echo $batch['question_count']; ?> questions in this batch.')">
                                                <i class="fas fa-trash"></i> Delete Batch
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#batchAccordion">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Question</th>
                                                <th>Option A</th>
                                                <th>Option B</th>
                                                <th>Option C</th>
                                                <th>Option D</th>
                                                <th>Correct Answer</th>
                                                <th>Time Limit</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($batch['questions'] as $question): ?>
                                            <tr>
                                                <td><?php echo $question['id']; ?></td>
                                                <td><?php echo htmlspecialchars(substr($question['question'], 0, 50)) . '...'; ?></td>
                                                <td><?php echo htmlspecialchars($question['option_a']); ?></td>
                                                <td><?php echo htmlspecialchars($question['option_b']); ?></td>
                                                <td><?php echo htmlspecialchars($question['option_c']); ?></td>
                                                <td><?php echo htmlspecialchars($question['option_d']); ?></td>
                                                <td><span class="badge bg-success"><?php echo $question['correct_answer']; ?></span></td>
                                                <td><?php echo $question['time_limit']; ?>s</td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href='edit.php?id=<?php echo $question['id']; ?>' class='btn btn-sm btn-outline-primary' title="Edit Question">
                                                            <i class='fas fa-edit'></i>
                                                        </a>
                                                        <a href='list.php?delete=<?php echo $question['id']; ?>' class='btn btn-sm btn-outline-danger' title="Delete Question" onclick='return confirm("Are you sure you want to delete this question?")'>
                                                            <i class='fas fa-trash'></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Question to Batch Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addQuestionModalLabel">Add Question to Batch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addQuestionForm" method="post" action="add_question_to_batch.php">
                    <input type="hidden" id="batch_test_type" name="test_type">
                    <input type="hidden" id="batch_category" name="category">
                    <input type="hidden" id="batch_difficulty_level" name="difficulty_level">
                    <input type="hidden" id="batch_instructor_id" name="instructor_id">
                    
                    <div class="mb-3">
                        <label for="question" class="form-label">Question</label>
                        <textarea class="form-control" id="question" name="question" rows="3" required placeholder="Enter the question text..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="option_a" class="form-label">Option A</label>
                            <input type="text" class="form-control" id="option_a" name="option_a" required placeholder="Enter option A">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="option_b" class="form-label">Option B</label>
                            <input type="text" class="form-control" id="option_b" name="option_b" required placeholder="Enter option B">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="option_c" class="form-label">Option C</label>
                            <input type="text" class="form-control" id="option_c" name="option_c" required placeholder="Enter option C">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="option_d" class="form-label">Option D</label>
                            <input type="text" class="form-control" id="option_d" name="option_d" required placeholder="Enter option D">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="correct_answer" class="form-label">Correct Answer</label>
                            <select class="form-select" id="correct_answer" name="correct_answer" required>
                                <option value="">Select Correct Option</option>
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="time_limit" class="form-label">Time Limit (seconds)</label>
                            <input type="number" class="form-control" id="time_limit" name="time_limit" min="10" max="300" value="30" required>
                            <div class="form-text">Time limit for this question (10-300 seconds)</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveQuestionBtn">
                    <i class="fas fa-save fa-sm"></i> Save Question
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Bootstrap core JavaScript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Page level custom scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle add question to batch
    const addQuestionModal = new bootstrap.Modal(document.getElementById('addQuestionModal'));
    const addQuestionForm = document.getElementById('addQuestionForm');
    const saveQuestionBtn = document.getElementById('saveQuestionBtn');
    
    // Add event listeners to all "Add Question" buttons
    document.querySelectorAll('.add-question-btn').forEach(button => {
        button.addEventListener('click', function() {
            const testType = this.getAttribute('data-test-type');
            const category = this.getAttribute('data-category');
            const difficultyLevel = this.getAttribute('data-difficulty-level');
            const instructorId = this.getAttribute('data-instructor-id');
            
            // Set hidden fields
            document.getElementById('batch_test_type').value = testType;
            document.getElementById('batch_category').value = category;
            document.getElementById('batch_difficulty_level').value = difficultyLevel;
            document.getElementById('batch_instructor_id').value = instructorId;
            
            // Show modal
            addQuestionModal.show();
        });
    });
    
    // Handle save question button
    saveQuestionBtn.addEventListener('click', function() {
        // Validate form
        if (!addQuestionForm.checkValidity()) {
            addQuestionForm.reportValidity();
            return;
        }
        
        // Submit form
        addQuestionForm.submit();
    });
    
    // Clear form when modal is hidden
    document.getElementById('addQuestionModal').addEventListener('hidden.bs.modal', function () {
        addQuestionForm.reset();
    });
});
</script>
</body>
</html>