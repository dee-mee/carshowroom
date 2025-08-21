<?php
// Include sidebar
include 'includes/sidebar.php';
?>

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
            <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo htmlspecialchars($user_name); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/carshowroom/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Wrapper -->
<div class="main-content">
    <?php if (isset($page_title) && $page_title != 'Dashboard'): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2"><?php echo $page_title; ?></h1>
        </div>
    <?php endif; ?>
