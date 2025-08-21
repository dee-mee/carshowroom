<?php
require_once '../config/database.php';
require_once '../includes/classes/Auth.php';

$auth = new Auth($conn);

// Redirect to login if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = $auth->getUser();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $error = 'New password and confirm password do not match.';
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'This email is already registered to another account.';
        } else {
            // Update user information
            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Update basic info
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $phone, $user['id']);
                $stmt->execute();
                
                // Update password if provided
                if (!empty($newPassword)) {
                    // Verify current password
                    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    
                    if (password_verify($currentPassword, $result['password_hash'])) {
                        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $stmt->bind_param("si", $newPasswordHash, $user['id']);
                        $stmt->execute();
                    } else {
                        throw new Exception('Current password is incorrect.');
                    }
                }
                
                // Update session
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                $conn->commit();
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $user = $auth->getUser();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage() ?: 'An error occurred while updating your profile. Please try again.';
            }
        }
    }
}

$page_title = 'My Profile | CAR LISTO';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="avatar-circle bg-primary text-white d-inline-flex align-items-center justify-content-center">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="saved-vehicles.php"><i class="fas fa-heart me-2"></i>Saved Vehicles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inquiries.php"><i class="fas fa-envelope me-2"></i>My Inquiries</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="test-drives.php"><i class="fas fa-car me-2"></i>Test Drives</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="btn btn-outline-danger w-100" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">My Profile</h2>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="POST" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter your full name.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Change Password</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <div class="form-text">Leave blank to keep current password</div>
                            </div>
                            <div class="col-md-6"></div>
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">At least 8 characters</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered bg-white mb-0">
                            <tbody>
                                <tr>
                                    <th class="w-25">Member Since</th>
                                    <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Last Login</th>
                                    <td>
                                        <?php 
                                        if ($user['last_login']) {
                                            echo date('F j, Y g:i A', strtotime($user['last_login']));
                                        } else {
                                            echo 'First login';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Account Status</th>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Account Type</th>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    font-size: 36px;
    font-weight: bold;
    line-height: 1;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
}
.nav-link {
    color: #495057;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    margin-bottom: 0.25rem;
}
.nav-link:hover, .nav-link.active {
    background-color: #f8f9fa;
    color: #0d6efd;
}
</style>

<?php include 'includes/footer.php'; ?>
