<?php
// Start the session for managing login state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Default XAMPP username
define('DB_PASSWORD', '');     // Default XAMPP password
define('DB_NAME', 'ecommerce_db'); 

// PDO connection setup
try {
    $dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
    
    // Set PDO to throw exceptions on error for security and debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("ERROR: Could not connect to the database. " . $e->getMessage());
}
?>