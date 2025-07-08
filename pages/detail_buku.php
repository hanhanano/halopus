<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$book_id = (int)($_GET['id'] ?? 0);

if ($book_id <= 0) {
    header('Location: cari_buku.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        header('Location: cari_buku.php');
        exit;
    }
} catch(PDOException $e) {
    $error_message = "Error loading book: " . $e->getMessage();
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, m.name as member_name, m.member_code
        FROM borrowings b
        JOIN members m ON b.member_id = m.id
        WHERE b.book_id = ? AND b.status IN ('borrowed', 'overdue')
        ORDER BY b.borrow_date DESC
    ");
    $stmt->execute([$book_id]);
    $current_borrowings = $stmt->fetchAll();
} catch(PDOException $e) {
    $current_borrowings = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, m.name as member_name, m.member_code
        FROM borrowings b
        JOIN members m ON b.member_id = m.id
        WHERE b.book_id = ?
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$book_id]);
    $borrowing_history = $stmt->fetchAll();
} catch(PDOException $e) {
    $borrowing_history = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_borrowings,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as times_returned,
            COUNT(CASE WHEN status IN ('borrowed', 'overdue') THEN 1 END) as currently_borrowed
        FROM borrowings 
        WHERE book_id = ?
    ");
    $stmt->execute([$book_id]);
    $stats = $stmt->fetch();
} catch(PDOException $e) {
    $stats = ['total_borrowings' => 0, 'times_returned' => 0, 'currently_borrowed' => 0];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Buku - <?php echo htmlspecialchars($book['title'] ?? 'Perpustakaan Digital'); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <style>
        
        .container {
            background: transparent;
        }

        
        .book-detail-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        
        .book-info-card {
            background: #1a1a2e !important;
            border: 1px solid rgba(255, 105, 180, 0.2);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(138, 43, 226, 0.3);
            height: fit-content;
        }

        
        .book-cover-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .book-cover-image {
            max-width: 200px;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .book-cover-placeholder {
            width: 200px;
            height: 300px;
            background: linear-gradient(140deg, #8a2be2 0%, #ff1493 50%, #ffd700 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
            padding: 1rem;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        
        .book-detail-item {
            margin-bottom: 1rem;
        }

        .book-detail-label {
            color: #ffd700;
            font-weight: bold;
            display: block;
            margin-bottom: 0.25rem;
        }

        .book-detail-value {
            color: #e8e3f3;
            font-size: 1.1rem;
            line-height: 1.4;
        }

        
        .book-actions {
            margin-top: 2rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
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
            letter-spacing: 0.5px;
        }

        
        .current-borrowings {
            background: #1a1a2e !important;
            border: 1px solid rgba(255, 105, 180, 0.2);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(138, 43, 226, 0.3);
            margin-bottom: 2rem;
        }

        .current-borrowings h3 {
            margin: 0 0 1rem 0;
            color: #ffd700;
            font-size: 1.3rem;
        }

        
        .borrowing-item {
            padding: 1rem;
            border: 1px solid rgba(255, 105, 180, 0.3);
            border-radius: 8px;
            margin-bottom: 1rem;
            background: rgba(45, 27, 105, 0.5);
            transition: background 0.3s ease;
        }

        .borrowing-item:hover {
            background: rgba(45, 27, 105, 0.8);
        }

        .borrowing-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .borrowing-member {
            color: #ff69b4;
            font-weight: bold;
        }

        .borrowing-code {
            color: #e8e3f3;
            font-size: 0.9rem;
        }

        .borrowing-dates {
            color: #b19cd9;
            font-size: 0.85rem;
        }

        
        .borrowing-history {
            background: #1a1a2e !important;
            border: 1px solid rgba(255, 105, 180, 0.2);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(138, 43, 226, 0.3);
        }

        .borrowing-history h3 {
            margin: 0 0 1rem 0;
            color: #ffd700;
            font-size: 1.3rem;
        }

        .no-history {
            color: #b19cd9;
            text-align: center;
            padding: 2rem;
            font-style: italic;
        }

        .history-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 105, 180, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }

        .history-item:hover {
            background: rgba(45, 27, 105, 0.3);
            border-radius: 8px;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-member {
            color: #ff69b4;
            font-weight: bold;
        }

        .history-code {
            color: #e8e3f3;
            font-size: 0.9rem;
        }

        .history-date {
            color: #b19cd9;
            font-size: 0.85rem;
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

        .btn[style*="cursor: not-allowed"] {
            background: linear-gradient(135deg, #666, #444);
            color: #999;
            cursor: not-allowed !important;
        }

        
        @media (max-width: 768px) {
            .book-detail-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .book-actions {
                justify-content: center;
            }
            
            .borrowing-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .history-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
            <li><a href="cari_buku.php" class="active"><img src="../images/bluebuku.svg" alt="Buku" class="icon"> Katalog Buku</a></li>
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
            <?php if (isset($book)): ?>
                <div class="book-detail-grid">
                    <!-- Book Info Card -->
                    <div class="book-info-card">
                        <!-- Book Cover -->
                        <div class="book-cover-container">
                            <?php if ($book['cover_image'] && $book['cover_image'] !== 'default.jpg' && file_exists('../uploads/covers/' . $book['cover_image'])): ?>
                                <img src="../uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     alt="Cover <?php echo htmlspecialchars($book['title']); ?>"
                                     class="book-cover-image">
                            <?php else: ?>
                                <div class="book-cover-placeholder">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="book-detail-item">
                                <span class="book-detail-label">Judul:</span>
                                <span class="book-detail-value"><?php echo htmlspecialchars($book['title']); ?></span>
                            </div>
                            <div class="book-detail-item">
                                <span class="book-detail-label">Penulis:</span>
                                <span class="book-detail-value"><?php echo htmlspecialchars($book['author']); ?></span>
                            </div>
                            <div class="book-detail-item">
                                <span class="book-detail-label">Penerbit:</span>
                                <span class="book-detail-value"><?php echo htmlspecialchars($book['publisher']); ?></span>
                            </div>
                            <div class="book-detail-item">
                                <span class="book-detail-label">Tahun Terbit:</span>
                                <span class="book-detail-value"><?php echo $book['year_published']; ?></span>
                            </div>
                            <?php if ($book['isbn']): ?>
                                <div class="book-detail-item">
                                    <span class="book-detail-label">ISBN:</span>
                                    <span class="book-detail-value"><?php echo htmlspecialchars($book['isbn']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($book['category']): ?>
                                <div class="book-detail-item">
                                    <span class="book-detail-label">Kategori:</span>
                                    <span class="book-detail-value"><?php echo htmlspecialchars($book['category']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($book['location']): ?>
                                <div class="book-detail-item">
                                    <span class="book-detail-label">Lokasi:</span>
                                    <span class="book-detail-value"><?php echo htmlspecialchars($book['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="book-detail-item">
                                <span class="book-detail-label">Ketersediaan:</span>
                                <span class="book-detail-value"><?php echo $book['available_copies']; ?> dari <?php echo $book['total_copies']; ?> buku</span>
                            </div>
                            <?php if ($book['description']): ?>
                                <div class="book-detail-item">
                                    <span class="book-detail-label">Deskripsi:</span>
                                    <span class="book-detail-value"><?php echo nl2br(htmlspecialchars($book['description'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="book-actions">
                            <a href="edit_buku.php?id=<?php echo $book['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="hapus_buku.php?id=<?php echo $book['id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Yakin ingin menghapus buku \'<?php echo htmlspecialchars($book['title']); ?>\'?')">
                                Hapus
                            </a>
                            <?php if ($book['available_copies'] > 0): ?>
                                <a href="peminjaman.php?book_id=<?php echo $book['id']; ?>" class="btn btn-success btn-sm">Pinjam</a>
                            <?php else: ?>
                                <span class="btn btn-danger btn-sm" style="cursor: not-allowed;">Habis</span>
                            <?php endif; ?>
                            <a href="cari_buku.php" class="btn btn-primary btn-sm">← Kembali</a>
                        </div>
                    </div>

                    <!-- Statistics and Activity -->
                    <div>
                        <!-- Statistics -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total_borrowings']; ?></div>
                                <div class="stat-label">Total Peminjaman</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['currently_borrowed']; ?></div>
                                <div class="stat-label">Sedang Dipinjam</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['times_returned']; ?></div>
                                <div class="stat-label">Kali Dikembalikan</div>
                            </div>
                        </div>

                        <!-- Current Borrowings -->
                        <?php if (!empty($current_borrowings)): ?>
                            <div class="current-borrowings">
                                <h3>Sedang Dipinjam Oleh</h3>
                                <?php foreach ($current_borrowings as $borrowing): ?>
                                    <div class="borrowing-item">
                                        <div class="borrowing-header">
                                            <div>
                                                <div class="borrowing-member"><?php echo htmlspecialchars($borrowing['member_name']); ?></div>
                                                <div class="borrowing-code"><?php echo htmlspecialchars($borrowing['member_code']); ?></div>
                                                <div class="borrowing-dates">Dipinjam: <?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?></div>
                                                <div class="borrowing-dates">Jatuh tempo: <?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?></div>
                                            </div>
                                            <span class="status-badge status-<?php echo $borrowing['status']; ?>">
                                                <?php echo $borrowing['status'] === 'overdue' ? 'Terlambat' : 'Dipinjam'; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Borrowing History -->
                        <div class="borrowing-history">
                            <h3>Histori Peminjaman Terbaru</h3>
                            <?php if (empty($borrowing_history)): ?>
                                <p class="no-history">Belum ada histori peminjaman</p>
                            <?php else: ?>
                                <?php foreach ($borrowing_history as $borrowing): ?>
                                    <div class="history-item">
                                        <div>
                                            <div class="history-member"><?php echo htmlspecialchars($borrowing['member_name']); ?></div>
                                            <div class="history-code"><?php echo htmlspecialchars($borrowing['member_code']); ?></div>
                                            <div class="history-date"><?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?></div>
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
                                    <a href="histori_peminjaman.php?search=<?php echo urlencode($book['title']); ?>" class="btn btn-primary btn-sm">
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