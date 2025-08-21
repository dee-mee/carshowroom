<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carlisto_showroom');
define('DB_SOCKET', '/opt/lampp/var/mysql/mysql.sock');

// Create PDO connection
try {
    // First try connecting with socket
    $dsn = "mysql:unix_socket=" . DB_SOCKET . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // Try to connect to the database
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Set timezone
    $conn->exec("SET time_zone = '+03:00'");
    
} catch(PDOException $e) {
    // If connection fails, try creating the database
    if ($e->getCode() == 1049) { // Database doesn't exist
        try {
            // Connect without database to create it
            $temp_dsn = "mysql:unix_socket=" . DB_SOCKET;
            $temp_conn = new PDO($temp_dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Create database
            $sql = "CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $temp_conn->exec($sql);
            
            // Now connect to the new database
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Import the database schema
            $schema = file_get_contents(__DIR__ . '/../database/car_showroom_schema.sql');
            $conn->exec($schema);
            
            // Verify users table structure
            $conn->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS name VARCHAR(100) AFTER id");
            
            // Set timezone
            $conn->exec("SET time_zone = '+03:00'");
            
        } catch (PDOException $e2) {
            die("Failed to create database: " . $e2->getMessage());
        }
    } else {
        die("Database connection failed: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
    }
}
?>
