<?php
/**
 * Print Terlambat Report
 */

require_once '../../config/functions.php';
require_once 'school_info.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query for overdue peminjaman
    $query = "
        SELECT 
            p.*,
            b.kode_barang,
            b.nama_barang,
            k.nama_kategori,
            l.nama_lokasi,
            u.nama_lengkap as petugas,
            DATEDIFF(CURDATE(), p.tanggal_kembali_rencana) as hari_terlambat
        FROM peminjaman p
        JOIN barang b ON p.barang_id = b.id
        JOIN kategori k ON b.kategori_id = k.id
        JOIN lokasi l ON b.lokasi_id = l.id
        JOIN users u ON p.created_by = u.id
        WHERE p.status = 'dipinjam' 
        AND p.tanggal_kembali_rencana < CURDATE()
        ORDER BY p.tanggal_kembali_rencana ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $terlambat_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_terlambat,
            SUM(jumlah_pinjam) as total_unit,
            AVG(DATEDIFF(CURDATE(), tanggal_kembali_rencana)) as rata_rata_terlambat,
            MAX(DATEDIFF(CURDATE(), tanggal_kembali_rencana)) as terlambat_terlama
        FROM peminjaman 
        WHERE status = 'dipinjam' 
        AND tanggal_kembali_rencana < CURDATE()
    ");
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $terlambat_list = [];
    $summary = ['total_terlambat' => 0, 'total_unit' => 0, 'rata_rata_terlambat' => 0, 'terlambat_terlama' => 0];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Peminjaman Terlambat - <?= getSchoolInfo('name') ?></title>
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
    <?= generatePrintHeaderHTML('Peminjaman Terlambat') ?>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_terlambat']) ?></div>
            <div class="label">Total Terlambat</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_unit']) ?></div>
            <div class="label">Total Unit Terlambat</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['rata_rata_terlambat'], 1) ?></div>
            <div class="label">Rata-rata Hari Terlambat</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['terlambat_terlama']) ?></div>
            <div class="label">Terlambat Terlama (Hari)</div>
        </div>
    </div>
    
    <!-- Terlambat Table -->
    <div class="card">
        <div class="card-body">
            <h5>Daftar Peminjaman Terlambat</h5>
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
                            <th>Hari Terlambat</th>
                            <th>Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($terlambat_list)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada peminjaman terlambat</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($terlambat_list as $index => $peminjaman): ?>
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
                                        <strong class="text-danger"><?= date('d/m/Y', strtotime($peminjaman['tanggal_kembali_rencana'])) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $hari_terlambat = $peminjaman['hari_terlambat'];
                                        $terlambat_class = $hari_terlambat <= 7 ? 'bg-warning' : ($hari_terlambat <= 30 ? 'bg-danger' : 'bg-dark');
                                        ?>
                                        <span class="badge <?= $terlambat_class ?>">
                                            <?= number_format($hari_terlambat) ?> hari
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
    <?php if (!empty($terlambat_list)): ?>
        <div class="summary-footer">
            <h6>Ringkasan Peminjaman Terlambat:</h6>
            <ul>
                <li><strong>Total Peminjaman Terlambat:</strong> <?= number_format($summary['total_terlambat']) ?> transaksi</li>
                <li><strong>Total Unit Terlambat:</strong> <?= number_format($summary['total_unit']) ?> unit</li>
                <li><strong>Rata-rata Keterlambatan:</strong> <?= number_format($summary['rata_rata_terlambat'], 1) ?> hari</li>
                <li><strong>Keterlambatan Terlama:</strong> <?= number_format($summary['terlambat_terlama']) ?> hari</li>
                <li><strong>Perlu Tindak Lanjut:</strong> Segera hubungi peminjam untuk pengembalian</li>
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