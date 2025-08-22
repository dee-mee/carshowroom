    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-car"></i> CarShowroom</h4>
            <small>Admin Panel</small>
        </div>
        <nav class="nav-menu">
            <div class="nav-item">
                <a href="/carshowroom/admin/dashboard.php" class="nav-link <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link <?php echo ($active_page === 'car-specs') ? 'active' : ''; ?>" onclick="toggleSubmenu(this)">
                    <i class="fas fa-cog"></i>
                    <span>Car Specifications</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="/carshowroom/admin/car-specs/categories.php" class="submenu-item">Category Management</a>
                    <a href="/carshowroom/admin/car-specs/conditions.php" class="submenu-item">Condition Management</a>
                    <a href="#" class="submenu-item">Brand Management</a>
                    <a href="#" class="submenu-item">Model Management</a>
                    <a href="#" class="submenu-item">Body Type Management</a>
                    <a href="#" class="submenu-item">Fuel Type Management</a>
                    <a href="#" class="submenu-item">Transmission Type Management</a>
                </div>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Pricing Ranges</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-list-alt"></i>
                    <span>Plan Management</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                    <i class="fas fa-car"></i>
                    <span>Car Management</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item">All Cars</a>
                    <a href="#" class="submenu-item">Featured Cars</a>
                    <a href="#" class="submenu-item">Pending Cars</a>
                    <a href="#" class="submenu-item">Published Cars</a>
                    <a href="#" class="submenu-item">Rejected Cars</a>
                </div>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Sellers Management</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                    <i class="fas fa-cog"></i>
                    <span>General Settings</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item">Logo</a>
                    <a href="#" class="submenu-item">Favicon</a>
                    <a href="#" class="submenu-item">Loader</a>
                    <a href="#" class="submenu-item">Breadcrumb</a>
                    <a href="#" class="submenu-item">Website Contents</a>
                    <a href="#" class="submenu-item">Payment Informations</a>
                    <a href="#" class="submenu-item">Footer</a>
                    <a href="#" class="submenu-item">Social Link Settings</a>
                </div>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                    <i class="fas fa-home"></i>
                    <span>Home Page Settings</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item">Header Banner</a>
                    <a href="#" class="submenu-item">Featured Cars Section</a>
                    <a href="#" class="submenu-item">Latest Cars Section</a>
                    <a href="#" class="submenu-item">Testimonial Management</a>
                    <a href="#" class="submenu-item">Blog Section</a>
                </div>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                    <i class="fas fa-file-alt"></i>
                    <span>Menu Page Settings</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item">About Page</a>
                    <a href="#" class="submenu-item">Contact Page</a>
                    <a href="#" class="submenu-item">Privacy Policy</a>
                    <a href="#" class="submenu-item">Terms & Conditions</a>
                </div>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                    <i class="fas fa-at"></i>
                    <span>Email Settings</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item">SMTP Configuration</a>
                    <a href="#" class-="submenu-item">Email Templates</a>
                </div>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-language"></i>
                    <span>Language Settings</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                    <i class="fas fa-file-alt"></i>
                    <span>Blog</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item">All Posts</a>
                    <a href="#" class="submenu-item">Add New Post</a>
                    <a href="#" class="submenu-item">Categories</a>
                </div>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                    <i class="fas fa-edit"></i>
                    <span>SEO Tools</span>
                    <i class="fas fa-angle-double-right dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item">Meta Tags</a>
                    <a href="#" class="submenu-item">Sitemap</a>
                    <a href="#" class_name="submenu-item">Robots.txt</a>
                </div>
            </div>

            <div class="nav-item">
                <a href="/carshowroom/admin/payments/index.php" class="nav-link">
                    <i class="fas fa-credit-card"></i>
                    <span>Payment History</span>
                </a>
            </div>

            <div class="nav-item">
                <a href="#" class="nav-link" onclick="toggleSubmenu(this)">
                    <i class="fas fa-cog"></i>
                    <span>System Activation</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item">Activation</a>
                    <a href="/carshowroom/admin/system/backup.php" class="submenu-item">Generate Backup</a>
                </div>
            </div>
        </nav>
    </div>

    <script>
        function toggleSubmenu(link) {
            const submenu = link.nextElementSibling;
            const arrow = link.querySelector('.dropdown-arrow');

            if (submenu.style.maxHeight) {
                submenu.style.maxHeight = null;
                arrow.classList.remove('expanded');
            } else {
                // Close any other open submenus
                const openSubmenus = document.querySelectorAll('.submenu');
                openSubmenus.forEach(openSubmenu => {
                    if (openSubmenu !== submenu) {
                        openSubmenu.style.maxHeight = null;
                        const openArrow = openSubmenu.previousElementSibling.querySelector('.dropdown-arrow');
                        if (openArrow) {
                            openArrow.classList.remove('expanded');
                        }
                    }
                });

                submenu.style.maxHeight = submenu.scrollHeight + "px";
                arrow.classList.add('expanded');
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const activeLink = document.querySelector('.nav-link.active');
            if (activeLink) {
                const submenu = activeLink.closest('.submenu');
                if (submenu) {
                    const parentLink = submenu.previousElementSibling;
                    parentLink.classList.add('active');
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                    const arrow = parentLink.querySelector('.dropdown-arrow');
                    if (arrow) {
                        arrow.classList.add('expanded');
                    }
                }
            }
        });
    </script>