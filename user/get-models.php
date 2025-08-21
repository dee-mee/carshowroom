<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['make_id']) || !is_numeric($_GET['make_id'])) {
    echo json_encode([]);
    exit();
}

$make_id = (int)$_GET['make_id'];

try {
    $stmt = $conn->prepare("SELECT id, name FROM models WHERE make_id = ? ORDER BY name");
    $stmt->execute([$make_id]);
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($models);
} catch (PDOException $e) {
    // Log error for debugging
    error_log("Error fetching models: " . $e->getMessage());
    echo json_encode([]);
}
