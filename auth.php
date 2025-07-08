<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}


function getCurrentAdmin() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'full_name' => $_SESSION['admin_full_name'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null,
        'role' => $_SESSION['admin_role'] ?? null
    ];
}


function requireLogin() {
    if (!isLoggedIn()) {
                $currentPath = $_SERVER['REQUEST_URI'];
        $loginPath = 'login.php';
        
                if (strpos($currentPath, '/pages/') !== false) {
            $loginPath = '../login.php';
        }
        
        header("Location: $loginPath");
        exit();
    }
}


function hasRole($role) {
    $admin = getCurrentAdmin();
    return $admin && $admin['role'] === $role;
}


function logout() {
        $_SESSION = array();
    
        if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
        session_destroy();
}

$currentFile = basename($_SERVER['PHP_SELF']);
$loginPages = ['login.php', 'process_login.php', 'setup_simple.php'];

if (!in_array($currentFile, $loginPages)) {
    requireLogin();
}
?>
