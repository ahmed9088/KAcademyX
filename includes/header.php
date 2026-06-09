<?php
// Global navigation header for KAcademyX
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure base URL is defined
if (!isset($baseUrl)) {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $subDir = '';
    if (strpos($projectRoot, $docRoot) === 0) {
        $subDir = substr($projectRoot, strlen($docRoot));
    }
    $subDir = trim($subDir, '/');
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/" . ($subDir ? $subDir . '/' : '');
}

$pageTitle = isset($pageTitle) ? $pageTitle : "Premier Educational Platform";
$activePage = isset($activePage) ? $activePage : "home";

// Global function to resolve images with robust path checking
if (!function_exists('getImagePath')) {
    function getImagePath($path, $defaultPath) {
        global $baseUrl;
        
        if (empty($path)) {
            return $defaultPath;
        }
        
        // If it's already an absolute URL, return it
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // Normalize path separators and trim slashes
        $path = str_replace('\\', '/', $path);
        $relativePath = ltrim($path, '/');
        
        // includes/header.php is located in [root]/includes/header.php.
        // So dirname(__DIR__) gives the absolute path to the root directory.
        $rootDir = dirname(__DIR__) . '/';
        
        // Define possible physical locations for the file on disk relative to the project root
        $possibleLocations = [
            $relativePath,                          // e.g. 'assets/avatars/avatar2.png' or 'Admin/uploads/instructors/file.png'
            'assets/' . $relativePath,              // e.g. 'assets/avatars/avatar2.png' if DB stored 'avatars/avatar2.png'
            'Admin/' . $relativePath,               // e.g. 'Admin/uploads/instructors/file.png' if DB stored 'uploads/instructors/file.png'
            'admin/' . $relativePath,               // case-insensitive match for admin folder
            str_replace('Admin/', 'admin/', $relativePath),
            str_replace('admin/', 'Admin/', $relativePath),
        ];
        
        foreach ($possibleLocations as $loc) {
            $fullPhysicalPath = $rootDir . $loc;
            if (file_exists($fullPhysicalPath) && is_file($fullPhysicalPath)) {
                return $baseUrl . $loc;
            }
        }
        
        return $defaultPath;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo htmlspecialchars($pageTitle); ?> - KAcademyX</title>
  <meta name="description" content="KAcademyX - Premier educational platform offering lectures in Physics, Computer Science, Biology, Mathematics, Career Guidance, and Scholarships">
  <meta name="keywords" content="education, physics, computer science, biology, mathematics, career guidance, scholarships, online learning">
  
  <!-- Favicons -->
  <link rel="icon" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/images/favicon.ico">
  <link rel="apple-touch-icon" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/images/apple-touch-icon.png">
  
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Geist+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  
  <!-- Main CSS File -->
  <link href="assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
  
  <!-- Modern Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">KAcademyX</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link <?php echo $activePage == 'home' ? 'active' : ''; ?>" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $activePage == 'about' ? 'active' : ''; ?>" href="about.php">About</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $activePage == 'lectures' ? 'active' : ''; ?>" href="lectures.php">Lectures</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $activePage == 'kts' ? 'active' : ''; ?>" href="kts.php">KTS</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $activePage == 'instructors' ? 'active' : ''; ?>" href="instructors.php">Instructors</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $activePage == 'resources' ? 'active' : ''; ?>" href="resources.php">Resources</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $activePage == 'tests' ? 'active' : ''; ?>" href="test.php">Tests</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $activePage == 'contact' ? 'active' : ''; ?>" href="contact.php">Contact</a>
          </li>
          <?php if (isset($_SESSION['user'])): ?>
            <?php
            $unread_count = 0;
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                $db_host = getenv('DB_HOST') ?: "localhost";
                $db_user = getenv('DB_USER') ?: "root";
                $db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
                $db_name = getenv('DB_NAME') ?: "kacademyx";
                $db_port = getenv('DB_PORT') ?: 3306;
                if ($db_host !== "localhost" && $db_host !== "127.0.0.1") {
                    $hdr_conn = mysqli_init();
                    if ($hdr_conn) {
                        mysqli_ssl_set($hdr_conn, NULL, NULL, NULL, NULL, NULL);
                        if (!@mysqli_real_connect($hdr_conn, $db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL)) {
                            $hdr_conn = false;
                        }
                    }
                } else {
                    $hdr_conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
                    if ($hdr_conn->connect_error) {
                        $hdr_conn = false;
                    }
                }
                if ($hdr_conn) {
                    $user_id = intval($_SESSION["id"]);
                    $st_res = mysqli_query($hdr_conn, "SELECT id FROM students WHERE user_id = $user_id");
                    if ($st_row = mysqli_fetch_assoc($st_res)) {
                        $student_id = $st_row['id'];
                        $unread_res = mysqli_query($hdr_conn, "SELECT COUNT(*) FROM notifications WHERE student_id = $student_id AND is_read = 0");
                        $unread_count = $unread_res ? mysqli_fetch_row($unread_res)[0] : 0;
                    }
                    mysqli_close($hdr_conn);
                }
            }
            ?>
            <li class="nav-item me-2 d-flex align-items-center">
              <a class="nav-link <?php echo $activePage == 'notifications' ? 'active' : ''; ?> position-relative py-2 px-3 text-muted-hover" href="notifications.php" title="Notifications">
                <i class="bi bi-bell fs-5"></i>
                <?php if ($unread_count > 0): ?>
                  <span class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 0.25em 0.45em; transform: translate(-30%, 20%) !important;">
                    <?php echo $unread_count; ?>
                  </span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user']); ?>
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                <li><a class="dropdown-item" href="my_tests.php"><i class="bi bi-journal-check me-2"></i>My Tests</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="forms/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
              </ul>
            </li>
          <?php endif; ?>
        </ul>
        <?php if (isset($_SESSION['user'])): ?>
          <a class="btn-getstarted ms-3" href="lectures.php">Explore Lectures</a>
        <?php else: ?>
          <a class="btn-getstarted ms-3" href="forms/login.php">Login/Signup</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
