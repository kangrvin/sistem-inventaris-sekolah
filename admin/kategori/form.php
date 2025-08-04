<?php
/**
 * Form Kategori - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Include functions
require_once '../../config/functions.php';

// Fungsi validateInput sudah ada di config/functions.php

$page_title = ($action == 'edit' ? 'Edit Kategori' : 'Tambah Kategori');
require_once '../includes/header.php';

$action = $_GET['action'] ?? 'add';
$kategori_id = $_GET['id'] ?? null;
$kategori = null;
$error = '';
$success = '';

// Jika edit, ambil data kategori
if ($action == 'edit' && $kategori_id) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id = ?");
        $stmt->execute([$kategori_id]);
        $kategori = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$kategori) {
            $error = 'Kategori tidak ditemukan!';
            echo "<script>alert('Kategori tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch(Exception $e) {
        $error = 'Terjadi kesalahan sistem!';
    }
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = validateInput($_POST['nama_kategori']);
    $deskripsi = validateInput($_POST['deskripsi']);
    
    // Validasi
    if (empty($nama_kategori)) {
        $error = 'Nama kategori harus diisi!';
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            if ($action == 'add') {
                // Cek nama kategori unik
                $stmt = $pdo->prepare("SELECT id FROM kategori WHERE nama_kategori = ?");
                $stmt->execute([$nama_kategori]);
                if ($stmt->fetch()) {
                    $error = 'Nama kategori sudah ada!';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO kategori (nama_kategori, deskripsi) 
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$nama_kategori, $deskripsi]);
                    
                    $success = 'Kategori berhasil ditambahkan!';
                    echo "<script>alert('Kategori berhasil ditambahkan!'); window.location.href='index.php';</script>";
                    exit();
                }
            } else {
                // Update kategori
                // Cek nama kategori unik (kecuali untuk kategori yang sedang diedit)
                $stmt = $pdo->prepare("SELECT id FROM kategori WHERE nama_kategori = ? AND id != ?");
                $stmt->execute([$nama_kategori, $kategori_id]);
                if ($stmt->fetch()) {
                    $error = 'Nama kategori sudah ada!';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE kategori SET 
                            nama_kategori = ?, deskripsi = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$nama_kategori, $deskripsi, $kategori_id]);
                    
                    $success = 'Kategori berhasil diupdate!';
                    echo "<script>alert('Kategori berhasil diupdate!'); window.location.href='index.php';</script>";
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
                            <?= $action == 'edit' ? 'Edit Kategori' : 'Tambah Kategori' ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <?= $action == 'edit' ? 'Edit data kategori yang sudah ada' : 'Tambahkan kategori baru ke inventaris' ?>
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
                    <i class="fas fa-info-circle me-2"></i>Informasi Kategori
                </h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="kategoriForm">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="mb-3">
                                <label for="nama_kategori" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Nama Kategori <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" 
                                       value="<?= htmlspecialchars($kategori['nama_kategori'] ?? '') ?>" 
                                       placeholder="Contoh: Elektronik, Meubel, ATK" required>
                                <div class="form-text">Masukkan nama kategori yang jelas dan mudah dipahami</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">
                                    <i class="fas fa-align-left me-1"></i>Deskripsi
                                </label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" 
                                          placeholder="Deskripsi detail kategori..."><?= htmlspecialchars($kategori['deskripsi'] ?? '') ?></textarea>
                                <div class="form-text">Opsional: Tambahkan deskripsi untuk menjelaskan kategori ini</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Info Card -->
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-info-circle me-2"></i>Informasi
                                    </h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-check-circle me-1 text-success"></i>
                                                Nama kategori harus unik
                                            </small>
                                        </li>
                                        <li class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-check-circle me-1 text-success"></i>
                                                Deskripsi bersifat opsional
                                            </small>
                                        </li>
                                        <li class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                                                Kategori tidak dapat dihapus jika masih digunakan
                                            </small>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Auto-save indicator -->
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
                                    <?= $action == 'edit' ? 'Update Kategori' : 'Simpan Kategori' ?>
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
const form = document.getElementById('kategoriForm');
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
    localStorage.setItem('kategoriFormData', JSON.stringify(Object.fromEntries(formData)));
}

function showAutoSaveIndicator() {
    autoSaveIndicator.style.display = 'block';
    setTimeout(() => {
        autoSaveIndicator.style.display = 'none';
    }, 2000);
}

// Restore form data on page load
window.addEventListener('load', function() {
    const savedData = localStorage.getItem('kategoriFormData');
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
    localStorage.removeItem('kategoriFormData');
});

// Form validation
form.addEventListener('submit', function(e) {
    const namaKategori = document.getElementById('nama_kategori').value.trim();
    
    if (namaKategori.length < 2) {
        e.preventDefault();
        alert('Nama kategori minimal 2 karakter!');
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