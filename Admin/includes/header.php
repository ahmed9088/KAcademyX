<?php
session_start();
if (!isset($_SESSION['admin']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    // Use absolute path instead of relative path
    header("Location: /KAcademyX/Admin/login.php");
    exit();
}

// Global function to resolve images on the Admin side
if (!function_exists('getImagePath')) {
    function getImagePath($path, $defaultPath) {
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $projectRoot = str_replace('\\', '/', dirname(dirname(__DIR__)));
        $subDir = '';
        if (strpos($projectRoot, $docRoot) === 0) {
            $subDir = substr($projectRoot, strlen($docRoot));
        }
        $subDir = trim($subDir, '/');
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/" . ($subDir ? $subDir . '/' : '');
        
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
        
        // Admin/includes/header.php is located in [root]/Admin/includes/header.php.
        // So dirname(dirname(__DIR__)) gives the absolute path to the root directory.
        $rootDir = dirname(dirname(__DIR__)) . '/';
        
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — KAcademyX Admin' : 'KAcademyX Admin Panel'; ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Vendor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Admin CSS -->
    <link href="/KAcademyX/Admin/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header d-flex d-md-none justify-content-between align-items-center px-3 py-2 bg-dark text-white sticky-top">
        <button class="btn text-white p-0 shadow-none border-0" id="sidebarToggleBtn" type="button" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-3"></i>
        </button>
        <h5 class="mb-0 text-white font-weight-bold" style="font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.5px;">KAcademyX Admin</h5>
        <div class="text-white-50">
            <i class="bi bi-person-circle fs-5"></i>
        </div>
    </div>

    <!-- Sidebar Mobile Backdrop Overlay -->
    <div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        function toggleSidebar() {
            if (sidebar) sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        }
        
        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
        }
        
        if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
    });
    </script>

    <div class="container-fluid">
        <div class="row">