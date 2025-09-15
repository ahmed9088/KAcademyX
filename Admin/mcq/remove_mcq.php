<?php
// Check if session is already active before starting it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $index = $_POST['index'];
    
    if (isset($_SESSION['mcq_batch']) && isset($_SESSION['mcq_batch'][$index])) {
        unset($_SESSION['mcq_batch'][$index]);
        // Reindex array
        $_SESSION['mcq_batch'] = array_values($_SESSION['mcq_batch']);
        
        echo json_encode([
            'success' => true,
            'count' => count($_SESSION['mcq_batch'])
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'count' => isset($_SESSION['mcq_batch']) ? count($_SESSION['mcq_batch']) : 0
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'count' => isset($_SESSION['mcq_batch']) ? count($_SESSION['mcq_batch']) : 0
    ]);
}
?>