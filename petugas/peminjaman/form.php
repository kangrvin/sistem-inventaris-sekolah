<?php
/**
 * Form Peminjaman - Petugas Panel
 * Versi: perbaikan untuk menghindari duplicate kode peminjaman (race condition),
 * dan memastikan operasi simpan/ubah stok berjalan di dalam transaction.
 */

$page_title = isset($_GET['id']) ? 'Edit Peminjaman' : 'Tambah Peminjaman';
require_once '../includes/header.php';

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'] ?? null;

// ---------------------------
// Buat 1 koneksi PDO reusable
// ---------------------------
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=inventaris_sekolah;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Exception $e) {
    die("Koneksi database gagal: " . htmlspecialchars($e->getMessage()));
}

// Ambil setting maksimal peminjaman di awal
try {
    $stmt = $pdo->prepare("SELECT nilai FROM pengaturan WHERE nama_pengaturan = 'maksimal_peminjaman' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    $maksimal_peminjaman = isset($row['nilai']) ? (int)$row['nilai'] : 7;
} catch (Exception $e) {
    $maksimal_peminjaman = 7; // Default jika gagal baca
}

// ---------------------------
// Fungsi: generate kode aman
// ---------------------------
// NOTE: fungsi ini harus dipanggil **di dalam** transaction (BEGIN ... FOR UPDATE ... COMMIT).
function generateKodePeminjaman(PDO $pdo, $tahun) {
    // pattern like PJM-2025-%
    $pattern = "PJM-{$tahun}-%";
    $stmt = $pdo->prepare("
        SELECT kode_peminjaman
        FROM peminjaman
        WHERE kode_peminjaman LIKE :pattern
        ORDER BY kode_peminjaman DESC
        LIMIT 1 FOR UPDATE
    ");
    $stmt->execute([':pattern' => $pattern]);
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/-(\d+)$/', $last, $m)) {
        $seq = intval($m[1]) + 1;
    } else {
        $seq = 1;
    }
    return sprintf('PJM-%d-%03d', $tahun, $seq);
}

// ---------------------------
// Handle edit mode (baca data bila id ada)
// ---------------------------
$edit_mode = false;
$peminjaman_data = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $peminjaman_id = (int) $_GET['id'];

    try {
        $stmt = $pdo->prepare("
            SELECT p.*, b.nama_barang, b.kode_barang, b.jumlah_tersedia
            FROM peminjaman p
            JOIN barang b ON p.barang_id = b.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$peminjaman_id]);
        $peminjaman_data = $stmt->fetch();

        if (!$peminjaman_data) {
            echo "<script>alert('Data peminjaman tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch (Exception $e) {
        echo "<script>alert('Terjadi kesalahan sistem!'); window.location.href='index.php';</script>";
        exit();
    }
}

// ---------------------------
// Handle form submission (INSERT / UPDATE) — transaction-safe
// ---------------------------
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data (aman)
    $barang_id = isset($_POST['barang_id']) ? (int)$_POST['barang_id'] : 0;
    $jumlah_pinjam = isset($_POST['jumlah_pinjam']) ? (int)$_POST['jumlah_pinjam'] : 0;
    $peminjam_nama = trim($_POST['peminjam_nama'] ?? '');
    $peminjam_kelas = trim($_POST['peminjam_kelas'] ?? '');
    $peminjam_nis = trim($_POST['peminjam_nis'] ?? '');
    $peminjam_kontak = trim($_POST['peminjam_kontak'] ?? '');
    $tanggal_pinjam = $_POST['tanggal_pinjam'] ?? '';
    $tanggal_kembali_rencana = $_POST['tanggal_kembali_rencana'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validasi input
    if (empty($barang_id)) $errors[] = "Barang harus dipilih";
    if ($jumlah_pinjam <= 0) $errors[] = "Jumlah pinjam harus lebih dari 0";
    if ($peminjam_nama === '') $errors[] = "Nama peminjam harus diisi";
    if ($peminjam_kontak === '') $errors[] = "Kontak peminjam harus diisi";
    if ($tanggal_pinjam === '') $errors[] = "Tanggal pinjam harus diisi";
    if ($tanggal_kembali_rencana === '') $errors[] = "Tanggal kembali rencana harus diisi";

    if (!empty($tanggal_pinjam) && !empty($tanggal_kembali_rencana)) {
        if (strtotime($tanggal_kembali_rencana) <= strtotime($tanggal_pinjam)) {
            $errors[] = "Tanggal kembali rencana harus setelah tanggal pinjam";
        } else {
            $selisih_hari = floor((strtotime($tanggal_kembali_rencana) - strtotime($tanggal_pinjam)) / (60*60*24));
            if ($selisih_hari > $maksimal_peminjaman) {
                $errors[] = "Maksimal peminjaman adalah $maksimal_peminjaman hari. Anda memilih $selisih_hari hari.";
            }
        }
    }

    // Jika belum error, lanjutkan simpan dengan transaction
    if (empty($errors)) {
        try {
            if ($edit_mode) {
                // ----------------------------
                // UPDATE FLOW (edit existing)
                // ----------------------------
                $pdo->beginTransaction();

                // Lock peminjaman row
                $stmt = $pdo->prepare("SELECT * FROM peminjaman WHERE id = ? FOR UPDATE");
                $stmt->execute([$peminjaman_id]);
                $old = $stmt->fetch();
                if (!$old) {
                    throw new Exception("Data peminjaman tidak ditemukan saat update.");
                }

                // Lock barang row (pakai barang_id dari data lama)
                $barang_id_locked = (int)$old['barang_id'];
                $stmt = $pdo->prepare("SELECT jumlah_tersedia FROM barang WHERE id = ? FOR UPDATE");
                $stmt->execute([$barang_id_locked]);
                $barang_row = $stmt->fetch();
                if (!$barang_row) {
                    throw new Exception("Data barang tidak ditemukan.");
                }
                $stok_sekarang = (int)$barang_row['jumlah_tersedia'];

                $old_jumlah = (int)$old['jumlah_pinjam'];
                $delta = $jumlah_pinjam - $old_jumlah;

                if ($delta > 0) {
                    // butuh menambah jumlah pinjam — cek stok
                    if ($stok_sekarang < $delta) {
                        throw new Exception("Stok barang tidak mencukupi untuk menaikkan jumlah pinjam. Tersedia: $stok_sekarang.");
                    }
                    $stmt = $pdo->prepare("UPDATE barang SET jumlah_tersedia = jumlah_tersedia - ? WHERE id = ?");
                    $stmt->execute([$delta, $barang_id_locked]);
                } elseif ($delta < 0) {
                    // dikembalikan sebagian
                    $stmt = $pdo->prepare("UPDATE barang SET jumlah_tersedia = jumlah_tersedia + ? WHERE id = ?");
                    $stmt->execute([abs($delta), $barang_id_locked]);
                }

                // Update peminjaman (tidak mengubah kode_peminjaman)
                $stmt = $pdo->prepare("
                    UPDATE peminjaman 
                    SET jumlah_pinjam = ?, peminjam_nama = ?, peminjam_kelas = ?, 
                        peminjam_nis = ?, peminjam_kontak = ?, tanggal_pinjam = ?, 
                        tanggal_kembali_rencana = ?, keterangan = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([
                    $jumlah_pinjam, $peminjam_nama, $peminjam_kelas,
                    $peminjam_nis, $peminjam_kontak, $tanggal_pinjam,
                    $tanggal_kembali_rencana, $keterangan, $peminjaman_id
                ]);

                $pdo->commit();
                $success = "Peminjaman berhasil diperbarui!";
            } else {
                // ----------------------------
                // INSERT FLOW (tambah baru) — dengan retry jika terjadi duplicate key
                // ----------------------------
                $maxAttempts = 5;
                $attempt = 0;
                $inserted = false;

                while ($attempt < $maxAttempts && !$inserted) {
                    try {
                        $pdo->beginTransaction();

                        // Lock barang row
                        $stmt = $pdo->prepare("SELECT jumlah_tersedia FROM barang WHERE id = ? FOR UPDATE");
                        $stmt->execute([$barang_id]);
                        $barang_row = $stmt->fetch();
                        if (!$barang_row) {
                            throw new Exception("Data barang tidak ditemukan.");
                        }
                        $stok_tersedia = (int)$barang_row['jumlah_tersedia'];

                        if ($stok_tersedia < $jumlah_pinjam) {
                            throw new Exception("Stok barang tidak mencukupi. Tersedia: $stok_tersedia.");
                        }

                        // Generate kode peminjaman secara aman (dijalankan di dalam transaction)
                        $tahun = date('Y');
                        $kode_peminjaman = generateKodePeminjaman($pdo, $tahun);

                        // Insert peminjaman
                        $stmt = $pdo->prepare("
                            INSERT INTO peminjaman (
                                kode_peminjaman, barang_id, jumlah_pinjam, peminjam_nama, peminjam_kelas,
                                peminjam_nis, peminjam_kontak, tanggal_pinjam, tanggal_kembali_rencana,
                                keterangan, status, created_by, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'dipinjam', ?, NOW())
                        ");
                        $stmt->execute([
                            $kode_peminjaman, $barang_id, $jumlah_pinjam, $peminjam_nama, $peminjam_kelas,
                            $peminjam_nis, $peminjam_kontak, $tanggal_pinjam, $tanggal_kembali_rencana,
                            $keterangan, $user_id
                        ]);

                        // Update stok barang
                        $stmt = $pdo->prepare("UPDATE barang SET jumlah_tersedia = jumlah_tersedia - ? WHERE id = ?");
                        $stmt->execute([$jumlah_pinjam, $barang_id]);

                        $pdo->commit();
                        $inserted = true;
                        $success = "Peminjaman berhasil ditambahkan!";
                    } catch (PDOException $e) {
                        // rollback dan cek apakah duplicate key — jika duplicate, retry
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        $isDuplicate = isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062;
                        $attempt++;
                        if ($isDuplicate && $attempt < $maxAttempts) {
                            // tunggu sebentar lalu ulangi (beri kesempatan transaksi lain commit)
                            usleep(150000); // 150 ms
                            continue;
                        }
                        // jika bukan duplicate atau sudah habis percobaan -> lempar lagi
                        throw $e;
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        throw $e;
                    }
                } // end while

                if (!$inserted) {
                    throw new Exception("Gagal menyimpan peminjaman setelah beberapa percobaan.");
                }
            } // end insert/update main

            // sukses -> redirect atau tampilkan pesan
            // pakai JS redirect agar tetap tampil alert pada halaman
            echo "<script>
                const msg = " . json_encode($success) . ";
                alert(msg);
                window.location.href = 'index.php';
            </script>";
            exit();
        } catch (Exception $e) {
            // Pastikan rollback jika masih di transaction
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = "Gagal menyimpan data: " . $e->getMessage();
        }
    } // end if empty($errors)
} // end if POST

// ---------------------------
// Ambil data barang untuk dropdown (sisa tampilan tidak diubah)
// ---------------------------
try {
    $stmt = $pdo->query("
        SELECT b.id, b.nama_barang, b.kode_barang, b.jumlah_tersedia, k.nama_kategori, l.nama_lokasi
        FROM barang b 
        JOIN kategori k ON b.kategori_id = k.id 
        JOIN lokasi l ON b.lokasi_id = l.id 
        WHERE b.jumlah_tersedia > 0 
        ORDER BY b.nama_barang
    ");
    $barang_list = $stmt->fetchAll();
} catch (Exception $e) {
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

<?php if (isset($success) && !$errors): ?>
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
                                            <?= ($edit_mode && isset($peminjaman_data['barang_id']) && $peminjaman_data['barang_id'] == $barang['id']) ? 'selected' : '' ?>>
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
// (JS bagian bawah tetap sama seperti file kamu — saya gunakan isi JS aslinya)
document.getElementById('barang_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const stokTersedia = selectedOption.getAttribute('data-stok');
    const barangInfo = document.getElementById('barangInfo');

    if (this.value) {
        document.getElementById('stok_tersedia').textContent = stokTersedia || '0';

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

// (sisanya - tanggal validation, form validation, dll - gunakan kode JS asli Anda)
</script>

<?php require_once '../includes/footer.php'; ?>
