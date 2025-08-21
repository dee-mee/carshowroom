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

// Pagination and search parameters
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $entries_per_page;

// Build search query
$search_condition = 'WHERE c.user_id = ?';
$params = [$user_id];

if (!empty($search)) {
    $search_condition .= " AND (c.title LIKE ? OR m.name LIKE ? OR md.name LIKE ? OR c.year LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

try {
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM cars c
        LEFT JOIN makes m ON c.make_id = m.id
        LEFT JOIN models md ON c.model_id = md.id
        $search_condition
    ";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];

    // Get cars with pagination
    $sql = "
        SELECT 
            c.id,
            c.title,
            COALESCE(m.name, 'Unknown') as make_name,
            COALESCE(md.name, 'Unknown Model') as model_name,
            c.year,
            c.featured,
            c.status,
            c.created_at
        FROM cars c
        LEFT JOIN makes m ON c.make_id = m.id
        LEFT JOIN models md ON c.model_id = md.id
        $search_condition
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $entries_per_page;
    $params[] = $offset;
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_pages = ceil($total_records / $entries_per_page);
    $start_entry = $offset + 1;
    $end_entry = min($offset + $entries_per_page, $total_records);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Car Showroom</title>
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

        /* Table Section */
        .table-section {
            background: white;
            border-radius: 0px;
            padding: 0;
            box-shadow: none;
            border: 1px solid #dee2e6;
        }

        /* Table Controls */
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .entries-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 14px;
            color: #495057;
        }

        .entries-control select {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 14px;
            background: white;
        }

        .search-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 14px;
            color: #495057;
        }

        .search-control input {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 0.5rem;
            width: 200px;
            font-size: 14px;
        }

        .search-control input:focus {
            border-color: rgb(34, 2, 78);
            outline: none;
            box-shadow: 0 0 0 2px rgba(34, 2, 78, 0.25);
        }

        /* Table Styles - Exact match to image */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background: white;
        }

        .data-table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            text-align: left;
            border-right: 1px solid #dee2e6;
        }

        .data-table thead th:last-child {
            border-right: none;
        }

        .data-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .data-table tbody td:last-child {
            border-right: none;
        }

        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Status and Featured Badges - Exact match to image */
        .featured-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }

        .featured-no {
            background-color: #dc3545;
        }

        .featured-yes {
            background-color: #28a745;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
            min-width: 60px;
            text-align: center;
            position: relative;
        }

        .status-active {
            background-color: #28a745;
        }

        .status-inactive {
            background-color: #6c757d;
        }

        /* Dropdown arrow for status */
        .status-badge::after {
            content: "▼";
            font-size: 8px;
            margin-left: 0.5rem;
        }

        /* Action Buttons - Exact match to image */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .btn-edit {
            background-color: rgb(34, 2, 78);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0px;
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background-color: rgb(46, 24, 70);
            color: white;
        }

        .btn-delete {
            background-color: rgb(34, 2, 78);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 50%;
            font-size: 12px;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #dc3545;
        }

        /* Pagination */
        .pagination-section {
            padding: 1.5rem 2rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .showing-entries {
            font-size: 14px;
            color: #495057;
        }

        .pagination {
            margin: 0;
        }

        .page-link {
            color: #495057;
            border-color: #dee2e6;
            padding: 0.5rem 0.75rem;
        }

        .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #495057;
        }

        .page-item.active .page-link {
            background-color: rgb(34, 2, 78);
            border-color: rgb(34, 2, 78);
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

            .pagination-section {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
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
                <a class="nav-link active" href="manage-cars.php">
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
        <!-- Cars Management Table -->
        <div class="table-section">
            <!-- Table Controls -->
            <div class="table-controls">
                <div class="entries-control">
                    <span>Show</span>
                    <select id="entriesSelect" onchange="changeEntries()">
                        <option value="10" <?php echo $entries_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $entries_per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $entries_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $entries_per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                    <span>entries</span>
                </div>
                <div class="search-control">
                    <span>Search:</span>
                    <input type="text" id="searchInput" placeholder="" value="<?php echo htmlspecialchars($search); ?>" onkeypress="handleSearch(event)">
                </div>
            </div>

            <!-- Data Table -->
            <table class="data-table">
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
                            <td colspan="7" style="text-align: center; padding: 3rem; color: #6c757d;">
                                <i class="fas fa-car fa-3x mb-3" style="display: block; opacity: 0.3;"></i>
                                <div>No cars found</div>
                                <small>Try adjusting your search terms or add a new car</small>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cars as $car): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($car['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($car['make_name']); ?></td>
                                <td><?php echo htmlspecialchars($car['model_name']); ?></td>
                                <td><?php echo htmlspecialchars($car['year'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="featured-badge <?php echo $car['featured'] ? 'featured-yes' : 'featured-no'; ?>">
                                        <?php echo $car['featured'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $car['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ucfirst($car['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit-car.php?id=<?php echo $car['id']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button class="btn-delete" onclick="deleteCar(<?php echo $car['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Section -->
            <div class="pagination-section">
                <div class="showing-entries">
                    <?php if ($total_records > 0): ?>
                        Showing <?php echo $start_entry; ?> to <?php echo $end_entry; ?> of <?php echo $total_records; ?> entries
                    <?php else: ?>
                        Showing 0 to 0 of 0 entries
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                            </li>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
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
        });

        function changeEntries() {
            const entries = document.getElementById('entriesSelect').value;
            const search = document.getElementById('searchInput').value;
            window.location.href = `?entries=${entries}&search=${encodeURIComponent(search)}`;
        }

        function handleSearch(event) {
            if (event.key === 'Enter') {
                const search = document.getElementById('searchInput').value;
                const entries = document.getElementById('entriesSelect').value;
                window.location.href = `?entries=${entries}&search=${encodeURIComponent(search)}`;
            }
        }

        function deleteCar(carId) {
            if (confirm('Are you sure you want to delete this car? This action cannot be undone.')) {
                // Create a form to submit the delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete-car.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'car_id';
                input.value = carId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>