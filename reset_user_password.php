<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// New password
$new_password = 'user123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$email = 'user@example.com';

try {
    // Update the password
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $result = $stmt->execute([$hashed_password, $email]);
    
    if ($result) {
        echo "Password has been reset successfully.\n";
        echo "New password: " . $new_password . "\n";
    } else {
        echo "Failed to reset password.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
