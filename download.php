<?php
// download.php
require_once 'forms/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Increment the downloads counter
    $conn->query("UPDATE resources SET downloads = downloads + 1 WHERE id = $id");
    
    // Get the actual download URL
    $res = $conn->query("SELECT download_url FROM resources WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $url = $row['download_url'];
        
        if (!empty($url) && $url !== '#') {
            header("Location: " . $url);
            exit();
        }
    }
}

// Redirect back to resources if URL is missing or placeholder
header("Location: resources.php");
exit();
?>
