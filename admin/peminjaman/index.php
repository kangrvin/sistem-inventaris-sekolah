<?php
/**
 * Data Peminjaman - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Include functions
require_once '../../config/functions.php';

// AJAX handler untuk get detail
if (isset($_GET['action']) && $_GET['action'] == 'get_detail') {
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                b.kode_barang,
                b.nama_barang,
                b.foto,
                k.nama_kategori,
                l.nama_lokasi,
                u.nama_lengkap as petugas
            FROM peminjaman p
            JOIN barang b ON p.barang_id = b.id
            JOIN kategori k ON b.kategori_id = k.id
            JOIN lokasi l ON b.lokasi_id = l.id
            JOIN users u ON p.created_by = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $peminjaman = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($peminjaman) {
            echo json_encode([
                'success' => true,
                'data' => $peminjaman
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Peminjaman tidak ditemukan'
            ]);
        }
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan saat memuat data'
        ]);
    }
    exit();
}

$page_title = 'Data Peminjaman';
require_once '../includes/header.php';

// Handle actions
$action = $_GET['action'] ?? '';



// Pagination
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get denda setting from database
    $stmt = $pdo->prepare("SELECT nilai FROM pengaturan WHERE nama_pengaturan = 'denda_terlambat'");
    $stmt->execute();
    $denda_per_hari = $stmt->fetchColumn() ?: 1000; // Default 1000 if not found
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Search dan filter
    $search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? validateInput($_GET['status']) : '';
    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(p.peminjam_nama LIKE ? OR p.peminjam_nis LIKE ? OR b.nama_barang LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($status_filter)) {
        if ($status_filter == 'terlambat') {
            $where_conditions[] = "p.status = 'dipinjam' AND p.tanggal_kembali_rencana < CURDATE()";
        } else {
            $where_conditions[] = "p.status = ?";
            $params[] = $status_filter;
        }
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Query untuk total data
    $count_query = "
        SELECT COUNT(*) as total 
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    // Query untuk data peminjaman
    $query = "
        SELECT 
            p.*,
            b.kode_barang,
            b.nama_barang,
            b.foto,
            k.nama_kategori,
            l.nama_lokasi,
            u.nama_lengkap as petugas
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        JOIN kategori k ON b.kategori_id = k.id
        JOIN lokasi l ON b.lokasi_id = l.id
        JOIN users u ON p.created_by = u.id
        $where_clause
        ORDER BY p.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $peminjaman_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    $peminjaman_list = [];
    $total_records = 0;
    $total_pages = 0;
}

// Get status counts for dashboard
try {
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN status = 'dipinjam' AND tanggal_kembali_rencana < CURDATE() THEN 'terlambat'
                ELSE status 
            END as status_actual,
            COUNT(*) as count
        FROM peminjaman 
        GROUP BY 
            CASE 
                WHEN status = 'dipinjam' AND tanggal_kembali_rencana < CURDATE() THEN 'terlambat'
                ELSE status 
            END
    ");
    $stmt->execute();
    $status_counts = [];
    while ($row = $stmt->fetch()) {
        $status_counts[$row['status_actual']] = $row['count'];
    }
} catch(Exception $e) {
    $status_counts = [];
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
                            <i class="fas fa-handshake me-2"></i>Data Peminjaman
                        </h2>
                        <p class="text-muted mb-0">Kelola peminjaman dan pengembalian barang inventaris sekolah</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="?status=terlambat" class="btn btn-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>Terlambat (<?= $status_counts['terlambat'] ?? 0 ?>)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $status_counts['dipinjam'] ?? 0 ?></h4>
                        <small>Sedang Dipinjam</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-handshake fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $status_counts['dikembalikan'] ?? 0 ?></h4>
                        <small>Sudah Dikembalikan</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $total_records ?></h4>
                        <small>Total Peminjaman</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-list fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $status_counts['terlambat'] ?? 0 ?></h4>
                        <small>Terlambat</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Cari nama peminjam, NIS, atau nama barang..." value="<?= htmlspecialchars($search) ?>"
                                   autocomplete="off">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status" id="statusFilter">
                            <option value="">Semua Status</option>
                            <option value="dipinjam" <?= $status_filter == 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                            <option value="dikembalikan" <?= $status_filter == 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                            <option value="terlambat" <?= $status_filter == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-refresh me-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Table Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Peminjaman
                        </h5>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            <?php if (!empty($search) || !empty($status_filter)): ?>
                                Hasil pencarian: <?= number_format($total_records) ?> peminjaman
                                <?php if (!empty($search)): ?>
                                    <span class="badge bg-primary ms-2">"<?= htmlspecialchars($search) ?>"</span>
                                <?php endif; ?>
                                <?php if (!empty($status_filter)): ?>
                                    <span class="badge bg-info ms-2">Status: <?= ucfirst($status_filter) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                Total: <?= number_format($total_records) ?> peminjaman
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($peminjaman_list)): ?>
                    <div class="text-center py-5">
                        <?php if (!empty($search) || !empty($status_filter)): ?>
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada hasil pencarian</h5>
                            <p class="text-muted">Tidak ditemukan peminjaman yang sesuai dengan kriteria pencarian.</p>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Hapus Filter
                            </a>
                        <?php else: ?>
                            <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada data peminjaman</h5>
                            <p class="text-muted">Belum ada peminjaman yang ditambahkan</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="peminjamanTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">Peminjam</th>
                                    <th width="25%">Barang</th>
                                    <th width="15%">Tanggal</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Petugas</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($peminjaman_list as $index => $peminjaman): ?>
                                    <tr>
                                        <td><?= $offset + $index + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($peminjaman['peminjam_nama']) ?></h6>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($peminjaman['peminjam_kelas']) ?> | 
                                                        NIS: <?= htmlspecialchars($peminjaman['peminjam_nis']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($peminjaman['foto']): ?>
                                                    <img src="../../uploads/<?= htmlspecialchars($peminjaman['foto']) ?>" 
                                                         class="avatar-sm rounded me-3" alt="Foto Barang">
                                                <?php else: ?>
                                                    <div class="avatar-sm bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-box text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($peminjaman['nama_barang']) ?></h6>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars($peminjaman['nama_kategori']) ?> | 
                                                        <?= $peminjaman['jumlah_pinjam'] ?> unit
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted">Tanggal Pinjam:</small><br>
                                                <strong><?= date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) ?></strong>
                                            </div>
                                            <div class="mt-1">
                                                <small class="text-muted">Jatuh Tempo:</small><br>
                                                <strong><?= date('d/m/Y', strtotime($peminjaman['tanggal_kembali_rencana'])) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            // Hitung status terlambat otomatis
                                            $status_actual = $peminjaman['status'];
                                            $is_terlambat = false;
                                            
                                            if ($peminjaman['status'] == 'dipinjam' && 
                                                strtotime($peminjaman['tanggal_kembali_rencana']) < time()) {
                                                $status_actual = 'terlambat';
                                                $is_terlambat = true;
                                            }
                                            
                                            switch($status_actual) {
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    $status_text = 'Pending';
                                                    break;
                                                case 'dipinjam':
                                                    $status_class = 'bg-primary';
                                                    $status_text = 'Dipinjam';
                                                    break;
                                                case 'dikembalikan':
                                                    $status_class = 'bg-success';
                                                    $status_text = 'Dikembalikan';
                                                    break;
                                                case 'terlambat':
                                                    $status_class = 'bg-danger';
                                                    $status_text = 'Terlambat';
                                                    break;
                                                case 'ditolak':
                                                    $status_class = 'bg-secondary';
                                                    $status_text = 'Ditolak';
                                                    break;
                                            }
                                            
                                            // Hitung denda jika terlambat
                                            $denda_info = '';
                                            if ($is_terlambat) {
                                                $hari_terlambat = ceil((time() - strtotime($peminjaman['tanggal_kembali_rencana'])) / (24 * 3600));
                                                $total_denda = $hari_terlambat * $denda_per_hari;
                                                $denda_info = "<div class='mt-1'><span class='badge bg-warning text-dark denda-badge'><i class='fas fa-exclamation-triangle me-1'></i>Denda: Rp " . number_format($total_denda) . "</span></div>";
                                            }
                                            ?>
                                            <div class="d-flex flex-column align-items-start status-container">
                                                <span class="badge <?= $status_class ?> status-badge">
                                                    <?= $status_text ?>
                                                </span>
                                                <?= $denda_info ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($peminjaman['petugas']) ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="showDetail(<?= $peminjaman['id'] ?>)" 
                                                        title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>

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
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-handshake me-2"></i>Detail Peminjaman Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>



<script>
// Detail modal functionality
function showDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    const modalBody = document.getElementById('detailModalBody');
    
    // Show loading
    modalBody.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat data...</p>
        </div>
    `;
    
    modal.show();
    
    // Fetch data
    fetch(`?action=get_detail&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const peminjaman = data.data;
                const statusClass = getStatusClass(peminjaman.status);
                const statusText = getStatusText(peminjaman.status);
                
                // Hitung denda jika terlambat
                let dendaInfo = '';
                let isTerlambat = false;
                let jumlahHariTerlambat = 0;
                
                if (peminjaman.status === 'dipinjam' && new Date(peminjaman.tanggal_kembali_rencana) < new Date()) {
                    isTerlambat = true;
                    const tanggalKembali = new Date(peminjaman.tanggal_kembali_rencana);
                    const hariIni = new Date();
                    const selisihWaktu = hariIni.getTime() - tanggalKembali.getTime();
                    jumlahHariTerlambat = Math.ceil(selisihWaktu / (1000 * 3600 * 24));
                    
                    // Ambil denda dari pengaturan (akan diambil dari server)
                    const dendaPerHari = <?= $denda_per_hari ?>; // Diambil dari database pengaturan
                    const totalDenda = jumlahHariTerlambat * dendaPerHari;
                    
                    dendaInfo = `
                        <div class="alert alert-warning border-warning mt-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                <h6 class="mb-0 text-warning">Peminjaman Terlambat!</h6>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Jumlah Hari Terlambat:</span>
                                        <span class="fw-bold text-danger">${jumlahHariTerlambat} hari</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total Denda:</span>
                                        <span class="fw-bold text-danger">Rp ${totalDenda.toLocaleString('id-ID')}</span>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Denda: Rp ${dendaPerHari.toLocaleString('id-ID')} per hari keterlambatan
                                </small>
                                <span class="badge bg-danger">
                                    <i class="fas fa-clock me-1"></i>
                                    Terlambat ${jumlahHariTerlambat} hari
                                </span>
                            </div>
                        </div>
                    `;
                }
                
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-center mb-4">
                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                    <i class="fas fa-handshake text-white"></i>
                                </div>
                                <h4>${peminjaman.kode_peminjaman}</h4>
                                <span class="badge ${isTerlambat ? 'bg-danger' : statusClass} fs-6">
                                    ${isTerlambat ? 'Terlambat' : statusText}
                                </span>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-user me-2"></i>Informasi Peminjam</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Nama:</strong></td>
                                            <td>${peminjaman.peminjam_nama}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kelas:</strong></td>
                                            <td>${peminjaman.peminjam_kelas || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>NIS:</strong></td>
                                            <td>${peminjaman.peminjam_nis || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kontak:</strong></td>
                                            <td>${peminjaman.peminjam_kontak || '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-box me-2"></i>Informasi Barang</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Barang:</strong></td>
                                            <td>${peminjaman.nama_barang}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kode:</strong></td>
                                            <td>${peminjaman.kode_barang}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kategori:</strong></td>
                                            <td>${peminjaman.nama_kategori}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Lokasi:</strong></td>
                                            <td>${peminjaman.nama_lokasi}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Jumlah:</strong></td>
                                            <td>${peminjaman.jumlah_pinjam} unit</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-calendar me-2"></i>Informasi Tanggal</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Tanggal Pinjam:</strong></td>
                                            <td>${new Date(peminjaman.tanggal_pinjam).toLocaleDateString('id-ID')}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Jatuh Tempo:</strong></td>
                                            <td>${new Date(peminjaman.tanggal_kembali_rencana).toLocaleDateString('id-ID')}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Dikembalikan:</strong></td>
                                            <td>${peminjaman.tanggal_kembali_aktual ? new Date(peminjaman.tanggal_kembali_aktual).toLocaleDateString('id-ID') : '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-info-circle me-2"></i>Informasi Sistem</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Petugas:</strong></td>
                                            <td>${peminjaman.petugas}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Dibuat:</strong></td>
                                            <td>${new Date(peminjaman.created_at).toLocaleDateString('id-ID')}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Diupdate:</strong></td>
                                            <td>${new Date(peminjaman.updated_at).toLocaleDateString('id-ID')}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            ${peminjaman.keterangan ? `
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <h6><i class="fas fa-sticky-note me-2"></i>Keterangan</h6>
                                    <div class="alert alert-info">
                                        ${peminjaman.keterangan}
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${dendaInfo}
                        </div>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5 class="text-warning">Data tidak ditemukan</h5>
                        <p class="text-muted">Peminjaman yang Anda cari tidak ditemukan atau telah dihapus.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">Terjadi Kesalahan</h5>
                    <p class="text-muted">Gagal memuat data peminjaman. Silakan coba lagi.</p>
                </div>
            `;
        });
}



// Helper functions
function getStatusClass(status) {
    switch(status) {
        case 'dipinjam': return 'bg-primary';
        case 'dikembalikan': return 'bg-success';
        case 'terlambat': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'dipinjam': return 'Dipinjam';
        case 'dikembalikan': return 'Dikembalikan';
        case 'terlambat': return 'Terlambat';
        default: return 'Unknown';
    }
}

// Auto-hide alerts
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    });
}, 3000);

// Search functionality improvements
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const statusFilter = document.getElementById('statusFilter');
    
    // Auto-submit search on Enter key
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                this.closest('form').submit();
            }
        });
        
        // Clear search on Escape key
        searchInput.addEventListener('keydown', function(e) {
            if (e.which === 27) { // Escape key
                e.preventDefault();
                this.value = '';
                window.location.href = 'index.php';
            }
        });
    }
    
    // Auto-submit filter dropdowns
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            submitFilter();
        });
    }
    
    // Function to submit filter form
    function submitFilter() {
        const form = document.querySelector('form[method="GET"]');
        if (form) {
            form.submit();
        }
    }
    
    // Highlight search terms in results
    <?php if (!empty($search)): ?>
    const searchTerm = '<?= htmlspecialchars($search) ?>';
    const cells = document.querySelectorAll('td');
    cells.forEach(function(cell) {
        const text = cell.textContent;
        if (text.toLowerCase().includes(searchTerm.toLowerCase())) {
            cell.innerHTML = text.replace(new RegExp(searchTerm, 'gi'), 
                '<mark class="bg-warning">$&</mark>');
        }
    });
    <?php endif; ?>
});
</script>

<style>
/* Ensure consistent styling for peminjaman items */
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    font-size: 1rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.075);
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    font-weight: 500;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Ensure search results look the same as normal results */
.table tbody tr {
    vertical-align: middle;
}

.table td {
    padding: 0.75rem;
}

/* Status badge styling */
.status-container {
    min-width: 120px;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    font-weight: 500;
    white-space: nowrap;
}

.denda-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    font-weight: 500;
    margin-top: 0.25rem;
    white-space: nowrap;
}

/* Ensure consistent table cell alignment */
.table td {
    vertical-align: middle;
}
</style>

<?php require_once '../includes/footer.php'; ?> 