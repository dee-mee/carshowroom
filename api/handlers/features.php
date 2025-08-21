<?php
require_once __DIR__ . '/../middleware/auth.php';

global $conn;

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get all features or filter by category
        $category = $_GET['category'] ?? null;
        $search = $_GET['q'] ?? null;
        
        $where = [];
        $params = [];
        $types = '';
        
        if ($category) {
            $where[] = "category = ?";
            $params[] = $category;
            $types .= 's';
        }
        
        if ($search) {
            $searchTerm = "%$search%";
            $where[] = "(name LIKE ? OR description LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'ss';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM car_features $whereClause ORDER BY category, name";
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $features = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Group by category if no specific category was requested
        if (!$category) {
            $grouped = [];
            foreach ($features as $feature) {
                $grouped[$feature['category'] ?? 'Other'][] = $feature;
            }
            echo json_encode(['data' => $grouped]);
        } else {
            echo json_encode(['data' => $features]);
        }
        break;
        
    case 'POST':
        // Create new feature (admin only)
        $user = authenticate();
        requireRole('admin');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Feature name is required']);
            exit();
        }
        
        $name = $data['name'];
        $category = $data['category'] ?? null;
        $icon = $data['icon'] ?? null;
        $description = $data['description'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO car_features (name, category, icon, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $category, $icon, $description);
        
        if ($stmt->execute()) {
            $featureId = $conn->insert_id;
            http_response_code(201);
            echo json_encode([
                'id' => $featureId,
                'name' => $name,
                'category' => $category,
                'icon' => $icon,
                'description' => $description
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create feature']);
        }
        break;
        
    case 'PUT':
    case 'PATCH':
        // Update feature (admin only)
        if (preg_match('/\/features\/(\d+)/', $path, $matches)) {
            $featureId = $matches[1];
            $user = authenticate();
            requireRole('admin');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $updates = [];
            $params = [];
            $types = '';
            
            $allowedFields = ['name', 'category', 'icon', 'description'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                    $types .= 's';
                }
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit();
            }
            
            $sql = "UPDATE car_features SET " . implode(', ', $updates) . " WHERE id = ?";
            $params[] = $featureId;
            $types .= 'i';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                // Return updated feature
                $stmt = $conn->prepare("SELECT * FROM car_features WHERE id = ?");
                $stmt->bind_param("i", $featureId);
                $stmt->execute();
                $feature = $stmt->get_result()->fetch_assoc();
                
                echo json_encode($feature);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update feature']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid feature ID']);
        }
        break;
        
    case 'DELETE':
        // Delete feature (admin only)
        if (preg_match('/\/features\/(\d+)/', $path, $matches)) {
            $featureId = $matches[1];
            $user = authenticate();
            requireRole('admin');
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // First delete from pivot table
                $stmt = $conn->prepare("DELETE FROM car_feature_pivot WHERE feature_id = ?");
                $stmt->bind_param("i", $featureId);
                $stmt->execute();
                
                // Then delete the feature
                $stmt = $conn->prepare("DELETE FROM car_features WHERE id = ?");
                $stmt->bind_param("i", $featureId);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $conn->commit();
                    echo json_encode(['message' => 'Feature deleted successfully']);
                } else {
                    throw new Exception('Feature not found');
                }
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(404);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid feature ID']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
