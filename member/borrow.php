<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: ../login.php?error=login_required&user=member');
    exit();
}

$member_id = $_SESSION['member_id'];
$success_message = '';
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
        SELECT COUNT(*) as current_borrowings
        FROM borrowings
        WHERE member_id = ? AND status IN ('borrowed', 'overdue')
    ");
    $stmt->execute([$member_id]);
    $borrowing_count = $stmt->fetch();
    
    $can_borrow = $borrowing_count['current_borrowings'] < $member['max_borrow_limit'];
    $remaining_quota = $member['max_borrow_limit'] - $borrowing_count['current_borrowings'];
    
        $stmt = $pdo->prepare("
        SELECT * FROM books 
        WHERE available_copies > 0
        ORDER BY title ASC
    ");
    $stmt->execute();
    $available_books = $stmt->fetchAll();
    
        $stmt = $pdo->prepare("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
        $book_id = $_POST['book_id'] ?? 0;
        $borrow_days = (int)($_POST['borrow_days'] ?? 7);         
                if ($borrow_days < 1 || $borrow_days > 30) {
            $borrow_days = 7;         }
        
                $errors = [];
        
        if (empty($book_id)) {
            $errors[] = "Buku harus dipilih";
        }
        
        if (!$can_borrow) {
            $errors[] = "Anda telah mencapai batas peminjaman maksimal ({$member['max_borrow_limit']} buku)";
        }
        
                if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND available_copies > 0");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
            if (!$book) {
                $errors[] = "Buku tidak tersedia atau stok habis";
            }
        }
        
                if (empty($errors)) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as already_borrowed
                FROM borrowings
                WHERE member_id = ? AND book_id = ? AND status IN ('borrowed', 'overdue')
            ");
            $stmt->execute([$member_id, $book_id]);
            $check = $stmt->fetch();
            
            if ($check['already_borrowed'] > 0) {
                $errors[] = "Anda sudah meminjam buku ini dan belum mengembalikannya";
            }
        }
        
        if (empty($errors)) {
            try {
                                $pdo->beginTransaction();
                
                                $borrow_date = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime("+{$borrow_days} days"));
                
                                $stmt = $pdo->prepare("
                    INSERT INTO borrowings (
                        member_id, book_id, borrow_date, due_date, 
                        status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'borrowed', NOW(), NOW())
                ");
                $stmt->execute([$member_id, $book_id, $borrow_date, $due_date]);
                
                                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET available_copies = available_copies - 1, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$book_id]);
                
                                $pdo->commit();
                
                $success_message = "Buku berhasil dipinjam! Silakan kembalikan sebelum tanggal " . date('d/m/Y', strtotime($due_date));
                
                                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as current_borrowings
                    FROM borrowings
                    WHERE member_id = ? AND status IN ('borrowed', 'overdue')
                ");
                $stmt->execute([$member_id]);
                $borrowing_count = $stmt->fetch();
                
                $can_borrow = $borrowing_count['current_borrowings'] < $member['max_borrow_limit'];
                $remaining_quota = $member['max_borrow_limit'] - $borrowing_count['current_borrowings'];
                
                                $stmt = $pdo->prepare("
                    SELECT * FROM books 
                    WHERE available_copies > 0
                    ORDER BY title ASC
                ");
                $stmt->execute();
                $available_books = $stmt->fetchAll();
                
            } catch(PDOException $e) {
                                $pdo->rollBack();
                $error_message = "Error: " . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $member = null;
    $can_borrow = false;
    $remaining_quota = 0;
    $available_books = [];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/form.css">
    <link rel="stylesheet" href="../css/style2member.css">
    <style>
        .book-cover {
            height: 200px;
            background-color: #16213e;
            position: relative;
            overflow: hidden;
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
            color: #9ca3af;
            background: #16213e;
        }
        
        .book-info {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .book-title {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: #e5e7eb;
            line-height: 1.3;
        }
        
        .book-author {
            margin: 0 0 1rem 0;
            font-size: 0.9rem;
            color: #9ca3af;
        }
        
        .borrow-button {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(140deg, #8b5cf6 0%, #ec4899 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .borrow-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        }
        
        .borrow-button:disabled {
            background: #6b7280;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: #1a1a2e;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            width: 80%;
            max-width: 500px;
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #9ca3af;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: #ec4899;
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #8b5cf6;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #e5e7eb;
        }
        
        .modal-body {
            margin-bottom: 1.5rem;
        }
        
        .modal-footer {
            padding-top: 1rem;
            border-top: 1px solid #8b5cf6;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .book-card {
            background: #1a1a2e;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            border: 1px solid #8b5cf6;
            display: flex;
            flex-direction: column;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(139, 92, 246, 0.2);
        }

        .book-actions {
        margin-top: auto;
        }
        
        .book-preview {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #8b5cf6;
        }
        
        .book-preview-cover {
            width: 100px;
            height: 150px;
            background: #16213e;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .book-preview-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .book-preview-info {
            flex: 1;
        }
        
        .book-preview-title {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: #e5e7eb;
        }
        
        .book-preview-author {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: #9ca3af;
        }
        
        .due-date-preview {
            font-weight: 600;
            color: #fbbf24;
            margin-top: 1rem;
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
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Sidebar -->
            <div class="dashboard-sidebar">
                <div class="profile-section">
                    <h3>Informasi Peminjaman</h3>
                    <p><strong>Batas Peminjaman:</strong> <?php echo $member['max_borrow_limit']; ?> buku</p>
                    <p><strong>Durasi Peminjaman:</strong> 7-30 hari</p>
                    <p><strong>Denda Keterlambatan:</strong> Rp 1.000/hari</p>
                </div>
                
                <div class="quota-info">
                    <p>Sisa kuota peminjaman:</p>
                    <p class="quota-remaining"><?php echo $remaining_quota; ?> dari <?php echo $member['max_borrow_limit']; ?> buku</p>
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
                        <a href="borrow.php" class="active">
                            <img src="../images/bluepinjam.svg" alt="Borrow" class="menu-icon">
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
                <div class="dashboard-card">
                    <h2>Buku Tersedia</h2>
                    
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
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="category" class="form-label">Kategori</label>
                                <select id="category" name="category" class="form-select">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>">
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button type="button" id="filterButton" class="btn btn-primary">Cari</button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (empty($available_books)): ?>
                        <div class="empty-state">
                            <h3>Tidak ada buku tersedia</h3>
                            <p>Saat ini tidak ada buku yang tersedia untuk dipinjam.</p>
                        </div>
                    <?php else: ?>
                        <!-- Books Grid -->
                        <div class="books-grid" id="booksGrid">
                            <?php foreach ($available_books as $book): ?>
                                <div class="book-card" 
                                     data-title="<?php echo htmlspecialchars(strtolower($book['title'])); ?>" 
                                     data-author="<?php echo htmlspecialchars(strtolower($book['author'])); ?>" 
                                     data-category="<?php echo htmlspecialchars(strtolower($book['category'])); ?>"
                                     data-id="<?php echo $book['id']; ?>"
                                     data-cover="<?php echo $book['cover_image'] && $book['cover_image'] !== 'default.jpg' && file_exists('../uploads/covers/' . $book['cover_image']) ? htmlspecialchars($book['cover_image']) : ''; ?>">
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
                                            <button 
                                                type="button" 
                                                class="borrow-button" 
                                                <?php echo !$can_borrow ? 'disabled' : ''; ?>
                                                onclick="openBorrowModal(<?php echo $book['id']; ?>, '<?php echo addslashes(htmlspecialchars($book['title'])); ?>', '<?php echo addslashes(htmlspecialchars($book['author'])); ?>')"
                                            >
                                                Pinjam Buku
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Borrow Modal -->
    <div id="borrowModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeBorrowModal()">&times;</span>
            
            <div class="modal-header">
                <h3>Konfirmasi Peminjaman</h3>
            </div>
            
            <div class="modal-body">
                <div class="book-preview">
                    <div class="book-preview-cover" id="modalBookCover">
                        <!-- Book cover will be inserted here by JavaScript -->
                    </div>
                    
                    <div class="book-preview-info">
                        <h4 class="book-preview-title" id="modalBookTitle"></h4>
                        <p class="book-preview-author" id="modalBookAuthor"></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="borrowDays" class="form-label">Pilih Durasi Peminjaman:</label>
                    <select id="borrowDays" class="form-select">
                        <option value="7">7 hari</option>
                        <option value="14" selected>14 hari</option>
                        <option value="21">21 hari</option>
                        <option value="30">30 hari</option>
                    </select>
                </div>
                
                <div class="due-date-preview" id="dueDatePreview">
                    Tanggal Kembali: <?php echo date('d/m/Y', strtotime('+14 days')); ?>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeBorrowModal()">Batal</button>
                <form id="borrowForm" method="POST" action="">
                    <input type="hidden" name="book_id" id="modalBookId">
                    <input type="hidden" name="borrow_days" id="modalBorrowDays" value="14">
                    <input type="hidden" name="borrow_book" value="1">
                    <button type="submit" class="btn btn-success">Konfirmasi Peminjaman</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistem Perpustakaan Digital.</p>
        </div>
    </footer>

    <script>
                document.getElementById('filterButton').addEventListener('click', function() {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const categoryFilter = document.getElementById('category').value.toLowerCase();
            const books = document.querySelectorAll('#booksGrid .book-card');
            
            let visibleCount = 0;
            
            books.forEach(book => {
                const title = book.dataset.title;
                const author = book.dataset.author;
                const category = book.dataset.category;
                
                const matchesSearch = !searchTerm || 
                    title.includes(searchTerm) || 
                    author.includes(searchTerm);
                
                const matchesCategory = !categoryFilter || 
                    category === categoryFilter;
                
                if (matchesSearch && matchesCategory) {
                    book.style.display = 'block';
                    visibleCount++;
                } else {
                    book.style.display = 'none';
                }
            });
            
                        const emptyState = document.querySelector('.empty-state');
            if (visibleCount === 0 && !emptyState) {
                const noResults = document.createElement('div');
                noResults.className = 'empty-state';
                noResults.innerHTML = `
                    <h3>Tidak ada buku ditemukan</h3>
                    <p>Coba ubah filter pencarian Anda.</p>
                `;
                document.getElementById('booksGrid').after(noResults);
            } else if (visibleCount > 0 && document.querySelector('.empty-state')) {
                document.querySelector('.empty-state').remove();
            }
        });
        
                const modal = document.getElementById('borrowModal');
        
        function openBorrowModal(bookId, bookTitle, bookAuthor) {
                        const bookCard = document.querySelector(`.book-card[data-id="${bookId}"]`);
            const coverImage = bookCard.dataset.cover;
            
                        document.getElementById('modalBookTitle').textContent = bookTitle;
            document.getElementById('modalBookAuthor').textContent = 'oleh ' + bookAuthor;
            document.getElementById('modalBookId').value = bookId;
            
                        const coverElement = document.getElementById('modalBookCover');
            if (coverImage) {
                coverElement.innerHTML = `<img src="../uploads/covers/${coverImage}" alt="Cover ${bookTitle}">`;
            } else {
                coverElement.innerHTML = `<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f0f0f0;">${bookTitle}</div>`;
            }
            
                        document.getElementById('borrowDays').value = '14';
            document.getElementById('modalBorrowDays').value = '14';
            updateDueDate(14);
            
                        modal.style.display = 'block';
        }
        
        function closeBorrowModal() {
            modal.style.display = 'none';
        }
        
                window.onclick = function(event) {
            if (event.target === modal) {
                closeBorrowModal();
            }
        };
        
                document.getElementById('borrowDays').addEventListener('change', function() {
            const days = parseInt(this.value);
            document.getElementById('modalBorrowDays').value = days;
            updateDueDate(days);
        });
        
        function updateDueDate(days) {
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + days);
            
                        const day = String(dueDate.getDate()).padStart(2, '0');
            const month = String(dueDate.getMonth() + 1).padStart(2, '0');
            const year = dueDate.getFullYear();
            
            document.getElementById('dueDatePreview').innerHTML = 
                'Tanggal Kembali: ' + day + '/' + month + '/' + year;
        }
    </script>
</body>
</html>
