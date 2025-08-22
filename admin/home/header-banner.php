<?php
session_start();

// Check if admin is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'carlisto_showroom';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to check if current page is in submenu
function isActiveMenu($menuItems, $currentPage) {
    if (is_array($menuItems)) {
        return in_array($currentPage, $menuItems);
    }
    return $menuItems === $currentPage;
}

$currentPage = basename($_SERVER['PHP_SELF']);
$carSpecsPages = ['categories.php', 'conditions.php', 'brands.php', 'models.php', 'body-types.php', 'fuel-types.php', 'transmission-types.php'];
$carManagementPages = ['all-cars.php', 'featured-cars.php', 'pending-cars.php', 'published-cars.php', 'rejected-cars.php'];
$generalSettingsPages = ['logo.php', 'favicon.php', 'loader.php', 'breadcrumb.php', 'website-contents.php', 'payment-info.php', 'footer.php', 'social-links.php'];
$homePageSettings = ['header-banner.php', 'featured-section.php', 'latest-cars-section.php', 'testimonials.php', 'blog-section.php'];
$menuPages = ['about.php', 'contact.php', 'privacy-policy.php', 'terms-conditions.php'];
$emailSettings = ['smtp-config.php', 'email-templates.php'];
$blogPages = ['all-posts.php', 'add-post.php', 'blog-categories.php'];
$seoTools = ['meta-tags.php', 'sitemap.php', 'robots.php'];
$systemActivation = ['activation.php', 'backup.php'];

$admin_name = $_SESSION['user_name'] ?? 'Admin';
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/banner/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Get current banner settings
        $stmt = $pdo->query("SELECT * FROM header_banner_settings WHERE id = 1");
        $current_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $background_image = $current_settings['background_image'] ?? '';
        $bottom_image = $current_settings['bottom_image'] ?? '';
        
        // Handle background image upload
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['background_image']['tmp_name'];
            $file_name = $_FILES['background_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate image type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_types)) {
                $new_filename = 'banner_bg_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Delete old background image if exists
                    if ($background_image && file_exists($upload_dir . basename($background_image))) {
                        unlink($upload_dir . basename($background_image));
                    }
                    $background_image = 'uploads/banner/' . $new_filename;
                }
            } else {
                throw new Exception('Invalid background image type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.');
            }
        }
        
        // Handle bottom image upload
        if (isset($_FILES['bottom_image']) && $_FILES['bottom_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['bottom_image']['tmp_name'];
            $file_name = $_FILES['bottom_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate image type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_types)) {
                $new_filename = 'banner_bottom_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Delete old bottom image if exists
                    if ($bottom_image && file_exists($upload_dir . basename($bottom_image))) {
                        unlink($upload_dir . basename($bottom_image));
                    }
                    $bottom_image = 'uploads/banner/' . $new_filename;
                }
            } else {
                throw new Exception('Invalid bottom image type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.');
            }
        }
        
        // Get other form data
        $title = $_POST['title'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';
        $button_text = $_POST['button_text'] ?? '';
        $button_link = $_POST['button_link'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Update or insert banner settings
        if ($current_settings) {
            $stmt = $pdo->prepare("
                UPDATE header_banner_settings 
                SET title = ?, subtitle = ?, background_image = ?, bottom_image = ?, 
                    button_text = ?, button_link = ?, is_active = ?, updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute([$title, $subtitle, $background_image, $bottom_image, $button_text, $button_link, $is_active]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO header_banner_settings 
                (id, title, subtitle, background_image, bottom_image, button_text, button_link, is_active, created_at, updated_at) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$title, $subtitle, $background_image, $bottom_image, $button_text, $button_link, $is_active]);
        }
        
        $pdo->commit();
        $success_message = 'Header banner settings updated successfully!';
        
        // Refresh current settings
        $stmt = $pdo->query("SELECT * FROM header_banner_settings WHERE id = 1");
        $current_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = 'Error: ' . $e->getMessage();
    }
} else {
    // Get current banner settings for display
    $stmt = $pdo->query("SELECT * FROM header_banner_settings WHERE id = 1");
    $current_settings = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Create table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS header_banner_settings (
            id INT PRIMARY KEY,
            title VARCHAR(255) DEFAULT '',
            subtitle TEXT DEFAULT '',
            background_image VARCHAR(500) DEFAULT '',
            bottom_image VARCHAR(500) DEFAULT '',
            button_text VARCHAR(100) DEFAULT '',
            button_link VARCHAR(500) DEFAULT '',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    error_log("Table creation error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header Banner Management - Car Showroom Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .submenu-item:hover, .submenu-item.active {
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

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .breadcrumb a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: white;
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

        /* Content */
        .content {
            padding: 30px;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .card-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-control-file {
            padding: 8px;
            border: 2px dashed #ddd;
            background: #f8f9fa;
        }

        .form-control-file:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            color: white;
        }

        /* Image Preview */
        .image-preview {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .preview-container {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .preview-container h5 {
            color: #6c757d;
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .no-image {
            color: #adb5bd;
            font-style: italic;
            padding: 60px 20px;
        }

        .no-image i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid transparent;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .image-preview {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .content {
                padding: 20px;
            }
        }

        .mobile-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-toggle {
                display: block;
                background: none;
                border: none;
                font-size: 1.5rem;
                color: white;
                cursor: pointer;
            }
        }

        .row {
            display: flex;
            gap: 20px;
        }

        .col-md-6 {
            flex: 1;
        }

        @media (max-width: 768px) {
            .row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-car"></i> CarShowroom</h4>
            <small>Admin Panel</small>
        </div>
        <nav class="nav-menu">
            <!-- Dashboard -->
            <div class="nav-item">
                <a href="../dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- Car Specifications -->
            <div class="nav-item">
                <a href="#" class="nav-link <?php echo isActiveMenu($carSpecsPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                    <i class="fas fa-cog"></i>
                    <span>Car Specifications</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu <?php echo isActiveMenu($carSpecsPages, $currentPage) ? 'show' : ''; ?>">
                    <a href="../car-specs/categories.php" class="submenu-item">Category Management</a>
                    <a href="../car-specs/conditions.php" class="submenu-item">Condition Management</a>
                    <a href="../car-specs/brands.php" class="submenu-item">Brand Management</a>
                    <a href="../car-specs/models.php" class="submenu-item">Model Management</a>
                    <a href="../car-specs/body-types.php" class="submenu-item">Body Type Management</a>
                    <a href="../car-specs/fuel-types.php" class="submenu-item">Fuel Type Management</a>
                    <a href="../car-specs/transmission-types.php" class="submenu-item">Transmission Types</a>
                </div>
            </div>

            <!-- Pricing Ranges -->
            <div class="nav-item">
                <a href="../pricing/index.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Pricing Ranges</span>
                </a>
            </div>

            <!-- Plan Management -->
            <div class="nav-item">
                <a href="../plans/index.php" class="nav-link">
                    <i class="fas fa-list-alt"></i>
                    <span>Plan Management</span>
                </a>
            </div>

            <!-- Car Management -->
            <div class="nav-item">
                <a href="#" class="nav-link <?php echo isActiveMenu($carManagementPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                    <i class="fas fa-car"></i>
                    <span>Car Management</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu <?php echo isActiveMenu($carManagementPages, $currentPage) ? 'show' : ''; ?>">
                    <a href="../cars/all-cars.php" class="submenu-item">All Cars</a>
                    <a href="../cars/featured-cars.php" class="submenu-item">Featured Cars</a>
                    <a href="../cars/pending-cars.php" class="submenu-item">Pending Cars</a>
                    <a href="../cars/published-cars.php" class="submenu-item">Published Cars</a>
                    <a href="../cars/rejected-cars.php" class="submenu-item">Rejected Cars</a>
                </div>
            </div>

            <!-- Sellers Management -->
            <div class="nav-item">
                <a href="../sellers/index.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Sellers Management</span>
                </a>
            </div>

            <!-- Car Approvals -->
            <div class="nav-item">
                <a href="../approve-cars.php" class="nav-link">
                    <i class="fas fa-check-circle"></i>
                    <span>Car Approvals</span>
                </a>
            </div>

            <!-- General Settings -->
            <div class="nav-item">
                <a href="#" class="nav-link <?php echo isActiveMenu($generalSettingsPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                    <i class="fas fa-cog"></i>
                    <span>General Settings</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu <?php echo isActiveMenu($generalSettingsPages, $currentPage) ? 'show' : ''; ?>">
                    <a href="../settings/logo.php" class="submenu-item">Logo</a>
                    <a href="../settings/favicon.php" class="submenu-item">Favicon</a>
                    <a href="../settings/loader.php" class="submenu-item">Loader</a>
                    <a href="../settings/breadcrumb.php" class="submenu-item">Breadcrumb</a>
                    <a href="../settings/website-contents.php" class="submenu-item">Website Contents</a>
                    <a href="../settings/payment-info.php" class="submenu-item">Payment Information</a>
                    <a href="../settings/footer.php" class="submenu-item">Footer</a>
                    <a href="../settings/social-links.php" class="submenu-item">Social Links</a>
                </div>
            </div>

            <!-- Home Page Settings -->
            <div class="nav-item">
                <a href="#" class="nav-link <?php echo isActiveMenu($homePageSettings, $currentPage) ? 'active expanded' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                    <i class="fas fa-home"></i>
                    <span>Home Page</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu <?php echo isActiveMenu($homePageSettings, $currentPage) ? 'show' : ''; ?>">
                    <a href="header-banner.php" class="submenu-item <?php echo $currentPage === 'header-banner.php' ? 'active' : ''; ?>">Header Banner</a>
                    <a href="featured-section.php" class="submenu-item">Featured Section</a>
                    <a href="latest-cars-section.php" class="submenu-item">Latest Cars</a>
                    <a href="testimonials.php" class="submenu-item">Testimonials</a>
                    <a href="blog-section.php" class="submenu-item">Blog Section</a>
                </div>
            </div>

            <!-- Menu Page Settings -->
            <div class="nav-item">
                <a href="#" class="nav-link <?php echo isActiveMenu($menuPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                    <i class="fas fa-file-alt"></i>
                    <span>Menu Pages</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu <?php echo isActiveMenu($menuPages, $currentPage) ? 'show' : ''; ?>">
                    <a href="../pages/about.php" class="submenu-item">About Us</a>
                    <a href="../pages/contact.php" class="submenu-item">Contact</a>
                    <a href="../pages/privacy-policy.php" class="submenu-item">Privacy Policy</a>
                    <a href="../pages/terms-conditions.php" class="submenu-item">Terms & Conditions</a>
                </div>
            </div>

            <!-- Blog -->
            <div class="nav-item">
                <a href="#" class="nav-link <?php echo isActiveMenu($blogPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                    <i class="fas fa-blog"></i>
                    <span>Blog</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu <?php echo isActiveMenu($blogPages, $currentPage) ? 'show' : ''; ?>">
                    <a href="../blog/all-posts.php" class="submenu-item">All Posts</a>
                    <a href="../blog/add-post.php" class="submenu-item">Add New</a>
                    <a href="../blog/categories.php" class="submenu-item">Categories</a>
                </div>
            </div>

            <!-- SEO Tools -->
            <div class="nav-item">
                <a href="#" class="nav-link <?php echo isActiveMenu($seoTools, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                    <i class="fas fa-search"></i>
                    <span>SEO Tools</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu <?php echo isActiveMenu($seoTools, $currentPage) ? 'show' : ''; ?>">
                    <a href="../seo/meta-tags.php" class="submenu-item">Meta Tags</a>
                    <a href="../seo/sitemap.php" class="submenu-item">Sitemap</a>
                    <a href="../seo/robots.php" class="submenu-item">Robots.txt</a>
                </div>
            </div>

            <!-- System -->
            <div class="nav-item">
                <a href="#" class="nav-link <?php echo isActiveMenu($systemActivation, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                    <i class="fas fa-tools"></i>
                    <span>System</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu <?php echo isActiveMenu($systemActivation, $currentPage) ? 'show' : ''; ?>">
                    <a href="../system/activation.php" class="submenu-item">Activation</a>
                    <a href="../system/backup.php" class="submenu-item">Backup</a>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h1 class="page-title">Header Banner</h1>
                <div class="breadcrumb">
                    <a href="../dashboard.php">Dashboard</a>
                    <i class="fas fa-angle-right"></i>
                    <span>Home Page Settings</span>
                    <i class="fas fa-angle-right"></i>
                    <span>Header Banner</span>
                </div>
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Header Banner Form -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-image"></i>
                        Header Banner Management
                    </h2>
                    <p style="margin-top: 10px; color: #666; font-size: 0.9rem;">
                        Manage your website's header banner images and content. Upload background and bottom images to create an attractive banner section.
                    </p>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-heading" style="margin-right: 5px;"></i>
                                    Banner Title
                                </label>
                                <input type="text" name="title" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_settings['title'] ?? ''); ?>"
                                       placeholder="Enter banner title">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-text-height" style="margin-right: 5px;"></i>
                                    Banner Subtitle
                                </label>
                                <input type="text" name="subtitle" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_settings['subtitle'] ?? ''); ?>"
                                       placeholder="Enter banner subtitle">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-mouse-pointer" style="margin-right: 5px;"></i>
                                    Button Text
                                </label>
                                <input type="text" name="button_text" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_settings['button_text'] ?? ''); ?>"
                                       placeholder="Enter button text (e.g., 'Shop Now')">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-link" style="margin-right: 5px;"></i>
                                    Button Link
                                </label>
                                <input type="url" name="button_link" class="form-control" 
                                       value="<?php echo htmlspecialchars($current_settings['button_link'] ?? ''); ?>"
                                       placeholder="Enter button link URL">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-image" style="margin-right: 5px;"></i>
                                    Background Image <span style="color: #e74c3c;">*</span>
                                </label>
                                <input type="file" name="background_image" class="form-control form-control-file" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">
                                    Recommended size: 1920x1080px. Supports: JPG, PNG, GIF, WEBP (Max: 5MB)
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-image" style="margin-right: 5px;"></i>
                                    Bottom Image
                                </label>
                                <input type="file" name="bottom_image" class="form-control form-control-file" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">
                                    Recommended size: 800x600px. Supports: JPG, PNG, GIF, WEBP (Max: 5MB)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="is_active" name="is_active" value="1" 
                                   <?php echo (isset($current_settings['is_active']) && $current_settings['is_active']) ? 'checked' : ''; ?>>
                            <label for="is_active" class="form-label" style="margin: 0; cursor: pointer;">
                                <i class="fas fa-toggle-on" style="margin-right: 5px; color: #28a745;"></i>
                                Enable Header Banner
                            </label>
                        </div>
                    </div>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                        <button type="submit" class="btn btn-primary" style="margin-right: 15px;">
                            <i class="fas fa-save" style="margin-right: 8px;"></i>
                            Update Banner Settings
                        </button>
                        <a href="../dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>

            <!-- Current Images Preview -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-eye"></i>
                        Current Banner Images
                    </h2>
                </div>

                <div class="image-preview">
                    <!-- Background Image Preview -->
                    <div class="preview-container">
                        <h5>
                            <i class="fas fa-image" style="margin-right: 8px;"></i>
                            Background Image
                        </h5>
                        <?php if (!empty($current_settings['background_image']) && file_exists('../' . $current_settings['background_image'])): ?>
                            <img src="../<?php echo htmlspecialchars($current_settings['background_image']); ?>" 
                                 alt="Background Image" class="preview-image">
                            <div style="margin-top: 10px; font-size: 0.8rem; color: #666;">
                                <i class="fas fa-info-circle"></i>
                                Current background image
                            </div>
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                                No background image uploaded
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bottom Image Preview -->
                    <div class="preview-container">
                        <h5>
                            <i class="fas fa-image" style="margin-right: 8px;"></i>
                            Bottom Image
                        </h5>
                        <?php if (!empty($current_settings['bottom_image']) && file_exists('../' . $current_settings['bottom_image'])): ?>
                            <img src="../<?php echo htmlspecialchars($current_settings['bottom_image']); ?>" 
                                 alt="Bottom Image" class="preview-image">
                            <div style="margin-top: 10px; font-size: 0.8rem; color: #666;">
                                <i class="fas fa-info-circle"></i>
                                Current bottom image
                            </div>
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                                No bottom image uploaded
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Banner Settings Info -->
            <?php if ($current_settings): ?>
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Current Banner Settings
                    </h2>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                        <h6 style="color: #667eea; margin-bottom: 10px; font-weight: 600;">
                            <i class="fas fa-heading"></i> Title
                        </h6>
                        <p style="margin: 0; color: #333;">
                            <?php echo !empty($current_settings['title']) ? htmlspecialchars($current_settings['title']) : '<em style="color: #999;">Not set</em>'; ?>
                        </p>
                    </div>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
                        <h6 style="color: #28a745; margin-bottom: 10px; font-weight: 600;">
                            <i class="fas fa-text-height"></i> Subtitle
                        </h6>
                        <p style="margin: 0; color: #333;">
                            <?php echo !empty($current_settings['subtitle']) ? htmlspecialchars($current_settings['subtitle']) : '<em style="color: #999;">Not set</em>'; ?>
                        </p>
                    </div>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <h6 style="color: #ffc107; margin-bottom: 10px; font-weight: 600;">
                            <i class="fas fa-mouse-pointer"></i> Button Text
                        </h6>
                        <p style="margin: 0; color: #333;">
                            <?php echo !empty($current_settings['button_text']) ? htmlspecialchars($current_settings['button_text']) : '<em style="color: #999;">Not set</em>'; ?>
                        </p>
                    </div>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;">
                        <h6 style="color: #dc3545; margin-bottom: 10px; font-weight: 600;">
                            <i class="fas fa-link"></i> Button Link
                        </h6>
                        <p style="margin: 0; color: #333;">
                            <?php echo !empty($current_settings['button_link']) ? htmlspecialchars($current_settings['button_link']) : '<em style="color: #999;">Not set</em>'; ?>
                        </p>
                    </div>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8;">
                        <h6 style="color: #17a2b8; margin-bottom: 10px; font-weight: 600;">
                            <i class="fas fa-toggle-on"></i> Status
                        </h6>
                        <p style="margin: 0;">
                            <?php if ($current_settings['is_active']): ?>
                                <span style="color: #28a745; font-weight: 500;">
                                    <i class="fas fa-check-circle"></i> Active
                                </span>
                            <?php else: ?>
                                <span style="color: #dc3545; font-weight: 500;">
                                    <i class="fas fa-times-circle"></i> Inactive
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #6c757d;">
                        <h6 style="color: #6c757d; margin-bottom: 10px; font-weight: 600;">
                            <i class="fas fa-clock"></i> Last Updated
                        </h6>
                        <p style="margin: 0; color: #333;">
                            <?php echo !empty($current_settings['updated_at']) ? date('M d, Y - H:i A', strtotime($current_settings['updated_at'])) : '<em style="color: #999;">Never</em>'; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !mobileToggle.contains(event.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });

        // Close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        });

        // File input preview (optional enhancement)
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const fileSize = this.files[0].size;
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    
                    if (fileSize > maxSize) {
                        alert('File size exceeds 5MB limit. Please choose a smaller image.');
                        this.value = '';
                        return;
                    }
                }
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>