<?php
/**
 * Data Barang - Petugas Panel (View Only)
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
                SELECT b.*, k.nama_kategori, l.nama_lokasi, u.nama_lengkap as created_by_name
                FROM barang b 
                JOIN kategori k ON b.kategori_id = k.id 
                JOIN lokasi l ON b.lokasi_id = l.id
                LEFT JOIN users u ON b.created_by = u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                // Hitung jumlah dipinjam
                $jumlah_dipinjam = $data['jumlah_total'] - $data['jumlah_tersedia'];
                $data['jumlah_dipinjam'] = $jumlah_dipinjam;
                
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
            }
            exit();
        }
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        exit();
    }
}

$page_title = 'Data Barang';
require_once '../includes/header.php';

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];

// Ambil data barang dengan filter
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
    $lokasi_filter = isset($_GET['lokasi']) ? $_GET['lokasi'] : '';
    $kondisi_filter = isset($_GET['kondisi']) ? $_GET['kondisi'] : '';
    
    // Build query
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(b.kode_barang LIKE ? OR b.nama_barang LIKE ? OR b.deskripsi LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($kategori_filter)) {
        $where_conditions[] = "b.kategori_id = ?";
        $params[] = $kategori_filter;
    }
    
    if (!empty($lokasi_filter)) {
        $where_conditions[] = "b.lokasi_id = ?";
        $params[] = $lokasi_filter;
    }
    
    if (!empty($kondisi_filter)) {
        $where_conditions[] = "b.kondisi = ?";
        $params[] = $kondisi_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total records
    $count_query = "
        SELECT COUNT(*) as total 
        FROM barang b 
        JOIN kategori k ON b.kategori_id = k.id 
        JOIN lokasi l ON b.lokasi_id = l.id 
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    
    // Pagination
    $records_per_page = 12;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;
    $total_pages = ceil($total_records / $records_per_page);
    
    // Get data
    $query = "
        SELECT b.*, k.nama_kategori, l.nama_lokasi
        FROM barang b 
        JOIN kategori k ON b.kategori_id = k.id 
        JOIN lokasi l ON b.lokasi_id = l.id 
        $where_clause
        ORDER BY b.nama_barang 
        LIMIT $records_per_page OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get filter options
    $stmt = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori");
    $kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id, nama_lokasi FROM lokasi ORDER BY nama_lokasi");
    $lokasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $error = "Terjadi kesalahan: " . $e->getMessage();
    $barang_list = [];
    $total_records = 0;
    $total_pages = 0;
    $kategori_list = [];
    $lokasi_list = [];
}
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2 fw-bold">
                            <i class="fas fa-boxes me-2 text-primary"></i>Data Barang
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Lihat data barang inventaris sekolah (View Only)
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="../peminjaman/form.php" class="btn btn-primary shadow-sm">
                            <i class="fas fa-plus me-2"></i>Tambah Peminjaman
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Section -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>Filter Data Barang
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" id="filterForm">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <label for="search" class="form-label fw-semibold">
                        <i class="fas fa-search me-1"></i>Cari
                    </label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Kode, nama, deskripsi...">
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <label for="kategori" class="form-label fw-semibold">
                        <i class="fas fa-tag me-1"></i>Kategori
                    </label>
                    <select class="form-select" id="kategori" name="kategori" onchange="autoSubmitForm('filterForm')">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kategori): ?>
                            <option value="<?= $kategori['id'] ?>" <?= $kategori_filter == $kategori['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kategori['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <label for="lokasi" class="form-label fw-semibold">
                        <i class="fas fa-map-marker-alt me-1"></i>Lokasi
                    </label>
                    <select class="form-select" id="lokasi" name="lokasi" onchange="autoSubmitForm('filterForm')">
                        <option value="">Semua Lokasi</option>
                        <?php foreach ($lokasi_list as $lokasi): ?>
                            <option value="<?= $lokasi['id'] ?>" <?= $lokasi_filter == $lokasi['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lokasi['nama_lokasi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <label for="kondisi" class="form-label fw-semibold">
                        <i class="fas fa-check-circle me-1"></i>Kondisi
                    </label>
                    <select class="form-select" id="kondisi" name="kondisi" onchange="autoSubmitForm('filterForm')">
                        <option value="">Semua Kondisi</option>
                        <option value="baik" <?= $kondisi_filter == 'baik' ? 'selected' : '' ?>>Baik</option>
                        <option value="rusak_ringan" <?= $kondisi_filter == 'rusak_ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                        <option value="rusak_berat" <?= $kondisi_filter == 'rusak_berat' ? 'selected' : '' ?>>Rusak Berat</option>
                    </select>
                </div>
                
                <div class="col-lg-3 col-md-12 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-search me-2"></i>
                            <span class="d-none d-md-inline">Cari</span>
                        </button>
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-2"></i>
                            <span class="d-none d-md-inline">Reset</span>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Data Cards -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-list me-2 text-primary"></i>Data Barang
            </h5>
            <span class="badge bg-primary fs-6"><?= number_format($total_records) ?> Data</span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($barang_list)): ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-box-open fa-4x text-muted mb-3 d-block"></i>
                </div>
                <h5 class="text-muted fw-bold mb-2">Tidak ada data barang</h5>
                <p class="text-muted mb-3">Coba ubah filter pencarian Anda</p>
                <a href="?" class="btn btn-outline-primary">
                    <i class="fas fa-undo me-2"></i>Reset Filter
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($barang_list as $item): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body p-3">
                                <!-- Foto Barang -->
                                <div class="text-center mb-3">
                                    <?php if ($item['foto']): ?>
                                        <img src="../../uploads/<?= htmlspecialchars($item['foto']) ?>" 
                                             alt="<?= htmlspecialchars($item['nama_barang']) ?>" 
                                             class="img-fluid rounded shadow-sm" 
                                             style="max-height: 140px; width: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded shadow-sm d-flex align-items-center justify-content-center" 
                                             style="height: 140px; width: 100%;">
                                            <i class="fas fa-box fa-4x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Header dengan nama dan tombol detail -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0 fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.3;">
                                        <?= htmlspecialchars($item['nama_barang']) ?>
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="showDetail(<?= $item['id'] ?>)" 
                                            style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                
                                <!-- Kode Barang -->
                                <p class="text-muted small mb-2 fw-semibold">
                                    <i class="fas fa-barcode me-1"></i><?= htmlspecialchars($item['kode_barang']) ?>
                                </p>
                                
                                <!-- Badge Kategori dan Lokasi -->
                                <div class="mb-3">
                                    <span class="badge bg-primary me-1 mb-1" style="font-size: 0.7rem;">
                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($item['nama_kategori']) ?>
                                    </span>
                                    <span class="badge bg-info mb-1" style="font-size: 0.7rem;">
                                        <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($item['nama_lokasi']) ?>
                                    </span>
                                </div>
                                
                                <!-- Informasi Stok -->
                                <div class="row text-center mb-3 g-1">
                                    <div class="col-4">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block" style="font-size: 0.65rem;">Total</small>
                                            <strong class="text-primary d-block" style="font-size: 1rem;"><?= $item['jumlah_total'] ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block" style="font-size: 0.65rem;">Tersedia</small>
                                            <strong class="text-success d-block" style="font-size: 1rem;"><?= $item['jumlah_tersedia'] ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block" style="font-size: 0.65rem;">Dipinjam</small>
                                            <strong class="text-warning d-block" style="font-size: 1rem;"><?= $item['jumlah_total'] - $item['jumlah_tersedia'] ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Badge Kondisi -->
                                <div class="text-center mb-2">
                                    <?php
                                    $kondisi_class = '';
                                    $kondisi_text = '';
                                    $kondisi_icon = '';
                                    switch($item['kondisi']) {
                                        case 'baik':
                                            $kondisi_class = 'bg-success';
                                            $kondisi_text = 'Baik';
                                            $kondisi_icon = 'fas fa-check-circle';
                                            break;
                                        case 'rusak_ringan':
                                            $kondisi_class = 'bg-warning';
                                            $kondisi_text = 'Rusak Ringan';
                                            $kondisi_icon = 'fas fa-exclamation-triangle';
                                            break;
                                        case 'rusak_berat':
                                            $kondisi_class = 'bg-danger';
                                            $kondisi_text = 'Rusak Berat';
                                            $kondisi_icon = 'fas fa-times-circle';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $kondisi_class ?> px-3 py-2" style="font-size: 0.75rem;">
                                        <i class="<?= $kondisi_icon ?> me-1"></i><?= $kondisi_text ?>
                                    </span>
                                </div>
                                
                                <!-- Alert Stok Rendah -->
                                <?php if ($item['jumlah_tersedia'] < 5): ?>
                                    <div class="alert alert-warning alert-sm mt-2 mb-0 py-2" style="font-size: 0.7rem;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <strong>Stok rendah!</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center pagination-sm">
                        <!-- Previous button -->
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori_filter) ?>&lokasi=<?= urlencode($lokasi_filter) ?>&kondisi=<?= urlencode($kondisi_filter) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Page numbers -->
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori_filter) ?>&lokasi=<?= urlencode($lokasi_filter) ?>&kondisi=<?= urlencode($kondisi_filter) ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori_filter) ?>&lokasi=<?= urlencode($lokasi_filter) ?>&kondisi=<?= urlencode($kondisi_filter) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori_filter) ?>&lokasi=<?= urlencode($lokasi_filter) ?>&kondisi=<?= urlencode($kondisi_filter) ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Next button -->
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= urlencode($kategori_filter) ?>&lokasi=<?= urlencode($lokasi_filter) ?>&kondisi=<?= urlencode($kondisi_filter) ?>">
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

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Detail Barang
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

<style>
/* Custom styles for barang cards */
.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    border-radius: 0.5rem;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.card-body {
    display: flex;
    flex-direction: column;
}

.card-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.badge {
    font-weight: 500;
}

.alert-sm {
    border-radius: 0.375rem;
    font-size: 0.75rem;
}

/* Custom button styles */
.btn-outline-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Pagination improvements */
.pagination .page-link {
    border-radius: 0.375rem;
    margin: 0 1px;
}

.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Form improvements */
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-xl-3 {
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 0.75rem !important;
    }
    
    .card-title {
        font-size: 0.9rem !important;
    }
    
    .badge {
        font-size: 0.65rem !important;
    }
}

/* Animation for loading states */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.3s ease-in-out;
}
</style>

<script>
// Show detail modal
function showDetail(id) {
    fetch(`?action=get_detail&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.data;
                const modalBody = document.getElementById('detailModalBody');
                
                // Prepare foto HTML
                let fotoHtml;
                if (item.foto) {
                    fotoHtml = `<img src="../../uploads/${item.foto}" alt="${item.nama_barang}" class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">`;
                } else {
                    fotoHtml = `<div class='d-flex flex-column align-items-center justify-content-center' style='height:200px;'>
                        <i class='fas fa-box-open fa-5x text-secondary mb-2'></i>
                        <div class='text-muted'>Tidak ada foto</div>
                    </div>`;
                }
                
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                ${fotoHtml}
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Kode Barang</h6>
                                    <p class="fw-bold mb-3">${item.kode_barang}</p>
                                    
                                    <h6 class="text-muted mb-1">Nama Barang</h6>
                                    <p class="fw-bold mb-3">${item.nama_barang}</p>
                                    
                                    <h6 class="text-muted mb-1">Kategori</h6>
                                    <p class="fw-bold mb-3">${item.nama_kategori || '-'}</p>
                                    
                                    <h6 class="text-muted mb-1">Lokasi</h6>
                                    <p class="fw-bold mb-3">${item.nama_lokasi || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Jumlah Total</h6>
                                    <p class="fw-bold mb-3">${item.jumlah_total} unit</p>
                                    
                                    <h6 class="text-muted mb-1">Jumlah Tersedia</h6>
                                    <p class="fw-bold mb-3 text-success">${item.jumlah_tersedia} unit</p>
                                    
                                    <h6 class="text-muted mb-1">Jumlah Dipinjam</h6>
                                    <p class="fw-bold mb-3 text-warning">${item.jumlah_dipinjam} unit</p>
                                    
                                    <h6 class="text-muted mb-1">Kondisi</h6>
                                    <span class="badge bg-${item.kondisi == 'baik' ? 'success' : (item.kondisi == 'rusak_ringan' ? 'warning' : 'danger')} mb-3">
                                        ${item.kondisi == 'baik' ? 'Baik' : (item.kondisi == 'rusak_ringan' ? 'Rusak Ringan' : 'Rusak Berat')}
                                    </span>
                                </div>
                            </div>
                            
                            ${item.tahun_pengadaan || item.harga_perolehan ? `
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Tahun Pengadaan</h6>
                                    <p class="fw-bold mb-3">${item.tahun_pengadaan || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Harga Perolehan</h6>
                                    <p class="fw-bold mb-3">${item.harga_perolehan ? 'Rp ' + new Intl.NumberFormat('id-ID').format(item.harga_perolehan) : '-'}</p>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${item.deskripsi ? `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6 class="text-muted mb-1">Deskripsi</h6>
                                    <p class="mb-0">${item.deskripsi}</p>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${item.spesifikasi ? `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6 class="text-muted mb-1">Spesifikasi</h6>
                                    <p class="mb-0">${item.spesifikasi}</p>
                                </div>
                            </div>
                            ` : ''}
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6 class="text-muted mb-1">Informasi Sistem</h6>
                                    <table class="table table-sm">
                                        <tr><td>Dibuat Oleh</td><td>: ${item.created_by_name || '-'}</td></tr>
                                        <tr><td>Tanggal Dibuat</td><td>: ${new Date(item.created_at).toLocaleDateString('id-ID')}</td></tr>
                                        <tr><td>Terakhir Update</td><td>: ${new Date(item.updated_at).toLocaleDateString('id-ID')}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
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

// Auto submit form on select change
function autoSubmitForm(formId) {
    document.getElementById(formId).submit();
}
</script>

<?php require_once '../includes/footer.php'; ?> 