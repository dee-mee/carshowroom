<?php
// Database configuration for XAMPP
$host = '127.0.0.1'; // Use IP instead of localhost to force TCP/IP
$dbname = 'carlisto_showroom';
$username = 'root';
$password = ''; // XAMPP default has no password

try {
    // Create connection using TCP/IP
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL to create table
    $sql = "CREATE TABLE IF NOT EXISTS `header_banner` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `background_image` varchar(255) DEFAULT NULL,
        `bottom_image` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // Execute the query
    $pdo->exec($sql);
    
    // Insert default row if not exists
    $check = $pdo->query("SELECT COUNT(*) FROM `header_banner` WHERE id = 1")->fetchColumn();
    if ($check == 0) {
        $pdo->exec("INSERT INTO `header_banner` (`id`) VALUES (1)");
        echo "Table created and default row inserted successfully!";
    } else {
        echo "Table already exists and contains data.";
    }
    
} catch(PDOException $e) {
    die("ERROR: Could not create table. " . $e->getMessage());
}

// Close connection
unset($pdo);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Banner Table</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Banner Table Setup</h1>
    <p>Run this script once to create the header_banner table in your database.</p>
    <p>Refresh this page to check if the table was created successfully.</p>
    
    <?php
    // Check if table exists
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $tableExists = $pdo->query("SHOW TABLES LIKE 'header_banner'")->rowCount() > 0;
        
        if ($tableExists) {
            echo '<p class="success">✓ The header_banner table exists in your database.</p>';
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE header_banner");
            echo '<h3>Table Structure:</h3>';
            echo '<table border="1" cellpadding="5" cellspacing="0">';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . $row['Field'] . '</td>';
                echo '<td>' . $row['Type'] . '</td>';
                echo '<td>' . $row['Null'] . '</td>';
                echo '<td>' . $row['Key'] . '</td>';
                echo '<td>' . ($row['Default'] ?? 'NULL') . '</td>';
                echo '<td>' . $row['Extra'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="error">✗ The header_banner table does not exist. Please check your database connection settings and try again.</p>';
        }
    } catch(PDOException $e) {
        echo '<p class="error">Database connection error: ' . $e->getMessage() . '</p>';
    }
    ?>
</body>
</html>
