<?php
/**
 * Laporan Stok Barang (FIXED VERSION)
 * - Mencegah error "jenis file tidak dikenali" saat export
 * - Menangani nilai NULL agar number_format() tidak error
 * - Tetap 100% sama strukturnya dengan versi kamu
 */

$kategori_id = $_GET['kategori_id'] ?? '';
$lokasi_id   = $_GET['lokasi_id'] ?? '';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ===== Build Query dengan Filter =====
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

    // ===== Query Data Barang =====
    $query = "
        SELECT 
            b.*,
            k.nama_kategori,
            l.nama_lokasi,
            u.nama_lengkap AS created_by_name
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

    // ===== Query Ringkasan Stok =====
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_barang,
            COALESCE(SUM(jumlah_total), 0) AS total_stok,
            COALESCE(SUM(jumlah_tersedia), 0) AS total_tersedia,
            COALESCE(SUM(jumlah_total - jumlah_tersedia), 0) AS total_dipinjam
        FROM barang b
        $where_clause
    ");
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Pastikan semua key ada dan tidak null
    $summary = array_map(fn($v) => $v ?? 0, $summary);

} catch (Exception $e) {
    $barang_list = [];
    $summary = [
        'total_barang' => 0,
        'total_stok' => 0,
        'total_tersedia' => 0,
        'total_dipinjam' => 0
    ];
}
?>

<!-- ===== Summary Cards ===== -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_barang'] ?? 0) ?></h4>
                <small>Total Jenis Barang</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_stok'] ?? 0) ?></h4>
                <small>Total Stok</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_tersedia'] ?? 0) ?></h4>
                <small>Stok Tersedia</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_dipinjam'] ?? 0) ?></h4>
                <small>Sedang Dipinjam</small>
            </div>
        </div>
    </div>
</div>

<!-- ===== Tabel Stok Barang ===== -->
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
                        <td><strong><?= htmlspecialchars($barang['kode_barang']) ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($barang['foto'])): ?>
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
                        <td><span class="badge bg-primary"><?= htmlspecialchars($barang['nama_kategori']) ?></span></td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($barang['nama_lokasi']) ?></span></td>
                        <td><span class="badge bg-secondary fs-6"><?= number_format($barang['jumlah_total'] ?? 0) ?></span></td>
                        <td>
                            <?php
                            $total = $barang['jumlah_total'] ?? 0;
                            $tersedia = $barang['jumlah_tersedia'] ?? 0;
                            $tersedia_percent = $total > 0 ? ($tersedia / $total) * 100 : 0;
                            $tersedia_class = $tersedia_percent > 50 ? 'bg-success' : ($tersedia_percent > 20 ? 'bg-warning' : 'bg-danger');
                            ?>
                            <span class="badge <?= $tersedia_class ?> fs-6"><?= number_format($tersedia) ?></span>
                        </td>
                        <td><span class="badge bg-info fs-6"><?= number_format(($barang['jumlah_total'] ?? 0) - ($barang['jumlah_tersedia'] ?? 0)) ?></span></td>
                        <td>
                            <?php
                            $kondisi = $barang['kondisi'] ?? '';
                            $kondisi_class = match($kondisi) {
                                'baik' => 'bg-success',
                                'rusak_ringan' => 'bg-warning',
                                'rusak_berat' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $kondisi_class ?>">
                                <?= ucfirst(str_replace('_', ' ', $kondisi)) ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?= $barang['tahun_pengadaan'] ?? '-' ?></small></td>
                        <td>
                            <small class="text-muted">
                                <?= !empty($barang['harga_perolehan']) ? 'Rp ' . number_format($barang['harga_perolehan']) : '-' ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ===== Ringkasan Footer ===== -->
<?php if (!empty($barang_list)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Ringkasan Laporan Stok:</h6>
                <ul class="mb-0">
                    <li><strong>Total Jenis Barang:</strong> <?= number_format($summary['total_barang'] ?? 0) ?> item</li>
                    <li><strong>Total Stok:</strong> <?= number_format($summary['total_stok'] ?? 0) ?> unit</li>
                    <li><strong>Stok Tersedia:</strong> <?= number_format($summary['total_tersedia'] ?? 0) ?> unit</li>
                    <li><strong>Sedang Dipinjam:</strong> <?= number_format($summary['total_dipinjam'] ?? 0) ?> unit</li>
                    <li><strong>Persentase Tersedia:</strong> 
                        <?= ($summary['total_stok'] ?? 0) > 0 ? number_format(($summary['total_tersedia'] / $summary['total_stok']) * 100, 1) : 0 ?>%
                    </li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>
