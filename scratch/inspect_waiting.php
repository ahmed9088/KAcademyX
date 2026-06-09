<?php
$conn = new mysqli('localhost', 'root', '', 'kacademyx');
if ($conn->connect_error) { die('Connection failed: '.$conn->connect_error); }
$res = $conn->query('SELECT * FROM waiting_students');
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo json_encode($row)."\n";
    }
} else { echo 'Error: '.$conn->error; }
?>
