<?php
require_once 'config/database.php';
require_once 'includes/classes/Auth.php';

$auth = new Auth($conn);

// Redirect to login if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user = $auth->getUser();
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } else {
        $query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $name, $email, $phone, $user['id']);
        
        if ($stmt->execute()) {
            $success = 'Profile updated successfully';
            // Refresh user data
            $user = $auth->getUser();
        } else {
            $error = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters long';
    } else {
        // Verify current password
        $query = "SELECT password_hash FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $db_user = $result->fetch_assoc();
        
        if (password_verify($current_password, $db_user['password_hash'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password_hash = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashed_password, $user['id']);
            
            if ($stmt->execute()) {
                $success = 'Password updated successfully';
            } else {
                $error = 'Failed to update password';
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}

$page_title = 'My Account | CAR LISTO';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="fw-bold">My Account</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">My Account</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</section>

<!-- Account Section -->
<section class="py-5">
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <div class="position-relative d-inline-block">
                                <img src="https://via.placeholder.com/150" class="rounded-circle" width="100" alt="Profile">
                                <button class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0">
                                    <i class="bi bi-camera"></i>
                                </button>
                            </div>
                        </div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                        <p class="text-muted small mb-0"><?php echo ucfirst($user['role']); ?></p>
                        <p class="small text-muted">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                        
                        <hr class="my-3">
                        
                        <div class="list-group list-group-flush">
                            <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="tab">
                                <i class="bi bi-person me-2"></i> Profile
                            </a>
                            <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                                <i class="bi bi-lock me-2"></i> Change Password
                            </a>
                            <a href="#vehicles" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                                <i class="bi bi-car-front me-2"></i> My Vehicles
                            </a>
                            <a href="#favorites" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                                <i class="bi bi-heart me-2"></i> Favorites
                            </a>
                            <a href="#test-drives" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                                <i class="bi bi-calendar-check me-2"></i> Test Drives
                            </a>
                            <?php if ($auth->hasRole('dealer') || $auth->hasRole('admin')): ?>
                                <a href="dealer/dashboard.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-speedometer2 me-2"></i> Dealer Dashboard
                                </a>
                            <?php endif; ?>
                            <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Account Type</label>
                                            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password Tab -->
                    <div class="tab-pane fade" id="password">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3">
                                <h5 class="mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                        <div class="form-text">Use 8 or more characters with a mix of letters, numbers & symbols</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- My Vehicles Tab -->
                    <div class="tab-pane fade" id="vehicles">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">My Vehicles</h5>
                                <a href="inventory.php?add=vehicle" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Add Vehicle
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-5">
                                    <i class="bi bi-car-front display-4 text-muted mb-3"></i>
                                    <h5>No vehicles found</h5>
                                    <p class="text-muted">You haven't added any vehicles yet.</p>
                                    <a href="inventory.php?add=vehicle" class="btn btn-primary">
                                        <i class="bi bi-plus-lg me-1"></i> Add Your First Vehicle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Favorites Tab -->
                    <div class="tab-pane fade" id="favorites">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3">
                                <h5 class="mb-0">My Favorites</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-5">
                                    <i class="bi bi-heart display-4 text-muted mb-3"></i>
                                    <h5>No favorites yet</h5>
                                    <p class="text-muted">Save your favorite vehicles to see them here.</p>
                                    <a href="inventory.php" class="btn btn-primary">Browse Vehicles</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test Drives Tab -->
                    <div class="tab-pane fade" id="test-drives">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 py-3">
                                <h5 class="mb-0">Scheduled Test Drives</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-5">
                                    <i class="bi bi-calendar-check display-4 text-muted mb-3"></i>
                                    <h5>No test drives scheduled</h5>
                                    <p class="text-muted">Schedule a test drive to see it here.</p>
                                    <a href="inventory.php" class="btn btn-primary">Schedule Test Drive</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
