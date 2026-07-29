<?php
// Konfigurasi koneksi database
$host = 'localhost';
$user = 'root';
$pass = ''; // kosongkan jika tanpa password
$dbname = 'inventaris_sekolah';

// Tentukan lokasi mysqldump.exe (sesuaikan dengan Laragon/XAMPP kamu)
$mysqldumpPath = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe'; 
// ⚠️ Ganti versi MySQL di atas sesuai yang kamu punya (cek di folder Laragon > bin > mysql)

// Nama file hasil backup
$backupFile = __DIR__ . '/backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';

// Jika ada password, gunakan format dengan --password=
if ($pass != '') {
    $command = "\"{$mysqldumpPath}\" --user={$user} --password={$pass} --host={$host} {$dbname} > \"{$backupFile}\"";
} else {
    $command = "\"{$mysqldumpPath}\" --user={$user} --host={$host} {$dbname} > \"{$backupFile}\"";
}

// Jalankan perintah
system($command, $output);

// Cek apakah file berhasil dibuat dan tidak kosong
if (file_exists($backupFile) && filesize($backupFile) > 0) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
    header('Content-Length: ' . filesize($backupFile));
    readfile($backupFile);
    unlink($backupFile);
    exit;
} else {
    echo "<script>alert('Backup gagal! Periksa path mysqldump.exe dan izin akses.'); window.location.href='index.php';</script>";
}
?>
