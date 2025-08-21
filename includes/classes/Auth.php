<?php
class Auth {
    private $conn;
    private $table = 'users';

    public function __construct($pdo) {
        $this->conn = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Register a new user
    public function register($username, $email, $password, $full_name = '', $phone = '', $role = 'user') {
        try {
            // Check if email already exists
            $query = "SELECT id FROM " . $this->table . " WHERE email = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Check if username already exists
            $query = "SELECT id FROM " . $this->table . " WHERE username = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username already exists'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $query = "INSERT INTO " . $this->table . " (username, email, full_name, phone, password_hash, role, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username, $email, $full_name, $phone, $hashed_password, $role]);
            
            if ($stmt->rowCount() > 0) {
                $user_id = $this->conn->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $full_name ?: $username;
                $_SESSION['user_role'] = $role;
                
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during registration'];
        }
    }

    // Login user
    public function login($email, $password) {
        try {
            error_log("Login attempt for email: " . $email);
            
            $query = "SELECT id, username, email, full_name, password_hash, role, status FROM " . $this->table . " WHERE email = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                $error = $this->conn->errorInfo();
                throw new Exception("Prepare failed: " . print_r($error, true));
            }
            
            $executed = $stmt->execute([$email]);
            
            if (!$executed) {
                $error = $stmt->errorInfo();
                throw new Exception("Execute failed: " . print_r($error, true));
            }
            
            $user = $stmt->fetch();
            
            if ($user) {
                error_log("User found: " . print_r($user, true));
                
                if (password_verify($password, $user['password_hash'])) {
                    if ($user['status'] !== 'active') {
                        error_log("Login failed: Account not active");
                        return ['success' => false, 'message' => 'Your account is not active'];
                    }
                    
                    // Update last login
                    $updateStmt = $this->conn->prepare("UPDATE " . $this->table . " SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    error_log("Login successful for user ID: " . $user['id']);
                    return ['success' => true, 'message' => 'Login successful'];
                } else {
                    error_log("Login failed: Invalid password for email: " . $email);
                }
            } else {
                error_log("Login failed: No user found with email: " . $email);
            }
            
            return ['success' => false, 'message' => 'Invalid email or password'];
            
        } catch (PDOException $e) {
            $errorMsg = "Database error in login: " . $e->getMessage() . "\n" . $e->getTraceAsString();
            error_log($errorMsg);
            return ['success' => false, 'message' => 'A database error occurred. Please try again.'];
        } catch (Exception $e) {
            $errorMsg = "Error in login: " . $e->getMessage() . "\n" . $e->getTraceAsString();
            error_log($errorMsg);
            return ['success' => false, 'message' => 'An error occurred during login. Please try again.'];
        }
    }

    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Check if user has specific role
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }

    // Get current user
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $query = "SELECT id, name, email, phone, role, status, last_login, created_at 
                     FROM " . $this->table . " 
                     WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }

    // Logout user
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        // Delete remember me cookie if exists
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        return true;
    }

    // Check if email exists
    public function emailExists($email) {
        try {
            $query = "SELECT id FROM " . $this->table . " WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Email exists check error: " . $e->getMessage());
            return false;
        }
    }

    // Update user password
    public function updatePassword($user_id, $new_password) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE " . $this->table . " SET password_hash = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$hashed_password, $user_id]);
        } catch (PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            return false;
        }
    }

    // Request password reset
    public function requestPasswordReset($email) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Check if user exists
        $query = "SELECT id FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetch();

        if (!$result) {
            return ['success' => true, 'message' => 'If an account exists with this email, a password reset link has been sent.'];
        }
        
        // Store token in database (you'll need to add a password_resets table)
        // For now, we'll just simulate the email sending
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
        
        // In a real application, you would send an email here
        // mail($email, 'Password Reset Request', 'Click here to reset your password: ' . $reset_link);
        
        return ['success' => true, 'message' => 'If an account exists with this email, a password reset link has been sent.'];
    }
    
    // Reset password
    public function resetPassword($token, $new_password) {
        // In a real application, you would verify the token from the database
        // For now, we'll just update the password directly
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table . " SET password_hash = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Password updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update password'];
    }
}
?>
