<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /carshowroom/login.php");
    exit();
}

// Database connection
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Initialize variables
$success = '';
$error = '';

// Get makes and models for dropdown
try {
    $stmt = $conn->prepare("SELECT id, name FROM makes ORDER BY name");
    $stmt->execute();
    $makes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $makes = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $make_id = $_POST['make_id'] ?? '';
    $model_id = $_POST['model_id'] ?? '';
    $year = $_POST['year'] ?? '';
    $price = $_POST['price'] ?? '';
    $mileage = $_POST['mileage'] ?? '';
    $fuel_type = $_POST['fuel_type'] ?? '';
    $transmission = $_POST['transmission'] ?? '';
    $color = $_POST['color'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    // Force status to 'pending' for non-admin users
    $is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    $allowed_statuses = ['draft', 'pending', 'approved', 'published', 'expired'];
    
    // Only allow admins to set status other than 'pending' or 'draft'
    if ($is_admin) {
        $status = in_array($_POST['status'] ?? 'pending', $allowed_statuses) ? $_POST['status'] : 'pending';
    } else {
        $status = 'pending'; // Regular users can only submit for approval
    }
    
    // Validation
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required';
    if (empty($make_id)) $errors[] = 'Make is required';
    if (empty($model_id)) $errors[] = 'Model is required';
    if (empty($year) || !is_numeric($year)) $errors[] = 'Valid year is required';
    if (!empty($price) && !is_numeric($price)) $errors[] = 'Price must be a number';
    if (!empty($mileage) && !is_numeric($mileage)) $errors[] = 'Mileage must be a number';
    
    if (empty($errors)) {
        try {
            // Generate slug from title
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $slug = preg_replace('/-+/', '-', $slug); // Replace multiple dashes with single dash
            $slug = trim($slug, '-'); // Remove dashes from beginning and end
            
            // Check if slug already exists and make it unique if needed
            $original_slug = $slug;
            $counter = 1;
            while (true) {
                $check_slug = $conn->prepare("SELECT id FROM cars WHERE slug = ?");
                $check_slug->execute([$slug]);
                if (!$check_slug->fetch()) break;
                $slug = $original_slug . '-' . $counter++;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO cars (
                    user_id, title, slug, make_id, model_id, year, price, mileage, 
                    fuel_type, transmission, color, description, featured, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            // Convert empty strings to null for numeric fields
            $price = $price !== '' ? $price : null;
            $mileage = $mileage !== '' ? $mileage : null;
            
            // Ensure model_id is not empty
            if (empty($model_id)) {
                throw new Exception('Please select or add a model');
            }
            
            $stmt->execute([
                $user_id, 
                $title, 
                $slug, 
                $make_id, 
                $model_id, 
                $year, 
                $price, 
                $mileage, 
                $fuel_type, 
                $transmission, 
                $color, 
                $description, 
                $featured, 
                $status
            ]);
            
            if ($status === 'pending') {
                $success = 'Car added successfully and submitted for admin approval!';
            } else {
                $success = 'Car added successfully!';
            }
            
            // Clear form data after success
            $title = $make_id = $model_id = $year = $price = $mileage = '';
            $fuel_type = $transmission = $color = $description = '';
            $featured = 0;
            $status = 'active';
            
        } catch (PDOException $e) {
            $error = 'Error adding car: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Car - Car Showroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
        }

        /* Sidebar Styles - EXACT match to dashboard */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand h4 {
            color: white;
            margin: 0;
            font-weight: 600;
        }

        .sidebar .nav {
            padding: 1rem 0;
        }

        .sidebar .nav-item {
            margin: 0.25rem 0;
        }

        .sidebar .nav-link {
            color: rgb(255, 255, 255);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s;
            border: none;
            background: none;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }

        /* Top Navigation */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: 60px;
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            border-bottom: 1px solid #dee2e6;
            z-index: 999;
            display: flex;
            align-items: center;
            padding: 0 2rem;
            justify-content: space-between;
            color: white;
        }

        .breadcrumb-nav {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .breadcrumb-nav a {
            color: white;
            text-decoration: none;
        }

        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: rgb(255, 255, 255);
        }

        .user-menu {
            display: flex;
            align-items: center;
        }

        #userDropdown {
            color: white !important;
            background: transparent;
            border: none;
        }

        .user-menu .dropdown-toggle {
            color: white !important;
        }

        .user-menu .dropdown-toggle::after {
            color: white !important;
            border-top-color: white !important;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgb(233, 231, 238);
            color: rgb(34, 2, 78);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.5rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            margin-top: 60px;
            padding: 2rem;
            min-height: calc(100vh - 60px);
        }

        /* Form Section */
        .form-section {
            background: white;
            border-radius: 0px;
            padding: 2rem;
            box-shadow: none;
            border: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }

        .form-section h2 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f4;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 0px;
            padding: 0.75rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: rgb(34, 2, 78);
            box-shadow: 0 0 0 0.2rem rgba(34, 2, 78, 0.25);
        }

        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 0px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: rgb(34, 2, 78);
            box-shadow: 0 0 0 0.2rem rgba(34, 2, 78, 0.25);
        }

        .form-check {
            margin-bottom: 1rem;
        }

        .form-check-input {
            border-radius: 0px;
        }

        .form-check-input:checked {
            background-color: rgb(34, 2, 78);
            border-color: rgb(34, 2, 78);
        }

        .btn-primary {
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            border: none;
            border-radius: 0px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: linear-gradient(180deg, rgb(46, 24, 70) 0%, rgb(34, 2, 78) 100%);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 0px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-secondary:hover {
            background: #5a6268;
            color: white;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 0px;
            border: none;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .top-navbar {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .hamburger-menu {
                display: block;
            }
        }

        /* Mobile backdrop */
        .mobile-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .mobile-backdrop.show {
                display: block;
            }
        }

        .row {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Mobile Backdrop -->
    <div class="mobile-backdrop" id="mobileBackdrop"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fas fa-car me-2"></i>CarShowroom</h4>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="add-car.php">
                    <i class="fas fa-plus"></i>
                    <span>Add Car</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage-cars.php">
                    <i class="fas fa-car"></i>
                    <span>Manage Cars</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="featured-cars.php">
                    <i class="fas fa-star"></i>
                    <span>Featured Cars</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="social-links.php">
                    <i class="fas fa-link"></i>
                    <span>Social Links</span>
                </a>
            </li>
            <li class="nav-item mt-4 pt-3" style="border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <a class="nav-link" href="/carshowroom/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Top Navigation -->
    <nav class="top-navbar">
        <div class="d-flex align-items-center">
            <button class="hamburger-menu" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
            </div>
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <?php echo htmlspecialchars($user_name); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/carshowroom/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Car Form -->
        <div class="form-section">
            <h2><i class="fas fa-plus-circle me-2"></i>Add New Car</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <!-- Basic Information -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading me-1"></i>Car Title *
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($title ?? ''); ?>" 
                                   placeholder="e.g., 2015 Toyota Camry LE" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="make_id" class="form-label">
                                <i class="fas fa-industry me-1"></i>Make/Brand *
                            </label>
                            <select class="form-select" id="make_id" name="make_id" required>
                                <option value="">Select Make</option>
                                <?php foreach ($makes as $make): ?>
                                    <option value="<?php echo $make['id']; ?>" 
                                            <?php echo ($make_id ?? '') == $make['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($make['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="model_id" class="form-label mb-0">
                                    <i class="fas fa-car me-1"></i>Model *
                                </label>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="newModelToggle">
                                    <label class="form-check-label small" for="newModelToggle">Add New Model</label>
                                </div>
                            </div>
                            <div id="modelSelectGroup">
                                <select class="form-select" id="model_id" name="model_id" required>
                                    <option value="">Select Model</option>
                                </select>
                            </div>
                            <div id="modelInputGroup" style="display: none;">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="new_model_name" name="new_model_name" placeholder="Enter new model name">
                                    <button class="btn btn-outline-secondary" type="button" id="addModelBtn">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="year" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Year *
                            </label>
                            <input type="number" class="form-control" id="year" name="year" 
                                   value="<?php echo htmlspecialchars($year ?? ''); ?>" 
                                   min="1900" max="<?php echo date('Y') + 1; ?>" 
                                   placeholder="e.g., 2015" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="price" class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>Price
                            </label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?php echo htmlspecialchars($price ?? ''); ?>" 
                                   step="0.01" placeholder="e.g., 15000">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mileage" class="form-label">
                                <i class="fas fa-tachometer-alt me-1"></i>Mileage
                            </label>
                            <input type="number" class="form-control" id="mileage" name="mileage" 
                                   value="<?php echo htmlspecialchars($mileage ?? ''); ?>" 
                                   placeholder="e.g., 75000">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="fuel_type" class="form-label">
                                <i class="fas fa-gas-pump me-1"></i>Fuel Type
                            </label>
                            <select class="form-select" id="fuel_type" name="fuel_type">
                                <option value="">Select Fuel Type</option>
                                <option value="Petrol" <?php echo ($fuel_type ?? '') == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                <option value="Diesel" <?php echo ($fuel_type ?? '') == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                <option value="Hybrid" <?php echo ($fuel_type ?? '') == 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                <option value="Electric" <?php echo ($fuel_type ?? '') == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                                <option value="CNG" <?php echo ($fuel_type ?? '') == 'CNG' ? 'selected' : ''; ?>>CNG</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="transmission" class="form-label">
                                <i class="fas fa-cogs me-1"></i>Transmission
                            </label>
                            <select class="form-select" id="transmission" name="transmission">
                                <option value="">Select Transmission</option>
                                <option value="Manual" <?php echo ($transmission ?? '') == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                <option value="Automatic" <?php echo ($transmission ?? '') == 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                                <option value="Semi-Automatic" <?php echo ($transmission ?? '') == 'Semi-Automatic' ? 'selected' : ''; ?>>Semi-Automatic</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="color" class="form-label">
                                <i class="fas fa-palette me-1"></i>Color
                            </label>
                            <input type="text" class="form-control" id="color" name="color" 
                                   value="<?php echo htmlspecialchars($color ?? ''); ?>" 
                                   placeholder="e.g., White">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">
                        <i class="fas fa-align-left me-1"></i>Description
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="4" 
                              placeholder="Describe the car's features, condition, and any additional details..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>

                <!-- Settings -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status" class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>Status *
                            </label>
                            <select class="form-select" id="status" name="status" <?php echo isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' ? '' : 'disabled'; ?> required>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <option value="draft" <?php echo ($status ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="pending" <?php echo ($status ?? '') == 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                    <option value="approved" <?php echo ($status ?? '') == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="published" <?php echo ($status ?? '') == 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="expired" <?php echo ($status ?? '') == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                <?php else: ?>
                                    <option value="pending" selected>Pending Admin Approval</option>
                                <?php endif; ?>
                            </select>
                            <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                                <input type="hidden" name="status" value="pending">
                                <small class="form-text text-muted">Your submission will be reviewed by an administrator before being published.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="featured" name="featured" 
                                       <?php echo ($featured ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">
                                    <i class="fas fa-star me-1"></i>Mark as Featured Car
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary me-3">
                        <i class="fas fa-save me-1"></i> Add Car
                    </button>
                    <a href="manage-cars.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileBackdrop = document.getElementById('mobileBackdrop');
            
            // Toggle sidebar on mobile
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.toggle('show');
                    mobileBackdrop.classList.toggle('show');
                });
            }
            
            // Close sidebar when clicking backdrop
            mobileBackdrop.addEventListener('click', function() {
                sidebar.classList.remove('show');
                mobileBackdrop.classList.remove('show');
            });
            
            // Close sidebar on window resize if mobile
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('show');
                    mobileBackdrop.classList.remove('show');
                }
            });

            // Toggle between select and input for model
            const modelToggle = document.getElementById('newModelToggle');
            const modelSelectGroup = document.getElementById('modelSelectGroup');
            const modelInputGroup = document.getElementById('modelInputGroup');
            const modelIdInput = document.getElementById('model_id');
            const newModelNameInput = document.getElementById('new_model_name');
            const addModelBtn = document.getElementById('addModelBtn');
            
            if (modelToggle) {
                modelToggle.addEventListener('change', function() {
                    if (this.checked) {
                        modelSelectGroup.style.display = 'none';
                        modelInputGroup.style.display = 'block';
                        modelIdInput.removeAttribute('required');
                        newModelNameInput.setAttribute('required', '');
                    } else {
                        modelSelectGroup.style.display = 'block';
                        modelInputGroup.style.display = 'none';
                        modelIdInput.setAttribute('required', '');
                        newModelNameInput.removeAttribute('required');
                    }
                });
            }
            
            // Handle adding new model
            if (addModelBtn) {
                addModelBtn.addEventListener('click', function() {
                    const makeId = document.getElementById('make_id').value;
                    const modelName = newModelNameInput.value.trim();
                    
                    if (!makeId) {
                        alert('Please select a make first');
                        return;
                    }
                    
                    if (!modelName) {
                        alert('Please enter a model name');
                        return;
                    }
                    
                    // Send request to add new model
                    fetch('add-model.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `make_id=${makeId}&name=${encodeURIComponent(modelName)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Add new model to select and select it
                            const option = document.createElement('option');
                            option.value = data.model_id;
                            option.textContent = modelName;
                            option.selected = true;
                            modelIdInput.innerHTML = '';
                            modelIdInput.appendChild(option);
                            
                            // Switch back to select
                            modelToggle.checked = false;
                            modelSelectGroup.style.display = 'block';
                            modelInputGroup.style.display = 'none';
                            modelIdInput.setAttribute('required', '');
                            newModelNameInput.removeAttribute('required');
                            newModelNameInput.value = '';
                            
                            // Show success message
                            alert('Model added successfully!');
                        } else {
                            alert('Error adding model: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error adding model. Please try again.');
                    });
                });
            }
            
            // Load models when make is selected
            document.getElementById('make_id').addEventListener('change', function() {
                const makeId = this.value;
                const modelSelect = document.getElementById('model_id');
                
                // Clear existing options
                modelSelect.innerHTML = '<option value="">Select Model</option>';
                
                if (makeId) {
                    fetch(`get-models.php?make_id=${makeId}`)
                        .then(response => response.json())
                        .then(data => {
                            data.forEach(model => {
                                const option = document.createElement('option');
                                option.value = model.id;
                                option.textContent = model.name;
                                modelSelect.appendChild(option);
                            });
                        })
                        .catch(error => console.error('Error loading models:', error));
                }
            });
        });
    </script>
</body>
</html>