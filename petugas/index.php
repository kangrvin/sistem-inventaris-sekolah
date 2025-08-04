<?php
/**
 * Router Petugas - Sistem Inventaris Sekolah
 */

session_start();

// Cek login dan role petugas
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header('Location: ../auth/login.php');
    exit();
}

// Router untuk modul petugas
$module = isset($_GET['module']) ? $_GET['module'] : '';

// Redirect ke dashboard jika tidak ada modul yang dipilih
if (empty($module)) {
    include 'dashboard.php';
    exit();
}

// Validasi modul yang diizinkan untuk petugas
$allowed_modules = ['peminjaman', 'barang', 'laporan'];

if (!in_array($module, $allowed_modules)) {
    header('Location: dashboard.php');
    exit();
}

// Include file modul yang sesuai
$module_file = $module . '/index.php';
if (file_exists($module_file)) {
    include $module_file;
} else {
    header('Location: dashboard.php');
    exit();
}
?> 