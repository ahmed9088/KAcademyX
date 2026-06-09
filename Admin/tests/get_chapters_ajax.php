<?php
include "../db.php";
header('Content-Type: application/json');

if (isset($_GET['subject_id'])) {
    $subject_id = intval($_GET['subject_id']);
    $stmt = $conn->prepare("SELECT id, name FROM chapters WHERE subject_id = ? ORDER BY name");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chapters = [];
    while ($row = $result->fetch_assoc()) {
        $chapters[] = $row;
    }
    $stmt->close();
    echo json_encode($chapters);
    exit();
}
echo json_encode([]);
?>
