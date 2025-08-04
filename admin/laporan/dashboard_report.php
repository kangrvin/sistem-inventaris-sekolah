<?php
/**
 * Dashboard Report - Overview
 */

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get monthly peminjaman data for chart
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(tanggal_pinjam, '%Y-%m') as bulan,
            COUNT(*) as total_peminjaman,
            SUM(jumlah_pinjam) as total_unit
        FROM peminjaman 
        WHERE tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(tanggal_pinjam, '%Y-%m')
        ORDER BY bulan ASC
    ");
    $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get kategori distribution
    $stmt = $pdo->query("
        SELECT 
            k.nama_kategori,
            COUNT(b.id) as total_barang,
            SUM(b.jumlah_total) as total_stok
        FROM kategori k
        LEFT JOIN barang b ON k.id = b.kategori_id
        GROUP BY k.id, k.nama_kategori
        ORDER BY total_barang DESC
    ");
    $kategori_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent peminjaman
    $stmt = $pdo->query("
        SELECT 
            p.*,
            b.nama_barang,
            u.nama_lengkap as petugas
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        JOIN users u ON p.created_by = u.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $recent_peminjaman = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get low stock items
    $stmt = $pdo->query("
        SELECT 
            b.*,
            k.nama_kategori,
            l.nama_lokasi
        FROM barang b
        JOIN kategori k ON b.kategori_id = k.id
        JOIN lokasi l ON b.lokasi_id = l.id
        WHERE b.jumlah_tersedia <= 5
        ORDER BY b.jumlah_tersedia ASC
        LIMIT 10
    ");
    $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $monthly_data = [];
    $kategori_data = [];
    $recent_peminjaman = [];
    $low_stock_items = [];
}
?>

<!-- Overview Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-primary">
            <h6><i class="fas fa-chart-line me-2"></i>Dashboard Laporan Sistem Inventaris Sekolah</h6>
            <p class="mb-0">Ringkasan statistik dan data penting sistem inventaris sekolah</p>
        </div>
    </div>
</div>

<!-- Monthly Chart -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Trend Peminjaman 6 Bulan Terakhir</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($monthly_data)): ?>
                    <canvas id="monthlyChart" height="200"></canvas>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Tidak ada data untuk ditampilkan</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-pie-chart me-2"></i>Distribusi Kategori</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($kategori_data)): ?>
                    <canvas id="kategoriChart" height="200"></canvas>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-pie-chart fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Tidak ada data kategori</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities & Low Stock -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Peminjaman Terbaru</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_peminjaman)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_peminjaman as $peminjaman): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($peminjaman['peminjam_nama']) ?></h6>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($peminjaman['nama_barang']) ?> | 
                                            <?= $peminjaman['jumlah_pinjam'] ?> unit
                                        </small>
                                        <br><small class="text-muted">
                                            Petugas: <?= htmlspecialchars($peminjaman['petugas']) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) ?>
                                        </small>
                                        <br>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        // Hitung status terlambat otomatis
                                        $status_actual = $peminjaman['status'];
                                        if ($peminjaman['status'] == 'dipinjam' && 
                                            strtotime($peminjaman['tanggal_kembali_rencana']) < time()) {
                                            $status_actual = 'terlambat';
                                        }
                                        
                                        switch($status_actual) {
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
                                            default: 
                                                $status_class = 'bg-secondary';
                                                $status_text = ucfirst($peminjaman['status']);
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Tidak ada peminjaman terbaru</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Stok Menipis</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($low_stock_items)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($low_stock_items as $item): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($item['nama_barang']) ?></h6>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($item['nama_kategori']) ?> | 
                                            <?= htmlspecialchars($item['nama_lokasi']) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger fs-6">
                                            <?= number_format($item['jumlah_tersedia']) ?> tersedia
                                        </span>
                                        <br><small class="text-muted">
                                            dari <?= number_format($item['jumlah_total']) ?> total
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h6 class="text-success">Stok semua barang mencukupi</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Statistik Cepat</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="border-end">
                            <h4 class="text-primary"><?= number_format(count($kategori_data)) ?></h4>
                            <small class="text-muted">Kategori Barang</small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border-end">
                            <h4 class="text-success"><?= number_format(array_sum(array_column($kategori_data, 'total_barang'))) ?></h4>
                            <small class="text-muted">Jenis Barang</small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="border-end">
                            <h4 class="text-info"><?= number_format(array_sum(array_column($kategori_data, 'total_stok'))) ?></h4>
                            <small class="text-muted">Total Stok</small>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div>
                            <h4 class="text-warning"><?= number_format(count($recent_peminjaman)) ?></h4>
                            <small class="text-muted">Peminjaman Bulan Ini</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Monthly Chart
<?php if (!empty($monthly_data)): ?>
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly_data, 'bulan')) ?>,
        datasets: [{
            label: 'Total Peminjaman',
            data: <?= json_encode(array_column($monthly_data, 'total_peminjaman')) ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }, {
            label: 'Total Unit',
            data: <?= json_encode(array_column($monthly_data, 'total_unit')) ?>,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
<?php endif; ?>

// Kategori Chart
<?php if (!empty($kategori_data)): ?>
const kategoriCtx = document.getElementById('kategoriChart').getContext('2d');
new Chart(kategoriCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($kategori_data, 'nama_kategori')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($kategori_data, 'total_barang')) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ]
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
<?php endif; ?>
</script> 