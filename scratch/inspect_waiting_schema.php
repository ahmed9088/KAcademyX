<?php
$conn = new mysqli('localhost','root','', 'kacademyx');
if($conn->connect_error){die('Connect error: '.$conn->connect_error);}
$res = $conn->query('DESCRIBE waiting_students');
if($res){
 while($row=$res->fetch_assoc()){
   echo $row['Field']." | " . $row['Type']." | " . $row['Null']." | " . $row['Key']." | " . $row['Default']." | " . $row['Extra']."\n";
 }
} else { echo 'Error: '.$conn->error; }
?>
