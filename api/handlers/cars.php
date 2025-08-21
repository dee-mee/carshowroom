<?php
require_once __DIR__ . '/../middleware/auth.php';

// Get database connection
global $conn;

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get car by ID
        if (preg_match('/\/cars\/(\d+)/', $path, $matches)) {
            $carId = $matches[1];
            $stmt = $conn->prepare("SELECT c.*, u.username as seller_username, d.business_name as dealer_name 
                                  FROM cars c 
                                  LEFT JOIN users u ON c.user_id = u.id 
                                  LEFT JOIN dealers d ON c.dealer_id = d.user_id 
                                  WHERE c.id = ?");
            $stmt->bind_param("i", $carId);
            $stmt->execute();
            $result = $stmt->get_result();
            $car = $result->fetch_assoc();
            
            if ($car) {
                // Get photos
                $stmt = $conn->prepare("SELECT * FROM car_photos WHERE car_id = ? ORDER BY sort_order");
                $stmt->bind_param("i", $carId);
                $stmt->execute();
                $car['photos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Get features
                $stmt = $conn->prepare("SELECT cf.* FROM car_features cf 
                                      JOIN car_feature_pivot cfp ON cf.id = cfp.feature_id 
                                      WHERE cfp.car_id = ?");
                $stmt->bind_param("i", $carId);
                $stmt->execute();
                $car['features'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                echo json_encode($car);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Car not found']);
            }
        } 
        // Search cars
        else {
            $queryParams = $_GET;
            $where = [];
            $params = [];
            $types = '';
            
            // Build query based on filters
            if (!empty($queryParams['make_id'])) {
                $where[] = "c.make_id = ?";
                $params[] = $queryParams['make_id'];
                $types .= 'i';
            }
            
            if (!empty($queryParams['model_id'])) {
                $where[] = "c.model_id = ?";
                $params[] = $queryParams['model_id'];
                $types .= 'i';
            }
            
            if (!empty($queryParams['min_price'])) {
                $where[] = "c.price >= ?";
                $params[] = $queryParams['min_price'];
                $types .= 'd';
            }
            
            if (!empty($queryParams['max_price'])) {
                $where[] = "c.price <= ?";
                $params[] = $queryParams['max_price'];
                $types .= 'd';
            }
            
            // Only show published cars to non-admins
            if (!isset($GLOBALS['user']) || $GLOBALS['user']['role'] !== 'admin') {
                $where[] = "c.status = 'published'";
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Get pagination parameters
            $page = max(1, intval($queryParams['page'] ?? 1));
            $limit = min(20, max(1, intval($queryParams['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM cars c $whereClause";
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            
            // Get cars with pagination
            $sql = "SELECT c.*, mk.name as make_name, md.name as model_name, 
                   u.username as seller_username, d.business_name as dealer_name 
                   FROM cars c 
                   JOIN makes mk ON c.make_id = mk.id 
                   JOIN models md ON c.model_id = md.id 
                   LEFT JOIN users u ON c.user_id = u.id 
                   LEFT JOIN dealers d ON c.dealer_id = d.user_id 
                   $whereClause 
                   ORDER BY c.created_at DESC 
                   LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            if (!empty($params)) {
                $stmt->bind_param($types . 'ii', ...array_merge($params, [$limit, $offset]));
            } else {
                $stmt->bind_param('ii', $limit, $offset);
            }
            
            $stmt->execute();
            $cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Add first photo to each car
            foreach ($cars as &$car) {
                $photoStmt = $conn->prepare("SELECT path FROM car_photos WHERE car_id = ? ORDER BY sort_order LIMIT 1");
                $photoStmt->bind_param("i", $car['id']);
                $photoStmt->execute();
                $photo = $photoStmt->get_result()->fetch_assoc();
                $car['photo'] = $photo ? $photo['path'] : null;
            }
            
            echo json_encode([
                'data' => $cars,
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
        // Require authentication
        $user = authenticate();
        
        // Only dealers and admins can create car listings
        if ($user['role'] !== 'dealer' && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Only dealers can create car listings']);
            exit();
        }
        
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['title', 'make_id', 'model_id', 'year', 'price', 'condition', 'transmission', 'fuel_type'];
        $missing = array_diff($required, array_keys($data));
        
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: ' . implode(', ', $missing)]);
            exit();
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert car
            $stmt = $conn->prepare("INSERT INTO cars (
                user_id, dealer_id, title, slug, make_id, model_id, trim_id, year, 
                price, currency, negotiable, mileage, mileage_unit, `condition`, 
                transmission, fuel_type, drivetrain, body_type, color, doors, 
                seats, engine_cc, horsepower, vin, location_city, lat, lng, 
                description, video_url, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Generate slug
            $slug = createSlug($data['title']);
            
            // Set default values
            $dealerId = $user['role'] === 'dealer' ? $user['id'] : null;
            $status = $user['role'] === 'admin' ? ($data['status'] ?? 'published') : 'pending';
            
            $stmt->bind_param(
                "iissiiiidsissssssssiisdsssdsss",
                $user['id'],
                $dealerId,
                $data['title'],
                $slug,
                $data['make_id'],
                $data['model_id'],
                $data['trim_id'] ?? null,
                $data['year'],
                $data['price'],
                $data['currency'] ?? 'USD',
                $data['negotiable'] ?? 0,
                $data['mileage'] ?? null,
                $data['mileage_unit'] ?? 'km',
                $data['condition'],
                $data['transmission'],
                $data['fuel_type'],
                $data['drivetrain'] ?? null,
                $data['body_type'] ?? null,
                $data['color'] ?? null,
                $data['doors'] ?? null,
                $data['seats'] ?? null,
                $data['engine_cc'] ?? null,
                $data['horsepower'] ?? null,
                $data['vin'] ?? null,
                $data['location_city'] ?? null,
                $data['lat'] ?? null,
                $data['lng'] ?? null,
                $data['description'] ?? null,
                $data['video_url'] ?? null,
                $status
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create car listing: " . $stmt->error);
            }
            
            $carId = $conn->insert_id;
            
            // Handle features
            if (!empty($data['features']) && is_array($data['features'])) {
                $featureStmt = $conn->prepare("INSERT INTO car_feature_pivot (car_id, feature_id) VALUES (?, ?)");
                
                foreach ($data['features'] as $featureId) {
                    $featureStmt->bind_param("ii", $carId, $featureId);
                    if (!$featureStmt->execute()) {
                        throw new Exception("Failed to add feature: " . $featureStmt->error);
                    }
                }
            }
            
            // Handle photos (in a real app, you'd upload files here)
            if (!empty($data['photos']) && is_array($data['photos'])) {
                $photoStmt = $conn->prepare("INSERT INTO car_photos (car_id, path, sort_order) VALUES (?, ?, ?)");
                $sortOrder = 0;
                
                foreach ($data['photos'] as $photoPath) {
                    $sortOrder++;
                    $photoStmt->bind_param("isi", $carId, $photoPath, $sortOrder);
                    if (!$photoStmt->execute()) {
                        throw new Exception("Failed to add photo: " . $photoStmt->error);
                    }
                }
            }
            
            $conn->commit();
            
            // Get the created car
            $stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
            $stmt->bind_param("i", $carId);
            $stmt->execute();
            $car = $stmt->get_result()->fetch_assoc();
            
            http_response_code(201);
            echo json_encode($car);
            
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update car (similar to POST but with ID in URL)
        // Implementation omitted for brevity
        http_response_code(501);
        echo json_encode(['error' => 'Not implemented']);
        break;
        
    case 'DELETE':
        // Delete car
        if (preg_match('/\/cars\/(\d+)/', $path, $matches)) {
            $carId = $matches[1];
            $user = authenticate();
            
            // Check if user owns the car or is admin
            $stmt = $conn->prepare("SELECT user_id FROM cars WHERE id = ?");
            $stmt->bind_param("i", $carId);
            $stmt->execute();
            $result = $stmt->get_result();
            $car = $result->fetch_assoc();
            
            if (!$car) {
                http_response_code(404);
                echo json_encode(['error' => 'Car not found']);
                exit();
            }
            
            if ($car['user_id'] !== $user['id'] && $user['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Not authorized to delete this car']);
                exit();
            }
            
            // In a real app, you might want to soft delete
            $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
            $stmt->bind_param("i", $carId);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Car deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete car']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid car ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

// Helper function to create URL-friendly slugs
function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}
