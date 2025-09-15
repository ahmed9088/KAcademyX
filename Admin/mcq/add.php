<?php
// Removed session_start() since it's already started in header.php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Add New MCQ";
// Initialize session for storing MCQs
if (!isset($_SESSION['mcq_batch'])) {
    $_SESSION['mcq_batch'] = [];
}
// Initialize session for test type
if (!isset($_SESSION['test_type'])) {
    $_SESSION['test_type'] = null;
}
// Fetch instructors for dropdown
$instructors_query = "SELECT id, name, expertise FROM instructors ORDER BY name";
$instructors_result = mysqli_query($conn, $instructors_query);
$instructors = [];
if ($instructors_result && mysqli_num_rows($instructors_result) > 0) {
    while ($row = mysqli_fetch_assoc($instructors_result)) {
        $instructors[] = $row;
    }
}
// Handle form submission for adding a question to the batch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_batch'])) {
    // Validate and sanitize inputs
    $question = trim($_POST['question']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_answer = trim($_POST['correct_answer']);
    $instructor_id = intval($_POST['instructor_id']);
    $test_type = trim($_POST['test_type']);
    $difficulty_level = trim($_POST['difficulty_level']);
    $category = trim($_POST['category']);
    $time_limit = intval($_POST['time_limit']);
    
    // Validate correct answer is A, B, C, or D
    if (!in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
        $error_message = "Invalid correct answer selected. Please choose A, B, C, or D.";
    } else {
        // Set test type in session if not set
        if ($_SESSION['test_type'] === null) {
            $_SESSION['test_type'] = $test_type;
        } else {
            // Use session test type instead of form value
            $test_type = $_SESSION['test_type'];
        }
        
        // Check if batch is full
        if (count($_SESSION['mcq_batch']) >= intval($test_type)) {
            $error_message = "Cannot add more questions. Test type allows only $test_type questions.";
        } else {
            // Validate required fields
            if (empty($question) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || 
                empty($correct_answer) || empty($instructor_id) || empty($test_type) || 
                empty($difficulty_level) || empty($category) || empty($time_limit)) {
                $error_message = "All fields are required.";
            } else {
                // Validate test type is one of allowed values
                $allowed_test_types = ['5', '25', '50', '100'];
                if (!in_array($test_type, $allowed_test_types)) {
                    $error_message = "Invalid test type selected.";
                } else {
                    // Validate difficulty level
                    $allowed_difficulties = ['Easy', 'Medium', 'Hard'];
                    if (!in_array($difficulty_level, $allowed_difficulties)) {
                        $error_message = "Invalid difficulty level selected.";
                    } else {
                        // Validate category
                        $allowed_categories = ['Physics', 'Computer Science', 'Biology', 'Mathematics', 'General Knowledge'];
                        if (!in_array($category, $allowed_categories)) {
                            $error_message = "Invalid category selected.";
                        } else {
                            // Validate time limit
                            if ($time_limit < 10 || $time_limit > 300) {
                                $error_message = "Time limit must be between 10 and 300 seconds.";
                            } else {
                                // Validate instructor exists
                                $check_instructor = $conn->prepare("SELECT id FROM instructors WHERE id = ?");
                                $check_instructor->bind_param("i", $instructor_id);
                                $check_instructor->execute();
                                $result = $check_instructor->get_result();
                                
                                if ($result->num_rows === 0) {
                                    $error_message = "Invalid instructor selected.";
                                } else {
                                    // Add to session batch
                                    $mcq = [
                                        'question' => $question,
                                        'option_a' => $option_a,
                                        'option_b' => $option_b,
                                        'option_c' => $option_c,
                                        'option_d' => $option_d,
                                        'correct_answer' => $correct_answer,
                                        'instructor_id' => $instructor_id,
                                        'test_type' => $test_type,
                                        'difficulty_level' => $difficulty_level,
                                        'category' => $category,
                                        'time_limit' => $time_limit
                                    ];
                                    
                                    $_SESSION['mcq_batch'][] = $mcq;
                                    
                                    // Reset form but keep instructor and test type
                                    $instructor_id = $_POST['instructor_id'];
                                    $difficulty_level = $_POST['difficulty_level'];
                                    $category = $_POST['category'];
                                    $time_limit = $_POST['time_limit'];
                                    
                                    // Clear form values
                                    $_POST = [];
                                    
                                    // Restore values
                                    $_POST['instructor_id'] = $instructor_id;
                                    $_POST['test_type'] = $test_type;
                                    $_POST['difficulty_level'] = $difficulty_level;
                                    $_POST['category'] = $category;
                                    $_POST['time_limit'] = $time_limit;
                                    
                                    $success_message = "Question added to batch successfully! Total questions: " . count($_SESSION['mcq_batch']) . " of $test_type";
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
// Handle batch upload to database
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_batch'])) {
    if (!empty($_SESSION['mcq_batch'])) {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($_SESSION['mcq_batch'] as $mcq) {
            // Validate correct answer before processing
            if (!in_array($mcq['correct_answer'], ['A', 'B', 'C', 'D'])) {
                $error_count++;
                error_log("Invalid correct answer: " . $mcq['correct_answer']);
                continue;
            }
            
            // Extract variables
            $question = mysqli_real_escape_string($conn, $mcq['question']);
            $option_a = mysqli_real_escape_string($conn, $mcq['option_a']);
            $option_b = mysqli_real_escape_string($conn, $mcq['option_b']);
            $option_c = mysqli_real_escape_string($conn, $mcq['option_c']);
            $option_d = mysqli_real_escape_string($conn, $mcq['option_d']);
            $correct_answer = mysqli_real_escape_string($conn, $mcq['correct_answer']);
            $instructor_id = intval($mcq['instructor_id']);
            $test_type = mysqli_real_escape_string($conn, $mcq['test_type']);
            $difficulty_level = mysqli_real_escape_string($conn, $mcq['difficulty_level']);
            $category = mysqli_real_escape_string($conn, $mcq['category']);
            $time_limit = intval($mcq['time_limit']);
            
            // Build the query with escaped values
            $sql = "INSERT INTO mcq_questions (question, option_a, option_b, option_c, option_d, correct_answer, instructor_id, test_type, difficulty_level, category, time_limit, created_at) 
                    VALUES ('$question', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_answer', $instructor_id, '$test_type', '$difficulty_level', '$category', $time_limit, NOW())";
            
            $result = mysqli_query($conn, $sql);
            
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
                error_log("Query failed: " . mysqli_error($conn));
            }
        }
        
        // Clear the batch
        $_SESSION['mcq_batch'] = [];
        $_SESSION['test_type'] = null;
        
        if ($error_count === 0) {
            $upload_message = "All $success_count questions uploaded successfully!";
        } else {
            $upload_message = "$success_count questions uploaded successfully. $error_count questions failed.";
        }
    } else {
        $upload_message = "No questions to upload!";
    }
}
// Handle clearing the batch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clear_batch'])) {
    $_SESSION['mcq_batch'] = [];
    $_SESSION['test_type'] = null;
    $clear_message = "Batch cleared successfully!";
}
include "../includes/sidebar.php";
include "../includes/footer.php";
?>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Add New MCQ Question</h6>
        <div class="badge bg-primary fs-6">
            Questions in batch: <span id="batch-count"><?php echo count($_SESSION['mcq_batch']); ?></span>
            <?php if ($_SESSION['test_type'] !== null): ?>
            / <?php echo $_SESSION['test_type']; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($upload_message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $upload_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($clear_message)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?php echo $clear_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="question" class="form-label">Question</label>
                        <textarea class="form-control" id="question" name="question" rows="3" required><?php echo isset($_POST['question']) ? htmlspecialchars($_POST['question']) : ''; ?></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="instructor_id" class="form-label">Instructor</label>
                        <select class="form-select" id="instructor_id" name="instructor_id" required>
                            <option value="">Select Instructor</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor['id']; ?>" <?php echo (isset($_POST['instructor_id']) && $_POST['instructor_id'] == $instructor['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($instructor['name'] . ' - ' . $instructor['expertise']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="option_a" class="form-label">Option A</label>
                    <input type="text" class="form-control" id="option_a" name="option_a" required value="<?php echo isset($_POST['option_a']) ? htmlspecialchars($_POST['option_a']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="option_b" class="form-label">Option B</label>
                    <input type="text" class="form-control" id="option_b" name="option_b" required value="<?php echo isset($_POST['option_b']) ? htmlspecialchars($_POST['option_b']) : ''; ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="option_c" class="form-label">Option C</label>
                    <input type="text" class="form-control" id="option_c" name="option_c" required value="<?php echo isset($_POST['option_c']) ? htmlspecialchars($_POST['option_c']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="option_d" class="form-label">Option D</label>
                    <input type="text" class="form-control" id="option_d" name="option_d" required value="<?php echo isset($_POST['option_d']) ? htmlspecialchars($_POST['option_d']) : ''; ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="correct_answer" class="form-label">Correct Answer</label>
                    <select class="form-select" id="correct_answer" name="correct_answer" required>
                        <option value="">Select Correct Option</option>
                        <option value="A" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'A') ? 'selected' : ''; ?>>Option A</option>
                        <option value="B" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'B') ? 'selected' : ''; ?>>Option B</option>
                        <option value="C" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'C') ? 'selected' : ''; ?>>Option C</option>
                        <option value="D" <?php echo (isset($_POST['correct_answer']) && $_POST['correct_answer'] == 'D') ? 'selected' : ''; ?>>Option D</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="test_type" class="form-label">Test Type</label>
                    <select class="form-select" id="test_type" name="test_type" required <?php echo ($_SESSION['test_type'] !== null) ? 'disabled' : ''; ?>>
                        <option value="">Select Test Type</option>
                        <option value="5" <?php echo (isset($_POST['test_type']) && $_POST['test_type'] == '5') || $_SESSION['test_type'] == '5' ? 'selected' : ''; ?>>5 Questions</option>
                        <option value="25" <?php echo (isset($_POST['test_type']) && $_POST['test_type'] == '25') || $_SESSION['test_type'] == '25' ? 'selected' : ''; ?>>25 Questions</option>
                        <option value="50" <?php echo (isset($_POST['test_type']) && $_POST['test_type'] == '50') || $_SESSION['test_type'] == '50' ? 'selected' : ''; ?>>50 Questions</option>
                        <option value="100" <?php echo (isset($_POST['test_type']) && $_POST['test_type'] == '100') || $_SESSION['test_type'] == '100' ? 'selected' : ''; ?>>100 Questions</option>
                    </select>
                    <?php if ($_SESSION['test_type'] !== null): ?>
                    <input type="hidden" name="test_type" value="<?php echo $_SESSION['test_type']; ?>">
                    <?php endif; ?>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="difficulty_level" class="form-label">Difficulty Level</label>
                    <select class="form-select" id="difficulty_level" name="difficulty_level" required>
                        <option value="">Select Difficulty</option>
                        <option value="Easy" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] == 'Easy') ? 'selected' : ''; ?>>Easy</option>
                        <option value="Medium" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="Hard" <?php echo (isset($_POST['difficulty_level']) && $_POST['difficulty_level'] == 'Hard') ? 'selected' : ''; ?>>Hard</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Physics" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Physics') ? 'selected' : ''; ?>>Physics</option>
                        <option value="Computer Science" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Biology" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Biology') ? 'selected' : ''; ?>>Biology</option>
                        <option value="Mathematics" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                        <option value="General Knowledge" <?php echo (isset($_POST['category']) && $_POST['category'] == 'General Knowledge') ? 'selected' : ''; ?>>General Knowledge</option>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="time_limit" class="form-label">Time Limit (seconds)</label>
                    <input type="number" class="form-control" id="time_limit" name="time_limit" min="10" max="300" value="<?php echo isset($_POST['time_limit']) ? htmlspecialchars($_POST['time_limit']) : '30'; ?>" required>
                    <small class="form-text text-muted">Time limit for this question in seconds (10-300)</small>
                </div>
                
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <button type="submit" name="add_to_batch" class="btn btn-primary w-100" <?php echo ($_SESSION['test_type'] !== null && count($_SESSION['mcq_batch']) >= intval($_SESSION['test_type'])) ? 'disabled' : ''; ?>>
                        <i class="bi bi-plus-circle me-1"></i> Add to Batch
                    </button>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Preview</label>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title" id="preview-question">Question will appear here</h5>
                        <div class="list-group">
                            <div class="list-group-item">
                                <input class="form-check-input me-1" type="radio" name="preview-option" id="preview-a">
                                <label class="form-check-label" for="preview-a" id="preview-option-a">Option A</label>
                            </div>
                            <div class="list-group-item">
                                <input class="form-check-input me-1" type="radio" name="preview-option" id="preview-b">
                                <label class="form-check-label" for="preview-b" id="preview-option-b">Option B</label>
                            </div>
                            <div class="list-group-item">
                                <input class="form-check-input me-1" type="radio" name="preview-option" id="preview-c">
                                <label class="form-check-label" for="preview-c" id="preview-option-c">Option C</label>
                            </div>
                            <div class="list-group-item">
                                <input class="form-check-input me-1" type="radio" name="preview-option" id="preview-d">
                                <label class="form-check-label" for="preview-d" id="preview-option-d">Option D</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <button type="submit" name="clear_batch" class="btn btn-warning">
                        <i class="bi bi-trash me-1"></i> Clear Batch
                    </button>
                </div>
                
                <div>
                    <a href="list.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" name="upload_batch" class="btn btn-success" <?php echo empty($_SESSION['mcq_batch']) ? 'disabled' : ''; ?>>
                        <i class="bi bi-upload me-1"></i> Upload All MCQs
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($_SESSION['mcq_batch'])): ?>
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Questions in Batch:</h5>
                <button class="btn btn-sm btn-outline-primary" id="toggleBatchView">
                    <i class="bi bi-eye me-1"></i> Toggle View
                </button>
            </div>
            
            <!-- Simple View (Default) -->
            <div id="simpleView" class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Difficulty</th>
                            <th>Test Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['mcq_batch'] as $index => $mcq): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars(substr($mcq['question'], 0, 50)) . '...'; ?></td>
                            <td><?php echo $mcq['category']; ?></td>
                            <td><?php echo $mcq['difficulty_level']; ?></td>
                            <td><?php echo $mcq['test_type']; ?> Questions</td>
                            <td>
                                <button class="btn btn-sm btn-info view-mcq" data-index="<?php echo $index; ?>" title="View Question">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger remove-mcq" data-index="<?php echo $index; ?>" title="Remove Question">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Detailed View (Hidden by default) -->
            <div id="detailedView" style="display: none;">
                <?php foreach ($_SESSION['mcq_batch'] as $index => $mcq): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Question #<?php echo $index + 1; ?></h6>
                        <div>
                            <span class="badge bg-secondary"><?php echo $mcq['category']; ?></span>
                            <span class="badge bg-info"><?php echo $mcq['difficulty_level']; ?></span>
                            <span class="badge bg-primary"><?php echo $mcq['test_type']; ?> Questions</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($mcq['question']); ?></h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-check <?php echo $mcq['correct_answer'] == 'A' ? 'text-success fw-bold' : ''; ?>">
                                    <input class="form-check-input" type="radio" name="question<?php echo $index; ?>" id="optionA<?php echo $index; ?>" disabled>
                                    <label class="form-check-label" for="optionA<?php echo $index; ?>">
                                        A. <?php echo htmlspecialchars($mcq['option_a']); ?>
                                    </label>
                                </div>
                                <div class="form-check <?php echo $mcq['correct_answer'] == 'B' ? 'text-success fw-bold' : ''; ?>">
                                    <input class="form-check-input" type="radio" name="question<?php echo $index; ?>" id="optionB<?php echo $index; ?>" disabled>
                                    <label class="form-check-label" for="optionB<?php echo $index; ?>">
                                        B. <?php echo htmlspecialchars($mcq['option_b']); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check <?php echo $mcq['correct_answer'] == 'C' ? 'text-success fw-bold' : ''; ?>">
                                    <input class="form-check-input" type="radio" name="question<?php echo $index; ?>" id="optionC<?php echo $index; ?>" disabled>
                                    <label class="form-check-label" for="optionC<?php echo $index; ?>">
                                        C. <?php echo htmlspecialchars($mcq['option_c']); ?>
                                    </label>
                                </div>
                                <div class="form-check <?php echo $mcq['correct_answer'] == 'D' ? 'text-success fw-bold' : ''; ?>">
                                    <input class="form-check-input" type="radio" name="question<?php echo $index; ?>" id="optionD<?php echo $index; ?>" disabled>
                                    <label class="form-check-label" for="optionD<?php echo $index; ?>">
                                        D. <?php echo htmlspecialchars($mcq['option_d']); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            <button class="btn btn-sm btn-danger remove-mcq" data-index="<?php echo $index; ?>">
                                <i class="bi bi-trash me-1"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- View Question Modal -->
<div class="modal fade" id="viewQuestionModal" tabindex="-1" aria-labelledby="viewQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewQuestionModalLabel">Question Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalBodyContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update batch count
    function updateBatchCount() {
        document.getElementById('batch-count').textContent = <?php echo count($_SESSION['mcq_batch']); ?>;
    }
    
    // Preview functionality
    const questionInput = document.getElementById('question');
    const optionAInput = document.getElementById('option_a');
    const optionBInput = document.getElementById('option_b');
    const optionCInput = document.getElementById('option_c');
    const optionDInput = document.getElementById('option_d');
    
    const previewQuestion = document.getElementById('preview-question');
    const previewOptionA = document.getElementById('preview-option-a');
    const previewOptionB = document.getElementById('preview-option-b');
    const previewOptionC = document.getElementById('preview-option-c');
    const previewOptionD = document.getElementById('preview-option-d');
    
    // Auto-update preview on input
    [questionInput, optionAInput, optionBInput, optionCInput, optionDInput].forEach(input => {
        input.addEventListener('input', function() {
            previewQuestion.textContent = questionInput.value || 'Question will appear here';
            previewOptionA.textContent = optionAInput.value || 'Option A';
            previewOptionB.textContent = optionBInput.value || 'Option B';
            previewOptionC.textContent = optionCInput.value || 'Option C';
            previewOptionD.textContent = optionDInput.value || 'Option D';
        });
    });
    
    // Toggle between simple and detailed view
    document.getElementById('toggleBatchView').addEventListener('click', function() {
        const simpleView = document.getElementById('simpleView');
        const detailedView = document.getElementById('detailedView');
        
        if (simpleView.style.display === 'none') {
            simpleView.style.display = 'block';
            detailedView.style.display = 'none';
        } else {
            simpleView.style.display = 'none';
            detailedView.style.display = 'block';
        }
    });
    
    // View MCQ details in modal
    document.querySelectorAll('.view-mcq').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            
            // Get the MCQ data from PHP session via AJAX
            fetch('get_mcq_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'index=' + index
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const mcq = data.mcq;
                    let modalContent = `
                        <div class="mb-3">
                            <h5>${mcq.question}</h5>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check ${mcq.correct_answer === 'A' ? 'text-success fw-bold' : ''}">
                                    <input class="form-check-input" type="radio" name="question" disabled>
                                    <label class="form-check-label">
                                        A. ${mcq.option_a}
                                    </label>
                                </div>
                                <div class="form-check ${mcq.correct_answer === 'B' ? 'text-success fw-bold' : ''}">
                                    <input class="form-check-input" type="radio" name="question" disabled>
                                    <label class="form-check-label">
                                        B. ${mcq.option_b}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check ${mcq.correct_answer === 'C' ? 'text-success fw-bold' : ''}">
                                    <input class="form-check-input" type="radio" name="question" disabled>
                                    <label class="form-check-label">
                                        C. ${mcq.option_c}
                                    </label>
                                </div>
                                <div class="form-check ${mcq.correct_answer === 'D' ? 'text-success fw-bold' : ''}">
                                    <input class="form-check-input" type="radio" name="question" disabled>
                                    <label class="form-check-label">
                                        D. ${mcq.option_d}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p><strong>Category:</strong> ${mcq.category}</p>
                            <p><strong>Difficulty:</strong> ${mcq.difficulty_level}</p>
                            <p><strong>Test Type:</strong> ${mcq.test_type} Questions</p>
                            <p><strong>Time Limit:</strong> ${mcq.time_limit} seconds</p>
                            <p><strong>Correct Answer:</strong> ${mcq.correct_answer}</p>
                        </div>
                    `;
                    
                    document.getElementById('modalBodyContent').innerHTML = modalContent;
                    const modal = new bootstrap.Modal(document.getElementById('viewQuestionModal'));
                    modal.show();
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
    
    // Remove MCQ from batch
    document.querySelectorAll('.remove-mcq').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            
            if (confirm('Are you sure you want to remove this question from the batch?')) {
                // Send AJAX request to remove from session
                fetch('remove_mcq.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'index=' + index
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Find the closest row or card and remove it
                        const row = this.closest('tr');
                        const card = this.closest('.card');
                        
                        if (row) {
                            row.remove();
                        } else if (card) {
                            card.remove();
                        }
                        
                        // Update batch count
                        document.getElementById('batch-count').textContent = data.count;
                        
                        // Disable upload button if no questions left
                        if (data.count === 0) {
                            document.querySelector('button[name="upload_batch"]').disabled = true;
                            document.querySelector('.table-responsive').parentElement.remove();
                            document.getElementById('detailedView').parentElement.remove();
                        }
                        
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show';
                        alertDiv.innerHTML = 'Question removed from batch successfully!';
                        document.querySelector('.card-body').prepend(alertDiv);
                        
                        // Remove alert after 3 seconds
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 3000);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });
});
</script>
</body>
</html>