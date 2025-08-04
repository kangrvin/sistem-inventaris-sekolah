    <!-- Footer -->
    <footer class="footer" id="kontak">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 footer-section">
                    <h5 class="footer-title">
                        <i class="fas fa-school me-2"></i><?= $nama_sekolah ?>
                    </h5>
                    <p class="text-muted">
                        Sistem inventaris barang sekolah yang modern dan mudah digunakan. 
                        Kelola barang dengan efisien dan transparan untuk mendukung 
                        kegiatan pembelajaran yang optimal.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-link"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 footer-section">
                    <h6 class="footer-title">Menu</h6>
                    <a href="#beranda" class="footer-link">Beranda</a>
                    <a href="#barang" class="footer-link">Data Barang</a>
                    <a href="#tentang" class="footer-link">Tentang</a>
                    <a href="#kontak" class="footer-link">Kontak</a>
                </div>
                
                <div class="col-lg-3 footer-section">
                    <h6 class="footer-title">Layanan</h6>
                    <a href="auth/login.php" class="footer-link">Login Admin</a>
                    <a href="auth/login.php" class="footer-link">Login Petugas</a>
                    <a href="#" class="footer-link">Panduan Penggunaan</a>
                    <a href="#" class="footer-link">Bantuan</a>
                </div>
                
                <div class="col-lg-3 footer-section">
                    <h6 class="footer-title">Kontak</h6>
                    <?php if ($alamat_sekolah): ?>
                        <p class="contact-info">
                            <i class="fas fa-map-marker-alt me-2"></i><?= $alamat_sekolah ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($telepon_sekolah): ?>
                        <p class="contact-info">
                            <i class="fas fa-phone me-2"></i><?= $telepon_sekolah ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($email_sekolah): ?>
                        <p class="contact-info">
                            <i class="fas fa-envelope me-2"></i><?= $email_sekolah ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; 2025 <a href="https://youtube.com/@klikkoding?si=rDnT-8jmno-88mx2" target="_blank" class="text-decoration-none">Klik Koding</a>. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0">
                            <a href="#" class="footer-bottom-link me-3">Privacy Policy</a>
                            <a href="#" class="footer-bottom-link">Terms of Service</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, var(--dark-color), #111827);
            color: white;
            padding: 80px 0 30px;
            margin-top: 80px;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }
        
        .footer-section {
            margin-bottom: 40px;
        }
        
        .footer-title {
            font-weight: 700;
            margin-bottom: 25px;
            color: #f9fafb;
            font-size: 1.1rem;
        }
        
        .footer-link {
            color: #d1d5db;
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .footer-link:hover {
            color: white;
            transform: translateX(5px);
        }
        
        .social-link {
            color: #d1d5db;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .social-link:hover {
            color: white;
            transform: translateY(-3px);
        }
        
        .contact-info {
            color: #d1d5db;
            margin-bottom: 15px;
            font-weight: 500;
            line-height: 1.6;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
            margin-top: 40px;
        }
        
        .footer-bottom-link {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-bottom-link:hover {
            color: white;
        }
        
        @media (max-width: 768px) {
            .footer {
                padding: 60px 0 20px;
            }
            
            .footer-bottom {
                text-align: center;
            }
            
            .footer-bottom .col-md-6:last-child {
                margin-top: 15px;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255,255,255,0.98) !important';
                navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.15)';
            } else {
                navbar.style.background = 'rgba(255,255,255,0.95) !important';
                navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.1)';
            }
        });
        
        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe stats cards
        document.querySelectorAll('.stats-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
        
        // Animate product cards
        document.querySelectorAll('.product-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `all 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html> 