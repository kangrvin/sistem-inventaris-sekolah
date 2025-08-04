<?php
/**
 * School Information for Print Headers
 */

// School information
$school_info = [
    'name' => 'SMA NEGERI 1 CONTOH',
    'address' => 'Jl. Contoh No. 123, Kota Contoh, Provinsi Contoh',
    'phone' => '(021) 1234567',
    'email' => 'info@sman1contoh.sch.id',
    'website' => 'www.sman1contoh.sch.id',
    'npsn' => '12345678',
    'logo_path' => '../../assets/images/logo.png', // Path to school logo
    'stamp_path' => '../../assets/images/stamp.png' // Path to school stamp
];

// Function to get school info
function getSchoolInfo($key = null) {
    global $school_info;
    
    if ($key) {
        return $school_info[$key] ?? '';
    }
    
    return $school_info;
}

// Function to generate school logo SVG (fallback if no image)
function generateSchoolLogoSVG() {
    return '
        <svg width="80" height="80" viewBox="0 0 80 80">
            <circle cx="40" cy="40" r="35" fill="#007bff" stroke="#000" stroke-width="2"/>
            <text x="40" y="30" text-anchor="middle" fill="white" font-size="8" font-weight="bold">SMA</text>
            <text x="40" y="42" text-anchor="middle" fill="white" font-size="8" font-weight="bold">NEGERI</text>
            <text x="40" y="54" text-anchor="middle" fill="white" font-size="8" font-weight="bold">1</text>
        </svg>
    ';
}

// Function to generate print header HTML
function generatePrintHeaderHTML($reportTitle, $reportSubtitle = 'Sistem Inventaris Sekolah') {
    $school = getSchoolInfo();
    $currentDate = date('d F Y');
    
    return '
        <div class="print-header">
            <div class="school-logo">
                ' . generateSchoolLogoSVG() . '
            </div>
            <h1 class="school-name">' . $school['name'] . '</h1>
            <p class="school-address">' . $school['address'] . '</p>
            <p class="school-address">Telepon: ' . $school['phone'] . ' | Email: ' . $school['email'] . '</p>
            <h2 class="report-title">Laporan ' . $reportTitle . '</h2>
            <p class="report-subtitle">' . $reportSubtitle . '</p>
        </div>
        
        <div class="print-info">
            <div class="info-row">
                <span class="info-label">Tanggal Cetak:</span>
                <span>' . $currentDate . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Dicetak Oleh:</span>
                <span>' . ($_SESSION['nama_lengkap'] ?? 'Administrator') . '</span>
            </div>
        </div>
    ';
}

// Function to generate print footer HTML
function generatePrintFooterHTML() {
    $currentDate = date('d F Y');
    
    return '
        <div class="print-footer">
            <p>Dicetak pada: ' . $currentDate . ' | Sistem Inventaris Sekolah v1.0</p>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Kepala Sekolah</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Petugas Inventaris</div>
            </div>
        </div>
    ';
}
?> 