<?php
include "../db.php";
$pageTitle = "Exam Grading";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Handle Saving Grade for a Specific Attempt
if (isset($_POST['save_grades'])) {
    $attempt_id = intval($_POST['attempt_id']);
    $points_awarded_arr = $_POST['points_awarded']; // array of question_id => points
    $is_correct_arr = $_POST['is_correct']; // array of question_id => 1 or 0
    
    foreach ($points_awarded_arr as $q_id => $pts) {
        $q_id = intval($q_id);
        $points = intval($pts);
        $correct = isset($is_correct_arr[$q_id]) ? intval($is_correct_arr[$q_id]) : 0;
        
        $stmt = $conn->prepare("UPDATE student_answers 
                                SET points_awarded = ?, is_correct = ?, checked_by_admin = 1 
                                WHERE attempt_id = ? AND question_id = ?");
        $stmt->bind_param("iiii", $points, $correct, $attempt_id, $q_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Now, recalculate and insert/update results table for this attempt
    // Fetch attempt details
    $att_stmt = $conn->prepare("SELECT * FROM student_attempts WHERE id = ?");
    $att_stmt->bind_param("i", $attempt_id);
    $att_stmt->execute();
    $attempt = $att_stmt->get_result()->fetch_assoc();
    $att_stmt->close();
    
    $student_id = $attempt['student_id'];
    $test_id = $attempt['test_id'];
    
    // Fetch test details for passing marks
    $test_stmt = $conn->prepare("SELECT * FROM tests WHERE id = ?");
    $test_stmt->bind_param("i", $test_id);
    $test_stmt->execute();
    $test = $test_stmt->get_result()->fetch_assoc();
    $test_stmt->close();
    
    // Calculate stats
    // Total Questions in test
    $total_qs_res = $conn->query("SELECT COUNT(*) FROM test_questions WHERE test_id = $test_id");
    $total_questions = $total_qs_res->fetch_row()[0];
    
    // Max points possible in test
    $max_pts_res = $conn->query("SELECT SUM(q.points) FROM test_questions tq JOIN questions q ON tq.question_id = q.id WHERE tq.test_id = $test_id");
    $max_points = $max_pts_res->fetch_row()[0] ?: 1;
    
    // Fetch all student answers
    $answers_res = $conn->query("SELECT sa.*, q.points as max_points FROM student_answers sa 
                                JOIN questions q ON sa.question_id = q.id 
                                WHERE sa.attempt_id = $attempt_id");
    
    $correct_answers = 0;
    $wrong_answers = 0;
    $skipped_questions = 0;
    $total_earned_score = 0;
    
    while ($ans = $answers_res->fetch_assoc()) {
        $is_skipped = empty($ans['selected_option_ids']) && empty($ans['text_answer']);
        if ($is_skipped) {
            $skipped_questions++;
        } else {
            if ($ans['is_correct'] == 1) {
                $correct_answers++;
            } else {
                $wrong_answers++;
            }
        }
        $total_earned_score += $ans['points_awarded'];
    }
    
    $percentage = round(($total_earned_score / $max_points) * 100, 2);
    $is_passed = ($percentage >= $test['passing_marks']) ? 1 : 0;
    
    // Calculate time taken
    $time_taken = strtotime($attempt['completed_at']) - strtotime($attempt['started_at']);
    if ($time_taken < 0) $time_taken = 0;
    
    // Check if result already exists
    $res_check = $conn->query("SELECT id FROM results WHERE attempt_id = $attempt_id");
    if ($res_check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE results SET 
                                total_questions = ?, correct_answers = ?, wrong_answers = ?, skipped_questions = ?, 
                                score = ?, percentage = ?, time_taken_seconds = ?, is_passed = ?
                                WHERE attempt_id = ?");
        $stmt->bind_param("iiiiddiii", $total_questions, $correct_answers, $wrong_answers, $skipped_questions, $total_earned_score, $percentage, $time_taken, $is_passed, $attempt_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO results (attempt_id, student_id, test_id, total_questions, correct_answers, wrong_answers, skipped_questions, score, percentage, time_taken_seconds, is_passed) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiiiidii", $attempt_id, $student_id, $test_id, $total_questions, $correct_answers, $wrong_answers, $skipped_questions, $total_earned_score, $percentage, $time_taken, $is_passed);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update Leaderboard entry & award badges
    require_once "../../includes/badge_helper.php";
    award_badges($conn, $student_id, $test_id, $attempt_id);

    // Auto generate certificate if passed and enabled
    if ($is_passed && $test['certificate_enabled']) {
        $res_id_res = $conn->query("SELECT id FROM results WHERE attempt_id = $attempt_id");
        $result_id = $res_id_res->fetch_row()[0];
        
        $cert_check = $conn->query("SELECT id FROM certificates WHERE result_id = $result_id");
        if ($cert_check->num_rows == 0) {
            $verification_code = 'CERT-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $conn->prepare("INSERT INTO certificates (result_id, student_id, test_id, verification_code) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $result_id, $student_id, $test_id, $verification_code);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    header("Location: manual_checking.php?success=graded");
    exit();
}

// Fetch all attempts requiring manual grading
$grading_query = "SELECT sa.id as attempt_id, sa.started_at, sa.completed_at, st.name as student_name, t.title as test_title,
                  COUNT(ans.id) as total_manual_qs,
                  SUM(CASE WHEN ans.checked_by_admin = 1 THEN 1 ELSE 0 END) as graded_manual_qs
                  FROM student_attempts sa
                  JOIN students st ON sa.student_id = st.id
                  JOIN tests t ON sa.test_id = t.id
                  JOIN student_answers ans ON ans.attempt_id = sa.id
                  JOIN questions q ON ans.question_id = q.id
                  WHERE q.question_type IN ('SHORT_ANSWER', 'LONG_ANSWER')
                  GROUP BY sa.id, sa.started_at, sa.completed_at, st.name, t.title
                  HAVING SUM(CASE WHEN ans.checked_by_admin = 1 THEN 1 ELSE 0 END) < COUNT(ans.id)
                  ORDER BY sa.completed_at DESC";
$grading_res = $conn->query($grading_query);

// Specific attempt detail grading view
$grade_attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;
$grading_details = [];
if ($grade_attempt_id > 0) {
    $detail_query = "SELECT ans.*, q.question_text, q.question_type, q.points as max_points, q.correct_text_answer
                     FROM student_answers ans
                     JOIN questions q ON ans.question_id = q.id
                     WHERE ans.attempt_id = $grade_attempt_id AND q.question_type IN ('SHORT_ANSWER', 'LONG_ANSWER')";
    $details_res = $conn->query($detail_query);
    while($row = $details_res->fetch_assoc()) {
        $grading_details[] = $row;
    }
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;"><i class="bi bi-pen me-2"></i>Exam Grading</h1>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        Grading successfully recorded and final results calculated!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- List of Attempts Needing Grading -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-clock-history me-2"></i>Grading Queue (<?php echo $grading_res->num_rows; ?>)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Assessment</th>
                                <th>Progress</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($grading_res->num_rows == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-patch-check fs-2 d-block mb-2"></i>
                                        All submissions have been fully graded!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($g = $grading_res->fetch_assoc()): ?>
                                    <tr class="<?php echo $grade_attempt_id == $g['attempt_id'] ? 'table-active border-primary' : ''; ?>">
                                        <td><strong><?php echo htmlspecialchars($g['student_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($g['test_title']); ?></td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo $g['graded_manual_qs']; ?> / <?php echo $g['total_manual_qs']; ?> Graded
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="manual_checking.php?attempt_id=<?php echo $g['attempt_id']; ?>" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 0.8rem;">
                                                Grade <i class="bi bi-chevron-right ms-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Grading workspace -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light py-3">
                <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-file-earmark-check me-2"></i>Grading Workspace</h6>
            </div>
            <div class="card-body">
                <?php if ($grade_attempt_id <= 0 || empty($grading_details)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-left fs-1 d-block mb-2 text-primary"></i>
                        Select a submission from the grading queue to grade it.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="attempt_id" value="<?php echo $grade_attempt_id; ?>">
                        
                        <?php foreach ($grading_details as $index => $gd): ?>
                            <div class="card mb-4 border-left-warning bg-light border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-dark">Q <?php echo $index + 1; ?> - <?php echo str_replace('_', ' ', $gd['question_type']); ?></span>
                                        <span class="text-muted small fw-bold">Max Marks: <?php echo $gd['max_points']; ?></span>
                                    </div>
                                    <div class="fw-bold mb-3"><?php echo htmlspecialchars($gd['question_text']); ?></div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label text-muted small fw-bold">Student's Answer:</label>
                                        <div class="bg-white p-3 rounded border text-indigo font-monospace" style="white-space: pre-wrap;"><?php echo htmlspecialchars($gd['text_answer'] ?: '[No Answer Provided]'); ?></div>
                                    </div>
                                    
                                    <?php if (!empty($gd['correct_text_answer'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Suggested Reference Answer:</label>
                                            <div class="bg-white p-3 rounded border text-success" style="white-space: pre-wrap;"><?php echo htmlspecialchars($gd['correct_text_answer']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label text-muted small fw-bold">Result:</label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input check-radio" type="radio" name="is_correct[<?php echo $gd['question_id']; ?>]" id="correct_<?php echo $gd['question_id']; ?>" value="1" required data-max="<?php echo $gd['max_points']; ?>" data-target="pts_<?php echo $gd['question_id']; ?>">
                                                    <label class="form-check-label text-success fw-bold" for="correct_<?php echo $gd['question_id']; ?>"><i class="bi bi-check-circle me-1"></i> Correct</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input check-radio" type="radio" name="is_correct[<?php echo $gd['question_id']; ?>]" id="incorrect_<?php echo $gd['question_id']; ?>" value="0" checked data-max="0" data-target="pts_<?php echo $gd['question_id']; ?>">
                                                    <label class="form-check-label text-danger fw-bold" for="incorrect_<?php echo $gd['question_id']; ?>"><i class="bi bi-x-circle me-1"></i> Incorrect</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="pts_<?php echo $gd['question_id']; ?>" class="form-label text-muted small fw-bold">Marks Awarded:</label>
                                            <input type="number" class="form-control form-control-sm" style="width: 100px;" id="pts_<?php echo $gd['question_id']; ?>" name="points_awarded[<?php echo $gd['question_id']; ?>]" min="0" max="<?php echo $gd['max_points']; ?>" value="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="save_grades" class="btn btn-success btn-lg">
                                <i class="bi bi-save me-1"></i> Save & Recalculate Results
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Automatically set marks when radio correct/incorrect changes
    document.querySelectorAll('.check-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const maxVal = this.getAttribute('data-max');
            const targetId = this.getAttribute('data-target');
            document.getElementById(targetId).value = maxVal;
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
