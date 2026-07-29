<?php
require_once '../../config/database.php'; // ambil konfigurasi koneksi dari sistem

if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] == 0) {
    $file_tmp = $_FILES['sql_file']['tmp_name'];
    $file_name = $_FILES['sql_file']['name'];

    // Buat folder penyimpanan sementara
    $upload_path = __DIR__ . '/uploads_restore/';
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0777, true);
    }

    $target_file = $upload_path . basename($file_name);
    move_uploaded_file($file_tmp, $target_file);

    try {
        // Koneksi ke MySQL tanpa pilih database dulu
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Buat database jika belum ada
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

        // Gunakan database
        $pdo->exec("USE `" . DB_NAME . "`");

        // Jalankan semua query dari file SQL
        $templine = '';
        $lines = file($target_file);
        foreach ($lines as $line) {
            if (substr($line, 0, 2) == '--' || trim($line) == '') continue;
            $templine .= $line;
            if (substr(trim($line), -1, 1) == ';') {
                $pdo->exec($templine);
                $templine = '';
            }
        }

        echo "<script>alert('Database berhasil direstore dari file: {$file_name}'); window.location.href='index.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Gagal restore database! Error: " . addslashes($e->getMessage()) . "'); window.location.href='index.php';</script>";
    }
} else {
    echo "<script>alert('Gagal restore database! Pastikan file .sql valid.'); window.location.href='index.php';</script>";
}
?>
