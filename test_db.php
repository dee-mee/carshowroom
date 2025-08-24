<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/config/database.php';

try {
    // Test connection
    echo "<h2>Testing Database Connection</h2>";
    
    // Check if connection exists
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception('Database connection failed');
    }
    
    echo "<p>✅ Successfully connected to MySQL server</p>";
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS carlisto_showroom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE carlisto_showroom");
    
    echo "<p>✅ Database 'carlisto_showroom' is ready</p>";
    
    // Create contact_messages table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            ip_address VARCHAR(45),
            user_agent TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "<p>✅ Table 'contact_messages' is ready</p>";
    
    // Test insert
    $testData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'subject' => 'Test Message',
        'phone' => '1234567890',
        'message' => 'This is a test message',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Script'
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO contact_messages 
        (name, email, subject, phone, message, ip_address, user_agent)
        VALUES 
        (:name, :email, :subject, :phone, :message, :ip_address, :user_agent)
    ");
    
    $stmt->execute($testData);
    
    echo "<p>✅ Successfully inserted test data into contact_messages</p>";
    
    // Count messages
    $count = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch()['count'];
    echo "<p>✅ Total messages in database: " . $count . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Error in: " . $e->getFile() . " on line " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
