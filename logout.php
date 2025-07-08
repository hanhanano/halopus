<?php
require_once 'auth.php';

if (isLoggedIn()) {
    $admin = getCurrentAdmin();
    error_log("Logout: {$admin['username']} at " . date('Y-m-d H:i:s'));
}

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

logout();

header('Location: login.php?error=logout');
exit();
?>
