<?php
require_once 'forms/db.php';

// Clean up waiting students older than 1 hour
$cleanup_query = "DELETE FROM waiting_students WHERE joined_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$conn->query($cleanup_query);

// Clean up test sessions older than 24 hours
$cleanup_sessions = "DELETE FROM test_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$conn->query($cleanup_sessions);

echo "Cleanup completed successfully.";
?>