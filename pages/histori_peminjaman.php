<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.name LIKE ? OR m.member_code LIKE ? OR b.title LIKE ? OR b.author LIKE ?)";
    $search_term = "%{$search}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if (!empty($status)) {
    $where_conditions[] = "br.status = ?";
    $params[] = $status;
}

if (!empty($start_date)) {
    $where_conditions[] = "br.borrow_date >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $where_conditions[] = "br.borrow_date <= ?";
    $params[] = $end_date;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM borrowings br
        JOIN members m ON br.member_id = m.id
        JOIN books b ON br.book_id = b.id
        {$where_clause}
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
} catch(PDOException $e) {
    $total_records = 0;
    $total_pages = 0;
}

$borrowings = [];
try {
    $sql = "
        SELECT br.*, m.name as member_name, m.member_code, 
               b.title as book_title, b.author, b.publisher
        FROM borrowings br
        JOIN members m ON br.member_id = m.id
        JOIN books b ON br.book_id = b.id
        {$where_clause}
        ORDER BY br.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $borrowings = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error loading history: " . $e->getMessage();
}

try {
    $stats_sql = "
        SELECT 
            COUNT(*) as total_borrowings,
            COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as currently_borrowed,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned,
            COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue,
            SUM(CASE WHEN fine_amount > 0 THEN fine_amount ELSE 0 END) as total_fines
        FROM borrowings
    ";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch();
} catch(PDOException $e) {
    $stats = [
        'total_borrowings' => 0,
        'currently_borrowed' => 0,
        'returned' => 0,
        'overdue' => 0,
        'total_fines' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Peminjaman - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/form.css">
    <style>
        
        .history-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .fine-amount {
            color: #dc3545;
            font-weight: bold;
        }
        
        .member-info {
            display: flex;
            flex-direction: column;
        }
        
        .book-info {
            display: flex;
            flex-direction: column;
        }
        
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
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
            <li><a href="data_anggota.php"><img src="../images/blackanggota.svg" alt="Anggota" class="icon"> Data Anggota</a></li>
            <li><a href="cari_buku.php"><img src="../images/blackbuku.svg" alt="Buku" class="icon"> Katalog Buku</a></li>
            <li><a href="peminjaman.php"><img src="../images/blackpinjam.svg" alt="Pinjam" class="icon"> Peminjaman</a></li>
            <li><a href="pengembalian.php"><img src="../images/blackkembali.svg" alt="Kembali" class="icon"> Pengembalian</a></li>
            <li><a href="histori_peminjaman.php" class="active"><img src="../images/bluehistori.svg" alt="Histori" class="icon"> Histori</a></li>
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
            <h1 style="margin-bottom: 2rem;">Histori Peminjaman Buku</h1>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_borrowings']); ?></div>
                    <div class="stat-label">Total Peminjaman</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['currently_borrowed']); ?></div>
                    <div class="stat-label">Sedang Dipinjam</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['returned']); ?></div>
                    <div class="stat-label">Sudah Dikembalikan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['overdue']); ?></div>
                    <div class="stat-label">Terlambat</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp <?php echo number_format($stats['total_fines'], 0, ',', '.'); ?></div>
                    <div class="stat-label">Total Denda</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="history-filters">
                <form method="GET" class="filter-grid">
                    <div class="form-group">
                        <label for="search" class="form-label">Cari</label>
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            class="form-control" 
                            placeholder="Nama anggota, kode, atau judul buku..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="borrowed" <?php echo $status === 'borrowed' ? 'selected' : ''; ?>>Dipinjam</option>
                            <option value="returned" <?php echo $status === 'returned' ? 'selected' : ''; ?>>Dikembalikan</option>
                            <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Terlambat</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date" class="form-label">Dari Tanggal</label>
                        <input 
                            type="date" 
                            id="start_date" 
                            name="start_date" 
                            class="form-control" 
                            value="<?php echo $start_date; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date" class="form-label">Sampai Tanggal</label>
                        <input 
                            type="date" 
                            id="end_date" 
                            name="end_date" 
                            class="form-control" 
                            value="<?php echo $end_date; ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </form>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php elseif (empty($borrowings)): ?>
                <div class="empty-state">
                    <h3> Tidak ada histori peminjaman</h3>
                    <p>Belum ada transaksi peminjaman atau tidak ada yang sesuai dengan filter.</p>
                    <a href="peminjaman.php" class="btn btn-primary">Mulai Peminjaman</a>
                </div>
            <?php else: ?>
                <!-- Results Info -->
                <div style="margin-bottom: 1rem;">
                    Menampilkan <?php echo number_format($total_records); ?> histori peminjaman
                    <?php if ($total_pages > 1): ?>
                        (Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>)
                    <?php endif; ?>
                </div>

                <!-- History Table -->
                <div class="history-table">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Tanggal Kembali</th>
                                    <th>Status</th>
                                    <th>Denda Diterima</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrowings as $borrowing): ?>
                                    <tr>
                                        <td>
                                            <div class="member-info">
                                                <strong><?php echo htmlspecialchars($borrowing['member_name']); ?></strong>
                                                <small><?php echo htmlspecialchars($borrowing['member_code']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="book-info">
                                                <strong><?php echo htmlspecialchars($borrowing['book_title']); ?></strong>
                                                <small>oleh <?php echo htmlspecialchars($borrowing['author']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($borrowing['return_date']): ?>
                                                <?php echo date('d/m/Y', strtotime($borrowing['return_date'])); ?>
                                            <?php else: ?>
                                                <span>-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $borrowing['status']; ?>">
                                                <?php 
                                                echo match($borrowing['status']) {
                                                    'borrowed' => 'Dipinjam',
                                                    'returned' => 'Dikembalikan',
                                                    'overdue' => 'Terlambat',
                                                    default => $borrowing['status']
                                                };
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($borrowing['fine_amount'] > 0): ?>
                                                <span class="fine-amount">
                                                    Rp <?php echo number_format($borrowing['fine_amount'], 0, ',', '.'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #28a745;">Rp 0</span>
                                            <?php endif; ?>
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
