<?php
/**
 * Pengaturan Sistem
 */

$page_title = 'Pengaturan Sistem';
require_once '../../config/functions.php';
require_once '../includes/header.php';

// Get current settings
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT * FROM pengaturan");
    $settings = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['nama_pengaturan']] = $row['nilai'];
    }
} catch(Exception $e) {
    $settings = [];
}
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0"><i class="fas fa-cogs me-2"></i>Pengaturan Sistem</h2>
                        <p class="text-muted mb-0">Kelola pengaturan sistem inventaris sekolah</p>
                    </div>
                    <div>
                        <a href="edit.php" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Pengaturan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Settings Display -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Informasi Sekolah -->
                <div class="border rounded p-4 mb-4 bg-light">
                    <h5 class="mb-4"><i class="fas fa-school me-2"></i>Informasi Sekolah</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="text-muted mb-2">Nama Sekolah</label>
                                <h6 class="mb-0"><?= htmlspecialchars($settings['nama_sekolah'] ?? '-') ?></h6>
                            </div>
                            <div class="mb-4">
                                <label class="text-muted mb-2">Alamat Sekolah</label>
                                <h6 class="mb-0"><?= nl2br(htmlspecialchars($settings['alamat_sekolah'] ?? '-')) ?></h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="text-muted mb-2">Telepon Sekolah</label>
                                <h6 class="mb-0">
                                    <i class="fas fa-phone me-2 text-primary"></i>
                                    <?= htmlspecialchars($settings['telepon_sekolah'] ?? '-') ?>
                                </h6>
                            </div>
                            <div class="mb-4">
                                <label class="text-muted mb-2">Email Sekolah</label>
                                <h6 class="mb-0">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    <?= htmlspecialchars($settings['email_sekolah'] ?? '-') ?>
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pengaturan Peminjaman -->
                <div class="border rounded p-4 bg-light">
                    <h5 class="mb-4"><i class="fas fa-clock me-2"></i>Pengaturan Peminjaman</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="text-muted mb-2">Maksimal Hari Peminjaman</label>
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                    <?= htmlspecialchars($settings['maksimal_peminjaman'] ?? '7') ?> hari
                                </h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="text-muted mb-2">Denda Terlambat</label>
                                <h6 class="mb-0">
                                    <i class="fas fa-money-bill me-2 text-primary"></i>
                                    Rp <?= number_format($settings['denda_terlambat'] ?? 1000) ?> per hari
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>