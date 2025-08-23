<?php
session_start();
// Check if admin is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once '/opt/lampp/htdocs/carshowroom/config/database.php';

// Create sellers table if it doesn't exist
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `sellers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `phone` varchar(20) NOT NULL,
            `address` text DEFAULT NULL,
            `commission_rate` decimal(5,2) DEFAULT 5.00,
            `profile_image` varchar(255) DEFAULT NULL,
            `status` enum('active','inactive') NOT NULL DEFAULT 'active',
            `total_sales` decimal(12,2) DEFAULT 0.00,
            `commission_earned` decimal(12,2) DEFAULT 0.00,
            `date_joined` timestamp NOT NULL DEFAULT current_timestamp(),
            `last_login` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
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
        // Get form data
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $commission_rate = $_POST['commission_rate'] ?? 5.00;
        $status = $_POST['status'] ?? 'active';
        $edit_id = $_POST['edit_id'] ?? null;
        
        // Basic validation
        if (empty($name) || empty($email) || empty($phone)) {
            throw new Exception("Name, email and phone are required");
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address");
        }
        
        // Check if email already exists (exclude current record if editing)
        if ($edit_id) {
            $stmt = $conn->prepare("SELECT id FROM sellers WHERE email = ? AND id != ?");
            $stmt->execute([$email, $edit_id]);
        } else {
            $stmt = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
            $stmt->execute([$email]);
        }
        if ($stmt->fetch()) {
            throw new Exception("A seller with this email already exists");
        }
        
        // Validate commission rate
        if ($commission_rate < 0 || $commission_rate > 100) {
            throw new Exception("Commission rate must be between 0 and 100");
        }
        
        // Handle profile image upload
        $profile_image = null;
        $old_image = null;
        
        // If editing, get current image
        if ($edit_id) {
            $stmt = $conn->prepare("SELECT profile_image FROM sellers WHERE id = ?");
            $stmt->execute([$edit_id]);
            $current_seller = $stmt->fetch();
            $old_image = $current_seller['profile_image'] ?? null;
            $profile_image = $old_image; // Keep existing image by default
        }
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/carshowroom/uploads/sellers/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['profile_image']['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.");
            }
            
            // Generate unique filename
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $filename = 'seller_' . time() . '_' . uniqid() . '.' . $ext;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                // Delete old image if exists and we're updating
                if ($old_image && file_exists($_SERVER['DOCUMENT_ROOT'] . '/carshowroom' . $old_image)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/carshowroom' . $old_image);
                }
                $profile_image = '/uploads/sellers/' . $filename;
            } else {
                throw new Exception("Failed to upload profile image");
            }
        }
        
        if ($edit_id) {
            // Update existing seller
            $stmt = $conn->prepare("
                UPDATE sellers 
                SET name = ?, email = ?, phone = ?, address = ?, commission_rate = ?, profile_image = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $name,
                $email,
                $phone,
                $address,
                $commission_rate,
                $profile_image,
                $status,
                $edit_id
            ]);
            
            $success_message = "Seller updated successfully!";
        } else {
            // Insert new seller
            $stmt = $conn->prepare("
                INSERT INTO sellers (name, email, phone, address, commission_rate, profile_image, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $name,
                $email,
                $phone,
                $address,
                $commission_rate,
                $profile_image,
                $status
            ]);
            
            $success_message = "Seller added successfully!";
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Get seller to delete
        $stmt = $conn->prepare("SELECT profile_image FROM sellers WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $seller = $stmt->fetch();
        
        if ($seller) {
            // Delete profile image file if exists
            if (!empty($seller['profile_image'])) {
                $image_path = $_SERVER['DOCUMENT_ROOT'] . '/carshowroom' . $seller['profile_image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM sellers WHERE id = ?");
            $stmt->execute([$_GET['delete']]);
            
            $success_message = "Seller deleted successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error deleting seller: " . $e->getMessage();
    }
}

// Handle status toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    try {
        $stmt = $conn->prepare("SELECT status FROM sellers WHERE id = ?");
        $stmt->execute([$_GET['toggle_status']]);
        $seller = $stmt->fetch();
        
        if ($seller) {
            $new_status = ($seller['status'] === 'active') ? 'inactive' : 'active';
            $stmt = $conn->prepare("UPDATE sellers SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $_GET['toggle_status']]);
            
            $success_message = "Seller status updated successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error updating seller status: " . $e->getMessage();
    }
}

// Get all sellers
$stmt = $conn->query("
    SELECT * FROM sellers 
    ORDER BY date_joined DESC
");
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stmt = $conn->query("SELECT 
    COUNT(*) as total_sellers,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_sellers,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_sellers,
    COALESCE(AVG(commission_rate), 0) as avg_commission,
    COALESCE(SUM(total_sales), 0) as total_sales_amount,
    COALESCE(SUM(commission_earned), 0) as total_commission_earned
    FROM sellers");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sellers Management - Car Showroom</title>
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

        /* Statistics Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: white;
        }

        .stat-icon.users {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
        }

        .stat-icon.active {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
        }

        .stat-icon.inactive {
            background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
        }

        .stat-icon.commission {
            background: linear-gradient(135deg, #fd79a8 0%, #e84393 100%);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
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

        .col-md-4 {
            flex: 0 0 33.333%;
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

        .btn-outline-warning {
            background: transparent;
            color: #ffc107;
            border: 2px solid #ffc107;
        }

        .btn-outline-warning:hover {
            background: #ffc107;
            color: #333;
        }

        .btn-outline-success {
            background: transparent;
            color: #28a745;
            border: 2px solid #28a745;
        }

        .btn-outline-success:hover {
            background: #28a745;
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

        /* Profile Image */
        .profile-image {
            width: 50px;
            height: 50px;
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
            color: #dc3545;
            font-weight: 500;
        }

        /* Commission Badge */
        .commission-badge {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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

            .col-md-6, .col-md-4 {
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

            .stats-row {
                grid-template-columns: 1fr;
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
                    <i class="fas fa-users"></i>
                    Sellers Management
                </h1>
                <nav class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">></span>
                    <span>Sellers Management</span>
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

            <!-- Sellers Table -->
            <div class="sellers-container">
                <div class="table-controls">
                    <div class="show-entries">
                        <label>Show 
                            <select id="entriesSelect" onchange="updateEntries()">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            entries
                        </label>
                    </div>
                    <div class="search-box">
                        <label>Search: 
                            <input type="text" id="searchInput" placeholder="" onkeyup="searchTable()">
                        </label>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="sellers-table" id="sellersTable">
                        <thead>
                            <tr>
                                <th>First Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sellers)): ?>
                                <tr>
                                    <td colspan="5" class="no-data">No sellers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sellers as $seller): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($seller['name']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['phone']); ?></td>
                                        <td>
                                            <button class="edit-btn" onclick="editSeller(<?php echo $seller['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
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

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Search functionality
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('sellersTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length - 1; j++) { // Exclude action column
                    if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        }

        // Update entries per page
        function updateEntries() {
            const select = document.getElementById('entriesSelect');
            const value = select.value;
            const table = document.getElementById('sellersTable');
            const rows = table.getElementsByTagName('tr');

            // Show all rows first
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = '';
            }

            // Hide rows beyond the selected limit
            if (value !== 'all') {
                const limit = parseInt(value);
                for (let i = limit + 1; i < rows.length; i++) {
                    rows[i].style.display = 'none';
                }
            }
        }

        // Edit seller function
        function editSeller(sellerId) {
            // You can implement a modal or redirect to edit page
            window.location.href = 'edit-seller.php?id=' + sellerId;
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Initialize table with default entries
        document.addEventListener('DOMContentLoaded', function() {
            updateEntries();
        });
    </script>

    <style>
        /* Sellers Container */
        .sellers-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Table Controls */
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .show-entries label,
        .search-box label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
        }
        
        .show-entries select,
        .search-box input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 14px;
            outline: none;
        }
        
        .show-entries select:focus,
        .search-box input:focus {
            border-color: #6c5ce7;
            box-shadow: 0 0 0 2px rgba(108, 92, 231, 0.1);
        }
        
        /* Table Wrapper */
        .table-wrapper {
            overflow-x: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Sellers Table */
        .sellers-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background: white;
        }
        
        .sellers-table th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
        }
        
        .sellers-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #555;
        }
        
        .sellers-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .sellers-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        .sellers-table tbody tr:nth-child(even):hover {
            background-color: #f0f0f0;
        }
        
        /* Edit Button */
        .edit-btn {
            background: #6c5ce7;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .edit-btn:hover {
            background: #5a4fcf;
            transform: translateY(-1px);
        }
        
        .edit-btn i {
            font-size: 11px;
        }
        
        /* No Data */
        .no-data {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 40px 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .table-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .show-entries,
            .search-box {
                justify-content: center;
            }
            
            .sellers-table th,
            .sellers-table td {
                padding: 8px 10px;
                font-size: 13px;
            }
        }
    </style>
</body>
</html>