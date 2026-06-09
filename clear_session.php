<?php
session_start();
session_unset();
echo "Session cleared. Redirecting to test.php...";
header("Refresh: 2; url=test.php");
?>
