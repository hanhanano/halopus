<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$author = trim($_GET['author'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR author LIKE ? OR publisher LIKE ? OR isbn LIKE ?)";
    $search_term = "%{$search}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if (!empty($category)) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
}

if (!empty($author)) {
    $where_conditions[] = "author LIKE ?";
    $params[] = "%{$author}%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    $count_sql = "SELECT COUNT(*) as total FROM books {$where_clause}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_books = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_books / $per_page);
} catch(PDOException $e) {
    $total_books = 0;
    $total_pages = 0;
}

$books = [];
try {
    $sql = "SELECT * FROM books {$where_clause} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Error loading books: " . $e->getMessage();
}

try {
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/form.css">
    <style>
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        @media (max-width: 768px) {
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
            <h1 class="section-title" style="margin-bottom: 2rem;">Katalog Buku Perpustakaan</h1>

            <!-- Search Filters -->
            <div class="search-filters">
                <form method="GET" class="filter-row">
                    <div class="form-group">
                        <label for="search" class="form-label">Cari Buku</label>
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            class="form-control" 
                            placeholder="Judul, penulis, penerbit, atau ISBN..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="form-label">Kategori</label>
                        <select id="category" name="category" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="author" class="form-label">Penulis</label>
                        <input 
                            type="text" 
                            id="author" 
                            name="author" 
                            class="form-control" 
                            placeholder="Nama penulis..."
                            value="<?php echo htmlspecialchars($author); ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </form>
            </div>

            <!-- Results Header -->
            <div class="results-header">
                <div>
                    <h2 class="sect-title" style="margin: 0;">
                        <?php if (!empty($search) || !empty($category) || !empty($author)): ?>
                            Hasil Pencarian Buku
                        <?php else: ?>
                            Semua Buku
                        <?php endif; ?>
                    </h2>
                    <p class="sect-title" style="margin: 0.5rem 0 0 0;">
                        Ditemukan <?php echo number_format($total_books); ?> buku
                        <?php if ($total_pages > 1): ?>
                            (Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>)
                        <?php endif; ?>
                    </p>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <a href="tambah_buku.php" class="btn btn-success">+ Tambah Buku</a>
                    <?php if (!empty($search) || !empty($category) || !empty($author)): ?>
                        <a href="cari_buku.php" class="btn btn-primary">Lihat Semua Buku</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php elseif (empty($books)): ?>
                <div class="empty-state">
                    <h3>Tidak ada buku ditemukan</h3>
                    <p>Coba ubah kata kunci pencarian atau tambahkan buku baru.</p>
                    <a href="tambah_buku.php" class="btn btn-primary">Tambah Buku Pertama</a>
                </div>
            <?php else: ?>
                <!-- Books Grid -->
                <div class="books-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <?php if ($book['cover_image'] && $book['cover_image'] !== 'default.jpg' && file_exists('../uploads/covers/' . $book['cover_image'])): ?>
                                    <img src="../uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                         alt="Cover <?php echo htmlspecialchars($book['title']); ?>">
                                <?php else: ?>
                                    <div class="book-cover-text">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author">oleh <?php echo htmlspecialchars($book['author']); ?></p>
                                
                                <div style="margin: 1rem 0; font-size: 0.9rem; color: #666;">
                                    <div><strong>Penerbit:</strong> <?php echo htmlspecialchars($book['publisher']); ?></div>
                                    <div><strong>Tahun:</strong> <?php echo $book['year_published']; ?></div>
                                    <?php if ($book['category']): ?>
                                        <div><strong>Kategori:</strong> <?php echo htmlspecialchars($book['category']); ?></div>
                                    <?php endif; ?>
                                    <div><strong>Tersedia:</strong> <?php echo $book['available_copies']; ?> dari <?php echo $book['total_copies']; ?> buku</div>
                                </div>
                                
                                <div class="book-actions">
                                    <a href="detail_buku.php?id=<?php echo $book['id']; ?>" 
                                       class="btn btn-primary btn-sm">Detail</a>
                                    <a href="edit_buku.php?id=<?php echo $book['id']; ?>" 
                                       class="btn btn-warning btn-sm">Edit</a>
                                    <a href="hapus_buku.php?id=<?php echo $book['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin ingin menghapus buku \'<?php echo htmlspecialchars($book['title']); ?>\'?')">
                                        Hapus
                                    </a>
                                    <?php if ($book['available_copies'] > 0): ?>
                                        <a href="peminjaman.php?book_id=<?php echo $book['id']; ?>" 
                                           class="btn btn-success btn-sm">Pinjam</a>
                                    <?php else: ?>
                                        <span class="btn btn-danger btn-sm" style="cursor: not-allowed;">Habis</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
    <script src="../js/pencarian.js"></script>
    <script src="../js/animasi.js"></script>
</body>
</html>
