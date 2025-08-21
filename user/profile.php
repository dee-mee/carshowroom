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

// Get user profile information
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header("Location: /carshowroom/logout.php");
        exit();
    }
} catch (PDOException $e) {
    $error = 'Error loading profile: ' . $e->getMessage();
    $user = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        // Validation
        $errors = [];
        if (empty($full_name)) $errors[] = 'Full name is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
        
        // Check if email exists for other users
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $errors[] = 'Email already exists';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error';
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    UPDATE users SET 
                    full_name = ?, email = ?, phone = ?, address = ?, 
                    city = ?, state = ?, country = ?, bio = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $full_name, $email, $phone, $address, 
                    $city, $state, $country, $bio, $user_id
                ]);
                
                // Update session
                $_SESSION['user_name'] = $full_name;
                $user_name = $full_name;
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $success = 'Profile updated successfully!';
                
            } catch (PDOException $e) {
                $error = 'Error updating profile: ' . $e->getMessage();
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
    
    elseif ($action == 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        $errors = [];
        if (empty($current_password)) $errors[] = 'Current password is required';
        if (empty($new_password)) $errors[] = 'New password is required';
        if (strlen($new_password) < 6) $errors[] = 'New password must be at least 6 characters';
        if ($new_password !== $confirm_password) $errors[] = 'Passwords do not match';
        
        if (empty($errors)) {
            try {
                // Verify current password
                if (password_verify($current_password, $user['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Current password is incorrect';
                }
            } catch (PDOException $e) {
                $error = 'Error changing password: ' . $e->getMessage();
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Get user statistics
try {
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_cars,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_cars,
            COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_cars
        FROM cars WHERE user_id = ?
    ");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total_cars' => 0, 'active_cars' => 0, 'featured_cars' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Car Showroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
        }

        /* Sidebar Styles - EXACT match to add-car.php */
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
            background-color: #f8f9fa;
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 0px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #dee2e6;
        }

        .page-header h1 {
            color: #495057;
            font-weight: 600;
            margin: 0;
            font-size: 2rem;
        }

        .breadcrumb-text {
            color: #6c757d;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .breadcrumb-text a {
            color: rgb(34, 2, 78);
            text-decoration: none;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 0px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            flex-shrink: 0;
        }

        .profile-info h2 {
            color: #495057;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
        }

        .profile-info .text-muted {
            margin-bottom: 1rem;
        }

        .profile-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: rgb(34, 2, 78);
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Tabs */
        .profile-tabs {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0px;
        }

        .nav-tabs {
            border-bottom: 2px solid #f1f3f4;
            background: #f8f9fa;
            padding: 0 2rem;
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 0;
            color: #6c757d;
            font-weight: 600;
            padding: 1rem 1.5rem;
            margin-right: 1rem;
        }

        .nav-tabs .nav-link.active {
            color: rgb(34, 2, 78);
            background: white;
            border-bottom: 3px solid rgb(34, 2, 78);
        }

        .tab-content {
            padding: 2rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: rgb(34, 2, 78);
            box-shadow: 0 0 0 0.2rem rgba(34, 2, 78, 0.25);
        }

        .btn-primary {
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            border: none;
            border-radius: 8px;
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
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }

        /* Alerts */
        .alert {
            border-radius: 8px;
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

        /* Password Form */
        .password-form {
            max-width: 500px;
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
                padding: 1rem;
            }

            .hamburger-menu {
                display: block;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
            }

            .tab-content {
                padding: 1rem;
            }
        }

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
                <a class="nav-link" href="add-car.php">
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
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-user me-2"></i>Profile</h1>
            <div class="breadcrumb-text">
                <a href="dashboard.php">Dashboard</a> > Profile
            </div>
        </div>

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

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['full_name'] ?? 'Unknown User'); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_cars']; ?></div>
                        <div class="stat-label">Total Cars</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['active_cars']; ?></div>
                        <div class="stat-label">Active Cars</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['featured_cars']; ?></div>
                        <div class="stat-label">Featured Cars</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Tabs -->
        <div class="profile-tabs">
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-info" type="button" role="tab">
                        <i class="fas fa-user me-2"></i>Profile Information
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-lock me-2"></i>Security
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="profileTabsContent">
                <!-- Profile Information Tab -->
                <div class="tab-pane fade show active" id="profile-info" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name" class="form-label">
                                        <i class="fas fa-user me-1"></i>Full Name *
                                    </label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email Address *
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="city" class="form-label">
                                        <i class="fas fa-city me-1"></i>City
                                    </label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Address
                            </label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="state" class="form-label">
                                        <i class="fas fa-map me-1"></i>State/Province
                                    </label>
                                    <input type="text" class="form-control" id="state" name="state" 
                                           value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="country" class="form-label">
                                        <i class="fas fa-globe me-1"></i>Country
                                    </label>
                                    <input type="text" class="form-control" id="country" name="country" 
                                           value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bio" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Bio
                            </label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" 
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary me-3">
                                <i class="fas fa-save me-1"></i> Update Profile
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo me-1"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="password-form">
                        <h4><i class="fas fa-key me-2"></i>Change Password</h4>
                        <p class="text-muted">Keep your account secure by using a strong password.</p>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Current Password *
                                </label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="form-group">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-key me-1"></i>New Password *
                                </label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="6" required>
                                <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-check me-1"></i>Confirm New Password *
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary me-3">
                                    <i class="fas fa-save me-1"></i> Change Password
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="mt-4">
                            <h5><i class="fas fa-shield-alt me-2 text-warning"></i>Account Security</h5>
                            <p class="text-muted">Your account was created on: 
                                <strong><?php echo date('F j, Y', strtotime($user['created_at'] ?? 'now')); ?></strong>
                            </p>
                            <p class="text-muted">Last updated: 
                                <strong><?php echo date('F j, Y g:i A', strtotime($user['updated_at'] ?? 'now')); ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
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

            // Password confirmation validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');

            function validatePassword() {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords don't match");
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }

            newPassword.addEventListener('input', validatePassword);
            confirmPassword.addEventListener('input', validatePassword);

            // Form validation feedback
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });

            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>