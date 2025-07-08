<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: ../login.php?error=login_required&user=member');
    exit();
}

$member_id = $_SESSION['member_id'];
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
        SELECT b.*, bk.title, bk.author, bk.publisher, bk.cover_image
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.member_id = ? AND b.status IN ('borrowed', 'overdue')
        ORDER BY b.due_date ASC
    ");
    $stmt->execute([$member_id]);
    $current_borrowings = $stmt->fetchAll();
    
        $stmt = $pdo->prepare("
        SELECT b.*, bk.title, bk.author, bk.publisher
        FROM borrowings b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.member_id = ?
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$member_id]);
    $recent_history = $stmt->fetchAll();
    
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
    
        if (!empty($current_borrowings)) {
        $today = date('Y-m-d');
        $overdue_ids = [];
        
        foreach ($current_borrowings as $key => $borrowing) {
            if ($borrowing['status'] === 'borrowed' && $borrowing['due_date'] < $today) {
                $overdue_ids[] = $borrowing['id'];
                $current_borrowings[$key]['status'] = 'overdue';
                
                                $due_date = new DateTime($borrowing['due_date']);
                $today_date = new DateTime($today);
                $days_overdue = $today_date->diff($due_date)->days;
                
                                $fine = $days_overdue * 1000;
                $current_borrowings[$key]['fine_amount'] = $fine;
            }
        }
        
                if (!empty($overdue_ids)) {
            $placeholders = implode(',', array_fill(0, count($overdue_ids), '?'));
            $stmt = $pdo->prepare("
                UPDATE borrowings 
                SET status = 'overdue', updated_at = NOW() 
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($overdue_ids);
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $member = null;
    $current_borrowings = [];
    $recent_history = [];
    $stats = ['total_borrowings' => 0, 'returned_books' => 0, 'current_books' => 0, 'total_fines' => 0];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Anggota - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/style2member.css">
    <style>
        .hero {
            background-image: 
                linear-gradient(140deg, rgba(139, 69, 196, 0.7), rgba(168, 85, 247, 0.6)),
                url('../images/background.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5%;
        }

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1200px;
        }

        .hero-text {
            flex: 1;
            padding-right: 40px;
        }

        .hero-text h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .hero-text .highlight {
            color: #f59e0b;
            margin-top: -10px;
        }

        .hero-text p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .btn-register {
            display: inline-block;
            padding: 12px 24px;
            background-color: #d946ef;
            color: #1a1625;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(217, 70, 239, 0.3);
        }

        .btn-register:hover {
            background-color: #c026d3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(217, 70, 239, 0.4);
        }

        .hero-image {
            flex: 1;
            text-align: center;
        }

        .hero-image img {
            height: 30rem;
            width: 30rem;
            max-width: 100%;
            height: auto;
            animation: float 3s ease-in-out infinite;
            position: relative;
            filter: 
            drop-shadow(0 20px 35px rgba(0, 0, 0, 0.5))
            drop-shadow(0 8px 15px rgba(0, 0, 0, 0.4));
        }

        .hero-image img::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            height: 15px;
            background: radial-gradient(ellipse, rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0.2) 50%, transparent 70%);
            border-radius: 50%;
            animation: shadowFloat 3s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { 
                transform: translateY(0px);
            }
            50% { 
                transform: translateY(-12px);
            }
        }

        @keyframes shadowFloat {
            0%, 100% { 
                transform: translateX(-50%) translateY(5px) scale(1);
                opacity: 0.5;
            }
            50% { 
                transform: translateX(-50%) translateY(15px) scale(1.2);
                opacity: 0.7;
            }
        }

        .book-cover {
            width: 120px;
            height: 160px;
            background-color: #16213e;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .book-cover-text {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            text-align: center;
            font-weight: bold;
            color: #16213e;
            background: #16213e;
        }

        @media (max-width: 768px) {
            .hero-content {
                flex-direction: column;
                text-align: center;
            }

            .hero-text {
                padding-right: 0;
                margin-bottom: 2rem;
            }

            .hero-image {
                display: none;
            }
        }

        @media screen and (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 480px) {
            .hero {
                padding: 2rem 0;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .action-card {
                padding: 1.5rem;
                min-height: 120px;
            }

            .action-icon {
                font-size: 2rem;
                margin-bottom: 0.5rem;
            }

            .action-card h3 {
                font-size: 1.1rem;
                margin-bottom: 0.3rem;
            }

            .action-card p {
                font-size: 0.9rem;
            }
        }

        .rotating-text {
        display: block;
        overflow: visible;
        vertical-align: top;
        min-height: max-content;
        max-width: 90vw;
        line-height: 1.2;
        text-align: left;
        }

        .rotating-text span {
        display: inline-block;
        opacity: 0;
        transform: translateY(100%);
        transition: opacity 0.7s cubic-bezier(0.65, 0, 0.35, 1),
                    transform 0.7s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .rotating-text.show span {
        opacity: 1;
        transform: translateY(0);
        }

        .rotating-text .space {
        display: inline-block;
        width: 0.2em;
        }

        
        @media (max-width: 768px) {
            .rotating-text {

                white-space: normal;
                max-width: 100%;
                text-align: center;
            }
        }

    </style>
</head>
<body>
    <!-- Member Header -->
    

    <header class="header" style="padding-left: 50px;">
        <a href="dashboard.php" class="logo"><img src="../images/logo.svg" alt="Profile" class="firsticon" > HALO-PUS</a>
    </header>

    <!-- Main Content -->
    <main class="container">
        <section class="hero">
            <div class="hero-content">
                <div class="hero-image">
                    <img src="../images/illustration2.png" alt="Ilustrasi Anggota">
                </div>
                <div class="hero-text">
                    <h1 id="rotating-text" class="rotating-text"></h1>
                    <h1><span class="highlight"><?php echo htmlspecialchars($member['name']); ?>!</span></h1>
                    <p>Lihat koleksi buku terbaru dan riwayat peminjaman Anda di sini.</p>
                    <a href="history.php" class="btn-register">Lihat Riwayat</a>
                </div>
            </div>
        </section>

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
                
                <ul class="menu-list">
                    <li>
                        <a href="dashboard.php" class="active">
                            <img src="../images/bluebank.svg" alt="Dashboard" class="menu-icon">
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
                <!-- Statistics -->
                <div class="dashboard-card">
                    <h2>Statistik Peminjaman</h2>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total_borrowings']; ?></div>
                            <div class="stat-label">Total Peminjaman</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['current_books']; ?></div>
                            <div class="stat-label">Sedang Dipinjam</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['returned_books']; ?></div>
                            <div class="stat-label">Sudah Dikembalikan</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-value">Rp <?php echo number_format($stats['total_fines'], 0, ',', '.'); ?></div>
                            <div class="stat-label">Total Denda</div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="borrow.php" class="action-button">
                            <div class="action-icon"><img src="../images/bukuquick.svg" alt="pinjam buku" class="icon"></div>
                            <div class="action-label">Pinjam Buku</div>
                        </a>
                        
                        <a href="history.php" class="action-button">
                            <div class="action-icon"><img src="../images/histori.svg" alt="Histori" class="icon"></div>
                            <div class="action-label">Lihat Riwayat</div>
                        </a>
                        
                        <a href="profile.php" class="action-button">
                            <div class="action-icon"><img src="../images/anggotaquick.svg" alt="profile" class="icon"></div>
                            <div class="action-label">Edit Profil</div>
                        </a>
                    </div>
                </div>
                
                <!-- Current Borrowings -->
                <div class="dashboard-card">
                    <h2>Buku yang Sedang Dipinjam</h2>
                    
                    <?php if (empty($current_borrowings)): ?>
                        <div class="empty-state">
                            <h3>Tidak ada buku yang sedang dipinjam</h3>
                            <p>Anda belum meminjam buku apapun saat ini.</p>
                            <p><a href="borrow.php" class="btn btn-primary" style="margin-top: 1rem;">Pinjam Buku Sekarang</a></p>
                        </div>
                    <?php else: ?>
                        <ul class="book-list">
                            <?php foreach ($current_borrowings as $borrowing): ?>
                                <li class="book-item">
                                    <div class="book-cover">
                                        <?php if ($borrowing['cover_image'] && $borrowing['cover_image'] !== 'default.jpg' && file_exists('../uploads/covers/' . $borrowing['cover_image'])): ?>
                                            <img src="../uploads/covers/<?php echo htmlspecialchars($borrowing['cover_image']); ?>" 
                                                 alt="Cover <?php echo htmlspecialchars($borrowing['title']); ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #16213e; font-weight: bold; font-size: 0.8rem; text-align: center; padding: 0.25rem;">
                                                <?php echo htmlspecialchars($borrowing['title']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="book-info">
                                        <h3 class="book-title"><?php echo htmlspecialchars($borrowing['title']); ?></h3>
                                        <p class="book-author">oleh <?php echo htmlspecialchars($borrowing['author']); ?></p>
                                        
                                        <div class="book-meta">
                                            <div class="book-meta-item">
                                                <strong>Dipinjam:</strong> <?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?>
                                            </div>
                                            <div class="book-meta-item">
                                                <strong>Jatuh Tempo:</strong> <?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?>
                                            </div>
                                            <div class="book-meta-item">
                                                <strong>Status:</strong> 
                                                <?php if ($borrowing['status'] === 'borrowed'): ?>
                                                    <span class="status-badge status-borrowed">Dipinjam</span>
                                                <?php elseif ($borrowing['status'] === 'overdue'): ?>
                                                    <span class="status-badge status-overdue">Terlambat</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($borrowing['status'] === 'overdue'): ?>
                                                <div class="book-meta-item">
                                                    <strong>Denda:</strong> Rp <?php echo number_format($borrowing['fine_amount'], 0, ',', '.'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <!-- Recent History -->
                <div class="dashboard-card">
                    <h2>Riwayat Peminjaman Terbaru</h2>
                    
                    <?php if (empty($recent_history)): ?>
                        <div class="empty-state">
                            <h3>📋 Belum ada riwayat peminjaman</h3>
                            <p>Anda belum memiliki riwayat peminjaman buku.</p>
                        </div>
                    <?php else: ?>
                        <ul class="book-list">
                            <?php foreach ($recent_history as $history): ?>
                                <li class="book-item">
                                    <div class="book-info">
                                        <h3 class="book-title"><?php echo htmlspecialchars($history['title']); ?></h3>
                                        <p class="book-author">oleh <?php echo htmlspecialchars($history['author']); ?></p>
                                        
                                        <div class="book-meta">
                                            <div class="book-meta-item">
                                                <strong>Dipinjam:</strong> <?php echo date('d/m/Y', strtotime($history['borrow_date'])); ?>
                                            </div>
                                            
                                            <?php if ($history['return_date']): ?>
                                                <div class="book-meta-item">
                                                    <strong>Dikembalikan:</strong> <?php echo date('d/m/Y', strtotime($history['return_date'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="book-meta-item">
                                                    <strong>Jatuh Tempo:</strong> <?php echo date('d/m/Y', strtotime($history['due_date'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="book-meta-item">
                                                <strong>Status:</strong> 
                                                <?php if ($history['status'] === 'borrowed'): ?>
                                                    <span class="status-badge status-borrowed">Dipinjam</span>
                                                <?php elseif ($history['status'] === 'returned'): ?>
                                                    <span class="status-badge status-returned">Dikembalikan</span>
                                                <?php elseif ($history['status'] === 'overdue'): ?>
                                                    <span class="status-badge status-overdue">Terlambat</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div style="margin-top: 1rem; text-align: center;">
                            <a href="history.php" class="btn btn-primary">Lihat Semua Riwayat</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        const texts = [
            "Selamat Datang,",                   "Welcome,",                          "Bienvenue,",                        "Bienvenido,",                       "Willkommen,",                       "ようこそ,",                            "欢迎,",                               "환영합니다,",                         "Добро пожаловать,",              ];

        let currentIndex = 0;
        const rotationInterval = 5000;
        const rotatingTextEl = document.getElementById("rotating-text");

        function showCharacters(text) {
            rotatingTextEl.innerHTML = "";
            [...text].forEach((char, i) => {
                const span = document.createElement("span");
                span.textContent = char;
                if (char === " ") span.classList.add("space");
                span.style.transitionDelay = `${i * 60}ms`;
                rotatingTextEl.appendChild(span);
            });

                        setTimeout(() => {
                rotatingTextEl.classList.add("show");
            }, 50);         }

        function updateText() {
            rotatingTextEl.classList.remove("show");
            setTimeout(() => {
                showCharacters(texts[currentIndex]);
                currentIndex = (currentIndex + 1) % texts.length;
            }, 2000);         }

                setInterval(updateText, rotationInterval);

                setTimeout(() => {
            updateText();
        }, 100);     </script>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistem Perpustakaan Digital.</p>
        </div>
    </footer>
</body>
</html>
