<?php
/**
 * Save Settings
 */

require_once '../../config/functions.php';

// Cek jika user sudah login
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

// Cek jika bukan admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update text settings
    $settings = [
        'nama_sekolah' => $_POST['nama_sekolah'],
        'alamat_sekolah' => $_POST['alamat_sekolah'],
        'telepon_sekolah' => $_POST['telepon_sekolah'],
        'email_sekolah' => $_POST['email_sekolah'],
        'maksimal_peminjaman' => $_POST['maksimal_peminjaman'],
        'denda_terlambat' => $_POST['denda_terlambat']
    ];
    
    foreach($settings as $key => $value) {
        $stmt = $pdo->prepare("UPDATE pengaturan SET nilai = ? WHERE nama_pengaturan = ?");
        $stmt->execute([$value, $key]);
    }
    
    $success_message = 'Pengaturan berhasil disimpan!';
    
} catch(Exception $e) {
    $error_message = 'Gagal menyimpan pengaturan: ' . $e->getMessage();
}

// Redirect dengan JavaScript alert
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
</head>
<body>
    <script>
        <?php if (isset($success_message)): ?>
            alert('<?= addslashes($success_message) ?>');
        <?php elseif (isset($error_message)): ?>
            alert('<?= addslashes($error_message) ?>');
        <?php endif; ?>
        window.location.href = 'index.php';
    </script>
</body>
</html>
<?php
exit();