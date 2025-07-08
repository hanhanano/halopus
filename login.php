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

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_credentials':
            $error = 'Email atau password salah.';
            break;
        case 'login_required':
            $error = 'Silakan login terlebih dahulu.';
            break;
        case 'account_not_found':
            $error = 'Akun tidak ditemukan.';
            break;
        case 'empty_fields':
            $error = 'Username dan password harus diisi!';
            break;
        case 'logout':
            $error = 'Anda telah logout.';
            break;
        default:
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
    }
}

$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'registered':
            $success_message = 'Pendaftaran berhasil! Silakan login dengan akun Anda.';
            break;
    }
}

$active_tab = isset($_GET['user']) ? $_GET['user'] : 'admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Perpustakaan Digital</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Perpustakaan Digital</h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="login-tabs">
            <div class="login-tab <?php echo $active_tab === 'admin' ? 'active' : ''; ?>" data-tab="admin">
                Admin
            </div>
            <div class="login-tab <?php echo $active_tab === 'member' ? 'active' : ''; ?>" data-tab="member">
                Anggota
            </div>
        </div>

        <form action="process_login.php" method="post" class="login-form <?php echo $active_tab === 'admin' ? 'active' : ''; ?>" id="admin-form">
            <input type="hidden" name="user_type" value="admin">
            <div class="form-group">
                <label for="admin-username" class="form-label">Username</label>
                <input type="text" id="admin-username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="admin-password" class="form-label">Password</label>
                <input type="password" id="admin-password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">Login sebagai Admin</button>
        
            <div class="login-footer">
                <p><small>&copy; 2025 Perpustakaan Digital</small></p>
            </div>
        </form>

        <form action="process_login.php" method="post" class="login-form <?php echo $active_tab === 'member' ? 'active' : ''; ?>" id="member-form">
            <input type="hidden" name="user_type" value="member">
            <div class="form-group">
                <label for="member-email" class="form-label">Email</label>
                <input type="email" id="member-email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="member-password" class="form-label">Password</label>
                <input type="password" id="member-password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">Login sebagai Anggota</button>
            
            <div class="login-footer">
                <p>Belum memiliki akun? <a href="register.php">Daftar di sini</a></p>
                <p><small>&copy; 2025 Perpustakaan Digital</small></p>
            </div>
        </form>
    </div>

    <script>
                document.querySelectorAll('.login-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                                document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.login-form').forEach(f => f.classList.remove('active'));
                
                                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-form').classList.add('active');
            });
        });
    </script>
</body>
</html>
