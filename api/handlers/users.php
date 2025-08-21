<?php
require_once __DIR__ . '/../middleware/auth.php';

global $conn;

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get user by ID or username
        if (preg_match('/\/users\/(\d+)/', $path, $matches)) {
            $userId = $matches[1];
            $currentUser = authenticate();
            
            // Allow users to view their own profile or admins to view any profile
            if ($currentUser['id'] != $userId && $currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to view this user']);
                exit();
            }
            
            $sql = "SELECT id, username, email, full_name, phone, avatar, role, status, 
                           email_verified_at, phone_verified, created_at, updated_at
                    FROM users 
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if ($user) {
                // Get additional data based on user role
                if ($user['role'] === 'dealer') {
                    $dealerStmt = $conn->prepare("SELECT * FROM dealers WHERE user_id = ?");
                    $dealerStmt->bind_param("i", $userId);
                    $dealerStmt->execute();
                    $dealerData = $dealerStmt->get_result()->fetch_assoc();
                    
                    if ($dealerData) {
                        unset($dealerData['user_id']);
                        $user = array_merge($user, $dealerData);
                    }
                }
                
                echo json_encode($user);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
            }
        } 
        // Get current user profile
        else if (rtrim($path, '/') === '/users/me') {
            $user = authenticate();
            
            // Get additional data based on user role
            if ($user['role'] === 'dealer') {
                $dealerStmt = $conn->prepare("SELECT * FROM dealers WHERE user_id = ?");
                $dealerStmt->bind_param("i", $user['id']);
                $dealerStmt->execute();
                $dealerData = $dealerStmt->get_result()->fetch_assoc();
                
                if ($dealerData) {
                    unset($dealerData['user_id']);
                    $user = array_merge($user, $dealerData);
                }
            }
            
            echo json_encode($user);
        }
        // List users (admin only)
        else {
            $user = authenticate();
            
            if ($user['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Only administrators can list users']);
                exit();
            }
            
            $queryParams = $_GET;
            $where = [];
            $params = [];
            $types = '';
            
            // Apply filters
            if (!empty($queryParams['role'])) {
                $where[] = "role = ?";
                $params[] = $queryParams['role'];
                $types .= 's';
            }
            
            if (!empty($queryParams['status'])) {
                $where[] = "status = ?";
                $params[] = $queryParams['status'];
                $types .= 's';
            }
            
            if (!empty($queryParams['search'])) {
                $search = "%{$queryParams['search']}%";
                $where[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
                array_push($params, $search, $search, $search);
                $types .= 'sss';
            }
            
            // Get pagination parameters
            $page = max(1, intval($queryParams['page'] ?? 1));
            $limit = min(100, max(1, intval($queryParams['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            // Build base query
            $baseSql = "
                SELECT id, username, email, full_name, phone, avatar, role, status, 
                       email_verified_at, phone_verified, created_at, updated_at
                FROM users
            ";
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM users";
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(' AND ', $where);
            }
            
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            // Get users with pagination
            $sql = $baseSql;
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Add sorting
            $sortField = in_array($queryParams['sort'] ?? '', ['username', 'email', 'created_at', 'role']) 
                       ? $queryParams['sort'] 
                       : 'created_at';
                       
            $sortOrder = in_array(strtoupper($queryParams['order'] ?? ''), ['ASC', 'DESC']) 
                       ? strtoupper($queryParams['order']) 
                       : 'DESC';
            
            $sql .= " ORDER BY $sortField $sortOrder LIMIT ? OFFSET ?";
            
            // Execute query
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            if (!empty($params)) {
                $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
            } else {
                $stmt->bind_param('ii', $limit, $offset);
            }
            
            $stmt->execute();
            $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'data' => $users,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
        
    case 'POST':
        // Create new user (admin only)
        $currentUser = authenticate();
        
        if ($currentUser['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Only administrators can create users']);
            exit();
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['username', 'email', 'password', 'role'];
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
            exit();
        }
        
        // Validate role
        $validRoles = ['user', 'dealer', 'admin'];
        if (!in_array($data['role'], $validRoles)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role. Must be one of: ' . implode(', ', $validRoles)]);
            exit();
        }
        
        // Check if username or email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->bind_param("ss", $data['username'], $data['email']);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Username or email already exists']);
            exit();
        }
        
        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO users (
                    username, email, password_hash, full_name, phone, 
                    avatar, role, status, email_verified_at, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW(), NOW())
            ");
            
            $fullName = $data['full_name'] ?? null;
            $phone = $data['phone'] ?? null;
            $avatar = $data['avatar'] ?? null;
            $status = $data['status'] ?? 'active';
            
            $stmt->bind_param(
                "ssssssss",
                $data['username'],
                $data['email'],
                $passwordHash,
                $fullName,
                $phone,
                $avatar,
                $data['role']
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create user: " . $stmt->error);
            }
            
            $userId = $conn->insert_id;
            
            // If role is dealer, create dealer profile
            if ($data['role'] === 'dealer') {
                $dealerStmt = $conn->prepare("
                    INSERT INTO dealers (
                        user_id, business_name, business_email, business_phone, 
                        business_address, city, state, country, postal_code,
                        tax_id, registration_number, website, description,
                        logo, banner, year_established, business_hours,
                        is_verified, is_featured, rating, total_reviews,
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0, NOW(), NOW())
                ");
                
                $businessName = $data['business_name'] ?? $fullName . "'s Dealership";
                $businessEmail = $data['business_email'] ?? $data['email'];
                $businessPhone = $data['business_phone'] ?? $phone;
                $businessAddress = $data['business_address'] ?? null;
                $city = $data['city'] ?? null;
                $state = $data['state'] ?? null;
                $country = $data['country'] ?? null;
                $postalCode = $data['postal_code'] ?? null;
                $taxId = $data['tax_id'] ?? null;
                $registrationNumber = $data['registration_number'] ?? null;
                $website = $data['website'] ?? null;
                $description = $data['description'] ?? null;
                $logo = $data['logo'] ?? null;
                $banner = $data['banner'] ?? null;
                $yearEstablished = $data['year_established'] ?? null;
                $businessHours = $data['business_hours'] ?? null;
                
                $dealerStmt->bind_param(
                    "issssssssssssssss",
                    $userId,
                    $businessName,
                    $businessEmail,
                    $businessPhone,
                    $businessAddress,
                    $city,
                    $state,
                    $country,
                    $postalCode,
                    $taxId,
                    $registrationNumber,
                    $website,
                    $description,
                    $logo,
                    $banner,
                    $yearEstablished,
                    $businessHours
                );
                
                if (!$dealerStmt->execute()) {
                    throw new Exception("Failed to create dealer profile: " . $dealerStmt->error);
                }
            }
            
            $conn->commit();
            
            // Get the created user
            $stmt = $conn->prepare("
                SELECT id, username, email, full_name, phone, avatar, role, status, 
                       email_verified_at, phone_verified, created_at, updated_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            // Add dealer data if applicable
            if ($user['role'] === 'dealer') {
                $dealerStmt = $conn->prepare("SELECT * FROM dealers WHERE user_id = ?");
                $dealerStmt->bind_param("i", $userId);
                $dealerStmt->execute();
                $dealerData = $dealerStmt->get_result()->fetch_assoc();
                
                if ($dealerData) {
                    unset($dealerData['user_id']);
                    $user = array_merge($user, $dealerData);
                }
            }
            
            // TODO: Send welcome email with credentials
            
            http_response_code(201);
            echo json_encode($user);
            
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        // Update user profile
        if (preg_match('/\/users\/(\d+)/', $path, $matches)) {
            $userId = $matches[1];
            $currentUser = authenticate();
            
            // Users can only update their own profile unless they're an admin
            if ($currentUser['id'] != $userId && $currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to update this user']);
                exit();
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update user table
                $userUpdates = [];
                $userParams = [];
                $types = '';
                
                // Fields that can be updated
                $updatableFields = ['full_name', 'phone', 'avatar'];
                if ($currentUser['role'] === 'admin') {
                    $updatableFields = array_merge($updatableFields, ['email', 'role', 'status']);
                }
                
                // Check if password is being updated
                if (!empty($data['password'])) {
                    // Non-admins must provide current password to change it
                    if ($currentUser['role'] !== 'admin') {
                        if (empty($data['current_password'])) {
                            throw new Exception('Current password is required to change password');
                        }
                        
                        // Verify current password
                        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                        $stmt->bind_param("i", $userId);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        
                        if (!password_verify($data['current_password'], $result['password_hash'])) {
                            throw new Exception('Current password is incorrect');
                        }
                    }
                    
                    // Hash new password
                    $userUpdates[] = "password_hash = ?";
                    $userParams[] = password_hash($data['password'], PASSWORD_BCRYPT);
                    $types .= 's';
                }
                
                // Process other updatable fields
                foreach ($updatableFields as $field) {
                    if (isset($data[$field])) {
                        $userUpdates[] = "$field = ?";
                        $userParams[] = $data[$field];
                        $types .= 's';
                    }
                }
                
                // Update user if there are changes
                if (!empty($userUpdates)) {
                    $sql = "UPDATE users SET " . implode(', ', $userUpdates) . ", updated_at = NOW() WHERE id = ?";
                    $userParams[] = $userId;
                    $types .= 'i';
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$userParams);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update user: " . $stmt->error);
                    }
                }
                
                // Update dealer profile if role is dealer
                if ($currentUser['role'] === 'admin' && isset($data['role']) && $data['role'] === 'dealer' || 
                    $currentUser['role'] === 'dealer') {
                    
                    $dealerFields = [
                        'business_name', 'business_email', 'business_phone', 'business_address',
                        'city', 'state', 'country', 'postal_code', 'tax_id', 'registration_number',
                        'website', 'description', 'logo', 'banner', 'year_established', 'business_hours'
                    ];
                    
                    $dealerUpdates = [];
                    $dealerParams = [];
                    $dealerTypes = '';
                    
                    // Check if dealer profile exists
                    $checkStmt = $conn->prepare("SELECT id FROM dealers WHERE user_id = ?");
                    $checkStmt->bind_param("i", $userId);
                    $checkStmt->execute();
                    $dealerExists = $checkStmt->get_result()->num_rows > 0;
                    
                    // Process dealer fields
                    foreach ($dealerFields as $field) {
                        if (isset($data[$field])) {
                            $dealerUpdates[] = "$field = ?";
                            $dealerParams[] = $data[$field];
                            $dealerTypes .= 's';
                        }
                    }
                    
                    // Update or insert dealer profile
                    if (!empty($dealerUpdates)) {
                        if ($dealerExists) {
                            $sql = "UPDATE dealers SET " . implode(', ', $dealerUpdates) . 
                                  ", updated_at = NOW() WHERE user_id = ?";
                            $dealerParams[] = $userId;
                            $dealerTypes .= 'i';
                        } else {
                            $sql = "INSERT INTO dealers (user_id, " . 
                                   implode(', ', array_map(function($u) { 
                                       return explode(' = ', $u)[0]; 
                                   }, $dealerUpdates)) . 
                                   ", created_at, updated_at) VALUES (?, " . 
                                   str_repeat('?, ', count($dealerUpdates)) . "NOW(), NOW())";
                            array_unshift($dealerParams, $userId);
                            $dealerTypes = 'i' . $dealerTypes;
                        }
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param($dealerTypes, ...$dealerParams);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to update dealer profile: " . $stmt->error);
                        }
                    }
                }
                
                $conn->commit();
                
                // Get the updated user
                $stmt = $conn->prepare("
                    SELECT id, username, email, full_name, phone, avatar, role, status, 
                           email_verified_at, phone_verified, created_at, updated_at
                    FROM users 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                
                // Add dealer data if applicable
                if ($user['role'] === 'dealer') {
                    $dealerStmt = $conn->prepare("SELECT * FROM dealers WHERE user_id = ?");
                    $dealerStmt->bind_param("i", $userId);
                    $dealerStmt->execute();
                    $dealerData = $dealerStmt->get_result()->fetch_assoc();
                    
                    if ($dealerData) {
                        unset($dealerData['user_id']);
                        $user = array_merge($user, $dealerData);
                    }
                }
                
                echo json_encode($user);
                
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid user ID']);
        }
        break;
        
    case 'DELETE':
        // Delete user (admin only)
        if (preg_match('/\/users\/(\d+)/', $path, $matches)) {
            $userId = $matches[1];
            $user = authenticate();
            
            if ($user['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Only administrators can delete users']);
                exit();
            }
            
            // Prevent deleting your own account
            if ($user['id'] == $userId) {
                http_response_code(400);
                echo json_encode(['error' => 'You cannot delete your own account']);
                exit();
            }
            
            // Check if user exists
            $checkStmt = $conn->prepare("SELECT id, role FROM users WHERE id = ?");
            $checkStmt->bind_param("i", $userId);
            $checkStmt->execute();
            $userToDelete = $checkStmt->get_result()->fetch_assoc();
            
            if (!$userToDelete) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                exit();
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Delete related data based on user role
                if ($userToDelete['role'] === 'dealer') {
                    // Delete dealer profile
                    $stmt = $conn->prepare("DELETE FROM dealers WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // TODO: Handle reassignment of dealer's cars and other related data
                }
                
                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    $conn->commit();
                    echo json_encode(['message' => 'User deleted successfully']);
                } else {
                    throw new Exception("Failed to delete user");
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid user ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
