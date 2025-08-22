<?php
if (!isset($page_title)) {
    $page_title = 'Admin Panel | CAR LISTO';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Admin Styles -->
    <link href="/carshowroom/admin/assets/css/admin-styles.css" rel="stylesheet">
    
    
    <!-- Page-specific styles -->
    <?php if (isset($custom_css)): ?>
    <style><?php echo $custom_css; ?></style>
    <?php endif; ?>
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        /* Highlight active menu item */
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2) !important;
            font-weight: 600;
        }
        
        /* Ensure proper spacing for main content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding-top: 70px;
            }
        }
    </style>
    
    <style>
        .sidebar {
            min-height: 100vh;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #c7d1da;
            padding: 0.75rem 1rem;
            margin: 0.2rem 0.5rem;
            border-radius: 0.35rem;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
        }
        .sidebar .nav-link i {
            opacity: 0.8;
        }
        .sidebar .nav-link.active i {
            opacity: 1;
        }
        .sidebar-heading {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #9e9e9e;
            font-weight: 600;
            padding: 1.5rem 1rem 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../../assets/images/logo-white.png" alt="CAR LISTO" height="30" class="d-inline-block align-text-top me-2">
                Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
