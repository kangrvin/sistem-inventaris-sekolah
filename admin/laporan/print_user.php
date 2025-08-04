<?php
/**
 * Print User Report
 */

require_once '../../config/functions.php';
require_once 'school_info.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../../auth/login.php');
    exit;
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user activity data
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.nama_lengkap,
            u.email,
            u.role,
            u.status,
            u.created_at,
            COUNT(p.id) as total_peminjaman,
            SUM(p.jumlah_pinjam) as total_unit,
            COUNT(CASE WHEN p.status = 'dipinjam' THEN 1 END) as sedang_dipinjam,
            COUNT(CASE WHEN p.status = 'dikembalikan' THEN 1 END) as sudah_dikembalikan,
            COUNT(CASE WHEN p.status = 'dipinjam' AND p.tanggal_kembali_rencana < CURDATE() THEN 1 END) as terlambat
        FROM users u
        LEFT JOIN peminjaman p ON u.id = p.created_by 
        AND p.tanggal_pinjam BETWEEN ? AND ?
        WHERE u.role IN ('admin', 'petugas')
        GROUP BY u.id, u.username, u.nama_lengkap, u.email, u.role, u.status, u.created_at
        ORDER BY total_peminjaman DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $user_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN role = 'admin' THEN 1 END) as total_admin,
            COUNT(CASE WHEN role = 'petugas' THEN 1 END) as total_petugas,
            COUNT(CASE WHEN status = 'aktif' THEN 1 END) as total_aktif,
            COUNT(CASE WHEN status = 'nonaktif' THEN 1 END) as total_nonaktif
        FROM users 
        WHERE role IN ('admin', 'petugas')
    ");
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $user_activities = [];
    $summary = ['total_users' => 0, 'total_admin' => 0, 'total_petugas' => 0, 'total_aktif' => 0, 'total_nonaktif' => 0];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Aktivitas User - <?= getSchoolInfo('name') ?></title>
    <link rel="stylesheet" href="print.css">
    <style>
        @media print {
            @page {
                margin: 1cm;
                size: A4;
            }
        }
    </style>
</head>
<body>
    <?= generatePrintHeaderHTML('Aktivitas User') ?>
    
    <!-- Filter Info -->
    <div class="print-info">
        <div class="info-row">
            <span class="info-label">Periode:</span>
            <span><?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></span>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_users']) ?></div>
            <div class="label">Total User</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_admin']) ?></div>
            <div class="label">Admin</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format($summary['total_petugas']) ?></div>
            <div class="label">Petugas</div>
        </div>
        <div class="summary-card">
            <div class="number"><?= number_format(array_sum(array_column($user_activities, 'total_peminjaman'))) ?></div>
            <div class="label">Total Aktivitas</div>
        </div>
    </div>
    
    <!-- User Activity Table -->
    <div class="card">
        <div class="card-body">
            <h5>Daftar Aktivitas User</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Total Peminjaman</th>
                            <th>Sedang Dipinjam</th>
                            <th>Sudah Dikembalikan</th>
                            <th>Terlambat</th>
                            <th>Bergabung Sejak</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_activities)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada data aktivitas user</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($user_activities as $index => $user): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong><br>
                                        <small>@<?= htmlspecialchars($user['username']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $role_class = $user['role'] == 'admin' ? 'bg-danger' : 'bg-success';
                                        $role_icon = $user['role'] == 'admin' ? 'fas fa-user-shield' : 'fas fa-user-tie';
                                        ?>
                                        <span class="badge <?= $role_class ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = $user['status'] == 'aktif' ? 'bg-success' : 'bg-warning';
                                        ?>
                                        <span class="badge <?= $status_class ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= number_format($user['total_peminjaman']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= number_format($user['sedang_dipinjam']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?= number_format($user['sudah_dikembalikan']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['terlambat'] > 0): ?>
                                            <span class="badge bg-danger">
                                                <?= number_format($user['terlambat']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Summary Footer -->
    <?php if (!empty($user_activities)): ?>
        <div class="summary-footer">
            <h6>Ringkasan Aktivitas User:</h6>
            <ul>
                <li><strong>Periode:</strong> <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></li>
                <li><strong>Total User Aktif:</strong> <?= number_format($summary['total_aktif']) ?> user</li>
                <li><strong>Total Aktivitas:</strong> <?= number_format(array_sum(array_column($user_activities, 'total_peminjaman'))) ?> transaksi</li>
                <li><strong>Total Unit:</strong> <?= number_format(array_sum(array_column($user_activities, 'total_unit'))) ?> unit</li>
                <li><strong>Sedang Dipinjam:</strong> <?= number_format(array_sum(array_column($user_activities, 'sedang_dipinjam'))) ?> transaksi</li>
                <li><strong>Sudah Dikembalikan:</strong> <?= number_format(array_sum(array_column($user_activities, 'sudah_dikembalikan'))) ?> transaksi</li>
                <li><strong>Terlambat:</strong> <?= number_format(array_sum(array_column($user_activities, 'terlambat'))) ?> transaksi</li>
            </ul>
        </div>
    <?php endif; ?>
    
    <?= generatePrintFooterHTML() ?>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html> 