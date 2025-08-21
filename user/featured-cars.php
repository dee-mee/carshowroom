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

// Handle actions (toggle featured status, delete, etc.)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $car_id = $_POST['car_id'] ?? '';
        
        try {
            switch ($_POST['action']) {
                case 'toggle_featured':
                    $stmt = $conn->prepare("UPDATE cars SET featured = CASE WHEN featured = 1 THEN 0 ELSE 1 END WHERE id = ? AND user_id = ?");
                    $stmt->execute([$car_id, $user_id]);
                    $success = 'Featured status updated successfully!';
                    break;
                    
                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM cars WHERE id = ? AND user_id = ?");
                    $stmt->execute([$car_id, $user_id]);
                    $success = 'Car deleted successfully!';
                    break;
                    
                case 'toggle_status':
                    $stmt = $conn->prepare("UPDATE cars SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ? AND user_id = ?");
                    $stmt->execute([$car_id, $user_id]);
                    $success = 'Status updated successfully!';
                    break;
            }
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Pagination and search
$search = $_GET['search'] ?? '';
$entries_per_page = $_GET['entries'] ?? 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $entries_per_page;

// Build query for featured cars
$where_clause = "WHERE c.featured = 1 AND c.user_id = ?";
$params = [$user_id];

if (!empty($search)) {
    $where_clause .= " AND (c.title LIKE ? OR m.name LIKE ? OR c.year LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

// Get total count
try {
    $count_sql = "
        SELECT COUNT(*) 
        FROM cars c 
        LEFT JOIN makes m ON c.make_id = m.id 
        $where_clause
    ";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $entries_per_page);
} catch (PDOException $e) {
    $total_records = 0;
    $total_pages = 1;
}

// Get featured cars
try {
    $sql = "
        SELECT c.*, m.name as make_name, mo.name as model_name
        FROM cars c 
        LEFT JOIN makes m ON c.make_id = m.id 
        LEFT JOIN models mo ON c.model_id = mo.id 
        $where_clause
        ORDER BY c.created_at DESC 
        LIMIT $entries_per_page OFFSET $offset
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cars = [];
    $error = 'Error loading cars: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Cars - Car Showroom</title>
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

        .breadcrumb-text a:hover {
            text-decoration: underline;
        }

        /* Table Controls */
        .table-controls {
            background: white;
            border-radius: 0px;
            padding: 1.5rem;
            margin-bottom: 0;
            border: 1px solid #dee2e6;
            border-bottom: none;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .entries-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .entries-control select {
            border: 2px solid #e9ecef;
            border-radius: 0px;
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
        }

        .search-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-control input {
            border: 2px solid #e9ecef;
            border-radius: 0px;
            padding: 0.375rem 0.75rem;
            font-size: 0.9rem;
            width: 250px;
        }

        .search-control input:focus {
            border-color: rgb(34, 2, 78);
            box-shadow: 0 0 0 0.2rem rgba(34, 2, 78, 0.25);
        }

        /* Table Styles */
        .table-container {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0px;
            margin-bottom: 2rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Status and Featured Badges */
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .featured-badge {
            background-color: #d4edda;
            color: #155724;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .btn-action {
            padding: 0.375rem 0.75rem;
            margin: 0.125rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background-color: rgb(34, 2, 78);
            color: white;
        }

        .btn-edit:hover {
            background-color: rgb(46, 24, 70);
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c82333;
            color: white;
        }

        /* Pagination */
        .pagination-container {
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination-info {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .pagination {
            margin: 0;
        }

        .page-link {
            color: rgb(34, 2, 78);
            border: 1px solid #dee2e6;
            border-radius: 0px;
            margin: 0 2px;
            padding: 0.5rem 0.75rem;
        }

        .page-link:hover {
            color: white;
            background-color: rgb(34, 2, 78);
            border-color: rgb(34, 2, 78);
        }

        .page-item.active .page-link {
            background-color: rgb(34, 2, 78);
            border-color: rgb(34, 2, 78);
            color: white;
        }

        /* Alerts */
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

            .table-controls {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .search-control input {
                width: 100%;
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
                <a class="nav-link active" href="featured-cars.php">
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
            <h1><i class="fas fa-star me-2"></i>Cars</h1>
            <div class="breadcrumb-text">
                <a href="dashboard.php">Dashboard</a> > Car Management
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

        <!-- Table Controls -->
        <div class="table-controls">
            <div class="entries-control">
                <label for="entriesSelect">Show</label>
                <select id="entriesSelect" onchange="changeEntries(this.value)">
                    <option value="10" <?php echo $entries_per_page == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $entries_per_page == 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $entries_per_page == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $entries_per_page == 100 ? 'selected' : ''; ?>>100</option>
                </select>
                <span>entries</span>
            </div>
            
            <div class="search-control">
                <label for="searchInput">Search:</label>
                <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search); ?>" 
                       onkeyup="searchTable(this.value)" placeholder="Search cars...">
            </div>
        </div>

        <!-- Cars Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Featured</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cars)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No featured cars found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cars as $car): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($car['title']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($car['make_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($car['model_name'] ?? 'Other Models'); ?></td>
                                <td><?php echo htmlspecialchars($car['year']); ?></td>
                                <td>
                                    <span class="featured-badge">
                                        <i class="fas fa-star me-1"></i>Yes
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $car['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ucfirst($car['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit-car.php?id=<?php echo $car['id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this car from featured?');">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <input type="hidden" name="action" value="toggle_featured">
                                        <button type="submit" class="btn-action btn-delete">
                                            <i class="fas fa-star-o"></i> Remove Featured
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <?php echo min(($page - 1) * $entries_per_page + 1, $total_records); ?> to 
                <?php echo min($page * $entries_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
            </div>
            
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                </ul>
            </nav>
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
        });

        function changeEntries(value) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('entries', value);
            urlParams.set('page', '1'); // Reset to first page
            window.location.search = urlParams.toString();
        }

        function searchTable(value) {
            const urlParams = new URLSearchParams(window.location.search);
            if (value.trim() === '') {
                urlParams.delete('search');
            } else {
                urlParams.set('search', value);
            }
            urlParams.set('page', '1'); // Reset to first page
            
            // Debounce search
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(() => {
                window.location.search = urlParams.toString();
            }, 500);
        }
    </script>
</body>
</html>