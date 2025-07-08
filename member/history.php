<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: ../login.php?error=login_required&user=member');
    exit();
}

$member_id = $_SESSION['member_id'];
$error_message = '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';

try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if (!$member) {
                session_destroy();
        header('Location: ../login.php?error=account_not_found&user=member');
        exit();
    }
    
        $where_clauses = ["b.member_id = ?"];
    $params = [$member_id];
    
    if ($status_filter !== 'all') {
        $where_clauses[] = "b.status = ?";
        $params[] = $status_filter;
    }
    
    if ($date_filter !== 'all') {
        switch ($date_filter) {
            case 'this_week':
                $where_clauses[] = "b.borrow_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'this_month':
                $where_clauses[] = "b.borrow_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'this_year':
                $where_clauses[] = "YEAR(b.borrow_date) = YEAR(CURDATE())";
                break;
        }
    }
    
    $where_clause = implode(" AND ", $where_clauses);
    
        $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM borrowings b
        WHERE $where_clause
    ");
    $stmt->execute($params);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_pages = ceil($total_count['total'] / $limit);
    
        $stmt = $pdo->prepare("
        SELECT b.*, bk.title, bk.author, bk.publisher
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE $where_clause
        ORDER BY b.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    
    $stmt->execute($params);
    $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$stats) {
        $stats = [
            'total_borrowings' => 0,
            'returned_books' => 0,
            'current_books' => 0,
            'total_fines' => 0
        ];
    }
    
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $member = null;
    $borrowings = [];
    $total_pages = 0;
    $stats = [
        'total_borrowings' => 0,
        'returned_books' => 0,
        'current_books' => 0,
        'total_fines' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Perpustakaan Digital</title>
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
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Sidebar -->
            <div class="dashboard-sidebar">
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
                        <a href="profile.php">
                            <img src="../images/blackanggota.svg" alt="Profile" class="menu-icon">
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
                        <a href="history.php" class="active">
                            <img src="../images/bluehistori.svg" alt="History" class="menu-icon">
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
                    <h2>Riwayat Peminjaman Buku</h2>
                    
                    <!-- Filter Bar -->
                    <div class="filter-bar">
                        <form method="GET" action="" id="filterForm" style="width: 100%; display: flex; flex-wrap: wrap; gap: 1rem;">
                            <div class="filter-group">
                                <span class="filter-label">Status:</span>
                                <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                                    <option value="borrowed" <?php echo $status_filter === 'borrowed' ? 'selected' : ''; ?>>Dipinjam</option>
                                    <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Dikembalikan</option>
                                    <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Terlambat</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <span class="filter-label">Periode:</span>
                                <select name="date" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>Semua Waktu</option>
                                    <option value="this_week" <?php echo $date_filter === 'this_week' ? 'selected' : ''; ?>>Minggu Ini</option>
                                    <option value="this_month" <?php echo $date_filter === 'this_month' ? 'selected' : ''; ?>>Bulan Ini</option>
                                    <option value="this_year" <?php echo $date_filter === 'this_year' ? 'selected' : ''; ?>>Tahun Ini</option>
                                </select>
                            </div>
                            
                            <?php if ($status_filter !== 'all' || $date_filter !== 'all'): ?>
                                <div class="filter-group">
                                    <a href="history.php" class="btn btn-sm btn-outline">Reset Filter</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <?php if (empty($borrowings)): ?>
                        <div class="empty-state">
                            <h3>📚 Tidak ada riwayat peminjaman</h3>
                            <p>Anda belum meminjam buku apapun.</p>
                        </div>
                    <?php else: ?>
                        <!-- History Table -->
                        <div style="overflow-x: auto;">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Judul Buku</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Jatuh Tempo</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Status</th>
                                        <th>Denda</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($borrowings as $borrowing): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($borrowing['title']); ?></div>
                                                <div style="font-size: 0.9rem; color: #666;">
                                                    <?php echo htmlspecialchars($borrowing['author']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?></td>
                                            <td>
                                                <?php if ($borrowing['return_date']): ?>
                                                    <?php echo date('d/m/Y', strtotime($borrowing['return_date'])); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($borrowing['status'] === 'borrowed'): ?>
                                                    <span class="status-badge status-borrowed">Dipinjam</span>
                                                <?php elseif ($borrowing['status'] === 'returned'): ?>
                                                    <span class="status-badge status-returned">Dikembalikan</span>
                                                <?php elseif ($borrowing['status'] === 'overdue'): ?>
                                                    <span class="status-badge status-overdue">Terlambat</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($borrowing['fine_amount'] > 0): ?>
                                                    Rp <?php echo number_format($borrowing['fine_amount'], 0, ',', '.'); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>">
                                        &laquo; Prev
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">&laquo; Prev</span>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>">
                                        Next &raquo;
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">Next &raquo;</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
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
</body>
</html>
