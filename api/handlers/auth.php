<?php
require_once __DIR__ . '/../middleware/auth.php';

global $conn;

// Handle different authentication endpoints
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$endpoint = basename($path);

switch ($endpoint) {
    case 'login':
        handleLogin();
        break;
        
    case 'register':
        handleRegister();
        break;
        
    case 'me':
        handleGetCurrentUser();
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
}

function handleLogin() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }
    
    // Find user by email
    $stmt = $conn->prepare("SELECT id, username, email, password_hash, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        return;
    }
    
    // Check if account is active
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Account is not active']);
        return;
    }
    
    // Generate and return API key
    $apiKey = generateApiKey($user['id']);
    
    // Return user data (without sensitive info)
    unset($user['password_hash']);
    
    echo json_encode([
        'user' => $user,
        'token' => $apiKey,
        'expires_in' => 3600 * 24 * 30 // 30 days
    ]);
}

function handleRegister() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['username', 'email', 'password', 'full_name'];
    $missing = array_diff($required, array_keys($data));
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
        return;
    }
    
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $data['username'], $data['email']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        http_response_code(409);
        echo json_encode(['error' => 'Username or email already exists']);
        return;
    }
    
    // Hash password
    $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
    $role = $data['role'] ?? 'user';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", 
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['full_name'],
            $role
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user: " . $stmt->error);
        }
        
        $userId = $conn->insert_id;
        
        // If registering as a dealer, create dealer profile
        if ($role === 'dealer') {
            $businessName = $data['business_name'] ?? $data['full_name'] . "'s Dealership";
            
            $stmt = $conn->prepare("INSERT INTO dealers (user_id, business_name) VALUES (?, ?)");
            $stmt->bind_param("is", $userId, $businessName);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create dealer profile: " . $stmt->error);
            }
        }
        
        // Generate API key
        $apiKey = generateApiKey($userId);
        
        $conn->commit();
        
        // Get the created user
        $stmt = $conn->prepare("SELECT id, username, email, role, status FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        http_response_code(201);
        echo json_encode([
            'user' => $user,
            'token' => $apiKey,
            'expires_in' => 3600 * 24 * 30 // 30 days
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetCurrentUser() {
    // This endpoint requires authentication
    $user = authenticate();
    
    // Get additional user data based on role
    if ($user['role'] === 'dealer') {
        global $conn;
        $stmt = $conn->prepare("SELECT * FROM dealers WHERE user_id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $dealerData = $stmt->get_result()->fetch_assoc();
        
        if ($dealerData) {
            unset($dealerData['user_id']);
            $user = array_merge($user, $dealerData);
        }
    }
    
    echo json_encode(['user' => $user]);
}
