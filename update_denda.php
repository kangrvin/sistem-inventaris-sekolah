<?php
/**
 * Update Denda Setting
 */

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current denda setting
    $stmt = $pdo->prepare("SELECT nilai FROM pengaturan WHERE nama_pengaturan = 'denda_terlambat'");
    $stmt->execute();
    $current_denda = $stmt->fetchColumn();
    
    echo "💰 <strong>Pengaturan Denda Saat Ini:</strong>\n";
    echo "Denda per hari: Rp " . number_format($current_denda) . "\n\n";
    
    // Update denda to 5000 (contoh)
    $new_denda = 5000;
    
    $stmt = $pdo->prepare("UPDATE pengaturan SET nilai = ? WHERE nama_pengaturan = 'denda_terlambat'");
    if ($stmt->execute([$new_denda])) {
        echo "✅ <strong>Denda berhasil diupdate!</strong>\n";
        echo "Denda baru: Rp " . number_format($new_denda) . " per hari\n";
        echo "📝 <em>Silakan cek di admin/pengaturan untuk mengubah denda sesuai kebutuhan</em>\n";
    } else {
        echo "❌ Gagal mengupdate denda\n";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 