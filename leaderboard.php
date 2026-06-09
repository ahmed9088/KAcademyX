<?php
session_start();
require_once 'forms/db.php';

// Auth guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

$user_id = $_SESSION["id"];

// Filters
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$subject_filter = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$class_filter = isset($_GET['class']) ? trim($_GET['class']) : '';
$month_filter = isset($_GET['month']) ? intval($_GET['month']) : 0;
$year_filter = isset($_GET['year']) ? intval($_GET['year']) : 0;
$test_id_filter = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;

$where = "1=1";
if ($category_filter > 0) $where .= " AND t.category_id = $category_filter";
if ($subject_filter > 0) $where .= " AND t.subject_id = $subject_filter";
if (!empty($class_filter)) $where .= " AND t.class_name = '" . mysqli_real_escape_string($conn, $class_filter) . "'";
if ($month_filter > 0) $where .= " AND MONTH(r.created_at) = $month_filter";
if ($year_filter > 0) $where .= " AND YEAR(r.created_at) = $year_filter";
if ($test_id_filter > 0) $where .= " AND r.test_id = $test_id_filter";

// Fetch leaderboard data
$lead_query = "SELECT r.student_id, st.name as student_name, t.title as test_title, s.name as subject_name,
               tc.name as category_name, MAX(r.percentage) as max_score, MIN(r.time_taken_seconds) as min_time, st.user_id
               FROM results r
               JOIN students st ON r.student_id = st.id
               JOIN tests t ON r.test_id = t.id
               JOIN subjects s ON t.subject_id = s.id
               JOIN test_categories tc ON t.category_id = tc.id
               WHERE $where
               GROUP BY r.student_id, r.test_id
               ORDER BY max_score DESC, min_time ASC
               LIMIT 50";
$lead_res = mysqli_query($conn, $lead_query);

// Fetch subjects, categories, and classes for filters
$subjects_res = mysqli_query($conn, "SELECT * FROM subjects ORDER BY name");
$categories_res = mysqli_query($conn, "SELECT * FROM test_categories ORDER BY id");
$classes_res = mysqli_query($conn, "SELECT DISTINCT class_name FROM tests WHERE class_name IS NOT NULL AND class_name != '' ORDER BY class_name");

$pageTitle = "Global Leaderboards";
$activePage = "tests";
include "includes/header.php";
?>

<div style="height: 100px;"></div>

<main class="container py-4">
    <div class="row mb-4" data-aos="fade-up">
        <div class="col-12 text-center">
            <h1 class="fw-bold text-dark"><i class="bi bi-trophy text-warning me-2"></i>Global Leaderboards</h1>
            <p class="text-muted">Compare your performance against other students in General, Weekly, and Monthly tests.</p>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="card border-0 shadow-sm mb-4" data-aos="fade-up">
        <div class="card-body">
            <form method="GET" action="leaderboard.php" class="row align-items-end g-3">
                <div class="col-md-3">
                    <label for="category" class="form-label small fw-semibold">Test Type</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Test Types</option>
                        <?php while ($c = mysqli_fetch_assoc($categories_res)): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($category_filter == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="subject" class="form-label small fw-semibold">Subject</label>
                    <select class="form-select" id="subject" name="subject">
                        <option value="">All Subjects</option>
                        <?php while ($s = mysqli_fetch_assoc($subjects_res)): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($subject_filter == $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="class" class="form-label small fw-semibold">Class / Grade</label>
                    <select class="form-select" id="class" name="class">
                        <option value="">All Classes</option>
                        <?php while ($cl = mysqli_fetch_assoc($classes_res)): ?>
                            <option value="<?php echo htmlspecialchars($cl['class_name']); ?>" <?php echo ($class_filter == $cl['class_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cl['class_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="month" class="form-label small fw-semibold">Month & Year</label>
                    <div class="d-flex gap-1">
                        <select class="form-select" id="month" name="month">
                            <option value="">Month</option>
                            <?php for($m=1; $m<=12; $m++): ?>
                                <option value="<?php echo $m; ?>" <?php echo ($month_filter == $m) ? 'selected' : ''; ?>>
                                    <?php echo date('M', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select class="form-select" id="year" name="year">
                            <option value="">Year</option>
                            <?php for($y=2026; $y<=2028; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo ($year_filter == $y) ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Apply</button>
                    <a href="leaderboard.php" class="btn btn-secondary w-100"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="card border-0 shadow-sm" data-aos="fade-up">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 80px;">Rank</th>
                            <th>Student Name</th>
                            <th>Test Attempted</th>
                            <th>Subject</th>
                            <th>Score</th>
                            <th>Completion Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($lead_res) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-emoji-neutral fs-2 d-block mb-2 text-warning"></i>
                                    No records found matching these filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $rank = 1; while ($row = mysqli_fetch_assoc($lead_res)): ?>
                                <?php 
                                $is_me = (isset($_SESSION['id']) && $row['user_id'] == $_SESSION['id']); 
                                $row_class = $is_me ? 'table-active border-primary fw-bold' : '';
                                
                                $rank_html = $rank;
                                if ($rank === 1) $rank_html = '🥇';
                                elseif ($rank === 2) $rank_html = '🥈';
                                elseif ($rank === 3) $rank_html = '🥉';
                                
                                $time_formatted = sprintf('%02d:%02d', floor($row['min_time'] / 60), $row['min_time'] % 60);
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td class="ps-4 fw-bold fs-5"><?php echo $rank_html; ?></td>
                                    <td>
                                        <span class="text-dark"><?php echo htmlspecialchars($row['student_name']); ?></span>
                                        <?php if ($is_me): ?>
                                            <span class="badge bg-primary ms-1">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['test_title']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['subject_name']); ?></span></td>
                                    <td class="fw-bold text-indigo fs-5"><?php echo round($row['max_score'], 1); ?>%</td>
                                    <td class="text-muted"><?php echo $time_formatted; ?></td>
                                </tr>
                            <?php $rank++; endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php
include "includes/footer.php";
?>
