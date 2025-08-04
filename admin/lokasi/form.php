<?php
/**
 * Form Lokasi - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Include functions
require_once '../../config/functions.php';

// Fungsi validateInput sudah ada di config/functions.php

$page_title = ($action == 'edit' ? 'Edit Lokasi' : 'Tambah Lokasi');
require_once '../includes/header.php';

$action = $_GET['action'] ?? 'add';
$lokasi_id = $_GET['id'] ?? null;
$lokasi = null;
$error = '';
$success = '';

// Jika edit, ambil data lokasi
if ($action == 'edit' && $lokasi_id) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM lokasi WHERE id = ?");
        $stmt->execute([$lokasi_id]);
        $lokasi = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$lokasi) {
            $error = 'Lokasi tidak ditemukan!';
            echo "<script>alert('Lokasi tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch(Exception $e) {
        $error = 'Terjadi kesalahan sistem!';
    }
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lokasi = validateInput($_POST['nama_lokasi']);
    $deskripsi = validateInput($_POST['deskripsi']);
    
    // Validasi
    if (empty($nama_lokasi)) {
        $error = 'Nama lokasi harus diisi!';
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            if ($action == 'add') {
                // Cek nama lokasi unik
                $stmt = $pdo->prepare("SELECT id FROM lokasi WHERE nama_lokasi = ?");
                $stmt->execute([$nama_lokasi]);
                if ($stmt->fetch()) {
                    $error = 'Nama lokasi sudah ada!';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO lokasi (nama_lokasi, deskripsi) 
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$nama_lokasi, $deskripsi]);
                    
                    $success = 'Lokasi berhasil ditambahkan!';
                    echo "<script>alert('Lokasi berhasil ditambahkan!'); window.location.href='index.php';</script>";
                    exit();
                }
            } else {
                // Update lokasi
                // Cek nama lokasi unik (kecuali untuk lokasi yang sedang diedit)
                $stmt = $pdo->prepare("SELECT id FROM lokasi WHERE nama_lokasi = ? AND id != ?");
                $stmt->execute([$nama_lokasi, $lokasi_id]);
                if ($stmt->fetch()) {
                    $error = 'Nama lokasi sudah ada!';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE lokasi SET 
                            nama_lokasi = ?, deskripsi = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$nama_lokasi, $deskripsi, $lokasi_id]);
                    
                    $success = 'Lokasi berhasil diupdate!';
                    echo "<script>alert('Lokasi berhasil diupdate!'); window.location.href='index.php';</script>";
                    exit();
                }
            }
        } catch(Exception $e) {
            $error = 'Terjadi kesalahan sistem!';
        }
    }
}
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-<?= $action == 'edit' ? 'edit' : 'plus' ?> me-2"></i>
                            <?= $action == 'edit' ? 'Edit Lokasi' : 'Tambah Lokasi Baru' ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <?= $action == 'edit' ? 'Update informasi lokasi barang' : 'Tambahkan lokasi baru untuk barang inventaris' ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    Form <?= $action == 'edit' ? 'Edit' : 'Tambah' ?> Lokasi
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="lokasiForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nama_lokasi" class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>Nama Lokasi <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nama_lokasi" name="nama_lokasi" 
                                       placeholder="Contoh: Ruang Kelas 1A, Laboratorium Komputer" 
                                       value="<?= htmlspecialchars($lokasi['nama_lokasi'] ?? '') ?>" required>
                                <div class="form-text">
                                    Masukkan nama lokasi yang jelas dan mudah diidentifikasi
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">
                                    <i class="fas fa-info-circle me-1"></i>Deskripsi
                                </label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" 
                                          placeholder="Deskripsi lokasi (opsional)"><?= htmlspecialchars($lokasi['deskripsi'] ?? '') ?></textarea>
                                <div class="form-text">
                                    Tambahkan deskripsi untuk memberikan informasi lebih detail tentang lokasi
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info alert-sm mt-3" id="autoSaveIndicator" style="display: none;">
                                <i class="fas fa-save me-1"></i>Auto-save aktif
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $action == 'edit' ? 'Update Lokasi' : 'Simpan Lokasi' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}
</style>

<script>
// Auto-save functionality
let autoSaveTimer;
const form = document.getElementById('lokasiForm');
const autoSaveIndicator = document.getElementById('autoSaveIndicator');

form.addEventListener('input', function() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        saveFormData();
        showAutoSaveIndicator();
    }, 2000);
});

function saveFormData() {
    const formData = new FormData(form);
    localStorage.setItem('lokasiFormData', JSON.stringify(Object.fromEntries(formData)));
}

function showAutoSaveIndicator() {
    autoSaveIndicator.style.display = 'block';
    setTimeout(() => {
        autoSaveIndicator.style.display = 'none';
    }, 2000);
}

// Restore form data on page load
window.addEventListener('load', function() {
    const savedData = localStorage.getItem('lokasiFormData');
    if (savedData && !<?= $action == 'edit' ? 'true' : 'false' ?>) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const element = form.elements[key];
            if (element) {
                element.value = data[key];
            }
        });
    }
});

// Clear saved data on successful submit
form.addEventListener('submit', function() {
    localStorage.removeItem('lokasiFormData');
});

// Form validation
form.addEventListener('submit', function(e) {
    const namaLokasi = document.getElementById('nama_lokasi').value.trim();
    
    if (namaLokasi.length < 3) {
        e.preventDefault();
        alert('Nama lokasi minimal 3 karakter!');
        return false;
    }
});

// Loading state
const submitBtn = document.getElementById('submitBtn');
form.addEventListener('submit', function() {
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
    submitBtn.disabled = true;
});
</script>

<?php require_once '../includes/footer.php'; ?> 