<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAR LISTO - Car Showroom</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-2">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="contact-info">
                        <span class="me-3"><i class="bi bi-telephone me-1"></i> +254 712 345 678</span>
                        <span><i class="bi bi-envelope me-1"></i> info@carlisto.co.ke</span>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="social-links">
                        <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <img src="assets/images/logo.png" alt="CAR LISTO" height="40" class="d-inline-block align-text-top me-2">
                CAR LISTO
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['inventory.php']) ? 'active' : ''; ?>" href="#" id="carsDropdown" role="button" data-bs-toggle="dropdown">
                            Cars
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="inventory.php">All Cars</a></li>
                            <li><a class="dropdown-item" href="inventory.php?filter=new">New Arrivals</a></li>
                            <li><a class="dropdown-item" href="inventory.php?filter=featured">Featured Cars</a></li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="inventory.php?add=vehicle">Sell Your Car</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">Inventory</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <?php
                // Check if user is logged in
                $isLoggedIn = isset($_SESSION['user_id']);
                $userName = $isLoggedIn ? $_SESSION['user_name'] ?? 'User' : '';
                $userRole = $isLoggedIn ? $_SESSION['user_role'] ?? 'user' : '';
                ?>
                
                <?php if ($isLoggedIn): ?>
                    <!-- User is logged in -->
                    <ul class="navbar-nav ms-auto">
                        <?php if ($userRole === 'admin' || $userRole === 'dealer'): ?>
                            <li class="nav-item">
                                <a href="admin/dashboard.php" class="nav-link">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="account.php"><i class="bi bi-person me-2"></i> My Account</a></li>
                                <li><a class="dropdown-item" href="favorites.php"><i class="bi bi-heart me-2"></i> Favorites</a></li>
                                <li><a class="dropdown-item" href="test-drives.php"><i class="bi bi-calendar-check me-2"></i> Test Drives</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                            </ul>
                        </li>
                        <li class="nav-item d-flex align-items-center ms-2">
                            <a href="inventory.php?add=vehicle" class="btn btn-warning btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> Sell Car
                            </a>
                        </li>
                    </ul>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <div class="d-flex ms-lg-3 mt-3 mt-lg-0">
                        <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                        <a href="register.php" class="btn btn-warning">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>