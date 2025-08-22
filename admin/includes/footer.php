    </div> <!-- End of container-fluid -->
    </div> <!-- End of main-content -->

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Dropdown Functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle submenu
        function toggleSubmenu(element) {
            const parent = element.parentElement;
            const submenu = parent.querySelector('.submenu');
            const arrow = element.querySelector('.dropdown-arrow');
            
            // Close all other open submenus in the same level
            const allSubmenus = document.querySelectorAll('.submenu');
            allSubmenus.forEach(menu => {
                if (menu !== submenu) {
                    menu.style.maxHeight = '0';
                    const parentItem = menu.closest('.nav-item');
                    if (parentItem) {
                        parentItem.classList.remove('active');
                        const parentArrow = parentItem.querySelector('.dropdown-arrow');
                        if (parentArrow) {
                            parentArrow.style.transform = 'rotate(0deg)';
                        }
                    }
                }
            });
            
            // Toggle current submenu
            if (submenu) {
                if (submenu.style.maxHeight === '0px' || !submenu.style.maxHeight) {
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                    parent.classList.add('active');
                    if (arrow) {
                        arrow.style.transform = 'rotate(90deg)';
                    }
                } else {
                    submenu.style.maxHeight = '0';
                    parent.classList.remove('active');
                    if (arrow) {
                        arrow.style.transform = 'rotate(0deg)';
                    }
                }
            }
        }
        
        // Make the function globally available
        window.toggleSubmenu = toggleSubmenu;
        
        // Toggle mobile sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Make the function globally available
        window.toggleSidebar = toggleSidebar;
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileToggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !mobileToggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
        
        // Highlight active menu item based on current URL
        function setActiveMenuItem() {
            const currentLocation = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link, .submenu-item');
            
            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentLocation.includes(href)) {
                    link.classList.add('active');
                    
                    // If this is a submenu item, open its parent
                    const submenuItem = link.closest('.submenu');
                    if (submenuItem) {
                        const parentItem = submenuItem.previousElementSibling;
                        if (parentItem) {
                            parentItem.classList.add('active');
                            submenuItem.style.maxHeight = submenuItem.scrollHeight + 'px';
                            const arrow = parentItem.querySelector('.dropdown-arrow');
                            if (arrow) {
                                arrow.style.transform = 'rotate(90deg)';
                            }
                        }
                    }
                }
            });
        }
        
        // Call the function when the page loads
        setActiveMenuItem();
    });
    </script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Custom scripts -->
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        // Toggle dropdown menus
        function toggleDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.matches('.user-dropdown, .user-dropdown *')) {
                const dropdowns = document.getElementsByClassName('dropdown-menu');
                for (let i = 0; i < dropdowns.length; i++) {
                    const openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        });
        
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Enable popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Initialize DataTables
        $(document).ready(function() {
            $('.table').DataTable({
                responsive: true,
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>
