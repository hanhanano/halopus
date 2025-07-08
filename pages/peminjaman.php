<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $book_id = (int)($_POST['book_id'] ?? 0);
    $borrow_days = (int)($_POST['borrow_days'] ?? 7);
    
    $errors = [];
    
    if ($member_id <= 0) $errors[] = "Anggota harus dipilih";
    if ($book_id <= 0) $errors[] = "Buku harus dipilih";
    if ($borrow_days < 1 || $borrow_days > 30) $errors[] = "Lama peminjaman harus 1-30 hari";
    
    if (empty($errors)) {
        try {
                        $stmt = $pdo->prepare("
                SELECT m.*, 
                       COUNT(b.id) as current_borrows 
                FROM members m 
                LEFT JOIN borrowings b ON m.id = b.member_id AND b.status IN ('borrowed', 'overdue')
                WHERE m.id = ? AND m.status = 'active'
                GROUP BY m.id
            ");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            
            if (!$member) {
                $errors[] = "Anggota tidak ditemukan atau tidak aktif";
            } elseif ($member['current_borrows'] >= $member['max_borrow_limit']) {
                $errors[] = "Anggota sudah mencapai batas maksimal peminjaman ({$member['max_borrow_limit']} buku)";
            }
            
                        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND available_copies > 0");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch();
            
            if (!$book) {
                $errors[] = "Buku tidak tersedia atau stok habis";
            }
            
            if (empty($errors)) {
                                $borrow_date = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime("+{$borrow_days} days"));
                
                $pdo->beginTransaction();
                
                                $stmt = $pdo->prepare("
                    INSERT INTO borrowings (member_id, book_id, borrow_date, due_date, status) 
                    VALUES (?, ?, ?, ?, 'borrowed')
                ");
                $stmt->execute([$member_id, $book_id, $borrow_date, $due_date]);
                
                                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
                $stmt->execute([$book_id]);
                
                $pdo->commit();
                
                $success_message = "Peminjaman berhasil! Buku harus dikembalikan pada tanggal " . date('d/m/Y', strtotime($due_date));
                $_POST = [];             }
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}

try {
    $stmt = $pdo->prepare("SELECT id, member_code, name, email FROM members WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch(PDOException $e) {
    $members = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.id 
        WHERE b.available_copies > 0 
        ORDER BY b.title
    ");
    $stmt->execute();
    $books = $stmt->fetchAll();
} catch(PDOException $e) {
    $books = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Buku - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/form.css">
    <style>
        
        .borrow-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .member-search, .book-search {
            background: #1a1a2e;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .search-results {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #8b5cf6;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .result-item {
            padding: 1rem;
            border-bottom: 1px solid #6b46c1;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .result-item:hover {
            background-color: #16213e;
        }
        
        .result-item.selected {
            background-color: #2d1b69;
            border-left: 4px solid #ec4899;
        }
        
        .selected-info {
            background: #2d1b69;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            border-left: 4px solid #fbbf24;
        }
        
        @media (max-width: 768px) {
            .borrow-container {
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
            <li><a href="peminjaman.php" class="active"><img src="../images/bluepinjam.svg" alt="Pinjam" class="icon"> Peminjaman</a></li>
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
            <h1 style="margin-bottom: 2rem;">Peminjaman Buku</h1>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                    <br><a href="histori_peminjaman.php">Lihat Histori Peminjaman</a>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="borrowForm">
                <div class="borrow-container">
                    <!-- Member Selection -->
                    <div class="member-search">
                        <h3>1. Pilih Anggota</h3>
                        <input type="hidden" id="member_id" name="member_id" value="">
                        
                        <div class="form-group">
                            <label for="member_search" class="form-label">Cari Anggota</label>
                            <input 
                                type="text" 
                                id="member_search" 
                                class="form-control" 
                                placeholder="Ketik nama atau kode anggota..."
                                autocomplete="off"
                            >
                        </div>
                        
                        <div id="member_results" class="search-results" style="display: none;"></div>
                        <div id="selected_member" class="selected-info" style="display: none;"></div>
                    </div>

                    <!-- Book Selection -->
                    <div class="book-search">
                        <h3>2. Pilih Buku</h3>
                        <input type="hidden" id="book_id" name="book_id" value="">
                        
                        <div class="form-group">
                            <label for="book_search" class="form-label">Cari Buku</label>
                            <input 
                                type="text" 
                                id="book_search" 
                                class="form-control" 
                                placeholder="Ketik judul atau penulis buku..."
                                autocomplete="off"
                            >
                        </div>
                        
                        <div id="book_results" class="search-results" style="display: none;"></div>
                        <div id="selected_book" class="selected-info" style="display: none;"></div>
                    </div>
                </div>

                <!-- Borrow Details -->
                <div class="form-container">
                    <h3>3. Detail Peminjaman</h3>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="borrow_days" class="form-label">Lama Peminjaman (hari)</label>
                                <select id="borrow_days" name="borrow_days" class="form-select" required>
                                    <option value="7">7 hari</option>
                                    <option value="14" selected>14 hari</option>
                                    <option value="21">21 hari</option>
                                    <option value="30">30 hari</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">Tanggal Jatuh Tempo</label>
                                <input type="text" id="due_date_display" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-success" id="submit_btn" disabled>
                             Proses Peminjaman
                        </button>
                        <a href="../index.php" class="btn btn-primary">Kembali ke Dashboard</a>
                    </div>
                </div>
            </form>
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
        const members = <?php echo json_encode($members); ?>;
        const books = <?php echo json_encode($books); ?>;
        
        let selectedMember = null;
        let selectedBook = null;

                document.getElementById('member_search').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const results = document.getElementById('member_results');
            
            if (query.length < 2) {
                results.style.display = 'none';
                return;
            }
            
            const filtered = members.filter(member => 
                member.name.toLowerCase().includes(query) || 
                member.member_code.toLowerCase().includes(query) ||
                member.email.toLowerCase().includes(query)
            );
            
            if (filtered.length > 0) {
                results.innerHTML = filtered.map(member => `
                    <div class="result-item" onclick="selectMember(${member.id})">
                        <strong>${member.name}</strong> (${member.member_code})
                        <br><small>${member.email}</small>
                    </div>
                `).join('');
                results.style.display = 'block';
            } else {
                results.innerHTML = '<div class="result-item">Tidak ada anggota ditemukan</div>';
                results.style.display = 'block';
            }
        });

                document.getElementById('book_search').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const results = document.getElementById('book_results');
            
            if (query.length < 2) {
                results.style.display = 'none';
                return;
            }
            
            const filtered = books.filter(book => 
                book.title.toLowerCase().includes(query) || 
                book.author.toLowerCase().includes(query)
            );
            
            if (filtered.length > 0) {
                results.innerHTML = filtered.map(book => `
                    <div class="result-item" onclick="selectBook(${book.id})">
                        <strong>${book.title}</strong>
                        <br><small>oleh ${book.author} | Tersedia: ${book.available_copies} buku</small>
                        <br><small>Kategori: ${book.category_name || 'Tidak ada'} | Lokasi: ${book.location || 'Tidak ada'}</small>
                    </div>
                `).join('');
                results.style.display = 'block';
            } else {
                results.innerHTML = '<div class="result-item">Tidak ada buku ditemukan</div>';
                results.style.display = 'block';
            }
        });

        function selectMember(memberId) {
            selectedMember = members.find(m => m.id == memberId);
            document.getElementById('member_id').value = memberId;
            document.getElementById('member_search').value = selectedMember.name;
            document.getElementById('member_results').style.display = 'none';
            
            document.getElementById('selected_member').innerHTML = `
                <strong>Anggota Terpilih:</strong><br>
                ${selectedMember.name} (${selectedMember.member_code})<br>
                <small>${selectedMember.email}</small>
            `;
            document.getElementById('selected_member').style.display = 'block';
            
            checkFormComplete();
        }

        function selectBook(bookId) {
            selectedBook = books.find(b => b.id == bookId);
            document.getElementById('book_id').value = bookId;
            document.getElementById('book_search').value = selectedBook.title;
            document.getElementById('book_results').style.display = 'none';
            
            document.getElementById('selected_book').innerHTML = `
                <strong>Buku Terpilih:</strong><br>
                ${selectedBook.title}<br>
                <small>oleh ${selectedBook.author} | Tersedia: ${selectedBook.available_copies} buku</small>
            `;
            document.getElementById('selected_book').style.display = 'block';
            
            checkFormComplete();
        }

        function checkFormComplete() {
            const submitBtn = document.getElementById('submit_btn');
            if (selectedMember && selectedBook) {
                submitBtn.disabled = false;
                updateDueDate();
            } else {
                submitBtn.disabled = true;
            }
        }

        function updateDueDate() {
            const days = document.getElementById('borrow_days').value;
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + parseInt(days));
            
            document.getElementById('due_date_display').value = dueDate.toLocaleDateString('id-ID');
        }

                document.getElementById('borrow_days').addEventListener('change', updateDueDate);

                document.addEventListener('click', function(e) {
            if (!e.target.closest('.member-search')) {
                document.getElementById('member_results').style.display = 'none';
            }
            if (!e.target.closest('.book-search')) {
                document.getElementById('book_results').style.display = 'none';
            }
        });

                document.getElementById('borrowForm').addEventListener('submit', function(e) {
            if (!selectedMember || !selectedBook) {
                alert('Silakan pilih anggota dan buku terlebih dahulu');
                e.preventDefault();
                return false;
            }
        });
    
            
                function getUrlParameter(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

                function autoSelectMemberFromUrl() {
            const memberIdFromUrl = getUrlParameter('member_id');
            
            if (memberIdFromUrl) {
                const memberId = parseInt(memberIdFromUrl);
                const member = members.find(m => m.id === memberId);
                
                if (member) {
                                        selectMember(memberId);
                    
                                        const notification = document.createElement('div');
                    notification.className = 'alert alert-info';
                    notification.innerHTML = `Anggota "<strong>${member.name}</strong>" telah dipilih secara otomatis.`;
                    notification.style.marginBottom = '1rem';
                    notification.style.border = '1px solid #b3d9ff';
                    notification.style.backgroundColor = '#e7f3ff';
                    notification.style.color = '#0066cc';
                    notification.style.padding = '0.75rem 1rem';
                    notification.style.borderRadius = '6px';
                    
                                        const container = document.querySelector('.container');
                    const h1 = container.querySelector('h1');
                    h1.insertAdjacentElement('afterend', notification);
                    
                                        setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 5000);
                } else {
                                        const notification = document.createElement('div');
                    notification.className = 'alert alert-warning';
                    notification.innerHTML = 'Anggota dengan ID ${memberId} tidak ditemukan atau tidak aktif.';
                    notification.style.marginBottom = '1rem';
                    notification.style.border = '1px solid #ffcc00';
                    notification.style.backgroundColor = '#fff3cd';
                    notification.style.color = '#856404';
                    notification.style.padding = '0.75rem 1rem';
                    notification.style.borderRadius = '6px';
                    
                    const container = document.querySelector('.container');
                    const h1 = container.querySelector('h1');
                    h1.insertAdjacentElement('afterend', notification);
                    
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 5000);
                }
            }
        }

                function autoSelectBookFromUrl() {
            const bookIdFromUrl = getUrlParameter('book_id');
            
            if (bookIdFromUrl) {
                const bookId = parseInt(bookIdFromUrl);
                const book = books.find(b => b.id === bookId);
                
                if (book) {
                                        selectBook(bookId);
                    
                                        const notification = document.createElement('div');
                    notification.className = 'alert alert-info';
                    notification.innerHTML = `Buku "<strong>${book.title}</strong>" telah dipilih secara otomatis.`;
                    notification.style.marginBottom = '1rem';
                    notification.style.border = '1px solid #b3d9ff';
                    notification.style.backgroundColor = '#e7f3ff';
                    notification.style.color = '#0066cc';
                    notification.style.padding = '0.75rem 1rem';
                    notification.style.borderRadius = '6px';
                    
                                        const container = document.querySelector('.container');
                    const h1 = container.querySelector('h1');
                    h1.insertAdjacentElement('afterend', notification);
                    
                                        setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 5000);
                } else {
                                        const notification = document.createElement('div');
                    notification.className = 'alert alert-warning';
                    notification.innerHTML = `Buku dengan ID ${bookId} tidak ditemukan atau tidak tersedia.`;
                    notification.style.marginBottom = '1rem';
                    notification.style.border = '1px solid #ffcc00';
                    notification.style.backgroundColor = '#fff3cd';
                    notification.style.color = '#856404';
                    notification.style.padding = '0.75rem 1rem';
                    notification.style.borderRadius = '6px';
                    
                    const container = document.querySelector('.container');
                    const h1 = container.querySelector('h1');
                    h1.insertAdjacentElement('afterend', notification);
                    
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 5000);
                }
            }
        }

                function autoSelectFromUrl() {
            autoSelectMemberFromUrl();
            autoSelectBookFromUrl();
        }

                if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', autoSelectFromUrl);
        } else {
                        autoSelectFromUrl();
        }
    </script>
</body>
</html>
