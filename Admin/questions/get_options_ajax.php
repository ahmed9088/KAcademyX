<?php
include "../db.php";
header('Content-Type: application/json');

if (isset($_GET['question_id'])) {
    $question_id = intval($_GET['question_id']);
    $stmt = $conn->prepare("SELECT option_text, is_correct FROM question_options WHERE question_id = ? ORDER BY id");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }
    $stmt->close();
    echo json_encode($options);
    exit();
}
echo json_encode([]);
?>
