            </div><!-- End of container-fluid -->
        </div><!-- End of main-content -->
    </div><!-- End of d-flex -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Toggle submenu
        function toggleSubmenu(element) {
            const submenu = element.nextElementSibling;
            const arrow = element.querySelector('.dropdown-arrow');
            
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('show');
                element.classList.toggle('expanded');
                
                // Toggle arrow rotation
                if (arrow) {
                    if (submenu.classList.contains('show')) {
                        arrow.style.transform = 'rotate(90deg)';
                    } else {
                        arrow.style.transform = 'rotate(0)';
                    }
                }
            }
        }
        
        // Close all other submenus when one is opened
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.getAttribute('onclick') && this.getAttribute('onclick').includes('toggleSubmenu')) {
                        e.preventDefault();
                        
                        // Close other open submenus
                        document.querySelectorAll('.submenu.show').forEach(openSubmenu => {
                            if (openSubmenu !== this.nextElementSibling) {
                                openSubmenu.classList.remove('show');
                                const parentLink = openSubmenu.previousElementSibling;
                                if (parentLink) {
                                    parentLink.classList.remove('expanded');
                                    const arrow = parentLink.querySelector('.dropdown-arrow');
                                    if (arrow) arrow.style.transform = 'rotate(0)';
                                }
                            }
                        });
                        
                        // Toggle current submenu
                        toggleSubmenu(this);
                    }
                });
            });
        });
    </script>
</body>
</html>
