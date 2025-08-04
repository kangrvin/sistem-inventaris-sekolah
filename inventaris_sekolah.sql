-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 04, 2025 at 02:16 PM
-- Server version: 8.0.30
-- PHP Version: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventaris_sekolah`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_laporan_peminjaman` (IN `p_tanggal_mulai` DATE, IN `p_tanggal_akhir` DATE, IN `p_status` VARCHAR(20))   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_laporan_stok_barang` (IN `p_kategori_id` INT, IN `p_lokasi_id` INT, IN `p_kondisi` VARCHAR(20))   BEGIN
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
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int NOT NULL,
  `kode_barang` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_barang` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `kategori_id` int NOT NULL,
  `lokasi_id` int NOT NULL,
  `jumlah_total` int NOT NULL DEFAULT '0',
  `jumlah_tersedia` int NOT NULL DEFAULT '0',
  `kondisi` enum('baik','rusak_ringan','rusak_berat') COLLATE utf8mb4_general_ci DEFAULT 'baik',
  `gambar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `spesifikasi` text COLLATE utf8mb4_general_ci,
  `tahun_pengadaan` year DEFAULT NULL,
  `harga_perolehan` decimal(15,2) DEFAULT '0.00',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `kode_barang`, `nama_barang`, `deskripsi`, `kategori_id`, `lokasi_id`, `jumlah_total`, `jumlah_tersedia`, `kondisi`, `gambar`, `foto`, `spesifikasi`, `tahun_pengadaan`, `harga_perolehan`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'MBL-001', 'Meja Siswa', 'Meja siswa standar ukuran 120x60cm', 1, 1, 30, 23, 'baik', NULL, '688e3e233b050.jpg', NULL, '2025', 250000.00, 1, '2025-08-01 14:28:54', '2025-08-02 16:34:43'),
(2, 'MBL-002', 'Kursi Siswa', 'Kursi siswa standar', 1, 1, 30, 20, 'baik', NULL, '688e3e36e59e9.jpg', NULL, '2025', 150000.00, 1, '2025-08-01 14:28:54', '2025-08-02 16:39:15'),
(3, 'ELE-001', 'Komputer Desktop', 'Komputer desktop untuk lab komputer', 2, 5, 20, 18, 'baik', NULL, '688e3e54b62af.jpg', NULL, '2025', 5000000.00, 1, '2025-08-01 14:28:54', '2025-08-02 16:35:32'),
(4, 'ELE-002', 'Proyektor LCD', 'Proyektor LCD untuk presentasi', 2, 1, 5, 3, 'baik', NULL, '688e3e6c1e44d.jpg', NULL, '2025', 3500000.00, 1, '2025-08-01 14:28:54', '2025-08-02 16:38:34'),
(5, 'ATK-001', 'Pulpen', 'Pulpen standar', 3, 7, 100, 80, 'baik', NULL, '688e3e82a022d.jpg', NULL, '2025', 5000.00, 1, '2025-08-01 14:28:54', '2025-08-02 16:36:18'),
(6, 'OLA-001', 'Bola Basket', 'Bola basket standar', 4, 11, 14, 14, 'baik', NULL, '688e3e988828b.jpg', NULL, '2025', 150000.00, 1, '2025-08-01 14:28:54', '2025-08-02 16:39:22');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Meubel', 'Barang-barang meubel seperti meja, kursi, lemari', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(2, 'Elektronik', 'Barang elektronik seperti komputer, proyektor, printer', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(3, 'ATK', 'Alat Tulis Kantor', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(4, 'Olahraga', 'Peralatan olahraga', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(5, 'Laboratorium', 'Peralatan laboratorium', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(6, 'Perpustakaan', 'Buku dan peralatan perpustakaan', '2025-08-01 14:28:53', '2025-08-01 14:28:53');

-- --------------------------------------------------------

--
-- Table structure for table `lokasi`
--

CREATE TABLE `lokasi` (
  `id` int NOT NULL,
  `nama_lokasi` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lokasi`
--

INSERT INTO `lokasi` (`id`, `nama_lokasi`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Ruang Kelas 1A', 'Ruang kelas untuk kelas 1A', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(2, 'Ruang Kelas 1B', 'Ruang kelas untuk kelas 1B', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(3, 'Ruang Kelas 2A', 'Ruang kelas untuk kelas 2A', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(4, 'Ruang Kelas 2B', 'Ruang kelas untuk kelas 2B', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(5, 'Laboratorium Komputer', 'Lab komputer untuk praktik', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(6, 'Laboratorium IPA', 'Lab IPA untuk praktik', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(7, 'Perpustakaan', 'Ruang perpustakaan', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(8, 'Ruang Guru', 'Ruang kerja guru', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(9, 'Ruang Kepala Sekolah', 'Ruang kepala sekolah', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(10, 'Aula', 'Aula sekolah', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(11, 'Lapangan Olahraga', 'Lapangan untuk olahraga', '2025-08-01 14:28:53', '2025-08-01 14:28:53'),
(12, 'Gudang', 'Gudang penyimpanan barang', '2025-08-01 14:28:53', '2025-08-01 14:28:53');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int NOT NULL,
  `kode_peminjaman` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `barang_id` int NOT NULL,
  `jumlah_pinjam` int NOT NULL,
  `peminjam_nama` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `peminjam_kelas` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `peminjam_nis` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `peminjam_kontak` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali_rencana` date NOT NULL,
  `tanggal_kembali_aktual` date DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan','terlambat') COLLATE utf8mb4_general_ci DEFAULT 'dipinjam',
  `keterangan` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `kode_peminjaman`, `barang_id`, `jumlah_pinjam`, `peminjam_nama`, `peminjam_kelas`, `peminjam_nis`, `peminjam_kontak`, `tanggal_pinjam`, `tanggal_kembali_rencana`, `tanggal_kembali_aktual`, `status`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES
(21, 'PJM-2025-001', 6, 1, 'Rizki', 'A2', '11110999', '082227654567', '2025-08-02', '2025-08-03', '2025-08-02', 'dikembalikan', 'Peminjaman ekstrakulikuler basket', 3, '2025-08-02 16:37:42', '2025-08-02 16:40:01'),
(22, 'PJM-2025-002', 4, 1, 'Rezkia Putri', 'A3', '111109887', '082234567891', '2025-08-02', '2025-08-05', NULL, 'dipinjam', '', 3, '2025-08-02 16:38:34', '2025-08-02 16:38:34'),
(23, 'PJM-2025-003', 2, 4, 'Saputra', 'A2', '111101122', '082367856754', '2025-07-29', '2025-07-31', NULL, 'dipinjam', '', 3, '2025-08-02 16:39:15', '2025-08-02 16:40:29');

--
-- Triggers `peminjaman`
--
DELIMITER $$
CREATE TRIGGER `after_peminjaman_insert` AFTER INSERT ON `peminjaman` FOR EACH ROW BEGIN
    UPDATE barang 
    SET jumlah_tersedia = jumlah_tersedia - NEW.jumlah_pinjam
    WHERE id = NEW.barang_id;
    
    INSERT INTO riwayat_barang (barang_id, jenis_aktivitas, jumlah_sebelum, jumlah_sesudah, keterangan, created_by)
    VALUES (NEW.barang_id, 'pinjam', 
            (SELECT jumlah_tersedia + NEW.jumlah_pinjam FROM barang WHERE id = NEW.barang_id),
            (SELECT jumlah_tersedia FROM barang WHERE id = NEW.barang_id),
            CONCAT('Peminjaman oleh ', NEW.peminjam_nama), NEW.created_by);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_peminjaman_update` AFTER UPDATE ON `peminjaman` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int NOT NULL,
  `nama_pengaturan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai` text COLLATE utf8mb4_general_ci,
  `deskripsi` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_pengaturan`, `nilai`, `deskripsi`, `updated_at`) VALUES
(1, 'nama_sekolah', 'SMA Negeri 1 Contoh', 'Nama sekolah', '2025-08-01 14:28:53'),
(2, 'alamat_sekolah', 'Jl. Contoh No. 123, Kota Contoh', 'Alamat sekolah', '2025-08-01 14:28:53'),
(3, 'telepon_sekolah', '021-12345677', 'Nomor telepon sekolah', '2025-08-02 06:33:09'),
(4, 'email_sekolah', 'info@sman1contoh.sch.id', 'Email sekolah', '2025-08-01 14:28:53'),
(5, 'maksimal_peminjaman', '10', 'Maksimal hari peminjaman', '2025-08-02 08:17:02'),
(6, 'denda_terlambat', '1000', 'Denda per hari keterlambatan (Rp)', '2025-08-02 06:43:30');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_barang`
--

CREATE TABLE `riwayat_barang` (
  `id` int NOT NULL,
  `barang_id` int NOT NULL,
  `jenis_aktivitas` enum('tambah','edit','hapus','pinjam','kembali') COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_sebelum` int DEFAULT NULL,
  `jumlah_sesudah` int DEFAULT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_barang`
--

INSERT INTO `riwayat_barang` (`id`, `barang_id`, `jenis_aktivitas`, `jumlah_sebelum`, `jumlah_sesudah`, `keterangan`, `created_by`, `created_at`) VALUES
(1, 1, 'pinjam', 30, 28, 'Peminjaman oleh Ahmad Siswa', 1, '2025-08-01 14:28:54'),
(2, 3, 'pinjam', 20, 19, 'Peminjaman oleh Budi Siswa', 1, '2025-08-01 14:28:54'),
(3, 1, 'pinjam', 26, 23, 'Peminjaman oleh Siti Siswa', 1, '2025-08-02 05:24:33'),
(4, 2, 'pinjam', 30, 25, 'Peminjaman oleh Rina Siswa', 1, '2025-08-02 05:24:33'),
(5, 3, 'pinjam', 18, 16, 'Peminjaman oleh Budi Siswa', 1, '2025-08-02 05:24:33'),
(6, 4, 'pinjam', 5, 4, 'Peminjaman oleh Ahmad Siswa', 1, '2025-08-02 05:24:33'),
(7, 5, 'pinjam', 100, 80, 'Peminjaman oleh Guru Matematika', 1, '2025-08-02 05:24:33'),
(8, 5, 'pinjam', 60, 56, 'Peminjaman oleh Siswa 1', 1, '2025-08-02 06:48:31'),
(9, 5, 'pinjam', 56, 53, 'Peminjaman oleh Siswa 2', 1, '2025-08-02 06:48:31'),
(10, 6, 'pinjam', 10, 8, 'Peminjaman oleh ucok', 3, '2025-08-02 08:26:57'),
(11, 6, 'kembali', 8, 10, 'Pengembalian oleh ucokk', 3, '2025-08-02 08:37:01'),
(12, 6, 'pinjam', 12, 10, 'Peminjaman oleh ucok', 3, '2025-08-02 08:38:06'),
(13, 6, 'kembali', 10, 12, 'Pengembalian oleh ucok', 3, '2025-08-02 08:39:59'),
(14, 6, 'pinjam', 14, 12, 'Peminjaman oleh ucok', 3, '2025-08-02 08:43:41'),
(15, 6, 'kembali', 12, 14, 'Pengembalian oleh ucok', 3, '2025-08-02 08:44:25'),
(16, 1, 'pinjam', 20, 18, 'Peminjaman oleh Ahmad Siswa', 2, '2025-08-02 11:02:45'),
(17, 2, 'pinjam', 20, 19, 'Peminjaman oleh Budi Siswa', 2, '2025-08-02 11:02:45'),
(18, 3, 'pinjam', 14, 11, 'Peminjaman oleh Citra Siswa', 2, '2025-08-02 11:02:45'),
(19, 1, 'pinjam', 18, 17, 'Peminjaman oleh Dewi Siswa', 2, '2025-08-02 11:02:45'),
(20, 2, 'pinjam', 19, 17, 'Peminjaman oleh Eko Siswa', 2, '2025-08-02 11:02:45'),
(21, 6, 'pinjam', 14, 13, 'Peminjaman oleh Rizki', 3, '2025-08-02 16:37:42'),
(22, 4, 'pinjam', 4, 3, 'Peminjaman oleh Rezkia Putri', 3, '2025-08-02 16:38:34'),
(23, 2, 'pinjam', 24, 20, 'Peminjaman oleh Saputra', 3, '2025-08-02 16:39:15'),
(24, 6, 'kembali', 13, 14, 'Pengembalian oleh Rizki', 3, '2025-08-02 16:39:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','petugas','user') COLLATE utf8mb4_general_ci DEFAULT 'user',
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_general_ci DEFAULT 'aktif',
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `role`, `status`, `foto`, `created_at`, `updated_at`) VALUES
(1, 'admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@sekolah.com', 'admin', 'aktif', 'profile_1_1754120481.jpg', '2025-08-01 14:28:53', '2025-08-04 14:10:53'),
(2, 'petugas2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Inventaris', 'petugas@sekolah.com', 'petugas', 'aktif', NULL, '2025-08-01 14:28:53', '2025-08-02 04:31:16'),
(3, 'petugas', '$2y$10$lxdVnpiObqPWYr/iYKwnnO5.udpQqkLiCOXF9DscaZ18Bq1icMMjm', 'petugas1', 'petugas2@gmail.com', 'petugas', 'aktif', NULL, '2025-08-02 04:31:59', '2025-08-02 11:52:56'),
(5, 'admin', '$2y$10$nvMUdtvcFT0XjLcRGk6cM.wdbBb9zuI9/dwXbBFo2CpG2LLLUP8G6', 'admin', 'admin@example.com', 'admin', 'aktif', NULL, '2025-08-04 14:11:14', '2025-08-04 14:11:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_laporan_peminjaman`
-- (See below for the actual view)
--
CREATE TABLE `v_laporan_peminjaman` (
`id` int
,`kode_peminjaman` varchar(50)
,`kode_barang` varchar(50)
,`nama_barang` varchar(200)
,`peminjam_nama` varchar(100)
,`peminjam_kelas` varchar(50)
,`peminjam_nis` varchar(20)
,`jumlah_pinjam` int
,`tanggal_pinjam` date
,`tanggal_kembali_rencana` date
,`tanggal_kembali_aktual` date
,`status` enum('dipinjam','dikembalikan','terlambat')
,`status_detail` varchar(12)
,`lama_peminjaman` int
,`petugas` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_stok_barang`
-- (See below for the actual view)
--
CREATE TABLE `v_stok_barang` (
`id` int
,`kode_barang` varchar(50)
,`nama_barang` varchar(200)
,`nama_kategori` varchar(100)
,`nama_lokasi` varchar(100)
,`jumlah_total` int
,`jumlah_tersedia` int
,`jumlah_dipinjam` bigint
,`kondisi` enum('baik','rusak_ringan','rusak_berat')
,`tahun_pengadaan` year
,`harga_perolehan` decimal(15,2)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `v_laporan_peminjaman`
--
DROP TABLE IF EXISTS `v_laporan_peminjaman`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_laporan_peminjaman`  AS SELECT `p`.`id` AS `id`, `p`.`kode_peminjaman` AS `kode_peminjaman`, `b`.`kode_barang` AS `kode_barang`, `b`.`nama_barang` AS `nama_barang`, `p`.`peminjam_nama` AS `peminjam_nama`, `p`.`peminjam_kelas` AS `peminjam_kelas`, `p`.`peminjam_nis` AS `peminjam_nis`, `p`.`jumlah_pinjam` AS `jumlah_pinjam`, `p`.`tanggal_pinjam` AS `tanggal_pinjam`, `p`.`tanggal_kembali_rencana` AS `tanggal_kembali_rencana`, `p`.`tanggal_kembali_aktual` AS `tanggal_kembali_aktual`, `p`.`status` AS `status`, (case when ((`p`.`status` = 'dipinjam') and (curdate() > `p`.`tanggal_kembali_rencana`)) then 'terlambat' when (`p`.`status` = 'dikembalikan') then 'selesai' else `p`.`status` end) AS `status_detail`, (to_days(coalesce(`p`.`tanggal_kembali_aktual`,curdate())) - to_days(`p`.`tanggal_pinjam`)) AS `lama_peminjaman`, `u`.`nama_lengkap` AS `petugas` FROM ((`peminjaman` `p` join `barang` `b` on((`p`.`barang_id` = `b`.`id`))) join `users` `u` on((`p`.`created_by` = `u`.`id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `v_stok_barang`
--
DROP TABLE IF EXISTS `v_stok_barang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_stok_barang`  AS SELECT `b`.`id` AS `id`, `b`.`kode_barang` AS `kode_barang`, `b`.`nama_barang` AS `nama_barang`, `k`.`nama_kategori` AS `nama_kategori`, `l`.`nama_lokasi` AS `nama_lokasi`, `b`.`jumlah_total` AS `jumlah_total`, `b`.`jumlah_tersedia` AS `jumlah_tersedia`, (`b`.`jumlah_total` - `b`.`jumlah_tersedia`) AS `jumlah_dipinjam`, `b`.`kondisi` AS `kondisi`, `b`.`tahun_pengadaan` AS `tahun_pengadaan`, `b`.`harga_perolehan` AS `harga_perolehan`, `b`.`created_at` AS `created_at` FROM ((`barang` `b` join `kategori` `k` on((`b`.`kategori_id` = `k`.`id`))) join `lokasi` `l` on((`b`.`lokasi_id` = `l`.`id`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_barang_kategori` (`kategori_id`),
  ADD KEY `idx_barang_lokasi` (`lokasi_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lokasi`
--
ALTER TABLE `lokasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_peminjaman` (`kode_peminjaman`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_peminjaman_barang` (`barang_id`),
  ADD KEY `idx_peminjaman_status` (`status`),
  ADD KEY `idx_peminjaman_tanggal` (`tanggal_pinjam`,`tanggal_kembali_rencana`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_pengaturan` (`nama_pengaturan`);

--
-- Indexes for table `riwayat_barang`
--
ALTER TABLE `riwayat_barang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_riwayat_barang` (`barang_id`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lokasi`
--
ALTER TABLE `lokasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `riwayat_barang`
--
ALTER TABLE `riwayat_barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `barang_ibfk_2` FOREIGN KEY (`lokasi_id`) REFERENCES `lokasi` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `barang_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `riwayat_barang`
--
ALTER TABLE `riwayat_barang`
  ADD CONSTRAINT `riwayat_barang_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_barang_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
