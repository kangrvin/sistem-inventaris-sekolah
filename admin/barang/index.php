<?php
/**
 * Data Barang - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Handle AJAX request untuk detail FIRST (before any output)
$action = $_GET['action'] ?? '';
if ($action == 'get_detail' && isset($_GET['id'])) {
    session_start();
    $barang_id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                k.nama_kategori,
                l.nama_lokasi
            FROM barang b
            LEFT JOIN kategori k ON b.kategori_id = k.id
            LEFT JOIN lokasi l ON b.lokasi_id = l.id
            WHERE b.id = ?
        ");
        $stmt->execute([$barang_id]);
        $barang = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($barang) {
            header('Content-Type: application/json');
            echo json_encode($barang);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Barang tidak ditemukan']);
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
    exit();
}

$page_title = 'Data Barang';
require_once '../includes/header.php';
?>

<style>
    /* Mobile responsive for barang table */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.8rem;
        }
        
        .table td, .table th {
            padding: 0.5rem 0.25rem;
        }
        
        .btn-group .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.7rem;
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        .fw-semibold {
            font-size: 0.9rem;
        }
        
        .text-muted {
            font-size: 0.75rem;
        }
    }
    
    @media (max-width: 576px) {
        .table-responsive {
            font-size: 0.75rem;
        }
        
        .table td, .table th {
            padding: 0.4rem 0.2rem;
        }
        
        .btn-group .btn {
            padding: 0.15rem 0.3rem;
            font-size: 0.65rem;
        }
        
        .badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }
        
        .fw-semibold {
            font-size: 0.85rem;
        }
        
        .text-muted {
            font-size: 0.7rem;
        }
    }
</style>

<?php

// Handle actions
if ($action == 'add' || $action == 'edit') {
    // Include form langsung
    include 'form.php';
    exit();
}

if ($action == 'delete' && isset($_GET['id'])) {
    $barang_id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Ambil info barang untuk log
        $stmt = $pdo->prepare("SELECT nama_barang, foto FROM barang WHERE id = ?");
        $stmt->execute([$barang_id]);
        $barang = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($barang) {
            // Hapus foto jika ada
            if ($barang['foto'] && file_exists('../../uploads/' . $barang['foto'])) {
                unlink('../../uploads/' . $barang['foto']);
            }
            
            // Hapus barang
            $stmt = $pdo->prepare("DELETE FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);
            
            $success = 'Barang berhasil dihapus!';
            echo "<script>alert('Barang berhasil dihapus!'); window.location.href='index.php';</script>";
            exit();
        } else {
            $error = 'Barang tidak ditemukan!';
            echo "<script>alert('Barang tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch(Exception $e) {
        $error = 'Gagal menghapus barang!';
        echo "<script>alert('Gagal menghapus barang!'); window.location.href='index.php';</script>";
    exit();
    }
}

// Ambil data barang dengan pagination dan search
$search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : '';
$lokasi_filter = isset($_GET['lokasi']) ? (int)$_GET['lokasi'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query untuk data barang
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(b.nama_barang LIKE ? OR b.kode_barang LIKE ? OR b.deskripsi LIKE ?)";
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
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Query untuk total data
    $count_query = "
        SELECT COUNT(*) as total 
        FROM barang b 
        LEFT JOIN kategori k ON b.kategori_id = k.id 
        LEFT JOIN lokasi l ON b.lokasi_id = l.id 
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Query untuk data barang
    $query = "
        SELECT 
            b.*,
            k.nama_kategori,
            l.nama_lokasi
        FROM barang b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN lokasi l ON b.lokasi_id = l.id
        $where_clause
        ORDER BY b.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $barang_list = $stmt->fetchAll();
    
    // Ambil data untuk filter
    $kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
    $lokasi_list = $pdo->query("SELECT * FROM lokasi ORDER BY nama_lokasi")->fetchAll();
    
} catch(Exception $e) {
    $barang_list = [];
    $total_pages = 0;
    $kategori_list = [];
    $lokasi_list = [];
}

// Flash message
$flash = getFlashMessage();
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-boxes me-2"></i>Data Barang
                        </h2>
                        <p class="text-muted mb-0">Kelola semua data barang inventaris sekolah</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Barang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Flash Message -->
<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Data</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-lg-4 col-md-6 col-12">
                        <label for="search" class="form-label">Cari Barang</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?= htmlspecialchars($search) ?>" placeholder="Nama, kode, atau deskripsi...">
                    </div>
                    <div class="col-lg-3 col-md-6 col-12">
                        <label for="kategori" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori" name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat['id'] ?>" <?= $kategori_filter == $kat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 col-12">
                        <label for="lokasi" class="form-label">Lokasi</label>
                        <select class="form-select" id="lokasi" name="lokasi">
                            <option value="">Semua Lokasi</option>
                            <?php foreach ($lokasi_list as $lok): ?>
                                <option value="<?= $lok['id'] ?>" <?= $lokasi_filter == $lok['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lok['nama_lokasi']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 col-12">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i><span class="d-none d-md-inline">Cari</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6 col-12 mb-2 mb-md-0">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Barang
                            <span class="badge bg-primary ms-2"><?= number_format($total_records) ?> item</span>
                        </h5>
                    </div>
                    <div class="col-md-6 col-12 text-md-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportData('excel')">
                                <i class="fas fa-file-excel me-1"></i><span class="d-none d-md-inline">Excel</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportData('pdf')">
                                <i class="fas fa-file-pdf me-1"></i><span class="d-none d-md-inline">PDF</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($barang_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada data barang</h5>
                        <p class="text-muted">Mulai dengan menambahkan barang baru</p>
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Barang Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th class="d-none d-md-table-cell">Kategori</th>
                                    <th class="d-none d-lg-table-cell">Lokasi</th>
                                    <th>Stok</th>
                                    <th class="d-none d-md-table-cell">Kondisi</th>
                                    <th class="d-none d-lg-table-cell">Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($barang_list as $barang): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($barang['kode_barang']) ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($barang['foto']): ?>
                                                    <div class="me-2">
                                                        <img src="../../uploads/<?= htmlspecialchars($barang['foto']) ?>" 
                                                             alt="<?= htmlspecialchars($barang['nama_barang']) ?>" 
                                                             class="rounded" style="width: 40px; height: 40px; object-fit: cover;">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="me-2 bg-light rounded d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="fas fa-box text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($barang['nama_barang']) ?></div>
                                                    <small class="text-muted d-none d-md-inline"><?= htmlspecialchars($barang['deskripsi']) ?></small>
                                                    <div class="d-md-none">
                                                        <small class="text-muted"><?= htmlspecialchars($barang['nama_kategori']) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <span class="badge bg-info"><?= htmlspecialchars($barang['nama_kategori']) ?></span>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            <span class="badge bg-warning"><?= htmlspecialchars($barang['nama_lokasi']) ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="fw-semibold me-2"><?= $barang['jumlah_tersedia'] ?></span>
                                                <small class="text-muted d-none d-md-inline">/ <?= $barang['jumlah_total'] ?></small>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <?php
                                            $kondisi_class = '';
                                            $kondisi_icon = '';
                                            switch($barang['kondisi']) {
                                                case 'baik':
                                                    $kondisi_class = 'bg-success';
                                                    $kondisi_icon = 'fas fa-check-circle';
                                                    break;
                                                case 'rusak_ringan':
                                                    $kondisi_class = 'bg-warning';
                                                    $kondisi_icon = 'fas fa-exclamation-triangle';
                                                    break;
                                                case 'rusak_berat':
                                                    $kondisi_class = 'bg-danger';
                                                    $kondisi_icon = 'fas fa-times-circle';
                                                    break;
                                                default:
                                                    $kondisi_class = 'bg-secondary';
                                                    $kondisi_icon = 'fas fa-question-circle';
                                            }
                                            ?>
                                            <span class="badge <?= $kondisi_class ?>">
                                                <i class="<?= $kondisi_icon ?> me-1"></i>
                                                <span class="d-none d-lg-inline"><?= ucfirst(str_replace('_', ' ', $barang['kondisi'])) ?></span>
                                            </span>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            <?php if ($barang['jumlah_tersedia'] > 0): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Tersedia
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i>Habis
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" onclick="showDetail(<?= $barang['id'] ?>)" 
                                                        class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="?action=edit&id=<?= $barang['id'] ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="showDeleteModal(<?= $barang['id'] ?>, '<?= htmlspecialchars($barang['nama_barang']) ?>', '<?= htmlspecialchars($barang['kode_barang']) ?>')" 
                                                        title="Hapus">
                                                    <i class="fas fa-trash"></i>
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
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>&lokasi=<?= $lokasi_filter ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>&lokasi=<?= $lokasi_filter ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&kategori=<?= $kategori_filter ?>&lokasi=<?= $lokasi_filter ?>">
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

<!-- Modal Detail Barang -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-box me-2"></i>Detail Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail barang...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
                <button type="button" class="btn btn-primary" id="editBarangBtn" style="display: none;">
                    <i class="fas fa-edit me-2"></i>Edit Barang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-trash-alt fa-4x text-danger mb-3"></i>
                    <h5>Hapus Barang</h5>
                    <p class="text-muted">Apakah Anda yakin ingin menghapus barang ini?</p>
                </div>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Peringatan:</strong> Tindakan ini tidak dapat dibatalkan dan foto barang juga akan dihapus secara permanen.
                </div>
                <div id="deleteItemInfo" class="text-center">
                    <!-- Info barang yang akan dihapus akan ditampilkan di sini -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus Barang
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Export data function
function exportData(type) {
    const search = document.getElementById('search').value;
    const kategori = document.getElementById('kategori').value;
    const lokasi = document.getElementById('lokasi').value;
    
    let url = `export_barang.php?type=${type}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (kategori) url += `&kategori=${kategori}`;
    if (lokasi) url += `&lokasi=${lokasi}`;
    
    window.open(url, '_blank');
}

// Auto-submit form on filter change
document.getElementById('kategori').addEventListener('change', function() {
    this.form.submit();
});

document.getElementById('lokasi').addEventListener('change', function() {
    this.form.submit();
});

// Detail popup function
function showDetail(id) {
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
    
    // Show loading
    document.getElementById('detailModalBody').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat detail barang...</p>
        </div>
    `;
    
    // Fetch data
    fetch(`?action=get_detail&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                document.getElementById('detailModalBody').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Error</h5>
                        <p class="text-muted">${data.error}</p>
                    </div>
                `;
            } else {
                // Format data
                const barang = data;
                let fotoHtml;
                if (barang.foto) {
                    fotoHtml = `<img src="../../uploads/${barang.foto}" alt="${barang.nama_barang}" class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">`;
                } else {
                    fotoHtml = `<div class='d-flex flex-column align-items-center justify-content-center' style='height:200px;'>
                        <i class='fas fa-box-open fa-5x text-secondary mb-2'></i>
                        <div class='text-muted'>Tidak ada foto</div>
                    </div>`;
                }
                const kondisiClass = {
                    'baik': 'success',
                    'rusak_ringan': 'warning',
                    'rusak_berat': 'danger'
                };
                const kondisiText = {
                    'baik': 'Baik',
                    'rusak_ringan': 'Rusak Ringan',
                    'rusak_berat': 'Rusak Berat'
                };
                
                document.getElementById('detailModalBody').innerHTML = `
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
                                    <p class="fw-bold mb-3">${barang.kode_barang}</p>
                                    
                                    <h6 class="text-muted mb-1">Nama Barang</h6>
                                    <p class="fw-bold mb-3">${barang.nama_barang}</p>
                                    
                                    <h6 class="text-muted mb-1">Kategori</h6>
                                    <p class="fw-bold mb-3">${barang.nama_kategori || '-'}</p>
                                    
                                    <h6 class="text-muted mb-1">Lokasi</h6>
                                    <p class="fw-bold mb-3">${barang.nama_lokasi || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Jumlah Total</h6>
                                    <p class="fw-bold mb-3">${barang.jumlah_total} unit</p>
                                    
                                    <h6 class="text-muted mb-1">Jumlah Tersedia</h6>
                                    <p class="fw-bold mb-3">${barang.jumlah_tersedia} unit</p>
                                    
                                    <h6 class="text-muted mb-1">Kondisi</h6>
                                    <span class="badge bg-${kondisiClass[barang.kondisi] || 'secondary'} mb-3">
                                        ${kondisiText[barang.kondisi] || barang.kondisi}
                                    </span>
                                    
                                    <h6 class="text-muted mb-1">Tanggal Input</h6>
                                    <p class="fw-bold mb-3">${new Date(barang.created_at).toLocaleDateString('id-ID')}</p>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6 class="text-muted mb-1">Deskripsi</h6>
                                    <p class="mb-0">${barang.deskripsi || 'Tidak ada deskripsi'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Show edit button
                document.getElementById('editBarangBtn').style.display = 'inline-block';
                document.getElementById('editBarangBtn').onclick = function() {
                    window.location.href = `?action=edit&id=${barang.id}`;
                };
            }
        })
        .catch(error => {
            document.getElementById('detailModalBody').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5>Error</h5>
                    <p class="text-muted">Terjadi kesalahan saat memuat data</p>
                </div>
            `;
        });
}

// Reset modal when closed
document.getElementById('detailModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('editBarangBtn').style.display = 'none';
});

// Show delete confirmation modal
function showDeleteModal(id, namaBarang, kodeBarang) {
    // Set info barang yang akan dihapus
    document.getElementById('deleteItemInfo').innerHTML = `
        <div class="card border-danger">
            <div class="card-body">
                <h6 class="card-title text-danger">
                    <i class="fas fa-box me-2"></i>${namaBarang}
                </h6>
                <p class="card-text mb-1">
                    <strong>Kode:</strong> <span class="badge bg-secondary">${kodeBarang}</span>
                </p>
                <p class="card-text mb-0">
                    <strong>ID:</strong> <span class="text-muted">#${id}</span>
                </p>
            </div>
        </div>
    `;
    
    // Set delete URL for confirm button
    document.getElementById('confirmDeleteBtn').onclick = function() {
        window.location.href = `?action=delete&id=${id}`;
    };

    // Show modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?> 