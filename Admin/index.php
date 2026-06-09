<?php
include "db.php";
$pageTitle = "Dashboard";
include "includes/header.php";
include "includes/sidebar.php";
include "includes/footer.php";

// Fetch stats
$total_students   = $conn->query("SELECT COUNT(*) AS count FROM students")->fetch_assoc()['count'];
$total_instructors = $conn->query("SELECT COUNT(*) AS count FROM instructors")->fetch_assoc()['count'];
$total_lectures    = $conn->query("SELECT COUNT(*) AS count FROM youtube_videos")->fetch_assoc()['count'];
$total_mcqs        = $conn->query("SELECT COUNT(*) AS count FROM mcq_questions")->fetch_assoc()['count'];

// Fetch exam system stats
$total_tests = $conn->query("SELECT COUNT(*) AS count FROM tests")->fetch_assoc()['count'];
$total_attempts = $conn->query("SELECT COUNT(*) AS count FROM student_attempts")->fetch_assoc()['count'];
$avg_score_res = $conn->query("SELECT AVG(percentage) AS avg FROM results");
$avg_score = ($avg_score_res) ? round($avg_score_res->fetch_assoc()['avg'] ?? 0, 1) : 0;
$pass_count = $conn->query("SELECT COUNT(*) AS count FROM results WHERE is_passed = 1")->fetch_assoc()['count'];
$total_results = $conn->query("SELECT COUNT(*) AS count FROM results")->fetch_assoc()['count'];
$pass_rate = ($total_results > 0) ? round(($pass_count / $total_results) * 100, 1) : 0;
?>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-primary stat-card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_students; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people-fill fs-2 text-primary" style="opacity:0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-success stat-card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Instructors</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_instructors; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-person-badge-fill fs-2 text-success" style="opacity:0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-info stat-card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Lectures</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_lectures; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-play-btn-fill fs-2 text-info" style="opacity:0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-warning stat-card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">MCQs (Scraped)</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_mcqs; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-question-circle-fill fs-2 text-warning" style="opacity:0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Exam System Stats Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-indigo stat-card h-100" style="border-left: 0.25rem solid #6610f2 !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-indigo text-uppercase mb-1" style="color: #6610f2;">Active Assessments</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_tests; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-file-earmark-check fs-2 text-indigo" style="opacity:0.2; color: #6610f2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-teal stat-card h-100" style="border-left: 0.25rem solid #20c997 !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-teal text-uppercase mb-1" style="color: #20c997;">Total Attempts</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $total_attempts; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-clipboard2-data fs-2 text-teal" style="opacity:0.2; color: #20c997;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-purple stat-card h-100" style="border-left: 0.25rem solid #6f42c1 !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-purple text-uppercase mb-1" style="color: #6f42c1;">Avg Exam Score</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $avg_score; ?>%</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-percent fs-2 text-purple" style="opacity:0.2; color: #6f42c1;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-left-pink stat-card h-100" style="border-left: 0.25rem solid #d63384 !important;">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-pink text-uppercase mb-1" style="color: #d63384;">Exam Pass Rate</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $pass_rate; ?>%</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-trophy fs-2 text-pink" style="opacity:0.2; color: #d63384;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6><i class="bi bi-people me-2"></i>Recent Students</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 5");
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td><strong>" . htmlspecialchars($row['name']) . "</strong></td>
                                            <td class='text-muted'>" . htmlspecialchars($row['email']) . "</td>
                                            <td><span class='badge bg-light text-dark'>" . date('M j, Y', strtotime($row['created_at'])) . "</span></td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center text-muted py-4'>No students found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6><i class="bi bi-play-btn me-2"></i>Recent Lectures</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Published</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT * FROM youtube_videos ORDER BY published_at DESC LIMIT 5");
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td><strong>" . htmlspecialchars(substr($row['title'], 0, 40)) . "...</strong></td>
                                            <td><span class='badge bg-primary bg-opacity-10 text-primary'>" . htmlspecialchars($row['category']) . "</span></td>
                                            <td><span class='badge bg-light text-dark'>" . date('M j, Y', strtotime($row['published_at'])) . "</span></td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center text-muted py-4'>No lectures found.</td></tr>";
                            }
                            ?>
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