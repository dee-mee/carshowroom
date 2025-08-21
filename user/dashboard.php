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

// Get dashboard statistics
try {
    // Total cars
    $stmt = $conn->prepare("SELECT COUNT(*) as total_cars FROM cars WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_cars = $stmt->fetchColumn();

    // Featured cars
    $stmt = $conn->prepare("SELECT COUNT(*) as featured_cars FROM cars WHERE user_id = ? AND featured = 1");
    $stmt->execute([$user_id]);
    $featured_cars = $stmt->fetchColumn();

    // Social links
    $stmt = $conn->prepare("SELECT COUNT(*) as social_links FROM social_links WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $social_links = $stmt->fetchColumn();

    // Recent cars
    $stmt = $conn->prepare("
        SELECT 
            c.id, 
            c.title, 
            IFNULL(m.name, '') AS make, 
            IFNULL(md.name, '') AS model, 
            c.year, 
            c.featured, 
            c.created_at 
        FROM cars c
        LEFT JOIN makes m ON c.make_id = m.id
        LEFT JOIN models md ON c.model_id = md.id
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Car Showroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
        }

        /* Sidebar Styles - EXACT match to image */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background:linear-gradient(180deg,rgb(34, 2, 78) 0%,rgb(46, 24, 70) 100%);
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
            background: linear-gradient(180deg,rgb(34, 2, 78) 0%,rgb(46, 24, 70) 100%);
            border-bottom: 1px solid #dee2e6;
            z-index: 999;
            display: flex;
            align-items: center;
            padding: 0 2rem;
            justify-content: flex-end;
            color: white;
        }

        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color:rgb(255, 255, 255);
        }

        .user-menu {
            display: flex;
            align-items: center;
        }
        #userDropdown {
    color: white !important;
    background: transparent; /* optional: keep background transparent */
    border: none;            /* optional: remove border */
}
.user-menu .dropdown-toggle {
    color: white !important;   /* username text */
}

.user-menu .dropdown-toggle::after {
    color: white !important;   /* arrow */
    border-top-color: white !important; /* makes the ▼ triangle white */
}

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background:rgb(233, 231, 238);
            color: white;
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

        /* Stats Cards - EXACT match to bold colors in image */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 0px; /* SHARP CORNERS like in image */
            padding: 1.5rem;
            box-shadow: none; /* No shadow like in image */
            position: relative;
            overflow: hidden;
            border: none;
        }

        /* EXACT BOLD COLORS from image */
        .stat-card.orange {
            background: #FF4500; /* Pure bold orange-red */
            color: white;
        }

        .stat-card.blue {
            background: #1E90FF; /* Pure bold blue */
            color: white;
        }

        .stat-card.purple {
            background: #8A2BE2; /* Pure bold purple */
            color: white;
        }

        .stat-card h3 {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }

        .stat-card .icon {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 3rem;
            opacity: 0.2;
        }

        .stat-card .btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            text-decoration: none;
            display: inline-block;
            margin-top: 0.5rem;
        }

        .stat-card .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        /* Table Section - Sharp corners like image */
        .table-section {
            background: white;
            border-radius: 0px; /* SHARP CORNERS */
            padding: 1.5rem;
            box-shadow: none; /* No shadow */
            border: 1px solid #dee2e6;
        }

        .table-section h5 {
            margin-bottom: 1rem;
            color: #495057;
            font-weight: 600;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.75rem;
        }

        .table tbody td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 0px; /* Sharp corners for badges too */
        }

        .badge.bg-danger {
            background-color: #dc3545 !important;
        }

        .btn-edit {
            background: #6f42c1;
            border: 1px solid #6f42c1;
            color: white;
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 0px; /* Sharp corners */
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-edit:hover {
            background: #5a359a;
            border-color: #5a359a;
            color: white;
        }

        .btn-edit i {
            margin-right: 0.25rem;
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

            .stats-grid {
                grid-template-columns: 1fr;
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
                <a class="nav-link active" href="dashboard.php">
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
        <button class="hamburger-menu" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
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
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card orange">
                <h3>Total Cars!</h3>
                <div class="number"><?php echo number_format($total_cars); ?></div>
                <a href="manage-cars.php" class="btn">View All</a>
                <i class="fas fa-image icon"></i>
            </div>
            
            <div class="stat-card blue">
                <h3>Featured Cars!</h3>
                <div class="number"><?php echo number_format($featured_cars); ?></div>
                <a href="featured-cars.php" class="btn">View All</a>
                <i class="fas fa-edit icon"></i>
            </div>
            
            <div class="stat-card purple">
                <h3>Total Social Links!</h3>
                <div class="number"><?php echo number_format($social_links); ?></div>
                <a href="social-links.php" class="btn">View All</a>
                <i class="fas fa-link icon"></i>
            </div>
        </div>

        <!-- Recent Cars Table -->
        <div class="table-section">
            <h5>Recently Added Cars By You</h5>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Year</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_cars) > 0): ?>
                            <?php foreach ($recent_cars as $car): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($car['title']); ?></td>
                                    <td><?php echo htmlspecialchars($car['make'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($car['model'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($car['year'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php if ($car['featured']): ?>
                                            <span class="badge bg-success">Yes</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit-car.php?id=<?php echo $car['id']; ?>" class="btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-car fa-3x mb-3"></i>
                                        <p>No cars found. Add your first car to get started!</p>
                                        <a href="add-car.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i> Add Your First Car
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    </script>
</body>
</html>