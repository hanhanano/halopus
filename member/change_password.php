<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: ../login.php?error=login_required&user=member');
    exit();
}

$member_id = $_SESSION['member_id'];
$success_message = '';
$error_message = '';

try {
    $stmt = $pdo->prepare("SELECT first_login FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    $is_first_login = $member && $member['first_login'] == 1;
} catch(PDOException $e) {
    $is_first_login = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
        $errors = [];
    
    if (empty($current_password)) $errors[] = "Password saat ini harus diisi";
    if (empty($new_password)) $errors[] = "Password baru harus diisi";
    if (strlen($new_password) < 6) $errors[] = "Password baru minimal 6 karakter";
    if ($new_password !== $confirm_password) $errors[] = "Konfirmasi password tidak cocok";
    
    if (empty($errors)) {
        try {
                        $stmt = $pdo->prepare("SELECT password FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            
                        if (!password_verify($current_password, $member['password'])) {
                $errors[] = "Password saat ini tidak valid";
            }
            
            if (empty($errors)) {
                                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE members SET password = ?, first_login = 0, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_password_hash, $member_id]);
                
                $success_message = "Password berhasil diubah! Anda akan diarahkan ke dashboard.";
                
                                header("refresh:3;url=dashboard.php");
            }
        } catch(PDOException $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Ganti Password</h1>
            <p>Halo, <?php echo htmlspecialchars($_SESSION['member_name']); ?>!</p>
        </div>

        <?php if ($is_first_login): ?>
            <div class="first-login-notice">
                <h3>🔐 Login Pertama Kali</h3>
                <p>Untuk keamanan akun Anda, silakan ganti password default dengan password baru yang lebih aman.</p>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="password-requirements">
            <h4>Persyaratan Password:</h4>
            <ul>
                <li>Minimal 6 karakter</li>
                <li>Kombinasi huruf dan angka direkomendasikan</li>
                <li>Hindari menggunakan informasi pribadi</li>
            </ul>
        </div>

        <form method="POST" id="changePasswordForm">
            <div class="form-group">
                <label for="current_password" class="form-label">Password Saat Ini *</label>
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    class="form-control" 
                    required
                    placeholder="Masukkan password saat ini"
                >
                <?php if ($is_first_login): ?>
                    <small style="color: #666; font-size: 0.8rem;">
                        Gunakan password yang diberikan admin atau kode anggota Anda
                    </small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="new_password" class="form-label">Password Baru *</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="form-control" 
                    required
                    minlength="6"
                    placeholder="Masukkan password baru"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Konfirmasi Password Baru *</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-control" 
                    required
                    placeholder="Ulangi password baru"
                >
            </div>

            <button type="submit" class="btn-login">
                Ganti Password
            </button>

            <?php if (!$is_first_login): ?>
                <a href="dashboard.php" class="btn-skip">
                    Kembali ke Dashboard
                </a>
            <?php endif; ?>
        </form>
    </div>

    <script>
                document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Konfirmasi password tidak cocok!');
                e.preventDefault();
                return false;
            }
            
            if (newPassword.length < 6) {
                alert('Password baru minimal 6 karakter!');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
