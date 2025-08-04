<?php
/**
 * Data Kategori - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Handle AJAX request untuk detail
$action = $_GET['action'] ?? '';
if ($action == 'get_detail' && isset($_GET['id'])) {
    session_start();
    $kategori_id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id = ?");
        $stmt->execute([$kategori_id]);
        $kategori = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($kategori) {
            header('Content-Type: application/json');
            echo json_encode($kategori);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Kategori tidak ditemukan']);
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
    }
    exit();
}

$page_title = 'Data Kategori';
require_once '../includes/header.php';
?>

<style>
    /* Mobile responsive for kategori table */
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
        
        /* Filter section mobile */
        .card-body {
            padding: 1rem;
        }
        
        .form-label {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .form-control, .form-select {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }
        
        /* Header mobile */
        .card-header {
            padding: 1rem;
        }
        
        .card-header h5 {
            font-size: 1rem;
        }
        
        /* Button text hidden on mobile */
        .btn i {
            margin-right: 0.25rem;
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
        
        /* Compact filter */
        .card-body {
            padding: 0.75rem;
        }
        
        .form-label {
            font-size: 0.8rem;
        }
        
        .form-control, .form-select {
            font-size: 0.8rem;
            padding: 0.4rem 0.6rem;
        }
        
        /* Compact header */
        .card-header {
            padding: 0.75rem;
        }
        
        .card-header h5 {
            font-size: 0.9rem;
        }
        
        /* Stack buttons vertically on very small screens */
        .btn-group {
            flex-direction: row;
            gap: 0.1rem;
        }
        
        .btn-group .btn {
            width: auto;
            justify-content: center;
        }
    }
    
    /* Extra small screens */
    @media (max-width: 480px) {
        .table-responsive {
            font-size: 0.7rem;
        }
        
        .table td, .table th {
            padding: 0.3rem 0.15rem;
        }
        
        .btn-group .btn {
            padding: 0.1rem 0.2rem;
            font-size: 0.6rem;
        }
        
        .badge {
            font-size: 0.6rem;
            padding: 0.15rem 0.3rem;
        }
        
        .fw-semibold {
            font-size: 0.8rem;
        }
        
        .text-muted {
            font-size: 0.65rem;
        }
        
        /* Minimal padding */
        .card-body {
            padding: 0.5rem;
        }
        
        .card-header {
            padding: 0.5rem;
        }
        
        .form-control, .form-select {
            font-size: 0.75rem;
            padding: 0.3rem 0.5rem;
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
    $kategori_id = (int)$_GET['id'];
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cek apakah kategori digunakan di barang
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM barang WHERE kategori_id = ?");
        $stmt->execute([$kategori_id]);
        $barang_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($barang_count > 0) {
                    $error = "Kategori tidak dapat dihapus karena masih digunakan oleh $barang_count barang!";
        echo "<script>alert('$error'); window.location.href='index.php';</script>";
        exit();
        }
        
        // Ambil info kategori
        $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id = ?");
        $stmt->execute([$kategori_id]);
        $kategori = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($kategori) {
            // Hapus kategori
            $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
            $stmt->execute([$kategori_id]);
            
            $success = 'Kategori berhasil dihapus!';
            echo "<script>alert('Kategori berhasil dihapus!'); window.location.href='index.php';</script>";
            exit();
        } else {
            $error = 'Kategori tidak ditemukan!';
            echo "<script>alert('Kategori tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch(Exception $e) {
        $error = 'Gagal menghapus kategori!';
        echo "<script>alert('Gagal menghapus kategori!'); window.location.href='index.php';</script>";
        exit();
    }
}

// Ambil data kategori dengan pagination dan search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query untuk data kategori
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(nama_kategori LIKE ? OR deskripsi LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Query untuk total data
    $count_query = "SELECT COUNT(*) as total FROM kategori $where_clause";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Query untuk data kategori dengan join ke barang
    $query = "
        SELECT 
            k.*,
            COUNT(b.id) as jumlah_barang
        FROM kategori k
        LEFT JOIN barang b ON k.id = b.kategori_id
        $where_clause
        GROUP BY k.id
        ORDER BY k.nama_kategori ASC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $kategori_list = [];
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
                            <i class="fas fa-tags me-2"></i>Data Kategori
                        </h2>
                        <p class="text-muted mb-0">Kelola kategori barang inventaris sekolah</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Kategori
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
                    <div class="col-lg-8 col-md-6 col-12">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Cari kategori..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i><span class="d-none d-md-inline">Cari</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-12 text-md-end">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-refresh me-2"></i><span class="d-none d-md-inline">Reset</span>
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
                    <div class="col-md-6 col-12 mb-2 mb-md-0">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Daftar Kategori
                        </h5>
                    </div>
                    <div class="col-md-6 col-12 text-md-end">
                        <small class="text-muted">
                            Total: <strong><?= number_format($total_records) ?></strong> kategori
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($kategori_list)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h5>Tidak ada data kategori</h5>
                        <p class="text-muted">
                            <?= !empty($search) ? 'Tidak ada kategori yang sesuai dengan pencarian.' : 'Belum ada kategori yang ditambahkan.' ?>
                        </p>
                        <?php if (empty($search)): ?>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Kategori Pertama
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="25%">Nama Kategori</th>
                                    <th width="40%" class="d-none d-md-table-cell">Deskripsi</th>
                                    <th width="10%">Jumlah Barang</th>
                                    <th width="10%">Tanggal Dibuat</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kategori_list as $index => $kategori): ?>
                                    <tr>
                                        <td><?= $offset + $index + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded me-2 d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-tag text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($kategori['nama_kategori']) ?></div>
                                                    <small class="text-muted">ID: #<?= $kategori['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <?php if ($kategori['deskripsi']): ?>
                                                <?= htmlspecialchars($kategori['deskripsi']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak ada deskripsi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-box me-1"></i><?= $kategori['jumlah_barang'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($kategori['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" onclick="showDetail(<?= $kategori['id'] ?>)" 
                                                        class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="?action=edit&id=<?= $kategori['id'] ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="showDeleteModal(<?= $kategori['id'] ?>, '<?= htmlspecialchars($kategori['nama_kategori']) ?>')" 
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

<!-- Modal Detail Kategori -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-tag me-2"></i>Detail Kategori
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail kategori...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Tutup
                </button>
                <button type="button" class="btn btn-primary" id="editKategoriBtn" style="display: none;">
                    <i class="fas fa-edit me-2"></i>Edit Kategori
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
                    <h5>Hapus Kategori</h5>
                    <p class="text-muted">Apakah Anda yakin ingin menghapus kategori ini?</p>
                </div>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Peringatan:</strong> Tindakan ini tidak dapat dibatalkan. Pastikan kategori tidak digunakan oleh barang manapun.
                </div>
                <div id="deleteItemInfo" class="text-center">
                    <!-- Info kategori yang akan dihapus akan ditampilkan di sini -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus Kategori
                </button>
            </div>
        </div>
    </div>
</div>

<script>
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
            <p class="mt-2">Memuat detail kategori...</p>
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
                const kategori = data;
                
                document.getElementById('detailModalBody').innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="bg-primary rounded d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 120px; height: 120px;">
                                    <i class="fas fa-tag fa-4x text-white"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">ID Kategori</h6>
                                    <p class="fw-bold mb-3">#${kategori.id}</p>
                                    
                                    <h6 class="text-muted mb-1">Nama Kategori</h6>
                                    <p class="fw-bold mb-3">${kategori.nama_kategori}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Tanggal Dibuat</h6>
                                    <p class="fw-bold mb-3">${new Date(kategori.created_at).toLocaleDateString('id-ID')}</p>
                                    
                                    <h6 class="text-muted mb-1">Terakhir Update</h6>
                                    <p class="fw-bold mb-3">${new Date(kategori.updated_at).toLocaleDateString('id-ID')}</p>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6 class="text-muted mb-1">Deskripsi</h6>
                                    <p class="mb-0">${kategori.deskripsi || 'Tidak ada deskripsi'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Show edit button
                document.getElementById('editKategoriBtn').style.display = 'inline-block';
                document.getElementById('editKategoriBtn').onclick = function() {
                    window.location.href = `?action=edit&id=${kategori.id}`;
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

// Show delete confirmation modal
function showDeleteModal(id, namaKategori) {
    // Set info kategori yang akan dihapus
    document.getElementById('deleteItemInfo').innerHTML = `
        <div class="card border-danger">
            <div class="card-body">
                <h6 class="card-title text-danger">
                    <i class="fas fa-tag me-2"></i>${namaKategori}
                </h6>
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

// Reset modal when closed
document.getElementById('detailModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('editKategoriBtn').style.display = 'none';
});
</script>

<?php require_once '../includes/footer.php'; ?> 