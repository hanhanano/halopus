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

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    
        $errors = [];
    
    if (empty($name)) $errors[] = "Nama harus diisi";
    if (empty($email)) $errors[] = "Email harus diisi";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    if (empty($password)) $errors[] = "Password harus diisi";
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
    if ($password !== $confirm_password) $errors[] = "Konfirmasi password tidak cocok";
    if (empty($phone)) $errors[] = "Nomor telepon harus diisi";
    if (empty($gender)) $errors[] = "Jenis kelamin harus dipilih";
    if (empty($date_of_birth)) $errors[] = "Tanggal lahir harus diisi";
    
        if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email sudah terdaftar. Silakan gunakan email lain atau login.";
            }
        } catch(PDOException $e) {
            $errors[] = "Error checking email: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
                        $member_code = 'MBR' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
                        $member_type = "public";             $max_borrow_limit = 2;             
            $stmt = $pdo->prepare("
                INSERT INTO members (member_code, name, email, password, phone, address, date_of_birth, gender, member_type, max_borrow_limit, join_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active')
            ");
            
            $stmt->execute([$member_code, $name, $email, $hashed_password, $phone, $address, $date_of_birth, $gender, $member_type, $max_borrow_limit]);
            
            $success_message = "Pendaftaran berhasil! Silakan login dengan email dan password Anda.";
            
                        $_POST = [];
            
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Anggota - Perpustakaan Digital</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Daftar Anggota Baru</h1>
            <p>Silakan isi formulir di bawah untuk mendaftar</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-row">
                <div class="form-col">
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            required
                            minlength="6"
                        >
                    </div>
                </div>
                <div class="form-col">
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Konfirmasi Password *</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="phone" class="form-label">Nomor Telepon *</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="date_of_birth" class="form-label">Tanggal Lahir *</label>
                <input 
                    type="date" 
                    id="date_of_birth" 
                    name="date_of_birth" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="address" class="form-label">Alamat</label>
                <textarea 
                    id="address" 
                    name="address" 
                    class="form-control" 
                    rows="3"
                ><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Jenis Kelamin *</label>
                <div class="radio-group">
                    <div class="radio-item">
                        <input 
                            type="radio" 
                            id="gender_l" 
                            name="gender" 
                            value="L" 
                            <?php echo ($_POST['gender'] ?? '') === 'L' ? 'checked' : ''; ?>
                            required
                        >
                        <label for="gender_l">Laki-laki</label>
                    </div>
                    <div class="radio-item">
                        <input 
                            type="radio" 
                            id="gender_p" 
                            name="gender" 
                            value="P" 
                            <?php echo ($_POST['gender'] ?? '') === 'P' ? 'checked' : ''; ?>
                            required
                        >
                        <label for="gender_p">Perempuan</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-login">
                Daftar Sekarang
            </button>

            <div class="login-footer">
                <p>Sudah memiliki akun? <a href="login.php">Login di sini</a></p>
            </div>
        </form>
    </div>

    <script>
                document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Konfirmasi password tidak cocok!');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
