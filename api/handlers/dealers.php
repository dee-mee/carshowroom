<?php
require_once __DIR__ . '/../middleware/auth.php';

global $conn;

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get dealer by ID or username
        if (preg_match('/\/dealers\/(\d+)/', $path, $matches)) {
            $dealerId = $matches[1];
            
            $stmt = $conn->prepare("
                SELECT d.*, u.username, u.email, u.phone, u.status, u.created_at 
                FROM dealers d 
                JOIN users u ON d.user_id = u.id 
                WHERE d.user_id = ? AND u.role = 'dealer' AND u.status = 'active'
            ");
            $stmt->bind_param("i", $dealerId);
            $stmt->execute();
            $dealer = $stmt->get_result()->fetch_assoc();
            
            if ($dealer) {
                // Get dealer's cars count
                $stmt = $conn->prepare("SELECT COUNT(*) as total_cars FROM cars WHERE dealer_id = ? AND status = 'published'");
                $stmt->bind_param("i", $dealerId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $dealer['total_cars'] = $result['total_cars'];
                
                // Get average rating
                $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE dealer_id = ? AND status = 'approved'");
                $stmt->bind_param("i", $dealerId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                $dealer['avg_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
                $dealer['total_reviews'] = $result['total_reviews'];
                
                echo json_encode($dealer);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Dealer not found']);
            }
        } 
        // Search dealers
        else {
            $queryParams = $_GET;
            $where = ["u.role = 'dealer' AND u.status = 'active'"];
            $params = [];
            $types = '';
            
            // Build query based on filters
            if (!empty($queryParams['q'])) {
                $search = "%{$queryParams['q']}%";
                $where[] = "(d.business_name LIKE ? OR d.description LIKE ? OR u.city LIKE ?)";
                $params = array_merge($params, [$search, $search, $search]);
                $types .= 'sss';
            }
            
            if (!empty($queryParams['city'])) {
                $where[] = "d.city = ?";
                $params[] = $queryParams['city'];
                $types .= 's';
            }
            
            if (!empty($queryParams['verified'])) {
                $where[] = "d.verified = ?";
                $params[] = 1;
                $types .= 'i';
            }
            
            // Get pagination parameters
            $page = max(1, intval($queryParams['page'] ?? 1));
            $limit = min(20, max(1, intval($queryParams['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            
            // Build base query
            $baseSql = "
                SELECT d.user_id, d.business_name, d.logo, d.city, d.verified, d.rating, 
                       u.created_at, u.status,
                       (SELECT COUNT(*) FROM cars c WHERE c.dealer_id = d.user_id AND c.status = 'published') as total_cars
                FROM dealers d
                JOIN users u ON d.user_id = u.id
            ";
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM dealers d JOIN users u ON d.user_id = u.id";
            if (!empty($where)) {
                $countSql .= " WHERE " . implode(' AND ', $where);
            }
            
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            // Get dealers with pagination
            $sql = $baseSql;
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Add sorting
            $sort = in_array(strtolower($queryParams['sort'] ?? ''), ['newest', 'rating', 'name']) 
                  ? $queryParams['sort'] 
                  : 'newest';
                  
            $orderBy = [
                'newest' => 'u.created_at DESC',
                'rating' => 'd.rating DESC',
                'name' => 'd.business_name ASC'
            ][$sort];
            
            $sql .= " ORDER BY $orderBy LIMIT ? OFFSET ?";
            
            // Execute query
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            if (!empty($params)) {
                $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
            } else {
                $stmt->bind_param('ii', $limit, $offset);
            }
            
            $stmt->execute();
            $dealers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'data' => $dealers,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        // Update dealer profile
        if (preg_match('/\/dealers\/(\d+)/', $path, $matches)) {
            $dealerId = $matches[1];
            $user = authenticate();
            
            // Only the dealer themselves or an admin can update
            if ($user['id'] != $dealerId && $user['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to update this dealer']);
                exit();
            }
            
            // Get request data
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update dealer table
                $dealerFields = [
                    'business_name', 'logo', 'description', 'address', 
                    'city', 'lat', 'lng', 'verified'
                ];
                
                $dealerUpdates = [];
                $dealerParams = [];
                $types = '';
                
                foreach ($dealerFields as $field) {
                    if (isset($data[$field])) {
                        $dealerUpdates[] = "$field = ?";
                        $dealerParams[] = $data[$field];
                        $types .= is_int($data[$field]) ? 'i' : 's';
                    }
                }
                
                if (!empty($dealerUpdates)) {
                    $sql = "UPDATE dealers SET " . implode(', ', $dealerUpdates) . " WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types . 'i', ...array_merge($dealerParams, [$dealerId]));
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update dealer: " . $stmt->error);
                    }
                }
                
                // Update users table (basic info)
                $userFields = ['email', 'phone'];
                $userUpdates = [];
                $userParams = [];
                $types = '';
                
                foreach ($userFields as $field) {
                    if (isset($data[$field])) {
                        $userUpdates[] = "$field = ?";
                        $userParams[] = $data[$field];
                        $types .= 's';
                    }
                }
                
                if (!empty($userUpdates)) {
                    $sql = "UPDATE users SET " . implode(', ', $userUpdates) . " WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types . 'i', ...array_merge($userParams, [$dealerId]));
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update user: " . $stmt->error);
                    }
                }
                
                $conn->commit();
                
                // Get updated dealer data
                $stmt = $conn->prepare("
                    SELECT d.*, u.username, u.email, u.phone, u.status, u.created_at 
                    FROM dealers d 
                    JOIN users u ON d.user_id = u.id 
                    WHERE d.user_id = ?
                ");
                $stmt->bind_param("i", $dealerId);
                $stmt->execute();
                $dealer = $stmt->get_result()->fetch_assoc();
                
                echo json_encode($dealer);
                
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid dealer ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
