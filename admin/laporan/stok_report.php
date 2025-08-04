<?php
/**
 * Laporan Stok Barang
 */

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
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_barang']) ?></h4>
                <small>Total Jenis Barang</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_stok']) ?></h4>
                <small>Total Stok</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_tersedia']) ?></h4>
                <small>Stok Tersedia</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_dipinjam']) ?></h4>
                <small>Sedang Dipinjam</small>
            </div>
        </div>
    </div>
</div>

<!-- Stock Table -->
<div class="table-responsive">
    <table class="table table-hover" id="stokTable">
        <thead class="table-light">
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
                    <td colspan="11" class="text-center py-4">
                        <i class="fas fa-box fa-3x text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Tidak ada data barang</h5>
                        <p class="text-muted">Tidak ditemukan barang yang sesuai dengan filter</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($barang_list as $index => $barang): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($barang['kode_barang']) ?></strong>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($barang['foto']): ?>
                                    <img src="../../uploads/<?= htmlspecialchars($barang['foto']) ?>" 
                                         class="avatar-sm rounded me-3" alt="Foto Barang">
                                <?php else: ?>
                                    <div class="avatar-sm bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-box text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($barang['nama_barang']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($barang['deskripsi'] ?? '') ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?= htmlspecialchars($barang['nama_kategori']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= htmlspecialchars($barang['nama_lokasi']) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-secondary fs-6"><?= number_format($barang['jumlah_total']) ?></span>
                        </td>
                        <td>
                            <?php
                            $tersedia_percent = $barang['jumlah_total'] > 0 ? ($barang['jumlah_tersedia'] / $barang['jumlah_total']) * 100 : 0;
                            $tersedia_class = $tersedia_percent > 50 ? 'bg-success' : ($tersedia_percent > 20 ? 'bg-warning' : 'bg-danger');
                            ?>
                            <span class="badge <?= $tersedia_class ?> fs-6">
                                <?= number_format($barang['jumlah_tersedia']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info fs-6"><?= number_format($barang['jumlah_total'] - $barang['jumlah_tersedia']) ?></span>
                        </td>
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
                        <td>
                            <small class="text-muted"><?= $barang['tahun_pengadaan'] ?? '-' ?></small>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= $barang['harga_perolehan'] ? 'Rp ' . number_format($barang['harga_perolehan']) : '-' ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Summary Footer -->
<?php if (!empty($barang_list)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Ringkasan Laporan Stok:</h6>
                <ul class="mb-0">
                    <li><strong>Total Jenis Barang:</strong> <?= number_format($summary['total_barang']) ?> item</li>
                    <li><strong>Total Stok:</strong> <?= number_format($summary['total_stok']) ?> unit</li>
                    <li><strong>Stok Tersedia:</strong> <?= number_format($summary['total_tersedia']) ?> unit</li>
                    <li><strong>Sedang Dipinjam:</strong> <?= number_format($summary['total_dipinjam']) ?> unit</li>
                    <li><strong>Persentase Tersedia:</strong> 
                        <?= $summary['total_stok'] > 0 ? number_format(($summary['total_tersedia'] / $summary['total_stok']) * 100, 1) : 0 ?>%
                    </li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?> 