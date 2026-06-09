<div class="col-md-2 sidebar p-0">
    <div class="d-flex flex-column p-3 text-white">
        <h4 class="mb-4 text-center">KAcademyX</h4>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="/KAcademyX/Admin/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], 'Admin/index.php') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/students/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'students/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-people me-2"></i> Students
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/instructors/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'instructors/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge me-2"></i> Instructors
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/subjects/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'subjects/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-tags me-2"></i> Subjects & Chapters
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/questions/bank.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'questions/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-journal-text me-2"></i> Question Bank
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/tests/manage.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'tests/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-check me-2"></i> Manage Tests
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/notifications/manage.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'notifications/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-bell me-2"></i> Notifications
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/reports/manual_checking.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'manual_checking') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-pen me-2"></i> Exam Grading
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/reports/analytics.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'analytics') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-graph-up-arrow me-2"></i> Reports & Stats
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/videos/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'videos/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-play-btn me-2"></i> Lectures
                </a>
            </li>
            <li>
                <a href="/KAcademyX/Admin/resources/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'resources/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-file-earmark-arrow-down me-2"></i> Resources
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="/KAcademyX/Admin/logout.php" class="d-flex align-items-center text-white text-decoration-none">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </div>
</div>