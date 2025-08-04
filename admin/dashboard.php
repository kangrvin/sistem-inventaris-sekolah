<?php
/**
 * Dashboard Admin - Sistem Inventaris Sekolah
 */

$page_title = 'Dashboard Admin';
require_once 'includes/header.php';

// Ambil statistik dashboard
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get dashboard stats
    $stmt = $pdo->query("SELECT COUNT(*) as total_barang FROM barang");
    $total_barang = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as peminjaman_aktif FROM peminjaman WHERE status = 'dipinjam'");
    $peminjaman_aktif = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_kategori FROM kategori");
    $total_kategori = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as barang_rusak FROM barang WHERE kondisi IN ('rusak_ringan', 'rusak_berat')");
    $barang_rusak = $stmt->fetchColumn();
    
    $stats = [
        'total_barang' => $total_barang,
        'peminjaman_aktif' => $peminjaman_aktif,
        'total_kategori' => $total_kategori,
        'barang_rusak' => $barang_rusak
    ];
    
    // Ambil data untuk grafik
    
    // Data peminjaman per bulan (6 bulan terakhir)
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(tanggal_pinjam, '%Y-%m') as bulan,
            COUNT(*) as total_peminjaman
        FROM peminjaman 
        WHERE tanggal_pinjam >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(tanggal_pinjam, '%Y-%m')
        ORDER BY bulan
    ");
    $peminjaman_chart = $stmt->fetchAll();
    
    // Data barang per kategori
    $stmt = $pdo->query("
        SELECT 
            k.nama_kategori,
            COUNT(b.id) as total_barang
        FROM kategori k
        LEFT JOIN barang b ON k.id = b.kategori_id
        GROUP BY k.id, k.nama_kategori
        ORDER BY total_barang DESC
    ");
    $kategori_chart = $stmt->fetchAll();
    
    // Peminjaman terlambat
    $stmt = $pdo->query("
        SELECT 
            p.kode_peminjaman,
            b.nama_barang,
            p.peminjam_nama,
            p.tanggal_kembali_rencana,
            DATEDIFF(CURDATE(), p.tanggal_kembali_rencana) as hari_terlambat
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        WHERE p.status = 'dipinjam' 
        AND p.tanggal_kembali_rencana < CURDATE()
        ORDER BY p.tanggal_kembali_rencana ASC
        LIMIT 5
    ");
    $terlambat = $stmt->fetchAll();
    
    // Barang dengan stok menipis (kurang dari 5)
    $stmt = $pdo->query("
        SELECT 
            kode_barang,
            nama_barang,
            jumlah_tersedia,
            k.nama_kategori
        FROM barang b
        JOIN kategori k ON b.kategori_id = k.id
        WHERE jumlah_tersedia <= 5
        ORDER BY jumlah_tersedia ASC
        LIMIT 5
    ");
    $stok_menipis = $stmt->fetchAll();
    
    // Peminjaman terbaru
    $stmt = $pdo->query("
        SELECT 
            p.kode_peminjaman,
            b.nama_barang,
            p.peminjam_nama,
            p.tanggal_pinjam,
            p.tanggal_kembali_rencana,
            p.status
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $peminjaman_terbaru = $stmt->fetchAll();
    
} catch(Exception $e) {
    $peminjaman_chart = [];
    $kategori_chart = [];
    $terlambat = [];
    $stok_menipis = [];
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
                        <h2 class="mb-2">Selamat Datang, <?= $user_name ?>! 👋</h2>
                        <p class="text-muted mb-0">Kelola inventaris sekolah dengan mudah dan efisien</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex gap-2 justify-content-md-end">
                            <a href="barang/?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Tambah Barang
                            </a>
                            <a href="peminjaman/" class="btn btn-success">
                                <i class="fas fa-handshake me-2"></i>Peminjaman
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistik Cards -->
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
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_kategori']) ?></div>
                <div class="stat-label">Kategori Barang</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['barang_rusak']) ?></div>
                <div class="stat-label">Barang Rusak</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-xl-8 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Peminjaman 6 Bulan Terakhir</h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="peminjamanChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Barang per Kategori</h5>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 300px;">
                    <canvas id="kategoriChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts & Recent Activity -->
<div class="row">
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Peminjaman Terlambat</h5>
            </div>
            <div class="card-body">
                <?php if (empty($terlambat)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted mb-0">Tidak ada peminjaman terlambat</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Barang</th>
                                    <th>Peminjam</th>
                                    <th>Terlambat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($terlambat as $item): ?>
                                    <tr>
                                        <td><span class="badge bg-warning"><?= $item['kode_peminjaman'] ?></span></td>
                                        <td><?= $item['nama_barang'] ?></td>
                                        <td><?= $item['peminjam_nama'] ?></td>
                                        <td><span class="badge bg-danger"><?= $item['hari_terlambat'] ?> hari</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Stok Menipis</h5>
            </div>
            <div class="card-body">
                <?php if (empty($stok_menipis)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted mb-0">Tidak ada barang dengan stok menipis</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Barang</th>
                                    <th>Kategori</th>
                                    <th>Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stok_menipis as $item): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= $item['kode_barang'] ?></span></td>
                                        <td><?= $item['nama_barang'] ?></td>
                                        <td><?= $item['nama_kategori'] ?></td>
                                        <td><span class="badge bg-danger"><?= $item['jumlah_tersedia'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Peminjaman Terbaru</h5>
            </div>
            <div class="card-body">
                <?php if (empty($peminjaman_terbaru)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Belum ada peminjaman</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Barang</th>
                                    <th>Peminjam</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($peminjaman_terbaru as $item): ?>
                                    <tr>
                                        <td><span class="badge bg-primary"><?= $item['kode_peminjaman'] ?></span></td>
                                        <td><?= $item['nama_barang'] ?></td>
                                        <td><?= $item['peminjam_nama'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($item['tanggal_pinjam'])) ?></td>
                                        <td>
                                            <?php
                                            $status_actual = $item['status'];
                                            if ($item['status'] == 'dipinjam' && 
                                                !empty($item['tanggal_kembali_rencana']) &&
                                                strtotime($item['tanggal_kembali_rencana']) < time()) {
                                                $status_actual = 'terlambat';
                                            }
                                            
                                            switch($status_actual) {
                                                case 'dipinjam': 
                                                    echo '<span class="badge bg-primary">Dipinjam</span>';
                                                    break;
                                                case 'dikembalikan': 
                                                    echo '<span class="badge bg-success">Dikembalikan</span>';
                                                    break;
                                                case 'terlambat': 
                                                    echo '<span class="badge bg-danger">Terlambat</span>';
                                                    break;
                                                default: 
                                                    echo '<span class="badge bg-secondary">' . ucfirst($item['status']) . '</span>';
                                                    break;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="barang/?action=add" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>Tambah Barang
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="peminjaman/" class="btn btn-success w-100">
                            <i class="fas fa-handshake me-2"></i>Peminjaman
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="laporan/" class="btn btn-info w-100">
                            <i class="fas fa-chart-bar me-2"></i>Laporan
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="user/" class="btn btn-warning w-100">
                            <i class="fas fa-user-plus me-2"></i>Kelola User
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart untuk peminjaman per bulan
const peminjamanCtx = document.getElementById('peminjamanChart').getContext('2d');
const peminjamanChart = new Chart(peminjamanCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($peminjaman_chart, 'bulan')) ?>,
        datasets: [{
            label: 'Jumlah Peminjaman',
            data: <?= json_encode(array_column($peminjaman_chart, 'total_peminjaman')) ?>,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#4f46e5',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6
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
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Chart untuk barang per kategori
const kategoriCtx = document.getElementById('kategoriChart').getContext('2d');
const kategoriChart = new Chart(kategoriCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($kategori_chart, 'nama_kategori')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($kategori_chart, 'total_barang')) ?>,
            backgroundColor: [
                '#4f46e5',
                '#7c3aed',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6'
            ],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 