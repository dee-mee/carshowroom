<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if admin is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /carshowroom/admin/login.php");
    exit();
}

// Include database configuration
require_once __DIR__ . '/../../config/database.php';

try {
    // Use the connection from database.php
    $pdo = $conn;
    $admin_name = $_SESSION['user_name'] ?? 'Admin';
} catch(PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Handle category actions (delete, status toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'delete' && isset($_POST['category_id'])) {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$_POST['category_id']]);
                $success_message = "Category deleted successfully!";
            } elseif ($_POST['action'] === 'toggle_status' && isset($_POST['category_id'])) {
                // Toggle status between 'active' and 'inactive'
                $stmt = $pdo->prepare("SELECT status FROM categories WHERE id = ?");
                $stmt->execute([$_POST['category_id']]);
                $current_status = $stmt->fetchColumn();
                
                $new_status = ($current_status === 'active') ? 'inactive' : 'active';
                
                $stmt = $pdo->prepare("UPDATE categories SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $_POST['category_id']]);
                $success_message = "Category status updated successfully!";
            }
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Pagination and search
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$entries_per_page = intval($_GET['show'] ?? 10);
$offset = ($page - 1) * $entries_per_page;

// Build search query
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE name LIKE ? OR slug LIKE ?";
    $search_params = ["%$search%", "%$search%"];
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM categories $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($search_params);
$total_categories = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get categories with pagination
$sql = "SELECT * FROM categories $search_condition ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$params = array_merge($search_params, [$entries_per_page, $offset]);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pagination
$total_pages = ceil($total_categories / $entries_per_page);
$showing_start = $total_categories > 0 ? $offset + 1 : 0;
$showing_end = min($offset + $entries_per_page, $total_categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Car Showroom</title>
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
            color: white;
            cursor: pointer;
            margin-right: auto;
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

        /* Categories Section */
        .categories-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .section-controls {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
            flex-wrap: wrap;
        }

        .controls-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .entries-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .entries-control select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
        }

        .search-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-control input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            min-width: 200px;
        }

        .add-new-btn {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .add-new-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(108, 92, 231, 0.4);
            color: white;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Status badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .status-badge.active {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
        }

        .status-badge.inactive {
            background: linear-gradient(135deg, #636e72 0%, #b2bec3 100%);
            color: white;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            border: 1px solid #e0e6ed;
            background: white;
            color: #666;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .action-btn:hover {
            color: #333;
            border-color: #333;
            transform: translateY(-1px);
        }

        .action-btn.edit:hover {
            color: #6c5ce7;
            border-color: #6c5ce7;
        }

        .action-btn.delete:hover {
            color: #e74c3c;
            border-color: #e74c3c;
        }

        /* Pagination */
        .table-footer {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .showing-info {
            color: #666;
            font-size: 0.9rem;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #666;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #f8f9fa;
            color: #333;
        }

        .pagination .active {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            border-color: #6c5ce7;
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
        }

        .alert-success {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
        }

        .alert-error {
            background: linear-gradient(135deg, #e74c3c 0%, #fd79a8 100%);
            color: white;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #666;
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .page-content {
                padding: 20px;
            }

            .table-responsive {
                overflow-x: auto;
            }

            .table-footer {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-tags"></i>
                    Categories
                </h1>
                <nav class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a>
                    <span class="breadcrumb-separator">></span>
                    <a href="#">Car Specifications</a>
                    <span class="breadcrumb-separator">></span>
                    <span>Category Management</span>
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

            <!-- Categories Section -->
            <div class="categories-section">
                <!-- Section Controls -->
                <div class="section-controls">
                    <div class="controls-left">
                        <div class="entries-control">
                            <label>Show</label>
                            <select onchange="changeEntries(this.value)">
                                <option value="10" <?php echo $entries_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $entries_per_page == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $entries_per_page == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $entries_per_page == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                            <label>entries</label>
                        </div>
                        <div class="search-control">
                            <label>Search:</label>
                            <input type="text" id="searchInput" placeholder="Search categories..." 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   onkeyup="handleSearch(event)">
                        </div>
                    </div>
                    <button class="add-new-btn" onclick="window.location.href='add-category.php'">
                        <i class="fas fa-plus"></i>
                        Add New Category
                    </button>
                </div>

                <!-- Categories Table -->
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tags"></i>
                        <h3>No categories found</h3>
                        <p>
                            <?php if (!empty($search)): ?>
                                No categories match your search criteria. Try adjusting your search terms.
                            <?php else: ?>
                                You haven't created any categories yet. Start by adding your first category.
                            <?php endif; ?>
                        </p>
                        <button class="add-new-btn" onclick="window.location.href='add-category.php'">
                            <i class="fas fa-plus"></i>
                            Add New Category
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($category['slug']); ?></code>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="status-badge <?php echo $category['status']; ?>"
                                                        onclick="return confirm('Are you sure you want to change the status?')">
                                                    <?php echo ucfirst($category['status']); ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit-category.php?id=<?php echo $category['id']; ?>" 
                                                   class="action-btn edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <button type="submit" class="action-btn delete" title="Delete"
                                                            onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer with Pagination -->
                    <div class="table-footer">
                        <div class="showing-info">
                            Showing <?php echo $showing_start; ?> to <?php echo $showing_end; ?> of <?php echo $total_categories; ?> entries
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <!-- Previous button -->
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&show=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                <?php else: ?>
                                    <span class="disabled">Previous</span>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <?php if ($i == $page): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>&show=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Next button -->
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&show=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                <?php else: ?>
                                    <span class="disabled">Next</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Edit category modal handler
        var editCategoryModal = document.getElementById('editCategoryModal');
        if (editCategoryModal) {
            editCategoryModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var name = button.getAttribute('data-name');
                var slug = button.getAttribute('data-slug');
                
                var modal = this;
                modal.querySelector('#editCategoryId').value = id;
                modal.querySelector('#editCategoryName').value = name;
                modal.querySelector('#editCategorySlug').value = slug;
            });
        }

        // Delete category modal handler
        var deleteCategoryModal = document.getElementById('deleteCategoryModal');
        if (deleteCategoryModal) {
            deleteCategoryModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var name = button.getAttribute('data-name');
                
                var modal = this;
                modal.querySelector('#deleteCategoryId').value = id;
                modal.querySelector('#deleteCategoryName').textContent = name;
            });
        }

        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }

            // Auto-generate slug from category name
            const categoryName = document.getElementById('categoryName');
            if (categoryName) {
                categoryName.addEventListener('input', function() {
                    const slug = this.value.toLowerCase()
                        .replace(/[^\w\s-]/g, '') // Remove special chars
                        .replace(/\s+/g, '-')      // Replace spaces with -
                        .replace(/--+/g, '-')       // Replace multiple - with single -
                        .trim();
                    document.getElementById('categorySlug').value = slug;
                });
            }
        });

        // Sidebar dropdown functionality
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
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const userDropdown = document.querySelector('.user-dropdown');
            
            if (!userDropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Close sidebar on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        });
    </script>
</body>
</html>