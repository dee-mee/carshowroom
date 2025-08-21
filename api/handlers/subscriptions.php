<?php
require_once __DIR__ . '/../middleware/auth.php';

global $conn;

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get subscription by ID
        if (preg_match('/\/subscriptions\/(\d+)/', $path, $matches)) {
            $subscriptionId = $matches[1];
            $user = authenticate();
            
            $sql = "SELECT s.*, p.name as plan_name, p.features, 
                           u1.username as user_name, u1.email as user_email,
                           u2.username as dealer_name, u2.email as dealer_email
                    FROM subscriptions s
                    LEFT JOIN plans p ON s.plan_id = p.id
                    LEFT JOIN users u1 ON s.user_id = u1.id
                    LEFT JOIN users u2 ON s.dealer_id = u2.id
                    WHERE s.id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $subscriptionId);
            $stmt->execute();
            $subscription = $stmt->get_result()->fetch_assoc();
            
            if ($subscription) {
                // Check permissions
                $isAdmin = $user['role'] === 'admin';
                $isOwner = $subscription['user_id'] == $user['id'];
                $isDealer = $subscription['dealer_id'] == $user['id'];
                
                if (!$isAdmin && !$isOwner && !$isDealer) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Not authorized to view this subscription']);
                    exit();
                }
                
                // Parse features JSON
                if (!empty($subscription['features'])) {
                    $subscription['features'] = json_decode($subscription['features'], true);
                }
                
                echo json_encode($subscription);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Subscription not found']);
            }
        } 
        // List subscriptions (filtered by user or dealer)
        else {
            $user = authenticate();
            $queryParams = $_GET;
            
            $where = [];
            $params = [];
            $types = '';
            
            // Apply role-based filtering
            if ($user['role'] === 'user') {
                $where[] = "s.user_id = ?";
                $params[] = $user['id'];
                $types .= 'i';
            } elseif ($user['role'] === 'dealer') {
                $where[] = "s.dealer_id = ?";
                $params[] = $user['id'];
                $types .= 'i';
            }
            
            // Apply filters
            if (!empty($queryParams['status'])) {
                $where[] = "s.status = ?";
                $params[] = $queryParams['status'];
                $types .= 's';
            }
            
            if (!empty($queryParams['plan_id'])) {
                $where[] = "s.plan_id = ?";
                $params[] = $queryParams['plan_id'];
                $types .= 'i';
            }
            
            if (!empty($queryParams['dealer_id'])) {
                if ($user['role'] !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Not authorized to view subscriptions for this dealer']);
                    exit();
                }
                $where[] = "s.dealer_id = ?";
                $params[] = $queryParams['dealer_id'];
                $types .= 'i';
            }
            
            if (!empty($queryParams['user_id'])) {
                if ($user['role'] !== 'admin' && $user['id'] != $queryParams['user_id']) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Not authorized to view subscriptions for this user']);
                    exit();
                }
                $where[] = "s.user_id = ?";
                $params[] = $queryParams['user_id'];
                $types .= 'i';
            }
            
            // Get pagination parameters
            $page = max(1, intval($queryParams['page'] ?? 1));
            $limit = min(50, max(1, intval($queryParams['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;
            
            // Build base query
            $baseSql = "
                SELECT s.*, p.name as plan_name, p.features,
                       u1.username as user_name, u1.email as user_email,
                       u2.username as dealer_name, u2.email as dealer_email
                FROM subscriptions s
                LEFT JOIN plans p ON s.plan_id = p.id
                LEFT JOIN users u1 ON s.user_id = u1.id
                LEFT JOIN users u2 ON s.dealer_id = u2.id
            ";
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM subscriptions s";
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(' AND ', $where);
            }
            
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            // Get subscriptions with pagination
            $sql = $baseSql;
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Add sorting
            $sortField = in_array($queryParams['sort'] ?? '', ['start_date', 'end_date', 'created_at']) 
                       ? $queryParams['sort'] 
                       : 'created_at';
                       
            $sortOrder = in_array(strtoupper($queryParams['order'] ?? ''), ['ASC', 'DESC']) 
                       ? strtoupper($queryParams['order']) 
                       : 'DESC';
            
            $sql .= " ORDER BY s.$sortField $sortOrder LIMIT ? OFFSET ?";
            
            // Execute query
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            if (!empty($params)) {
                $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
            } else {
                $stmt->bind_param('ii', $limit, $offset);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $subscriptions = [];
            
            while ($row = $result->fetch_assoc()) {
                // Parse features JSON
                if (!empty($row['features'])) {
                    $row['features'] = json_decode($row['features'], true);
                }
                $subscriptions[] = $row;
            }
            
            echo json_encode([
                'data' => $subscriptions,
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
        // Create a new subscription
        $user = authenticate();
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['plan_id', 'payment_method'];
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
            exit();
        }
        
        // Get plan details
        $stmt = $conn->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->bind_param("i", $data['plan_id']);
        $stmt->execute();
        $plan = $stmt->get_result()->fetch_assoc();
        
        if (!$plan) {
            http_response_code(404);
            echo json_encode(['error' => 'Plan not found']);
            exit();
        }
        
        // Check if user already has an active subscription for this dealer
        $dealerId = $data['dealer_id'] ?? null;
        
        if ($dealerId) {
            $checkStmt = $conn->prepare("
                SELECT id FROM subscriptions 
                WHERE user_id = ? AND dealer_id = ? 
                AND status IN ('active', 'trial', 'pending') 
                AND (end_date IS NULL OR end_date >= CURDATE())
            ");
            $checkStmt->bind_param("ii", $user['id'], $dealerId);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'You already have an active subscription for this dealer']);
                exit();
            }
        }
        
        // Calculate dates
        $startDate = date('Y-m-d H:i:s');
        $trialEndDate = $plan['trial_days'] > 0 
            ? date('Y-m-d H:i:s', strtotime("+{$plan['trial_days']} days")) 
            : null;
            
        $endDate = $plan['billing_cycle'] === 'monthly'
            ? date('Y-m-d H:i:s', strtotime('+1 month'))
            : ($plan['billing_cycle'] === 'yearly'
                ? date('Y-m-d H:i:s', strtotime('+1 year'))
                : null);
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert subscription
            $stmt = $conn->prepare("
                INSERT INTO subscriptions (
                    user_id, dealer_id, plan_id, start_date, trial_end_date, end_date,
                    status, payment_method, amount, billing_cycle, auto_renew,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, 1, NOW(), NOW())
            ");
            
            $status = $plan['trial_days'] > 0 ? 'trial' : 'active';
            $amount = $plan['trial_days'] > 0 ? 0 : $plan['price'];
            $billingCycle = $plan['billing_cycle'];
            
            $stmt->bind_param(
                "iiisssdsi",
                $user['id'],
                $dealerId,
                $plan['id'],
                $startDate,
                $trialEndDate,
                $endDate,
                $data['payment_method'],
                $amount,
                $billingCycle
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create subscription: " . $stmt->error);
            }
            
            $subscriptionId = $conn->insert_id;
            
            // Create payment record
            $paymentStmt = $conn->prepare("
                INSERT INTO payments (
                    user_id, subscription_id, amount, payment_method, status,
                    transaction_id, created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'completed', UUID(), NOW(), NOW())
            ");
            
            $paymentStmt->bind_param(
                "iids",
                $user['id'],
                $subscriptionId,
                $amount,
                $data['payment_method']
            );
            
            if (!$paymentStmt->execute()) {
                throw new Exception("Failed to record payment: " . $paymentStmt->error);
            }
            
            // Update user role if this is their first subscription
            if ($user['role'] === 'user') {
                $updateUserStmt = $conn->prepare("UPDATE users SET role = 'subscriber' WHERE id = ?");
                $updateUserStmt->bind_param("i", $user['id']);
                $updateUserStmt->execute();
            }
            
            $conn->commit();
            
            // Get the created subscription
            $stmt = $conn->prepare("
                SELECT s.*, p.name as plan_name, p.features,
                       u1.username as user_name, u1.email as user_email,
                       u2.username as dealer_name, u2.email as dealer_email
                FROM subscriptions s
                LEFT JOIN plans p ON s.plan_id = p.id
                LEFT JOIN users u1 ON s.user_id = u1.id
                LEFT JOIN users u2 ON s.dealer_id = u2.id
                WHERE s.id = ?
            ");
            $stmt->bind_param("i", $subscriptionId);
            $stmt->execute();
            $subscription = $stmt->get_result()->fetch_assoc();
            
            if (!empty($subscription['features'])) {
                $subscription['features'] = json_decode($subscription['features'], true);
            }
            
            // TODO: Send confirmation email
            
            http_response_code(201);
            echo json_encode($subscription);
            
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        // Update subscription (admin, dealer, or owner)
        if (preg_match('/\/subscriptions\/(\d+)/', $path, $matches)) {
            $subscriptionId = $matches[1];
            $user = authenticate();
            
            // Get the subscription
            $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE id = ?");
            $stmt->bind_param("i", $subscriptionId);
            $stmt->execute();
            $subscription = $stmt->get_result()->fetch_assoc();
            
            if (!$subscription) {
                http_response_code(404);
                echo json_encode(['error' => 'Subscription not found']);
                exit();
            }
            
            // Check permissions
            $isAdmin = $user['role'] === 'admin';
            $isDealer = $user['role'] === 'dealer' && $subscription['dealer_id'] == $user['id'];
            $isOwner = $subscription['user_id'] == $user['id'];
            
            if (!$isAdmin && !$isDealer && !$isOwner) {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to update this subscription']);
                exit();
            }
            
            // Only allow certain fields to be updated
            $allowedFields = ['status', 'auto_renew', 'notes'];
            if ($isAdmin) {
                $allowedFields = array_merge($allowedFields, ['plan_id', 'start_date', 'end_date', 'trial_end_date', 'amount']);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $updates = [];
            $params = [];
            $types = '';
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                    $types .= is_int($data[$field]) ? 'i' : 's';
                }
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit();
            }
            
            // Build and execute update query
            $sql = "UPDATE subscriptions SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            $params[] = $subscriptionId;
            $types .= 'i';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                // Get updated subscription
                $stmt = $conn->prepare("
                    SELECT s.*, p.name as plan_name, p.features,
                           u1.username as user_name, u1.email as user_email,
                           u2.username as dealer_name, u2.email as dealer_email
                    FROM subscriptions s
                    LEFT JOIN plans p ON s.plan_id = p.id
                    LEFT JOIN users u1 ON s.user_id = u1.id
                    LEFT JOIN users u2 ON s.dealer_id = u2.id
                    WHERE s.id = ?
                ");
                $stmt->bind_param("i", $subscriptionId);
                $stmt->execute();
                $subscription = $stmt->get_result()->fetch_assoc();
                
                if (!empty($subscription['features'])) {
                    $subscription['features'] = json_decode($subscription['features'], true);
                }
                
                // TODO: Send notification if status changed
                
                echo json_encode($subscription);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update subscription']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid subscription ID']);
        }
        break;
        
    case 'DELETE':
        // Cancel subscription (admin, dealer, or owner)
        if (preg_match('/\/subscriptions\/(\d+)/', $path, $matches)) {
            $subscriptionId = $matches[1];
            $user = authenticate();
            
            // Get the subscription
            $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE id = ?");
            $stmt->bind_param("i", $subscriptionId);
            $stmt->execute();
            $subscription = $stmt->get_result()->fetch_assoc();
            
            if (!$subscription) {
                http_response_code(404);
                echo json_encode(['error' => 'Subscription not found']);
                exit();
            }
            
            // Check permissions
            $isAdmin = $user['role'] === 'admin';
            $isDealer = $user['role'] === 'dealer' && $subscription['dealer_id'] == $user['id'];
            $isOwner = $subscription['user_id'] == $user['id'];
            
            if (!$isAdmin && !$isDealer && !$isOwner) {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to cancel this subscription']);
                exit();
            }
            
            // Only allow cancelling active or trial subscriptions
            if (!in_array($subscription['status'], ['active', 'trial'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Only active or trial subscriptions can be cancelled']);
                exit();
            }
            
            // Update subscription status to cancelled
            $stmt = $conn->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled', 
                    cancelled_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $subscriptionId);
            
            if ($stmt->execute()) {
                // TODO: Send cancellation email
                
                echo json_encode(['message' => 'Subscription cancelled successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to cancel subscription']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid subscription ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
