// Admin Layout JavaScript - Shared functionality for all admin pages

// Functions for sidebar and navbar
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('mobile-open');
}

function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('show');
}

// Submenu toggle function
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

// Initialize when DOM is loaded - with timeout to prevent blocking
document.addEventListener("DOMContentLoaded", function() {
    // Use setTimeout to prevent blocking the main thread
    setTimeout(function() {
        try {
            const activeLink = document.querySelector('.nav-link.active');
            if (activeLink) {
                const submenu = activeLink.closest('.submenu');
                if (submenu) {
                    const parentLink = submenu.previousElementSibling;
                    if (parentLink) {
                        parentLink.classList.add('active');
                        submenu.style.maxHeight = submenu.scrollHeight + "px";
                        const arrow = parentLink.querySelector('.dropdown-arrow');
                        if (arrow) {
                            arrow.classList.add('expanded');
                        }
                    }
                }
            }
        } catch (e) {
            console.warn('Error in submenu initialization:', e);
        }
    }, 100);

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const mobileToggle = document.querySelector('.mobile-toggle');
        
        if (window.innerWidth <= 768 && 
            sidebar && !sidebar.contains(event.target) && 
            mobileToggle && !mobileToggle.contains(event.target)) {
            sidebar.classList.remove('mobile-open');
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        const userDropdown = document.querySelector('.user-dropdown');
        
        if (dropdown && userDropdown && !userDropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });

    // Close sidebar on window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('mobile-open');
        }
    });

    // Auto-hide alerts after 5 seconds - with error handling
    setTimeout(function() {
        try {
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                alerts.forEach(function(alert) {
                    if (alert && alert.style) {
                        alert.style.transition = 'opacity 0.5s';
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            if (alert && alert.parentNode) {
                                alert.remove();
                            }
                        }, 500);
                    }
                });
            }
        } catch (e) {
            console.warn('Error in alert auto-hide:', e);
        }
    }, 5000);
});
