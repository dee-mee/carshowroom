<?php
session_start();
require_once 'config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add':
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', $name)) . '-' . substr(md5(uniqid()), 0, 8);
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, status, created_at) VALUES (?, ?, 'active', NOW())");
            if ($stmt->execute([$name, $slug])) {
                echo json_encode(['success' => true, 'message' => 'Category added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add category']);
            }
            exit();
            
        case 'edit':
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            $slug = $_POST['slug'];
            
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
            if ($stmt->execute([$name, $slug, $id])) {
                echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update category']);
            }
            exit();
            
        case 'delete':
            $id = $_POST['id'];
            
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
            }
            exit();
            
        case 'toggle_status':
            $id = $_POST['id'];
            $status = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE categories SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
            exit();
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$offset = ($page - 1) * $per_page;

// Build query
$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE name LIKE ? OR slug LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM categories $where");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get categories
$stmt = $pdo->prepare("SELECT * FROM categories $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-purple: #663399;
            --dark-purple: #4a2668;
            --light-purple: #f8f4ff;
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-purple) 0%, var(--dark-purple) 100%);
            min-height: 100vh;
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            background: none;
            width: 100%;
        }

        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-menu .nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        .dropdown-menu {
            background-color: rgba(0,0,0,0.1);
            border: none;
            margin-left: 0;
        }

        .dropdown-item {
            color: rgba(255,255,255,0.7);
            padding: 8px 40px;
            border: none;
            background: none;
        }

        .dropdown-item:hover,
        .dropdown-item.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }

        .main-content {
            margin-left: 280px;
            padding: 0;
        }

        .topbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-wrapper {
            padding: 30px;
        }

        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 30px;
        }

        .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .table-controls {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
        }

        .btn-primary {
            background-color: var(--primary-purple);
            border-color: var(--primary-purple);
        }

        .btn-primary:hover {
            background-color: var(--dark-purple);
            border-color: var(--dark-purple);
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table thead th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
        }

        .table tbody tr:hover {
            background-color: rgba(102, 51, 153, 0.05);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .status-active {
            background-color: #28a745;
            color: white;
        }

        .status-inactive {
            background-color: #dc3545;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
        }

        .pagination {
            margin-top: 20px;
        }

        .page-link {
            color: var(--primary-purple);
        }

        .page-item.active .page-link {
            background-color: var(--primary-purple);
            border-color: var(--primary-purple);
        }

        .modal-header {
            background-color: var(--primary-purple);
            color: white;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .hamburger {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="hamburger d-md-none" onclick="toggleSidebar()">
                <i class="bi bi-x-lg"></i>
            </button>
            <h4 class="text-white mb-0">Admin Panel</h4>
        </div>
        
        <div class="sidebar-menu">
            <a href="admin_dashboard.php" class="nav-link">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
            
            <div class="dropdown">
                <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-gear"></i>
                    Car Specifications
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item active" href="category_management.php">Category Management</a></li>
                    <li><a class="dropdown-item" href="condition_management.php">Condition Management</a></li>
                    <li><a class="dropdown-item" href="brand_management.php">Brand Management</a></li>
                    <li><a class="dropdown-item" href="model_management.php">Model Management</a></li>
                    <li><a class="dropdown-item" href="body_type_management.php">Body Type Management</a></li>
                    <li><a class="dropdown-item" href="fuel_type_management.php">Fuel Type Management</a></li>
                    <li><a class="dropdown-item" href="transmission_management.php">Transmission Type Management</a></li>
                </ul>
            </div>
            
            <a href="pricing_ranges.php" class="nav-link">
                <i class="bi bi-tags"></i>
                Pricing Ranges
            </a>
            
            <a href="plan_management.php" class="nav-link">
                <i class="bi bi-list-check"></i>
                Plan Management
            </a>
            
            <div class="dropdown">
                <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-car-front"></i>
                    Car Management
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="car_listings.php">Car Listings</a></li>
                    <li><a class="dropdown-item" href="featured_cars.php">Featured Cars</a></li>
                </ul>
            </div>
            
            <a href="sellers_management.php" class="nav-link">
                <i class="bi bi-people"></i>
                Sellers Management
            </a>
            
            <div class="dropdown">
                <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-gear"></i>
                    General Settings
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="site_settings.php">Site Settings</a></li>
                    <li><a class="dropdown-item" href="email_settings.php">Email Settings</a></li>
                </ul>
            </div>
            
            <div class="dropdown">
                <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-gear"></i>
                    Users Data Settings
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="user_management.php">User Management</a></li>
                    <li><a class="dropdown-item" href="admin_management.php">Admin Management</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button class="hamburger d-md-none me-3" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0">Categories</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="https://via.placeholder.com/40x40/6c757d/ffffff?text=A" class="rounded-circle" alt="Admin">
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content-wrapper">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Car Specifications</a></li>
                    <li class="breadcrumb-item active">Category Management</li>
                </ol>
            </nav>

            <!-- Table Controls -->
            <div class="table-controls">
                <div class="d-flex align-items-center gap-3">
                    <label for="perPage">Show</label>
                    <select id="perPage" class="form-select" style="width: auto;" onchange="changePerPage()">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                    <span>entries</span>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <label for="search">Search:</label>
                    <input type="text" id="search" class="form-control" style="width: 200px;" 
                           value="<?php echo htmlspecialchars($search); ?>" onkeyup="searchCategories()">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus"></i> Add New Category
                    </button>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                <td>
                                    <button class="status-badge <?php echo $category['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>" 
                                            onclick="toggleStatus(<?php echo $category['id']; ?>, '<?php echo $category['status'] == 'active' ? 'inactive' : 'active'; ?>')">
                                        <?php echo ucfirst($category['status']); ?> <i class="bi bi-chevron-down"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['slug']); ?>')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">No categories found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Info and Controls -->
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                </div>
                
                <?php if ($total_pages > 1): ?>
                <nav>
                    <ul class="pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $per_page; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addCategoryForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="categoryName" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editCategoryForm">
                    <div class="modal-body">
                        <input type="hidden" id="editCategoryId" name="id">
                        <div class="mb-3">
                            <label for="editCategoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editCategoryName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCategorySlug" class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editCategorySlug" name="slug" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }

        function changePerPage() {
            const perPage = document.getElementById('perPage').value;
            const search = document.getElementById('search').value;
            window.location.href = `?page=1&per_page=${perPage}&search=${encodeURIComponent(search)}`;
        }

        let searchTimeout;
        function searchCategories() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const search = document.getElementById('search').value;
                const perPage = document.getElementById('perPage').value;
                window.location.href = `?page=1&per_page=${perPage}&search=${encodeURIComponent(search)}`;
            }, 500);
        }

        // Add Category
        document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });

        // Edit Category
        function editCategory(id, name, slug) {
            document.getElementById('editCategoryId').value = id;
            document.getElementById('editCategoryName').value = name;
            document.getElementById('editCategorySlug').value = slug;
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }

        document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'edit');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });

        // Delete Category
        function deleteCategory(id) {
            if (confirm('Are you sure you want to delete this category?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }

        // Toggle Status
        function toggleStatus(id, newStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', id);
            formData.append('status', newStatus);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>