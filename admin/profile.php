<?php
/**
 * Profile Admin - Sistem Inventaris Sekolah
 */

$page_title = 'Profile Admin';
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
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    
    // Validasi input
    $errors = [];
    
    if (empty($nama_lengkap)) {
        $errors[] = "Nama lengkap harus diisi";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    if (empty($username)) {
        $errors[] = "Username harus diisi";
    }
    
    // Cek username unik (kecuali untuk user yang sedang login)
    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Username sudah digunakan";
        }
    }
    
    // Cek email unik (kecuali untuk user yang sedang login)
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Email sudah digunakan";
        }
    }
    
    // Handle foto upload
    $foto = $user['foto']; // Default ke foto lama
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['foto']['type'], $allowed_types)) {
            $errors[] = "Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF";
        } elseif ($_FILES['foto']['size'] > $max_size) {
            $errors[] = "Ukuran file terlalu besar. Maksimal 2MB";
        } else {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                // Hapus foto lama jika ada
                if ($user['foto'] && file_exists($upload_dir . $user['foto'])) {
                    unlink($upload_dir . $user['foto']);
                }
                $foto = $new_filename;
            } else {
                $errors[] = "Gagal mengupload foto";
            }
        }
    }
    
    // Jika tidak ada error, update data
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET nama_lengkap = ?, email = ?, username = ?, foto = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$nama_lengkap, $email, $username, $foto, $user_id]);
            
            // Update session data
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['email'] = $email;
            $_SESSION['username'] = $username;
            
            $success = "Profile berhasil diperbarui!";
            
            // Refresh data user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(Exception $e) {
            $errors[] = "Gagal memperbarui profile: " . $e->getMessage();
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
                            <i class="fas fa-user me-2"></i>Profile Admin
                        </h2>
                        <p class="text-muted mb-0">Kelola informasi profile Anda</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="change-password.php" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Ubah Password
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
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Profile Form -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Edit Profile
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                   value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" 
                                   value="<?= ucfirst($user['role']) ?>" readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="foto" class="form-label">Foto Profile</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <input type="text" class="form-control" id="status" 
                                   value="<?= ucfirst($user['status']) ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-circle me-2"></i>Preview Profile
                </h5>
            </div>
            <div class="card-body text-center">
                <div class="mb-3">
                    <?php if ($user['foto']): ?>
                        <img src="../uploads/<?= htmlspecialchars($user['foto']) ?>" 
                             alt="Profile Photo" class="rounded-circle" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                             style="width: 150px; height: 150px;">
                            <i class="fas fa-user fa-4x text-white"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h5 class="mb-1"><?= htmlspecialchars($user['nama_lengkap']) ?></h5>
                <p class="text-muted mb-2">@<?= htmlspecialchars($user['username']) ?></p>
                
                <div class="row text-start">
                    <div class="col-12 mb-2">
                        <small class="text-muted">Email:</small><br>
                        <span><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    
                    <div class="col-12 mb-2">
                        <small class="text-muted">Role:</small><br>
                        <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
                    </div>
                    
                    <div class="col-12 mb-2">
                        <small class="text-muted">Status:</small><br>
                        <span class="badge bg-<?= $user['status'] == 'aktif' ? 'success' : 'danger' ?>">
                            <?= ucfirst($user['status']) ?>
                        </span>
                    </div>
                    
                    <div class="col-12">
                        <small class="text-muted">Bergabung sejak:</small><br>
                        <span><?= date('d F Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 