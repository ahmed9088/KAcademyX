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
    <div class="container-fluid">
        <div class="row">