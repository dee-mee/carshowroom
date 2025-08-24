<?php
// Start output buffering
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display to prevent output

// Set error log file
ini_set('error_log', __DIR__ . '/contact_errors.log');

// Set JSON content type header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Function to clean all output buffers
function cleanOutput() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

// Function to log errors without output
function logError($message, $data = []) {
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    if (!empty($data)) {
        $logMessage .= 'Data: ' . str_replace("\n", ' ', print_r($data, true)) . "\n";
    }
    $logMessage .= 'Request: ' . str_replace("\n", ' ', print_r($_SERVER, true)) . "\n";
    @file_put_contents(__DIR__ . '/contact_errors.log', $logMessage, FILE_APPEND);
}

// Initialize response array
$response = [
    'success' => false,
    'message' => 'An error occurred. Please try again later.',
    'debug' => []
];

try {
    // Include database configuration
    require_once __DIR__ . '/../../config/database.php';
    
    // Check if database connection is established
    if (!isset($conn) || !($conn instanceof PDO)) {
        throw new Exception('Database connection failed');
    }

    // Ensure database and table exist
    try {
        $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->exec("USE " . DB_NAME);
        
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
        
        error_log("Ensured database and table exist");
    } catch (PDOException $e) {
        logError('Database/table creation failed', ['error' => $e->getMessage()]);
        throw new Exception('Failed to initialize database');
    }

    // Only handle POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    try {
        // Get the raw POST data
        $json = file_get_contents('php://input');
        if ($json === false) {
            throw new Exception('Failed to read input data');
        }
        
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data: ' . json_last_error_msg());
        }

        // Validate required fields
        $required = ['name', 'email', 'subject', 'message'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $response['message'] = 'Missing required fields: ' . implode(', ', $missing);
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        // Prepare data
        $name = trim($data['name']);
        $email = trim($data['email']);
        $subject = trim($data['subject']);
        $phone = !empty($data['phone']) ? trim($data['phone']) : '';
        $message = trim($data['message']);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO contact_messages 
            (name, email, subject, phone, message, ip_address, user_agent)
            VALUES 
            (:name, :email, :subject, :phone, :message, :ip_address, :user_agent)
        ");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->bindParam(':user_agent', $userAgent);

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Failed to save message to database: ' . ($errorInfo[2] ?? 'Unknown error'));
        }

        // Success
        $response['success'] = true;
        $response['message'] = 'Your message has been sent successfully!';
        $response['message_id'] = $conn->lastInsertId();
        
        http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        logError('Contact form error', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        http_response_code(500);
        $response['message'] = 'Failed to process your request. Please try again.';
        $response['debug']['error'] = $e->getMessage();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
} catch (Exception $e) {
    logError('Unexpected error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    cleanOutput();
    http_response_code(500);
    $response['message'] = 'An unexpected error occurred. Please try again later.';
    if (ini_get('display_errors')) {
        $response['debug']['error'] = $e->getMessage();
    }
    echo json_encode($response);
    exit;
}