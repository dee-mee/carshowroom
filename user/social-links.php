<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /carshowroom/login.php");
    exit();
}

// Database connection
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Initialize variables
$success = '';
$error = '';

// Get existing social links
try {
    $stmt = $conn->prepare("SELECT * FROM social_links WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $social_links = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no record exists, create default values
    if (!$social_links) {
        $social_links = [
            'facebook_url' => '',
            'twitter_url' => '',
            'linkedin_url' => '',
            'instagram_url' => '',
            'youtube_url' => '',
            'facebook_enabled' => 0,
            'twitter_enabled' => 0,
            'linkedin_enabled' => 0,
            'instagram_enabled' => 0,
            'youtube_enabled' => 0
        ];
    }
} catch (PDOException $e) {
    $error = 'Error loading social links: ' . $e->getMessage();
    $social_links = [
        'facebook_url' => '',
        'twitter_url' => '',
        'linkedin_url' => '',
        'instagram_url' => '',
        'youtube_url' => '',
        'facebook_enabled' => 0,
        'twitter_enabled' => 0,
        'linkedin_enabled' => 0,
        'instagram_enabled' => 0,
        'youtube_enabled' => 0
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $facebook_url = trim($_POST['facebook_url'] ?? '');
    $twitter_url = trim($_POST['twitter_url'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $instagram_url = trim($_POST['instagram_url'] ?? '');
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    
    $facebook_enabled = isset($_POST['facebook_enabled']) ? 1 : 0;
    $twitter_enabled = isset($_POST['twitter_enabled']) ? 1 : 0;
    $linkedin_enabled = isset($_POST['linkedin_enabled']) ? 1 : 0;
    $instagram_enabled = isset($_POST['instagram_enabled']) ? 1 : 0;
    $youtube_enabled = isset($_POST['youtube_enabled']) ? 1 : 0;
    
    try {
        // Check if record exists
        $check_stmt = $conn->prepare("SELECT id FROM social_links WHERE user_id = ?");
        $check_stmt->execute([$user_id]);
        $exists = $check_stmt->fetch();
        
        if ($exists) {
            // Update existing record
            $stmt = $conn->prepare("
                UPDATE social_links SET 
                facebook_url = ?, twitter_url = ?, linkedin_url = ?, instagram_url = ?, youtube_url = ?,
                facebook_enabled = ?, twitter_enabled = ?, linkedin_enabled = ?, instagram_enabled = ?, youtube_enabled = ?,
                updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $facebook_url, $twitter_url, $linkedin_url, $instagram_url, $youtube_url,
                $facebook_enabled, $twitter_enabled, $linkedin_enabled, $instagram_enabled, $youtube_enabled,
                $user_id
            ]);
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO social_links (
                    user_id, facebook_url, twitter_url, linkedin_url, instagram_url, youtube_url,
                    facebook_enabled, twitter_enabled, linkedin_enabled, instagram_enabled, youtube_enabled,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $user_id, $facebook_url, $twitter_url, $linkedin_url, $instagram_url, $youtube_url,
                $facebook_enabled, $twitter_enabled, $linkedin_enabled, $instagram_enabled, $youtube_enabled
            ]);
        }
        
        $success = 'Social links updated successfully!';
        
        // Update local array with new values
        $social_links = [
            'facebook_url' => $facebook_url,
            'twitter_url' => $twitter_url,
            'linkedin_url' => $linkedin_url,
            'instagram_url' => $instagram_url,
            'youtube_url' => $youtube_url,
            'facebook_enabled' => $facebook_enabled,
            'twitter_enabled' => $twitter_enabled,
            'linkedin_enabled' => $linkedin_enabled,
            'instagram_enabled' => $instagram_enabled,
            'youtube_enabled' => $youtube_enabled
        ];
        
    } catch (PDOException $e) {
        $error = 'Error updating social links: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Links - Car Showroom</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
        }

        /* Sidebar Styles - EXACT match to add-car.php */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand h4 {
            color: white;
            margin: 0;
            font-weight: 600;
        }

        .sidebar .nav {
            padding: 1rem 0;
        }

        .sidebar .nav-item {
            margin: 0.25rem 0;
        }

        .sidebar .nav-link {
            color: rgb(255, 255, 255);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s;
            border: none;
            background: none;
            display: flex;
            align-items: center;
            text-decoration: none;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid white;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }

        /* Top Navigation */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            height: 60px;
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            border-bottom: 1px solid #dee2e6;
            z-index: 999;
            display: flex;
            align-items: center;
            padding: 0 2rem;
            justify-content: space-between;
            color: white;
        }

        .breadcrumb-nav {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .breadcrumb-nav a {
            color: white;
            text-decoration: none;
        }

        .hamburger-menu {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: rgb(255, 255, 255);
        }

        .user-menu {
            display: flex;
            align-items: center;
        }

        #userDropdown {
            color: white !important;
            background: transparent;
            border: none;
        }

        .user-menu .dropdown-toggle {
            color: white !important;
        }

        .user-menu .dropdown-toggle::after {
            color: white !important;
            border-top-color: white !important;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgb(233, 231, 238);
            color: rgb(34, 2, 78);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.5rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            margin-top: 60px;
            padding: 2rem;
            min-height: calc(100vh - 60px);
            background-color: #f8f9fa;
        }

        /* Page Header */
        .page-header {
            background: white;
            border-radius: 0px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #dee2e6;
        }

        .page-header h1 {
            color: #495057;
            font-weight: 600;
            margin: 0;
            font-size: 2rem;
        }

        .breadcrumb-text {
            color: #6c757d;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .breadcrumb-text a {
            color: rgb(34, 2, 78);
            text-decoration: none;
        }

        .breadcrumb-text a:hover {
            text-decoration: underline;
        }

        /* Social Links Form */
        .social-links-form {
            background: white;
            border-radius: 0px;
            padding: 3rem;
            border: 1px solid #dee2e6;
            max-width: 800px;
            margin: 0 auto;
        }

        .social-link-row {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .social-link-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .social-label {
            width: 120px;
            font-weight: 600;
            color: #495057;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }

        .social-label i {
            width: 24px;
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        .social-input {
            flex: 1;
            margin: 0 2rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            border-color: rgb(34, 2, 78);
            box-shadow: 0 0 0 0.2rem rgba(34, 2, 78, 0.25);
            background-color: white;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            border-radius: 30px;
            transition: 0.3s;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 24px;
            width: 24px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toggle-switch input:checked + .toggle-slider {
            background-color: rgb(34, 2, 78);
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }

        /* Brand Colors */
        .facebook .social-label { color: #1877F2; }
        .twitter .social-label { color: #1DA1F2; }
        .linkedin .social-label { color: #0077B5; }
        .instagram .social-label { color: #E4405F; }
        .youtube .social-label { color: #FF0000; }

        /* Submit Button */
        .submit-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f3f4;
            text-align: center;
        }

        .btn-submit {
            background: linear-gradient(180deg, rgb(34, 2, 78) 0%, rgb(46, 24, 70) 100%);
            border: none;
            border-radius: 25px;
            padding: 0.875rem 3rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: white;
            font-size: 1rem;
            min-width: 200px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: linear-gradient(180deg, rgb(46, 24, 70) 0%, rgb(34, 2, 78) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 2, 78, 0.3);
            color: white;
        }

        /* Alerts */
        .alert {
            border-radius: 0px;
            border: none;
            font-weight: 500;
            margin-bottom: 2rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .top-navbar {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .hamburger-menu {
                display: block;
            }

            .social-links-form {
                padding: 2rem 1rem;
            }

            .social-link-row {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .social-label {
                width: auto;
                justify-content: center;
            }

            .social-input {
                margin: 0;
            }

            .toggle-switch {
                align-self: center;
            }
        }

        .mobile-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .mobile-backdrop.show {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Backdrop -->
    <div class="mobile-backdrop" id="mobileBackdrop"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fas fa-car me-2"></i>CarShowroom</h4>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add-car.php">
                    <i class="fas fa-plus"></i>
                    <span>Add Car</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage-cars.php">
                    <i class="fas fa-car"></i>
                    <span>Manage Cars</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="featured-cars.php">
                    <i class="fas fa-star"></i>
                    <span>Featured Cars</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="social-links.php">
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
    </div>

    <!-- Top Navigation -->
    <nav class="top-navbar">
        <div class="d-flex align-items-center">
            <button class="hamburger-menu" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
            </div>
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <?php echo htmlspecialchars($user_name); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/carshowroom/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-link me-2"></i>Social Links</h1>
            <div class="breadcrumb-text">
                <a href="dashboard.php">Dashboard</a> > Social Links
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Social Links Form -->
        <div class="social-links-form">
            <form method="POST">
                
                <!-- Facebook -->
                <div class="social-link-row facebook">
                    <div class="social-label">
                        <i class="fab fa-facebook"></i>
                        Facebook
                    </div>
                    <div class="social-input">
                        <input type="url" class="form-control" name="facebook_url" 
                               value="<?php echo htmlspecialchars($social_links['facebook_url']); ?>" 
                               placeholder="https://www.facebook.com/">
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="facebook_enabled" 
                               <?php echo $social_links['facebook_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- Twitter -->
                <div class="social-link-row twitter">
                    <div class="social-label">
                        <i class="fab fa-twitter"></i>
                        Twitter
                    </div>
                    <div class="social-input">
                        <input type="url" class="form-control" name="twitter_url" 
                               value="<?php echo htmlspecialchars($social_links['twitter_url']); ?>" 
                               placeholder="https://twitter.com/">
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="twitter_enabled" 
                               <?php echo $social_links['twitter_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- LinkedIn -->
                <div class="social-link-row linkedin">
                    <div class="social-label">
                        <i class="fab fa-linkedin"></i>
                        LinkedIn
                    </div>
                    <div class="social-input">
                        <input type="url" class="form-control" name="linkedin_url" 
                               value="<?php echo htmlspecialchars($social_links['linkedin_url']); ?>" 
                               placeholder="https://www.linkedin.com/">
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="linkedin_enabled" 
                               <?php echo $social_links['linkedin_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- Instagram -->
                <div class="social-link-row instagram">
                    <div class="social-label">
                        <i class="fab fa-instagram"></i>
                        Instagram
                    </div>
                    <div class="social-input">
                        <input type="url" class="form-control" name="instagram_url" 
                               value="<?php echo htmlspecialchars($social_links['instagram_url']); ?>" 
                               placeholder="https://www.instagram.com/">
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="instagram_enabled" 
                               <?php echo $social_links['instagram_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- YouTube -->
                <div class="social-link-row youtube">
                    <div class="social-label">
                        <i class="fab fa-youtube"></i>
                        YouTube
                    </div>
                    <div class="social-input">
                        <input type="url" class="form-control" name="youtube_url" 
                               value="<?php echo htmlspecialchars($social_links['youtube_url']); ?>" 
                               placeholder="https://www.youtube.com/">
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="youtube_enabled" 
                               <?php echo $social_links['youtube_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="submit-section">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save me-2"></i>
                        SUBMIT
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileBackdrop = document.getElementById('mobileBackdrop');
            
            // Toggle sidebar on mobile
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.toggle('show');
                    mobileBackdrop.classList.toggle('show');
                });
            }
            
            // Close sidebar when clicking backdrop
            mobileBackdrop.addEventListener('click', function() {
                sidebar.classList.remove('show');
                mobileBackdrop.classList.remove('show');
            });
            
            // Close sidebar on window resize if mobile
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('show');
                    mobileBackdrop.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>