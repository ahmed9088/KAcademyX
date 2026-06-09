<?php
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$dbname = getenv('DB_NAME') ?: "kacademyx";
$port = getenv('DB_PORT') ?: 3306;

if ($host !== "localhost" && $host !== "127.0.0.1") {
    $conn = mysqli_init();
    if (!$conn) {
        die("mysqli_init failed");
    }
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    if (!@mysqli_real_connect($conn, $host, $user, $pass, $dbname, $port, NULL, MYSQLI_CLIENT_SSL)) {
        die("Database SSL connection failed: " . mysqli_connect_error());
    }
} else {
    $conn = new mysqli($host, $user, $pass, $dbname, $port);
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
}

// Disable ONLY_FULL_GROUP_BY mode to ensure compatibility with legacy queries
if ($conn) {
    $res = $conn->query("SELECT @@sql_mode as mode");
    if ($res) {
        $row = $res->fetch_assoc();
        $modes = explode(',', $row['mode']);
        $key = array_search('ONLY_FULL_GROUP_BY', $modes);
        if ($key !== false) {
            unset($modes[$key]);
            $new_mode = implode(',', $modes);
            $conn->query("SET SESSION sql_mode = '" . $conn->real_escape_string($new_mode) . "'");
        }
    }
}
?>
