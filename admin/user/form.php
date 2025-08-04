<?php
/**
 * Form User - Admin Panel
 * Sistem Inventaris Sekolah
 */

require_once '../../config/functions.php';

$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? 0;
$page_title = ($action == 'edit' ? 'Edit User' : 'Tambah User');
require_once '../includes/header.php';

// Get user data for edit
$user = null;
if ($action == 'edit' && $user_id) {
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "<script>alert('User tidak ditemukan!'); window.location.href='index.php';</script>";
            exit();
        }
    } catch(Exception $e) {
        echo "<script>alert('Gagal memuat data user!'); window.location.href='index.php';</script>";
        exit();
    }
}

// Handle form submission
if ($_POST) {
    $username = validateInput($_POST['username']);
    $nama_lengkap = validateInput($_POST['nama_lengkap']);
    $email = validateInput($_POST['email']);
    $role = validateInput($_POST['role']);
    $status = validateInput($_POST['status']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = 'Username harus diisi!';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username minimal 3 karakter!';
    }
    
    if (empty($nama_lengkap)) {
        $errors[] = 'Nama lengkap harus diisi!';
    }
    
    if (empty($email)) {
        $errors[] = 'Email harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid!';
    }
    
    if (empty($role)) {
        $errors[] = 'Role harus dipilih!';
    }
    
    if (empty($status)) {
        $errors[] = 'Status harus dipilih!';
    }
    
    // Password validation for new user or password change
    if ($action == 'add' || !empty($password)) {
        if (empty($password)) {
            $errors[] = 'Password harus diisi!';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter!';
        } elseif ($password !== $confirm_password) {
            $errors[] = 'Konfirmasi password tidak cocok!';
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check username uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Username sudah digunakan!';
            }
            
            // Check email uniqueness
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email sudah digunakan!';
            }
            
            if (empty($errors)) {
                if ($action == 'add') {
                    // Insert new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, nama_lengkap, email, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$username, $hashed_password, $nama_lengkap, $email, $role, $status]);
                    
                    echo "<script>alert('User berhasil ditambahkan!'); window.location.href='index.php';</script>";
                    exit();
                } else {
                    // Update existing user
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET username = ?, password = ?, nama_lengkap = ?, email = ?, role = ?, status = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $hashed_password, $nama_lengkap, $email, $role, $status, $user_id]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET username = ?, nama_lengkap = ?, email = ?, role = ?, status = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$username, $nama_lengkap, $email, $role, $status, $user_id]);
                    }
                    
                    echo "<script>alert('User berhasil diupdate!'); window.location.href='index.php';</script>";
                    exit();
                }
            }
        } catch(Exception $e) {
            $errors[] = 'Terjadi kesalahan sistem!';
        }
    }
}
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2">
                            <i class="fas fa-user-plus me-2"></i><?= $page_title ?>
                        </h2>
                        <p class="text-muted mb-0">
                            <?= $action == 'edit' ? 'Edit data user sistem inventaris' : 'Tambah user baru ke sistem inventaris' ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Form Data User
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Terjadi Kesalahan:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Username -->
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-1"></i>Username <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                                   placeholder="Masukkan username" required>
                            <div class="form-text">Username minimal 3 karakter, unik</div>
                        </div>

                        <!-- Nama Lengkap -->
                        <div class="col-md-6 mb-3">
                            <label for="nama_lengkap" class="form-label">
                                <i class="fas fa-id-card me-1"></i>Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                   value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" 
                                   placeholder="Masukkan nama lengkap" required>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                   placeholder="contoh@email.com" required>
                        </div>

                        <!-- Role -->
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">
                                <i class="fas fa-user-tag me-1"></i>Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Pilih Role</option>
                                <option value="admin" <?= ($user['role'] ?? '') == 'admin' ? 'selected' : '' ?>>
                                    <i class="fas fa-user-shield"></i> Admin
                                </option>
                                <option value="petugas" <?= ($user['role'] ?? '') == 'petugas' ? 'selected' : '' ?>>
                                    <i class="fas fa-user-tie"></i> Petugas
                                </option>
                            </select>
                            <div class="form-text">
                                <strong>Admin:</strong> Akses penuh ke semua fitur<br>
                                <strong>Petugas:</strong> Input peminjaman dan kelola barang
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Pilih Status</option>
                                <option value="aktif" <?= ($user['status'] ?? '') == 'aktif' ? 'selected' : '' ?>>
                                    <i class="fas fa-check-circle"></i> Aktif
                                </option>
                                <option value="nonaktif" <?= ($user['status'] ?? '') == 'nonaktif' ? 'selected' : '' ?>>
                                    <i class="fas fa-times-circle"></i> Nonaktif
                                </option>
                            </select>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Password 
                                <?php if ($action == 'add'): ?>
                                    <span class="text-danger">*</span>
                                <?php else: ?>
                                    <small class="text-muted">(kosongkan jika tidak diubah)</small>
                                <?php endif; ?>
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="<?= $action == 'add' ? 'Masukkan password' : 'Kosongkan jika tidak diubah' ?>"
                                   <?= $action == 'add' ? 'required' : '' ?>>
                            <div class="form-text">Password minimal 6 karakter</div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Konfirmasi Password
                                <?php if ($action == 'add'): ?>
                                    <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Konfirmasi password"
                                   <?= $action == 'add' ? 'required' : '' ?>>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <hr>
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Batal
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    <?= $action == 'edit' ? 'Update User' : 'Simpan User' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak cocok!');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
});
</script>

<?php require_once '../includes/footer.php'; ?> 