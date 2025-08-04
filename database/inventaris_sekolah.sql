-- =====================================================
-- DATABASE SISTEM INVENTARIS SEKOLAH
-- =====================================================

-- Buat database
CREATE DATABASE IF NOT EXISTS inventaris_sekolah;
USE inventaris_sekolah;

-- =====================================================
-- TABEL USERS (Pengguna)
-- =====================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'petugas', 'user') DEFAULT 'user',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL KATEGORI BARANG
-- =====================================================
CREATE TABLE kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL LOKASI BARANG
-- =====================================================
CREATE TABLE lokasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_lokasi VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABEL BARANG
-- =====================================================
CREATE TABLE barang (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_barang VARCHAR(50) UNIQUE NOT NULL,
    nama_barang VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    kategori_id INT NOT NULL,
    lokasi_id INT NOT NULL,
    jumlah_total INT NOT NULL DEFAULT 0,
    jumlah_tersedia INT NOT NULL DEFAULT 0,
    kondisi ENUM('baik', 'rusak_ringan', 'rusak_berat') DEFAULT 'baik',
    gambar VARCHAR(255) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    spesifikasi TEXT,
    tahun_pengadaan YEAR,
    harga_perolehan DECIMAL(15,2) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT,
    FOREIGN KEY (lokasi_id) REFERENCES lokasi(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- =====================================================
-- TABEL PEMINJAMAN
-- =====================================================
CREATE TABLE peminjaman (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_peminjaman VARCHAR(50) UNIQUE NOT NULL,
    barang_id INT NOT NULL,
    jumlah_pinjam INT NOT NULL,
    peminjam_nama VARCHAR(100) NOT NULL,
    peminjam_kelas VARCHAR(50),
    peminjam_nis VARCHAR(20),
    peminjam_kontak VARCHAR(20),
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali_rencana DATE NOT NULL,
    tanggal_kembali_aktual DATE NULL,
    status ENUM('dipinjam', 'dikembalikan', 'terlambat') DEFAULT 'dipinjam',
    keterangan TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- =====================================================
-- TABEL RIWAYAT BARANG
-- =====================================================
CREATE TABLE riwayat_barang (
    id INT PRIMARY KEY AUTO_INCREMENT,
    barang_id INT NOT NULL,
    jenis_aktivitas ENUM('tambah', 'edit', 'hapus', 'pinjam', 'kembali') NOT NULL,
    jumlah_sebelum INT,
    jumlah_sesudah INT,
    keterangan TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- =====================================================
-- TABEL PENGATURAN SISTEM
-- =====================================================
CREATE TABLE pengaturan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_pengaturan VARCHAR(100) UNIQUE NOT NULL,
    nilai TEXT,
    deskripsi TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- INSERT DATA AWAL
-- =====================================================

-- Insert admin default
INSERT INTO users (username, password, nama_lengkap, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@sekolah.com', 'admin');

-- Insert petugas default
INSERT INTO users (username, password, nama_lengkap, email, role) VALUES 
('petugas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Inventaris', 'petugas@sekolah.com', 'petugas');

-- Insert kategori default
INSERT INTO kategori (nama_kategori, deskripsi) VALUES 
('Meubel', 'Barang-barang meubel seperti meja, kursi, lemari'),
('Elektronik', 'Barang elektronik seperti komputer, proyektor, printer'),
('ATK', 'Alat Tulis Kantor'),
('Olahraga', 'Peralatan olahraga'),
('Laboratorium', 'Peralatan laboratorium'),
('Perpustakaan', 'Buku dan peralatan perpustakaan');

-- Insert lokasi default
INSERT INTO lokasi (nama_lokasi, deskripsi) VALUES 
('Ruang Kelas 1A', 'Ruang kelas untuk kelas 1A'),
('Ruang Kelas 1B', 'Ruang kelas untuk kelas 1B'),
('Ruang Kelas 2A', 'Ruang kelas untuk kelas 2A'),
('Ruang Kelas 2B', 'Ruang kelas untuk kelas 2B'),
('Laboratorium Komputer', 'Lab komputer untuk praktik'),
('Laboratorium IPA', 'Lab IPA untuk praktik'),
('Perpustakaan', 'Ruang perpustakaan'),
('Ruang Guru', 'Ruang kerja guru'),
('Ruang Kepala Sekolah', 'Ruang kepala sekolah'),
('Aula', 'Aula sekolah'),
('Lapangan Olahraga', 'Lapangan untuk olahraga'),
('Gudang', 'Gudang penyimpanan barang');

-- Insert pengaturan default
INSERT INTO pengaturan (nama_pengaturan, nilai, deskripsi) VALUES 
('nama_sekolah', 'SMA Negeri 1 Contoh', 'Nama sekolah'),
('alamat_sekolah', 'Jl. Contoh No. 123, Kota Contoh', 'Alamat sekolah'),
('telepon_sekolah', '021-1234567', 'Nomor telepon sekolah'),
('email_sekolah', 'info@sman1contoh.sch.id', 'Email sekolah'),
('maksimal_peminjaman', '7', 'Maksimal hari peminjaman'),
('denda_terlambat', '1000', 'Denda per hari keterlambatan (Rp)');

-- =====================================================
-- INDEX UNTUK OPTIMASI
-- =====================================================
CREATE INDEX idx_barang_kategori ON barang(kategori_id);
CREATE INDEX idx_barang_lokasi ON barang(lokasi_id);
CREATE INDEX idx_peminjaman_barang ON peminjaman(barang_id);
CREATE INDEX idx_peminjaman_status ON peminjaman(status);
CREATE INDEX idx_peminjaman_tanggal ON peminjaman(tanggal_pinjam, tanggal_kembali_rencana);
CREATE INDEX idx_riwayat_barang ON riwayat_barang(barang_id, created_at);

-- =====================================================
-- VIEW UNTUK LAPORAN
-- =====================================================

-- View untuk laporan stok barang
CREATE VIEW v_stok_barang AS
SELECT 
    b.id,
    b.kode_barang,
    b.nama_barang,
    k.nama_kategori,
    l.nama_lokasi,
    b.jumlah_total,
    b.jumlah_tersedia,
    (b.jumlah_total - b.jumlah_tersedia) as jumlah_dipinjam,
    b.kondisi,
    b.tahun_pengadaan,
    b.harga_perolehan,
    b.created_at
FROM barang b
JOIN kategori k ON b.kategori_id = k.id
JOIN lokasi l ON b.lokasi_id = l.id;

-- View untuk laporan peminjaman
CREATE VIEW v_laporan_peminjaman AS
SELECT 
    p.id,
    p.kode_peminjaman,
    b.kode_barang,
    b.nama_barang,
    p.peminjam_nama,
    p.peminjam_kelas,
    p.peminjam_nis,
    p.jumlah_pinjam,
    p.tanggal_pinjam,
    p.tanggal_kembali_rencana,
    p.tanggal_kembali_aktual,
    p.status,
    CASE 
        WHEN p.status = 'dipinjam' AND CURDATE() > p.tanggal_kembali_rencana THEN 'terlambat'
        WHEN p.status = 'dikembalikan' THEN 'selesai'
        ELSE p.status
    END as status_detail,
    DATEDIFF(COALESCE(p.tanggal_kembali_aktual, CURDATE()), p.tanggal_pinjam) as lama_peminjaman,
    u.nama_lengkap as petugas
FROM peminjaman p
JOIN barang b ON p.barang_id = b.id
JOIN users u ON p.created_by = u.id;

-- =====================================================
-- TRIGGER UNTUK UPDATE JUMLAH TERSEDIA
-- =====================================================

DELIMITER //
CREATE TRIGGER after_peminjaman_insert
AFTER INSERT ON peminjaman
FOR EACH ROW
BEGIN
    UPDATE barang 
    SET jumlah_tersedia = jumlah_tersedia - NEW.jumlah_pinjam
    WHERE id = NEW.barang_id;
    
    INSERT INTO riwayat_barang (barang_id, jenis_aktivitas, jumlah_sebelum, jumlah_sesudah, keterangan, created_by)
    VALUES (NEW.barang_id, 'pinjam', 
            (SELECT jumlah_tersedia + NEW.jumlah_pinjam FROM barang WHERE id = NEW.barang_id),
            (SELECT jumlah_tersedia FROM barang WHERE id = NEW.barang_id),
            CONCAT('Peminjaman oleh ', NEW.peminjam_nama), NEW.created_by);
END//

CREATE TRIGGER after_peminjaman_update
AFTER UPDATE ON peminjaman
FOR EACH ROW
BEGIN
    IF NEW.status = 'dikembalikan' AND OLD.status = 'dipinjam' THEN
        UPDATE barang 
        SET jumlah_tersedia = jumlah_tersedia + NEW.jumlah_pinjam
        WHERE id = NEW.barang_id;
        
        INSERT INTO riwayat_barang (barang_id, jenis_aktivitas, jumlah_sebelum, jumlah_sesudah, keterangan, created_by)
        VALUES (NEW.barang_id, 'kembali', 
                (SELECT jumlah_tersedia - NEW.jumlah_pinjam FROM barang WHERE id = NEW.barang_id),
                (SELECT jumlah_tersedia FROM barang WHERE id = NEW.barang_id),
                CONCAT('Pengembalian oleh ', NEW.peminjam_nama), NEW.created_by);
    END IF;
END//
DELIMITER ;

-- =====================================================
-- STORED PROCEDURE UNTUK LAPORAN
-- =====================================================

DELIMITER //
CREATE PROCEDURE sp_laporan_stok_barang(
    IN p_kategori_id INT,
    IN p_lokasi_id INT,
    IN p_kondisi VARCHAR(20)
)
BEGIN
    SELECT 
        b.kode_barang,
        b.nama_barang,
        k.nama_kategori,
        l.nama_lokasi,
        b.jumlah_total,
        b.jumlah_tersedia,
        (b.jumlah_total - b.jumlah_tersedia) as jumlah_dipinjam,
        b.kondisi,
        b.tahun_pengadaan,
        b.harga_perolehan
    FROM barang b
    JOIN kategori k ON b.kategori_id = k.id
    JOIN lokasi l ON b.lokasi_id = l.id
    WHERE (p_kategori_id IS NULL OR b.kategori_id = p_kategori_id)
    AND (p_lokasi_id IS NULL OR b.lokasi_id = p_lokasi_id)
    AND (p_kondisi IS NULL OR b.kondisi = p_kondisi)
    ORDER BY b.nama_barang;
END//

CREATE PROCEDURE sp_laporan_peminjaman(
    IN p_tanggal_mulai DATE,
    IN p_tanggal_akhir DATE,
    IN p_status VARCHAR(20)
)
BEGIN
    SELECT 
        p.kode_peminjaman,
        b.nama_barang,
        p.peminjam_nama,
        p.peminjam_kelas,
        p.jumlah_pinjam,
        p.tanggal_pinjam,
        p.tanggal_kembali_rencana,
        p.tanggal_kembali_aktual,
        p.status,
        u.nama_lengkap as petugas
    FROM peminjaman p
    JOIN barang b ON p.barang_id = b.id
    JOIN users u ON p.created_by = u.id
    WHERE p.tanggal_pinjam BETWEEN p_tanggal_mulai AND p_tanggal_akhir
    AND (p_status IS NULL OR p.status = p_status)
    ORDER BY p.tanggal_pinjam DESC;
END//
DELIMITER ;

-- =====================================================
-- SAMPLE DATA UNTUK TESTING
-- =====================================================

-- Insert sample barang
INSERT INTO barang (kode_barang, nama_barang, deskripsi, kategori_id, lokasi_id, jumlah_total, jumlah_tersedia, kondisi, tahun_pengadaan, harga_perolehan, created_by) VALUES
('MBL-001', 'Meja Siswa', 'Meja siswa standar ukuran 120x60cm', 1, 1, 30, 30, 'baik', 2023, 250000, 1),
('MBL-002', 'Kursi Siswa', 'Kursi siswa standar', 1, 1, 30, 30, 'baik', 2023, 150000, 1),
('ELE-001', 'Komputer Desktop', 'Komputer desktop untuk lab komputer', 2, 5, 20, 20, 'baik', 2023, 5000000, 1),
('ELE-002', 'Proyektor LCD', 'Proyektor LCD untuk presentasi', 2, 1, 5, 5, 'baik', 2023, 3500000, 1),
('ATK-001', 'Pulpen', 'Pulpen standar', 3, 7, 100, 100, 'baik', 2023, 5000, 1),
('OLA-001', 'Bola Basket', 'Bola basket standar', 4, 11, 10, 10, 'baik', 2023, 150000, 1);

-- Insert sample peminjaman
INSERT INTO peminjaman (kode_peminjaman, barang_id, jumlah_pinjam, peminjam_nama, peminjam_kelas, peminjam_nis, tanggal_pinjam, tanggal_kembali_rencana, status, created_by) VALUES
('PJM-2024-001', 1, 2, 'Ahmad Siswa', '1A', '2024001', '2024-01-15', '2024-01-22', 'dipinjam', 1),
('PJM-2024-002', 3, 1, 'Budi Siswa', '2A', '2024002', '2024-01-10', '2024-01-17', 'dikembalikan', 1);

-- Update jumlah tersedia untuk peminjaman yang sudah dikembalikan
UPDATE barang SET jumlah_tersedia = jumlah_tersedia - 2 WHERE id = 1;
UPDATE barang SET jumlah_tersedia = jumlah_tersedia - 1 WHERE id = 3;

COMMIT; 