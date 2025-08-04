<?php
/**
 * Admin Dashboard - Sistem Inventaris Sekolah
 */

session_start();

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Handle actions untuk semua modul
$action = $_GET['action'] ?? '';
$module = $_GET['module'] ?? '';

// Mapping modul ke folder
$module_folders = [
    'kategori' => 'kategori',
    'lokasi' => 'lokasi', 
    'peminjaman' => 'peminjaman',
    'user' => 'user',
    'laporan' => 'laporan',
    'pengaturan' => 'pengaturan',
    'barang' => 'barang'
];

if ($module && isset($module_folders[$module]) && ($action == 'add' || $action == 'edit')) {
    // Redirect ke folder modul yang sesuai
    $folder = $module_folders[$module];
    header('Location: ' . $folder . '/?action=' . $action . (isset($_GET['id']) ? '&id=' . $_GET['id'] : ''));
    exit();
} elseif ($action == 'add' || $action == 'edit') {
    // Default redirect ke folder barang
    header('Location: barang/?action=' . $action . (isset($_GET['id']) ? '&id=' . $_GET['id'] : ''));
    exit();
}

// Include dashboard langsung
include 'dashboard.php';
?> 