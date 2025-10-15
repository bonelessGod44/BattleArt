<?php
// Database credentials. Server with default setting (user 'root' with no password)
define('DB_SERVER', 'sql308.infinityfree.com');
define('DB_USERNAME', 'if0_40175419');
define('DB_PASSWORD', 'L5KaIeJZne');
define('DB_NAME', 'if0_40175419_battleart');

/* Attempt to connect to MySQL database */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($mysqli === false){
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

$mysqli->query("SET time_zone = '+08:00'");
?>
