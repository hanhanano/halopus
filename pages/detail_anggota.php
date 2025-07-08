<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

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

try {
    $stmt = $pdo->prepare("
        SELECT b.*, bk.title, bk.author, bk.publisher
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.member_id = ?
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$member_id]);
    $borrowing_history = $stmt->fetchAll();
} catch(PDOException $e) {
    $borrowing_history = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, bk.title, bk.author
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.member_id = ? AND b.status IN ('borrowed', 'overdue')
        ORDER BY b.due_date ASC
    ");
    $stmt->execute([$member_id]);
    $current_borrowings = $stmt->fetchAll();
} catch(PDOException $e) {
    $current_borrowings = [];
}

try {
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
} catch(PDOException $e) {
    $stats = ['total_borrowings' => 0, 'returned_books' => 0, 'current_books' => 0, 'total_fines' => 0];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Anggota - <?php echo htmlspecialchars($member['name'] ?? 'Perpustakaan Digital'); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        

        .member-detail-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        
        .member-info-card {
            background: #1a1a2e !important;
            border: 1px solid rgba(255, 105, 180, 0.2);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(138, 43, 226, 0.3);
            height: fit-content;
        }

        .member-profile {
            text-align: center;
            margin-bottom: 2rem;
        }

        .member-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(140deg, #8a2be2 0%, #ff1493 50%, #ffd700 100%);
            border: 3px solid #ffd700;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .member-name {
            margin: 0;
            color: #ffd700;
            font-size: 1.5rem;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(255, 215, 0, 0.3);
        }

        .member-code {
            margin: 0.5rem 0;
            color: #ff69b4;
            font-size: 1.1rem;
            font-weight: 600;
        }

        
        .member-details {
            margin-bottom: 2rem;
        }

        .detail-item {
            margin-bottom: 1rem;
        }

        .detail-label {
            color: #ffd700;
            font-weight: bold;
            display: block;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            color: #e8e3f3;
            font-size: 1rem;
        }

        
        .member-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        
        .member-activity {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        
        .stat-card {
            background: #1a1a2e !important;
            border: 1px solid rgba(255, 105, 180, 0.3);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(138, 43, 226, 0.25);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 105, 180, 0.4);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(255, 215, 0, 0.5);
        }

        .stat-label {
            color: #e8e3f3;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        
        .current-borrowings-card {
            background: #1a1a2e !important;
            border: 1px solid rgba(255, 105, 180, 0.2);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(138, 43, 226, 0.3);
        }

        .card-title {
            margin: 0 0 1rem 0;
            color: #ffd700;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .borrowing-item {
            padding: 1rem;
            border: 1px solid rgba(255, 105, 180, 0.3);
            border-radius: 8px;
            margin-bottom: 1rem;
            background: rgba(45, 27, 105, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: start;
            transition: all 0.3s ease;
        }

        .borrowing-item:hover {
            border-color: #ff69b4;
            background: rgba(45, 27, 105, 0.8);
            box-shadow: 0 4px 20px rgba(255, 105, 180, 0.2);
            transform: translateY(-2px);
        }

        .borrowing-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .book-title {
            color: #ff69b4;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .book-author,
        .borrow-date,
        .due-date {
            color: #b19cd9;
            font-size: 0.9rem;
        }

        
        .borrowing-history-card {
            background: #1a1a2e !important;
            border: 1px solid rgba(255, 105, 180, 0.2);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(138, 43, 226, 0.3);
        }

        .history-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .history-item:hover {
            background: rgba(255, 105, 180, 0.1);
            transform: translateX(5px);
            border-radius: 8px;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .history-info .book-title {
            color: #ff69b4;
            font-weight: bold;
        }

        .history-info .book-author,
        .history-info .borrow-date {
            color: #b19cd9;
            font-size: 0.9rem;
        }

        .no-data {
            color: #b19cd9;
            text-align: center;
            padding: 2rem;
            font-style: italic;
        }

        .view-all-history {
            text-align: center;
            margin-top: 1rem;
        }

        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-borrowed {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            color: white;
            box-shadow: 0 2px 10px rgba(255, 105, 180, 0.4);
        }

        .status-returned {
            background: linear-gradient(135deg, #32cd32, #228b22);
            color: white;
            box-shadow: 0 2px 10px rgba(50, 205, 50, 0.4);
        }

        .status-overdue {
            background: linear-gradient(135deg, #ff4500, #dc143c);
            color: white;
            box-shadow: 0 2px 10px rgba(255, 69, 0, 0.4);
            animation: pulse 2s infinite;
        }

        .status-active {
            background: linear-gradient(135deg, #32cd32, #228b22);
            color: white;
            box-shadow: 0 2px 10px rgba(50, 205, 50, 0.4);
        }

        .status-inactive {
            background: linear-gradient(135deg, #666, #444);
            color: #ccc;
            box-shadow: 0 2px 10px rgba(102, 102, 102, 0.4);
        }

        @keyframes pulse {
            0% { box-shadow: 0 2px 10px rgba(255, 69, 0, 0.4); }
            50% { box-shadow: 0 4px 20px rgba(255, 69, 0, 0.8); }
            100% { box-shadow: 0 2px 10px rgba(255, 69, 0, 0.4); }
        }

        
        .btn {
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffd700, #ffb347);
            color: #1a0b2e;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #ffb347, #ffd700);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff1493, #dc143c);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc143c, #ff1493);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 20, 147, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #32cd32, #228b22);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #228b22, #32cd32);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(50, 205, 50, 0.4);
        }

        .btn-primary {
            background: linear-gradient(135deg, #8a2be2, #4b0082);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4b0082, #8a2be2);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(138, 43, 226, 0.4);
        }

        .btn-info {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #ff1493, #ff69b4);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 105, 180, 0.4);
        }

        
        @media (max-width: 768px) {
            .member-detail-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .member-actions {
                justify-content: center;
            }
            
            .borrowing-item,
            .history-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .member-avatar {
                width: 80px;
                height: 80px;
                font-size: 1.5rem;
            }
            
            .member-name {
                font-size: 1.3rem;
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
            <?php if (isset($member)): ?>
                <div class="member-detail-grid">
                    <!-- Member Info Card -->
                    <div class="member-info-card">
                        <div class="member-profile">
                            <div class="member-avatar">
                                <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                            </div>
                            <h2 class="member-name"><?php echo htmlspecialchars($member['name']); ?></h2>
                            <p class="member-code"><?php echo htmlspecialchars($member['member_code']); ?></p>
                            <span class="status-badge status-<?php echo $member['status']; ?>">
                                <?php echo $member['status'] === 'active' ? 'Aktif' : ($member['status'] === 'inactive' ? 'Tidak Aktif' : 'Suspended'); ?>
                            </span>
                        </div>

                        <div class="member-details">
                            <div class="detail-item">
                                <strong class="detail-label">Email:</strong>
                                <span class="detail-value"><?php echo htmlspecialchars($member['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong class="detail-label">Telepon:</strong>
                                <span class="detail-value"><?php echo htmlspecialchars($member['phone']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong class="detail-label">Jenis Kelamin:</strong>
                                <span class="detail-value"><?php echo $member['gender'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></span>
                            </div>
                            <div class="detail-item">
                                <strong class="detail-label">Tanggal Lahir:</strong>
                                <span class="detail-value"><?php echo date('d/m/Y', strtotime($member['date_of_birth'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong class="detail-label">Tipe Anggota:</strong>
                                <span class="member-type-badge type-<?php echo $member['member_type']; ?>">
                                    <?php echo $member['member_type'] === 'student' ? 'Siswa' : ($member['member_type'] === 'teacher' ? 'Guru' : 'Umum'); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <strong class="detail-label">Bergabung:</strong>
                                <span class="detail-value"><?php echo date('d/m/Y', strtotime($member['join_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong class="detail-label">Alamat:</strong>
                                <span class="detail-value"><?php echo htmlspecialchars($member['address']); ?></span>
                            </div>
                        </div>

                        <div class="member-actions">
                            <a href="edit_anggota.php?id=<?php echo $member['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="peminjaman.php?member_id=<?php echo $member['id']; ?>" class="btn btn-success btn-sm">Pinjam Buku</a>
                            <a href="data_anggota.php" class="btn btn-primary btn-sm">← Kembali</a>
                        </div>
                    </div>

                    <!-- Statistics and Activity -->
                    <div class="member-activity">
                        <!-- Statistics -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_borrowings']; ?></div>
                                <div class="stat-label">Total Peminjaman</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['current_books']; ?></div>
                                <div class="stat-label">Sedang Dipinjam</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['returned_books']; ?></div>
                                <div class="stat-label">Sudah Dikembalikan</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">Rp <?php echo number_format($stats['total_fines'], 0, ',', '.'); ?></div>
                                <div class="stat-label">Total Denda</div>
                            </div>
                        </div>

                        <!-- Current Borrowings -->
                        <?php if (!empty($current_borrowings)): ?>
                            <div class="current-borrowings-card">
                                <h3 class="card-title">Buku yang Sedang Dipinjam</h3>
                                <?php foreach ($current_borrowings as $borrowing): ?>
                                    <div class="borrowing-item">
                                        <div class="borrowing-info">
                                            <strong class="book-title"><?php echo htmlspecialchars($borrowing['title']); ?></strong>
                                            <small class="book-author">oleh <?php echo htmlspecialchars($borrowing['author']); ?></small>
                                            <small class="borrow-date">Dipinjam: <?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?></small>
                                            <small class="due-date">Jatuh tempo: <?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?></small>
                                        </div>
                                        <span class="status-badge status-<?php echo $borrowing['status']; ?>">
                                            <?php echo $borrowing['status'] === 'overdue' ? 'Terlambat' : 'Dipinjam'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Borrowing History -->
                        <div class="borrowing-history-card">
                            <h3 class="card-title">Histori Peminjaman Terbaru</h3>
                            <?php if (empty($borrowing_history)): ?>
                                <p class="no-data">Belum ada histori peminjaman</p>
                            <?php else: ?>
                                <?php foreach ($borrowing_history as $borrowing): ?>
                                    <div class="history-item">
                                        <div class="history-info">
                                            <strong class="book-title"><?php echo htmlspecialchars($borrowing['title']); ?></strong>
                                            <small class="book-author">oleh <?php echo htmlspecialchars($borrowing['author']); ?></small>
                                            <small class="borrow-date"><?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?></small>
                                        </div>
                                        <span class="status-badge status-<?php echo $borrowing['status']; ?>">
                                            <?php echo match($borrowing['status']) {
                                                'borrowed' => 'Dipinjam',
                                                'returned' => 'Dikembalikan',
                                                'overdue' => 'Terlambat',
                                                default => $borrowing['status']
                                            }; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="view-all-history">
                                    <a href="histori_peminjaman.php?search=<?php echo urlencode($member['member_code']); ?>" class="btn btn-primary btn-sm">
                                        Lihat Semua Histori
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
</body>
</html>