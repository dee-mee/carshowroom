<?php
require_once 'config/database.php';

try {
    $stmt = $conn->query('SELECT * FROM header_banner WHERE id = 1');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $result,
        'server' => $_SERVER,
        'files' => array_diff(scandir(__DIR__ . '/assets/images/'), ['..', '.']),
        'uploads_dir_exists' => is_dir(__DIR__ . '/uploads/header-banner'),
        'uploads_dir_writable' => is_writable(__DIR__ . '/uploads/header-banner')
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
