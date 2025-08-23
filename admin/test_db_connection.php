<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../config/database.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Test connection
    echo "<p>Connecting to database...</p>";
    
    // Test query
    $stmt = $conn->query("SELECT * FROM header_banner WHERE id = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p style='color:green;'>✓ Database connection successful!</p>";
    
    // Show banner data if exists
    if ($result) {
        echo "<h3>Current Banner Data:</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "<p>No banner data found. Attempting to create default record...</p>";
        
        // Create default record
        $stmt = $conn->prepare("INSERT INTO header_banner (id, background_image, bottom_image, created_at, updated_at) 
                              VALUES (1, '', '', NOW(), NOW()) ON DUPLICATE KEY UPDATE id=id");
        if ($stmt->execute()) {
            echo "<p style='color:green;'>✓ Default banner record created successfully!</p>";
        } else {
            echo "<p style='color:red;'>Failed to create default banner record.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Show connection details for debugging
    echo "<h3>Connection Details:</h3>";
    echo "<pre>";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    echo "DSN: mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4\n";
    echo "</pre>";
    
    // Try to create the database if it doesn't exist
    if ($e->getCode() == 1049) { // Database doesn't exist
        try {
            echo "<p>Attempting to create database...</p>";
            $temp_conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
            $temp_conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<p style='color:green;'>✓ Database created successfully!</p>";
            
            // Reconnect to the new database
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            
            // Create the table
            $sql = "CREATE TABLE IF NOT EXISTS `header_banner` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `background_image` varchar(255) DEFAULT NULL,
                `bottom_image` varchar(255) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->exec($sql);
            echo "<p style='color:green;'>✓ Table created successfully!</p>";
            
            // Create default record
            $conn->exec("INSERT INTO `header_banner` (`id`) VALUES (1)");
            echo "<p style='color:green;'>✓ Default record created successfully!</p>";
            
        } catch (PDOException $e2) {
            echo "<p style='color:red;'>Failed to create database: " . htmlspecialchars($e2->getMessage()) . "</p>";
        }
    }
}
?>
