<?php
// Test database connection
require_once __DIR__ . '/../config/database.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Test query
    $stmt = $conn->query("SELECT * FROM header_banner LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Database connection successful!</p>";
    echo "<h3>Banner Data:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Show connection details (for debugging, remove in production)
    echo "<h3>Connection Details:</h3>";
    echo "<pre>DB_HOST: " . DB_HOST . "
";
    echo "DB_NAME: " . DB_NAME . "
";
    echo "DB_USER: " . DB_USER . "
";
    echo "DSN: mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4</pre>";
}
