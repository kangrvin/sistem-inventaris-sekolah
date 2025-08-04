<?php
/**
 * Edit Pengaturan Sistem
 */

$page_title = 'Edit Pengaturan Sistem';
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
                        <h2 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Pengaturan Sistem</h2>
                        <p class="text-muted mb-0">Ubah pengaturan sistem inventaris sekolah</p>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Settings Form -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="save.php" method="post">
                    <!-- Informasi Sekolah -->
                    <h5 class="mb-3">Informasi Sekolah</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nama Sekolah</label>
                                <input type="text" class="form-control" name="nama_sekolah" 
                                       value="<?= htmlspecialchars($settings['nama_sekolah'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat Sekolah</label>
                                <textarea class="form-control" name="alamat_sekolah" rows="3" required><?= htmlspecialchars($settings['alamat_sekolah'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Telepon Sekolah</label>
                                <input type="text" class="form-control" name="telepon_sekolah" 
                                       value="<?= htmlspecialchars($settings['telepon_sekolah'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Sekolah</label>
                                <input type="email" class="form-control" name="email_sekolah" 
                                       value="<?= htmlspecialchars($settings['email_sekolah'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Pengaturan Peminjaman -->
                    <h5 class="mb-3">Pengaturan Peminjaman</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maksimal Hari Peminjaman</label>
                                <input type="number" class="form-control" name="maksimal_peminjaman" 
                                       value="<?= htmlspecialchars($settings['maksimal_peminjaman'] ?? '7') ?>" required>
                                <small class="text-muted">Jumlah hari maksimal untuk peminjaman barang</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Denda Terlambat (Rp)</label>
                                <input type="number" class="form-control" name="denda_terlambat" 
                                       value="<?= htmlspecialchars($settings['denda_terlambat'] ?? '1000') ?>" required>
                                <small class="text-muted">Denda per hari keterlambatan dalam rupiah</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="index.php" class="btn btn-secondary me-2">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>