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
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
                session_destroy();
        header('Location: ../login.php?error=account_not_found&user=member');
        exit();
    }
    
        $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_borrowings,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_books,
            COUNT(CASE WHEN status IN ('borrowed', 'overdue') THEN 1 END) as current_books,
            SUM(CASE WHEN fine_amount > 0 THEN fine_amount ELSE 0 END) as total_fines
        FROM borrowings 
        WHERE member_id = ?
    ");
    $stmt->execute([$member_id]);
    $stats = $stmt->fetch();
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
                $errors = [];
        
        if (empty($name)) $errors[] = "Nama harus diisi";
        if (empty($phone)) $errors[] = "Nomor telepon harus diisi";
        if (empty($date_of_birth)) $errors[] = "Tanggal lahir harus diisi";
        
                if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = "Password saat ini harus diisi untuk mengubah password";
            } elseif (!password_verify($current_password, $member['password'])) {
                $errors[] = "Password saat ini tidak valid";
            }
            
            if (strlen($new_password) < 6) {
                $errors[] = "Password baru minimal 6 karakter";
            }
            
            if ($new_password !== $confirm_password) {
                $errors[] = "Konfirmasi password baru tidak cocok";
            }
        }
        
        if (empty($errors)) {
                        $update_fields = [];
            $params = [];
            
            $update_fields[] = "name = ?";
            $params[] = $name;
            
            $update_fields[] = "phone = ?";
            $params[] = $phone;
            
            $update_fields[] = "address = ?";
            $params[] = $address;
            
            if (!empty($date_of_birth)) {
                $update_fields[] = "date_of_birth = ?";
                $params[] = $date_of_birth;
            }
            
            if (!empty($new_password)) {
                $update_fields[] = "password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            $update_fields[] = "updated_at = NOW()";
            
                        $params[] = $member_id;
            
            $sql = "UPDATE members SET " . implode(", ", $update_fields) . " WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
                        $_SESSION['member_name'] = $name;
            
                        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            
            $success_message = "Profil berhasil diperbarui!";
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $member = null;
    $stats = ['total_borrowings' => 0, 'returned_books' => 0, 'current_books' => 0, 'total_fines' => 0];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/style2member.css">
</head>
<body>
    <!-- Member Header -->
    <header class="header" style="padding-left: 50px;">
        <a href="dashboard.php" class="logo"><img src="../images/logo.svg" alt="Profile" class="firsticon" > HALO-PUS</a>
    </header>

    <!-- Main Content -->
    <main class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Sidebar -->
            <div class="dashboard-sidebar">
                <div class="profile-section">
                    <h3>Informasi Anggota</h3>
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($member['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
                    <p><strong>Telepon:</strong> <?php echo htmlspecialchars($member['phone']); ?></p>
                    <p><strong>Tanggal Lahir:</strong> <?php echo date('d/m/Y', strtotime($member['date_of_birth'])); ?></p>
                    <p><strong>Alamat:</strong> <?php echo htmlspecialchars($member['address'] ?: 'Belum diisi'); ?></p>
                    <p><strong>Bergabung:</strong> <?php echo date('d/m/Y', strtotime($member['join_date'])); ?></p>
                </div>
                
                <div class="profile-section">
                    <h3>Statistik Peminjaman</h3>
                    <p><strong>Total Peminjaman:</strong> <?php echo $stats['total_borrowings']; ?> buku</p>
                    <p><strong>Sedang Dipinjam:</strong> <?php echo $stats['current_books']; ?> buku</p>
                    <p><strong>Sudah Dikembalikan:</strong> <?php echo $stats['returned_books']; ?> buku</p>
                    <p><strong>Total Denda:</strong> Rp <?php echo number_format($stats['total_fines'], 0, ',', '.'); ?></p>
                </div>
                
                <ul class="menu-list">
                    <li>
                        <a href="dashboard.php">
                            <img src="../images/blackbank.svg" alt="Dashboard" class="menu-icon">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="active">
                            <img src="../images/blueanggota.svg" alt="Profile" class="menu-icon">
                            Profil Saya
                        </a>
                    </li>
                    <li>
                        <a href="borrow.php">
                            <img src="../images/blackpinjam.svg" alt="Borrow" class="menu-icon">
                            Pinjam Buku
                        </a>
                    </li>
                    <li>
                        <a href="history.php">
                            <img src="../images/blackhistori.svg" alt="History" class="menu-icon">
                            Riwayat Peminjaman
                        </a>
                    </li>
                </ul>
                
                <a href="../member_logout.php" class="btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                    Logout
                </a>
            </div>

            <!-- Main Content -->
            <div class="dashboard-main">
                <div class="dashboard-card">
                    <h2>Edit Profil</h2>
                    
                    <form method="POST" action="">
                        <div class="form-section">
                            <h3>Informasi Pribadi</h3>
                            
                            <div class="form-group">
                                <label for="name">Nama Lengkap *</label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name"
                                    class="form-control"  
                                    value="<?php echo htmlspecialchars($member['name']); ?>" 
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email (tidak dapat diubah)</label>
                                <input 
                                    type="email" 
                                    id="email"
                                    class="form-control"  
                                    value="<?php echo htmlspecialchars($member['email']); ?>" 
                                    disabled
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Nomor Telepon *</label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone"
                                    class="form-control"  
                                    value="<?php echo htmlspecialchars($member['phone']); ?>" 
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth">Tanggal Lahir *</label>
                                <input 
                                    type="date" 
                                    id="date_of_birth" 
                                    name="date_of_birth"
                                    class="form-control"  
                                    value="<?php echo $member['date_of_birth']; ?>"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Alamat</label>
                                <textarea 
                                    id="address" 
                                    name="address" 
                                    rows="3"
                                    class="form-control" 
                                ><?php echo htmlspecialchars($member['address']); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Ubah Password</h3>
                            <p style="margin-bottom: 1rem; color: #666; font-size: 0.9rem;">
                                Kosongkan bagian ini jika Anda tidak ingin mengubah password.
                            </p>
                            
                            <div class="form-group">
                                <label for="current_password">Password Saat Ini</label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password"
                                    class="form-control" 
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">Password Baru</label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password"
                                    minlength="6"
                                    class="form-control" 
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Konfirmasi Password Baru</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password"
                                    class="form-control" 
                                >
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <input type="hidden" name="update_profile" value="1">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistem Perpustakaan Digital.</p>
        </div>
    </footer>

    <script>
                document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            if (newPassword || confirmPassword || currentPassword) {
                if (!currentPassword) {
                    alert('Password saat ini harus diisi untuk mengubah password!');
                    e.preventDefault();
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    alert('Konfirmasi password baru tidak cocok!');
                    e.preventDefault();
                    return false;
                }
                
                if (newPassword.length < 6) {
                    alert('Password baru minimal 6 karakter!');
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    </script>
</body>
</html>
