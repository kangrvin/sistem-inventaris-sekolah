<?php
/**
 * Functions - Sistem Inventaris Sekolah
 * Berisi semua fungsi helper untuk aplikasi
 */

require_once 'database.php';

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Cek apakah user adalah admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Cek apakah user adalah petugas
 */
function isPetugas() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'petugas');
}

/**
 * Cek apakah user adalah user biasa
 */
function isUser() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

/**
 * Redirect ke halaman tertentu
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generate kode unik
 */
function generateUniqueCode($prefix = '', $length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = $prefix;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

/**
 * Upload file
 */
function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        return false;
    }
    
    $filename = uniqid() . '.' . $extension;
    $filepath = $destination . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return false;
    }
    
    return $filename;
}

/**
 * Format tanggal Indonesia
 */
function formatTanggal($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Validasi stok barang
 */
function validateStock($barang_id, $jumlah) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT jumlah_tersedia FROM barang WHERE id = ?");
        $stmt->execute([$barang_id]);
        $barang = $stmt->fetch();
        
        return $barang && $barang['jumlah_tersedia'] >= $jumlah;
    } catch(Exception $e) {
        return false;
    }
}

/**
 * Ambil statistik dashboard
 */
function getDashboardStats() {
    try {
        $pdo = getConnection();
        
        // Total barang
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang");
        $total_barang = $stmt->fetch()['total'];
        
        // Peminjaman aktif
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'");
        $peminjaman_aktif = $stmt->fetch()['total'];
        
        // Total kategori
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM kategori");
        $total_kategori = $stmt->fetch()['total'];
        
        // Barang rusak
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM barang WHERE kondisi = 'rusak'");
        $barang_rusak = $stmt->fetch()['total'];
        
        return [
            'total_barang' => $total_barang,
            'peminjaman_aktif' => $peminjaman_aktif,
            'total_kategori' => $total_kategori,
            'barang_rusak' => $barang_rusak
        ];
    } catch(Exception $e) {
        return [
            'total_barang' => 0,
            'peminjaman_aktif' => 0,
            'total_kategori' => 0,
            'barang_rusak' => 0
        ];
    }
}

/**
 * Validasi input
 */
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log activity
 */
function logActivity($user_id, $action, $description = '') {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO riwayat_barang (user_id, action, description, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action, $description]);
    } catch(Exception $e) {
        // Log error silently
    }
}

/**
 * Get pengaturan
 */
function getPengaturan($key) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT nilai FROM pengaturan WHERE kunci = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['nilai'] : null;
    } catch(Exception $e) {
        return null;
    }
}

/**
 * Update pengaturan
 */
function updatePengaturan($key, $value) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO pengaturan (kunci, nilai, updated_at) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE nilai = ?, updated_at = NOW()
        ");
        return $stmt->execute([$key, $value, $value]);
    } catch(Exception $e) {
        return false;
    }
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

/**
 * Check if file is image
 */
function isImage($file) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    return in_array($file['type'], $allowedTypes);
}

/**
 * Resize image
 */
function resizeImage($source, $destination, $width, $height) {
    $info = getimagesize($source);
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    $resized = imagecreatetruecolor($width, $height);
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
    
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($resized, $destination, 90);
            break;
        case 'image/png':
            imagepng($resized, $destination, 9);
            break;
        case 'image/gif':
            imagegif($resized, $destination);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($resized);
    
    return true;
}
?> 