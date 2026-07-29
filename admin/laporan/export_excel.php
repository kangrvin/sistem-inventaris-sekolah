<?php
// export_excel.php
// Pastikan file ini berada di folder yang sama dengan index.php

require_once __DIR__ . '/../../config/database.php'; // sesuaikan jika path beda
$pdo = getConnection();

// Ambil parameter filter
$type       = $_GET['type'] ?? 'stok';
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';
$kategori_id= $_GET['kategori_id'] ?? '';
$lokasi_id  = $_GET['lokasi_id'] ?? '';
$status     = $_GET['status'] ?? '';

// Nama file
$filename = "Laporan_{$type}_" . date('YmdHis') . ".xls";

// Headers untuk Excel (XLS via HTML table)
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

// Fungsi bantu output tabel
function start_table() {
    echo "<table border='1'>";
}
function end_table() {
    echo "</table>";
}
function tr_th($cols) {
    echo "<tr>";
    foreach($cols as $c) echo "<th>" . htmlspecialchars($c) . "</th>";
    echo "</tr>";
}
function tr_td($row) {
    echo "<tr>";
    foreach ($row as $c) {
        echo "<td>" . htmlspecialchars($c ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
    }
    echo "</tr>";
}


// Pilih query berdasarkan type
if ($type === 'stok') {
    // gunakan view v_stok_barang jika ada
    $sql = "SELECT kode_barang, nama_barang, nama_kategori, nama_lokasi, jumlah_total, jumlah_tersedia, (jumlah_total - jumlah_tersedia) AS jumlah_dipinjam, kondisi, tahun_pengadaan, harga_perolehan
            FROM v_stok_barang WHERE 1=1";
    $params = [];

    if (!empty($kategori_id)) {
        $sql .= " AND id IN (SELECT b.id FROM barang b WHERE b.kategori_id = :kategori_id)";
        $params[':kategori_id'] = $kategori_id;
    }
    if (!empty($lokasi_id)) {
        $sql .= " AND id IN (SELECT b.id FROM barang b WHERE b.lokasi_id = :lokasi_id)";
        $params[':lokasi_id'] = $lokasi_id;
    }
    if (!empty($status)) {
        // asumsi status di barang adalah kondisi / atau field status — kalau beda sesuaikan
        $sql .= " AND kondisi = :kondisi";
        $params[':kondisi'] = $status;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    start_table();
    tr_th(['No','Kode Barang','Nama Barang','Kategori','Lokasi','Jumlah Total','Jumlah Tersedia','Jumlah Dipinjam','Kondisi','Tahun','Harga Perolehan']);
    $no = 1;
    foreach($rows as $r) {
        tr_td([$no, $r['kode_barang'],$r['nama_barang'],$r['nama_kategori'],$r['nama_lokasi'],$r['jumlah_total'],$r['jumlah_tersedia'],$r['jumlah_dipinjam'],$r['kondisi'],$r['tahun_pengadaan'],$r['harga_perolehan']]);
        $no++;
    }
    end_table();
    exit;
}

if ($type === 'peminjaman') {
    $sql = "SELECT p.kode_peminjaman, b.nama_barang, p.peminjam_nama, p.jumlah_pinjam, p.tanggal_pinjam, p.tanggal_kembali_rencana, p.tanggal_kembali_aktual, p.status, u.nama_lengkap AS petugas
            FROM peminjaman p
            JOIN barang b ON p.barang_id = b.id
            JOIN users u ON p.created_by = u.id
            WHERE 1=1";
    $params = [];
    if (!empty($start_date) && !empty($end_date)) {
        $sql .= " AND p.tanggal_pinjam BETWEEN :start AND :end";
        $params[':start'] = $start_date;
        $params[':end'] = $end_date;
    }
    if (!empty($status)) {
        $sql .= " AND p.status = :status";
        $params[':status'] = $status;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    start_table();
    tr_th(['No','Kode Peminjaman','Barang','Peminjam','Jumlah','Tanggal Pinjam','Tgl Kembali Rencana','Tgl Kembali Aktual','Status','Petugas']);
    $no = 1;
    foreach($rows as $r) {
        tr_td([$no, $r['kode_peminjaman'], $r['nama_barang'], $r['peminjam_nama'], $r['jumlah_pinjam'], $r['tanggal_pinjam'], $r['tanggal_kembali_rencana'], $r['tanggal_kembali_aktual'], $r['status'], $r['petugas']]);
        $no++;
    }
    end_table();
    exit;
}

if ($type === 'terlambat') {
    // gunakan view v_laporan_peminjaman atau sesuaikan query
    $sql = "SELECT kode_peminjaman, nama_barang, peminjam_nama, tanggal_pinjam, tanggal_kembali_rencana, DATEDIFF(CURDATE(), tanggal_kembali_rencana) AS hari_terlambat, status
            FROM v_laporan_peminjaman
            WHERE status = 'dipinjam' AND tanggal_kembali_rencana < CURDATE()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    start_table();
    tr_th(['No','Kode Peminjaman','Barang','Peminjam','Tanggal Pinjam','Jatuh Tempo','Hari Terlambat','Status']);
    $no = 1;
    foreach($rows as $r) {
        tr_td([$no, $r['kode_peminjaman'], $r['nama_barang'], $r['peminjam_nama'], $r['tanggal_pinjam'], $r['tanggal_kembali_rencana'], $r['hari_terlambat'], $r['status']]);
        $no++;
    }
    end_table();
    exit;
}

if ($type === 'user') {
    $sql = "SELECT username, nama_lengkap, role, status, created_at FROM users";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    start_table();
    tr_th(['No','Username','Nama Lengkap','Role','Status','Bergabung Sejak']);
    $no = 1;
    foreach($rows as $r) {
        tr_td([$no, $r['username'], $r['nama_lengkap'], $r['role'], $r['status'], $r['created_at']]);
        $no++;
    }
    end_table();
    exit;
}

// default
echo "Jenis export tidak dikenali.";
exit;
