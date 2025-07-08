<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

if (!hasRole('super_admin')) {
    header('Location: ../index.php?error=access_denied');
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_admin') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'admin';
        
                $errors = [];
        if (empty($username)) $errors[] = "Username harus diisi";
        if (empty($email)) $errors[] = "Email harus diisi";
        if (empty($password)) $errors[] = "Password harus diisi";
        if (empty($full_name)) $errors[] = "Nama lengkap harus diisi";
        if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
        
        if (empty($errors)) {
            try {
                                $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error_message = "Username atau email sudah digunakan!";
                } else {
                                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $password_hash, $full_name, $role]);
                    $success_message = "Admin baru berhasil ditambahkan!";
                }
            } catch(PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
    
    elseif ($action === 'update_status') {
        $admin_id = $_POST['admin_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        try {
            $stmt = $pdo->prepare("UPDATE admins SET status = ? WHERE id = ?");
            $stmt->execute([$status, $admin_id]);
            $success_message = "Status admin berhasil diupdate!";
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC");
    $admins = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error loading admins: " . $e->getMessage();
    $admins = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/form.css">
    <style>
        .admin-table {
            border-radius: 12px;
            overflow: hidden;
        }

        @media (max-width: 1100px) {
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><img src="../images/whitemenu.svg" alt="Menu" class="icon"> Menu</h3>
            <button class="sidebar-close" id="sidebarClose">&times;</button>
        </div>
        <ul class="sidebar-menu" id="sidebarMenu">
            <li><a href="../index.php"><img src="../images/blackbank.svg" alt="Dashboard" class="icon"> Dashboard</a></li>
            <li><a href="data_anggota.php"><img src="../images/blackanggota.svg" alt="Anggota" class="icon"> Data Anggota</a></li>
            <li><a href="cari_buku.php"><img src="../images/blackbuku.svg" alt="Buku" class="icon"> Katalog Buku</a></li>
            <li><a href="peminjaman.php"><img src="../images/blackpinjam.svg" alt="Pinjam" class="icon"> Peminjaman</a></li>
            <li><a href="pengembalian.php"><img src="../images/blackkembali.svg" alt="Kembali" class="icon"> Pengembalian</a></li>
            <li><a href="histori_peminjaman.php"><img src="../images/blackhistori.svg" alt="Histori" class="icon"> Histori</a></li>
            <li><a href="kategori.php"><img src="../images/blackkategori.svg" alt="Kategori" class="icon"> Kategori</a></li>
            <?php if (hasRole('super_admin')): ?>
            <li>
                <a href="manage_admin.php" class="active">
                    <img src="../images/blueadmin.svg" alt="Admin" class="icon"> Kelola Admin
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

    <main class="main-content">
        <div class="container">
            <div class="main-content">
                <div class="content-header">
                    <h1 style="margin-bottom: 2rem;">Kelola Admin</h1>
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

                <!-- Add New Admin Form -->
                <div class="card">
                    <div class="card-header">
                        <h2>Tambah Admin Baru</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="add_admin">
                            
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" class="form-control" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Nama Lengkap *</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="admin">Admin</option>
                                    <option value="librarian">Pustakawan</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Tambah Admin</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Admin List -->
                <div class="card">
                    <div class="admin-table">
                        <h2>Daftar Admin</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td>
                                            <span class="member-type-badge type-<?php echo $admin['role']; ?>">
                                                <?php 
                                                if ($admin['role'] === 'admin') {
                                                    echo 'Admin';
                                                } elseif ($admin['role'] === 'librarian') {
                                                    echo 'Pustakawan';
                                                } elseif ($admin['role'] === 'super_admin'){
                                                    echo 'Super';
                                                } else {
                                                    echo 'Staff';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $admin['status']; ?>">
                                                <?php 
                                                if ($admin['status'] === 'active') {
                                                    echo 'Aktif';
                                                } elseif ($admin['status'] === 'inactive') {
                                                    echo 'Tidak Aktif';
                                                } else {
                                                    echo 'Suspended';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : 'Belum pernah'; ?>
                                        </td>
                                        <td>
                                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="form-select" >
                                                    <option value="active" <?php echo $admin['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="inactive" <?php echo $admin['status'] === 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                                    <option value="suspended" <?php echo $admin['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                </select>
                                            </form>
                                            <?php else: ?>
                                            <em>Current User</em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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

    <script src="../js/main.js"></script>
    <script src="../js/animasi.js"></script>

</body>
</html>
