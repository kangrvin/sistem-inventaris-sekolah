<?php
/**
 * Print Peminjaman Report
 */

require_once '../../config/functions.php';
require_once 'school_info.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';

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
    
} catch(Exception $e) {
    $peminjaman_list = [];
    $summary = ['total_peminjaman' => 0, 'total_unit' => 0, 'sedang_dipinjam' => 0, 'sudah_dikembalikan' => 0, 'terlambat' => 0];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Peminjaman - <?= getSchoolInfo('name') ?></title>
    <link rel="stylesheet" href="print.css">
    <style>
        @media print {
            @page {
                margin: 1cm;
                size: A4;
            }
        }
    </style>
</head>
<body>
    <?= generatePrintHeaderHTML('Peminjaman') ?>
    
    <!-- Filter Info -->
    <div class="print-info">
        <div class="info-row">
            <span class="info-label">Periode:</span>
            <span><?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></span>
        </div>
        <?php if (!empty($status)): ?>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span><?= ucfirst($status) ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_peminjaman']) ?></div>
            <div class="label">Total Peminjaman</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_unit']) ?></div>
            <div class="label">Total Unit</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['sedang_dipinjam']) ?></div>
            <div class="label">Sedang Dipinjam</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['sudah_dikembalikan']) ?></div>
            <div class="label">Sudah Dikembalikan</div>
        </div>
    </div>
    
    <!-- Peminjaman Table -->
    <div class="card">
        <div class="card-body">
            <h5>Daftar Peminjaman</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
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
                                <td colspan="8" class="text-center">Tidak ada data peminjaman</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($peminjaman_list as $index => $peminjaman): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($peminjaman['kode_peminjaman']) ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($peminjaman['peminjam_nama']) ?></strong><br>
                                        <small><?= htmlspecialchars($peminjaman['peminjam_kelas']) ?> | NIS: <?= htmlspecialchars($peminjaman['peminjam_nis']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($peminjaman['nama_barang']) ?></strong><br>
                                        <small><?= htmlspecialchars($peminjaman['nama_kategori']) ?> | <?= $peminjaman['jumlah_pinjam'] ?> unit</small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($peminjaman['tanggal_kembali_rencana'])) ?>
                                        <?php if ($peminjaman['tanggal_kembali_aktual']): ?>
                                            <br><small class="text-success">Dikembalikan: <?= date('d/m/Y', strtotime($peminjaman['tanggal_kembali_aktual'])) ?></small>
                                        <?php endif; ?>
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
                                            case 'pending': $status_class = 'bg-warning'; break;
                                            case 'dipinjam': $status_class = 'bg-primary'; break;
                                            case 'dikembalikan': $status_class = 'bg-success'; break;
                                            case 'terlambat': $status_class = 'bg-danger'; break;
                                            case 'ditolak': $status_class = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>">
                                            <?= ucfirst($status_actual) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($peminjaman['petugas']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Summary Footer -->
    <?php if (!empty($peminjaman_list)): ?>
        <div class="summary-footer">
            <h6>Ringkasan Laporan Peminjaman:</h6>
            <ul>
                <li><strong>Periode:</strong> <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></li>
                <li><strong>Total Peminjaman:</strong> <?= number_format($summary['total_peminjaman']) ?> transaksi</li>
                <li><strong>Total Unit:</strong> <?= number_format($summary['total_unit']) ?> unit</li>
                <li><strong>Sedang Dipinjam:</strong> <?= number_format($summary['sedang_dipinjam']) ?> transaksi</li>
                <li><strong>Sudah Dikembalikan:</strong> <?= number_format($summary['sudah_dikembalikan']) ?> transaksi</li>
                <li><strong>Terlambat:</strong> <?= number_format($summary['terlambat']) ?> transaksi 
                    (<?= $summary['total_peminjaman'] > 0 ? number_format(($summary['terlambat'] / $summary['total_peminjaman']) * 100, 1) : 0 ?>%)</li>
            </ul>
        </div>
    <?php endif; ?>
    
    <?= generatePrintFooterHTML() ?>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html> 