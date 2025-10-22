<?php
// Database credentials. Server with default setting (user 'root' with no password)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'battleart');

/* Attempt to connect to MySQL database */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($mysqli->connect_error) {
    // If the connection fails, stop everything and show an error message.
    die("
        <div style='font-family: sans-serif; border: 2px solid red; padding: 1rem; margin: 1rem;'>
            <h2 style='color: red;'>Database Connection Error</h2>
            <p>Could not connect to the database. Please check your credentials in config.php.</p>
            <p><strong>Error details:</strong> " . htmlspecialchars($mysqli->connect_error) . "</p>
        </div>
    ");
}

?>
