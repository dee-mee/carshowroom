<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/middleware/auth.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

// Route the request
$routes = [
    'auth' => 'auth.php',
    'cars' => 'cars.php',
    'dealers' => 'dealers.php',
    'features' => 'features.php',
    'inquiries' => 'inquiries.php',
    'test-drives' => 'test_drives.php',
    'reviews' => 'reviews.php',
    'subscriptions' => 'subscriptions.php',
    'users' => 'users.php'
];

// Find the matching route
$handler = null;
foreach ($routes as $route => $file) {
    if (strpos($path, "/$route") === 0) {
        $handler = __DIR__ . "/handlers/$file";
        break;
    }
}

// Handle 404
if (!$handler || !file_exists($handler)) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit();
}

// Include the handler
require_once $handler;
