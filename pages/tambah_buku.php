<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$success_message = '';
$error_message = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
        $cover_image = 'default.jpg';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
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
                INSERT INTO books (title, author, publisher, year_published, isbn, category_id, category, total_copies, available_copies, location, description, cover_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$title, $author, $publisher, $year_published, $isbn, $category_id, $category_name, $total_copies, $total_copies, $location, $description, $cover_image]);
            
            $book_id = $pdo->lastInsertId();
            $success_message = "Buku berhasil ditambahkan ke perpustakaan!";
            
                        $_POST = [];
            
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
    <title>Tambah Buku - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/upload-image.css">
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
            <div class="form-container">
                <h1 class="form-title">Tambah Buku Baru</h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                        <br><a href="cari_buku.php">Lihat Katalog Buku</a> | 
                        <a href="tambah_buku.php">Tambah Buku Lagi</a>
                        <?php if (isset($book_id)): ?>
                            | <a href="detail_buku.php?id=<?php echo $book_id; ?>">Lihat Detail Buku</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="bookForm">
                    <!-- Cover Image Upload -->
                    <div class="image-upload-section" id="imageUploadSection">
                        <div class="upload-icon">📷</div>
                        <h3>Upload Cover Buku</h3>
                        <p>Drag & drop gambar cover atau klik tombol di bawah</p>
                        
                        <div id="imagePreview" style="display: none;">
                            <img id="previewImg" class="upload-preview" alt="Preview">
                        </div>
                        
                        <div class="file-input-wrapper">
                            <input type="file" id="cover_image" name="cover_image" class="file-input" accept="image/*">
                            <button type="button" class="file-input-button">Pilih Gambar</button>
                        </div>
                        
                        <div class="upload-info">
                            Format yang didukung: JPG, PNG, GIF, WebP<br>
                            Ukuran maksimal: 5MB
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
                                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
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
                                    value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>"
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
                                    value="<?php echo htmlspecialchars($_POST['publisher'] ?? ''); ?>"
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
                                    value="<?php echo $_POST['year_published'] ?? date('Y'); ?>"
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
                                    value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>"
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
                                                <?php echo ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
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
                                    value="<?php echo $_POST['total_copies'] ?? '1'; ?>"
                                    min="1"
                                    required
                                >
                                <small style="color: #666;">Jumlah total buku yang akan ditambahkan</small>
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
                                    value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                                    placeholder="Contoh: Rak A1"
                                >
                                <small style="color: #666;">Lokasi penyimpanan buku di perpustakaan</small>
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
                            placeholder="Masukkan deskripsi singkat tentang buku..."
                        ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-success">
                             Tambah Buku
                        </button>
                        <a href="cari_buku.php" class="btn btn-primary">Kembali ke Katalog</a>
                        <a href="../index.php" class="btn btn-primary">Dashboard</a>
                    </div>
                </form>
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
                const imageUploadSection = document.getElementById('imageUploadSection');
        const fileInput = document.getElementById('cover_image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const fileInputButton = document.querySelector('.file-input-button');

                fileInputButton.addEventListener('click', () => {
            fileInput.click();
        });

                fileInput.addEventListener('change', handleFileSelect);

                imageUploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploadSection.classList.add('dragover');
        });

        imageUploadSection.addEventListener('dragleave', () => {
            imageUploadSection.classList.remove('dragover');
        });

        imageUploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUploadSection.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect();
            }
        });

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP');
                    fileInput.value = '';
                    return;
                }

                                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB');
                    fileInput.value = '';
                    return;
                }

                                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                    fileInputButton.textContent = 'Ganti Gambar';
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
