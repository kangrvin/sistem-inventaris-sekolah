<?php
/**
 * Change Password Petugas - Sistem Inventaris Sekolah
 */

$page_title = 'Ubah Password';
require_once 'includes/header.php';

// Ambil data user yang sedang login
$user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=inventaris_sekolah", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<script>alert('User tidak ditemukan!'); window.location.href='dashboard.php';</script>";
        exit();
    }
    
} catch(Exception $e) {
    echo "<script>alert('Terjadi kesalahan sistem!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = "Password saat ini harus diisi";
    }
    
    if (empty($new_password)) {
        $errors[] = "Password baru harus diisi";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Password baru minimal 6 karakter";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Konfirmasi password harus diisi";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok";
    }
    
    // Cek password saat ini
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Password saat ini salah";
        }
    }
    
    // Cek password baru tidak sama dengan password lama
    if (!empty($new_password) && password_verify($new_password, $user['password'])) {
        $errors[] = "Password baru tidak boleh sama dengan password lama";
    }
    
    // Jika tidak ada error, update password
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$hashed_password, $user_id]);
            
            $success = "Password berhasil diubah! Silakan login kembali.";
            
            // Redirect ke logout setelah 2 detik
            echo "<script>
                setTimeout(function() {
                    window.location.href = '../auth/logout.php';
                }, 2000);
            </script>";
            
        } catch(Exception $e) {
            $errors[] = "Gagal mengubah password: " . $e->getMessage();
        }
    }
}
?>

<!-- Header Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-2 fw-bold">
                            <i class="fas fa-key me-2 text-primary"></i>Ubah Password
                        </h2>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Ganti password akun Anda dengan yang baru
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="profile.php" class="btn btn-primary shadow-sm">
                            <i class="fas fa-user me-2"></i>Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= htmlspecialchars($success) ?>
        <p class="mb-0 mt-2">Anda akan dialihkan ke halaman login dalam beberapa detik...</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Change Password Form -->
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-lock me-2 text-primary"></i>Form Ubah Password
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="changePasswordForm">
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">Password Saat Ini <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye" id="current_password_icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye" id="new_password_icon"></i>
                            </button>
                        </div>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-semibold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="confirm_password_icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tips Keamanan Password:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol</li>
                                <li>Jangan gunakan informasi pribadi seperti nama atau tanggal lahir</li>
                                <li>Gunakan password yang berbeda untuk setiap akun</li>
                                <li>Ganti password secara berkala</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning shadow-sm">
                            <i class="fas fa-key me-2"></i>Ubah Password
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Password Strength Indicator -->
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-shield-alt me-2 text-primary"></i>Kekuatan Password
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <small class="text-muted" id="passwordStrengthText">Masukkan password baru untuk melihat kekuatan</small>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '_icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthText = document.getElementById('passwordStrengthText');
    
    let strength = 0;
    let feedback = '';
    
    if (password.length >= 6) strength += 20;
    if (password.length >= 8) strength += 20;
    if (/[a-z]/.test(password)) strength += 20;
    if (/[A-Z]/.test(password)) strength += 20;
    if (/[0-9]/.test(password)) strength += 20;
    if (/[^A-Za-z0-9]/.test(password)) strength += 20;
    
    // Set progress bar
    strengthBar.style.width = strength + '%';
    
    // Set color and text
    if (strength <= 20) {
        strengthBar.className = 'progress-bar bg-danger';
        feedback = 'Sangat Lemah';
    } else if (strength <= 40) {
        strengthBar.className = 'progress-bar bg-warning';
        feedback = 'Lemah';
    } else if (strength <= 60) {
        strengthBar.className = 'progress-bar bg-info';
        feedback = 'Sedang';
    } else if (strength <= 80) {
        strengthBar.className = 'progress-bar bg-primary';
        feedback = 'Kuat';
    } else {
        strengthBar.className = 'progress-bar bg-success';
        feedback = 'Sangat Kuat';
    }
    
    strengthText.textContent = feedback;
});

// Form validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Konfirmasi password tidak cocok!');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Password baru minimal 6 karakter!');
        return false;
    }
    
    return confirm('Apakah Anda yakin ingin mengubah password?');
});
</script>

<?php require_once 'includes/footer.php'; ?> 