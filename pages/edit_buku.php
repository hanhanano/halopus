<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$success_message = '';
$error_message = '';
$book = null;

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
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $book) {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $year_published = (int)($_POST['year_published'] ?? 0);
    $isbn = trim($_POST['isbn'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $total_copies = (int)($_POST['total_copies'] ?? 1);
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
        $errors = [];
    
    if (empty($title)) $errors[] = "Judul buku harus diisi";
    if (empty($author)) $errors[] = "Nama penulis harus diisi";
    if (empty($publisher)) $errors[] = "Nama penerbit harus diisi";
    if ($year_published < 1900 || $year_published > date('Y')) $errors[] = "Tahun terbit tidak valid";
    if ($category_id <= 0) $errors[] = "Kategori harus dipilih";
    if ($total_copies < 1) $errors[] = "Jumlah buku minimal 1";
    
        $cover_image = $book['cover_image'];     if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/covers/';
        
                if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['cover_image']['name']);
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower($file_info['extension']);
        
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP";
        } elseif ($_FILES['cover_image']['size'] > 5 * 1024 * 1024) {             $errors[] = "Ukuran file terlalu besar. Maksimal 5MB";
        } else {
                        $new_filename = uniqid('cover_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                                if ($book['cover_image'] && $book['cover_image'] !== 'default.jpg' && file_exists($upload_dir . $book['cover_image'])) {
                    unlink($upload_dir . $book['cover_image']);
                }
                $cover_image = $new_filename;
            } else {
                $errors[] = "Gagal mengupload gambar cover";
            }
        }
    }
    
    if (empty($errors)) {
        try {
                        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category_name = $stmt->fetch(PDO::FETCH_COLUMN);
            
            $stmt = $pdo->prepare("
                UPDATE books 
                SET title = ?, author = ?, publisher = ?, year_published = ?, isbn = ?,
                    category_id = ?, category = ?, total_copies = ?, available_copies = ?, 
                    location = ?, description = ?, cover_image = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $title, $author, $publisher, $year_published, $isbn,
                $category_id, $category_name, $total_copies, $total_copies, 
                $location, $description, $cover_image, $book_id
            ]);
            
            $success_message = "Buku berhasil diperbarui!";
            
                        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
        } catch(PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Buku - <?php echo htmlspecialchars($book['title'] ?? 'Perpustakaan Digital'); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/upload-image.css">
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
        <?php if ($book): ?>
            <div class="form-container">
                <h1 class="form-title">Edit Buku</h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                        <br><a href="detail_buku.php?id=<?php echo $book['id']; ?>">Lihat Detail</a> | 
                        <a href="cari_buku.php">Kembali ke Katalog</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="editBookForm">
                    <!-- Cover Image Upload -->
                    <div class="image-upload-section" id="imageUploadSection">
                        <div class="upload-icon">📷</div>
                        <h3>Cover Buku</h3>
                        
                        <!-- Current Cover Display -->
                        <div id="currentCoverContainer">
                            <?php if ($book['cover_image'] && $book['cover_image'] !== 'default.jpg' && file_exists('../uploads/covers/' . $book['cover_image'])): ?>
                                <img src="../uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     alt="Current Cover" class="current-cover" id="currentCover">
                                <p class="cover-status">Cover saat ini</p>
                            <?php else: ?>
                                <div class="default-cover" id="currentCover">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </div>
                                <p class="cover-status">Belum ada cover</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Upload Preview (hidden by default) -->
                        <div id="previewContainer" style="display: none;">
                            <img id="uploadPreview" class="upload-preview" alt="Preview">
                            <p class="cover-status">Cover baru (belum disimpan)</p>
                        </div>
                        
                        <p class="upload-description">Drag & drop gambar cover baru atau klik tombol di bawah</p>
                        
                        <div class="file-input-wrapper">
                            <input type="file" id="cover_image" name="cover_image" class="file-input" accept="image/*">
                            <button type="button" class="file-input-button">
                                📁 Pilih Gambar Baru
                            </button>
                        </div>
                        
                        <div class="upload-info">
                            Format yang didukung: JPG, PNG, GIF, WebP<br>
                            Ukuran maksimal: 5MB<br>
                            <small>Kosongkan jika tidak ingin mengubah cover</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="title" class="form-label">Judul Buku *</label>
                                <input 
                                    type="text" 
                                    id="title" 
                                    name="title" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($book['title']); ?>"
                                    required
                                >
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="author" class="form-label">Penulis *</label>
                                <input 
                                    type="text" 
                                    id="author" 
                                    name="author" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($book['author']); ?>"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="publisher" class="form-label">Penerbit *</label>
                                <input 
                                    type="text" 
                                    id="publisher" 
                                    name="publisher" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($book['publisher']); ?>"
                                    required
                                >
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="year_published" class="form-label">Tahun Terbit *</label>
                                <input 
                                    type="number" 
                                    id="year_published" 
                                    name="year_published" 
                                    class="form-control" 
                                    value="<?php echo $book['year_published']; ?>"
                                    min="1900" 
                                    max="<?php echo date('Y'); ?>"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input 
                                    type="text" 
                                    id="isbn" 
                                    name="isbn" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($book['isbn']); ?>"
                                    placeholder="978-xxx-xxx-xxx-x"
                                >
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="category_id" class="form-label">Kategori *</label>
                                <select id="category_id" name="category_id" class="form-select" required>
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $book['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="total_copies" class="form-label">Jumlah Buku *</label>
                                <input 
                                    type="number" 
                                    id="total_copies" 
                                    name="total_copies" 
                                    class="form-control" 
                                    value="<?php echo $book['total_copies']; ?>"
                                    min="1"
                                    required
                                >
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="location" class="form-label">Lokasi Rak</label>
                                <input 
                                    type="text" 
                                    id="location" 
                                    name="location" 
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($book['location']); ?>"
                                    placeholder="Contoh: A1-B2"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            class="form-control textarea" 
                            rows="4"
                            placeholder="Masukkan deskripsi atau sinopsis buku..."
                        ><?php echo htmlspecialchars($book['description']); ?></textarea>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-success">
                            Perbarui Buku
                        </button>
                        <a href="detail_buku.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">Lihat Detail</a>
                        <a href="cari_buku.php" class="btn btn-primary">Kembali ke Katalog</a>
                    </div>
                </form>
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
        document.getElementById('cover_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP');
                this.value = '';
                return;
            }

                        if (file.size > 5 * 1024 * 1024) {
                alert('Ukuran file terlalu besar. Maksimal 5MB');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('uploadPreview');
                const previewContainer = document.getElementById('previewContainer');
                const currentContainer = document.getElementById('currentCoverContainer');
                
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
                currentContainer.style.opacity = '0.5';
            };
            reader.readAsDataURL(file);
        } else {
                        document.getElementById('previewContainer').style.display = 'none';
            document.getElementById('currentCoverContainer').style.opacity = '1';
        }
    });

        const uploadSection = document.getElementById('imageUploadSection');
    const fileInput = document.getElementById('cover_image');

    uploadSection.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadSection.classList.add('dragover');
    });

    uploadSection.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadSection.classList.remove('dragover');
    });

    uploadSection.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadSection.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });

        document.querySelector('.file-input-button').addEventListener('click', function() {
        fileInput.click();
    });
</script>
</body>
</html>
