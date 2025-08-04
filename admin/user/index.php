<?php
/**
 * Kelola User - Admin Panel
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
                u.*,
                COUNT(p.id) as total_peminjaman
            FROM users u
            LEFT JOIN peminjaman p ON u.id = p.created_by
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User tidak ditemukan'
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

$page_title = 'Kelola User';
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
        
        $user_id = $_GET['id'];
        
        // Cek apakah user yang akan dihapus adalah diri sendiri
        if ($user_id == $_SESSION['user_id']) {
            echo "<script>alert('Anda tidak dapat menghapus akun sendiri!'); window.location.href='index.php';</script>";
            exit();
        }
        
        // Cek apakah user digunakan oleh peminjaman
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM peminjaman WHERE created_by = ?");
        $stmt->execute([$user_id]);
        $peminjaman_count = $stmt->fetch()['count'];
        
        if ($peminjaman_count > 0) {
            $error = "User tidak dapat dihapus karena masih memiliki $peminjaman_count data peminjaman!";
            echo "<script>alert('$error'); window.location.href='index.php';</script>";
            exit();
        }
        
        // Cek apakah user digunakan oleh barang
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM barang WHERE created_by = ?");
        $stmt->execute([$user_id]);
        $barang_count = $stmt->fetch()['count'];
        
        if ($barang_count > 0) {
            $error = "User tidak dapat dihapus karena masih memiliki $barang_count data barang!";
            echo "<script>alert('$error'); window.location.href='index.php';</script>";
            exit();
        }
        
        // Hapus user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            echo "<script>alert('User berhasil dihapus!'); window.location.href='index.php';</script>";
            exit();
        } else {
            echo "<script>alert('User tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch(Exception $e) {
        echo "<script>alert('Gagal menghapus user!'); window.location.href='index.php';</script>";
        exit();
    }
}

// Handle status toggle
if ($action == 'toggle_status' && isset($_GET['id'])) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $user_id = $_GET['id'];
        
        // Cek apakah user yang akan diubah adalah diri sendiri
        if ($user_id == $_SESSION['user_id']) {
            echo "<script>alert('Anda tidak dapat mengubah status akun sendiri!'); window.location.href='index.php';</script>";
            exit();
        }
        
        // Get current status
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_status = $stmt->fetch()['status'];
        
        // Toggle status
        $new_status = ($current_status == 'aktif') ? 'nonaktif' : 'aktif';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $user_id])) {
            $status_text = ($new_status == 'aktif') ? 'diaktifkan' : 'dinonaktifkan';
            echo "<script>alert('User berhasil $status_text!'); window.location.href='index.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal mengubah status user!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch(Exception $e) {
        echo "<script>alert('Gagal mengubah status user!'); window.location.href='index.php';</script>";
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
    $role_filter = isset($_GET['role']) ? validateInput($_GET['role']) : '';
    $status_filter = isset($_GET['status']) ? validateInput($_GET['status']) : '';
    $where_conditions = [];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(u.username LIKE ? OR u.nama_lengkap LIKE ? OR u.email LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($role_filter)) {
        $where_conditions[] = "u.role = ?";
        $params[] = $role_filter;
    }

    if (!empty($status_filter)) {
        $where_conditions[] = "u.status = ?";
        $params[] = $status_filter;
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Query untuk total data
    $count_query = "
        SELECT COUNT(*) as total 
        FROM users u
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    // Query untuk data user
    $query = "
        SELECT 
            u.*,
            COUNT(p.id) as total_peminjaman
        FROM users u
        LEFT JOIN peminjaman p ON u.id = p.created_by
        $where_clause
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $user_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    $user_list = [];
    $total_records = 0;
    $total_pages = 0;
}

// Get role counts for dashboard
try {
    $stmt = $pdo->prepare("
        SELECT 
            role,
            COUNT(*) as count
        FROM users 
        GROUP BY role
    ");
    $stmt->execute();
    $role_counts = [];
    while ($row = $stmt->fetch()) {
        $role_counts[$row['role']] = $row['count'];
    }
} catch(Exception $e) {
    $role_counts = [];
}

// Get status counts
try {
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM users 
        GROUP BY status
    ");
    $stmt->execute();
    $status_counts = [];
    while ($row = $stmt->fetch()) {
        $status_counts[$row['status']] = $row['count'];
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
                            <i class="fas fa-users me-2"></i>Kelola User
                        </h2>
                        <p class="text-muted mb-0">Kelola pengguna sistem inventaris sekolah</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah User
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
                        <h4 class="mb-0"><?= $role_counts['admin'] ?? 0 ?></h4>
                        <small>Total Admin</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-shield fa-2x"></i>
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
                        <h4 class="mb-0"><?= $role_counts['petugas'] ?? 0 ?></h4>
                        <small>Total Petugas</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-tie fa-2x"></i>
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
                        <h4 class="mb-0"><?= $status_counts['aktif'] ?? 0 ?></h4>
                        <small>User Aktif</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $status_counts['nonaktif'] ?? 0 ?></h4>
                        <small>User Nonaktif</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-times fa-2x"></i>
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
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Cari username, nama lengkap, atau email..." value="<?= htmlspecialchars($search) ?>"
                                   autocomplete="off">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="role" id="roleFilter">
                            <option value="">Semua Role</option>
                            <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="petugas" <?= $role_filter == 'petugas' ? 'selected' : '' ?>>Petugas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status" id="statusFilter">
                            <option value="">Semua Status</option>
                            <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $status_filter == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
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
                            <i class="fas fa-list me-2"></i>Daftar User
                        </h5>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                                Hasil pencarian: <?= number_format($total_records) ?> user
                                <?php if (!empty($search)): ?>
                                    <span class="badge bg-primary ms-2">"<?= htmlspecialchars($search) ?>"</span>
                                <?php endif; ?>
                                <?php if (!empty($role_filter)): ?>
                                    <span class="badge bg-info ms-2">Role: <?= ucfirst($role_filter) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($status_filter)): ?>
                                    <span class="badge bg-warning ms-2">Status: <?= ucfirst($status_filter) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                Total: <?= number_format($total_records) ?> user
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($user_list)): ?>
                    <div class="text-center py-5">
                        <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada hasil pencarian</h5>
                            <p class="text-muted">Tidak ditemukan user yang sesuai dengan kriteria pencarian.</p>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Hapus Filter
                            </a>
                        <?php else: ?>
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada data user</h5>
                            <p class="text-muted">Belum ada user yang ditambahkan</p>
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah User Pertama
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="userTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">User</th>
                                    <th width="15%">Role</th>
                                    <th width="20%">Kontak</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Aktivitas</th>
                                    <th width="10%">Tanggal Dibuat</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_list as $index => $user): ?>
                                    <tr>
                                        <td><?= $offset + $index + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($user['foto']): ?>
                                                    <img src="../../uploads/<?= htmlspecialchars($user['foto']) ?>" 
                                                         class="avatar-sm rounded-circle me-3" alt="Foto User">
                                                <?php else: ?>
                                                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($user['nama_lengkap']) ?></h6>
                                                    <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $role_class = '';
                                            $role_icon = '';
                                            switch($user['role']) {
                                                case 'admin':
                                                    $role_class = 'bg-danger';
                                                    $role_icon = 'fas fa-user-shield';
                                                    break;
                                                case 'petugas':
                                                    $role_class = 'bg-success';
                                                    $role_icon = 'fas fa-user-tie';
                                                    break;
                                                case 'user':
                                                    $role_class = 'bg-secondary';
                                                    $role_icon = 'fas fa-user';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?= $role_class ?>">
                                                <i class="<?= $role_icon ?> me-1"></i><?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <small class="text-muted">Email:</small><br>
                                                <strong><?= htmlspecialchars($user['email'] ?? '-') ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = ($user['status'] == 'aktif') ? 'bg-success' : 'bg-warning';
                                            $status_text = ($user['status'] == 'aktif') ? 'Aktif' : 'Nonaktif';
                                            ?>
                                            <span class="badge <?= $status_class ?>">
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= number_format($user['total_peminjaman']) ?> peminjaman
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        onclick="showDetail(<?= $user['id'] ?>)" 
                                                        title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="?action=edit&id=<?= $user['id'] ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                            onclick="confirmToggleStatus(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>', '<?= $user['status'] ?>')" 
                                                            title="<?= $user['status'] == 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                        <i class="fas fa-<?= $user['status'] == 'aktif' ? 'ban' : 'check' ?>"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama_lengkap']) ?>', <?= $user['total_peminjaman'] ?>)" 
                                                            title="Hapus">
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
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>">
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
                    <i class="fas fa-user me-2"></i>Detail User
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
                <h6 class="text-center mb-3">Apakah Anda yakin ingin menghapus user ini?</h6>
                <div class="alert alert-warning">
                    <strong>Nama User:</strong> <span id="deleteUserName"></span><br>
                    <strong>Total Peminjaman:</strong> <span id="deletePeminjamanCount"></span> data
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Perhatian:</strong> User yang masih memiliki data peminjaman atau barang tidak dapat dihapus.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Hapus User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Status Modal -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="toggleStatusModalHeader">
                <h5 class="modal-title" id="toggleStatusModalLabel">
                    <i class="fas fa-question-circle me-2"></i>Konfirmasi Ubah Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-question-circle fa-3x text-primary" id="toggleStatusIcon"></i>
                </div>
                <h6 class="text-center mb-3" id="toggleStatusMessage"></h6>
                <div class="alert alert-info">
                    <strong>Nama User:</strong> <span id="toggleStatusUserName"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmToggleStatusBtn">
                    <i class="fas fa-check me-2"></i>Konfirmasi
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
                const user = data.data;
                const roleClass = getRoleClass(user.role);
                const roleIcon = getRoleIcon(user.role);
                const statusClass = user.status == 'aktif' ? 'bg-success' : 'bg-warning';
                const statusText = user.status == 'aktif' ? 'Aktif' : 'Nonaktif';
                
                modalBody.innerHTML = `
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-center mb-4">
                                ${user.foto ? 
                                    `<img src="../../uploads/${user.foto}" class="avatar-lg rounded-circle mb-3" alt="Foto User">` :
                                    `<div class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                        <i class="fas fa-user fa-2x text-white"></i>
                                    </div>`
                                }
                                <h4>${user.nama_lengkap}</h4>
                                <p class="text-muted">@${user.username}</p>
                                <span class="badge ${roleClass} fs-6 me-2">
                                    <i class="${roleIcon} me-1"></i>${user.role.toUpperCase()}
                                </span>
                                <span class="badge ${statusClass} fs-6">
                                    ${statusText}
                                </span>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><i class="fas fa-user me-2"></i>Informasi User</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Username:</strong></td>
                                            <td>${user.username}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Nama Lengkap:</strong></td>
                                            <td>${user.nama_lengkap}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>${user.email || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Role:</strong></td>
                                            <td><span class="badge ${roleClass}">${user.role.toUpperCase()}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td><span class="badge ${statusClass}">${statusText}</span></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6><i class="fas fa-chart-bar me-2"></i>Statistik Aktivitas</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Total Peminjaman:</strong></td>
                                            <td><span class="badge bg-info">${user.total_peminjaman} data</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Bergabung Sejak:</strong></td>
                                            <td>${new Date(user.created_at).toLocaleDateString('id-ID')}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Terakhir Update:</strong></td>
                                            <td>${new Date(user.updated_at).toLocaleDateString('id-ID')}</td>
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
                        <p class="text-muted">User yang Anda cari tidak ditemukan atau telah dihapus.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">Terjadi Kesalahan</h5>
                    <p class="text-muted">Gagal memuat data user. Silakan coba lagi.</p>
                </div>
            `;
        });
}

// Delete confirmation
let deleteId = null;

function confirmDelete(id, nama, totalPeminjaman) {
    deleteId = id;
    document.getElementById('deleteUserName').textContent = nama;
    document.getElementById('deletePeminjamanCount').textContent = totalPeminjaman;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteId) {
        window.location.href = `?action=delete&id=${deleteId}`;
    }
});

// Toggle status confirmation
let toggleStatusId = null;
let toggleStatusCurrentStatus = null;

function confirmToggleStatus(id, nama, currentStatus) {
    toggleStatusId = id;
    toggleStatusCurrentStatus = currentStatus;
    
    const modal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
    const header = document.getElementById('toggleStatusModalHeader');
    const icon = document.getElementById('toggleStatusIcon');
    const message = document.getElementById('toggleStatusMessage');
    const userName = document.getElementById('toggleStatusUserName');
    const confirmBtn = document.getElementById('confirmToggleStatusBtn');
    
    userName.textContent = nama;
    
    if (currentStatus === 'aktif') {
        header.className = 'modal-header bg-warning text-white';
        icon.className = 'fas fa-ban fa-3x text-warning';
        message.textContent = 'Apakah Anda yakin ingin menonaktifkan user ini?';
        confirmBtn.className = 'btn btn-warning';
        confirmBtn.innerHTML = '<i class="fas fa-ban me-2"></i>Nonaktifkan';
    } else {
        header.className = 'modal-header bg-success text-white';
        icon.className = 'fas fa-check-circle fa-3x text-success';
        message.textContent = 'Apakah Anda yakin ingin mengaktifkan user ini?';
        confirmBtn.className = 'btn btn-success';
        confirmBtn.innerHTML = '<i class="fas fa-check me-2"></i>Aktifkan';
    }
    
    modal.show();
}

document.getElementById('confirmToggleStatusBtn').addEventListener('click', function() {
    if (toggleStatusId) {
        window.location.href = `?action=toggle_status&id=${toggleStatusId}`;
    }
});

// Helper functions
function getRoleClass(role) {
    switch(role) {
        case 'admin': return 'bg-danger';
        case 'petugas': return 'bg-success';
        case 'user': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

function getRoleIcon(role) {
    switch(role) {
        case 'admin': return 'fas fa-user-shield';
        case 'petugas': return 'fas fa-user-tie';
        case 'user': return 'fas fa-user';
        default: return 'fas fa-user';
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
    const roleFilter = document.getElementById('roleFilter');
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
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            submitFilter();
        });
    }
    
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
/* Ensure consistent styling for user items */
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

.avatar-lg {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    font-size: 2rem;
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