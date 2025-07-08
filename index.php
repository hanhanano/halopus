<?php
require_once 'auth.php';
require_once 'config/koneksi.php';

try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books");
    $stmt->execute();
    $total_books = $stmt->fetch()['total'];
    
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM members WHERE status = 'active'");
    $stmt->execute();
    $total_members = $stmt->fetch()['total'];
    
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrowings WHERE status IN ('borrowed', 'overdue')");
    $stmt->execute();
    $borrowed_books = $stmt->fetch()['total'];
    
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrowings WHERE status = 'overdue'");
    $stmt->execute();
    $overdue_books = $stmt->fetch()['total'];
    
        $stmt = $pdo->prepare("
        SELECT b.title, m.name as member_name, br.borrow_date, br.due_date, br.status
        FROM borrowings br
        JOIN books b ON br.book_id = b.id
        JOIN members m ON br.member_id = m.id
        ORDER BY br.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recent_borrowings = $stmt->fetchAll();
    
} catch(PDOException $e) {
    $error_message = "Error loading data: " . $e->getMessage();
    $total_books = $total_members = $borrowed_books = $overdue_books = 0;
    $recent_borrowings = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Perpustakaan Digital</title>
    <meta name="description" content="Sistem manajemen perpustakaan digital untuk mengelola buku, anggota, dan transaksi peminjaman.">
    <link rel="stylesheet" href="css/style.css">
    <style>
        
        .hero {
            background-image: 
                linear-gradient(140deg, rgba(139, 69, 196, 0.7), rgba(168, 85, 247, 0.6)),
                url('images/background.jpg');
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
            height: 20rem;
            width: 20rem;
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

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .quick-actions-section {
            margin: 3rem 0;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: linear-gradient(140deg, #8b45c4 0%, #a855f7 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(139, 69, 196, 0.4);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-height: 180px;
            justify-content: center;
            border: 1px solid rgba(217, 70, 239, 0.3);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(139, 69, 196, 0.6);
            color: white;
            border-color: #d946ef;
        }

        .action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #f59e0b;
        }

        .action-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .action-card p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
        }

        
        .action-card.member {
            background: linear-gradient(140deg, #8b45c4 0%, #9333ea 100%);
        }
        .action-card.book {
            background: linear-gradient(140deg, #a855f7 0%, #d946ef 100%);
        }
        .action-card.borrow {
            background: linear-gradient(140deg, #9333ea 0%, #c026d3 100%);
        }
        .action-card.return {
            background: linear-gradient(140deg, #7c3aed 0%, #a855f7 100%);
        }
        .action-card.history {
            background: linear-gradient(140deg, #6d28d9 0%, #8b45c4 100%);
        }
        .action-card.category {
            background: linear-gradient(140deg, #5b21b6 0%, #7c3aed 100%);
        }

        .recent-activity {
            background: #1a1625;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(139, 69, 196, 0.3);
            border: 1px solid #2d1b3d;
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #441d5c;
            color: #c8b8d4;
        }

        .activity-item:last-child {
            border-bottom: none;
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
        min-height: 2.5em;
        max-width: 90vw;
        line-height: 1.2;
        text-align: left;
        }

        .rotating-text span {
        display: inline;
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
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><img src="images/whitemenu.svg" alt="Menu" class="icon"> Menu</h3>
            <button class="sidebar-close" id="sidebarClose">&times;</button>
        </div>
        <ul class="sidebar-menu" id="sidebarMenu">
            <li><a href="index.php" class="active"><img src="images/bluebank.svg" alt="Dashboard" class="icon"> Dashboard</a></li>
            <li><a href="pages/data_anggota.php"><img src="images/blackanggota.svg" alt="Anggota" class="icon"> Data Anggota</a></li>
            <li><a href="pages/cari_buku.php"><img src="images/blackbuku.svg" alt="Buku" class="icon"> Katalog Buku</a></li>
            <li><a href="pages/peminjaman.php"><img src="images/blackpinjam.svg" alt="Pinjam" class="icon"> Peminjaman</a></li>
            <li><a href="pages/pengembalian.php"><img src="images/blackkembali.svg" alt="Kembali" class="icon"> Pengembalian</a></li>
            <li><a href="pages/histori_peminjaman.php"><img src="images/blackhistori.svg" alt="Histori" class="icon"> Histori</a></li>
            <li><a href="pages/kategori.php"><img src="images/blackkategori.svg" alt="Kategori" class="icon"> Kategori</a></li>
            <?php if (hasRole('super_admin')): ?>
            <li>
                <a href="pages/manage_admin.php">
                    <img src="images/blackadmin.svg" alt="Admin" class="icon"> Kelola Admin
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
                <a href="index.php" class="logo"><img src="images/logo.svg" alt="Profile" class="firsticon" > HALO-PUS</a>
            </div>
            <div class="nav-right">
                <div class="user-info">
                    <span class="user-name"><img src="images/profile.svg" alt="Profile" class="icon" > <?php echo htmlspecialchars(getCurrentAdmin()['full_name']); ?></span>
                    <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin logout?')">
                        <img src="images/quit.svg" alt="Profile" class="icon"> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content" style="margin-top: 0;" >
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 id="rotating-text" class="rotating-text"></h1>
                    <p>Kelola koleksi buku, anggota, dan transaksi peminjaman dengan mudah dan efisien</p>
                    <a href="pages/cari_buku.php" class="btn-register">Daftar Buku</a>
                    <a href="pages/detail_anggota.php" class="btn-register">Daftar Anggota</a>
                </div>
                <div class="hero-image">
                    <img src="images/illustration.png" alt="Ilustrasi Pustakawan">
                </div>
            </div>
        </section>

        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <section class="stats-section">
                <h2 class="section-title">Statistik Perpustakaan</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><img src="images/totalbuku.svg" alt="Total" class="icon"></div>
                        <div class="stat-number"><?php echo number_format($total_books); ?></div>
                        <div class="stat-label">Total Jenis Buku</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><img src="images/anggota.svg" alt="Anggota" class="icon"></div>
                        <div class="stat-number"><?php echo number_format($total_members); ?></div>
                        <div class="stat-label">Anggota Aktif</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><img src="images/dipinjam.svg" alt="Dipinjam" class="icon"></div>
                        <div class="stat-number"><?php echo number_format($borrowed_books); ?></div>
                        <div class="stat-label">Sedang Dipinjam</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><img src="images/telat.svg" alt="Telat" class="icon"></div>
                        <div class="stat-number"><?php echo number_format($overdue_books); ?></div>
                        <div class="stat-label">Terlambat</div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions-section">
                <h2 class="section-title">Aksi Cepat</h2>
                <div class="quick-actions">
                    <!-- Semua kartu dalam satu container -->
                    <a href="pages/tambah_anggota.php" class="action-card member">
                        <div class="action-icon"><img src="images/anggotaquick.svg" alt="Anggota" class="icon"></div>
                        <h3>Tambah Anggota</h3>
                        <p>Daftarkan anggota baru</p>
                    </a>
                    
                    <a href="pages/tambah_buku.php" class="action-card book">
                        <div class="action-icon"><img src="images/bukuquick.svg" alt="Tambah Buku" class="icon"></div>
                        <h3>Tambah Buku</h3>
                        <p>Tambah buku ke koleksi</p>
                    </a>
                    
                    <a href="pages/peminjaman.php" class="action-card borrow">
                        <div class="action-icon"><img src="images/peminjaman.svg" alt="Peminjaman" class="icon"></div>
                        <h3>Peminjaman</h3>
                        <p>Proses peminjaman buku</p>
                    </a>

                    <a href="pages/pengembalian.php" class="action-card return">
                        <div class="action-icon"><img src="images/pengembalian.svg" alt="Pengembalian" class="icon"></div>
                        <h3>Pengembalian</h3>
                        <p>Proses pengembalian buku</p>
                    </a>
                    
                    <a href="pages/histori_peminjaman.php" class="action-card history">
                        <div class="action-icon"><img src="images/histori.svg" alt="Histori" class="icon"></div>
                        <h3>Histori</h3>
                        <p>Lihat riwayat peminjaman</p>
                    </a>
                    
                    <a href="pages/kategori.php" class="action-card category">
                        <div class="action-icon"><img src="images/kategori.svg" alt="Kategori" class="icon"></div>
                        <h3>Kategori</h3>
                        <p>Kelola kategori buku</p>
                    </a>
                    
                </div>
            </section>


            <!-- Recent Activity -->
            <section class="recent-activity-section">
                <h2 class="section-title">Aktivitas Terbaru</h2>
                <div class="recent-activity">
                    <?php if (empty($recent_borrowings)): ?>
                        <p class="text-center">Belum ada aktivitas peminjaman.</p>
                    <?php else: ?>
                        <?php foreach ($recent_borrowings as $borrowing): ?>
                            <div class="activity-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($borrowing['title']); ?></strong>
                                    <br>
                                    <small>Dipinjam oleh: <?php echo htmlspecialchars($borrowing['member_name']); ?></small>
                                    <br>
                                    <small>Tanggal: <?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?> | 
                                           Jatuh tempo: <?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?></small>
                                </div>
                                <div>
                                    <span class="status-badge status-<?php echo $borrowing['status']; ?>">
                                        <?php 
                                        echo $borrowing['status'] === 'borrowed' ? 'Dipinjam' : 
                                             ($borrowing['status'] === 'overdue' ? 'Terlambat' : 'Dikembalikan');
                                        ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-3">
                            <a href="pages/histori_peminjaman.php" class="btn btn-primary">Lihat Semua Histori</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistem Perpustakaan Digital.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script src="js/animasi.js"></script>
    <script>
        const texts = [
            "Temukan Buku Favoritmu Online",
            "Baca Dimana Saja, Kapan Saja",
            "Koleksi Digital Terlengkap",
            "Pinjam Buku Tanpa Antri",
            "Perpustakaan Digital, Lebih Praktis",
            "Akses Ribuan Buku Dalam Genggaman",
            "Baca Gratis, Mudah & Cepat"
        ];

        let currentIndex = 0;
        const rotationInterval = 5000;
        const rotatingTextEl = document.getElementById("rotating-text");

        function showCharacters(text) {
            rotatingTextEl.innerHTML = "HALO-PUS ";
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

</body>
</html>
