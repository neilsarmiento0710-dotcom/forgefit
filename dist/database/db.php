<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');  // Your MySQL port
define('DB_NAME', 'forgefit_db');  // Change to your database name
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log($e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}
?>