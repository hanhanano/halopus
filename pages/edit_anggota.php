<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$success_message = '';
$error_message = '';
$member = null;

$member_id = (int)($_GET['id'] ?? 0);

if ($member_id <= 0) {
    header('Location: data_anggota.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
        header('Location: data_anggota.php');
        exit;
    }
} catch(PDOException $e) {
    $error_message = "Error loading member: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $member) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $member_type = $_POST['member_type'] ?? '';
    $status = $_POST['status'] ?? '';
    
        $errors = [];
    
    if (empty($name)) $errors[] = "Nama harus diisi";
    if (empty($email)) $errors[] = "Email harus diisi";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    if (empty($phone)) $errors[] = "Nomor telepon harus diisi";
    if (empty($address)) $errors[] = "Alamat harus diisi";
    if (empty($date_of_birth)) $errors[] = "Tanggal lahir harus diisi";
    if (empty($gender)) $errors[] = "Jenis kelamin harus dipilih";
    if (empty($member_type)) $errors[] = "Tipe anggota harus dipilih";
    if (empty($status)) $errors[] = "Status harus dipilih";
    
        if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
            $stmt->execute([$email, $member_id]);
            if ($stmt->fetch()) {
                $errors[] = "Email sudah digunakan oleh anggota lain";
            }
        } catch(PDOException $e) {
            $errors[] = "Error checking email: " . $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        try {
                        $max_borrow_limit = match($member_type) {
                'student' => 3,
                'teacher' => 5,
                'public' => 2,
                default => 2
            };
            
            $stmt = $pdo->prepare("
                UPDATE members 
                SET name = ?, email = ?, phone = ?, address = ?, date_of_birth = ?, 
                    gender = ?, member_type = ?, max_borrow_limit = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([$name, $email, $phone, $address, $date_of_birth, $gender, $member_type, $max_borrow_limit, $status, $member_id]);
            
            $success_message = "Data anggota berhasil diperbarui!";
            
                        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            
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
    <title>Edit Anggota - <?php echo htmlspecialchars($member['name'] ?? 'Perpustakaan Digital'); ?></title>
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
            <?php if ($member): ?>
                <div class="form-container">
                    <h1 class="form-title">Edit Data Anggota</h1>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                            <br><a href="detail_anggota.php?id=<?php echo $member['id']; ?>">Lihat Detail</a> | 
                            <a href="data_anggota.php">Kembali ke Data Anggota</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="editMemberForm">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="name" class="form-label">Nama Lengkap *</label>
                                    <input 
                                        type="text" 
                                        id="name" 
                                        name="name" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($member['name']); ?>"
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
                                        value="<?php echo htmlspecialchars($member['email']); ?>"
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
                                        value="<?php echo htmlspecialchars($member['phone']); ?>"
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
                                        value="<?php echo $member['date_of_birth']; ?>"
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
                                                <?php echo $member['gender'] === 'L' ? 'checked' : ''; ?>
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
                                                <?php echo $member['gender'] === 'P' ? 'checked' : ''; ?>
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
                                        <option value="student" <?php echo $member['member_type'] === 'student' ? 'selected' : ''; ?>>
                                            Siswa (Maks 3 buku)
                                        </option>
                                        <option value="teacher" <?php echo $member['member_type'] === 'teacher' ? 'selected' : ''; ?>>
                                            Guru (Maks 5 buku)
                                        </option>
                                        <option value="public" <?php echo $member['member_type'] === 'public' ? 'selected' : ''; ?>>
                                            Umum (Maks 2 buku)
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="status" class="form-label">Status *</label>
                                    <select id="status" name="status" class="form-select" required>
                                        <option value="">Pilih Status</option>
                                        <option value="active" <?php echo $member['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="inactive" <?php echo $member['status'] === 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                        <option value="suspended" <?php echo $member['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Kode Anggota</label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($member['member_code']); ?>"
                                        readonly
                                        style="background-color: #f8f9fa;"
                                    >
                                    <small style="color: #666;">Kode anggota tidak dapat diubah</small>
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
                            ><?php echo htmlspecialchars($member['address']); ?></textarea>
                        </div>

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-success">
                                 Perbarui Data Anggota
                            </button>
                            <a href="detail_anggota.php?id=<?php echo $member['id']; ?>" class="btn btn-primary">Lihat Detail</a>
                            <a href="data_anggota.php" class="btn btn-primary">Kembali ke Data Anggota</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
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
                document.getElementById('editMemberForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            
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
            
            return true;
        });
    </script>
</body>
</html>
