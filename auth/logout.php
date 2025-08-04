<?php
/**
 * Halaman Logout
 * Sistem Inventaris Sekolah
 */

session_start();
require_once '../config/functions.php';

// Log aktivitas logout jika user sudah login
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'logout', 'User logout dari sistem');
}

// Hapus semua session
session_destroy();

// Redirect ke halaman login
redirect('login.php');
?> 