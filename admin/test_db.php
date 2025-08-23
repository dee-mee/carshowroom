<?php
// Test database connection
try {
    require_once __DIR__ . '/../config/database.php';
    
    // Test query
    $stmt = $conn->query("SELECT * FROM header_banner LIMIT 1");
    $result = $stmt->fetch();
    
    echo "<h1>Database Connection Test</h1>";
    echo "<p>Connection successful!</p>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h1>Database Connection Error</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . print_r($e->getTraceAsString(), true) . "</pre>";
}
