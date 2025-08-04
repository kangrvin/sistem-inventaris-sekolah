<?php
/**
 * Data Lokasi - Admin Panel
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
                l.*,
                COUNT(b.id) as jumlah_barang
            FROM lokasi l
            LEFT JOIN barang b ON l.id = b.lokasi_id
            WHERE l.id = ?
            GROUP BY l.id
        ");
        $stmt->execute([$id]);
        $lokasi = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lokasi) {
            echo json_encode([
                'success' => true,
                'data' => $lokasi
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Lokasi tidak ditemukan'
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

$page_title = 'Data Lokasi';
require_once '../includes/header.php';

// Handle actions
$action = $_GET['action'] ?? '';

if ($action == 'add' || $action == 'edit') {
    // Include form langsung
    include 'form.php';
    exit();
}

// Handle delete
if ($action == 'delete' && isset($_GET['id'])) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $lokasi_id = $_GET['id'];
        
        // Cek apakah lokasi digunakan oleh barang
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM barang WHERE lokasi_id = ?");
        $stmt->execute([$lokasi_id]);
        $barang_count = $stmt->fetch()['count'];
        
        if ($barang_count > 0) {
            $error = "Lokasi tidak dapat dihapus karena masih digunakan oleh $barang_count barang!";
            echo "<script>alert('$error'); window.location.href='index.php';</script>";
            exit();
        }
        
        // Hapus lokasi
        $stmt = $pdo->prepare("DELETE FROM lokasi WHERE id = ?");
        if ($stmt->execute([$lokasi_id])) {
            $success = 'Lokasi berhasil dihapus!';
            echo "<script>alert('Lokasi berhasil dihapus!'); window.location.href='index.php';</script>";
            exit();
        } else {
            $error = 'Lokasi tidak ditemukan!';
            echo "<script>alert('Lokasi tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch(Exception $e) {
        $error = 'Gagal menghapus lokasi!';
        echo "<script>alert('Gagal menghapus lokasi!'); window.location.href='index.php';</script>";
        exit();
    }
}

// Pagination
try {
$pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search dan filter
$search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(l.nama_lokasi LIKE ? OR l.deskripsi LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    

}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query untuk total data
$count_query = "SELECT COUNT(*) as total FROM lokasi l $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Query untuk data lokasi dengan join ke barang
$query = "
    SELECT 
        l.*,
        COUNT(b.id) as jumlah_barang
    FROM lokasi l
    LEFT JOIN barang b ON l.id = b.lokasi_id
    $where_clause
    GROUP BY l.id, l.nama_lokasi, l.deskripsi, l.created_at, l.updated_at
    ORDER BY l.nama_lokasi ASC
    LIMIT $limit OFFSET $offset
";



$stmt = $pdo->prepare($query);
$stmt->execute($params);
$lokasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);



} catch(Exception $e) {
    $lokasi_list = [];
    $total_records = 0;
    $total_pages = 0;
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
                            <i class="fas fa-map-marker-alt me-2"></i>Data Lokasi
                        </h2>
                        <p class="text-muted mb-0">Kelola lokasi barang inventaris sekolah</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Lokasi
                            </a>
                        </div>
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
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Cari nama lokasi atau deskripsi..." value="<?= htmlspecialchars($search) ?>"
                                   autocomplete="off">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
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
                            <i class="fas fa-list me-2"></i>Daftar Lokasi
                        </h5>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            <?php if (!empty($search)): ?>
                                Hasil pencarian: <?= number_format($total_records) ?> lokasi
                                <span class="badge bg-primary ms-2">"<?= htmlspecialchars($search) ?>"</span>
                            <?php else: ?>
                                Total: <?= number_format($total_records) ?> lokasi
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($lokasi_list)): ?>
                    <div class="text-center py-5">
                        <?php if (!empty($search)): ?>
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada hasil pencarian</h5>
                            <p class="text-muted">Tidak ditemukan lokasi yang sesuai dengan kata kunci: <strong>"<?= htmlspecialchars($search) ?>"</strong></p>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Hapus Pencarian
                            </a>
                        <?php else: ?>
                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada data lokasi</h5>
                            <p class="text-muted">Belum ada lokasi yang ditambahkan</p>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Lokasi Pertama
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="lokasiTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="25%">Nama Lokasi</th>
                                    <th width="35%">Deskripsi</th>
                                    <th width="15%">Jumlah Barang</th>
                                    <th width="10%">Tanggal Dibuat</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lokasi_list as $index => $lokasi): ?>
                                    <tr>
                                        <td><?= $offset + $index + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <i class="fas fa-map-marker-alt text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($lokasi['nama_lokasi']) ?></h6>
                                                    <small class="text-muted">ID: <?= $lokasi['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($lokasi['deskripsi'])): ?>
                                                <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                      title="<?= htmlspecialchars($lokasi['deskripsi']) ?>">
                                                    <?= htmlspecialchars($lokasi['deskripsi']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= number_format($lokasi['jumlah_barang']) ?> barang
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($lokasi['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="showDetail(<?= $lokasi['id'] ?>)" 
                                                        title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="?action=edit&id=<?= $lokasi['id'] ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?= $lokasi['id'] ?>, '<?= htmlspecialchars($lokasi['nama_lokasi']) ?>', <?= $lokasi['jumlah_barang'] ?>)" 
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
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
                    <i class="fas fa-map-marker-alt me-2"></i>Detail Lokasi
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                </div>
                <h6 class="text-center mb-3">Apakah Anda yakin ingin menghapus lokasi ini?</h6>
                <div class="alert alert-warning">
                    <strong>Nama Lokasi:</strong> <span id="deleteLokasiName"></span><br>
                    <strong>Jumlah Barang:</strong> <span id="deleteBarangCount"></span> barang
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Perhatian:</strong> Lokasi yang masih digunakan oleh barang tidak dapat dihapus.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Hapus Lokasi
                </button>
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
                const lokasi = data.data;
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-center mb-4">
                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                    <i class="fas fa-map-marker-alt text-white"></i>
                                </div>
                                <h4>${lokasi.nama_lokasi}</h4>
                                <p class="text-muted">ID: ${lokasi.id}</p>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-info-circle me-2"></i>Informasi Lokasi</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Nama Lokasi:</strong></td>
                                            <td>${lokasi.nama_lokasi}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Deskripsi:</strong></td>
                                            <td>${lokasi.deskripsi || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Jumlah Barang:</strong></td>
                                            <td><span class="badge bg-info">${lokasi.jumlah_barang} barang</span></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-calendar me-2"></i>Informasi Sistem</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Dibuat:</strong></td>
                                            <td>${new Date(lokasi.created_at).toLocaleDateString('id-ID')}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Diupdate:</strong></td>
                                            <td>${new Date(lokasi.updated_at).toLocaleDateString('id-ID')}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5 class="text-warning">Data tidak ditemukan</h5>
                        <p class="text-muted">Lokasi yang Anda cari tidak ditemukan atau telah dihapus.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">Terjadi Kesalahan</h5>
                    <p class="text-muted">Gagal memuat data lokasi. Silakan coba lagi.</p>
                </div>
            `;
        });
}

// Delete confirmation
let deleteId = null;

function confirmDelete(id, nama, jumlahBarang) {
    deleteId = id;
    document.getElementById('deleteLokasiName').textContent = nama;
    document.getElementById('deleteBarangCount').textContent = jumlahBarang;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteId) {
        window.location.href = `?action=delete&id=${deleteId}`;
    }
});

// DataTable initialization - disabled untuk menghindari konflik dengan pagination custom
// document.addEventListener('DOMContentLoaded', function() {
//     if (typeof $ !== 'undefined') {
//         $('#lokasiTable').DataTable({
//             "pageLength": 10,
//             "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
//             "language": {
//                 "url": "//cdn.datatables.net/plug-ins/1.13.0/i18n/id.json"
//             },
//             "order": [[1, "asc"]],
//             "responsive": true
//         });
//     }
// });

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
/* Ensure consistent styling for location items */
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
</style>

<?php require_once '../includes/footer.php'; ?> 