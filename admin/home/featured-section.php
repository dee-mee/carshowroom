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
    <title>Featured Cars Management - Car Showroom</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin-layout.css" rel="stylesheet">
    <style>
        /* Page-specific styles */
        .featured-cars-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-yes {
            background-color: #d4edda;
            color: #155724;
        }

        .status-no {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border-radius: 4px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php $active_page = 'home'; ?>
    <?php require_once '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php require_once '../includes/navbar.php'; ?>

        <!-- Page Content -->
        <div class="page-content">
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
                    <div class="table-wrapper">
                        <table class="featured-cars-table">
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
                                                 alt="Car" class="car-image" 
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div style="display:none; width:60px; height:40px; background:#6c5ce7; color:white; display:flex; align-items:center; justify-content:center; font-size:10px; border-radius:4px;">Car</div>
                                        </td>
                                        <td><?php echo htmlspecialchars($car['title']); ?></td>
                                        <td>$<?php echo number_format($car['price'], 2); ?></td>
                                        <td><?php echo $car['year']; ?></td>
                                        <td><?php echo htmlspecialchars($car['condition_status']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $car['status']; ?>">
                                                <?php echo ucfirst($car['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $car['sort_order']; ?></td>
                                        <td>
                                            <button class="action-btn btn-primary" onclick="editCar(<?php echo htmlspecialchars(json_encode($car)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="?toggle_status=<?php echo $car['id']; ?>" 
                                               class="action-btn btn-warning"
                                               onclick="return confirm('Are you sure you want to change the status?')">
                                                <i class="fas fa-toggle-on"></i> Toggle
                                            </a>
                                            <a href="?delete=<?php echo $car['id']; ?>" 
                                               class="action-btn btn-danger"
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

    <script>
        function editCar(car) {
            // Populate form with car data
            document.getElementById('formAction').value = 'edit';
            document.getElementById('editId').value = car.id;
            document.getElementById('title').value = car.title;
            document.getElementById('price').value = car.price;
            
            // Add hidden field for existing image
            let existingImageField = document.getElementById('existing_image');
            if (!existingImageField) {
                existingImageField = document.createElement('input');
                existingImageField.type = 'hidden';
                existingImageField.id = 'existing_image';
                existingImageField.name = 'existing_image';
                document.getElementById('featuredCarForm').appendChild(existingImageField);
            }
            existingImageField.value = car.image_url;
            
            // Make image upload optional for editing
            document.getElementById('car_image').required = false;
            document.getElementById('year').value = car.year;
            document.getElementById('mileage').value = car.mileage;
            document.getElementById('fuel_type').value = car.fuel_type;
            document.getElementById('condition_status').value = car.condition_status;
            document.getElementById('views').value = car.views;
            document.getElementById('time_posted').value = car.time_posted;
            document.getElementById('status').value = car.status;
            document.getElementById('sort_order').value = car.sort_order;
            
            // Update form title and button
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Featured Car';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i> Update Featured Car';
            
            // Scroll to form
            document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('featuredCarForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('editId').value = '';
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Add New Featured Car';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i> Save Featured Car';
        }

    </script>
</body>
</html>