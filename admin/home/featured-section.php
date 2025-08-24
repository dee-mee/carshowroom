<?php
session_start();

// Check if admin is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Set active page for sidebar
$active_page = 'featured-cars';

// Include database configuration
require_once '../../config/database.php';

// Create featured_cars table if it doesn't exist
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `featured_cars` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `image_url` varchar(500) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `year` int(4) NOT NULL,
            `mileage` varchar(50) NOT NULL,
            `fuel_type` varchar(50) NOT NULL,
            `condition_status` varchar(50) NOT NULL,
            `views` int(11) DEFAULT 0,
            `time_posted` varchar(50) NOT NULL,
            `status` enum('active','inactive') NOT NULL DEFAULT 'active',
            `sort_order` int(11) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Handle form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add' || $action === 'edit') {
            // Get form data
            $title = $_POST['title'] ?? '';
            $price = $_POST['price'] ?? 0;
            
            // Handle image upload
            $image_path = '';
            if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/cars/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $filename = 'featured_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $target_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['car_image']['tmp_name'], $target_path)) {
                        $image_path = 'uploads/cars/' . $filename;
                    } else {
                        throw new Exception('Failed to upload image');
                    }
                } else {
                    throw new Exception('Invalid image format. Only JPG, PNG, GIF, and WebP are allowed.');
                }
            } elseif ($action === 'edit' && isset($_POST['existing_image'])) {
                $image_path = $_POST['existing_image'];
            }
            $year = $_POST['year'] ?? date('Y');
            $mileage = $_POST['mileage'] ?? '';
            $fuel_type = $_POST['fuel_type'] ?? '';
            $condition_status = $_POST['condition_status'] ?? '';
            $views = $_POST['views'] ?? 0;
            $time_posted = $_POST['time_posted'] ?? '';
            $status = $_POST['status'] ?? 'active';
            $sort_order = $_POST['sort_order'] ?? 0;
            $edit_id = $_POST['edit_id'] ?? null;
            
            // Basic validation
            if (empty($title) || empty($image_path) || empty($price)) {
                throw new Exception("Title, image and price are required");
            }
            
            if ($action === 'edit' && $edit_id) {
                // Update existing featured car
                $stmt = $conn->prepare("
                    UPDATE featured_cars 
                    SET title = ?, image_url = ?, price = ?, year = ?, mileage = ?, 
                        fuel_type = ?, condition_status = ?, views = ?, time_posted = ?, 
                        status = ?, sort_order = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $title, $image_path, $price, $year, $mileage, 
                    $fuel_type, $condition_status, $views, $time_posted, 
                    $status, $sort_order, $edit_id
                ]);
                
                $success_message = "Featured car updated successfully!";
            } else {
                // Insert new featured car
                $stmt = $conn->prepare("
                    INSERT INTO featured_cars (title, image_url, price, year, mileage, fuel_type, 
                                             condition_status, views, time_posted, status, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $title, $image_path, $price, $year, $mileage, 
                    $fuel_type, $condition_status, $views, $time_posted, 
                    $status, $sort_order
                ]);
                
                $success_message = "Featured car added successfully!";
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM featured_cars WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success_message = "Featured car deleted successfully!";
    } catch (Exception $e) {
        $error_message = "Error deleting featured car: " . $e->getMessage();
    }
}

// Handle status toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    try {
        $stmt = $conn->prepare("SELECT status FROM featured_cars WHERE id = ?");
        $stmt->execute([$_GET['toggle_status']]);
        $car = $stmt->fetch();
        
        if ($car) {
            $new_status = ($car['status'] === 'active') ? 'inactive' : 'active';
            $stmt = $conn->prepare("UPDATE featured_cars SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $_GET['toggle_status']]);
            $success_message = "Featured car status updated successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get all featured cars
$stmt = $conn->query("
    SELECT * FROM featured_cars 
    ORDER BY sort_order ASC, created_at DESC
");
$featured_cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->query("SELECT 
    COUNT(*) as total_cars,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_cars,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_cars
    FROM featured_cars");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Featured Cars - Admin Panel</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/admin-layout.css">
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f6fa;
    color: #333;
}

/* Sidebar */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg,rgb(34, 2, 78) 0%,rgb(46, 24, 70) 100%);
    color: white;
    overflow-y: auto;
    z-index: 1000;
    transition: all 0.3s ease;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.sidebar-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header h4 {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.sidebar-header small {
    opacity: 0.7;
    font-size: 0.9rem;
}

.nav-menu {
    padding: 20px 0;
}

.nav-item {
    margin: 2px 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.99);
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.nav-link:hover, .nav-link.active {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: #00d4ff;
}

.nav-link i {
    width: 20px;
    margin-right: 15px;
    text-align: center;
    font-size: 1.1rem;
}

.nav-link .dropdown-arrow {
    margin-left: auto;
    transition: transform 0.3s ease;
    font-size: 0.9rem;
}

.nav-link.expanded .dropdown-arrow {
    transform: rotate(90deg);
}

/* Submenu */
.submenu {
    max-height: 0;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.2);
    transition: max-height 0.3s ease;
}

.submenu.show {
    max-height: 500px;
}

.submenu-item {
    display: block;
    padding: 10px 20px 10px 55px;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.submenu-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    padding-left: 60px;
}

/* Main Content */
.main-content {
    margin-left: 280px;
    min-height: 100vh;
    background-color: #f5f6fa;
}

.top-navbar {
    background: linear-gradient(180deg,rgb(34, 2, 78) 0%,rgb(46, 24, 70) 100%);
    padding: 15px 30px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
}

.mobile-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: white;
    cursor: pointer;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.user-dropdown {
    position: relative;
    cursor: pointer;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 180px;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    transition: background 0.3s ease;
}

.dropdown-item:hover {
    background: #f8f9fa;
    color: #333;
}

.dropdown-item.text-danger {
    color: #dc3545 !important;
}

.dropdown-divider {
    height: 1px;
    background: #e9ecef;
    margin: 5px 0;
}

/* Dashboard Content */
.dashboard-content {
    padding: 30px;
}

/* Page Header */
.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-title i {
    color: #6c5ce7;
    font-size: 1.8rem;
}

.breadcrumb {
    margin-top: 8px;
    font-size: 0.9rem;
    color: #6c757d;
}

.breadcrumb a {
    color: #6c5ce7;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    margin: 0 8px;
    color: #adb5bd;
}

/* Stats Cards */
.stats-row {
    display: flex;
    gap: 20px;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    min-width: 200px;
    flex: 1;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1rem;
    opacity: 0.9;
    font-weight: 500;
}

/* Alert Messages */
.alert {
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    border: none;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Cards */
.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: none;
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid #dee2e6;
    padding: 1.25rem 1.5rem;
}

.card-header h5 {
    margin: 0;
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-body {
    padding: 1.5rem;
}

/* Form Styles */
.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #6c5ce7;
    box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
}

.text-danger {
    color: #e74c3c !important;
}

.form-text {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

/* Buttons */
.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
    transform: translateY(-2px);
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
    color: white;
}

.btn-action {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    margin: 0 0.25rem;
}

/* Table Styles */
.table-responsive {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    background: white;
}

.table {
    margin: 0;
    font-size: 0.95rem;
}

.table thead th {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}

.table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.img-thumbnail {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #dee2e6;
}

/* Status Badges */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
    color: white;
}

.status-inactive {
    background: linear-gradient(135deg, #e17055 0%, #d63031 100%);
    color: white;
}

/* Image Preview */
.preview-image {
    max-width: 200px;
    max-height: 150px;
    margin-top: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* No Content State */
.text-center {
    text-align: center;
}

.text-center i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.text-muted {
    color: #6c757d;
}

/* Featured Cars Container */
.featured-cars-container {
    margin-top: 2rem;
}

.featured-cars-container h5 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.mobile-open {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
    }

    .mobile-toggle {
        display: block;
    }

    .dashboard-content {
        padding: 20px;
    }

    .stats-row {
        flex-direction: column;
    }

    .stat-card {
        min-width: auto;
    }

    .user-info span {
        display: none;
    }

    .top-navbar {
        padding: 15px 20px;
    }

    .page-title {
        font-size: 1.5rem;
    }

    .btn {
        padding: 0.6rem 1.2rem;
        font-size: 0.9rem;
    }

    .table-responsive {
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {
    .dashboard-content {
        padding: 15px;
    }

    .card-header,
    .card-body {
        padding: 1rem;
    }

    .stat-number {
        font-size: 2rem;
    }

    .btn-action {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
}
    </style>
</head>
<body>
    <?php require_once '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php require_once '../includes/navbar.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-content" style="padding: 20px;">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-star"></i>
                    Featured Cars Management
                </h1>
                <nav class="breadcrumb">
                    <a href="../dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">></span>
                    <span>Home Management</span>
                    <span class="breadcrumb-separator">></span>
                    <span>Featured Cars</span>
                </nav>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_cars']; ?></div>
                    <div class="stat-label">Total Featured Cars</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_cars']; ?></div>
                    <div class="stat-label">Active Cars</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['inactive_cars']; ?></div>
                    <div class="stat-label">Inactive Cars</div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Add/Edit Featured Car Form -->
            <div class="card">
                <div class="card-header">
                    <h5 id="formTitle"><i class="fas fa-plus me-2"></i>Add New Featured Car</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="featuredCarForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="edit_id" id="editId" value="">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Car Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="car_image" class="form-label">Car Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="car_image" name="car_image" accept="image/*" required>
                                <small class="form-text text-muted">Supported formats: JPG, PNG, GIF, WebP</small>
                                <div id="imagePreview"></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="year" class="form-label">Year</label>
                                <input type="number" class="form-control" id="year" name="year" min="1900" max="2030" value="<?php echo date('Y'); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="mileage" class="form-label">Mileage</label>
                                <input type="text" class="form-control" id="mileage" name="mileage" placeholder="e.g., 12k km">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="fuel_type" class="form-label">Fuel Type</label>
                                <select class="form-select" id="fuel_type" name="fuel_type">
                                    <option value="Petrol">Petrol</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Electric">Electric</option>
                                    <option value="Hybrid">Hybrid</option>
                                    <option value="Gas">Gas</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="condition_status" class="form-label">Condition</label>
                                <select class="form-select" id="condition_status" name="condition_status">
                                    <option value="New">New</option>
                                    <option value="Used">Used</option>
                                    <option value="Certified">Certified</option>
                                    <option value="Premium">Premium</option>
                                    <option value="Rare">Rare</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="views" class="form-label">Views</label>
                                <input type="number" class="form-control" id="views" name="views" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="time_posted" class="form-label">Time Posted</label>
                                <input type="text" class="form-control" id="time_posted" name="time_posted" placeholder="e.g., 3 weeks ago">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                            </div>
                        </div>
                        
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">Reset</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-1"></i> Save Featured Car
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Featured Cars List -->
            <div class="featured-cars-container">
                <h5 class="mb-3"><i class="fas fa-list me-2"></i>Featured Cars List</h5>
                
                <?php if (empty($featured_cars)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-car fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No featured cars found. Add your first featured car using the form above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Price</th>
                                    <th>Year</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                    <th>Sort Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($featured_cars as $car): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($car['image_url']); ?>" 
                                                 alt="Car" class="img-thumbnail" 
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div style="display:none; width:60px; height:40px; background:#6c5ce7; color:white; display:flex; align-items:center; justify-content:center; font-size:10px; border-radius:4px;">Car</div>
                                        </td>
                                        <td><?php echo htmlspecialchars($car['title']); ?></td>
                                        <td>$<?php echo number_format($car['price'], 2); ?></td>
                                        <td><?php echo $car['year']; ?></td>
                                        <td><?php echo htmlspecialchars($car['condition_status']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $car['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($car['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $car['sort_order']; ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-action" onclick="editCar(<?php echo htmlspecialchars(json_encode($car)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="?toggle_status=<?php echo $car['id']; ?>" 
                                               class="btn btn-warning btn-action"
                                               onclick="return confirm('Are you sure you want to change the status?')">
                                                <i class="fas fa-toggle-on"></i> Toggle
                                            </a>
                                            <a href="?delete=<?php echo $car['id']; ?>" 
                                               class="btn btn-danger btn-action"
                                               onclick="return confirm('Are you sure you want to delete this featured car?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Toggle sidebar
            $('.sidebar-toggle').on('click', function() {
                $('.sidebar').toggleClass('collapsed');
                $('.main-content').toggleClass('expanded');
            });
        });

        function editCar(car) {
            // Populate form with car data
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Featured Car';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('editId').value = car.id;
            document.getElementById('title').value = car.title;
            document.getElementById('price').value = car.price;
            document.getElementById('year').value = car.year;
            document.getElementById('mileage').value = car.mileage;
            document.getElementById('fuel_type').value = car.fuel_type;
            document.getElementById('condition_status').value = car.condition_status;
            document.getElementById('views').value = car.views;
            document.getElementById('time_posted').value = car.time_posted;
            document.getElementById('status').value = car.status;
            document.getElementById('sort_order').value = car.sort_order || 0;
            
            // Update image preview
            if (car.image_url) {
                document.getElementById('imagePreview').innerHTML = 
                    `<img src="../../${car.image_url}" class="img-thumbnail preview-image" style="max-width: 200px;">
                     <input type="hidden" name="existing_image" value="${car.image_url}">`;
            }
            
            // Scroll to form
            document.getElementById('featuredCarForm').scrollIntoView({ behavior: 'smooth' });
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this featured car?')) {
                window.location.href = `?delete=${id}`;
            }
        }

        function resetForm() {
            document.getElementById('featuredCarForm').reset();
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Add New Featured Car';
            document.getElementById('formAction').value = 'add';
            document.getElementById('editId').value = '';
            document.getElementById('imagePreview').innerHTML = '';
        }

        // Preview image before upload
        document.getElementById('car_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').innerHTML = 
                        `<img src="${e.target.result}" class="img-thumbnail preview-image" style="max-width: 200px;">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Toggle sidebar
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        }
        function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    const arrow = element.querySelector('.dropdown-arrow');
    
    // Close all other submenus
    document.querySelectorAll('.submenu.show').forEach(menu => {
        if (menu !== submenu) {
            menu.classList.remove('show');
            menu.previousElementSibling.classList.remove('expanded');
        }
    });
    
    // Toggle current submenu
    submenu.classList.toggle('show');
    element.classList.toggle('expanded');
}

function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (userDropdown && !userDropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});
    </script>
</body>
</html>