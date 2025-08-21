<?php
require_once __DIR__ . '/../middleware/auth.php';

global $conn;

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get test drive by ID
        if (preg_match('/\/test-drives\/(\d+)/', $path, $matches)) {
            $testDriveId = $matches[1];
            $user = authenticate();
            
            $sql = "SELECT td.*, c.title as car_title, c.make_id, c.model_id,
                           u1.username as customer_name, u1.email as customer_email, u1.phone as customer_phone,
                           u2.username as dealer_name, u2.email as dealer_email, u2.phone as dealer_phone
                    FROM test_drives td
                    LEFT JOIN cars c ON td.car_id = c.id
                    LEFT JOIN users u1 ON td.user_id = u1.id
                    LEFT JOIN users u2 ON td.dealer_id = u2.id
                    WHERE td.id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $testDriveId);
            $stmt->execute();
            $testDrive = $stmt->get_result()->fetch_assoc();
            
            if ($testDrive) {
                // Check permissions
                $isAdmin = $user['role'] === 'admin';
                $isCustomer = $testDrive['user_id'] == $user['id'];
                $isDealer = $testDrive['dealer_id'] == $user['id'];
                
                if (!$isAdmin && !$isCustomer && !$isDealer) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Not authorized to view this test drive']);
                    exit();
                }
                
                echo json_encode($testDrive);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Test drive not found']);
            }
        } 
        // List test drives (filtered by user role)
        else {
            $user = authenticate();
            $queryParams = $_GET;
            
            $where = [];
            $params = [];
            $types = '';
            
            // Apply role-based filtering
            if ($user['role'] === 'user') {
                $where[] = "td.user_id = ?";
                $params[] = $user['id'];
                $types .= 'i';
            } elseif ($user['role'] === 'dealer') {
                $where[] = "td.dealer_id = ?";
                $params[] = $user['id'];
                $types .= 'i';
            }
            
            // Apply filters
            if (!empty($queryParams['status'])) {
                $where[] = "td.status = ?";
                $params[] = $queryParams['status'];
                $types .= 's';
            }
            
            if (!empty($queryParams['car_id'])) {
                $where[] = "td.car_id = ?";
                $params[] = $queryParams['car_id'];
                $types .= 'i';
            }
            
            if (!empty($queryParams['dealer_id'])) {
                $where[] = "td.dealer_id = ?";
                $params[] = $queryParams['dealer_id'];
                $types .= 'i';
            }
            
            if (!empty($queryParams['start_date'])) {
                $where[] = "DATE(td.scheduled_date) >= ?";
                $params[] = $queryParams['start_date'];
                $types .= 's';
            }
            
            if (!empty($queryParams['end_date'])) {
                $where[] = "DATE(td.scheduled_date) <= ?";
                $params[] = $queryParams['end_date'];
                $types .= 's';
            }
            
            // Get pagination parameters
            $page = max(1, intval($queryParams['page'] ?? 1));
            $limit = min(50, max(1, intval($queryParams['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            // Build base query
            $baseSql = "
                SELECT td.*, c.title as car_title, c.make_id, c.model_id,
                       u1.username as customer_name, u1.email as customer_email,
                       u2.username as dealer_name, u2.email as dealer_email
                FROM test_drives td
                LEFT JOIN cars c ON td.car_id = c.id
                LEFT JOIN users u1 ON td.user_id = u1.id
                LEFT JOIN users u2 ON td.dealer_id = u2.id
            ";
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM test_drives td";
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(' AND ', $where);
            }
            
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            // Get test drives with pagination
            $sql = $baseSql;
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Add sorting
            $sort = in_array(strtoupper($queryParams['sort'] ?? ''), ['ASC', 'DESC']) 
                  ? $queryParams['sort'] 
                  : 'DESC';
                  
            $sql .= " ORDER BY td.scheduled_date $sort, td.created_at $sort LIMIT ? OFFSET ?";
            
            // Execute query
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            if (!empty($params)) {
                $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
            } else {
                $stmt->bind_param('ii', $limit, $offset);
            }
            
            $stmt->execute();
            $testDrives = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'data' => $testDrives,
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
        // Schedule a new test drive
        $user = authenticate();
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['car_id', 'scheduled_date'];
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
            exit();
        }
        
        // Get car and dealer info
        $stmt = $conn->prepare("
            SELECT c.*, u.id as dealer_user_id, u.role 
            FROM cars c 
            LEFT JOIN users u ON c.dealer_id = u.id OR (c.dealer_id IS NULL AND c.user_id = u.id)
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $data['car_id']);
        $stmt->execute();
        $car = $stmt->get_result()->fetch_assoc();
        
        if (!$car) {
            http_response_code(404);
            echo json_encode(['error' => 'Car not found']);
            exit();
        }
        
        // Don't allow users to schedule test drives for their own cars
        if (($car['user_id'] == $user['id'] || $car['dealer_user_id'] == $user['id']) && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot schedule a test drive for your own car']);
            exit();
        }
        
        // Validate scheduled date (must be in the future)
        $scheduledDate = new DateTime($data['scheduled_date']);
        $now = new DateTime();
        
        if ($scheduledDate <= $now) {
            http_response_code(400);
            echo json_encode(['error' => 'Scheduled date must be in the future']);
            exit();
        }
        
        // Check for existing test drive at the same time
        $stmt = $conn->prepare("
            SELECT id FROM test_drives 
            WHERE car_id = ? 
            AND DATE(scheduled_date) = DATE(?) 
            AND HOUR(scheduled_date) = HOUR(?) 
            AND status IN ('pending', 'confirmed')
        ");
        
        $dateTime = $data['scheduled_date'];
        $date = date('Y-m-d', strtotime($dateTime));
        $hour = date('H', strtotime($dateTime));
        
        $stmt->bind_param("iss", $data['car_id'], $date, $hour);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'A test drive is already scheduled for this time slot']);
            exit();
        }
        
        // Insert test drive
        $stmt = $conn->prepare("
            INSERT INTO test_drives (
                user_id, car_id, dealer_id, scheduled_date, duration_minutes, 
                status, notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 60, 'pending', ?, NOW(), NOW())
        ");
        
        $dealerId = $car['dealer_id'] ?: $car['user_id'];
        $notes = $data['notes'] ?? '';
        
        $stmt->bind_param(
            "iisss",
            $user['id'],
            $data['car_id'],
            $dealerId,
            $data['scheduled_date'],
            $notes
        );
        
        if ($stmt->execute()) {
            $testDriveId = $conn->insert_id;
            
            // Get the created test drive with related data
            $stmt = $conn->prepare("
                SELECT td.*, c.title as car_title, c.make_id, c.model_id,
                       u1.username as customer_name, u1.email as customer_email,
                       u2.username as dealer_name, u2.email as dealer_email
                FROM test_drives td
                LEFT JOIN cars c ON td.car_id = c.id
                LEFT JOIN users u1 ON td.user_id = u1.id
                LEFT JOIN users u2 ON td.dealer_id = u2.id
                WHERE td.id = ?
            ");
            $stmt->bind_param("i", $testDriveId);
            $stmt->execute();
            $testDrive = $stmt->get_result()->fetch_assoc();
            
            // TODO: Send notification email to dealer
            
            http_response_code(201);
            echo json_encode($testDrive);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to schedule test drive']);
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        // Update test drive status (admin/dealer only)
        if (preg_match('/\/test-drives\/(\d+)/', $path, $matches)) {
            $testDriveId = $matches[1];
            $user = authenticate();
            
            // Only admin or dealer can update test drive status
            if ($user['role'] !== 'admin' && $user['role'] !== 'dealer') {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to update this test drive']);
                exit();
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Only allow updating status and notes
            $updates = [];
            $params = [];
            $types = '';
            
            if (isset($data['status'])) {
                $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
                if (!in_array($data['status'], $validStatuses)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid status']);
                    exit();
                }
                $updates[] = "status = ?";
                $params[] = $data['status'];
                $types .= 's';
                
                // Set completed_at if status is completed
                if ($data['status'] === 'completed') {
                    $updates[] = "completed_at = NOW()";
                }
            }
            
            if (isset($data['notes'])) {
                $updates[] = "notes = ?";
                $params[] = $data['notes'];
                $types .= 's';
            }
            
            if (isset($data['scheduled_date'])) {
                $updates[] = "scheduled_date = ?";
                $params[] = $data['scheduled_date'];
                $types .= 's';
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit();
            }
            
            // Check if user has permission to update this test drive
            $checkStmt = $conn->prepare("SELECT id FROM test_drives WHERE id = ? AND (dealer_id = ? OR ? = 'admin')");
            $isAdmin = $user['role'] === 'admin' ? 1 : 0;
            $checkStmt->bind_param("iii", $testDriveId, $user['id'], $isAdmin);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to update this test drive']);
                exit();
            }
            
            // Build and execute update query
            $sql = "UPDATE test_drives SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $params[] = $testDriveId;
            $types .= 'i';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                // Get updated test drive
                $stmt = $conn->prepare("
                    SELECT td.*, c.title as car_title, c.make_id, c.model_id,
                           u1.username as customer_name, u1.email as customer_email,
                           u2.username as dealer_name, u2.email as dealer_email
                    FROM test_drives td
                    LEFT JOIN cars c ON td.car_id = c.id
                    LEFT JOIN users u1 ON td.user_id = u1.id
                    LEFT JOIN users u2 ON td.dealer_id = u2.id
                    WHERE td.id = ?
                ");
                $stmt->bind_param("i", $testDriveId);
                $stmt->execute();
                $testDrive = $stmt->get_result()->fetch_assoc();
                
                // TODO: Send notification to user if status changed
                
                echo json_encode($testDrive);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update test drive']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid test drive ID']);
        }
        break;
        
    case 'DELETE':
        // Cancel test drive (user, dealer, or admin)
        if (preg_match('/\/test-drives\/(\d+)/', $path, $matches)) {
            $testDriveId = $matches[1];
            $user = authenticate();
            
            // Check if test drive exists and user has permission
            $sql = "SELECT id, user_id, dealer_id, status FROM test_drives WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $testDriveId);
            $stmt->execute();
            $testDrive = $stmt->get_result()->fetch_assoc();
            
            if (!$testDrive) {
                http_response_code(404);
                echo json_encode(['error' => 'Test drive not found']);
                exit();
            }
            
            $isOwner = $testDrive['user_id'] == $user['id'];
            $isDealer = $testDrive['dealer_id'] == $user['id'];
            $isAdmin = $user['role'] === 'admin';
            
            if (!$isOwner && !$isDealer && !$isAdmin) {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to cancel this test drive']);
                exit();
            }
            
            // Only allow cancelling if not already completed or cancelled
            if (in_array($testDrive['status'], ['completed', 'cancelled'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot cancel a ' . $testDrive['status'] . ' test drive']);
                exit();
            }
            
            // Update status to cancelled
            $stmt = $conn->prepare("UPDATE test_drives SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $testDriveId);
            
            if ($stmt->execute()) {
                // TODO: Send cancellation notification to the other party
                
                echo json_encode(['message' => 'Test drive cancelled successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to cancel test drive']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid test drive ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
