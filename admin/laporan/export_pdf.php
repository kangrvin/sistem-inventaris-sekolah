<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$pdo = getConnection();

// Ambil parameter filter
$type        = $_GET['type'] ?? 'stok';
$start_date  = $_GET['start_date'] ?? '';
$end_date    = $_GET['end_date'] ?? '';
$kategori_id = $_GET['kategori_id'] ?? '';
$lokasi_id   = $_GET['lokasi_id'] ?? '';
$status      = $_GET['status'] ?? '';

// Data tanda tangan
$sign_left_name  = $_GET['sign_left_name']  ?? '';
$sign_left_nip   = $_GET['sign_left_nip']   ?? '';
$sign_right_name = $_GET['sign_right_name'] ?? '';
$sign_right_nip  = $_GET['sign_right_nip']  ?? '';
$place           = $_GET['place']           ?? 'Jakarta';
$date            = $_GET['date']            ?? date('d F Y');

// --- HTML Template Awal ---
$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Laporan</title>
<style>
@page {
    margin: 40px 40px 70px 40px;
}
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #000;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 10px;
}
th, td {
    border: 1px solid #000;
    padding: 6px;
    text-align: left;
}
th {
    background: #f2f2f2;
}
h2, h3 {
    margin: 0;
    text-align: center;
}
h3 {
    margin-top: 4px;
}
p {
    margin: 4px 0;
}

/* Tanda tangan */
.signature-section {
    width: 100%;
    margin-top: 60px;
    text-align: center;
}
.signature-table {
    width: 100%;
    border: none;
    table-layout: fixed;
}
.signature-table td {
    border: none;
    text-align: center;
    vertical-align: top;
    width: 50%;
}
.signature-space {
    height: 80px;
}
.signature-name {
    font-weight: bold;
    text-decoration: underline;
    margin-bottom: 2px;
}
.signature-nip {
    font-size: 10px;
}
</style>
</head>
<body>';

// --- Header ---
$html .= '
<div style="text-align:center; margin-bottom:10px;">
    <h2>SDN Kebayoran Lama Utara 09 Pagi</h2>
    <h3>Laporan ' . htmlspecialchars(ucfirst($type)) . '</h3>
</div>';

if (!empty($start_date) || !empty($end_date)) {
    $html .= '<p style="text-align:center;">Periode: ' .
        htmlspecialchars($start_date ?: '-') . ' s/d ' .
        htmlspecialchars($end_date ?: '-') . '</p>';
}

// --- Proses Data Berdasarkan Jenis ---
if ($type === 'stok') {
    $sql = "SELECT b.kode_barang, b.nama_barang, k.nama_kategori, l.nama_lokasi,
                   b.jumlah_total, b.jumlah_tersedia, 
                   (b.jumlah_total - b.jumlah_tersedia) AS jumlah_dipinjam,
                   b.kondisi, b.tahun_pengadaan, b.harga_perolehan
            FROM barang b
            LEFT JOIN kategori k ON b.kategori_id = k.id
            LEFT JOIN lokasi l ON b.lokasi_id = l.id
            WHERE 1=1";
    $params = [];

    if (!empty($kategori_id)) {
        $sql .= " AND b.kategori_id = :kategori_id";
        $params[':kategori_id'] = $kategori_id;
    }
    if (!empty($lokasi_id)) {
        $sql .= " AND b.lokasi_id = :lokasi_id";
        $params[':lokasi_id'] = $lokasi_id;
    }
    if (!empty($status)) {
        $sql .= " AND b.kondisi = :status";
        $params[':status'] = $status;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html .= '<table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Lokasi</th>
                <th>Total</th>
                <th>Tersedia</th>
                <th>Dipinjam</th>
                <th>Kondisi</th>
                <th>Tahun</th>
                <th>Harga</th>
            </tr>
        </thead><tbody>';

    if (count($rows) === 0) {
        $html .= '<tr><td colspan="11" style="text-align:center;">Tidak ada data.</td></tr>';
    } else {
        $no = 1;
        foreach ($rows as $r) {
            $html .= "<tr>
                <td>{$no}</td>
                <td>{$r['kode_barang']}</td>
                <td>{$r['nama_barang']}</td>
                <td>{$r['nama_kategori']}</td>
                <td>{$r['nama_lokasi']}</td>
                <td>{$r['jumlah_total']}</td>
                <td>{$r['jumlah_tersedia']}</td>
                <td>{$r['jumlah_dipinjam']}</td>
                <td>{$r['kondisi']}</td>
                <td>{$r['tahun_pengadaan']}</td>
                <td>{$r['harga_perolehan']}</td>
            </tr>";
            $no++;
        }
    }
    $html .= '</tbody></table>';

} elseif ($type === 'peminjaman') {
    $sql = "SELECT p.kode_peminjaman, b.nama_barang, p.peminjam_nama, p.jumlah_pinjam,
                   p.tanggal_pinjam, p.tanggal_kembali_rencana, p.tanggal_kembali_aktual,
                   p.status, u.nama_lengkap AS petugas
            FROM peminjaman p
            LEFT JOIN barang b ON p.barang_id = b.id
            LEFT JOIN users u ON p.created_by = u.id
            LEFT JOIN kategori k ON b.kategori_id = k.id
            LEFT JOIN lokasi l ON b.lokasi_id = l.id
            WHERE 1=1";
    $params = [];

    if (!empty($start_date) && !empty($end_date)) {
        $sql .= " AND p.tanggal_pinjam BETWEEN :start AND :end";
        $params[':start'] = $start_date;
        $params[':end'] = $end_date;
    }
    if (!empty($kategori_id)) {
        $sql .= " AND b.kategori_id = :kategori_id";
        $params[':kategori_id'] = $kategori_id;
    }
    if (!empty($lokasi_id)) {
        $sql .= " AND b.lokasi_id = :lokasi_id";
        $params[':lokasi_id'] = $lokasi_id;
    }
    if (!empty($status)) {
        $sql .= " AND p.status = :status";
        $params[':status'] = $status;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html .= '<table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Peminjam</th>
                <th>Jumlah</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Kembali</th>
                <th>Status</th>
                <th>Petugas</th>
            </tr>
        </thead><tbody>';

    if (count($rows) === 0) {
        $html .= '<tr><td colspan="10" style="text-align:center;">Tidak ada data.</td></tr>';
    } else {
        $no = 1;
        foreach ($rows as $r) {
            $html .= "<tr>
                <td>{$no}</td>
                <td>{$r['kode_peminjaman']}</td>
                <td>{$r['nama_barang']}</td>
                <td>{$r['peminjam_nama']}</td>
                <td>{$r['jumlah_pinjam']}</td>
                <td>{$r['tanggal_pinjam']}</td>
                <td>{$r['tanggal_kembali_rencana']}</td>
                <td>{$r['tanggal_kembali_aktual']}</td>
                <td>{$r['status']}</td>
                <td>{$r['petugas']}</td>
            </tr>";
            $no++;
        }
    }
    $html .= '</tbody></table>';

} elseif ($type === 'terlambat') {
    $sql = "SELECT p.kode_peminjaman, b.nama_barang, p.peminjam_nama,
                   p.tanggal_pinjam, p.tanggal_kembali_rencana,
                   DATEDIFF(CURDATE(), p.tanggal_kembali_rencana) AS hari_terlambat,
                   p.status
            FROM peminjaman p
            JOIN barang b ON p.barang_id = b.id
            WHERE p.status = 'dipinjam' AND p.tanggal_kembali_rencana < CURDATE()";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html .= '<table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Peminjam</th>
                <th>Tgl Pinjam</th>
                <th>Jatuh Tempo</th>
                <th>Keterlambatan</th>
                <th>Status</th>
            </tr>
        </thead><tbody>';

    if (count($rows) === 0) {
        $html .= '<tr><td colspan="8" style="text-align:center;">Tidak ada data.</td></tr>';
    } else {
        $no = 1;
        foreach ($rows as $r) {
            $html .= "<tr>
                <td>{$no}</td>
                <td>{$r['kode_peminjaman']}</td>
                <td>{$r['nama_barang']}</td>
                <td>{$r['peminjam_nama']}</td>
                <td>{$r['tanggal_pinjam']}</td>
                <td>{$r['tanggal_kembali_rencana']}</td>
                <td>{$r['hari_terlambat']} hari</td>
                <td>{$r['status']}</td>
            </tr>";
            $no++;
        }
    }
    $html .= '</tbody></table>';

} elseif ($type === 'user') {
    $stmt = $pdo->query("SELECT username, nama_lengkap, role, status, created_at FROM users");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html .= '<table>
        <thead>
            <tr>
                <th>No</th>
                <th>Username</th>
                <th>Nama Lengkap</th>
                <th>Role</th>
                <th>Status</th>
                <th>Tgl Buat</th>
            </tr>
        </thead><tbody>';

    if (count($rows) === 0) {
        $html .= '<tr><td colspan="6" style="text-align:center;">Tidak ada data.</td></tr>';
    } else {
        $no = 1;
        foreach ($rows as $r) {
            $html .= "<tr>
                <td>{$no}</td>
                <td>{$r['username']}</td>
                <td>{$r['nama_lengkap']}</td>
                <td>{$r['role']}</td>
                <td>{$r['status']}</td>
                <td>{$r['created_at']}</td>
            </tr>";
            $no++;
        }
    }
    $html .= '</tbody></table>';

} else {
    $html .= '<p style="text-align:center;">Tipe laporan tidak dikenali.</p>';
}

// --- Tanda Tangan ---
$html .= '
<div class="signature-section">
    <p style="text-align:right; margin-right:50px;">' . htmlspecialchars($place) . ', ' . htmlspecialchars($date) . '</p>
    <table class="signature-table">
        <tr>
            <td>Mengetahui,<br>Kepala Sekolah</td>
            <td>Petugas Inventaris</td>
        </tr>
        <tr><td class="signature-space"></td><td class="signature-space"></td></tr>
        <tr>
            <td>
                <div class="signature-name">' . htmlspecialchars($sign_left_name) . '</div>
                <div class="signature-nip">NIP. ' . htmlspecialchars($sign_left_nip) . '</div>
            </td>
            <td>
                <div class="signature-name">' . htmlspecialchars($sign_right_name) . '</div>
                <div class="signature-nip">NIP. ' . htmlspecialchars($sign_right_nip) . '</div>
            </td>
        </tr>
    </table>
</div>
</body></html>';

// --- Generate PDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$pdf = new Dompdf($options);
$pdf->setPaper('A4', 'landscape'); // Ganti 'portrait' jika ingin vertikal
$pdf->loadHtml($html);
$pdf->render();

$filename = "Laporan_{$type}_" . date('YmdHis') . ".pdf";
$pdf->stream($filename, ["Attachment" => true]);
exit;
?>
