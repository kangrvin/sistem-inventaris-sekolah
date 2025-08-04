<?php
/**
 * Laporan Peminjaman Terlambat
 */

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query for overdue peminjaman
    $query = "
        SELECT 
            p.*,
            b.kode_barang,
            b.nama_barang,
            b.foto,
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

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_terlambat']) ?></h4>
                <small>Total Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_unit']) ?></h4>
                <small>Total Unit Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['rata_rata_terlambat'], 1) ?></h4>
                <small>Rata-rata Hari Terlambat</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['terlambat_terlama']) ?></h4>
                <small>Terlambat Terlama (Hari)</small>
            </div>
        </div>
    </div>
</div>

<!-- Terlambat Table -->
<div class="table-responsive">
    <table class="table table-hover" id="terlambatTable">
        <thead class="table-light">
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
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                        <h5 class="text-success">Tidak ada peminjaman terlambat</h5>
                        <p class="text-muted">Semua peminjaman sudah dikembalikan tepat waktu</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($terlambat_list as $index => $peminjaman): ?>
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
                                <strong class="text-danger"><?= date('d/m/Y', strtotime($peminjaman['tanggal_kembali_rencana'])) ?></strong>
                            </div>
                        </td>
                        <td>
                            <?php
                            $hari_terlambat = $peminjaman['hari_terlambat'];
                            $terlambat_class = $hari_terlambat <= 7 ? 'bg-warning' : ($hari_terlambat <= 30 ? 'bg-danger' : 'bg-dark');
                            ?>
                            <span class="badge <?= $terlambat_class ?> fs-6">
                                <?= number_format($hari_terlambat) ?> hari
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
<?php if (!empty($terlambat_list)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Ringkasan Peminjaman Terlambat:</h6>
                <ul class="mb-0">
                    <li><strong>Total Peminjaman Terlambat:</strong> <?= number_format($summary['total_terlambat']) ?> transaksi</li>
                    <li><strong>Total Unit Terlambat:</strong> <?= number_format($summary['total_unit']) ?> unit</li>
                    <li><strong>Rata-rata Keterlambatan:</strong> <?= number_format($summary['rata_rata_terlambat'], 1) ?> hari</li>
                    <li><strong>Keterlambatan Terlama:</strong> <?= number_format($summary['terlambat_terlama']) ?> hari</li>
                    <li><strong>Perlu Tindak Lanjut:</strong> Segera hubungi peminjam untuk pengembalian</li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?> 