<?php
session_start();
// Check if admin is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once '/opt/lampp/htdocs/carshowroom/config/database.php';

// Create testimonials table if it doesn't exist
try {
    $sql = "CREATE TABLE IF NOT EXISTS `testimonials` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `client_name` varchar(100) NOT NULL,
        `company` varchar(100) DEFAULT NULL,
        `testimonial` text NOT NULL,
        `image_path` varchar(255) DEFAULT NULL,
        `rating` tinyint(1) DEFAULT 5,
        `status` enum('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->exec($sql);
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $client_name = $_POST['client_name'] ?? '';
        $company = $_POST['company'] ?? '';
        $testimonial = $_POST['testimonial'] ?? '';
        $rating = min(5, max(1, intval($_POST['rating'] ?? 5)));
        $status = $_POST['status'] ?? 'active';
        
        // Basic validation
        if (empty($client_name) || empty($testimonial)) {
            throw new Exception("Client name and testimonial are required");
        }
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/carshowroom/uploads/testimonials/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.");
            }
            
            // Generate unique filename
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'testimonial_' . time() . '_' . uniqid() . '.' . $ext;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = '/uploads/testimonials/' . $filename;
            } else {
                throw new Exception("Failed to upload image");
            }
        }
        
        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO testimonials (client_name, company, testimonial, image_path, rating, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $client_name,
            $company,
            $testimonial,
            $image_path,
            $rating,
            $status
        ]);
        
        $success_message = "Testimonial added successfully!";
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Get testimonial to delete
        $stmt = $conn->prepare("SELECT image_path FROM testimonials WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $testimonial = $stmt->fetch();
        
        if ($testimonial) {
            // Delete image file if exists
            if (!empty($testimonial['image_path'])) {
                $image_path = $_SERVER['DOCUMENT_ROOT'] . '/carshowroom' . $testimonial['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            
            $success_message = "Testimonial deleted successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error deleting testimonial: " . $e->getMessage();
    }
}

// Get all testimonials
$stmt = $conn->query("
    SELECT * FROM testimonials 
    ORDER BY created_at DESC
");
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management - Car Showroom</title>
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

        /* Card Styles */
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 30px;
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            border-bottom: none;
        }

        .card-header h5 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        /* Form Controls */
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: #6c5ce7;
            box-shadow: 0 0 0 0.2rem rgba(108, 92, 231, 0.25);
        }

        .mb-3 {
            margin-bottom: 20px;
        }

        .row {
            display: flex;
            margin: 0 -15px;
        }

        .col-md-6 {
            flex: 0 0 50%;
            padding: 0 15px;
        }

        .col-md-3 {
            flex: 0 0 25%;
            padding: 0 15px;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-muted {
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
        }

        .btn-outline-primary {
            background: transparent;
            color: #6c5ce7;
            border: 2px solid #6c5ce7;
        }

        .btn-outline-primary:hover {
            background: #6c5ce7;
            color: white;
        }

        .btn-outline-danger {
            background: transparent;
            color: #dc3545;
            border: 2px solid #dc3545;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .d-grid {
            display: grid;
        }

        .d-md-flex {
            display: flex;
        }

        .justify-content-md-end {
            justify-content: flex-end;
        }

        .gap-2 {
            gap: 8px;
        }

        .me-1 {
            margin-right: 4px;
        }

        .me-2 {
            margin-right: 8px;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table-hover tbody tr:hover {
            background: rgba(108, 92, 231, 0.05);
        }

        .text-center {
            text-align: center;
        }

        /* Post Image - Updated for testimonials */
        .post-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #e9ecef;
        }

        /* Status Styles */
        .status-active {
            color: #28a745;
            font-weight: 500;
        }

        .status-inactive {
            color: #6c757d;
            font-weight: 500;
        }

        .rating {
            color: #ffc107;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }

        .d-flex {
            display: flex !important;
        }

        .align-items-center {
            align-items: center !important;
        }

        .justify-content-center {
            justify-content: center !important;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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

            .row {
                flex-direction: column;
            }

            .col-md-6, .col-md-3 {
                flex: 1;
                padding: 0;
                margin-bottom: 20px;
            }

            .d-md-flex {
                flex-direction: column;
            }

            .justify-content-md-end {
                justify-content: stretch;
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
                    <i class="fas fa-quote-left"></i>
                    Testimonials Management
                </h1>
                <nav class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">></span>
                    <span>Testimonials</span>
                </nav>
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

            <!-- Add New Testimonial Form -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-plus-circle me-2"></i>Add New Testimonial</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="client_name" name="client_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="company" class="form-label">Company</label>
                                <input type="text" class="form-control" id="company" name="company" placeholder="Company name (optional)">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="testimonial" class="form-label">Testimonial <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="testimonial" name="testimonial" rows="4" required placeholder="Write the client testimonial here..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="image" class="form-label">Client Photo</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">Square image works best (1:1 aspect ratio)</small>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <select class="form-select" id="rating" name="rating">
                                    <option value="5">★★★★★ (5/5)</option>
                                    <option value="4">★★★★☆ (4/5)</option>
                                    <option value="3">★★★☆☆ (3/5)</option>
                                    <option value="2">★★☆☆☆ (2/5)</option>
                                    <option value="1">★☆☆☆☆ (1/5)</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Testimonial
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Testimonials List -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list me-2"></i>Testimonials</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Client</th>
                                    <th>Testimonial</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($testimonials)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No testimonials found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($testimonials as $testimonial): ?>
                                        <tr>
                                            <td>
                                                <?php if ($testimonial['image_path']): ?>
                                                    <img src="/carshowroom<?php echo htmlspecialchars($testimonial['image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($testimonial['client_name']); ?>" 
                                                         class="post-image">
                                                <?php else: ?>
                                                    <div class="post-image bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-user text-muted" style="font-size: 1.5rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($testimonial['client_name']); ?></strong>
                                                <?php if ($testimonial['company']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($testimonial['company']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 300px;" 
                                                     title="<?php echo htmlspecialchars($testimonial['testimonial']); ?>">
                                                    <?php echo htmlspecialchars(substr($testimonial['testimonial'], 0, 100)) . (strlen($testimonial['testimonial']) > 100 ? '...' : ''); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="rating">
                                                    <?php 
                                                    $full_stars = (int)$testimonial['rating'];
                                                    $empty_stars = 5 - $full_stars;
                                                    echo str_repeat('★', $full_stars) . str_repeat('☆', $empty_stars);
                                                    ?>
                                                    <br><small class="text-muted">(<?php echo $testimonial['rating']; ?>/5)</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-<?php echo $testimonial['status']; ?>">
                                                    <i class="fas fa-<?php echo $testimonial['status'] === 'active' ? 'check-circle' : 'times-circle'; ?>"></i>
                                                    <?php echo ucfirst($testimonial['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($testimonial['created_at'])); ?></td>
                                            <td>
                                                <a href="edit-testimonial.php?id=<?php echo $testimonial['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary me-1" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $testimonial['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this testimonial?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
            
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const clientName = document.getElementById('client_name').value.trim();
            const testimonial = document.getElementById('testimonial').value.trim();
            
            if (!clientName || !testimonial) {
                e.preventDefault();
                alert('Please fill in all required fields (Client Name and Testimonial).');
                return false;
            }
        });

        // Preview image before upload
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can add image preview functionality here if needed
                    console.log('Image selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>