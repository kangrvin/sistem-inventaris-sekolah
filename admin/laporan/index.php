<?php
/**
 * Laporan - Admin Panel
 * Sistem Inventaris Sekolah
 */

// ----------------------
// EXPORT HANDLER (LETakkan DI ATAS include header)
// ----------------------
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    require_once __DIR__ . '/export_excel.php';
    exit;
}

if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    require_once __DIR__ . '/export_pdf.php';
    exit;
}
// ----------------------

// Include functions
require_once '../../config/functions.php';

$page_title = 'Laporan';
require_once '../includes/header.php';

// Add print CSS and JS
echo '<link rel="stylesheet" href="print.css">';
echo '<script src="print.js"></script>';

// Get report type
$report_type = $_GET['type'] ?? 'dashboard';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$kategori_id = $_GET['kategori_id'] ?? '';
$lokasi_id = $_GET['lokasi_id'] ?? '';
$status = $_GET['status'] ?? '';

// Get data for dashboard
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori");
    $stmt->execute();
    $kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT id, nama_lokasi FROM lokasi ORDER BY nama_lokasi");
    $stmt->execute();
    $lokasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM barang");
    $stmt->execute();
    $total_barang = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $total_peminjaman = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam' AND tanggal_kembali_rencana < CURDATE()");
    $stmt->execute();
    $total_terlambat = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'petugas' AND status = 'aktif'");
    $stmt->execute();
    $total_petugas = $stmt->fetch()['total'];

} catch(Exception $e) {
    $kategori_list = [];
    $lokasi_list = [];
    $total_barang = 0;
    $total_peminjaman = 0;
    $total_terlambat = 0;
    $total_petugas = 0;
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
              <i class="fas fa-chart-bar me-2"></i>Laporan
            </h2>
            <p class="text-muted mb-0">Generate dan export laporan sistem inventaris sekolah</p>
          </div>
          <div class="col-md-4 text-md-end">
            <div class="d-flex gap-2 justify-content-md-end">
              
              <!-- Export Excel -->
              <button type="button" class="btn btn-success" onclick="exportReport('excel')">
                <i class="fas fa-file-excel me-2"></i>Export Excel
              </button>

              <!-- Export PDF -->
              <button type="button" class="btn btn-danger" id="openExportModal">
                <i class="fas fa-file-pdf me-2"></i>Export PDF
              </button>

              <!-- Tombol Print -->
              <button type="button" class="btn btn-primary" onclick="printReport()" id="printBtn" style="display:none;">
                <i class="fas fa-print me-2"></i>Print
              </button>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Export PDF -->
<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-labelledby="exportPdfModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="exportPdfModalLabel">
          <i class="fas fa-file-pdf me-2"></i>Export Laporan ke PDF
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="container-fluid">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nama Kepala Sekolah</label>
              <input type="text" class="form-control" id="signLeftName" placeholder="Masukkan nama kepala sekolah">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">NIP Kepala Sekolah</label>
              <input type="text" class="form-control" id="signLeftNip" placeholder="Masukkan NIP kepala sekolah">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Nama Petugas Inventaris</label>
              <input type="text" class="form-control" id="signRightName" placeholder="Masukkan nama petugas">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">NIP Petugas Inventaris</label>
              <input type="text" class="form-control" id="signRightNip" placeholder="Masukkan NIP petugas">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Tempat Diterbitkan</label>
              <input type="text" class="form-control" id="place" placeholder="Contoh: Jakarta">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Tanggal Diterbitkan</label>
              <input type="text" class="form-control" id="date" placeholder="Contoh: 11 Oktober 2025">
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-2"></i>Batal
        </button>
        <button type="button" class="btn btn-danger" id="btnExportPdf">
          <i class="fas fa-file-export me-2"></i>Export PDF
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Dashboard Stats -->
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card bg-primary text-white">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4 class="mb-0"><?= number_format($total_barang) ?></h4>
            <small>Total Barang</small>
          </div>
          <div class="align-self-center">
            <i class="fas fa-boxes fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card bg-success text-white">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4 class="mb-0"><?= number_format($total_peminjaman) ?></h4>
            <small>Peminjaman Bulan Ini</small>
          </div>
          <div class="align-self-center">
            <i class="fas fa-handshake fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card bg-warning text-white">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4 class="mb-0"><?= number_format($total_terlambat) ?></h4>
            <small>Peminjaman Terlambat</small>
          </div>
          <div class="align-self-center">
            <i class="fas fa-exclamation-triangle fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="card bg-info text-white">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4 class="mb-0"><?= number_format($total_petugas) ?></h4>
            <small>Petugas Aktif</small>
          </div>
          <div class="align-self-center">
            <i class="fas fa-user-tie fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Section -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Laporan</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="" class="row g-3">
          <div class="col-md-2">
            <label class="form-label">Jenis Laporan</label>
            <select class="form-select" name="type" id="reportType">
              <option value="dashboard" <?= $report_type == 'dashboard' ? 'selected' : '' ?>>Dashboard</option>
              <option value="stok" <?= $report_type == 'stok' ? 'selected' : '' ?>>Stok Barang</option>
              <option value="peminjaman" <?= $report_type == 'peminjaman' ? 'selected' : '' ?>>Peminjaman</option>
              <option value="user" <?= $report_type == 'user' ? 'selected' : '' ?>>Aktivitas User</option>
              <option value="terlambat" <?= $report_type == 'terlambat' ? 'selected' : '' ?>>Peminjaman Terlambat</option>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
          </div>

          <div class="col-md-2">
            <label class="form-label">Tanggal Akhir</label>
            <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
          </div>

          <div class="col-md-2">
            <label class="form-label">Kategori</label>
            <select class="form-select" name="kategori_id" id="kategoriFilter">
              <option value="">Semua Kategori</option>
              <?php foreach ($kategori_list as $kategori): ?>
                <option value="<?= $kategori['id'] ?>" <?= $kategori_id == $kategori['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($kategori['nama_kategori']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Lokasi</label>
            <select class="form-select" name="lokasi_id" id="lokasiFilter">
              <option value="">Semua Lokasi</option>
              <?php foreach ($lokasi_list as $lokasi): ?>
                <option value="<?= $lokasi['id'] ?>" <?= $lokasi_id == $lokasi['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($lokasi['nama_lokasi']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" id="statusFilter">
              <option value="">Semua Status</option>
              <option value="dipinjam" <?= $status == 'dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
              <option value="dikembalikan" <?= $status == 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
              <option value="terlambat" <?= $status == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
            </select>
          </div>

          <div class="col-12">
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-search me-2"></i>Generate Laporan
              </button>
              <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-refresh me-2"></i>Reset
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Report Content -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="fas fa-chart-line me-2"></i>
          <?php
          switch($report_type) {
            case 'stok': echo 'Laporan Stok Barang'; break;
            case 'peminjaman': echo 'Laporan Peminjaman'; break;
            case 'user': echo 'Laporan Aktivitas User'; break;
            case 'terlambat': echo 'Laporan Peminjaman Terlambat'; break;
            default: echo 'Dashboard Laporan'; break;
          }
          ?>
        </h5>
        <small class="text-muted">
          Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>
        </small>
      </div>
      <div class="card-body">
        <?php
        switch($report_type) {
          case 'stok': include 'stok_report.php'; break;
          case 'peminjaman': include 'peminjaman_report.php'; break;
          case 'user': include 'user_report.php'; break;
          case 'terlambat': include 'terlambat_report.php'; break;
          default: include 'dashboard_report.php'; break;
        }
        ?>
      </div>
    </div>
  </div>
</div>

<script>
// ================== FIX: Tidak auto-submit supaya tidak kedip ==================
document.addEventListener('DOMContentLoaded', function() {
  const printBtn = document.getElementById('printBtn');
  const currentType = getCurrentReportType();

  if (currentType === 'dashboard') {
    printBtn.style.display = 'none';
  } else {
    printBtn.style.display = 'inline-block';
  }
});

// Export Excel/PDF
function exportReport(type) {
  const currentUrl = new URL(window.location);
  currentUrl.searchParams.set('export', type);
  window.open(currentUrl.toString(), '_blank');
}

// Modal Export PDF (fix kedip)
document.addEventListener("DOMContentLoaded", () => {
  const openModalBtn = document.getElementById("openExportModal");
  const modalElement = document.getElementById("exportPdfModal");
  const btnExportPdf = document.getElementById("btnExportPdf");
  const modal = new bootstrap.Modal(modalElement);

  openModalBtn.addEventListener("click", () => {
    const today = new Date();
    document.getElementById("date").value = formatTanggalIndonesia(today);
    modal.show();
  });

  btnExportPdf.addEventListener("click", () => {
  const signLeftName  = document.getElementById('signLeftName').value.trim();
  const signLeftNip   = document.getElementById('signLeftNip').value.trim();
  const signRightName = document.getElementById('signRightName').value.trim();
  const signRightNip  = document.getElementById('signRightNip').value.trim();
  const place         = document.getElementById('place').value.trim();
  const date          = document.getElementById('date').value.trim();

  if (!signLeftName || !signLeftNip || !signRightName || !signRightNip || !place || !date) {
    alert('Mohon lengkapi semua data sebelum export PDF.');
    return;
  }

  btnExportPdf.disabled = true;
  btnExportPdf.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';

  const type        = document.getElementById('reportType')?.value ?? '';
  const start_date  = document.querySelector('input[name="start_date"]')?.value ?? '';
  const end_date    = document.querySelector('input[name="end_date"]')?.value ?? '';
  const kategori_id = document.getElementById('kategoriFilter')?.value ?? '';
  const lokasi_id   = document.getElementById('lokasiFilter')?.value ?? '';
  const status      = document.getElementById('statusFilter')?.value ?? '';

  const exportUrl =
    `export_pdf.php?type=${encodeURIComponent(type)}` +
    `&start_date=${encodeURIComponent(start_date)}` +
    `&end_date=${encodeURIComponent(end_date)}` +
    `&kategori_id=${encodeURIComponent(kategori_id)}` +
    `&lokasi_id=${encodeURIComponent(lokasi_id)}` +
    `&status=${encodeURIComponent(status)}` +
    `&sign_left_name=${encodeURIComponent(signLeftName)}` +
    `&sign_left_nip=${encodeURIComponent(signLeftNip)}` +
    `&sign_right_name=${encodeURIComponent(signRightName)}` +
    `&sign_right_nip=${encodeURIComponent(signRightNip)}` +
    `&place=${encodeURIComponent(place)}` +
    `&date=${encodeURIComponent(date)}`;

  // buka di tab baru agar halaman index tidak refresh
  window.open(exportUrl, '_blank');

  setTimeout(() => {
    btnExportPdf.disabled = false;
    btnExportPdf.innerHTML = '<i class="fas fa-file-export me-2"></i>Export PDF';
  }, 2000);
});

});

function getCurrentReportType() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('type') || 'dashboard';
}

function formatTanggalIndonesia(dateObj) {
  const bulan = [
    "Januari","Februari","Maret","April","Mei","Juni",
    "Juli","Agustus","September","Oktober","November","Desember"
  ];
  const tanggal = dateObj.getDate();
  const bulanNama = bulan[dateObj.getMonth()];
  const tahun = dateObj.getFullYear();
  return `${tanggal} ${bulanNama} ${tahun}`;
}

function printReport() {
  window.print();
}
</script>

<?php require_once '../includes/footer.php'; ?>
