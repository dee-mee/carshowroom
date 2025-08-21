<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Backdrop -->
<div class="mobile-backdrop" id="mobileBackdrop"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-car me-2"></i>CarShowroom</h4>
    </div>
    
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'add-car.php' ? 'active' : '' ?>" href="add-car.php">
                <i class="fas fa-plus"></i>
                <span>Add Car</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'manage-cars.php' ? 'active' : '' ?>" href="manage-cars.php">
                <i class="fas fa-car"></i>
                <span>Manage Cars</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'featured-cars.php' ? 'active' : '' ?>" href="featured-cars.php">
                <i class="fas fa-star"></i>
                <span>Featured Cars</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'social-links.php' ? 'active' : '' ?>" href="social-links.php">
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
</nav>
