<div class="col-md-2 sidebar p-0">
    <div class="d-flex flex-column p-3 text-white">
        <h4 class="mb-4 text-center">KAcademyX</h4>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="../index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="./students/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'students/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-people me-2"></i> Students
                </a>
            </li>
            <li>
                <a href="./instructors/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'instructors/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge me-2"></i> Instructors
                </a>
            </li>
            <li>
                <a href="../courses/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'courses/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-book me-2"></i> Courses
                </a>
            </li>
            <li>
                <a href="../mcq/list.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'mcq/') !== false ? 'active' : ''; ?>">
                    <i class="bi bi-question-circle me-2"></i> MCQs
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="../logout.php" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </div>
</div>