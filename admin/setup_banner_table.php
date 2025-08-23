<?php
require_once '../config/database.php';

try {
    // Create header_banner table
    $sql = "CREATE TABLE IF NOT EXISTS `header_banner` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `background_image` varchar(255) DEFAULT NULL,
        `bottom_image` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    
    // Insert default row if not exists
    $check = $pdo->query("SELECT COUNT(*) FROM `header_banner` WHERE id = 1")->fetchColumn();
    if ($check == 0) {
        $pdo->exec("INSERT INTO `header_banner` (`id`) VALUES (1)");
    }
    
    echo "Banner table created and initialized successfully!";
} catch(PDOException $e) {
    die("ERROR: Could not create table. " . $e->getMessage());
}

// Close connection
unset($pdo);
?>
