<?php
/**
 * Laporan Peminjaman
 */

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';

// Debug: Check if we have any peminjaman data
try {
    $debug_pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $debug_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check total peminjaman
    $debug_stmt = $debug_pdo->prepare("SELECT COUNT(*) as total FROM peminjaman");
    $debug_stmt->execute();
    $total_peminjaman = $debug_stmt->fetchColumn();
    
    // Check peminjaman in current period
    $debug_stmt = $debug_pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ?");
    $debug_stmt->execute([$start_date, $end_date]);
    $total_in_period = $debug_stmt->fetchColumn();
    
    // Check all peminjaman dates
    $debug_stmt = $debug_pdo->prepare("SELECT tanggal_pinjam, status FROM peminjaman ORDER BY tanggal_pinjam DESC");
    $debug_stmt->execute();
    $all_dates = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $total_peminjaman = 0;
    $total_in_period = 0;
    $all_dates = [];
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build query with filters
    $where_conditions = ["p.tanggal_pinjam BETWEEN ? AND ?"];
    $params = [$start_date, $end_date];
    
    if (!empty($status)) {
        if ($status == 'terlambat') {
            $where_conditions[] = "p.status = 'dipinjam' AND p.tanggal_kembali_rencana < CURDATE()";
        } else {
            $where_conditions[] = "p.status = ?";
            $params[] = $status;
        }
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Query for peminjaman report
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
        ORDER BY p.tanggal_pinjam DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $peminjaman_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_peminjaman,
            SUM(jumlah_pinjam) as total_unit,
            COUNT(CASE WHEN status = 'dipinjam' THEN 1 END) as sedang_dipinjam,
            COUNT(CASE WHEN status = 'dikembalikan' THEN 1 END) as sudah_dikembalikan,
            COUNT(CASE WHEN status = 'dipinjam' AND tanggal_kembali_rencana < CURDATE() THEN 1 END) as terlambat
        FROM peminjaman p
        $where_clause
    ");
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get top borrowed items
    $stmt = $pdo->prepare("
        SELECT 
            b.nama_barang,
            k.nama_kategori,
            COUNT(p.id) as total_peminjaman,
            SUM(p.jumlah_pinjam) as total_unit
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        JOIN kategori k ON b.kategori_id = k.id
        $where_clause
        GROUP BY b.id, b.nama_barang, k.nama_kategori
        ORDER BY total_peminjaman DESC
        LIMIT 5
    ");
    $stmt->execute($params);
    $top_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $peminjaman_list = [];
    $summary = ['total_peminjaman' => 0, 'total_unit' => 0, 'sedang_dipinjam' => 0, 'sudah_dikembalikan' => 0, 'terlambat' => 0];
    $top_items = [];
}
?>

<!-- Debug Info -->
<?php if ($total_peminjaman > 0): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle me-2"></i>Debug Info:</h6>
            <ul class="mb-0">
                <li><strong>Total Peminjaman di Database:</strong> <?= $total_peminjaman ?></li>
                <li><strong>Peminjaman dalam Periode (<?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>):</strong> <?= $total_in_period ?></li>
                <li><strong>Semua Tanggal Peminjaman:</strong>
                    <?php foreach ($all_dates as $date): ?>
                        <?= date('d/m/Y', strtotime($date['tanggal_pinjam'])) ?> (<?= $date['status'] ?>)
                        <?= $date !== end($all_dates) ? ', ' : '' ?>
                    <?php endforeach; ?>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_peminjaman']) ?></h4>
                <small>Total Peminjaman</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_unit'] ?? 0) ?></h4>
                <small>Total Unit</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['sedang_dipinjam'] ?? 0) ?></h4>
                <small>Sedang Dipinjam</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['sudah_dikembalikan'] ?? 0) ?></h4>
                <small>Sudah Dikembalikan</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['terlambat'] ?? 0) ?></h4>
                <small>Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= ($summary['total_peminjaman'] ?? 0) > 0 ? number_format((($summary['terlambat'] ?? 0) / ($summary['total_peminjaman'] ?? 1)) * 100, 1) : 0 ?>%</h4>
                <small>% Terlambat</small>
            </div>
        </div>
    </div>
</div>

<!-- Top Items Chart -->
<?php if (!empty($top_items)): ?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Barang Terpopuler</h6>
            </div>
            <div class="card-body">
                <?php foreach ($top_items as $index => $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?= htmlspecialchars($item['nama_barang']) ?></strong>
                            <br><small class="text-muted"><?= htmlspecialchars($item['nama_kategori']) ?></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary"><?= number_format($item['total_peminjaman'] ?? 0) ?>x dipinjam</span>
                            <br><small class="text-muted"><?= number_format($item['total_unit'] ?? 0) ?> unit</small>
                        </div>
                    </div>
                    <?php if ($index < count($top_items) - 1): ?>
                        <hr class="my-2">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Statistik Periode</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-primary"><?= number_format($summary['total_peminjaman'] ?? 0) ?></h4>
                            <small class="text-muted">Total Peminjaman</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success"><?= number_format($summary['total_unit'] ?? 0) ?></h4>
                            <small class="text-muted">Total Unit</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-info"><?= number_format($summary['sedang_dipinjam'] ?? 0) ?></h4>
                            <small class="text-muted">Sedang Dipinjam</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-warning"><?= number_format($summary['sudah_dikembalikan'] ?? 0) ?></h4>
                            <small class="text-muted">Sudah Dikembalikan</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Peminjaman Table -->
<div class="table-responsive">
    <table class="table table-hover" id="peminjamanTable">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Kode Peminjaman</th>
                <th>Peminjam</th>
                <th>Barang</th>
                <th>Tanggal Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($peminjaman_list)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-handshake fa-3x text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Tidak ada data peminjaman</h5>
                        <p class="text-muted">Tidak ditemukan peminjaman yang sesuai dengan filter</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($peminjaman_list as $index => $peminjaman): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($peminjaman['kode_peminjaman']) ?></strong>
                        </td>
                        <td>
                            <div>
                                <strong><?= htmlspecialchars($peminjaman['peminjam_nama']) ?></strong>
                                <br><small class="text-muted">
                                    <?= htmlspecialchars($peminjaman['peminjam_kelas']) ?> | 
                                    NIS: <?= htmlspecialchars($peminjaman['peminjam_nis']) ?>
                                </small>
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
                                <strong><?= date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) ?></strong>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong><?= date('d/m/Y', strtotime($peminjaman['tanggal_kembali_rencana'])) ?></strong>
                                <?php if ($peminjaman['tanggal_kembali_aktual']): ?>
                                    <br><small class="text-success">
                                        Dikembalikan: <?= date('d/m/Y', strtotime($peminjaman['tanggal_kembali_aktual'])) ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            $status_actual = $peminjaman['status'];
                            if ($peminjaman['status'] == 'dipinjam' && 
                                strtotime($peminjaman['tanggal_kembali_rencana']) < time()) {
                                $status_actual = 'terlambat';
                            }
                            
                            $status_class = '';
                            switch($status_actual) {
                                case 'dipinjam': $status_class = 'bg-primary'; break;
                                case 'dikembalikan': $status_class = 'bg-success'; break;
                                case 'terlambat': $status_class = 'bg-danger'; break;
                                default: $status_class = 'bg-secondary'; break;
                            }
                            ?>
                            <span class="badge <?= $status_class ?>">
                                <?= ucfirst($status_actual) ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted"><?= htmlspecialchars($peminjaman['petugas']) ?></small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Summary Footer -->
<?php if (!empty($peminjaman_list)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Ringkasan Laporan Peminjaman:</h6>
                <ul class="mb-0">
                    <li><strong>Periode:</strong> <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></li>
                    <li><strong>Total Peminjaman:</strong> <?= number_format($summary['total_peminjaman'] ?? 0) ?> transaksi</li>
                    <li><strong>Total Unit:</strong> <?= number_format($summary['total_unit'] ?? 0) ?> unit</li>
                    <li><strong>Sedang Dipinjam:</strong> <?= number_format($summary['sedang_dipinjam'] ?? 0) ?> transaksi</li>
                    <li><strong>Sudah Dikembalikan:</strong> <?= number_format($summary['sudah_dikembalikan'] ?? 0) ?> transaksi</li>
                    <li><strong>Terlambat:</strong> <?= number_format($summary['terlambat'] ?? 0) ?> transaksi 
                        (<?= ($summary['total_peminjaman'] ?? 0) > 0 ? number_format((($summary['terlambat'] ?? 0) / ($summary['total_peminjaman'] ?? 1)) * 100, 1) : 0 ?>%)</li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?> 