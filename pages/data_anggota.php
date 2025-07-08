<?php
require_once '../config/koneksi.php';
require_once '../auth.php';


$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$member_type = $_GET['member_type'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR member_code LIKE ? OR phone LIKE ?)";
    $search_term = "%{$search}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if (!empty($status)) {
    $where_conditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($member_type)) {
    $where_conditions[] = "member_type = ?";
    $params[] = $member_type;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    $count_sql = "SELECT COUNT(*) as total FROM members {$where_clause}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_members = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_members / $per_page);
} catch(PDOException $e) {
    $total_members = 0;
    $total_pages = 0;
}

$members = [];
try {
    $sql = "SELECT * FROM members {$where_clause} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error loading members: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Anggota - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/form.css">
    <style>
        
        .members-table {
            background: #1a1625;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(139, 69, 196, 0.3);
            border: 1px solid #2d1b3d;
        }

        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(140deg, #d946ef 0%, #c026d3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1625;
            font-weight: bold;
            margin-right: 1rem;
            box-shadow: 0 2px 8px rgba(217, 70, 239, 0.3);
        }

        .member-info {
            display: flex;
            align-items: center;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        @media (max-width: 1100px) {
            .table-container {
                overflow-x: auto;
            }

            .results-header {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
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
            <h1 style="margin-bottom: 2rem;">Data Anggota Perpustakaan</h1>

            <!-- Filters -->
            <div class="member-filters">
                <form method="GET" class="filter-row">
                    <div class="form-group">
                        <label for="search" class="form-label">Cari Anggota</label>
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            class="form-control" 
                            placeholder="Nama, email, kode anggota, atau telepon..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="member_type" class="form-label">Tipe Anggota</label>
                        <select id="member_type" name="member_type" class="form-select">
                            <option value="">Semua Tipe</option>
                            <option value="student" <?php echo $member_type === 'student' ? 'selected' : ''; ?>>Siswa</option>
                            <option value="teacher" <?php echo $member_type === 'teacher' ? 'selected' : ''; ?>>Guru</option>
                            <option value="public" <?php echo $member_type === 'public' ? 'selected' : ''; ?>>Umum</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
            </div>

            <!-- Results Header -->
            <div class="results-header">
                <div>
                    <h2 style="margin: 0;">
                        <?php if (!empty($search) || !empty($status) || !empty($member_type)): ?>
                            Hasil Pencarian Anggota
                        <?php else: ?>
                            Semua Anggota
                        <?php endif; ?>
                    </h2>
                    <p style="margin: 0.5rem 0 0 0;">
                        Ditemukan <?php echo number_format($total_members); ?> anggota
                        <?php if ($total_pages > 1): ?>
                            (Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>)
                        <?php endif; ?>
                    </p>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <a href="tambah_anggota.php" class="btn btn-success">+ Tambah Anggota</a>
                    <?php if (!empty($search) || !empty($status) || !empty($member_type)): ?>
                        <a href="data_anggota.php" class="btn btn-primary">Lihat Semua Anggota</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php elseif (empty($members)): ?>
                <div class="empty-state">
                    <h3>Tidak ada anggota ditemukan</h3>
                    <p>Coba ubah filter pencarian atau tambahkan anggota baru.</p>
                    <a href="tambah_anggota.php" class="btn btn-primary">Tambah Anggota Pertama</a>
                </div>
            <?php else: ?>
                <div class="members-table">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Anggota</th>
                                    <th>Kode</th>
                                    <th>Kontak</th>
                                    <th>Tipe</th>
                                    <th>Bergabung</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td>
                                            <div class="member-info">
                                                <div class="member-avatar">
                                                    <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($member['name']); ?></strong>
                                                    <br>
                                                    <small style="color: #666;">
                                                        <?php echo $member['gender'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                                        <?php if ($member['date_of_birth']): ?>
                                                            | <?php echo date('d/m/Y', strtotime($member['date_of_birth'])); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($member['member_code']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($member['email']); ?>
                                            <?php if ($member['phone']): ?>
                                                <br><small><?php echo htmlspecialchars($member['phone']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="member-type-badge type-<?php echo $member['member_type']; ?>">
                                                <?php 
                                                if ($member['member_type'] === 'student') {
                                                    echo 'Siswa';
                                                } elseif ($member['member_type'] === 'teacher') {
                                                    echo 'Guru';
                                                } else {
                                                    echo 'Umum';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($member['join_date'])); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $member['status']; ?>">
                                                <?php 
                                                if ($member['status'] === 'active') {
                                                    echo 'Aktif';
                                                } elseif ($member['status'] === 'inactive') {
                                                    echo 'Tidak Aktif';
                                                } else {
                                                    echo 'Suspended';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="detail_anggota.php?id=<?php echo $member['id']; ?>" 
                                                   class="btn btn-primary btn-sm">Detail</a>
                                                <a href="edit_anggota.php?id=<?php echo $member['id']; ?>" 
                                                   class="btn btn-warning btn-sm">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">« Pertama</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">‹ Sebelumnya</a>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Selanjutnya ›</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">Terakhir »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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

</body>
</html>
