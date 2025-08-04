<?php
/**
 * Header untuk halaman utama
 * Sistem Inventaris Sekolah
 */

// Ambil pengaturan sekolah
$nama_sekolah = getPengaturan('nama_sekolah') ?: 'Sistem Inventaris Sekolah';
$alamat_sekolah = getPengaturan('alamat_sekolah') ?: '';
$telepon_sekolah = getPengaturan('telepon_sekolah') ?: '';
$email_sekolah = getPengaturan('email_sekolah') ?: '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $nama_sekolah ?> - Inventaris Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #3730a3;
            --secondary-color: #7c3aed;
            --accent-color: #ec4899;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --gray-color: #6b7280;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Header Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .navbar-nav .nav-link {
            font-weight: 500;
            color: var(--dark-color) !important;
            margin: 0 10px;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            color: white;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
            color: white;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn-hero {
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-hero-primary {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
        }
        
        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.4);
            color: var(--primary-color);
        }
        
        .btn-hero-outline {
            border: 2px solid white;
            color: white;
            background: transparent;
        }
        
        .btn-hero-outline:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        /* Stats Cards */
        .stats-section {
            background: white;
            padding: 80px 0;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .stats-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 32px;
            color: white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-icon.primary { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
        }
        .stats-icon.success { 
            background: linear-gradient(135deg, var(--success-color), #059669); 
        }
        .stats-icon.warning { 
            background: linear-gradient(135deg, var(--warning-color), #d97706); 
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .stats-label {
            color: var(--gray-color);
            font-weight: 500;
            font-size: 1rem;
        }
        
        /* Content Section */
        .content-section {
            background: var(--light-color);
            padding: 80px 0;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            text-align: center;
            color: var(--gray-color);
            margin-bottom: 3rem;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 24px;
            padding: 40px;
            margin: 40px 0;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .btn-search {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
            color: white;
        }
        
        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .product-card .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .product-card .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }
        
        .product-card .card-body {
            padding: 1.5rem;
        }
        
        .product-card .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-card .badge {
            font-weight: 500;
            border-radius: 8px;
        }
        
        .product-card .alert-sm {
            border-radius: 8px;
            font-size: 0.75rem;
            margin: 0;
        }
        
        .product-card .btn-outline-primary {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .product-card .btn-outline-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        /* Image styles */
        .product-card img {
            border-radius: 12px;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover img {
            transform: scale(1.05);
        }
        
        .product-card .bg-light {
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .product-card:hover .bg-light {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important;
        }
        
        /* Stats box */
        .product-card .bg-light.rounded {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0) !important;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .product-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(5) { animation-delay: 0.5s; }
        .product-card:nth-child(6) { animation-delay: 0.6s; }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .product-card .card-body {
                padding: 1rem;
            }
            
            .product-card .card-title {
                font-size: 1rem;
            }
            
            .product-card .badge {
                font-size: 0.65rem;
            }
        }
        
        .stat-value.success {
            color: var(--success-color);
        }
        
        .product-year {
            text-align: center;
            margin: 20px 0;
        }
        
        .product-year small {
            color: var(--gray-color);
            font-weight: 500;
        }
        
        .btn-disabled {
            background: #f3f4f6;
            color: var(--gray-color);
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: not-allowed;
        }
        
        /* About Section */
        .about-section {
            background: white;
            padding: 80px 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success-color), #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .feature-content h6 {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .feature-content small {
            color: var(--gray-color);
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-section {
                padding: 100px 0 60px;
            }
            
            .stats-section {
                margin-top: -30px;
                padding: 60px 0;
            }
            
            .stats-card {
                margin-bottom: 20px;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-school me-2"></i><?= $nama_sekolah ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#beranda">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#barang">Data Barang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tentang">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#kontak">Kontak</a>
                    </li>
                </ul>
                
                <div class="d-flex">
                    <a href="auth/login.php" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login Admin
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="beranda">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">
                        Sistem Inventaris Barang Sekolah
                    </h1>
                    <p class="hero-subtitle">
                        Lihat data barang yang tersedia di gudang sekolah kami. 
                        Semua barang terdaftar dan dapat dipinjam melalui petugas.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#barang" class="btn-hero btn-hero-primary">
                            <i class="fas fa-boxes me-2"></i>Lihat Barang
                        </a>
                        <a href="auth/login.php" class="btn-hero btn-hero-outline">
                            <i class="fas fa-user-tie me-2"></i>Login Admin
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-warehouse fa-8x opacity-50"></i>
                </div>
            </div>
        </div>
    </section> 