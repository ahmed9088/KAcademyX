<?php
// Check if session is already active before starting it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $index = $_POST['index'];
    
    if (isset($_SESSION['mcq_batch']) && isset($_SESSION['mcq_batch'][$index])) {
        $mcq = $_SESSION['mcq_batch'][$index];
        
        echo json_encode([
            'success' => true,
            'mcq' => $mcq
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Question not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>