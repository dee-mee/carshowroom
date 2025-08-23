<?php
require_once 'config/database.php';
require_once 'includes/classes/Auth.php';

$auth = new Auth($conn);
$error = '';
$success = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: user/dashboard.php');
    exit();
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validate form data
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!$terms) {
        $error = 'You must accept the terms and conditions';
    } else {
        // Attempt to register the user
        $result = $auth->register($name, $email, $password, $phone);
        
        if ($result['success']) {
            $success = 'Registration successful! Redirecting to your account...';
            // Redirect after 2 seconds
            header('Refresh: 2; URL=user/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}

$page_title = 'Create Account | CAR LISTO';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="page-header bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="fw-bold">Create an Account</h1>
                <p class="lead">Join CAR LISTO to save your favorite vehicles and more</p>
            </div>
        </div>
    </div>
</section>

<!-- Registration Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <form action="register.php" method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                                    <div class="invalid-feedback">
                                        Please enter your full name.
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    <div class="invalid-feedback">
                                        Please enter a valid email address.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">At least 8 characters</div>
                                    <div class="invalid-feedback">
                                        Please enter a password.
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="invalid-feedback">
                                        Please confirm your password.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required
                                    <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                                    <a href="privacy.php" target="_blank">Privacy Policy</a> *
                                </label>
                                <div class="invalid-feedback">
                                    You must agree to the terms and conditions.
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? <a href="login.php" class="text-primary">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Password Strength Meter -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (password && confirmPassword) {
        // Check password match
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        password.onchange = validatePassword;
        confirmPassword.onkeyup = validatePassword;
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
