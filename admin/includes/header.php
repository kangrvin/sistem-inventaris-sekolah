<?php
/**
 * Header Admin - Sistem Inventaris Sekolah
 */

session_start();

// Cek path yang benar berdasarkan lokasi file yang memanggil
$current_dir = dirname($_SERVER['SCRIPT_NAME']);
if (strpos($current_dir, '/admin/barang') !== false || 
    strpos($current_dir, '/admin/kategori') !== false ||
    strpos($current_dir, '/admin/lokasi') !== false ||
    strpos($current_dir, '/admin/peminjaman') !== false ||
    strpos($current_dir, '/admin/user') !== false ||
    strpos($current_dir, '/admin/laporan') !== false ||
    strpos($current_dir, '/admin/pengaturan') !== false) {
    require_once '../../config/functions.php';
} else {
    require_once '../config/functions.php';
}

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

$page_title = isset($page_title) ? $page_title : 'Dashboard Admin';
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Ambil data user untuk ditampilkan
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['nama_lengkap'];
$user_role = $_SESSION['role'];
$user_email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $page_title ?> - Admin Panel</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.0/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
    
    <!-- Chart.js JavaScript Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #3730a3;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --gray-color: #6b7280;
            --sidebar-width: 280px;
            --header-height: 70px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-brand:hover {
            color: white;
            text-decoration: none;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 25px 25px 0;
            margin-right: 20px;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
            font-weight: 600;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        /* Header */
        .top-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .header-content {
            margin-left: 15px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.2;
        }
        
        .breadcrumb-nav {
            margin-top: 5px;
        }
        
        .breadcrumb {
            margin: 0;
            padding: 0;
            font-size: 0.875rem;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .user-details h6 {
            margin: 0;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .user-details small {
            color: var(--gray-color);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .dropdown-item {
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dropdown-item:hover {
            background: var(--light-color);
        }
        
        /* Content Area */
        .content-wrapper {
            padding: 30px;
        }
        
        /* Mobile Toggle Button */
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--dark-color);
            font-size: 1.5rem;
            padding: 10px;
            cursor: pointer;
        }
        
        /* Mobile Overlay */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .mobile-overlay.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .content-wrapper {
                padding: 15px;
            }
            
            .top-header {
                padding: 8px 15px;
                flex-wrap: nowrap;
                gap: 0;
            }
            
            .header-left {
                flex: 1;
                min-width: 0;
                display: flex;
                align-items: center;
            }
            
            .header-content {
                margin-left: 10px;
                min-width: 0;
                flex: 1;
            }
            
            .page-title {
                font-size: 1.1rem;
                line-height: 1.2;
                word-wrap: break-word;
                margin: 0;
            }
            
            .breadcrumb-nav {
                margin-top: 2px;
            }
            
            .breadcrumb {
                font-size: 0.7rem;
                margin: 0;
                padding: 0;
            }
            
            .header-right {
                margin-left: 8px;
                flex-shrink: 0;
            }
            
            .stat-card {
                padding: 20px;
                margin-bottom: 15px;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .user-dropdown {
                margin-left: auto;
            }
            
            .dropdown-menu {
                position: fixed !important;
                top: 70px !important;
                left: 15px !important;
                right: 15px !important;
                width: auto !important;
                transform: none !important;
            }
            
            /* Table responsive */
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .table td, .table th {
                padding: 0.5rem;
            }
            
            /* Card responsive */
            .card {
                margin-bottom: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            /* Button responsive */
            .btn {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            
            /* Modal responsive */
            .modal-dialog {
                margin: 10px;
            }
            
            .modal-body {
                padding: 15px;
            }
            
            /* Chart responsive */
            canvas {
                max-height: 300px !important;
            }
        }
        
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 10px;
            }
            
            .top-header {
                padding: 6px 12px;
                flex-direction: row;
                align-items: center;
                gap: 0;
            }
            
            .header-left {
                flex: 1;
                display: flex;
                align-items: center;
                min-width: 0;
            }
            
            .header-content {
                margin-left: 8px;
                flex: 1;
                min-width: 0;
            }
            
            .page-title {
                font-size: 1rem;
                line-height: 1.1;
                word-break: break-word;
                margin: 0;
            }
            
            .breadcrumb-nav {
                margin-top: 1px;
            }
            
            .breadcrumb {
                font-size: 0.65rem;
                margin: 0;
                padding: 0;
            }
            
            .header-right {
                margin-left: 6px;
                flex-shrink: 0;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .stat-number {
                font-size: 1.25rem;
            }
            
            .table-responsive {
                font-size: 0.75rem;
            }
            
            .btn {
                font-size: 0.75rem;
                padding: 0.375rem 0.75rem;
            }
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--gray-color);
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }
        
        .stat-icon.primary { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
        }
        .stat-icon.success { 
            background: linear-gradient(135deg, var(--success-color), #059669); 
        }
        .stat-icon.warning { 
            background: linear-gradient(135deg, var(--warning-color), #d97706); 
        }
        .stat-icon.danger { 
            background: linear-gradient(135deg, var(--danger-color), #dc2626); 
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .stat-label {
            color: var(--gray-color);
            font-weight: 500;
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="<?= strpos($current_dir, '/admin/barang') !== false || 
                       strpos($current_dir, '/admin/kategori') !== false ||
                       strpos($current_dir, '/admin/lokasi') !== false ||
                       strpos($current_dir, '/admin/peminjaman') !== false ||
                       strpos($current_dir, '/admin/user') !== false ||
                       strpos($current_dir, '/admin/laporan') !== false ||
                       strpos($current_dir, '/admin/pengaturan') !== false ? '../dashboard.php' : 'dashboard.php' ?>" class="sidebar-brand">
                <i class="fas fa-school"></i>
                Admin Panel
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page == 'dashboard' ? 'active' : '' ?>" 
                       href="<?= strpos($current_dir, '/admin/barang') !== false || 
                               strpos($current_dir, '/admin/kategori') !== false ||
                               strpos($current_dir, '/admin/lokasi') !== false ||
                               strpos($current_dir, '/admin/peminjaman') !== false ||
                               strpos($current_dir, '/admin/user') !== false ||
                               strpos($current_dir, '/admin/laporan') !== false ||
                               strpos($current_dir, '/admin/pengaturan') !== false ? '../dashboard.php' : 'dashboard.php' ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/barang') !== false ? 'active' : '' ?>" 
                       href="<?= strpos($current_dir, '/admin/barang') !== false ? './' : 
                               (strpos($current_dir, '/admin/kategori') !== false ? '../barang/' : 
                               (strpos($current_dir, '/admin/lokasi') !== false ? '../barang/' :
                               (strpos($current_dir, '/admin/peminjaman') !== false ? '../barang/' :
                               (strpos($current_dir, '/admin/user') !== false ? '../barang/' :
                               (strpos($current_dir, '/admin/laporan') !== false ? '../barang/' :
                               (strpos($current_dir, '/admin/pengaturan') !== false ? '../barang/' : 'barang/')))))) ?>">
                        <i class="fas fa-boxes"></i>
                        Data Barang
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/kategori') !== false ? 'active' : '' ?>" 
                       href="<?= strpos($current_dir, '/admin/barang') !== false ? '../kategori/' : 
                               (strpos($current_dir, '/admin/kategori') !== false ? './' : 
                               (strpos($current_dir, '/admin/lokasi') !== false ? '../kategori/' :
                               (strpos($current_dir, '/admin/peminjaman') !== false ? '../kategori/' :
                               (strpos($current_dir, '/admin/user') !== false ? '../kategori/' :
                               (strpos($current_dir, '/admin/laporan') !== false ? '../kategori/' :
                               (strpos($current_dir, '/admin/pengaturan') !== false ? '../kategori/' : 'kategori/')))))) ?>">
                        <i class="fas fa-tags"></i>
                        Kategori
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/lokasi') !== false ? 'active' : '' ?>" 
                       href="<?= strpos($current_dir, '/admin/barang') !== false || strpos($current_dir, '/admin/kategori') !== false ? '../lokasi/' : 
                               (strpos($current_dir, '/admin/lokasi') !== false ? './' : 
                               (strpos($current_dir, '/admin/peminjaman') !== false ? '../lokasi/' :
                               (strpos($current_dir, '/admin/user') !== false ? '../lokasi/' :
                               (strpos($current_dir, '/admin/laporan') !== false ? '../lokasi/' :
                               (strpos($current_dir, '/admin/pengaturan') !== false ? '../lokasi/' : 'lokasi/'))))) ?>">
                        <i class="fas fa-map-marker-alt"></i>
                        Lokasi Barang
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/peminjaman') !== false ? 'active' : '' ?>" 
                       href="<?= strpos($current_dir, '/admin/barang') !== false || strpos($current_dir, '/admin/kategori') !== false ? '../peminjaman/' : 
                               (strpos($current_dir, '/admin/lokasi') !== false ? '../peminjaman/' :
                               (strpos($current_dir, '/admin/peminjaman') !== false ? './' : 
                               (strpos($current_dir, '/admin/user') !== false ? '../peminjaman/' :
                               (strpos($current_dir, '/admin/laporan') !== false ? '../peminjaman/' :
                               (strpos($current_dir, '/admin/pengaturan') !== false ? '../peminjaman/' : 'peminjaman/'))))) ?>">
                        <i class="fas fa-handshake"></i>
                        Peminjaman
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/user') !== false ? 'active' : '' ?>" 
                       href="<?= strpos($current_dir, '/admin/barang') !== false || strpos($current_dir, '/admin/kategori') !== false ? '../user/' : 
                               (strpos($current_dir, '/admin/lokasi') !== false ? '../user/' :
                               (strpos($current_dir, '/admin/peminjaman') !== false ? '../user/' :
                               (strpos($current_dir, '/admin/user') !== false ? './' : 
                               (strpos($current_dir, '/admin/laporan') !== false ? '../user/' :
                               (strpos($current_dir, '/admin/pengaturan') !== false ? '../user/' : 'user/'))))) ?>">
                        <i class="fas fa-users"></i>
                        Kelola User
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/laporan') !== false ? 'active' : '' ?>" 
                       href="<?= strpos($current_dir, '/admin/barang') !== false || strpos($current_dir, '/admin/kategori') !== false ? '../laporan/' : 
                               (strpos($current_dir, '/admin/lokasi') !== false ? '../laporan/' :
                               (strpos($current_dir, '/admin/peminjaman') !== false ? '../laporan/' :
                               (strpos($current_dir, '/admin/user') !== false ? '../laporan/' :
                               (strpos($current_dir, '/admin/laporan') !== false ? './' : 
                               (strpos($current_dir, '/admin/pengaturan') !== false ? '../laporan/' : 'laporan/'))))) ?>">
                        <i class="fas fa-chart-bar"></i>
                        Laporan
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/pengaturan') !== false ? 'active' : '' ?>" 
                       href="<?= strpos($current_dir, '/admin/barang') !== false || strpos($current_dir, '/admin/kategori') !== false ? '../pengaturan/' : 
                               (strpos($current_dir, '/admin/lokasi') !== false ? '../pengaturan/' :
                               (strpos($current_dir, '/admin/peminjaman') !== false ? '../pengaturan/' :
                               (strpos($current_dir, '/admin/user') !== false ? '../pengaturan/' :
                               (strpos($current_dir, '/admin/laporan') !== false ? '../pengaturan/' :
                               (strpos($current_dir, '/admin/pengaturan') !== false ? './' : 'pengaturan/'))))) ?>">
                        <i class="fas fa-cog"></i>
                        Pengaturan
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer" style="position: absolute; bottom: 20px; left: 20px; right: 20px;">
            <a href="https://youtube.com/@klikkoding?si=rDnT-8jmno-88mx2" target="_blank" class="text-white text-decoration-none">
                <small><i class="fas fa-code me-1"></i>Klik Koding</small>
            </a>
        </div>
    </div>
    
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-content">
                    <h1 class="page-title"><?= $page_title ?></h1>
                    <nav aria-label="breadcrumb" class="breadcrumb-nav">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= strpos($current_dir, '/admin/barang') !== false || 
                                                                   strpos($current_dir, '/admin/kategori') !== false ||
                                                                   strpos($current_dir, '/admin/lokasi') !== false ||
                                                                   strpos($current_dir, '/admin/peminjaman') !== false ||
                                                                   strpos($current_dir, '/admin/user') !== false ||
                                                                   strpos($current_dir, '/admin/laporan') !== false ||
                                                                   strpos($current_dir, '/admin/pengaturan') !== false ? '../dashboard.php' : 'dashboard.php' ?>">Admin</a></li>
                            <li class="breadcrumb-item active"><?= $page_title ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <div class="header-right">
                <div class="user-info dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                        </div>
                        <div class="user-details ms-2 d-none d-md-block">
                            <h6><?= $user_name ?></h6>
                            <small><?= ucfirst($user_role) ?></small>
                        </div>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= strpos($current_dir, '/admin/barang') !== false || 
                                                           strpos($current_dir, '/admin/kategori') !== false ||
                                                           strpos($current_dir, '/admin/lokasi') !== false ||
                                                           strpos($current_dir, '/admin/peminjaman') !== false ||
                                                           strpos($current_dir, '/admin/user') !== false ||
                                                           strpos($current_dir, '/admin/laporan') !== false ||
                                                           strpos($current_dir, '/admin/pengaturan') !== false ? '../profile.php' : 'profile.php' ?>"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="<?= strpos($current_dir, '/admin/barang') !== false || 
                                                           strpos($current_dir, '/admin/kategori') !== false ||
                                                           strpos($current_dir, '/admin/lokasi') !== false ||
                                                           strpos($current_dir, '/admin/peminjaman') !== false ||
                                                           strpos($current_dir, '/admin/user') !== false ||
                                                           strpos($current_dir, '/admin/laporan') !== false ||
                                                           strpos($current_dir, '/admin/pengaturan') !== false ? '../change-password.php' : 'change-password.php' ?>"><i class="fas fa-key"></i> Ganti Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Content Wrapper -->
        <div class="content-wrapper"> 