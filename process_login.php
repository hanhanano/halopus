<?php
session_start();
require_once 'config/koneksi.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
} elseif (isset($_SESSION['member_logged_in']) && $_SESSION['member_logged_in'] === true) {
    header('Location: member/dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$user_type = $_POST['user_type'] ?? '';

if ($user_type === 'admin') {
        $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
        if (empty($username) || empty($password)) {
        header('Location: login.php?error=empty_fields');
        exit();
    }
    
    try {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            header('Location: login.php?error=invalid_credentials');
            exit();
        }
        
                if (!password_verify($password, $admin['password'])) {
            header('Location: login.php?error=invalid_credentials');
            exit();
        }
        
                $stmt = $pdo->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$admin['id']]);
        
                $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_full_name'] = $admin['full_name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['login_time'] = time();
        
                error_log("Admin login successful: {$admin['username']} ({$admin['role']}) at " . date('Y-m-d H:i:s'));
        
                header('Location: index.php');
        exit();
        
    } catch(PDOException $e) {
        error_log("Admin login error: " . $e->getMessage());
        header('Location: login.php?error=database_error');
        exit();
    }
    
} elseif ($user_type === 'member') {
        $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
        if (empty($email) || empty($password)) {
        header('Location: login.php?error=empty_fields&user=member');
        exit();
    }
    
    try {
                $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $member = $stmt->fetch();
        
        if (!$member) {
            header('Location: login.php?error=invalid_credentials&user=member');
            exit();
        }
        
                if (!isset($member['password']) || $member['password'] === null) {
                        $default_password = strtolower($member['member_code']);
            $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
            
                        $stmt = $pdo->prepare("UPDATE members SET password = ?, first_login = 1 WHERE id = ?");
            $stmt->execute([$password_hash, $member['id']]);
            
                        $member['password'] = $password_hash;
            $member['first_login'] = 1;
        }
        
                if (!password_verify($password, $member['password'])) {
            header('Location: login.php?error=invalid_credentials&user=member');
            exit();
        }
        
                $_SESSION['member_logged_in'] = true;
        $_SESSION['member_id'] = $member['id'];
        $_SESSION['member_name'] = $member['name'];
        $_SESSION['member_email'] = $member['email'];
        $_SESSION['member_code'] = $member['member_code'];
        $_SESSION['login_time'] = time();
        
                error_log("Member login successful: {$member['name']} ({$member['email']}) at " . date('Y-m-d H:i:s'));
        
                if ($member['first_login'] == 1) {
                        header('Location: member/change_password.php');
        } else {
                        header('Location: member/dashboard.php');
        }
        exit();
        
    } catch(PDOException $e) {
        error_log("Member login error: " . $e->getMessage());
        header('Location: login.php?error=database_error&user=member');
        exit();
    }
    
} else {
        header('Location: login.php?error=invalid_user_type');
    exit();
}
?>
