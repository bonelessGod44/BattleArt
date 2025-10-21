<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'battleart');

// Create connection
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($mysqli->connect_error) {
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// Set timezone to Philippines (UTC+8)
if (!$mysqli->query("SET time_zone = '+08:00'")) {
    error_log("Failed to set timezone: " . $mysqli->error);
}
?>
