<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="fas fa-car"></i> CarShowroom</h4>
        <small>Admin Panel</small>
    </div>
    <nav class="nav-menu">
        <div class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Car Specifications -->
        <div class="nav-item">
            <a href="#" class="nav-link" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-cog"></i>
                <span>Car Specifications</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu">
                <a href="car-specs/categories.php" class="submenu-item">Category Management</a>
                <a href="car-specs/conditions.php" class="submenu-item">Condition Management</a>
                <a href="car-specs/brands.php" class="submenu-item">Brand Management</a>
                <a href="car-specs/models.php" class="submenu-item">Model Management</a>
                <a href="car-specs/body-types.php" class="submenu-item">Body Type Management</a>
                <a href="car-specs/fuel-types.php" class="submenu-item">Fuel Type Management</a>
                <a href="car-specs/transmission-types.php" class="submenu-item">Transmission Types</a>
            </div>
        </div>

        <!-- Other Menu Items (shortened for brevity) -->
        <div class="nav-item">
            <a href="pricing/index.php" class="nav-link">
                <i class="fas fa-tags"></i>
                <span>Pricing Ranges</span>
            </a>
        </div>

        <div class="nav-item">
            <a href="plans/index.php" class="nav-link">
                <i class="fas fa-list-alt"></i>
                <span>Plan Management</span>
            </a>
        </div>

        <div class="nav-item">
            <a href="cars/index.php" class="nav-link">
                <i class="fas fa-car"></i>
                <span>Car Management</span>
            </a>
        </div>

        <div class="nav-item">
            <a href="sellers/index.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Sellers Management</span>
            </a>
        </div>
    </nav>
</div>
