<?php
// api/index.php
// Router for Vercel deployment to route legacy PHP files from the root and subdirectories.

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$normalizedPath = ltrim($path, '/');

// Strip KAcademyX subdirectory prefix if present (Vercel routes compatibility)
if (strpos($normalizedPath, 'KAcademyX/') === 0) {
    $normalizedPath = substr($normalizedPath, 10);
} elseif ($normalizedPath === 'KAcademyX') {
    $normalizedPath = 'index.php';
}

if ($normalizedPath === '') {
    $normalizedPath = 'index.php';
}

$projectRoot = realpath(dirname(__DIR__));
$requestedPath = $projectRoot . '/' . $normalizedPath;

if (is_dir($requestedPath)) {
    $requestedPath = rtrim($requestedPath, '/') . '/index.php';
    $normalizedPath = rtrim($normalizedPath, '/') . '/index.php';
}

$targetFile = realpath($requestedPath);

if (!$targetFile) {
    // Try appending .php (e.g. /about -> about.php)
    $targetFile = realpath($requestedPath . '.php');
    if ($targetFile) {
        $normalizedPath .= '.php';
    }
}

// Security check: ensure path is within project root and is a file
if ($targetFile && strpos($targetFile, $projectRoot) === 0 && is_file($targetFile)) {
    // Set standard environment variables so the routed script knows its context
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    $_SERVER['SCRIPT_NAME'] = '/' . $normalizedPath;
    $_SERVER['PHP_SELF'] = '/' . $normalizedPath;
    
    // Change working directory so relative paths work
    chdir(dirname($targetFile));
    
    require $targetFile;
    exit;
}

// Fallback: If not found, return 404
header("HTTP/1.1 404 Not Found");
echo "404 Not Found: " . htmlspecialchars($path);
