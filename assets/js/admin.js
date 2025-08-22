// Admin Dashboard JavaScript Functions

// Toggle sidebar on mobile devices
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    }
}

// Toggle user dropdown menu
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
}

// Toggle submenu items in sidebar
function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    const arrow = element.querySelector('.dropdown-arrow');
    
    if (submenu.classList.contains('show')) {
        submenu.classList.remove('show');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    } else {
        // Close all other submenus first
        const allSubmenus = document.querySelectorAll('.submenu');
        const allArrows = document.querySelectorAll('.dropdown-arrow');
        
        allSubmenus.forEach(sub => sub.classList.remove('show'));
        allArrows.forEach(arr => arr.style.transform = 'rotate(0deg)');
        
        // Open current submenu
        submenu.classList.add('show');
        if (arrow) arrow.style.transform = 'rotate(90deg)';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (dropdown && userDropdown && !userDropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

// Initialize page functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Initialize tooltips if any
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        // Bootstrap tooltip initialization would go here if using Bootstrap
    });
    
    // Handle responsive sidebar on window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth > 768) {
            // Desktop view - ensure sidebar is visible
            if (sidebar) sidebar.classList.remove('collapsed');
            if (mainContent) mainContent.classList.remove('expanded');
        }
    });
});

// Utility function for AJAX requests
function makeAjaxRequest(url, method = 'GET', data = null, callback = null) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    
    if (method === 'POST') {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                if (callback) callback(xhr.responseText);
            } else {
                console.error('AJAX request failed:', xhr.status);
            }
        }
    };
    
    xhr.send(data);
}

// Show loading spinner
function showLoading(element) {
    if (element) {
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        element.disabled = true;
    }
}

// Hide loading spinner
function hideLoading(element, originalText) {
    if (element) {
        element.innerHTML = originalText;
        element.disabled = false;
    }
}

// Confirm delete action
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Show success message
function showSuccessMessage(message) {
    showMessage(message, 'success');
}

// Show error message
function showErrorMessage(message) {
    showMessage(message, 'error');
}

// Generic show message function
function showMessage(message, type = 'info') {
    const messageContainer = document.createElement('div');
    messageContainer.className = `alert alert-${type} alert-dismissible fade show`;
    messageContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Insert at the top of the main content
    const mainContent = document.querySelector('.dashboard-content');
    if (mainContent) {
        mainContent.insertBefore(messageContainer, mainContent.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            messageContainer.remove();
        }, 5000);
    }
}