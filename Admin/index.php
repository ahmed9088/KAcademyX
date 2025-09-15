<?php
include "db.php";
include "includes/header.php";
$pageTitle = "Dashboard";
include "includes/sidebar.php";
include "includes/footer.php";

// Fetch stats
$total_students = $conn->query("SELECT COUNT(*) AS count FROM students")->fetch_assoc()['count'];
$total_instructors = $conn->query("SELECT COUNT(*) AS count FROM instructors")->fetch_assoc()['count'];
$total_courses = $conn->query("SELECT COUNT(*) AS count FROM courses")->fetch_assoc()['count'];
$total_mcqs = $conn->query("SELECT COUNT(*) AS count FROM mcq_questions")->fetch_assoc()['count'];
?>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Students</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_students; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people-fill fs-2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Instructors</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_instructors; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-person-badge-fill fs-2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Courses</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_courses; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-book-fill fs-2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">MCQs</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_mcqs; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-question-circle-fill fs-2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Students</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
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
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['name']}</td>
                                        <td>{$row['email']}</td>
                                        <td>".date('M j, Y', strtotime($row['created_at']))."</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Courses</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Instructor</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT courses.*, instructors.name as instructor_name 
                                                  FROM courses 
                                                  LEFT JOIN instructors ON courses.instructor_id = instructors.id 
                                                  ORDER BY courses.created_at DESC LIMIT 5");
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$row['title']}</td>
                                        <td>{$row['instructor_name']}</td>
                                        <td>".date('M j, Y', strtotime($row['created_at']))."</td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>