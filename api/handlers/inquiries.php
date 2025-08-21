<?php
require_once __DIR__ . '/../middleware/auth.php';

global $conn;

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get inquiry by ID (admin/dealer/owner only)
        if (preg_match('/\/inquiries\/(\d+)/', $path, $matches)) {
            $inquiryId = $matches[1];
            $user = authenticate();
            
            $sql = "SELECT i.*, c.title as car_title, u1.username as sender_name, u1.email as sender_email,
                           u2.username as recipient_name, u2.email as recipient_email
                    FROM inquiries i
                    LEFT JOIN cars c ON i.car_id = c.id
                    LEFT JOIN users u1 ON i.user_id = u1.id
                    LEFT JOIN users u2 ON i.dealer_id = u2.id
                    WHERE i.id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $inquiryId);
            $stmt->execute();
            $inquiry = $stmt->get_result()->fetch_assoc();
            
            if ($inquiry) {
                // Check permissions
                $isAdmin = $user['role'] === 'admin';
                $isOwner = $inquiry['user_id'] == $user['id'];
                $isRecipient = $inquiry['dealer_id'] == $user['id'] || $inquiry['dealer_id'] === null;
                
                if (!$isAdmin && !$isOwner && !$isRecipient) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Not authorized to view this inquiry']);
                    exit();
                }
                
                // Mark as read if the current user is the recipient
                if (($isRecipient || $isAdmin) && $inquiry['status'] === 'unread') {
                    $updateStmt = $conn->prepare("UPDATE inquiries SET status = 'read', read_at = NOW() WHERE id = ?");
                    $updateStmt->bind_param("i", $inquiryId);
                    $updateStmt->execute();
                    $inquiry['status'] = 'read';
                }
                
                echo json_encode($inquiry);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Inquiry not found']);
            }
        } 
        // List inquiries (filtered by user role)
        else {
            $user = authenticate();
            $queryParams = $_GET;
            
            $where = [];
            $params = [];
            $types = '';
            
            // Apply role-based filtering
            if ($user['role'] === 'user') {
                $where[] = "i.user_id = ?";
                $params[] = $user['id'];
                $types .= 'i';
            } elseif ($user['role'] === 'dealer') {
                $where[] = "(i.dealer_id = ? OR c.user_id = ?)";
                $params[] = $user['id'];
                $params[] = $user['id'];
                $types .= 'ii';
            }
            
            // Apply filters
            if (!empty($queryParams['status'])) {
                $where[] = "i.status = ?";
                $params[] = $queryParams['status'];
                $types .= 's';
            }
            
            if (!empty($queryParams['car_id'])) {
                $where[] = "i.car_id = ?";
                $params[] = $queryParams['car_id'];
                $types .= 'i';
            }
            
            if (!empty($queryParams['type'])) {
                $where[] = "i.type = ?";
                $params[] = $queryParams['type'];
                $types .= 's';
            }
            
            // Get pagination parameters
            $page = max(1, intval($queryParams['page'] ?? 1));
            $limit = min(50, max(1, intval($queryParams['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            // Build base query
            $baseSql = "
                SELECT i.*, c.title as car_title, 
                       u1.username as sender_name, u1.email as sender_email,
                       u2.username as recipient_name, u2.email as recipient_email
                FROM inquiries i
                LEFT JOIN cars c ON i.car_id = c.id
                LEFT JOIN users u1 ON i.user_id = u1.id
                LEFT JOIN users u2 ON i.dealer_id = u2.id
            ";
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM inquiries i";
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(' AND ', $where);
            }
            
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            // Get inquiries with pagination
            $sql = $baseSql;
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Add sorting
            $sort = in_array(strtoupper($queryParams['sort'] ?? ''), ['ASC', 'DESC']) 
                  ? $queryParams['sort'] 
                  : 'DESC';
                  
            $sql .= " ORDER BY i.created_at $sort LIMIT ? OFFSET ?";
            
            // Execute query
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            if (!empty($params)) {
                $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
            } else {
                $stmt->bind_param('ii', $limit, $offset);
            }
            
            $stmt->execute();
            $inquiries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'data' => $inquiries,
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
        // Create new inquiry
        $user = authenticate();
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['car_id', 'message', 'type'];
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
            exit();
        }
        
        // Validate inquiry type
        $validTypes = ['question', 'price', 'availability', 'test_drive', 'other'];
        if (!in_array($data['type'], $validTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid inquiry type']);
            exit();
        }
        
        // Get car and dealer info
        $stmt = $conn->prepare("SELECT user_id, dealer_id FROM cars WHERE id = ?");
        $stmt->bind_param("i", $data['car_id']);
        $stmt->execute();
        $car = $stmt->get_result()->fetch_assoc();
        
        if (!$car) {
            http_response_code(404);
            echo json_encode(['error' => 'Car not found']);
            exit();
        }
        
        // Don't allow users to send inquiries to themselves
        if ($car['user_id'] == $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot send inquiry for your own car']);
            exit();
        }
        
        // Set dealer_id (if car is listed by a dealer)
        $dealerId = $car['dealer_id'] ?: $car['user_id'];
        
        // Insert inquiry
        $stmt = $conn->prepare("
            INSERT INTO inquiries (
                user_id, car_id, dealer_id, type, message, status
            ) VALUES (?, ?, ?, ?, ?, 'unread')
        ");
        
        $stmt->bind_param(
            "iiiss",
            $user['id'],
            $data['car_id'],
            $dealerId,
            $data['type'],
            $data['message']
        );
        
        if ($stmt->execute()) {
            $inquiryId = $conn->insert_id;
            
            // Get the created inquiry with related data
            $stmt = $conn->prepare("
                SELECT i.*, c.title as car_title, 
                       u1.username as sender_name, u1.email as sender_email,
                       u2.username as recipient_name, u2.email as recipient_email
                FROM inquiries i
                LEFT JOIN cars c ON i.car_id = c.id
                LEFT JOIN users u1 ON i.user_id = u1.id
                LEFT JOIN users u2 ON i.dealer_id = u2.id
                WHERE i.id = ?
            ");
            $stmt->bind_param("i", $inquiryId);
            $stmt->execute();
            $inquiry = $stmt->get_result()->fetch_assoc();
            
            // TODO: Send notification email to dealer
            
            http_response_code(201);
            echo json_encode($inquiry);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create inquiry']);
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        // Update inquiry status (admin/dealer only)
        if (preg_match('/\/inquiries\/(\d+)/', $path, $matches)) {
            $inquiryId = $matches[1];
            $user = authenticate();
            
            // Only admin or dealer can update inquiry status
            if ($user['role'] !== 'admin' && $user['role'] !== 'dealer') {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to update this inquiry']);
                exit();
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Only allow updating status and response
            $updates = [];
            $params = [];
            $types = '';
            
            if (isset($data['status'])) {
                $validStatuses = ['unread', 'read', 'pending', 'contacted', 'resolved', 'spam'];
                if (!in_array($data['status'], $validStatuses)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid status']);
                    exit();
                }
                $updates[] = "status = ?";
                $params[] = $data['status'];
                $types .= 's';
                
                // Set resolved_at if status is resolved
                if ($data['status'] === 'resolved') {
                    $updates[] = "resolved_at = NOW()";
                }
            }
            
            if (isset($data['response'])) {
                $updates[] = "response = ?, responded_at = NOW()";
                $params[] = $data['response'];
                $types .= 's';
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit();
            }
            
            // Check if user has permission to update this inquiry
            $checkStmt = $conn->prepare("SELECT id FROM inquiries WHERE id = ? AND (dealer_id = ? OR ? = 'admin')");
            $isAdmin = $user['role'] === 'admin' ? 1 : 0;
            $checkStmt->bind_param("iii", $inquiryId, $user['id'], $isAdmin);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to update this inquiry']);
                exit();
            }
            
            // Build and execute update query
            $sql = "UPDATE inquiries SET " . implode(', ', $updates) . " WHERE id = ?";
            $params[] = $inquiryId;
            $types .= 'i';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                // Get updated inquiry
                $stmt = $conn->prepare("
                    SELECT i.*, c.title as car_title, 
                           u1.username as sender_name, u1.email as sender_email,
                           u2.username as recipient_name, u2.email as recipient_email
                    FROM inquiries i
                    LEFT JOIN cars c ON i.car_id = c.id
                    LEFT JOIN users u1 ON i.user_id = u1.id
                    LEFT JOIN users u2 ON i.dealer_id = u2.id
                    WHERE i.id = ?
                ");
                $stmt->bind_param("i", $inquiryId);
                $stmt->execute();
                $inquiry = $stmt->get_result()->fetch_assoc();
                
                // TODO: Send notification to user if status changed or response was added
                
                echo json_encode($inquiry);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update inquiry']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid inquiry ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
