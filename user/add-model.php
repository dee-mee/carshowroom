<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and validate input
$make_id = isset($_POST['make_id']) ? (int)$_POST['make_id'] : 0;
$name = trim($_POST['name'] ?? '');

if ($make_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid make ID']);
    exit();
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Model name cannot be empty']);
    exit();
}

try {
    // Check if make exists
    $stmt = $conn->prepare("SELECT id FROM makes WHERE id = ?");
    $stmt->execute([$make_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Invalid make']);
        exit();
    }
    
    // Check if model already exists for this make
    $stmt = $conn->prepare("SELECT id FROM models WHERE make_id = ? AND LOWER(name) = LOWER(?)");
    $stmt->execute([$make_id, $name]);
    if ($existing = $stmt->fetch()) {
        echo json_encode([
            'success' => true,
            'model_id' => $existing['id'],
            'message' => 'Model already exists'
        ]);
        exit();
    }
    
    // Insert new model
    $stmt = $conn->prepare("INSERT INTO models (make_id, name, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$make_id, $name]);
    
    $model_id = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'model_id' => $model_id,
        'message' => 'Model added successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Error adding model: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
