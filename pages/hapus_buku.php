<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$book_id = (int)($_GET['id'] ?? 0);
$book = null;
$error_message = '';
$success_message = '';

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

$has_active_borrowings = false;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrowings WHERE book_id = ? AND status IN ('borrowed', 'overdue')");
    $stmt->execute([$book_id]);
    $active_borrowings = $stmt->fetch()['count'];
    $has_active_borrowings = $active_borrowings > 0;
} catch(PDOException $e) {
    $error_message = "Error checking borrowings: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $book) {
    if ($has_active_borrowings) {
        $error_message = "Tidak dapat menghapus buku yang sedang dipinjam!";
    } else {
        try {
                        $pdo->beginTransaction();
            
                        $stmt = $pdo->prepare("DELETE FROM borrowings WHERE book_id = ?");
            $stmt->execute([$book_id]);
            
                        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            
                        if ($book['cover_image'] && $book['cover_image'] !== 'default.jpg') {
                $cover_path = '../uploads/covers/' . $book['cover_image'];
                if (file_exists($cover_path)) {
                    unlink($cover_path);
                }
            }
            
            $pdo->commit();
            $success_message = "Buku berhasil dihapus!";
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error deleting book: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Buku - <?php echo htmlspecialchars($book['title'] ?? 'Perpustakaan Digital'); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .delete-container {
            max-width: 600px;
            margin: 2rem auto;
            background: #1a1a2e;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .delete-warning {
            background: #2d1b69;
            border: 1px solid #8b5cf6;
            color: #fbbf24;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .delete-warning-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .book-info {
            background: #16213e;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .book-cover-small {
            width: 80px;
            height: 120px;
            background: linear-gradient(140deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            text-align: center;
            padding: 0.5rem;
            border-radius: 4px;
            float: left;
            margin-right: 1rem;
            margin-bottom: 1rem;
        }
        
        .book-cover-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .book-details h3 {
            margin: 0 0 0.5rem 0;
            color: #e5e7eb;
        }
        
        .book-details p {
            margin: 0.25rem 0;
            color: #9ca3af;
        }
        
        .danger-zone {
            background: #4c1d95;
            border: 1px solid #8b5cf6;
            color: #fbbf24;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-danger {
            background: linear-gradient(140deg, #ec4899 0%, #8b5cf6 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.4);
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .book-cover-small {
                float: none;
                margin: 0 auto 1rem;
            }
        }
    </style>
</head>
<body>
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

    <main class="container">
        <?php if ($success_message): ?>
            <div class="delete-container">
                <div class="alert alert-success text-center">
                    <h2>Buku Berhasil Dihapus</h2>
                    <p><?php echo $success_message; ?></p>
                    <div class="action-buttons">
                        <a href="cari_buku.php" class="btn btn-primary"> Kembali ke Katalog</a>
                        <a href="../index.php" class="btn btn-primary"> Dashboard</a>
                    </div>
                </div>
            </div>
        <?php elseif ($book): ?>
            <div class="delete-container">
                <h1 style="text-align: center; color: #dc3545; margin-bottom: 2rem;"> Hapus Buku</h1>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="delete-warning">
                    <div class="delete-warning-icon">⚠️</div>
                    <h3>Peringatan!</h3>
                    <p>Anda akan menghapus buku ini secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
                </div>

                <div class="book-info clearfix">
                    <div class="book-cover-small">
                        <?php if ($book['cover_image'] && $book['cover_image'] !== 'default.jpg' && file_exists('../uploads/covers/' . $book['cover_image'])): ?>
                            <img src="../uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <?php else: ?>
                            <?php echo htmlspecialchars(substr($book['title'], 0, 20)); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="book-details">
                        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p><strong>Penulis:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p><strong>Penerbit:</strong> <?php echo htmlspecialchars($book['publisher']); ?> (<?php echo $book['year_published']; ?>)</p>
                        <?php if ($book['isbn']): ?>
                            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                        <?php endif; ?>
                        <p><strong>Jumlah Buku:</strong> <?php echo $book['total_copies']; ?> eksemplar</p>
                        <?php if ($book['location']): ?>
                            <p><strong>Lokasi:</strong> <?php echo htmlspecialchars($book['location']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($has_active_borrowings): ?>
                    <div class="danger-zone">
                        <h4>❌ Tidak Dapat Menghapus</h4>
                        <p>Buku ini sedang dipinjam oleh anggota. Tunggu hingga semua peminjaman selesai sebelum menghapus buku.</p>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="detail_buku.php?id=<?php echo $book['id']; ?>" class="btn btn-primary"> Lihat Detail</a>
                        <a href="cari_buku.php" class="btn btn-primary"> Kembali ke Katalog</a>
                    </div>
                <?php else: ?>
                    <div class="danger-zone">
                        <h4> Konfirmasi Penghapusan</h4>
                        <p>Dengan menghapus buku ini, semua data terkait termasuk riwayat peminjaman akan ikut terhapus.</p>
                        <p><strong>Apakah Anda yakin ingin melanjutkan?</strong></p>
                    </div>

                    <form method="POST" id="deleteForm">
                        <div class="action-buttons">
                            <button type="submit" name="confirm_delete" class="btn btn-danger" 
                                    onclick="return confirmDelete()">
                                 Ya, Hapus Buku
                            </button>
                            <a href="detail_buku.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">❌ Batal</a>
                            <a href="cari_buku.php" class="btn btn-primary"> Kembali ke Katalog</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
        function confirmDelete() {
            return confirm('Apakah Anda benar-benar yakin ingin menghapus buku "<?php echo htmlspecialchars($book['title'] ?? ''); ?>"?\n\nTindakan ini tidak dapat dibatalkan!');
        }
    </script>
</body>
</html>
