<?php
// Function to check if current page is in submenu
function isActiveMenu($menuItems, $currentPage) {
    if (is_array($menuItems)) {
        return in_array($currentPage, $menuItems);
    }
    return $menuItems === $currentPage;
}

$currentPage = basename($_SERVER['PHP_SELF']);
$carSpecsPages = ['categories.php', 'conditions.php', 'brands.php', 'models.php', 'body-types.php', 'fuel-types.php', 'transmission-types.php'];
$carManagementPages = ['all-cars.php', 'featured-cars.php', 'pending-cars.php', 'published-cars.php', 'rejected-cars.php'];
$generalSettingsPages = ['logo.php', 'favicon.php', 'loader.php', 'breadcrumb.php', 'website-contents.php', 'payment-info.php', 'footer.php', 'social-links.php'];
$homePageSettings = ['header-banner.php', 'featured-section.php', 'latest-cars-section.php', 'testimonials.php', 'blog-section.php'];
$menuPages = ['about.php', 'contact.php', 'privacy-policy.php', 'terms-conditions.php'];
$emailSettings = ['smtp-config.php', 'email-templates.php'];
$blogPages = ['all-posts.php', 'add-post.php', 'blog-categories.php'];
$seoTools = ['meta-tags.php', 'sitemap.php', 'robots.php'];
$systemActivation = ['activation.php', 'backup.php'];
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="fas fa-car"></i> CarShowroom</h4>
        <small>Admin Panel</small>
    </div>
    <nav class="nav-menu">
        <!-- Dashboard -->
        <div class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Car Specifications -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($carSpecsPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-cog"></i>
                <span>Car Specifications</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($carSpecsPages, $currentPage) ? 'show' : ''; ?>">
                <a href="car-specs/categories.php" class="submenu-item <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">Category Management</a>
                <a href="car-specs/conditions.php" class="submenu-item <?php echo $currentPage === 'conditions.php' ? 'active' : ''; ?>">Condition Management</a>
                <a href="car-specs/brands.php" class="submenu-item <?php echo $currentPage === 'brands.php' ? 'active' : ''; ?>">Brand Management</a>
                <a href="car-specs/models.php" class="submenu-item <?php echo $currentPage === 'models.php' ? 'active' : ''; ?>">Model Management</a>
                <a href="car-specs/body-types.php" class="submenu-item <?php echo $currentPage === 'body-types.php' ? 'active' : ''; ?>">Body Type Management</a>
                <a href="car-specs/fuel-types.php" class="submenu-item <?php echo $currentPage === 'fuel-types.php' ? 'active' : ''; ?>">Fuel Type Management</a>
                <a href="car-specs/transmission-types.php" class="submenu-item <?php echo $currentPage === 'transmission-types.php' ? 'active' : ''; ?>">Transmission Types</a>
            </div>
        </div>

        <!-- Pricing Ranges -->
        <div class="nav-item">
            <a href="pricing/index.php" class="nav-link <?php echo strpos($currentPage, 'pricing/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Pricing Ranges</span>
            </a>
        </div>

        <!-- Plan Management -->
        <div class="nav-item">
            <a href="plans/index.php" class="nav-link <?php echo strpos($currentPage, 'plans/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-list-alt"></i>
                <span>Plan Management</span>
            </a>
        </div>

        <!-- Car Management -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($carManagementPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-car"></i>
                <span>Car Management</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($carManagementPages, $currentPage) ? 'show' : ''; ?>">
                <a href="cars/all-cars.php" class="submenu-item <?php echo $currentPage === 'all-cars.php' ? 'active' : ''; ?>">All Cars</a>
                <a href="cars/featured-cars.php" class="submenu-item <?php echo $currentPage === 'featured-cars.php' ? 'active' : ''; ?>">Featured Cars</a>
                <a href="cars/pending-cars.php" class="submenu-item <?php echo $currentPage === 'pending-cars.php' ? 'active' : ''; ?>">Pending Cars</a>
                <a href="cars/published-cars.php" class="submenu-item <?php echo $currentPage === 'published-cars.php' ? 'active' : ''; ?>">Published Cars</a>
                <a href="cars/rejected-cars.php" class="submenu-item <?php echo $currentPage === 'rejected-cars.php' ? 'active' : ''; ?>">Rejected Cars</a>
            </div>
        </div>

        <!-- Sellers Management -->
        <div class="nav-item">
            <a href="sellers/index.php" class="nav-link <?php echo strpos($currentPage, 'sellers/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Sellers Management</span>
            </a>
        </div>

        <!-- Car Approvals -->
        <div class="nav-item">
            <a href="approve-cars.php" class="nav-link <?php echo $currentPage === 'approve-cars.php' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i>
                <span>Car Approvals</span>
                <?php
                try {
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM cars WHERE status = 'pending'");
                    $pending_count = $stmt->fetch()['count'];
                    if ($pending_count > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-2"><?php echo $pending_count; ?></span>
                    <?php endif;
                } catch (Exception $e) {
                    // Ignore error
                }
                ?>
            </a>
        </div>

        <!-- General Settings -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($generalSettingsPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-cog"></i>
                <span>General Settings</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($generalSettingsPages, $currentPage) ? 'show' : ''; ?>">
                <a href="settings/logo.php" class="submenu-item <?php echo $currentPage === 'logo.php' ? 'active' : ''; ?>">Logo</a>
                <a href="settings/favicon.php" class="submenu-item <?php echo $currentPage === 'favicon.php' ? 'active' : ''; ?>">Favicon</a>
                <a href="settings/loader.php" class="submenu-item <?php echo $currentPage === 'loader.php' ? 'active' : ''; ?>">Loader</a>
                <a href="settings/breadcrumb.php" class="submenu-item <?php echo $currentPage === 'breadcrumb.php' ? 'active' : ''; ?>">Breadcrumb</a>
                <a href="settings/website-contents.php" class="submenu-item <?php echo $currentPage === 'website-contents.php' ? 'active' : ''; ?>">Website Contents</a>
                <a href="settings/payment-info.php" class="submenu-item <?php echo $currentPage === 'payment-info.php' ? 'active' : ''; ?>">Payment Informations</a>
                <a href="settings/footer.php" class="submenu-item <?php echo $currentPage === 'footer.php' ? 'active' : ''; ?>">Footer</a>
                <a href="settings/social-links.php" class="submenu-item <?php echo $currentPage === 'social-links.php' ? 'active' : ''; ?>">Social Link Settings</a>
            </div>
        </div>

        <!-- Home Page Settings -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($homePageSettings, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-home"></i>
                <span>Home Page Settings</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($homePageSettings, $currentPage) ? 'show' : ''; ?>">
                <a href="home/header-banner.php" class="submenu-item <?php echo $currentPage === 'header-banner.php' ? 'active' : ''; ?>">Header Banner</a>
                <a href="home/featured-section.php" class="submenu-item <?php echo $currentPage === 'featured-section.php' ? 'active' : ''; ?>">Featured Cars Section</a>
                <a href="home/latest-cars-section.php" class="submenu-item <?php echo $currentPage === 'latest-cars-section.php' ? 'active' : ''; ?>">Latest Cars Section</a>
                <a href="home/testimonials.php" class="submenu-item <?php echo $currentPage === 'testimonials.php' ? 'active' : ''; ?>">Testimonial Management</a>
                <a href="home/blog-section.php" class="submenu-item <?php echo $currentPage === 'blog-section.php' ? 'active' : ''; ?>">Blog Section</a>
            </div>
        </div>

        <!-- Menu Page Settings -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($menuPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-file-alt"></i>
                <span>Menu Page Settings</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($menuPages, $currentPage) ? 'show' : ''; ?>">
                <a href="pages/about.php" class="submenu-item <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">About Page</a>
                <a href="pages/contact.php" class="submenu-item <?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>">Contact Page</a>
                <a href="pages/privacy-policy.php" class="submenu-item <?php echo $currentPage === 'privacy-policy.php' ? 'active' : ''; ?>">Privacy Policy</a>
                <a href="pages/terms-conditions.php" class="submenu-item <?php echo $currentPage === 'terms-conditions.php' ? 'active' : ''; ?>">Terms & Conditions</a>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($emailSettings, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-at"></i>
                <span>Email Settings</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($emailSettings, $currentPage) ? 'show' : ''; ?>">
                <a href="email/smtp-config.php" class="submenu-item <?php echo $currentPage === 'smtp-config.php' ? 'active' : ''; ?>">SMTP Configuration</a>
                <a href="email/email-templates.php" class="submenu-item <?php echo $currentPage === 'email-templates.php' ? 'active' : ''; ?>">Email Templates</a>
            </div>
        </div>

        <!-- Language Settings -->
        <div class="nav-item">
            <a href="languages/index.php" class="nav-link <?php echo strpos($currentPage, 'languages/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-language"></i>
                <span>Language Settings</span>
            </a>
        </div>

        <!-- Blog -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($blogPages, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-file-alt"></i>
                <span>Blog</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($blogPages, $currentPage) ? 'show' : ''; ?>">
                <a href="blog/all-posts.php" class="submenu-item <?php echo $currentPage === 'all-posts.php' ? 'active' : ''; ?>">All Posts</a>
                <a href="blog/add-post.php" class="submenu-item <?php echo $currentPage === 'add-post.php' ? 'active' : ''; ?>">Add New Post</a>
                <a href="blog/categories.php" class="submenu-item <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">Categories</a>
            </div>
        </div>

        <!-- SEO Tools -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($seoTools, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-edit"></i>
                <span>SEO Tools</span>
                <i class="fas fa-angle-double-right dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($seoTools, $currentPage) ? 'show' : ''; ?>">
                <a href="seo/meta-tags.php" class="submenu-item <?php echo $currentPage === 'meta-tags.php' ? 'active' : ''; ?>">Meta Tags</a>
                <a href="seo/sitemap.php" class="submenu-item <?php echo $currentPage === 'sitemap.php' ? 'active' : ''; ?>">Sitemap</a>
                <a href="seo/robots.php" class="submenu-item <?php echo $currentPage === 'robots.php' ? 'active' : ''; ?>">Robots.txt</a>
            </div>
        </div>

        <!-- Payment History -->
        <div class="nav-item">
            <a href="payments/index.php" class="nav-link <?php echo strpos($currentPage, 'payments/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>Payment History</span>
            </a>
        </div>

        <!-- System Activation -->
        <div class="nav-item">
            <a href="#" class="nav-link <?php echo isActiveMenu($systemActivation, $currentPage) ? 'active' : ''; ?>" onclick="toggleSubmenu(this); return false;">
                <i class="fas fa-cog"></i>
                <span>System Activation</span>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </a>
            <div class="submenu <?php echo isActiveMenu($systemActivation, $currentPage) ? 'show' : ''; ?>">
                <a href="system/activation.php" class="submenu-item <?php echo $currentPage === 'activation.php' ? 'active' : ''; ?>">Activation</a>
                <a href="system/backup.php" class="submenu-item <?php echo $currentPage === 'backup.php' ? 'active' : ''; ?>">Generate Backup</a>
            </div>
        </div>
    </nav>
</div>

<script>
// Toggle submenu
function toggleSubmenu(element) {
    const parent = element.parentElement;
    const submenu = parent.querySelector('.submenu');
    const arrow = element.querySelector('.dropdown-arrow');
    
    // Toggle active class on the parent nav-item
    parent.classList.toggle('active');
    
    // Toggle show class on submenu
    if (submenu) {
        submenu.classList.toggle('show');
    }
    
    // Toggle arrow rotation
    if (arrow) {
        arrow.classList.toggle('fa-rotate-90');
    }
    
    // Prevent default anchor behavior
    return false;
}

// Close other submenus when one is opened
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') === '#') {
                // Close other open submenus
                navLinks.forEach(otherLink => {
                    if (otherLink !== this && otherLink.getAttribute('href') === '#') {
                        const otherParent = otherLink.parentElement;
                        const otherSubmenu = otherParent.querySelector('.submenu');
                        const otherArrow = otherLink.querySelector('.dropdown-arrow');
                        
                        otherParent.classList.remove('active');
                        if (otherSubmenu) otherSubmenu.classList.remove('show');
                        if (otherArrow) otherArrow.classList.remove('fa-rotate-90');
                    }
                });
            }
        });
    });
    
    // Initialize submenus based on current page
    const currentPage = '<?php echo $currentPage; ?>';
    const activeSubmenus = document.querySelectorAll('.nav-link.active');
    
    activeSubmenus.forEach(link => {
        const parent = link.parentElement;
        const submenu = parent.querySelector('.submenu');
        const arrow = link.querySelector('.dropdown-arrow');
        
        if (submenu) {
            parent.classList.add('active');
            submenu.classList.add('show');
            if (arrow) arrow.classList.add('fa-rotate-90');
        }
    });
});
</script>
