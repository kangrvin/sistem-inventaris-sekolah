<?php
/**
 * Index.php - Halaman Utama Sistem Inventaris Sekolah
 * Untuk user melihat data barang tanpa login
 */

require_once 'config/functions.php';

// Ambil data barang untuk ditampilkan
try {
    $pdo = getConnection();
    
    // Filter
    $kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
    $lokasi_filter = isset($_GET['lokasi']) ? (int)$_GET['lokasi'] : 0;
    $search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
    
    // Query untuk barang yang tersedia
    $where_conditions = ["b.jumlah_tersedia > 0"];
    $params = [];
    
    if ($kategori_filter > 0) {
        $where_conditions[] = "b.kategori_id = ?";
        $params[] = $kategori_filter;
    }
    
    if ($lokasi_filter > 0) {
        $where_conditions[] = "b.lokasi_id = ?";
        $params[] = $lokasi_filter;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(b.nama_barang LIKE ? OR b.kode_barang LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $sql = "
        SELECT 
            b.id,
            b.kode_barang,
            b.nama_barang,
            b.deskripsi,
            b.jumlah_tersedia,
            b.kondisi,
            b.foto,
            b.tahun_pengadaan,
            b.harga_perolehan,
            k.nama_kategori,
            l.nama_lokasi
        FROM barang b
        JOIN kategori k ON b.kategori_id = k.id
        JOIN lokasi l ON b.lokasi_id = l.id
        WHERE $where_clause
        ORDER BY b.nama_barang ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $barang_list = $stmt->fetchAll();
    
    // Ambil data kategori untuk filter
    $stmt = $pdo->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori");
    $kategori_list = $stmt->fetchAll();
    
    // Ambil data lokasi untuk filter
    $stmt = $pdo->query("SELECT id, nama_lokasi FROM lokasi ORDER BY nama_lokasi");
    $lokasi_list = $stmt->fetchAll();
    
    // Statistik untuk ditampilkan
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE jumlah_tersedia > 0");
    $total_barang = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM kategori");
    $total_kategori = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM lokasi");
    $total_lokasi = $stmt->fetch()['total'];
    
} catch(Exception $e) {
    $barang_list = [];
    $kategori_list = [];
    $lokasi_list = [];
    $total_barang = 0;
    $total_kategori = 0;
    $total_lokasi = 0;
}

// Include header
require_once 'includes/header.php';
?>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_barang) ?></div>
                    <div class="stats-label">Total Barang Tersedia</div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon success">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_kategori) ?></div>
                    <div class="stats-label">Kategori Barang</div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <div class="stats-icon warning">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stats-number"><?= number_format($total_lokasi) ?></div>
                    <div class="stats-label">Lokasi Penyimpanan</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Filter & Search Section -->
<section class="content-section" id="barang">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Data Barang Tersedia</h2>
            <p class="section-subtitle">Cari dan temukan barang yang Anda butuhkan dengan mudah</p>
        </div>
        
        <div class="filter-section">
            <form method="GET" action="" class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-search me-2"></i>Cari Barang
                    </label>
                    <input type="text" class="form-control" name="search" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Masukkan nama atau kode barang...">
                </div>
                
                <div class="col-lg-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-tags me-2"></i>Kategori
                    </label>
                    <select class="form-select" name="kategori">
                        <option value="0">Semua Kategori</option>
                        <?php foreach ($kategori_list as $kategori): ?>
                            <option value="<?= $kategori['id'] ?>" 
                                    <?= $kategori_filter == $kategori['id'] ? 'selected' : '' ?>>
                                <?= $kategori['nama_kategori'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-lg-3">
                    <label class="form-label fw-bold">
                        <i class="fas fa-map-marker-alt me-2"></i>Lokasi
                    </label>
                    <select class="form-select" name="lokasi">
                        <option value="0">Semua Lokasi</option>
                        <?php foreach ($lokasi_list as $lokasi): ?>
                            <option value="<?= $lokasi['id'] ?>" 
                                    <?= $lokasi_filter == $lokasi['id'] ? 'selected' : '' ?>>
                                <?= $lokasi['nama_lokasi'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-lg-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-search w-100">
                        <i class="fas fa-search me-2"></i>Cari
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Results Info -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-list me-2"></i>
                Ditemukan <?= count($barang_list) ?> barang tersedia
            </h5>
            
            <!-- <a href="auth/login.php" class="btn btn-login">
                <i class="fas fa-user-tie me-2"></i>Login untuk Peminjaman
            </a> -->
        </div>
        
        <!-- Products Grid -->
        <div class="row">
            <?php if (empty($barang_list)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">Tidak ada barang yang ditemukan</h4>
                        <p class="text-muted">Coba ubah filter pencarian Anda</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($barang_list as $barang): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm border-0 product-card">
                            <div class="card-body p-3">
                                <!-- Foto Barang -->
                                <div class="text-center mb-3">
                                    <?php if ($barang['foto']): ?>
                                        <img src="uploads/<?= htmlspecialchars($barang['foto']) ?>" 
                                             alt="<?= htmlspecialchars($barang['nama_barang']) ?>" 
                                             class="img-fluid rounded shadow-sm" 
                                             style="max-height: 200px; width: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded shadow-sm d-flex align-items-center justify-content-center" 
                                             style="height: 200px; width: 100%;">
                                            <i class="fas fa-box fa-4x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Header dengan nama dan badge kondisi -->
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0 fw-bold text-dark" style="font-size: 1rem; line-height: 1.3;">
                                        <?= htmlspecialchars($barang['nama_barang']) ?>
                                    </h6>
                                    <?php
                                    $kondisi_class = '';
                                    $kondisi_text = '';
                                    $kondisi_icon = '';
                                    switch($barang['kondisi']) {
                                        case 'baik':
                                            $kondisi_class = 'bg-success';
                                            $kondisi_text = 'Baik';
                                            $kondisi_icon = 'fas fa-check-circle';
                                            break;
                                        case 'rusak_ringan':
                                            $kondisi_class = 'bg-warning';
                                            $kondisi_text = 'Rusak Ringan';
                                            $kondisi_icon = 'fas fa-exclamation-triangle';
                                            break;
                                        case 'rusak_berat':
                                            $kondisi_class = 'bg-danger';
                                            $kondisi_text = 'Rusak Berat';
                                            $kondisi_icon = 'fas fa-times-circle';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $kondisi_class ?> px-2 py-1" style="font-size: 0.7rem;">
                                        <i class="<?= $kondisi_icon ?> me-1"></i><?= $kondisi_text ?>
                                    </span>
                                </div>
                                
                                <!-- Kode Barang -->
                                <p class="text-muted small mb-2 fw-semibold">
                                    <i class="fas fa-barcode me-1"></i><?= htmlspecialchars($barang['kode_barang']) ?>
                                </p>
                                
                                <!-- Badge Kategori dan Lokasi -->
                                <div class="mb-3">
                                    <span class="badge bg-primary me-1 mb-1" style="font-size: 0.7rem;">
                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($barang['nama_kategori']) ?>
                                    </span>
                                    <span class="badge bg-info mb-1" style="font-size: 0.7rem;">
                                        <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($barang['nama_lokasi']) ?>
                                    </span>
                                </div>
                                
                                <!-- Informasi Stok -->
                                <div class="row text-center mb-3 g-1">
                                    <div class="col-12">
                                        <div class="bg-light rounded p-2">
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">Tersedia</small>
                                            <strong class="text-success d-block" style="font-size: 1.2rem;"><?= $barang['jumlah_tersedia'] ?> unit</strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tahun Pengadaan -->
                                <?php if ($barang['tahun_pengadaan']): ?>
                                    <div class="text-center mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Tahun <?= $barang['tahun_pengadaan'] ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Alert Stok Rendah -->
                                <?php if ($barang['jumlah_tersedia'] < 5): ?>
                                    <div class="alert alert-warning alert-sm mt-2 mb-0 py-2" style="font-size: 0.7rem;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <strong>Stok rendah!</strong>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Button Hubungi Petugas -->
                                <div class="d-grid mt-3">
                                    <button class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-phone me-2"></i>Hubungi Petugas untuk Pinjam
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about-section" id="tentang">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="section-title text-start mb-4">Tentang Sistem Inventaris</h2>
                <p class="lead mb-4">
                    Sistem inventaris barang sekolah ini dirancang untuk memudahkan pengelolaan 
                    dan pencarian barang yang tersedia di gudang sekolah dengan teknologi modern.
                </p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="feature-content">
                                <h6>Data Terpusat</h6>
                                <small>Semua data barang tersimpan dengan aman</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="feature-content">
                                <h6>Pencarian Cepat</h6>
                                <small>Temukan barang dengan mudah</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-sync-alt"></i>
                            </div>
                            <div class="feature-content">
                                <h6>Update Real-time</h6>
                                <small>Data selalu diperbarui</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="feature-content">
                                <h6>Layanan Petugas</h6>
                                <small>Peminjaman melalui petugas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-chart-line fa-8x text-primary opacity-50"></i>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?> 