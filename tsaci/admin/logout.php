<?php
require_once 'config.php';

// Log activity
if (isLoggedIn()) {
    logActivity('logout', 'admin_users', $_SESSION['admin_id'], 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;

