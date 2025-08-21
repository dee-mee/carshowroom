<?php
// Authentication Middleware
function authenticate() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    // Remove 'Bearer ' prefix if present
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit();
    }
    
    // In a real app, validate the JWT token here
    // For now, we'll just check if it matches a user's API key
    $user = validateToken($token);
    
    if (!$user) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit();
    }
    
    // Add user to request for handlers to use
    $GLOBALS['user'] = $user;
    return $user;
}

function validateToken($token) {
    global $conn;
    
    // In a real app, use JWT or similar for stateless auth
    // For now, we'll check against a user's API key
    $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE api_key = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Role-based access control
function requireRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    $user = $GLOBALS['user'] ?? null;
    
    if (!$user || !in_array($user['role'], $roles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit();
    }
}

// Generate API key (for user registration or key refresh)
function generateApiKey($userId) {
    global $conn;
    
    $apiKey = bin2hex(random_bytes(32));
    $hashedKey = password_hash($apiKey, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("UPDATE users SET api_key = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedKey, $userId);
    
    if ($stmt->execute()) {
        return $apiKey; // Return the plain key to the user (only shown once)
    }
    
    return null;
}
