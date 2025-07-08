<?php
require_once '../config/koneksi.php';
require_once '../auth.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $borrowing_id = (int)($_POST['borrowing_id'] ?? 0);
    
    if ($borrowing_id > 0) {
        try {
                        $stmt = $pdo->prepare("
                SELECT b.*, m.name as member_name, bk.title as book_title, bk.id as book_id
                FROM borrowings b
                JOIN members m ON b.member_id = m.id
                JOIN books bk ON b.book_id = bk.id
                WHERE b.id = ? AND b.status IN ('borrowed', 'overdue')
            ");
            $stmt->execute([$borrowing_id]);
            $borrowing = $stmt->fetch();
            
            if ($borrowing) {
                $return_date = date('Y-m-d');
                $due_date = $borrowing['due_date'];
                $fine_amount = 0;
                
                                if ($return_date > $due_date) {
                    $days_late = (strtotime($return_date) - strtotime($due_date)) / (60 * 60 * 24);
                    $fine_amount = $days_late * 1000;                 }
                
                $pdo->beginTransaction();
                
                                $stmt = $pdo->prepare("
                    UPDATE borrowings 
                    SET return_date = ?, status = 'returned', fine_amount = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$return_date, $fine_amount, $borrowing_id]);
                
                                $stmt = $pdo->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
                $stmt->execute([$borrowing['book_id']]);
                
                $pdo->commit();
                
                $success_message = "Buku berhasil dikembalikan!";
                if ($fine_amount > 0) {
                    $success_message .= " Denda keterlambatan: Rp " . number_format($fine_amount, 0, ',', '.');
                }
                
            } else {
                $error_message = "Data peminjaman tidak ditemukan atau sudah dikembalikan.";
            }
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

$search = trim($_GET['search'] ?? '');
$where_conditions = ["b.status IN ('borrowed', 'overdue')"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(m.name LIKE ? OR m.member_code LIKE ? OR bk.title LIKE ?)";
    $search_term = "%{$search}%";
    $params = [$search_term, $search_term, $search_term];
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

try {
    $stmt = $pdo->prepare("
        SELECT b.*, m.name as member_name, m.member_code, bk.title as book_title, bk.author,
               CASE 
                   WHEN b.due_date < CURDATE() THEN 'overdue'
                   ELSE b.status
               END as current_status,
               DATEDIFF(CURDATE(), b.due_date) as days_overdue
        FROM borrowings b
        JOIN members m ON b.member_id = m.id
        JOIN books bk ON b.book_id = bk.id
        {$where_clause}
        ORDER BY b.due_date ASC
    ");
    $stmt->execute($params);
    $borrowings = $stmt->fetchAll();
    
        $pdo->prepare("UPDATE borrowings SET status = 'overdue' WHERE due_date < CURDATE() AND status = 'borrowed'")->execute();
    
} catch(PDOException $e) {
    $borrowings = [];
    $error_message = "Error loading data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Buku - Perpustakaan Digital</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/form.css">
    <style>
        
        body {
            height: 100vh;
        }
        
        .borrowings-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .fine-amount {
            color: #dc3545;
            font-weight: bold;
        }
        
        .return-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            background: linear-gradient(140deg, #1a0e2e 0%, #2d1b69 100%);
            width: 90%;
        }

        @media (max-width: 768px) {
            .borrowings-table {
                overflow-x: auto;
            }

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
            <li><a href="cari_buku.php"><img src="../images/blackbuku.svg" alt="Buku" class="icon"> Katalog Buku</a></li>
            <li><a href="peminjaman.php"><img src="../images/blackpinjam.svg" alt="Pinjam" class="icon"> Peminjaman</a></li>
            <li><a href="pengembalian.php" class="active"><img src="../images/bluekembali.svg" alt="Kembali" class="icon"> Pengembalian</a></li>
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
            <h1 style="margin-bottom: 2rem;">Pengembalian Buku</h1>
            
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

            <!-- Search Filter -->
            <div class="return-filters">
                <form method="GET" class="filter-row">
                    <div class="form-group">
                        <label for="search" class="form-label">Cari Peminjaman</label>
                        <input 
                            type="text" 
                            id="search" 
                            name="search" 
                            class="form-control" 
                            placeholder="Nama anggota, kode anggota, atau judul buku..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"> Cari</button>
                    </div>
                </form>
            </div>

            <!-- Borrowings Table -->
            <?php if (empty($borrowings)): ?>
                <div class="empty-state">
                    <h3>Tidak ada buku yang sedang dipinjam</h3>
                    <p>Semua buku sudah dikembalikan atau belum ada peminjaman.</p>
                    <a href="peminjaman.php" class="btn btn-primary">Proses Peminjaman Baru</a>
                </div>
            <?php else: ?>
                <div class="borrowings-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Tanggal Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Denda</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowings as $borrowing): ?>
                                <?php
                                $fine = 0;
                                if ($borrowing['current_status'] === 'overdue') {
                                    $fine = max(0, $borrowing['days_overdue']) * 1000;
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($borrowing['member_name']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($borrowing['member_code']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($borrowing['book_title']); ?></strong>
                                        <br>
                                        <small>oleh <?php echo htmlspecialchars($borrowing['author']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($borrowing['borrow_date'])); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($borrowing['due_date'])); ?>
                                        <?php if ($borrowing['current_status'] === 'overdue'): ?>
                                            <br><small class="fine-amount">Terlambat <?php echo $borrowing['days_overdue']; ?> hari</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $borrowing['current_status']; ?>">
                                            <?php echo $borrowing['current_status'] === 'overdue' ? 'Terlambat' : 'Dipinjam'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($fine > 0): ?>
                                            <span class="fine-amount">Rp <?php echo number_format($fine, 0, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span style="color: #28a745;">Rp 0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button 
                                            class="btn btn-success btn-sm" 
                                            onclick="confirmReturn(<?php echo $borrowing['id']; ?>, '<?php echo htmlspecialchars($borrowing['book_title']); ?>', '<?php echo htmlspecialchars($borrowing['member_name']); ?>', <?php echo $fine; ?>)"
                                        >
                                             Kembalikan
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Return Confirmation Modal -->
    <div id="returnModal" class="return-modal">
        <div class="modal-content">
            <h3>Konfirmasi Pengembalian</h3>
            <div id="returnDetails"></div>
            <form method="POST" style="margin-top: 2rem;">
                <input type="hidden" id="borrowing_id" name="borrowing_id">
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-primary" onclick="closeModal()">Batal</button>
                    <button type="submit" name="return_book" class="btn btn-success">Konfirmasi Pengembalian</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Sistem Perpustakaan Digital.</p>
        </div>
    </footer>

    <script src="../js/main.js"></script>
    <script src="../js/animasi.js"></script>

    <script>
        function confirmReturn(borrowingId, bookTitle, memberName, fine) {
            document.getElementById('borrowing_id').value = borrowingId;
            
            let fineText = '';
            if (fine > 0) {
                fineText = `<div class="alert alert-danger" style="margin-top: 1rem;">
                    <strong>Denda Keterlambatan:</strong> Rp ${fine.toLocaleString('id-ID')}
                </div>`;
            }
            
            document.getElementById('returnDetails').innerHTML = `
                <p><strong>Buku:</strong> ${bookTitle}</p>
                <p><strong>Anggota:</strong> ${memberName}</p>
                <p><strong>Tanggal Pengembalian:</strong> ${new Date().toLocaleDateString('id-ID')}</p>
                ${fineText}
                <p style="margin-top: 1rem;">Apakah Anda yakin ingin memproses pengembalian buku ini?</p>
            `;
            
            document.getElementById('returnModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('returnModal').style.display = 'none';
        }
        
                document.getElementById('returnModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
