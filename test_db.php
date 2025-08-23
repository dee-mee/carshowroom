<?php
require_once 'config/database.php';

try {
    // Test database connection
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'header_banner'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create table if it doesn't exist
        $conn->exec("CREATE TABLE IF NOT EXISTS `header_banner` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `background_image` varchar(255) DEFAULT NULL,
            `bottom_image` varchar(255) DEFAULT NULL,
            `title` varchar(255) DEFAULT NULL,
            `subtitle` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Insert a default row
        $conn->exec("INSERT INTO `header_banner` (`id`, `background_image`, `title`, `subtitle`) VALUES (1, '/assets/images/default-banner.jpg', 'Welcome to Car Showroom', 'Find your dream car today')");
    }
    
    // Get banner data
    $stmt = $conn->query("SELECT * FROM header_banner WHERE id = 1");
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Output results
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'banner' => $banner,
        'table_exists' => $tableExists,
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'current_dir' => __DIR__,
        'file_exists' => file_exists(__DIR__ . $banner['background_image'])
    ], JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
