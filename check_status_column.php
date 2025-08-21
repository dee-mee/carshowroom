<?php
require_once 'config/database.php';

try {
    // Get the column definition
    $stmt = $conn->query("SHOW COLUMNS FROM cars LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Status column definition:\n";
    print_r($column);
    
    // Show current values in the status column
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM cars GROUP BY status");
    echo "\nCurrent status values in the database:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- '{$row['status']}': {$row['count']} records\n";
    }
    
    // Show the ENUM values if it's an ENUM
    if (strpos($column['Type'], 'enum') === 0) {
        preg_match("/^enum\(\'(.*)\'\)$/", str_replace("'", "", $column['Type']), $matches);
        $enum_values = explode(',', $matches[1]);
        echo "\nAllowed ENUM values: " . implode(', ', $enum_values) . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
