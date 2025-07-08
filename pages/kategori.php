<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        $errors = [];
        
        if (empty($name)) $errors[] = "Nama kategori harus diisi";
        
                if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->fetch()) {
                    $errors[] = "Nama kategori sudah ada";
                }
            } catch(PDOException $e) {
                $errors[] = "Error checking category: " . $e->getMessage();
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $success_message = "Kategori berhasil ditambahkan!";
                $_POST = [];             } catch(PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
    
    if (isset($_POST['edit_category'])) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        $errors = [];
        
        if ($id <= 0) $errors[] = "ID kategori tidak valid";
        if (empty($name)) $errors[] = "Nama kategori harus diisi";
        
                if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $stmt->execute([$name, $id]);
                if ($stmt->fetch()) {
                    $errors[] = "Nama kategori sudah ada";
                }
            } catch(PDOException $e) {
                $errors[] = "Error checking category: " . $e->getMessage();
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                $success_message = "Kategori berhasil diperbarui!";
            } catch(PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = ?");
        $stmt->execute([$id]);
        $book_count = $stmt->fetch()['count'];
        
        if ($book_count > 0) {
            $error_message = "Kategori tidak dapat dihapus karena masih digunakan oleh {$book_count} buku.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Kategori berhasil dihapus!";
        }
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(b.id) as book_count 
        FROM categories c 
        LEFT JOIN books b ON c.id = b.category_id 
        GROUP BY c.id 
        ORDER BY c.name
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
    $error_message = "Error loading categories: " . $e->getMessage();
}

$edit_category = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_category = $stmt->fetch();
    } catch(PDOException $e) {
        $error_message = "Error loading category: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/form.css">
    <style>
        .category-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .category-form {
            background: #1a1a2e;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            height: fit-content;
            position: sticky;
            top: 120px;

        }

        @media (max-width: 768px) {
            .category-form {
                position: static;
            }
        }
        
        .categories-list {
            background: #1a1a2e;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .category-item {
            padding: 1.5rem;
            border-bottom: 1px solid #8b5cf6;
            transition: background-color 0.2s ease;
        }
        
        .category-item:hover {
            background-color: #16213e;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .category-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e5e7eb;
        }
        
        .book-count {
            background: linear-gradient(140deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .category-description {
            color: #9ca3af;
            margin-bottom: 1rem;
        }
        
        .category-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .cannot-delete-info {
            background: #2d1b69;
            color: #fbbf24;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            border-left: 4px solid #fbbf24;
        }
        
        @media (max-width: 768px) {
            .category-container {
                grid-template-columns: 1fr;
                gap: 1rem;
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
            <li><a href="histori_peminjaman.php"><img src="../images/blackhistori.svg" alt="Histori" class="icon"> Histori</a></li>
            <li><a href="kategori.php" class="active"><img src="../images/bluekategori.svg" alt="Kategori" class="icon"> Kategori</a></li>
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
            <h1 style="margin-bottom: 2rem;">Manajemen Kategori Buku</h1>
            
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

            <div class="category-container">
                <!-- Category Form -->
                <div class="category-form">
                    <h2 class="form-title">
                        <?php echo $edit_category ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?>
                    </h2>
                    
                    <form method="POST" id="categoryForm">
                        <?php if ($edit_category): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name" class="form-label">Nama Kategori *</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>"
                                required
                                placeholder="Contoh: Fiksi, Sains, Sejarah"
                            >
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                class="form-control textarea" 
                                rows="4"
                                placeholder="Deskripsi singkat tentang kategori ini..."
                            ><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <?php if ($edit_category): ?>
                                <button type="submit" name="edit_category" class="btn btn-success">
                                     Perbarui Kategori
                                </button>
                                <a href="kategori.php" class="btn btn-primary">Batal</a>
                            <?php else: ?>
                                <button type="submit" name="add_category" class="btn btn-success">
                                    Tambah Kategori
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Categories List -->
                <div class="categories-list">
                    <div style="padding: 1.5rem;">
                        <h3 style="margin: 0;">Daftar Kategori (<?php echo count($categories); ?>)</h3>
                    </div>
                    
                    <?php if (empty($categories)): ?>
                        <div style="padding: 2rem; text-align: center; color: #666;">
                            <p>Belum ada kategori. Tambahkan kategori pertama!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="category-item">
                                <div class="category-header">
                                    <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                    <div class="book-count"><?php echo $category['book_count']; ?> buku</div>
                                </div>
                                
                                <?php if ($category['description']): ?>
                                    <div class="category-description">
                                        <?php echo htmlspecialchars($category['description']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="category-actions">
                                    <a href="kategori.php?edit=<?php echo $category['id']; ?>" 
                                       class="btn btn-warning btn-sm"> Edit</a>
                                    
                                    <?php if ($category['book_count'] == 0): ?>
                                        <a href="kategori.php?delete=<?php echo $category['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus kategori \'<?php echo htmlspecialchars($category['name']); ?>\'?')">
                                            Hapus
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-danger btn-sm" disabled title="Tidak dapat dihapus karena masih digunakan">
                                             Hapus
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="cari_buku.php?category=<?php echo urlencode($category['name']); ?>" 
                                       class="btn btn-primary btn-sm"> Lihat Buku</a>
                                </div>
                                
                                <?php if ($category['book_count'] > 0): ?>
                                    <div class="cannot-delete-info">
                                        ⚠️ Kategori ini tidak dapat dihapus karena masih digunakan oleh <?php echo $category['book_count']; ?> buku. 
                                        Hapus atau pindahkan semua buku dari kategori ini terlebih dahulu.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
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

    <script src="../js/main.js"></script>
    <script src="../js/animasi.js"></script>

    <script>
                document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            
            if (!name) {
                alert('Nama kategori harus diisi');
                e.preventDefault();
                return false;
            }
            
            if (name.length < 2) {
                alert('Nama kategori minimal 2 karakter');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
