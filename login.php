<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log session status
error_log('Session started: ' . json_encode($_SESSION));

require_once 'config/database.php';
require_once 'includes/classes/Auth.php';

// Initialize error and success messages
$error = '';
$success = '';

try {
    // Create database connection
    require_once 'config/database.php';
    
    // Initialize Auth with PDO connection
    $auth = new Auth($conn);
    
    // Redirect if already logged in
    if ($auth->isLoggedIn()) {
        if ($auth->hasRole('admin')) {
            header('Location: /carshowroom/admin/dashboard.php');
            exit();
        } else {
            // For regular users and other roles
            header('Location: /carshowroom/user/dashboard.php');
            exit();
        }
        exit();
    }
    
    // Process login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password';
        } else {
            $result = $auth->login($email, $password);
            
            if ($result['success']) {
                if ($remember) {
                    // Set remember me cookie (30 days)
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    setcookie('remember_token', $token, [
                        'expires' => $expires,
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }
                
                // Redirect based on user role
                if ($auth->hasRole('admin')) {
                    header('Location: /carshowroom/admin/dashboard.php');
                } else {
                    header('Location: /carshowroom/user/dashboard.php');
                }
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
} catch (PDOException $e) {
    error_log('Database error in login.php: ' . $e->getMessage());
    $error = 'A database error occurred. Please try again later.';
} catch (Exception $e) {
    error_log('Error in login.php: ' . $e->getMessage());
    $error = 'An error occurred. Please try again.';
}

$page_title = 'Login | CAR LISTO';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="fw-bold">Login to Your Account</h1>
                <p class="lead">Access your saved vehicles, inquiries, and more</p>
            </div>
        </div>
    </div>
</section>

<!-- Login Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <form action="login.php" method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="password" class="form-label mb-0">Password *</label>
                                    <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                                </div>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">
                                    Please enter your password.
                                </div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary">Register here</a></p>
                        </div>
                        
                        <div class="divider my-4">
                            <span class="px-3 bg-white">OR</span>
                        </div>
                        
                        <div class="social-login">
                            <h6 class="text-center mb-3">Login with social media</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="#" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-google me-2"></i> Google
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="#" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-facebook me-2"></i> Facebook
                                    </a>
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
