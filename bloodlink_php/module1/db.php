<?php
// BloodLink — Database Connection
define('DB_HOST', '127.0.0.1'); // Changed from 'localhost'
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bloodlink');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
?>
