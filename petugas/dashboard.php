<?php
/**
 * Dashboard Petugas - Sistem Inventaris Sekolah
 */

$page_title = 'Dashboard Petugas';
require_once 'includes/header.php';

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Statistik untuk petugas
    $stats = [];
    
    // Total barang
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang");
    $stats['total_barang'] = $stmt->fetch()['total'];
    
    // Total peminjaman aktif
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'");
    $stats['peminjaman_aktif'] = $stmt->fetch()['total'];
    
    // Total peminjaman hari ini
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE DATE(tanggal_pinjam) = CURDATE()");
    $stats['peminjaman_hari_ini'] = $stmt->fetch()['total'];
    
    // Total pengembalian hari ini
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE DATE(tanggal_kembali_aktual) = CURDATE()");
    $stats['pengembalian_hari_ini'] = $stmt->fetch()['total'];
    
    // Peminjaman terlambat
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam' AND tanggal_kembali_rencana < CURDATE()");
    $stats['peminjaman_terlambat'] = $stmt->fetch()['total'];
    
    // Barang dengan stok rendah (kurang dari 5)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE jumlah_tersedia < 5");
    $stats['stok_rendah'] = $stmt->fetch()['total'];
    
    // Data untuk chart peminjaman bulanan (6 bulan terakhir)
    $chart_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE DATE_FORMAT(tanggal_pinjam, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $chart_data['labels'][] = date('M Y', strtotime("-$i months"));
        $chart_data['data'][] = $stmt->fetch()['total'];
    }
    
    // Data untuk chart kategori barang
    $stmt = $pdo->query("
        SELECT k.nama_kategori, COUNT(b.id) as jumlah 
        FROM kategori k 
        LEFT JOIN barang b ON k.id = b.kategori_id 
        GROUP BY k.id, k.nama_kategori 
        ORDER BY jumlah DESC 
        LIMIT 5
    ");
    $kategori_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Peminjaman terbaru
    $stmt = $pdo->query("
        SELECT p.*, b.nama_barang, b.kode_barang 
        FROM peminjaman p 
        JOIN barang b ON p.barang_id = b.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $peminjaman_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    echo "<script>alert('Terjadi kesalahan sistem: " . $e->getMessage() . "');</script>";
    $stats = ['total_barang' => 0, 'peminjaman_aktif' => 0, 'peminjaman_hari_ini' => 0, 'pengembalian_hari_ini' => 0, 'peminjaman_terlambat' => 0, 'stok_rendah' => 0];
    $chart_data = ['labels' => [], 'data' => []];
    $kategori_data = [];
    $peminjaman_terbaru = [];
}
?>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-user-tie me-2"></i>Selamat Datang, <?= htmlspecialchars($user_name) ?>!
                        </h2>
                        <p class="text-muted mb-0">Kelola peminjaman dan pengembalian barang inventaris sekolah</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="peminjaman/form.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Peminjaman
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_barang']) ?></div>
                <div class="stat-label">Total Barang</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['peminjaman_aktif']) ?></div>
                <div class="stat-label">Peminjaman Aktif</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['peminjaman_hari_ini']) ?></div>
                <div class="stat-label">Peminjaman Hari Ini</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['peminjaman_terlambat']) ?></div>
                <div class="stat-label">Peminjaman Terlambat</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mb-4">
    <div class="col-xl-8 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>Grafik Peminjaman Bulanan
                </h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="peminjamanChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Distribusi Kategori Barang
                </h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="kategoriChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Recent Activities -->
<div class="row">
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Aksi Cepat
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="peminjaman/form.php" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Tambah Peminjaman
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="peminjaman/" class="btn btn-success w-100">
                            <i class="fas fa-list me-2"></i>Lihat Peminjaman
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="barang/" class="btn btn-info w-100">
                            <i class="fas fa-boxes me-2"></i>Data Barang
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="laporan/" class="btn btn-warning w-100">
                            <i class="fas fa-chart-bar me-2"></i>Laporan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>Peminjaman Terbaru
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Barang</th>
                                <th>Peminjam</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($peminjaman_terbaru)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data peminjaman</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($peminjaman_terbaru as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary"><?= htmlspecialchars($item['kode_peminjaman']) ?></span>
                                        </td>
                                        <td>
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
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch($item['status']) {
                                                case 'dipinjam':
                                                    if (!empty($item['tanggal_kembali_rencana']) && strtotime($item['tanggal_kembali_rencana']) < time()) {
                                                        $status_class = 'bg-danger';
                                                        $status_text = 'Terlambat';
                                                    } else {
                                                        $status_class = 'bg-warning';
                                                        $status_text = 'Dipinjam';
                                                    }
                                                    break;
                                                case 'dikembalikan':
                                                    $status_class = 'bg-success';
                                                    $status_text = 'Dikembalikan';
                                                    break;
                                                default:
                                                    $status_class = 'bg-secondary';
                                                    $status_text = ucfirst($item['status']);
                                            }
                                            ?>
                                            <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td>
                                            <small>
                                                <?= date('d/m/Y', strtotime($item['tanggal_pinjam'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Info Cards -->
<div class="row">
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-undo fa-3x text-success mb-3"></i>
                <h4><?= number_format($stats['pengembalian_hari_ini']) ?></h4>
                <p class="text-muted mb-0">Pengembalian Hari Ini</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h4><?= number_format($stats['stok_rendah']) ?></h4>
                <p class="text-muted mb-0">Barang Stok Rendah</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-calendar-check fa-3x text-info mb-3"></i>
                <h4><?= date('d M Y') ?></h4>
                <p class="text-muted mb-0">Tanggal Hari Ini</p>
            </div>
        </div>
    </div>
</div>

<script>
// Chart Peminjaman Bulanan
const peminjamanCtx = document.getElementById('peminjamanChart').getContext('2d');
new Chart(peminjamanCtx, {
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

// Chart Kategori Barang
const kategoriCtx = document.getElementById('kategoriChart').getContext('2d');
new Chart(kategoriCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($kategori_data, 'nama_kategori')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($kategori_data, 'jumlah')) ?>,
            backgroundColor: [
                '#059669',
                '#10b981',
                '#34d399',
                '#6ee7b7',
                '#a7f3d0'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 