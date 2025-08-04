<?php
/**
 * Laporan - Petugas Panel (View Only)
 * Sistem Inventaris Sekolah
 */

// Get filter parameters
$start_date = $_GET['start_date'] ?? ''; // Allow empty for all dates
$end_date = $_GET['end_date'] ?? ''; // Allow empty for all dates
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$page_title = 'Laporan';
require_once '../includes/header.php';

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];

// Get data for dashboard and main report
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Dashboard stats untuk petugas (semua data peminjaman di sekolah)
    if ($start_date && $end_date) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $total_peminjaman = $stmt->fetch()['total'];
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ?");
        $stmt->execute([date('Y-m-01'), date('Y-m-t')]);
        $total_peminjaman = $stmt->fetch()['total'];
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'");
    $stmt->execute();
    $peminjaman_aktif = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam' AND tanggal_kembali_rencana < CURDATE()");
    $stmt->execute();
    $total_terlambat = $stmt->fetch()['total'];
    
    if ($start_date && $end_date) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dikembalikan' AND tanggal_kembali_aktual BETWEEN ? AND ?");
        $stmt->execute([$start_date, $end_date]);
        $total_pengembalian = $stmt->fetch()['total'];
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dikembalikan' AND tanggal_kembali_aktual BETWEEN ? AND ?");
        $stmt->execute([date('Y-m-01'), date('Y-m-t')]);
        $total_pengembalian = $stmt->fetch()['total'];
    }
    
    // Data untuk chart peminjaman bulanan (6 bulan terakhir) - semua data
    $chart_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE DATE_FORMAT(tanggal_pinjam, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $chart_data['labels'][] = date('M Y', strtotime("-$i months"));
        $chart_data['data'][] = $stmt->fetch()['total'];
    }
    
    // Main data query with filters
    $where_conditions = [];
    $params = [];
    
    // Date filter
    if ($start_date && $end_date) {
        $where_conditions[] = "p.tanggal_pinjam BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
    }
    
    // Status filter
    if ($status) {
        if ($status == 'terlambat') {
            $where_conditions[] = "p.status = 'dipinjam' AND p.tanggal_kembali_rencana < CURDATE()";
        } else {
            $where_conditions[] = "p.status = ?";
            $params[] = $status;
        }
    }
    
    // Debug: Tampilkan query yang akan dijalankan
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo "<div class='alert alert-info'>";
        echo "<strong>Debug Info:</strong><br>";
        echo "Status filter: " . ($status ?: 'Tidak ada') . "<br>";
        echo "Where conditions: " . implode(' AND ', $where_conditions) . "<br>";
        echo "Parameters: " . implode(', ', $params) . "<br>";
        echo "</div>";
    }
    
    // Search filter
    if ($search) {
        $where_conditions[] = "(b.nama_barang LIKE ? OR p.peminjam_nama LIKE ? OR p.kode_peminjaman LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total records for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM peminjaman p 
        LEFT JOIN barang b ON p.barang_id = b.id 
        LEFT JOIN users u ON p.created_by = u.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    
    // Debug: Tampilkan statistik data di database
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        $debug_stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM peminjaman GROUP BY status");
        $debug_stmt->execute();
        $debug_data = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $terlambat_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam' AND tanggal_kembali_rencana < CURDATE()");
        $terlambat_stmt->execute();
        $terlambat_count = $terlambat_stmt->fetch()['total'];
        
        echo "<div class='alert alert-warning'>";
        echo "<strong>Database Statistics:</strong><br>";
        foreach ($debug_data as $row) {
            echo "Status '{$row['status']}': {$row['total']} data<br>";
        }
        echo "Data terlambat (dipinjam + lewat tanggal): {$terlambat_count} data<br>";
        echo "Total records found with current filter: {$total_records} data<br>";
        echo "<br><strong>Debug Query Info:</strong><br>";
        echo "SQL Query: " . $sql . "<br>";
        echo "Where Clause: " . $where_clause . "<br>";
        echo "Parameters: " . implode(', ', $params) . "<br>";
        echo "Current Status Filter: " . ($status ?: 'Tidak ada') . "<br>";
        echo "Current Date Range: {$start_date} to {$end_date}<br>";
        echo "</div>";
    }
    
    // Pagination
    $records_per_page = 10;
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($current_page - 1) * $records_per_page;
    $total_pages = ceil($total_records / $records_per_page);
    
    // Main data query
    $sql = "
        SELECT p.*, b.nama_barang, b.kode_barang, u.nama_lengkap as petugas_nama
        FROM peminjaman p 
        LEFT JOIN barang b ON p.barang_id = b.id 
        LEFT JOIN users u ON p.created_by = u.id
        $where_clause
        ORDER BY p.created_at DESC 
        LIMIT $records_per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $peminjaman_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $total_peminjaman = 0;
    $peminjaman_aktif = 0;
    $total_terlambat = 0;
    $total_pengembalian = 0;
    $chart_data = ['labels' => [], 'data' => []];
    $peminjaman_data = [];
    $total_records = 0;
    $total_pages = 0;
    $current_page = 1;
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
                            <i class="fas fa-chart-bar me-2 text-primary"></i>Laporan
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Lihat laporan aktivitas peminjaman sekolah (semua data)
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

<!-- Dashboard Stats -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-primary fw-bold"><?= number_format($total_peminjaman) ?></h4>
                        <small class="text-muted">Peminjaman Bulan Ini</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-handshake fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-success fw-bold"><?= number_format($peminjaman_aktif) ?></h4>
                        <small class="text-muted">Peminjaman Aktif</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-warning fw-bold"><?= number_format($total_terlambat) ?></h4>
                        <small class="text-muted">Peminjaman Terlambat</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-info fw-bold"><?= number_format($total_pengembalian) ?></h4>
                        <small class="text-muted">Pengembalian Bulan Ini</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-undo fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>Filter Laporan
        </h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-calendar me-1"></i>Tanggal Mulai
                </label>
                                 <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>" placeholder="Kosongkan untuk semua tanggal">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">
                    <i class="fas fa-calendar me-1"></i>Tanggal Akhir
                </label>
                                 <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>" placeholder="Kosongkan untuk semua tanggal">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">
                    <i class="fas fa-check-circle me-1"></i>Status
                </label>
                                 <select class="form-select" name="status" id="statusFilter">
                     <option value="">Semua Status</option>
                     <option value="dipinjam" <?= $status == 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                     <option value="dikembalikan" <?= $status == 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                     <option value="terlambat" <?= $status == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                 </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-search me-1"></i>Cari
                </label>
                                 <input type="text" class="form-control" name="search" id="searchFilter" value="<?= htmlspecialchars($search) ?>" placeholder="Cari barang, peminjam, atau kode..." onkeyup="debounceSearch()">
            </div>
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="filterBtn">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                    <a href="?" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                    <a href="?status=<?= $status ?>&search=<?= urlencode($search) ?>" class="btn btn-outline-success">
                        <i class="fas fa-calendar-times me-2"></i>Hapus Filter Tanggal
                    </a>
                    <!-- <a href="?debug=1" class="btn btn-outline-info">
                        <i class="fas fa-bug me-2"></i>Debug
                    </a> -->
                    <div class="d-none" id="loadingIndicator">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <span>Memproses...</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Chart Section -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-chart-line me-2 text-primary"></i>Grafik Peminjaman (6 Bulan Terakhir)
                </h6>
            </div>
            <div class="card-body">
                <canvas id="peminjamanChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-info-circle me-2 text-primary"></i>Informasi
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb me-2"></i>
                    <strong>Tips:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Laporan ini menampilkan semua data peminjaman sekolah</li>
                        <li>Grafik menunjukkan tren peminjaman 6 bulan terakhir</li>
                        <li>Gunakan filter untuk melihat data spesifik</li>
                        <li>Status terlambat perlu ditindaklanjuti segera</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-list me-2 text-primary"></i>Data Peminjaman
                </h6>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">
                    Total: <?= number_format($total_records) ?> data
                </small>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($peminjaman_data)): ?>
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Belum ada data peminjaman</h6>
                <p class="text-muted">Coba ubah filter atau mulai dengan menambahkan peminjaman baru</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Barang</th>
                            <th>Peminjam</th>
                            <th>Tanggal Pinjam</th>
                            <th>Status</th>
                            <th>Petugas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peminjaman_data as $peminjaman): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($peminjaman['kode_peminjaman']) ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?= htmlspecialchars($peminjaman['nama_barang'] ?? 'Barang tidak ditemukan') ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($peminjaman['kode_barang'] ?? 'Kode tidak tersedia') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($peminjaman['peminjam_nama']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($peminjaman['peminjam_kelas']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold"><?= date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) ?></div>
                                        <small class="text-muted"><?= $peminjaman['jumlah_pinjam'] ?> unit</small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    $display_status = $peminjaman['status'];
                                    
                                    // Check if loan is overdue (status is 'dipinjam' but return date has passed)
                                    if ($peminjaman['status'] == 'dipinjam' && strtotime($peminjaman['tanggal_kembali_rencana']) < time()) {
                                        $display_status = 'terlambat';
                                    }
                                    
                                    switch($display_status) {
                                        case 'dipinjam':
                                            $status_class = 'bg-primary';
                                            $status_icon = 'fas fa-clock';
                                            break;
                                        case 'dikembalikan':
                                            $status_class = 'bg-success';
                                            $status_icon = 'fas fa-check';
                                            break;
                                        case 'terlambat':
                                            $status_class = 'bg-danger';
                                            $status_icon = 'fas fa-exclamation-triangle';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $status_class ?>">
                                        <i class="<?= $status_icon ?> me-1"></i>
                                        <?= ucfirst($display_status) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($peminjaman['petugas_nama'] ?? 'Petugas tidak ditemukan') ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="../peminjaman/index.php" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
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
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                            <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>">
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

<script>
// Chart.js untuk grafik peminjaman
const ctx = document.getElementById('peminjamanChart').getContext('2d');
const peminjamanChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_data['labels']) ?>,
        datasets: [{
            label: 'Jumlah Peminjaman',
            data: <?= json_encode($chart_data['data']) ?>,
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Auto-submit untuk filter tanggal
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            showLoading();
            this.form.submit();
        });
    });
    
    // Auto-submit untuk status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            showLoading();
            this.form.submit();
        });
    }
});

// Debounce function untuk search
let searchTimeout;
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        showLoading();
        document.getElementById('searchFilter').form.submit();
    }, 500); // Delay 500ms setelah user berhenti mengetik
}

// Function untuk menampilkan loading indicator
function showLoading() {
    const filterBtn = document.getElementById('filterBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    
    if (filterBtn) filterBtn.classList.add('d-none');
    if (loadingIndicator) loadingIndicator.classList.remove('d-none');
}
</script>

<?php require_once '../includes/footer.php'; ?> 