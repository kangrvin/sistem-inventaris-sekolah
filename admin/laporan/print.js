/**
 * Print functionality for Laporan
 */

// Print function
function printReport(reportType = 'dashboard') {
    // Get current report data
    const reportData = getCurrentReportData();
    
    // Create print window
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    // Generate print content
    const printContent = generatePrintContent(reportType, reportData);
    
    // Write content to print window
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait for content to load then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}

// Get current report data from page
function getCurrentReportData() {
    const data = {
        reportType: getCurrentReportType(),
        filters: getCurrentFilters(),
        summary: getCurrentSummary(),
        tableData: getCurrentTableData()
    };
    return data;
}

// Get current report type
function getCurrentReportType() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('type') || 'dashboard';
}

// Get current filters
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

// Get current summary data
function getCurrentSummary() {
    const summary = {};
    
    // Get summary cards data
    const summaryCards = document.querySelectorAll('.card.bg-primary, .card.bg-success, .card.bg-info, .card.bg-warning, .card.bg-danger, .card.bg-secondary');
    summaryCards.forEach((card, index) => {
        const number = card.querySelector('h4')?.textContent || '0';
        const label = card.querySelector('small')?.textContent || '';
        summary[`card_${index + 1}`] = { number, label };
    });
    
    return summary;
}

// Get current table data
function getCurrentTableData() {
    const table = document.querySelector('table');
    if (!table) return [];
    
    const rows = [];
    const tableRows = table.querySelectorAll('tbody tr');
    
    tableRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [];
        cells.forEach(cell => {
            rowData.push(cell.textContent.trim());
        });
        rows.push(rowData);
    });
    
    return rows;
}

// Generate print content based on report type
function generatePrintContent(reportType, data) {
    const header = generatePrintHeader(reportType, data.filters);
    const content = generateReportContent(reportType, data);
    const footer = generatePrintFooter();
    
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Laporan ${getReportTitle(reportType)}</title>
            <link rel="stylesheet" href="print.css">
            <style>
                @media print {
                    @page {
                        margin: 1cm;
                        size: A4;
                    }
                }
            </style>
        </head>
        <body>
            ${header}
            ${content}
            ${footer}
        </body>
        </html>
    `;
}

// Generate print header
function generatePrintHeader(reportType, filters) {
    const reportTitle = getReportTitle(reportType);
    const currentDate = new Date().toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    
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
            <h2 class="report-title">Laporan ${reportTitle}</h2>
            <p class="report-subtitle">Sistem Inventaris Sekolah</p>
        </div>
        
        <div class="print-info">
            <div class="info-row">
                <span class="info-label">Tanggal Cetak:</span>
                <span>${currentDate}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Dicetak Oleh:</span>
                <span>${getCurrentUserName()}</span>
            </div>
            ${generateFilterInfo(filters)}
        </div>
    `;
}

// Generate filter information
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
        const kategoriName = getKategoriName(filters.kategori_id);
        if (kategoriName) {
            filterInfo += `
                <div class="info-row">
                    <span class="info-label">Kategori:</span>
                    <span>${kategoriName}</span>
                </div>
            `;
        }
    }
    
    if (filters.lokasi_id) {
        const lokasiName = getLokasiName(filters.lokasi_id);
        if (lokasiName) {
            filterInfo += `
                <div class="info-row">
                    <span class="info-label">Lokasi:</span>
                    <span>${lokasiName}</span>
                </div>
            `;
        }
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

// Generate report content based on type
function generateReportContent(reportType, data) {
    switch(reportType) {
        case 'stok':
            return generateStokReportContent(data);
        case 'peminjaman':
            return generatePeminjamanReportContent(data);
        case 'terlambat':
            return generateTerlambatReportContent(data);
        case 'user':
            return generateUserReportContent(data);
        default:
            return generateDashboardReportContent(data);
    }
}

// Generate stok report content
function generateStokReportContent(data) {
    return `
        <div class="stok-report">
            <div class="summary-cards">
                ${generateSummaryCards(data.summary)}
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5>Daftar Stok Barang</h5>
                    ${generateStokTable(data.tableData)}
                </div>
            </div>
            
            ${generateSummaryFooter(data.summary, 'stok')}
        </div>
    `;
}

// Generate peminjaman report content
function generatePeminjamanReportContent(data) {
    return `
        <div class="peminjaman-report">
            <div class="summary-cards">
                ${generateSummaryCards(data.summary)}
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5>Daftar Peminjaman</h5>
                    ${generatePeminjamanTable(data.tableData)}
                </div>
            </div>
            
            ${generateSummaryFooter(data.summary, 'peminjaman')}
        </div>
    `;
}

// Generate terlambat report content
function generateTerlambatReportContent(data) {
    return `
        <div class="terlambat-report">
            <div class="summary-cards">
                ${generateSummaryCards(data.summary)}
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5>Daftar Peminjaman Terlambat</h5>
                    ${generateTerlambatTable(data.tableData)}
                </div>
            </div>
            
            ${generateSummaryFooter(data.summary, 'terlambat')}
        </div>
    `;
}

// Generate user report content
function generateUserReportContent(data) {
    return `
        <div class="user-report">
            <div class="summary-cards">
                ${generateSummaryCards(data.summary)}
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5>Daftar Aktivitas User</h5>
                    ${generateUserTable(data.tableData)}
                </div>
            </div>
            
            ${generateSummaryFooter(data.summary, 'user')}
        </div>
    `;
}

// Generate dashboard report content
function generateDashboardReportContent(data) {
    return `
        <div class="dashboard-report">
            <div class="summary-cards">
                ${generateSummaryCards(data.summary)}
            </div>
            
            <div class="print-grid">
                <div class="card">
                    <div class="card-body">
                        <h5>Statistik Cepat</h5>
                        ${generateQuickStats(data.summary)}
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5>Ringkasan</h5>
                        ${generateDashboardSummary(data.summary)}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Generate summary cards
function generateSummaryCards(summary) {
    let cards = '';
    Object.keys(summary).forEach(key => {
        const card = summary[key];
        cards += `
            <div class="summary-card">
                <div class="number">${card.number}</div>
                <div class="label">${card.label}</div>
            </div>
        `;
    });
    return cards;
}

// Generate tables
function generateStokTable(tableData) {
    return generateGenericTable(tableData, [
        'No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Lokasi', 
        'Stok Total', 'Stok Tersedia', 'Dipinjam', 'Kondisi', 'Tahun', 'Harga'
    ]);
}

function generatePeminjamanTable(tableData) {
    return generateGenericTable(tableData, [
        'No', 'Kode Peminjaman', 'Peminjam', 'Barang', 'Tanggal Pinjam', 
        'Jatuh Tempo', 'Status', 'Petugas'
    ]);
}

function generateTerlambatTable(tableData) {
    return generateGenericTable(tableData, [
        'No', 'Kode Peminjaman', 'Peminjam', 'Barang', 'Tanggal Pinjam', 
        'Jatuh Tempo', 'Hari Terlambat', 'Petugas'
    ]);
}

function generateUserTable(tableData) {
    return generateGenericTable(tableData, [
        'No', 'User', 'Role', 'Status', 'Total Peminjaman', 
        'Sedang Dipinjam', 'Sudah Dikembalikan', 'Terlambat', 'Bergabung Sejak'
    ]);
}

// Generate generic table
function generateGenericTable(tableData, headers) {
    if (!tableData || tableData.length === 0) {
        return '<p class="text-center">Tidak ada data untuk ditampilkan</p>';
    }
    
    let table = '<table class="table">';
    
    // Header
    table += '<thead><tr>';
    headers.forEach(header => {
        table += `<th>${header}</th>`;
    });
    table += '</tr></thead>';
    
    // Body
    table += '<tbody>';
    tableData.forEach((row, index) => {
        table += '<tr>';
        row.forEach(cell => {
            table += `<td>${cell}</td>`;
        });
        table += '</tr>';
    });
    table += '</tbody>';
    
    table += '</table>';
    return table;
}

// Generate summary footer
function generateSummaryFooter(summary, reportType) {
    const footerContent = getFooterContent(reportType, summary);
    
    return `
        <div class="summary-footer">
            <h6>Ringkasan Laporan</h6>
            <ul>
                ${footerContent}
            </ul>
        </div>
    `;
}

// Generate print footer
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

// Helper functions
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

function getCurrentUserName() {
    // Get from session or default
    return 'Administrator';
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

function getKategoriName(kategoriId) {
    // Get from page data or return empty
    return '';
}

function getLokasiName(lokasiId) {
    // Get from page data or return empty
    return '';
}

function getStatusLabel(status) {
    const labels = {
        'pending': 'Pending',
        'dipinjam': 'Dipinjam',
        'dikembalikan': 'Dikembalikan',
        'terlambat': 'Terlambat',
        'ditolak': 'Ditolak'
    };
    return labels[status] || status;
}

function getFooterContent(reportType, summary) {
    const footers = {
        'stok': `
            <li><strong>Total Jenis Barang:</strong> ${summary.card_1?.number || 0} item</li>
            <li><strong>Total Stok:</strong> ${summary.card_2?.number || 0} unit</li>
            <li><strong>Stok Tersedia:</strong> ${summary.card_3?.number || 0} unit</li>
            <li><strong>Sedang Dipinjam:</strong> ${summary.card_4?.number || 0} unit</li>
        `,
        'peminjaman': `
            <li><strong>Total Peminjaman:</strong> ${summary.card_1?.number || 0} transaksi</li>
            <li><strong>Total Unit:</strong> ${summary.card_2?.number || 0} unit</li>
            <li><strong>Sedang Dipinjam:</strong> ${summary.card_3?.number || 0} transaksi</li>
            <li><strong>Sudah Dikembalikan:</strong> ${summary.card_4?.number || 0} transaksi</li>
        `,
        'terlambat': `
            <li><strong>Total Peminjaman Terlambat:</strong> ${summary.card_1?.number || 0} transaksi</li>
            <li><strong>Total Unit Terlambat:</strong> ${summary.card_2?.number || 0} unit</li>
            <li><strong>Rata-rata Keterlambatan:</strong> ${summary.card_3?.number || 0} hari</li>
            <li><strong>Keterlambatan Terlama:</strong> ${summary.card_4?.number || 0} hari</li>
        `,
        'user': `
            <li><strong>Total User Aktif:</strong> ${summary.card_1?.number || 0} user</li>
            <li><strong>Total Aktivitas:</strong> ${summary.card_2?.number || 0} transaksi</li>
            <li><strong>Total Unit:</strong> ${summary.card_3?.number || 0} unit</li>
            <li><strong>Sedang Dipinjam:</strong> ${summary.card_4?.number || 0} transaksi</li>
        `
    };
    
    return footers[reportType] || '<li>Tidak ada data ringkasan</li>';
}

// Export functions for global use
window.printReport = printReport; 