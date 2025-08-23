<?php
require_once 'config/database.php';
require_once 'includes/classes/Auth.php';

$auth = new Auth($conn);
$auth->logout();

// Redirect to home page
header('Location: index.php');
exit();
?>
