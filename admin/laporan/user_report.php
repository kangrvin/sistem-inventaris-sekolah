<?php
/**
 * Laporan Aktivitas User
 */

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
    
    // Get top active users
    $stmt = $pdo->prepare("
        SELECT 
            u.nama_lengkap,
            u.role,
            COUNT(p.id) as total_aktivitas
        FROM users u
        LEFT JOIN peminjaman p ON u.id = p.created_by 
        AND p.tanggal_pinjam BETWEEN ? AND ?
        WHERE u.role IN ('admin', 'petugas')
        GROUP BY u.id, u.nama_lengkap, u.role
        ORDER BY total_aktivitas DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $user_activities = [];
    $summary = ['total_users' => 0, 'total_admin' => 0, 'total_petugas' => 0, 'total_aktif' => 0, 'total_nonaktif' => 0];
    $top_users = [];
}
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_users']) ?></h4>
                <small>Total User</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_admin']) ?></h4>
                <small>Admin</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_petugas']) ?></h4>
                <small>Petugas</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_aktif']) ?></h4>
                <small>User Aktif</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format($summary['total_nonaktif']) ?></h4>
                <small>User Nonaktif</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4 class="mb-0"><?= number_format(array_sum(array_column($user_activities, 'total_peminjaman'))) ?></h4>
                <small>Total Aktivitas</small>
            </div>
        </div>
    </div>
</div>

<!-- Top Users & Activity Chart -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-trophy me-2"></i>User Teraktif</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($top_users)): ?>
                    <?php foreach ($top_users as $index => $user): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <span class="text-white fw-bold"><?= $index + 1 ?></span>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($user['nama_lengkap']) ?></h6>
                                    <small class="text-muted">
                                        <?= ucfirst($user['role']) ?>
                                    </small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary fs-6">
                                    <?= number_format($user['total_aktivitas']) ?> aktivitas
                                </span>
                            </div>
                        </div>
                        <?php if ($index < count($top_users) - 1): ?>
                            <hr class="my-2">
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Tidak ada data aktivitas</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Distribusi Aktivitas</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-primary"><?= number_format(array_sum(array_column($user_activities, 'sedang_dipinjam'))) ?></h4>
                            <small class="text-muted">Sedang Dipinjam</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-success"><?= number_format(array_sum(array_column($user_activities, 'sudah_dikembalikan'))) ?></h4>
                            <small class="text-muted">Sudah Dikembalikan</small>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-danger"><?= number_format(array_sum(array_column($user_activities, 'terlambat'))) ?></h4>
                            <small class="text-muted">Terlambat</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="text-info"><?= number_format(array_sum(array_column($user_activities, 'total_unit'))) ?></h4>
                            <small class="text-muted">Total Unit</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Activity Table -->
<div class="table-responsive">
    <table class="table table-hover" id="userActivityTable">
        <thead class="table-light">
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
                    <td colspan="9" class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Tidak ada data aktivitas user</h5>
                        <p class="text-muted">Tidak ditemukan aktivitas user dalam periode yang dipilih</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($user_activities as $index => $user): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($user['nama_lengkap']) ?></h6>
                                    <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            $role_class = $user['role'] == 'admin' ? 'bg-danger' : 'bg-success';
                            $role_icon = $user['role'] == 'admin' ? 'fas fa-user-shield' : 'fas fa-user-tie';
                            ?>
                            <span class="badge <?= $role_class ?>">
                                <i class="<?= $role_icon ?> me-1"></i><?= ucfirst($user['role']) ?>
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
                            <span class="badge bg-primary fs-6">
                                <?= number_format($user['total_peminjaman']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info fs-6">
                                <?= number_format($user['sedang_dipinjam']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-success fs-6">
                                <?= number_format($user['sudah_dikembalikan']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['terlambat'] > 0): ?>
                                <span class="badge bg-danger fs-6">
                                    <?= number_format($user['terlambat']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary fs-6">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Summary Footer -->
<?php if (!empty($user_activities)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle me-2"></i>Ringkasan Aktivitas User:</h6>
                <ul class="mb-0">
                    <li><strong>Periode:</strong> <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></li>
                    <li><strong>Total User Aktif:</strong> <?= number_format($summary['total_aktif']) ?> user</li>
                    <li><strong>Total Aktivitas:</strong> <?= number_format(array_sum(array_column($user_activities, 'total_peminjaman'))) ?> transaksi</li>
                    <li><strong>Total Unit:</strong> <?= number_format(array_sum(array_column($user_activities, 'total_unit'))) ?> unit</li>
                    <li><strong>Sedang Dipinjam:</strong> <?= number_format(array_sum(array_column($user_activities, 'sedang_dipinjam'))) ?> transaksi</li>
                    <li><strong>Sudah Dikembalikan:</strong> <?= number_format(array_sum(array_column($user_activities, 'sudah_dikembalikan'))) ?> transaksi</li>
                    <li><strong>Terlambat:</strong> <?= number_format(array_sum(array_column($user_activities, 'terlambat'))) ?> transaksi</li>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?> 