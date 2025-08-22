<?php
session_start();

// Check if admin is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
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

$admin_name = $_SESSION['user_name'] ?? 'Admin';

// Get dashboard statistics
try {
    // Total sellers (users with role 'user')
    $stmt = $pdo->query("SELECT COUNT(*) as total_sellers FROM users WHERE role = 'user'");
    $total_sellers = $stmt->fetch(PDO::FETCH_ASSOC)['total_sellers'];
    
    // Total subscription plans
    $stmt = $pdo->query("SELECT COUNT(*) as total_plans FROM plans");
    $total_plans = $stmt->fetch(PDO::FETCH_ASSOC)['total_plans'];
    
    // Total blog posts
    $stmt = $pdo->query("SELECT COUNT(*) as total_posts FROM blog_posts");
    $total_posts = $stmt->fetch(PDO::FETCH_ASSOC)['total_posts'];
    
    // Total social links
    $stmt = $pdo->query("SELECT COUNT(*) as total_social_links FROM admin_social_links");
    $total_social_links = $stmt->fetch(PDO::FETCH_ASSOC)['total_social_links'];
    
    // Total car categories
    $stmt = $pdo->query("SELECT COUNT(*) as total_categories FROM car_categories");
    $total_categories = $stmt->fetch(PDO::FETCH_ASSOC)['total_categories'];
    
    // Total testimonials
    $stmt = $pdo->query("SELECT COUNT(*) as total_testimonials FROM testimonials");
    $total_testimonials = $stmt->fetch(PDO::FETCH_ASSOC)['total_testimonials'];
    
    // Get recent cars from all users
    $stmt = $pdo->query("
        SELECT c.id, c.title, c.make_id, c.model_id, c.year, c.featured, c.created_at, u.username
        FROM cars c 
        JOIN users u ON c.user_id = u.id
        ORDER BY c.created_at DESC 
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get make and model names for the cars
    foreach ($stmt as &$car) {
        $make_stmt = $pdo->prepare("SELECT name FROM makes WHERE id = ?");
        $make_stmt->execute([$car['make_id']]);
        $car['make'] = $make_stmt->fetchColumn();
        
        $model_stmt = $pdo->prepare("SELECT name FROM models WHERE id = ?");
        $model_stmt->execute([$car['model_id']]);
        $car['model'] = $model_stmt->fetchColumn();
    }
    $recent_cars = $stmt;
    
} catch(PDOException $e) {
    // Initialize default values in case of error
    $total_sellers = 0;
    $total_plans = 0;
    $total_posts = 0;
    $total_social_links = 0;
    $total_categories = 0;
    $total_testimonials = 0;
    $recent_cars = [];
    
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Car Showroom</title>
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

        .submenu-item:hover {
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
            background: linear-gradient(180deg,rgb(34, 2, 78) 0%,rgb(46, 24, 70) 100%);;
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

        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
        }


        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 0;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            width: 300px;   /* fixed width */
            height: 200px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card-content {
            padding: 25px;
            position: relative;
            z-index: 2;
        }

        .stat-card.orange {
    background: linear-gradient(135deg, #ff4500 0%, #ff8800 100%);
    color: white;
}

.stat-card.blue {
    background: linear-gradient(135deg, #0066ff 0%, #00c6ff 100%);
    color: white;
}

.stat-card.green {
    background: linear-gradient(135deg, #00b140 0%, #00e676 100%);
    color: white;
}

.stat-card.purple {
    background: linear-gradient(135deg, #5a00ff 0%, #9b00ff 100%);
    color: white;
}

.stat-card.lime {
    background: linear-gradient(135deg, #32cd32 0%, #76ff03 100%);
    color: white;
}

.stat-card.red {
    background: linear-gradient(135deg, #e60026 0%, #ff1744 100%);
    color: white;
}


        .stat-icon {
            position: absolute;
            right: 25px;
            top: 25px;
            font-size: 3rem;
            opacity: 0.3;
        }

        .stat-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .stat-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .stat-link:hover {
            color: white;
            gap: 12px;
        }

        /* Recent Cars Section */
        .recent-cars {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin: 0;
        }

        .view-all-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .no-cars {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-cars i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .no-cars p {
            font-size: 1.1rem;
            margin-bottom: 25px;
        }

        .add-car-btn {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .add-car-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.4);
            color: white;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }

        .table th {
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }

        .car-thumbnail {
            width: 50px;
            height: 40px;
            border-radius: 6px;
            overflow: hidden;
            margin-right: 15px;
        }

        .car-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .car-info {
            display: flex;
            align-items: center;
        }

        .car-details h6 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .car-details small {
            color: #888;
            font-size: 0.8rem;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge.featured {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .badge.standard {
            background: #6c757d;
            color: white;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid #e0e6ed;
            background: white;
            color: #666;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .action-btn:hover {
            color: #333;
            border-color: #333;
            transform: translateY(-1px);
        }

        .action-btn.delete:hover {
            color: #dc3545;
            border-color: #dc3545;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-content {
                padding: 20px;
            }

            .user-info span {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .top-navbar {
                padding: 15px 20px;
            }

            .dashboard-content {
                padding: 15px;
            }

            .stat-number {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php require_once 'includes/navbar.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-content">

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card orange">
                    <div class="stat-card-content">
                        <i class="fas fa-users stat-icon"></i>
                        <div class="stat-title">Total Sellers!</div>
                        <div class="stat-number"><?php echo number_format($total_sellers); ?></div>
                        <a href="users.php" class="stat-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card blue">
                    <div class="stat-card-content">
                        <i class="fas fa-list-alt stat-icon"></i>
                        <div class="stat-title">Total Plans!</div>
                        <div class="stat-number"><?php echo number_format($total_plans); ?></div>
                        <a href="plans.php" class="stat-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-card-content">
                        <i class="fas fa-blog stat-icon"></i>
                        <div class="stat-title">Total Posts</div>
                        <div class="stat-number"><?php echo number_format($total_posts); ?></div>
                        <a href="blog.php" class="stat-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-card-content">
                        <i class="fas fa-link stat-icon"></i>
                        <div class="stat-title">Total Social Links!</div>
                        <div class="stat-number"><?php echo number_format($total_social_links); ?></div>
                        <a href="social-links.php" class="stat-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card lime">
                    <div class="stat-card-content">
                        <i class="fas fa-tags stat-icon"></i>
                        <div class="stat-title">Total Categories!</div>
                        <div class="stat-number"><?php echo number_format($total_categories); ?></div>
                        <a href="categories.php" class="stat-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div class="stat-card red">
                    <div class="stat-card-content">
                        <i class="fas fa-star stat-icon"></i>
                        <div class="stat-title">Total Testimonials!</div>
                        <div class="stat-number"><?php echo number_format($total_testimonials); ?></div>
                        <a href="testimonials.php" class="stat-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recently Added Cars -->
            <div class="recent-cars">
                <div class="section-header">
                    <h3 class="section-title">Recently Added Cars</h3>
                    <a href="cars.php" class="view-all-btn">View All</a>
                </div>
                
                <?php if (empty($recent_cars)): ?>
                    <div class="no-cars">
                        <i class="fas fa-car"></i>
                        <p>No cars added yet.</p>
                        <a href="add-car.php" class="add-car-btn">Add New Car</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Car</th>
                                    <th>Make & Model</th>
                                    <th>Year</th>
                                    <th>Posted By</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_cars as $car): ?>
                                    <tr>
                                        <td>
                                            <div class="car-info">
                                                <div class="car-thumbnail">
                                                    <img src="<?php echo !empty($car['featured_image']) ? htmlspecialchars($car['featured_image']) : 'assets/img/car-placeholder.jpg'; ?>" alt="Car">
                                                </div>
                                                <div class="car-details">
                                                    <h6><?php echo htmlspecialchars($car['title']); ?></h6>
                                                    <small><?php echo date('M d, Y', strtotime($car['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($car['make']); ?></div>
                                                <small style="color: #888;"><?php echo htmlspecialchars($car['model']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($car['year']); ?></td>
                                        <td><?php echo htmlspecialchars($car['username']); ?></td>
                                        <td>
                                            <?php if ($car['featured']): ?>
                                                <span class="badge featured">Featured</span>
                                            <?php else: ?>
                                                <span class="badge standard">Standard</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit-car.php?id=<?php echo $car['id']; ?>" class="action-btn">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this car?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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