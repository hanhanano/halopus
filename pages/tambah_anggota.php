<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $member_type = $_POST['member_type'] ?? '';
    $password_option = $_POST['password_option'] ?? 'auto';
    $custom_password = $_POST['custom_password'] ?? '';
    
        $errors = [];
    
    if (empty($name)) $errors[] = "Nama harus diisi";
    if (empty($email)) $errors[] = "Email harus diisi";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    if (empty($phone)) $errors[] = "Nomor telepon harus diisi";
    if (empty($address)) $errors[] = "Alamat harus diisi";
    if (empty($date_of_birth)) $errors[] = "Tanggal lahir harus diisi";
    if (empty($gender)) $errors[] = "Jenis kelamin harus dipilih";
    if (empty($member_type)) $errors[] = "Tipe anggota harus dipilih";
    
        if ($password_option === 'custom') {
        if (empty($custom_password)) {
            $errors[] = "Password kustom harus diisi";
        } elseif (strlen($custom_password) < 6) {
            $errors[] = "Password minimal 6 karakter";
        }
    }
    
        if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email sudah terdaftar";
            }
        } catch(PDOException $e) {
            $errors[] = "Error checking email: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
                        $member_code = 'MBR' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
                        $max_borrow_limit = match($member_type) {
                'student' => 3,
                'teacher' => 5,
                'public' => 2,
                default => 2
            };
            
                        if ($password_option === 'auto') {
                                $default_password = strtolower($member_code);
                $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
                $password_info = "Password default: <strong>$default_password</strong>";
            } else {
                                $password_hash = password_hash($custom_password, PASSWORD_DEFAULT);
                $password_info = "Password kustom telah diset";
                $default_password = $custom_password;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO members (member_code, name, email, password, phone, address, date_of_birth, gender, member_type, max_borrow_limit, join_date, status, first_login) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active', 1)
            ");
            
            $stmt->execute([$member_code, $name, $email, $password_hash, $phone, $address, $date_of_birth, $gender, $member_type, $max_borrow_limit]);
            
            $member_id = $pdo->lastInsertId();
            $success_message = "
                Anggota berhasil didaftarkan!<br>
                <strong>Kode Anggota:</strong> $member_code<br>
                <strong>Email:</strong> $email<br>
                $password_info<br>
                <em>Anggota akan diminta mengganti password saat login pertama kali.</em>
            ";
            
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
    <title>Tambah Anggota - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/form.css">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><img src="../images/whitemenu.svg" alt="Menu" class="icon"> Menu</h3>
            <button class="sidebar-close" id="sidebarClose">&times;</button>
        </div>
        <ul class="sidebar-menu" id="sidebarMenu">
            <li><a href="../index.php"><img src="../images/blackbank.svg" alt="Dashboard" class="icon"> Dashboard</a></li>
            <li><a href="data_anggota.php" class="active"><img src="../images/blueanggota.svg" alt="Anggota" class="icon"> Data Anggota</a></li>
            <li><a href="cari_buku.php"><img src="../images/blackbuku.svg" alt="Buku" class="icon"> Katalog Buku</a></li>
            <li><a href="peminjaman.php"><img src="../images/blackpinjam.svg" alt="Pinjam" class="icon"> Peminjaman</a></li>
            <li><a href="pengembalian.php"><img src="../images/blackkembali.svg" alt="Kembali" class="icon"> Pengembalian</a></li>
            <li><a href="histori_peminjaman.php"><img src="../images/blackhistori.svg" alt="Histori" class="icon"> Histori</a></li>
            <li><a href="kategori.php"><img src="../images/blackkategori.svg" alt="Kategori" class="icon"> Kategori</a></li>
            <?php if (hasRole('super_admin')): ?>
            <li>
                <a href="manage_admin.php">
                    <img src="../images/blackadmin.svg" alt="Admin" class="icon"> Kelola Admin
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="nav-left">
                <button class="hamburger-menu" id="hamburgerMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <a href="../index.php" class="logo"><img src="../images/logo.svg" alt="Profile" class="firsticon" > HALO-PUS</a>
            </div>
            <div class="nav-right">
                <div class="user-info">
                    <span class="user-name"><img src="../images/profile.svg" alt="Profile" class="icon" > <?php echo htmlspecialchars(getCurrentAdmin()['full_name']); ?></span>
                    <a href="../logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
                        <img src="../images/quit.svg" alt="Profile" class="icon"> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="form-container">
                <h1 class="form-title">Tambah Anggota Baru</h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                        <br><br>
                        <a href="data_anggota.php" class="btn btn-sm btn-primary">Lihat Data Anggota</a>
                        <a href="tambah_anggota.php" class="btn btn-sm btn-success">Tambah Anggota Lagi</a>
                        <?php if (isset($member_id)): ?>
                            <a href="detail_anggota.php?id=<?php echo $member_id; ?>" class="btn btn-sm btn-info">Lihat Detail</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="memberForm">
                    <div class="form-row">
                        <div class="form-col">
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
                        </div>
                        <div class="form-col">
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
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
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
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">Tanggal Lahir *</label>
                                <input 
                                    type="date" 
                                    id="date_of_birth" 
                                    name="date_of_birth" 
                                    class="form-control" 
                                    value="<?php echo $_POST['date_of_birth'] ?? ''; ?>"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
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
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="member_type" class="form-label">Tipe Anggota *</label>
                                <select id="member_type" name="member_type" class="form-select" required>
                                    <option value="">Pilih Tipe Anggota</option>
                                    <option value="student" <?php echo ($_POST['member_type'] ?? '') === 'student' ? 'selected' : ''; ?>>
                                        Siswa (Maks 3 buku)
                                    </option>
                                    <option value="teacher" <?php echo ($_POST['member_type'] ?? '') === 'teacher' ? 'selected' : ''; ?>>
                                        Guru (Maks 5 buku)
                                    </option>
                                    <option value="public" <?php echo ($_POST['member_type'] ?? '') === 'public' ? 'selected' : ''; ?>>
                                        Umum (Maks 2 buku)
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Alamat *</label>
                        <textarea 
                            id="address" 
                            name="address" 
                            class="form-control textarea" 
                            rows="3"
                            required
                        ><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Password Options -->
                    <div class="form-group">
                        <label class="form-label">Pengaturan Password *</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input 
                                    type="radio" 
                                    id="password_auto" 
                                    name="password_option" 
                                    value="auto" 
                                    <?php echo ($_POST['password_option'] ?? 'auto') === 'auto' ? 'checked' : ''; ?>
                                    required
                                >
                                <label for="password_auto">
                                    <strong>Password Otomatis</strong><br>
                                    <small>Gunakan kode anggota sebagai password default</small>
                                </label>
                            </div>
                            <div class="radio-item">
                                <input 
                                    type="radio" 
                                    id="password_custom" 
                                    name="password_option" 
                                    value="custom" 
                                    <?php echo ($_POST['password_option'] ?? '') === 'custom' ? 'checked' : ''; ?>
                                    required
                                >
                                <label for="password_custom">
                                    <strong>Password Kustom</strong><br>
                                    <small>Tentukan password sendiri</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="customPasswordGroup" style="display: none;">
                        <label for="custom_password" class="form-label">Password Kustom</label>
                        <input 
                            type="password" 
                            id="custom_password" 
                            name="custom_password" 
                            class="form-control" 
                            minlength="6"
                            placeholder="Minimal 6 karakter"
                        >
                        <small class="form-text">Password ini akan diberikan kepada anggota untuk login pertama kali.</small>
                    </div>

                    <div class="alert alert-info">
                        <strong>Catatan:</strong> Anggota akan diminta mengganti password saat login pertama kali untuk keamanan.
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-success">
                            👤 Daftarkan Anggota
                        </button>
                        <a href="data_anggota.php" class="btn btn-primary">Kembali ke Data Anggota</a>
                        <a href="../index.php" class="btn btn-primary">Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistem Perpustakaan Digital.</p>
        </div>
    </footer>

    <script src="../js/main.js"></script>
    <script src="../js/animasi.js"></script>

    <script>
                document.querySelectorAll('input[name="password_option"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const customPasswordGroup = document.getElementById('customPasswordGroup');
                const customPasswordInput = document.getElementById('custom_password');
                
                if (this.value === 'custom') {
                    customPasswordGroup.style.display = 'block';
                    customPasswordInput.required = true;
                } else {
                    customPasswordGroup.style.display = 'none';
                    customPasswordInput.required = false;
                    customPasswordInput.value = '';
                }
            });
        });

                document.getElementById('memberForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const passwordOption = document.querySelector('input[name="password_option"]:checked').value;
            const customPassword = document.getElementById('custom_password').value;
            
            if (!name || !email || !phone) {
                alert('Nama, email, dan telepon harus diisi');
                e.preventDefault();
                return false;
            }
            
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Format email tidak valid');
                e.preventDefault();
                return false;
            }
            
                        if (passwordOption === 'custom') {
                if (!customPassword || customPassword.length < 6) {
                    alert('Password kustom minimal 6 karakter');
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    </script>
</body>
</html>
