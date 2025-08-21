<?php
require_once '../../config/database.php';
require_once '../../includes/classes/Auth.php';

session_start();
$auth = new Auth($conn);

// Check if user is logged in and is an admin
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    $_SESSION['error'] = 'You do not have permission to access the admin area.';
    header('Location: ../../login.php');
    exit();
}
?>
