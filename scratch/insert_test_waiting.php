<?php
$host='localhost'; $user='root'; $pass=''; $db='kacademyx';
$conn = new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die('Connect error: '.$conn->connect_error);
$name='Test Student';
$session_id=uniqid('sess_');
$stmt=$conn->prepare('INSERT INTO waiting_students (name, session_id) VALUES (?, ?)');
$stmt->bind_param('ss',$name,$session_id);
if($stmt->execute()) echo "Inserted with session $session_id\n";
else echo "Insert error: ".$conn->error;
$stmt->close();
$res=$conn->query('SELECT * FROM waiting_students');
while($row=$res->fetch_assoc()) echo json_encode($row)."\n";
?>
