<?php
/**
 * Print Stok Report
 */

require_once '../../config/functions.php';
require_once 'school_info.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Get filter parameters
$kategori_id = $_GET['kategori_id'] ?? '';
$lokasi_id = $_GET['lokasi_id'] ?? '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($kategori_id)) {
        $where_conditions[] = "b.kategori_id = ?";
        $params[] = $kategori_id;
    }
    
    if (!empty($lokasi_id)) {
        $where_conditions[] = "b.lokasi_id = ?";
        $params[] = $lokasi_id;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Query for stock report
    $query = "
        SELECT 
            b.*,
            k.nama_kategori,
            l.nama_lokasi,
            u.nama_lengkap as created_by_name
        FROM barang b
        JOIN kategori k ON b.kategori_id = k.id
        JOIN lokasi l ON b.lokasi_id = l.id
        JOIN users u ON b.created_by = u.id
        $where_clause
        ORDER BY b.nama_barang ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_barang,
            SUM(jumlah_total) as total_stok,
            SUM(jumlah_tersedia) as total_tersedia,
            SUM(jumlah_total - jumlah_tersedia) as total_dipinjam
        FROM barang b
        $where_clause
    ");
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $barang_list = [];
    $summary = ['total_barang' => 0, 'total_stok' => 0, 'total_tersedia' => 0, 'total_dipinjam' => 0];
}

// Get filter names
$kategori_name = '';
$lokasi_name = '';

if (!empty($kategori_id)) {
    try {
        $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id = ?");
        $stmt->execute([$kategori_id]);
        $kategori_name = $stmt->fetchColumn();
    } catch(Exception $e) {
        $kategori_name = '';
    }
}

if (!empty($lokasi_id)) {
    try {
        $stmt = $pdo->prepare("SELECT nama_lokasi FROM lokasi WHERE id = ?");
        $stmt->execute([$lokasi_id]);
        $lokasi_name = $stmt->fetchColumn();
    } catch(Exception $e) {
        $lokasi_name = '';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Stok Barang - <?= getSchoolInfo('name') ?></title>
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
    <?= generatePrintHeaderHTML('Stok Barang') ?>
    
    <!-- Filter Info -->
    <div class="print-info">
        <?php if (!empty($kategori_name)): ?>
            <div class="info-row">
                <span class="info-label">Kategori:</span>
                <span><?= htmlspecialchars($kategori_name) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($lokasi_name)): ?>
            <div class="info-row">
                <span class="info-label">Lokasi:</span>
                <span><?= htmlspecialchars($lokasi_name) ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_barang']) ?></div>
            <div class="label">Total Jenis Barang</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_stok']) ?></div>
            <div class="label">Total Stok</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_tersedia']) ?></div>
            <div class="label">Stok Tersedia</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_dipinjam']) ?></div>
            <div class="label">Sedang Dipinjam</div>
        </div>
    </div>
    
    <!-- Stock Table -->
    <div class="card">
        <div class="card-body">
            <h5>Daftar Stok Barang</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Stok Total</th>
                            <th>Stok Tersedia</th>
                            <th>Dipinjam</th>
                            <th>Kondisi</th>
                            <th>Tahun</th>
                            <th>Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($barang_list)): ?>
                            <tr>
                                <td colspan="11" class="text-center">Tidak ada data barang</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($barang_list as $index => $barang): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($barang['kode_barang']) ?></strong></td>
                                    <td><?= htmlspecialchars($barang['nama_barang']) ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($barang['nama_kategori']) ?></span></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($barang['nama_lokasi']) ?></span></td>
                                    <td><span class="badge bg-secondary"><?= number_format($barang['jumlah_total']) ?></span></td>
                                    <td>
                                        <?php
                                        $tersedia_percent = $barang['jumlah_total'] > 0 ? ($barang['jumlah_tersedia'] / $barang['jumlah_total']) * 100 : 0;
                                        $tersedia_class = $tersedia_percent > 50 ? 'bg-success' : ($tersedia_percent > 20 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <span class="badge <?= $tersedia_class ?>">
                                            <?= number_format($barang['jumlah_tersedia']) ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-info"><?= number_format($barang['jumlah_total'] - $barang['jumlah_tersedia']) ?></span></td>
                                    <td>
                                        <?php
                                        $kondisi_class = '';
                                        switch($barang['kondisi']) {
                                            case 'baik': $kondisi_class = 'bg-success'; break;
                                            case 'rusak_ringan': $kondisi_class = 'bg-warning'; break;
                                            case 'rusak_berat': $kondisi_class = 'bg-danger'; break;
                                            default: $kondisi_class = 'bg-secondary'; break;
                                        }
                                        ?>
                                        <span class="badge <?= $kondisi_class ?>">
                                            <?= ucfirst(str_replace('_', ' ', $barang['kondisi'])) ?>
                                        </span>
                                    </td>
                                    <td><?= $barang['tahun_pengadaan'] ?? '-' ?></td>
                                    <td><?= $barang['harga_perolehan'] ? 'Rp ' . number_format($barang['harga_perolehan']) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Summary Footer -->
    <?php if (!empty($barang_list)): ?>
        <div class="summary-footer">
            <h6>Ringkasan Laporan Stok:</h6>
            <ul>
                <li><strong>Total Jenis Barang:</strong> <?= number_format($summary['total_barang']) ?> item</li>
                <li><strong>Total Stok:</strong> <?= number_format($summary['total_stok']) ?> unit</li>
                <li><strong>Stok Tersedia:</strong> <?= number_format($summary['total_tersedia']) ?> unit</li>
                <li><strong>Sedang Dipinjam:</strong> <?= number_format($summary['total_dipinjam']) ?> unit</li>
                <li><strong>Persentase Tersedia:</strong> 
                    <?= $summary['total_stok'] > 0 ? number_format(($summary['total_tersedia'] / $summary['total_stok']) * 100, 1) : 0 ?>%
                </li>
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