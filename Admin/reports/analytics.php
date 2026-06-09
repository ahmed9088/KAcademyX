<?php
include "../db.php";
$pageTitle = "Reports & Analytics";
include "../includes/header.php";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Fetch general stats
$total_students = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$total_tests = $conn->query("SELECT COUNT(*) FROM tests")->fetch_row()[0];
$total_attempts = $conn->query("SELECT COUNT(*) FROM student_attempts")->fetch_row()[0];

$average_score_res = $conn->query("SELECT AVG(percentage) FROM results");
$average_score = $average_score_res ? round($average_score_res->fetch_row()[0], 1) : 0;

$pass_count = $conn->query("SELECT COUNT(*) FROM results WHERE is_passed = 1")->fetch_row()[0];
$total_results = $conn->query("SELECT COUNT(*) FROM results")->fetch_row()[0];
$pass_rate = ($total_results > 0) ? round(($pass_count / $total_results) * 100, 1) : 0;

// Fetch Top 5 Students
$top_students_query = "SELECT st.name, st.email, AVG(r.percentage) as avg_score, COUNT(r.id) as tests_taken
                       FROM results r
                       JOIN students st ON r.student_id = st.id
                       GROUP BY r.student_id
                       ORDER BY avg_score DESC, tests_taken DESC
                       LIMIT 5";
$top_students_res = $conn->query($top_students_query);

// Fetch Test-by-Test Performance
$test_perf_query = "SELECT t.title, s.name as subject_name, tc.name as category_name,
                    COUNT(r.id) as attempts,
                    AVG(r.percentage) as avg_score,
                    SUM(CASE WHEN r.is_passed = 1 THEN 1 ELSE 0 END) as passed_count
                    FROM tests t
                    JOIN subjects s ON t.subject_id = s.id
                    JOIN test_categories tc ON t.category_id = tc.id
                    LEFT JOIN results r ON r.test_id = t.id
                    GROUP BY t.id
                    ORDER BY attempts DESC";
$test_perf_res = $conn->query($test_perf_query);

// Fetch Question Item Analysis (Top 5 hardest questions)
$question_analysis_query = "SELECT q.question_text, s.name as subject_name,
                            COUNT(sa.id) as total_answers,
                            SUM(CASE WHEN sa.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers
                            FROM questions q
                            JOIN subjects s ON q.subject_id = s.id
                            JOIN student_answers sa ON sa.question_id = q.id
                            GROUP BY q.id, q.question_text, s.name
                            HAVING COUNT(sa.id) >= 2
                            ORDER BY (SUM(CASE WHEN sa.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(sa.id)) ASC
                            LIMIT 5";
$question_analysis_res = $conn->query($question_analysis_query);
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800" style="font-weight: 700;"><i class="bi bi-graph-up-arrow me-2"></i>Reports & Analytics</h1>
</div>

<!-- Stats Dashboard Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow-sm h-100 py-2 border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Student Registrations</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_students; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people-fill fs-2 text-primary" style="opacity:0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow-sm h-100 py-2 border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Exams & Tests</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_tests; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-file-earmark-check fs-2 text-success" style="opacity:0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow-sm h-100 py-2 border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Score (All Students)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $average_score; ?>%</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-percent fs-2 text-info" style="opacity:0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow-sm h-100 py-2 border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Overall Pass Rate</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pass_rate; ?>%</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-trophy-fill fs-2 text-warning" style="opacity:0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Test-by-Test Performance Table -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-table me-2"></i>Assessment Performance Matrix</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Test Title</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Attempts</th>
                                <th>Avg. Score</th>
                                <th>Pass Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($test_perf_res->num_rows == 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">No test attempt data recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $test_perf_res->fetch_assoc()): ?>
                                    <?php 
                                    $t_pass_rate = ($row['attempts'] > 0) ? round(($row['passed_count'] / $row['attempts']) * 100, 1) : 0;
                                    $t_avg = ($row['attempts'] > 0) ? round($row['avg_score'], 1) : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['subject_name']); ?></span></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['category_name']); ?></span></td>
                                        <td><?php echo $row['attempts']; ?></td>
                                        <td class="fw-semibold text-indigo"><?php echo $t_avg; ?>%</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 5px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo $t_pass_rate; ?>%"></div>
                                                </div>
                                                <span class="small font-weight-bold"><?php echo $t_pass_rate; ?>%</span>
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
    </div>

    <!-- Top 5 Students -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-light py-3">
                <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-star-fill me-2 text-warning"></i>Top Performers</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if ($top_students_res->num_rows == 0): ?>
                        <li class="list-group-item text-center py-5 text-muted">No student scores found yet.</li>
                    <?php else: ?>
                        <?php $r = 1; while ($s = $top_students_res->fetch_assoc()): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 32px; height: 32px; font-weight: 700;">
                                        #<?php echo $r++; ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($s['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($s['email']); ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success font-weight-bold"><?php echo round($s['avg_score'], 1); ?>%</span>
                                    <small class="d-block text-muted" style="font-size: 0.75rem;"><?php echo $s['tests_taken']; ?> Tests</small>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Item Analysis: Hardest Questions -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-3">
                <h6 class="m-0 font-weight-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Item Analysis (Top 5 Hardest Questions)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th>
                                <th>Question Text</th>
                                <th>Total Answers</th>
                                <th>Correct Answers</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($question_analysis_res->num_rows == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No question attempt data yet. Keep gathering student attempts.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($q = $question_analysis_res->fetch_assoc()): ?>
                                    <?php 
                                    $rate = round(($q['correct_answers'] / $q['total_answers']) * 100, 1);
                                    ?>
                                    <tr>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($q['subject_name']); ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($q['question_text']); ?></strong></td>
                                        <td><?php echo $q['total_answers']; ?></td>
                                        <td class="text-success fw-bold"><?php echo $q['correct_answers']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 5px; width: 100px;">
                                                    <div class="progress-bar bg-danger" style="width: <?php echo $rate; ?>%"></div>
                                                </div>
                                                <span class="small text-danger font-weight-bold"><?php echo $rate; ?>%</span>
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
    </div>
</div>

</div><!-- end col-md-10 content -->
</div><!-- end row -->
</div><!-- end container-fluid -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
