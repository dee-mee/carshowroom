<?php
session_start();
// Check if admin is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once '/opt/lampp/htdocs/carshowroom/config/database.php';

try {
    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'header_banner'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create table if it doesn't exist
        $conn->exec("CREATE TABLE IF NOT EXISTS `header_banner` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `background_image` varchar(255) DEFAULT NULL,
            `bottom_image` varchar(255) DEFAULT NULL,
            `title` varchar(255) DEFAULT NULL,
            `subtitle` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        // Insert a default row
        $conn->exec("INSERT INTO `header_banner` (`id`) VALUES (1)");
    } else {
        // Add title column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `header_banner` LIKE 'title'");
        if ($result->rowCount() == 0) {
            $conn->exec("ALTER TABLE `header_banner` ADD COLUMN `title` varchar(255) DEFAULT NULL AFTER `bottom_image`");
        }
        
        // Add subtitle column if it doesn't exist
        $result = $conn->query("SHOW COLUMNS FROM `header_banner` LIKE 'subtitle'");
        if ($result->rowCount() == 0) {
            $conn->exec("ALTER TABLE `header_banner` ADD COLUMN `subtitle` varchar(255) DEFAULT NULL AFTER `title`");
        }
    }
    
    // We'll use $conn consistently throughout the script
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Get current banner data
$banner_data = [];
try {
    $stmt = $conn->query("SELECT * FROM header_banner WHERE id = 1");
    $banner_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching banner data: " . $e->getMessage();
}

// Handle form submission
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function
function debug_log($message) {
    $log_file = '/tmp/upload_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

$background_image = ""; // ensure it's defined

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        debug_log('=== Starting file upload process ===');
        
        // Uploads folder relative to web root
        $target_dir = "/uploads/header-banner/";
        $absolute_dir = $_SERVER['DOCUMENT_ROOT'] . "/carshowroom" . $target_dir;

        // Make sure upload directory exists and is writable
        if (!is_dir($absolute_dir)) {
            if (!mkdir($absolute_dir, 0775, true)) {
                throw new Exception("Failed to create upload directory. Please check permissions for: " . dirname($absolute_dir));
            }
            // Set proper permissions
            chmod($absolute_dir, 0775);
        } elseif (!is_writable($absolute_dir)) {
            throw new Exception("Upload directory is not writable. Please check permissions for: " . $absolute_dir);
        }

        // Get existing banner data if any
        $stmt = $conn->query("SELECT * FROM header_banner WHERE id = 1");
        $banner_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get form data
        $title = $_POST['title'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';

        // Handle background image upload
        if (isset($_FILES["background_image"]) && $_FILES["background_image"]["error"] === UPLOAD_ERR_OK) {
            $background_tmp = $_FILES["background_image"]["tmp_name"];
            $background_filename = "background_" . time() . "." . strtolower(pathinfo($_FILES["background_image"]["name"], PATHINFO_EXTENSION));
            $background_path = $absolute_dir . $background_filename;

            // Validate mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $background_tmp);
            finfo_close($finfo);

            $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!in_array($mime, $allowed_mimes)) {
                throw new Exception("Invalid file type for background image.");
            }

            if (!move_uploaded_file($background_tmp, $background_path)) {
                throw new Exception("Failed to move uploaded background image.");
            }

            $background_image = '/' . ltrim($target_dir, '/') . $background_filename;
        } else {
            // keep old one if exists
            $background_image = !empty($banner_data['background_image']) ? '/' . ltrim($banner_data['background_image'], '/') : "";
        }

        // ✅ Require background image only if none in DB AND none uploaded
        if (empty($background_image)) {
            if (!isset($_FILES["background_image"]) || $_FILES["background_image"]["error"] !== UPLOAD_ERR_OK) {
                debug_log("No background image uploaded. _FILES error code: " . ($_FILES["background_image"]["error"] ?? 'not set'));
            } else {
                debug_log("Upload attempted but background_image is still empty — check move_uploaded_file or permissions.");
            }
            throw new Exception("Background image is required for the first save.");
        }

        // Handle bottom image upload
        $bottom_image = '';
        if (isset($_FILES["bottom_image"]) && $_FILES["bottom_image"]["error"] === UPLOAD_ERR_OK) {
            $bottom_tmp = $_FILES["bottom_image"]["tmp_name"];
            $bottom_filename = "bottom_" . time() . "." . strtolower(pathinfo($_FILES["bottom_image"]["name"], PATHINFO_EXTENSION));
            $bottom_path = $absolute_dir . $bottom_filename;

            // Validate mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $bottom_tmp);
            finfo_close($finfo);

            $allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
            if (!in_array($mime, $allowed_mimes)) {
                throw new Exception("Invalid file type for bottom image.");
            }

            if (!move_uploaded_file($bottom_tmp, $bottom_path)) {
                throw new Exception("Failed to move uploaded bottom image.");
            }

            $bottom_image = '/' . ltrim($target_dir, '/') . $bottom_filename;
        } else {
            // keep old one if exists
            $bottom_image = !empty($banner_data['bottom_image']) ? '/' . ltrim($banner_data['bottom_image'], '/') : "";
        }

        // Insert/update DB
        $stmt = $conn->prepare("
            INSERT INTO header_banner (id, background_image, bottom_image, title, subtitle, updated_at)
            VALUES (1, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            background_image = VALUES(background_image),
            bottom_image = VALUES(bottom_image),
            title = VALUES(title),
            subtitle = VALUES(subtitle),
            updated_at = NOW()
        ");
        
        if ($stmt->execute([$background_image, $bottom_image, $title, $subtitle])) {
            $success_message = "Banner saved successfully!";
            // Refresh banner data
            $stmt = $conn->query("SELECT * FROM header_banner WHERE id = 1");
            $banner_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            throw new Exception("Failed to save banner data");
        }
    } catch(Exception $e) {
        $error_message = $e->getMessage();
        debug_log('Error in form submission: ' . $error_message);
    }
}

// If no banner data exists, create a default entry
if (empty($banner_data)) {
    try {
        $stmt = $conn->prepare("INSERT INTO header_banner (id, background_image, bottom_image, updated_at) VALUES (1, '', '', NOW())");
        $stmt->execute();
        $banner_data = ['id' => 1, 'background_image' => '', 'bottom_image' => '', 'updated_at' => date('Y-m-d H:i:s')];
    } catch(PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
        debug_log($error_message);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header Banner Management - Car Showroom</title>
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
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
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
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            color: white;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
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

        /* Page Content */
        .page-content {
            padding: 30px;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 1.8rem;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .breadcrumb a {
            color: #6c5ce7;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #999;
        }

        /* Banner Section */
        .banner-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .upload-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .upload-item {
            text-align: center;
        }

        .upload-label {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .required {
            color: #e74c3c;
        }

        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 40px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .upload-area:hover {
            border-color: #6c5ce7;
            background: rgba(108, 92, 231, 0.02);
        }

        .upload-area.dragover {
            border-color: #6c5ce7;
            background: rgba(108, 92, 231, 0.1);
        }

        .upload-preview {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 10px;
        }

        .upload-preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 10px;
        }

        .upload-area:hover .upload-preview-overlay {
            opacity: 1;
        }

        .upload-icon {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
        }

        .upload-text {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 10px;
        }

        .upload-subtext {
            font-size: 0.9rem;
            color: #999;
        }

        .upload-btn {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
        }

        .change-btn {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 8px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .change-btn:hover {
            background: white;
        }

        .file-input {
            display: none;
        }

        /* Form Controls */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
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
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
        }

        .alert-error {
            background: linear-gradient(135deg, #e74c3c 0%, #fd79a8 100%);
            color: white;
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

            .page-content {
                padding: 20px;
            }

            .upload-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .form-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .upload-area {
                padding: 30px 15px;
                min-height: 250px;
            }

            .upload-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-image"></i>
                    Header Banner
                </h1>
                <nav class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">></span>
                    <a href="#">Home Page Settings</a>
                    <span class="breadcrumb-separator">></span>
                    <span>Header Banner</span>
                </nav>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Banner Section -->
            <div class="banner-section">
                <form method="POST" enctype="multipart/form-data">
                    <div class="upload-grid">
                        <!-- Background Image Upload -->
                        <div class="upload-item">
                            <div class="upload-label">
                                Background <span class="required">*</span>
                            </div>
                            <div class="upload-area" onclick="document.getElementById('background_image').click()" 
                                 ondrop="handleDrop(event, 'background')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                
                                <?php if (!empty($banner_data['background_image'])): ?>
                                    <div class="upload-preview" style="background-image: url('/carshowroom/admin/<?php echo htmlspecialchars($banner_data['background_image']); ?>')"></div>
                                    <div class="upload-preview-overlay">
                                        <button type="button" class="change-btn" onclick="event.stopPropagation(); document.getElementById('background_image').click()">
                                            <i class="fas fa-edit"></i> Change Image
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-image upload-icon"></i>
                                    <div class="upload-text">Click to upload background image</div>
                                    <div class="upload-subtext">or drag and drop</div>
                                    <button type="button" class="upload-btn" onclick="event.stopPropagation(); document.getElementById('background_image').click()">
                                        <i class="fas fa-upload"></i> Upload Image
                                    </button>
                                <?php endif; ?>
                                
                                <input type="file" id="background_image" name="background_image" class="file-input" 
                                       accept="image/*" onchange="previewImage(this, 'background')">
                            </div>
                        </div>

                        <!-- Bottom Image Upload -->
                        <div class="upload-item">
                            <div class="upload-label">
                                Bottom Image
                            </div>
                            <div class="upload-area" onclick="document.getElementById('bottom_image').click()" 
                                 ondrop="handleDrop(event, 'bottom')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                
                                <?php if (!empty($banner_data['bottom_image'])): ?>
                                    <div class="upload-preview" style="background-image: url('/carshowroom/admin/<?php echo htmlspecialchars($banner_data['bottom_image']); ?>')"></div>
                                    <div class="upload-preview-overlay">
                                        <button type="button" class="change-btn" onclick="event.stopPropagation(); document.getElementById('bottom_image').click()">
                                            <i class="fas fa-edit"></i> Change Image
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-image upload-icon"></i>
                                    <div class="upload-text">Click to upload bottom image</div>
                                    <div class="upload-subtext">or drag and drop (optional)</div>
                                    <button type="button" class="upload-btn" onclick="event.stopPropagation(); document.getElementById('bottom_image').click()">
                                        <i class="fas fa-upload"></i> Upload Image
                                    </button>
                                <?php endif; ?>
                                
                                <input type="file" id="bottom_image" name="bottom_image" class="file-input" 
                                       accept="image/*" onchange="previewImage(this, 'bottom')">
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
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

        function toggleDropdown() {
            document.getElementById('userDropdown').classList.toggle('show');
        }

        function previewImage(input, type) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                const uploadArea = input.closest('.upload-area');
                
                reader.onload = function(e) {
                    // Create or update preview
                    let preview = uploadArea.querySelector('.upload-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'upload-preview';
                        uploadArea.appendChild(preview);
                        
                        // Add change button if it doesn't exist
                        const changeBtn = document.createElement('button');
                        changeBtn.type = 'button';
                        changeBtn.className = 'change-btn';
                        changeBtn.innerHTML = '<i class="fas fa-edit"></i> Change Image';
                        changeBtn.onclick = (e) => {
                            e.stopPropagation();
                            input.click();
                        };
                        uploadArea.appendChild(changeBtn);
                    }
                    preview.style.backgroundImage = `url('${e.target.result}')`;
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        function handleDragOver(event) {
            event.preventDefault();
            event.currentTarget.classList.add('dragover');
        }

        function handleDragLeave(event) {
            event.preventDefault();
            event.currentTarget.classList.remove('dragover');
        }

        function handleDrop(event, type) {
            event.preventDefault();
            event.currentTarget.classList.remove('dragover');
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById(type + '_image');
                input.files = files;
                previewImage(input, type);
            }
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

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle')) {
                const dropdowns = document.getElementsByClassName('dropdown-menu');
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }


        // Close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

    </script>
</body>
</html>