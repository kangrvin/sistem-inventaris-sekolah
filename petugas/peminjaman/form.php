<?php
/**
 * Form Peminjaman - Petugas Panel
 */

$page_title = isset($_GET['id']) ? 'Edit Peminjaman' : 'Tambah Peminjaman';
require_once '../includes/header.php';

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];

// Ambil setting maksimal peminjaman di awal
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT nilai FROM pengaturan WHERE nama_pengaturan = 'maksimal_peminjaman'");
    $maksimal_peminjaman = (int)($stmt->fetch()['nilai'] ?? 7);
    
} catch(Exception $e) {
    $maksimal_peminjaman = 7; // Default value jika error
}

// Handle edit mode
$edit_mode = false;
$peminjaman_data = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $peminjaman_id = $_GET['id'];
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT p.*, b.nama_barang, b.kode_barang, b.jumlah_tersedia
            FROM peminjaman p 
            JOIN barang b ON p.barang_id = b.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$peminjaman_id]);
        $peminjaman_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$peminjaman_data) {
            echo "<script>alert('Data peminjaman tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
        
    } catch(Exception $e) {
        echo "<script>alert('Terjadi kesalahan sistem!'); window.location.href='index.php';</script>";
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $barang_id = $_POST['barang_id'];
    $jumlah_pinjam = (int)$_POST['jumlah_pinjam'];
    $peminjam_nama = trim($_POST['peminjam_nama']);
    $peminjam_kelas = trim($_POST['peminjam_kelas']);
    $peminjam_nis = trim($_POST['peminjam_nis']);
    $peminjam_kontak = trim($_POST['peminjam_kontak']);
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali_rencana = $_POST['tanggal_kembali_rencana'];
    $keterangan = trim($_POST['keterangan']);
    
    // Validasi input
    $errors = [];
    
    if (empty($barang_id)) {
        $errors[] = "Barang harus dipilih";
    }
    
    if (empty($jumlah_pinjam) || $jumlah_pinjam <= 0) {
        $errors[] = "Jumlah pinjam harus lebih dari 0";
    }
    
    if (empty($peminjam_nama)) {
        $errors[] = "Nama peminjam harus diisi";
    }
    
    if (empty($peminjam_kontak)) {
        $errors[] = "Kontak peminjam harus diisi";
    }
    
    if (empty($tanggal_pinjam)) {
        $errors[] = "Tanggal pinjam harus diisi";
    }
    
    if (empty($tanggal_kembali_rencana)) {
        $errors[] = "Tanggal kembali rencana harus diisi";
    }
    
    if (!empty($tanggal_pinjam) && !empty($tanggal_kembali_rencana)) {
        if (strtotime($tanggal_kembali_rencana) <= strtotime($tanggal_pinjam)) {
            $errors[] = "Tanggal kembali rencana harus setelah tanggal pinjam";
        } else {
            // Cek maksimal hari peminjaman
            $tanggal_pinjam_timestamp = strtotime($tanggal_pinjam);
            $tanggal_kembali_timestamp = strtotime($tanggal_kembali_rencana);
            $selisih_hari = floor(($tanggal_kembali_timestamp - $tanggal_pinjam_timestamp) / (60 * 60 * 24));
            
            if ($selisih_hari > $maksimal_peminjaman) {
                $errors[] = "Maksimal peminjaman adalah $maksimal_peminjaman hari. Anda memilih $selisih_hari hari.";
            }
        }
    }
    
    // Cek stok barang jika bukan edit mode
    if (!$edit_mode) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT jumlah_tersedia FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);
            $barang = $stmt->fetch();
            
            if ($barang && $jumlah_pinjam > $barang['jumlah_tersedia']) {
                $errors[] = "Stok barang tidak mencukupi. Tersedia: " . $barang['jumlah_tersedia'];
            }
        } catch(Exception $e) {
            $errors[] = "Gagal mengecek stok barang";
        }
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            if ($edit_mode) {
                // Update peminjaman
                $stmt = $pdo->prepare("
                    UPDATE peminjaman 
                    SET barang_id = ?, jumlah_pinjam = ?, peminjam_nama = ?, peminjam_kelas = ?, 
                        peminjam_nis = ?, peminjam_kontak = ?, tanggal_pinjam = ?, 
                        tanggal_kembali_rencana = ?, keterangan = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $barang_id, $jumlah_pinjam, $peminjam_nama, $peminjam_kelas,
                    $peminjam_nis, $peminjam_kontak, $tanggal_pinjam,
                    $tanggal_kembali_rencana, $keterangan, $peminjaman_id
                ]);
                
                $success = "Peminjaman berhasil diperbarui!";
            } else {
                // Generate kode peminjaman
                $tahun = date('Y');
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE YEAR(created_at) = ?");
                $stmt->execute([$tahun]);
                $count = $stmt->fetch()['total'] + 1;
                $kode_peminjaman = "PJM-$tahun-" . str_pad($count, 3, '0', STR_PAD_LEFT);
                
                // Insert peminjaman baru
                $stmt = $pdo->prepare("
                    INSERT INTO peminjaman (
                        kode_peminjaman, barang_id, jumlah_pinjam, peminjam_nama, peminjam_kelas,
                        peminjam_nis, peminjam_kontak, tanggal_pinjam, tanggal_kembali_rencana,
                        keterangan, status, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'dipinjam', ?)
                ");
                $stmt->execute([
                    $kode_peminjaman, $barang_id, $jumlah_pinjam, $peminjam_nama, $peminjam_kelas,
                    $peminjam_nis, $peminjam_kontak, $tanggal_pinjam, $tanggal_kembali_rencana,
                    $keterangan, $user_id
                ]);
                
                $success = "Peminjaman berhasil ditambahkan!";
            }
            
            // Redirect setelah 2 detik
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 2000);
            </script>";
            
        } catch(Exception $e) {
            $errors[] = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}

// Ambil data barang untuk dropdown
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("
        SELECT b.id, b.nama_barang, b.kode_barang, b.jumlah_tersedia, k.nama_kategori, l.nama_lokasi
        FROM barang b 
        JOIN kategori k ON b.kategori_id = k.id 
        JOIN lokasi l ON b.lokasi_id = l.id 
        WHERE b.jumlah_tersedia > 0 
        ORDER BY b.nama_barang
    ");
    $barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $errors[] = "Gagal memuat data barang: " . $e->getMessage();
    $barang_list = [];
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
                            <i class="fas fa-<?= $edit_mode ? 'edit' : 'plus' ?> me-2"></i><?= $page_title ?>
                        </h2>
                        <p class="text-muted mb-0"><?= $edit_mode ? 'Edit data peminjaman' : 'Tambah peminjaman baru' ?></p>
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

<!-- Alert Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($success) ?>
        <p class="mb-0 mt-2">Anda akan dialihkan ke halaman list dalam beberapa detik...</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Form Peminjaman
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="peminjamanForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="barang_id" class="form-label">Barang <span class="text-danger">*</span></label>
                            <select class="form-select" id="barang_id" name="barang_id" required <?= $edit_mode ? 'disabled' : '' ?>>
                                <option value="">Pilih Barang</option>
                                <?php foreach ($barang_list as $barang): ?>
                                    <option value="<?= $barang['id'] ?>" 
                                            data-stok="<?= $barang['jumlah_tersedia'] ?>"
                                            <?= ($edit_mode && $peminjaman_data['barang_id'] == $barang['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($barang['nama_barang']) ?> 
                                        (<?= htmlspecialchars($barang['kode_barang']) ?>) - 
                                        Stok: <?= $barang['jumlah_tersedia'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="barang_id" value="<?= $peminjaman_data['barang_id'] ?>">
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="jumlah_pinjam" class="form-label">Jumlah Pinjam <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="jumlah_pinjam" name="jumlah_pinjam" 
                                   min="1" required
                                   value="<?= $edit_mode ? $peminjaman_data['jumlah_pinjam'] : '' ?>">
                            <small class="text-muted">Stok tersedia: <span id="stok_tersedia">-</span></small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="peminjam_nama" class="form-label">Nama Peminjam <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="peminjam_nama" name="peminjam_nama" 
                                   required
                                   value="<?= $edit_mode ? htmlspecialchars($peminjaman_data['peminjam_nama']) : '' ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="peminjam_kelas" class="form-label">Kelas</label>
                            <input type="text" class="form-control" id="peminjam_kelas" name="peminjam_kelas" 
                                   value="<?= $edit_mode ? htmlspecialchars($peminjaman_data['peminjam_kelas']) : '' ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="peminjam_nis" class="form-label">NIS</label>
                            <input type="text" class="form-control" id="peminjam_nis" name="peminjam_nis" 
                                   value="<?= $edit_mode ? htmlspecialchars($peminjaman_data['peminjam_nis']) : '' ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="peminjam_kontak" class="form-label">Kontak <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="peminjam_kontak" name="peminjam_kontak" 
                                   value="<?= $edit_mode ? htmlspecialchars($peminjaman_data['peminjam_kontak']) : '' ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_pinjam" class="form-label">Tanggal Pinjam <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_pinjam" name="tanggal_pinjam" 
                                   required
                                   value="<?= $edit_mode ? $peminjaman_data['tanggal_pinjam'] : date('Y-m-d') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_kembali_rencana" class="form-label">Tanggal Kembali Rencana <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_kembali_rencana" name="tanggal_kembali_rencana" 
                                   required
                                   value="<?= $edit_mode ? $peminjaman_data['tanggal_kembali_rencana'] : date('Y-m-d', strtotime('+7 days')) ?>">
                            <small class="text-muted">Maksimal peminjaman: <strong><?= $maksimal_peminjaman ?> hari</strong></small>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= $edit_mode ? htmlspecialchars($peminjaman_data['keterangan']) : '' ?></textarea>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?= $edit_mode ? 'Update' : 'Simpan' ?>
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informasi
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-lightbulb me-2"></i>Tips:</h6>
                    <ul class="mb-0">
                        <li>Pilih barang yang tersedia stoknya</li>
                        <li>Jumlah pinjam tidak boleh melebihi stok tersedia</li>
                        <li>Tanggal kembali rencana minimal 1 hari setelah tanggal pinjam</li>
                        <li><strong>Maksimal peminjaman: <?= $maksimal_peminjaman ?> hari</strong></li>
                        <li><strong>Kontak peminjam wajib diisi</strong></li>
                        <li>Data peminjaman akan otomatis mengurangi stok barang</li>
                    </ul>
                </div>
                
                <?php if ($edit_mode): ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Mode Edit:</h6>
                        <ul class="mb-0">
                            <li>Barang tidak dapat diubah setelah peminjaman dibuat</li>
                            <li>Hanya data peminjam dan tanggal yang dapat diubah</li>
                            <li>Status peminjaman dikelola terpisah</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Barang Info -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>Info Barang
                </h5>
            </div>
            <div class="card-body" id="barangInfo">
                <p class="text-muted text-center">Pilih barang untuk melihat detail</p>
            </div>
        </div>
    </div>
</div>

<script>
// Update stok info when barang is selected
document.getElementById('barang_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const stokTersedia = selectedOption.getAttribute('data-stok');
    const barangInfo = document.getElementById('barangInfo');
    
    if (this.value) {
        document.getElementById('stok_tersedia').textContent = stokTersedia || '0';
        
        // Update barang info
        barangInfo.innerHTML = `
            <h6>${selectedOption.text}</h6>
            <p class="text-muted mb-2">Stok tersedia: <strong>${stokTersedia || '0'}</strong></p>
            <small class="text-muted">Pastikan jumlah pinjam tidak melebihi stok tersedia</small>
        `;
    } else {
        document.getElementById('stok_tersedia').textContent = '-';
        barangInfo.innerHTML = '<p class="text-muted text-center">Pilih barang untuk melihat detail</p>';
    }
});

// Real-time validation for tanggal kembali rencana
document.getElementById('tanggal_pinjam').addEventListener('change', function() {
    const tanggalPinjam = this.value;
    const tanggalKembaliInput = document.getElementById('tanggal_kembali_rencana');
    const maksimalPeminjaman = <?= $maksimal_peminjaman ?>;
    
    if (tanggalPinjam) {
        // Set max date untuk tanggal kembali
        const tanggalPinjamDate = new Date(tanggalPinjam);
        const maxDate = new Date(tanggalPinjamDate);
        maxDate.setDate(tanggalPinjamDate.getDate() + maksimalPeminjaman);
        
        // Set min dan max untuk input date
        tanggalKembaliInput.min = tanggalPinjam;
        tanggalKembaliInput.max = maxDate.toISOString().split('T')[0];
        
        // Set default value jika kosong atau melebihi batas
        if (!tanggalKembaliInput.value || new Date(tanggalKembaliInput.value) > maxDate) {
            tanggalKembaliInput.value = maxDate.toISOString().split('T')[0];
        }
        
        // Update info
        const infoElement = tanggalKembaliInput.parentElement.querySelector('small');
        if (infoElement) {
            infoElement.innerHTML = `Maksimal peminjaman: <strong>${maksimalPeminjaman} hari</strong> (sampai ${maxDate.toLocaleDateString('id-ID')})`;
        }
        
        // Disable dates in calendar yang melebihi batas
        disableDatesInCalendar(tanggalKembaliInput, tanggalPinjamDate, maxDate);
    }
});

// Real-time validation for tanggal kembali rencana
document.getElementById('tanggal_kembali_rencana').addEventListener('change', function() {
    const tanggalPinjam = document.getElementById('tanggal_pinjam').value;
    const tanggalKembali = this.value;
    const maksimalPeminjaman = <?= $maksimal_peminjaman ?>;
    
    if (tanggalPinjam && tanggalKembali) {
        const tanggalPinjamDate = new Date(tanggalPinjam);
        const tanggalKembaliDate = new Date(tanggalKembali);
        const selisihHari = Math.floor((tanggalKembaliDate - tanggalPinjamDate) / (1000 * 60 * 60 * 24));
        
        const infoElement = this.parentElement.querySelector('small');
        if (selisihHari > maksimalPeminjaman) {
            if (infoElement) {
                infoElement.innerHTML = `<span class="text-danger">Maksimal peminjaman: <strong>${maksimalPeminjaman} hari</strong>. Anda memilih ${selisihHari} hari.</span>`;
            }
        } else {
            if (infoElement) {
                infoElement.innerHTML = `Maksimal peminjaman: <strong>${maksimalPeminjaman} hari</strong> (${selisihHari} hari dipilih)`;
            }
        }
    }
});

// Function to disable dates in calendar
function disableDatesInCalendar(inputElement, startDate, maxDate) {
    // Add event listener untuk mencegah input manual tanggal yang tidak valid
    inputElement.addEventListener('input', function() {
        const selectedDate = new Date(this.value);
        const minDate = new Date(startDate);
        const maxDateObj = new Date(maxDate);
        
        if (selectedDate < minDate || selectedDate > maxDateObj) {
            // Reset ke tanggal yang valid
            this.value = maxDateObj.toISOString().split('T')[0];
            
            // Show warning
            const infoElement = this.parentElement.querySelector('small');
            if (infoElement) {
                infoElement.innerHTML = `<span class="text-danger">Tanggal tidak valid! Maksimal: ${maxDateObj.toLocaleDateString('id-ID')}</span>`;
                
                // Reset info after 3 seconds
                setTimeout(() => {
                    infoElement.innerHTML = `Maksimal peminjaman: <strong><?= $maksimal_peminjaman ?> hari</strong> (sampai ${maxDateObj.toLocaleDateString('id-ID')})`;
                }, 3000);
            }
        }
    });
    
    // Add CSS untuk visual feedback
    const style = document.createElement('style');
    style.textContent = `
        input[type="date"]:invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        input[type="date"]:valid {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
    `;
    document.head.appendChild(style);
}

// Initialize date validation on page load
document.addEventListener('DOMContentLoaded', function() {
    const tanggalPinjamInput = document.getElementById('tanggal_pinjam');
    const tanggalKembaliInput = document.getElementById('tanggal_kembali_rencana');
    const maksimalPeminjaman = <?= $maksimal_peminjaman ?>;
    
    // Set initial max date untuk tanggal kembali
    if (tanggalPinjamInput.value) {
        const tanggalPinjamDate = new Date(tanggalPinjamInput.value);
        const maxDate = new Date(tanggalPinjamDate);
        maxDate.setDate(tanggalPinjamDate.getDate() + maksimalPeminjaman);
        
        tanggalKembaliInput.min = tanggalPinjamInput.value;
        tanggalKembaliInput.max = maxDate.toISOString().split('T')[0];
        
        // Disable dates in calendar
        disableDatesInCalendar(tanggalKembaliInput, tanggalPinjamDate, maxDate);
    }
});

// Form validation
document.getElementById('peminjamanForm').addEventListener('submit', function(e) {
    const jumlahPinjam = parseInt(document.getElementById('jumlah_pinjam').value);
    const stokTersedia = parseInt(document.getElementById('stok_tersedia').textContent);
    const tanggalPinjam = document.getElementById('tanggal_pinjam').value;
    const tanggalKembali = document.getElementById('tanggal_kembali_rencana').value;
    const peminjamNama = document.getElementById('peminjam_nama').value.trim();
    const peminjamKontak = document.getElementById('peminjam_kontak').value.trim();
    const maksimalPeminjaman = <?= $maksimal_peminjaman ?>;
    
    // Validasi field wajib
    if (!peminjamNama) {
        e.preventDefault();
        alert('Nama peminjam harus diisi!');
        document.getElementById('peminjam_nama').focus();
        return false;
    }
    
    if (!peminjamKontak) {
        e.preventDefault();
        alert('Kontak peminjam harus diisi!');
        document.getElementById('peminjam_kontak').focus();
        return false;
    }
    
    if (jumlahPinjam > stokTersedia) {
        e.preventDefault();
        alert('Jumlah pinjam tidak boleh melebihi stok tersedia!');
        return false;
    }
    
    if (tanggalKembali <= tanggalPinjam) {
        e.preventDefault();
        alert('Tanggal kembali rencana harus setelah tanggal pinjam!');
        return false;
    }
    
    // Cek maksimal hari peminjaman
    const tanggalPinjamDate = new Date(tanggalPinjam);
    const tanggalKembaliDate = new Date(tanggalKembali);
    const selisihHari = Math.floor((tanggalKembaliDate - tanggalPinjamDate) / (1000 * 60 * 60 * 24));
    
    if (selisihHari > maksimalPeminjaman) {
        e.preventDefault();
        alert(`Maksimal peminjaman adalah ${maksimalPeminjaman} hari. Anda memilih ${selisihHari} hari.`);
        return false;
    }
    
    return confirm('Apakah Anda yakin ingin menyimpan data peminjaman ini?');
});

// Initialize stok info if in edit mode
<?php if ($edit_mode): ?>
document.addEventListener('DOMContentLoaded', function() {
    const barangSelect = document.getElementById('barang_id');
    const selectedOption = barangSelect.options[barangSelect.selectedIndex];
    const stokTersedia = selectedOption.getAttribute('data-stok');
    
    document.getElementById('stok_tersedia').textContent = stokTersedia || '0';
    
    const barangInfo = document.getElementById('barangInfo');
    barangInfo.innerHTML = `
        <h6>${selectedOption.text}</h6>
        <p class="text-muted mb-2">Stok tersedia: <strong>${stokTersedia || '0'}</strong></p>
        <small class="text-muted">Mode edit - stok tidak akan berubah</small>
    `;
});
<?php endif; ?>
</script>

<?php require_once '../includes/footer.php'; ?> 