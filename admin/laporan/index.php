<?php
/**
 * Laporan - Admin Panel
 * Sistem Inventaris Sekolah
 */

// Include functions
require_once '../../config/functions.php';

$page_title = 'Laporan';
require_once '../includes/header.php';

// Add print CSS and JS
echo '<link rel="stylesheet" href="print.css">';
echo '<script src="print.js"></script>';

// Get report type
$report_type = $_GET['type'] ?? 'dashboard';
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$kategori_id = $_GET['kategori_id'] ?? '';
$lokasi_id = $_GET['lokasi_id'] ?? '';
$status = $_GET['status'] ?? '';

// Get data for dashboard
try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get kategori for filter
    $stmt = $pdo->prepare("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori");
    $stmt->execute();
    $kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get lokasi for filter
    $stmt = $pdo->prepare("SELECT id, nama_lokasi FROM lokasi ORDER BY nama_lokasi");
    $stmt->execute();
    $lokasi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dashboard stats
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

// Handle export
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    // PDF export logic will be implemented
    echo "<script>alert('Fitur export PDF akan segera tersedia!');</script>";
}

if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Excel export logic will be implemented
    echo "<script>alert('Fitur export Excel akan segera tersedia!');</script>";
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
                            <button type="button" class="btn btn-success" onclick="exportReport('excel')">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </button>
                            <button type="button" class="btn btn-primary" onclick="printReport()" id="printBtn" style="display: none;">
                                <i class="fas fa-print me-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
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
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filter Laporan
                </h5>
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
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
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
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">
                            Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php
                // Load report content based on type
                switch($report_type) {
                    case 'stok':
                        include 'stok_report.php';
                        break;
                    case 'peminjaman':
                        include 'peminjaman_report.php';
                        break;
                    case 'user':
                        include 'user_report.php';
                        break;
                    case 'terlambat':
                        include 'terlambat_report.php';
                        break;
                    default:
                        include 'dashboard_report.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-submit filter dropdowns
document.addEventListener('DOMContentLoaded', function() {
    const reportType = document.getElementById('reportType');
    const kategoriFilter = document.getElementById('kategoriFilter');
    const lokasiFilter = document.getElementById('lokasiFilter');
    const statusFilter = document.getElementById('statusFilter');
    const printBtn = document.getElementById('printBtn');
    
    // Show/hide print button based on report type
    function togglePrintButton() {
        const currentType = getCurrentReportType();
        if (currentType === 'dashboard') {
            printBtn.style.display = 'none';
        } else {
            printBtn.style.display = 'inline-block';
        }
    }
    
    // Initial check
    togglePrintButton();
    
    // Auto-submit filter dropdowns
    if (reportType) {
        reportType.addEventListener('change', function() {
            submitFilter();
        });
    }
    
    if (kategoriFilter) {
        kategoriFilter.addEventListener('change', function() {
            submitFilter();
        });
    }
    
    if (lokasiFilter) {
        lokasiFilter.addEventListener('change', function() {
            submitFilter();
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            submitFilter();
        });
    }
    
    // Function to submit filter form
    function submitFilter() {
        const form = document.querySelector('form[method="GET"]');
        if (form) {
            form.submit();
        }
    }
});

// Export function
function exportReport(type) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', type);
    window.open(currentUrl.toString(), '_blank');
}

// Print function
function printReport() {
    const reportType = getCurrentReportType();
    const filters = getCurrentFilters();
    
    // Build URL for print page
    let printUrl = '';
    switch(reportType) {
        case 'stok':
            printUrl = 'print_stok.php';
            if (filters.kategori_id) printUrl += `?kategori_id=${filters.kategori_id}`;
            if (filters.lokasi_id) printUrl += `${filters.kategori_id ? '&' : '?'}lokasi_id=${filters.lokasi_id}`;
            break;
        case 'peminjaman':
            printUrl = 'print_peminjaman.php';
            if (filters.start_date) printUrl += `?start_date=${filters.start_date}`;
            if (filters.end_date) printUrl += `${filters.start_date ? '&' : '?'}end_date=${filters.end_date}`;
            if (filters.status) printUrl += `${filters.start_date || filters.end_date ? '&' : '?'}status=${filters.status}`;
            break;
        case 'terlambat':
            printUrl = 'print_terlambat.php';
            break;
        case 'user':
            printUrl = 'print_user.php';
            if (filters.start_date) printUrl += `?start_date=${filters.start_date}`;
            if (filters.end_date) printUrl += `${filters.start_date ? '&' : '?'}end_date=${filters.end_date}`;
            break;
        default:
            return; // No print for dashboard
    }
    
    // Open print window
    const printWindow = window.open(printUrl, '_blank', 'width=800,height=600');
    
    // Auto print when loaded
    if (printWindow) {
        printWindow.onload = function() {
            printWindow.print();
        };
    }
}

// Helper functions for print
function getCurrentReportType() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('type') || 'dashboard';
}

function getReportTitle(reportType) {
    const titles = {
        'dashboard': 'Dashboard',
        'stok': 'Stok Barang',
        'peminjaman': 'Peminjaman',
        'terlambat': 'Peminjaman Terlambat',
        'user': 'Aktivitas User'
    };
    return titles[reportType] || 'Dashboard';
}

function generatePrintHeader(reportType) {
    const currentDate = new Date().toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    
    const filters = getCurrentFilters();
    
    return `
        <div class="print-header">
            <div class="school-logo">
                <svg width="80" height="80" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="35" fill="#007bff" stroke="#000" stroke-width="2"/>
                    <text x="40" y="45" text-anchor="middle" fill="white" font-size="12" font-weight="bold">LOGO</text>
                </svg>
            </div>
            <h1 class="school-name">SMA NEGERI 1 CONTOH</h1>
            <p class="school-address">Jl. Contoh No. 123, Kota Contoh, Provinsi Contoh</p>
            <p class="school-address">Telepon: (021) 1234567 | Email: info@sman1contoh.sch.id</p>
            <h2 class="report-title">Laporan ${getReportTitle(reportType)}</h2>
            <p class="report-subtitle">Sistem Inventaris Sekolah</p>
        </div>
        
        <div class="print-info">
            <div class="info-row">
                <span class="info-label">Tanggal Cetak:</span>
                <span>${currentDate}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Dicetak Oleh:</span>
                <span>Administrator</span>
            </div>
            ${generateFilterInfo(filters)}
        </div>
    `;
}

function getCurrentFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    return {
        start_date: urlParams.get('start_date') || '',
        end_date: urlParams.get('end_date') || '',
        kategori_id: urlParams.get('kategori_id') || '',
        lokasi_id: urlParams.get('lokasi_id') || '',
        status: urlParams.get('status') || ''
    };
}

function generateFilterInfo(filters) {
    let filterInfo = '';
    
    if (filters.start_date && filters.end_date) {
        filterInfo += `
            <div class="info-row">
                <span class="info-label">Periode:</span>
                <span>${formatDate(filters.start_date)} - ${formatDate(filters.end_date)}</span>
            </div>
        `;
    }
    
    if (filters.kategori_id) {
        filterInfo += `
            <div class="info-row">
                <span class="info-label">Kategori:</span>
                <span>ID: ${filters.kategori_id}</span>
            </div>
        `;
    }
    
    if (filters.lokasi_id) {
        filterInfo += `
            <div class="info-row">
                <span class="info-label">Lokasi:</span>
                <span>ID: ${filters.lokasi_id}</span>
            </div>
        `;
    }
    
    if (filters.status) {
        filterInfo += `
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span>${getStatusLabel(filters.status)}</span>
            </div>
        `;
    }
    
    return filterInfo;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

function getStatusLabel(status) {
    const labels = {
        'dipinjam': 'Dipinjam',
        'dikembalikan': 'Dikembalikan',
        'terlambat': 'Terlambat'
    };
    return labels[status] || status;
}

function generatePrintFooter() {
    const currentDate = new Date().toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    
    return `
        <div class="print-footer">
            <p>Dicetak pada: ${currentDate} | Sistem Inventaris Sekolah v1.0</p>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Kepala Sekolah</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Petugas Inventaris</div>
            </div>
        </div>
    `;
}

// Auto-hide alerts
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    });
}, 5000);
</script>

<?php require_once '../includes/footer.php'; ?> 