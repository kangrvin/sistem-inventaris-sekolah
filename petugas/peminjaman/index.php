<?php
/**
 * List Peminjaman - Petugas Panel
 */

// Handle AJAX requests FIRST - before any HTML output
if (isset($_GET['action'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Cek login dan role petugas
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($_GET['action'] == 'get_detail') {
            $id = $_GET['id'];
            $stmt = $pdo->prepare("
                SELECT p.*, b.nama_barang, b.kode_barang, b.jumlah_tersedia, k.nama_kategori, l.nama_lokasi, u.nama_lengkap as petugas_nama
                FROM peminjaman p 
                JOIN barang b ON p.barang_id = b.id 
                JOIN kategori k ON b.kategori_id = k.id
                JOIN lokasi l ON b.lokasi_id = l.id
                JOIN users u ON p.created_by = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                // Hitung denda jika terlambat
                $denda = 0;
                if ($data['status'] == 'dipinjam' && !empty($data['tanggal_kembali_rencana'])) {
                    $tanggal_rencana = strtotime($data['tanggal_kembali_rencana']);
                    $tanggal_sekarang = time();
                    if ($tanggal_sekarang > $tanggal_rencana) {
                        $selisih_hari = floor(($tanggal_sekarang - $tanggal_rencana) / (60 * 60 * 24));
                        
                        // Ambil setting denda dari database
                        $stmt_denda = $pdo->query("SELECT nilai FROM pengaturan WHERE nama_pengaturan = 'denda_terlambat'");
                        $denda_per_hari = $stmt_denda->fetch()['nilai'] ?? 1000;
                        $denda = $selisih_hari * $denda_per_hari;
                    }
                }
                $data['denda'] = $denda;
                
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            exit();
        }
        
        if ($_GET['action'] == 'delete') {
            $id = $_GET['id'];
            
            // Cek apakah peminjaman sudah dikembalikan
            $stmt = $pdo->prepare("SELECT status FROM peminjaman WHERE id = ?");
            $stmt->execute([$id]);
            $peminjaman = $stmt->fetch();
            
            if ($peminjaman['status'] == 'dipinjam') {
                echo json_encode(['success' => false, 'message' => 'Tidak dapat menghapus peminjaman yang masih aktif']);
                exit();
            }
            
            $stmt = $pdo->prepare("DELETE FROM peminjaman WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Peminjaman berhasil dihapus']);
            exit();
        }
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        exit();
    }
}

$page_title = 'Kelola Peminjaman';
require_once '../includes/header.php';

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];

// Handle form submission untuk pengembalian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($_POST['action'] == 'kembalikan') {
            $peminjaman_id = $_POST['peminjaman_id'];
            $tanggal_kembali = $_POST['tanggal_kembali'];
            
            // Cek apakah sudah dikembalikan sebelumnya
            $stmt = $pdo->prepare("SELECT status FROM peminjaman WHERE id = ?");
            $stmt->execute([$peminjaman_id]);
            $peminjaman = $stmt->fetch();
            
            if ($peminjaman['status'] == 'dikembalikan') {
                $error = "Peminjaman ini sudah dikembalikan sebelumnya!";
            } else {
                // Update status peminjaman - trigger akan otomatis update stok
                $stmt = $pdo->prepare("
                    UPDATE peminjaman 
                    SET status = 'dikembalikan', tanggal_kembali_aktual = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$tanggal_kembali, $peminjaman_id]);
                
                $success = "Barang berhasil dikembalikan! Stok telah diperbarui otomatis.";
            }
        }
        
    } catch(Exception $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Ambil data peminjaman dengan filter
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    
    // Build query
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(p.kode_peminjaman LIKE ? OR p.peminjam_nama LIKE ? OR b.nama_barang LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "p.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "p.tanggal_pinjam >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "p.tanggal_pinjam <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total records
    $count_query = "
        SELECT COUNT(*) as total 
        FROM peminjaman p 
        JOIN barang b ON p.barang_id = b.id 
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    
    // Pagination
    $records_per_page = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;
    $total_pages = ceil($total_records / $records_per_page);
    
    // Get data
    $query = "
        SELECT p.*, b.nama_barang, b.kode_barang, k.nama_kategori, l.nama_lokasi, u.nama_lengkap as petugas_nama
        FROM peminjaman p 
        JOIN barang b ON p.barang_id = b.id 
        JOIN kategori k ON b.kategori_id = k.id
        JOIN lokasi l ON b.lokasi_id = l.id
        JOIN users u ON p.created_by = u.id
        $where_clause
        ORDER BY p.created_at DESC 
        LIMIT $records_per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $peminjaman_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get denda setting
    $stmt = $pdo->query("SELECT nilai FROM pengaturan WHERE nama_pengaturan = 'denda_terlambat'");
    $denda_per_hari = $stmt->fetch()['nilai'] ?? 1000;
    
} catch(Exception $e) {
    $error = "Terjadi kesalahan: " . $e->getMessage();
    $peminjaman_list = [];
    $total_records = 0;
    $total_pages = 0;
    $denda_per_hari = 1000;
}
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-handshake me-2"></i>Kelola Peminjaman
                        </h2>
                        <p class="text-muted mb-0">Kelola data peminjaman dan pengembalian barang</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="form.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Peminjaman
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" id="filterForm">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($search) ?>" placeholder="Kode, nama, barang...">
                </div>
                
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" onchange="autoSubmitForm('filterForm')">
                        <option value="">Semua Status</option>
                        <option value="dipinjam" <?= $status_filter == 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                        <option value="dikembalikan" <?= $status_filter == 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                    </select>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= htmlspecialchars($date_from) ?>">
                </div>
                
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?= htmlspecialchars($date_to) ?>">
                </div>
                
                <div class="col-lg-3 col-md-12 mb-3 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-2"></i><span class="d-none d-md-inline">Cari</span>
                        </button>
                        <a href="?" class="btn btn-secondary">
                            <i class="fas fa-undo me-2"></i><span class="d-none d-md-inline">Reset</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>Data Peminjaman
            <span class="badge bg-primary ms-2"><?= number_format($total_records) ?> Data</span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($peminjaman_list)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Tidak ada data peminjaman</h5>
                <p class="text-muted">Mulai dengan menambahkan peminjaman baru</p>
                <a href="form.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tambah Peminjaman
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th class="d-none d-md-table-cell">Barang</th>
                            <th>Peminjam</th>
                            <th class="d-none d-lg-table-cell">Jumlah</th>
                            <th class="d-none d-lg-table-cell">Tanggal Pinjam</th>
                            <th class="d-none d-lg-table-cell">Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peminjaman_list as $item): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($item['kode_peminjaman']) ?></span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <div>
                                        <strong><?= htmlspecialchars($item['nama_barang']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($item['kode_barang']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($item['peminjam_nama']) ?></strong>
                                        <?php if ($item['peminjam_kelas']): ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($item['peminjam_kelas']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="badge bg-info"><?= $item['jumlah_pinjam'] ?></span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <small><?= date('d/m/Y', strtotime($item['tanggal_pinjam'])) ?></small>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <small><?= date('d/m/Y', strtotime($item['tanggal_kembali_rencana'])) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    $is_terlambat = false;
                                    
                                    if ($item['status'] == 'dipinjam') {
                                        if (!empty($item['tanggal_kembali_rencana']) && strtotime($item['tanggal_kembali_rencana']) < time()) {
                                            $status_class = 'bg-danger';
                                            $status_text = 'Terlambat';
                                            $is_terlambat = true;
                                        } else {
                                            $status_class = 'bg-warning';
                                            $status_text = 'Dipinjam';
                                        }
                                    } elseif ($item['status'] == 'dikembalikan') {
                                        $status_class = 'bg-success';
                                        $status_text = 'Dikembalikan';
                                    } else {
                                        $status_class = 'bg-secondary';
                                        $status_text = ucfirst($item['status']);
                                    }
                                    ?>
                                    <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                    
                                    <?php if ($is_terlambat): ?>
                                        <?php
                                        $selisih_hari = floor((time() - strtotime($item['tanggal_kembali_rencana'])) / (60 * 60 * 24));
                                        $denda = $selisih_hari * $denda_per_hari;
                                        ?>
                                        <br>
                                        <small class="text-danger">Denda: Rp <?= number_format($denda) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="showDetail(<?= $item['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($item['status'] == 'dipinjam'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="showKembalikan(<?= $item['id'] ?>)">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="form.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($item['status'] == 'dikembalikan'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deletePeminjaman(<?= $item['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Detail Peminjaman
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Pengembalian Modal -->
<div class="modal fade" id="kembalikanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-undo me-2"></i>Pengembalian Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="kembalikan">
                    <input type="hidden" name="peminjaman_id" id="peminjaman_id">
                    
                    <div class="mb-3">
                        <label for="tanggal_kembali" class="form-label">Tanggal Pengembalian <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tanggal_kembali" name="tanggal_kembali" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Info:</strong> Barang akan dikembalikan dan stok akan diperbarui otomatis.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Konfirmasi Pengembalian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show detail modal
function showDetail(id) {
    fetch(`?action=get_detail&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.data;
                const modalBody = document.getElementById('detailModalBody');
                
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informasi Peminjaman</h6>
                            <table class="table table-sm">
                                <tr><td>Kode Peminjaman</td><td>: <strong>${item.kode_peminjaman}</strong></td></tr>
                                <tr><td>Tanggal Pinjam</td><td>: ${new Date(item.tanggal_pinjam).toLocaleDateString('id-ID')}</td></tr>
                                <tr><td>Jatuh Tempo</td><td>: ${new Date(item.tanggal_kembali_rencana).toLocaleDateString('id-ID')}</td></tr>
                                <tr><td>Status</td><td>: <span class="badge bg-${item.status == 'dipinjam' ? 'warning' : 'success'}">${item.status == 'dipinjam' ? 'Dipinjam' : 'Dikembalikan'}</span></td></tr>
                                <tr><td>Dibuat Oleh</td><td>: <strong><i class="fas fa-user me-1"></i>${item.petugas_nama}</strong></td></tr>
                                ${item.tanggal_kembali_aktual ? `<tr><td>Tanggal Kembali</td><td>: ${new Date(item.tanggal_kembali_aktual).toLocaleDateString('id-ID')}</td></tr>` : ''}
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Informasi Barang</h6>
                            <table class="table table-sm">
                                <tr><td>Nama Barang</td><td>: <strong>${item.nama_barang}</strong></td></tr>
                                <tr><td>Kode Barang</td><td>: ${item.kode_barang}</td></tr>
                                <tr><td>Kategori</td><td>: ${item.nama_kategori}</td></tr>
                                <tr><td>Lokasi</td><td>: ${item.nama_lokasi}</td></tr>
                                <tr><td>Jumlah Dipinjam</td><td>: <span class="badge bg-info">${item.jumlah_pinjam}</span></td></tr>
                                <tr><td>Stok Tersedia</td><td>: <span class="badge bg-success">${item.jumlah_tersedia}</span></td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Informasi Peminjam</h6>
                            <table class="table table-sm">
                                <tr><td>Nama</td><td>: <strong>${item.peminjam_nama}</strong></td></tr>
                                ${item.peminjam_kelas ? `<tr><td>Kelas</td><td>: ${item.peminjam_kelas}</td></tr>` : ''}
                                ${item.peminjam_nis ? `<tr><td>NIS</td><td>: ${item.peminjam_nis}</td></tr>` : ''}
                                ${item.peminjam_kontak ? `<tr><td>Kontak</td><td>: ${item.peminjam_kontak}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                    ${item.denda > 0 ? `
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Denda Keterlambatan:</strong> Rp ${new Intl.NumberFormat('id-ID').format(item.denda)}
                    </div>
                    ` : ''}
                    ${item.keterangan ? `
                    <div class="mt-3">
                        <h6>Keterangan</h6>
                        <p class="text-muted">${item.keterangan}</p>
                    </div>
                    ` : ''}
                `;
                
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memuat data');
        });
}

// Show pengembalian modal
function showKembalikan(id) {
    document.getElementById('peminjaman_id').value = id;
    new bootstrap.Modal(document.getElementById('kembalikanModal')).show();
}

// Delete peminjaman
function deletePeminjaman(id) {
    if (confirm('Apakah Anda yakin ingin menghapus peminjaman ini?')) {
        fetch(`?action=delete&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Peminjaman berhasil dihapus');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus data');
            });
    }
}

// Auto submit form on select change
function autoSubmitForm(formId) {
    document.getElementById(formId).submit();
}
</script>

<?php require_once '../includes/footer.php'; ?> 