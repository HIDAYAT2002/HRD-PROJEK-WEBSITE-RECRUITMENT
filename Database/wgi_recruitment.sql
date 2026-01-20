-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 29, 2025 at 07:55 AM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wgi_recruitment`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `lowongan`
--

CREATE TABLE `lowongan` (
  `id` int(11) NOT NULL,
  `posisi` varchar(100) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pekerjaan` text DEFAULT NULL,
  `pekerjaan_en` text DEFAULT NULL,
  `kriteria` text DEFAULT NULL,
  `kriteria_en` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `lowongan`
--

INSERT INTO `lowongan` (`id`, `posisi`, `lokasi`, `deskripsi`, `status`, `created_at`, `pekerjaan`, `pekerjaan_en`, `kriteria`, `kriteria_en`, `kota`, `deadline`) VALUES
(5, 'Sales Supervisor Sejabotabek', NULL, NULL, 'aktif', '2025-12-19 09:05:49', 'Menawarkan dan menjual produk/jasa perusahaan kepada pelanggan baru maupun existing\r\n\r\nMencapai target penjualan harian, mingguan, dan bulanan\r\n\r\nMelakukan follow-up prospek hingga terjadi transaksi\r\n\r\nMenjalin hubungan baik dan jangka panjang dengan pelanggan\r\n\r\nMemberikan informasi produk secara jelas dan meyakinkan\r\n\r\nMenangani pertanyaan, keluhan, dan kebutuhan pelanggan\r\n\r\nMembuat laporan penjualan secara berkala\r\n\r\nMelakukan survei pasar dan memahami kebutuhan pelanggan\r\n\r\nBekerja sama dengan tim marketing dan tim internal lainnya', NULL, 'Pendidikan minimal SMA/SMK (D3/S1 lebih diutamakan)\r\n\r\nPengalaman sebagai Sales minimal 1 tahun (fresh graduate dipersilakan melamar)\r\n\r\nMemiliki kemampuan komunikasi dan negosiasi yang baik\r\n\r\nBerorientasi pada target dan hasil\r\n\r\nPercaya diri, jujur, dan bertanggung jawab\r\n\r\nMampu bekerja secara mandiri maupun dalam tim\r\n\r\nMenguasai penggunaan WhatsApp, email, dan media sosial\r\n\r\nMemiliki kendaraan pribadi & SIM (jika dibutuhkan untuk sales lapangan)', NULL, 'Sukabumi', '2026-01-01'),
(7, 'Sales Manager', NULL, NULL, 'aktif', '2025-12-20 03:31:04', 'Merencanakan, mengelola, dan mengembangkan strategi penjualan untuk mencapai target perusahaan.\r\n\r\nMemimpin, mengarahkan, dan mengevaluasi kinerja tim sales.\r\n\r\nMenentukan target penjualan dan melakukan monitoring pencapaiannya secara berkala.\r\n\r\nMenganalisis pasar, kebutuhan pelanggan, serta peluang bisnis baru.\r\n\r\nMenjalin dan menjaga hubungan baik dengan klien, mitra, dan pelanggan potensial.\r\n\r\nMenyusun laporan penjualan (harian, mingguan, dan bulanan) kepada manajemen.\r\n\r\nBerkoordinasi dengan divisi lain (marketing, operasional, dan keuangan).\r\n\r\nMenangani keluhan pelanggan dan memastikan kepuasan pelanggan.\r\n\r\nMengembangkan sistem dan proses penjualan agar lebih efektif dan efisien.\r\n\r\nMengawasi pelaksanaan kebijakan dan standar penjualan perusahaan.', NULL, 'Pendidikan minimal S1 semua jurusan (Manajemen/Marketing lebih diutamakan).\r\n\r\nPengalaman kerja minimal 3 tahun di bidang penjualan, minimal 1 tahun sebagai Supervisor/Manager.\r\n\r\nMemiliki kemampuan leadership dan komunikasi yang baik.\r\n\r\nTerbiasa bekerja dengan target dan tekanan kerja.\r\n\r\nMampu menyusun strategi penjualan dan analisis pasar.\r\n\r\nMemiliki kemampuan negosiasi dan presentasi yang baik.\r\n\r\nMenguasai Microsoft Office (Excel, Word, PowerPoint).\r\n\r\nBerorientasi pada hasil (target oriented).\r\n\r\nMemiliki integritas, disiplin, dan tanggung jawab tinggi.\r\n\r\nBersedia melakukan perjalanan dinas jika dibutuhkan.', NULL, 'Jabotabek', '2026-01-02'),
(8, 'Sales ', NULL, NULL, 'aktif', '2025-12-20 03:32:18', 'Melakukan penjualan produk atau jasa perusahaan kepada pelanggan.\r\n\r\nMencari dan mengembangkan pelanggan baru (prospecting).\r\n\r\nMenjaga hubungan baik dengan pelanggan lama.\r\n\r\nMenjelaskan produk, harga, dan promo kepada pelanggan.\r\n\r\nMelakukan negosiasi dan closing penjualan.\r\n\r\nMencapai target penjualan yang telah ditentukan perusahaan.\r\n\r\nMembuat laporan penjualan harian, mingguan, dan bulanan.\r\n\r\nMenangani pertanyaan dan keluhan pelanggan dengan baik.\r\n\r\nMengikuti kegiatan promosi, pameran, atau event penjualan.\r\n\r\nMelaporkan aktivitas penjualan kepada atasan (Sales Supervisor/Manager).', NULL, 'Pendidikan minimal SMA/SMK sederajat.\r\n\r\nPengalaman sebagai sales minimal 1 tahun (fresh graduate dipersilakan melamar).\r\n\r\nMemiliki kemampuan komunikasi dan negosiasi yang baik.\r\n\r\nBerpenampilan rapi dan menarik.\r\n\r\nTarget oriented dan siap bekerja di bawah tekanan.\r\n\r\nJujur, disiplin, dan bertanggung jawab.\r\n\r\nMampu bekerja secara mandiri maupun dalam tim.\r\n\r\nMemiliki kendaraan pribadi dan SIM C/A (nilai tambah).\r\n\r\nMenguasai area pemasaran (diutamakan).\r\n\r\nBersedia bekerja dengan sistem target dan insentif.', NULL, 'Jawa barat', '2026-01-03'),
(9, 'HRD PROJECT', NULL, NULL, 'nonaktif', '2025-12-20 03:37:44', 'Mengelola kebutuhan SDM untuk proyek IT (rekrutmen, penempatan, dan kontrak proyek).\r\n\r\nMenyusun dan mengelola administrasi karyawan proyek (kontrak, absensi, dan evaluasi).\r\n\r\nBerkoordinasi dengan Project Manager terkait kebutuhan tim IT (programmer, tester, analyst, dll).\r\n\r\nMengatur onboarding dan offboarding anggota tim proyek IT.\r\n\r\nMemantau kinerja dan kedisiplinan SDM selama proyek berlangsung.\r\n\r\nMengelola data payroll, insentif, dan honor tim proyek IT.\r\n\r\nMenangani permasalahan ketenagakerjaan dalam lingkup proyek IT.\r\n\r\nMemastikan kepatuhan terhadap kebijakan perusahaan dan regulasi ketenagakerjaan.\r\n\r\nMenyusun laporan SDM proyek kepada manajemen.\r\n\r\nMendukung pengembangan kompetensi SDM IT sesuai kebutuhan proyek.', NULL, 'Pendidikan minimal S1 Psikologi / Manajemen SDM / Teknik Informatika / Sistem Informasi.\r\n\r\nPengalaman minimal 2 tahun sebagai HRD atau HR Project (diutamakan di bidang IT).\r\n\r\nMemahami alur proyek IT dan struktur tim pengembangan sistem.\r\n\r\nMenguasai proses rekrutmen (screening CV, interview, assessment).\r\n\r\nMemahami dasar hukum ketenagakerjaan dan kontrak kerja proyek.\r\n\r\nMampu berkomunikasi dengan baik lintas divisi (teknis dan non-teknis).\r\n\r\nTerbiasa bekerja dengan target dan deadline proyek.\r\n\r\nMenguasai tools HR dan administrasi (Ms. Office, HRIS menjadi nilai tambah).\r\n\r\nTeliti, tegas, dan memiliki integritas tinggi.\r\n\r\nMampu bekerja secara mandiri maupun dalam tim proyek.', NULL, 'Cibitung', '2026-01-10'),
(11, 'Driver B3', NULL, NULL, 'aktif', '2025-12-22 04:28:09', 'Mengemudikan kendaraan perusahaan dengan aman, tertib, dan sesuai peraturan lalu lintas.\r\nMengantar dan menjemput pimpinan, karyawan, tamu perusahaan, atau barang sesuai jadwal.\r\nMenjaga kebersihan dan kondisi kendaraan agar selalu layak jalan.\r\nMelakukan pengecekan rutin kendaraan (oli, air radiator, rem, ban, bahan bakar).\r\nMelaporkan kondisi kendaraan dan kebutuhan perawatan atau perbaikan kepada atasan.\r\nMengurus dokumen kendaraan (STNK, pajak, servis berkala) jika diperlukan.\r\nMemastikan keamanan barang atau penumpang selama perjalanan.\r\nBersikap sopan, disiplin, dan menjaga nama baik perusahaan.\r\nSiap bekerja lembur atau perjalanan luar kota jika dibutuhkan.', NULL, 'Pendidikan minimal SMA/SMK atau sederajat.\r\nMemiliki SIM A / SIM B1 yang masih aktif (sesuai jenis kendaraan).\r\nBerpengalaman sebagai driver minimal 1–2 tahun (lebih diutamakan driver perusahaan).\r\nSehat jasmani dan rohani.\r\nMenguasai rute jalan dalam dan luar kota.\r\nMampu mengemudi dengan aman, sabar, dan bertanggung jawab.\r\nMemahami perawatan dasar kendaraan.\r\nMampu menggunakan Google Maps atau aplikasi navigasi lainnya.', NULL, 'Sumatra', '2026-01-10'),
(12, 'IT Support', NULL, NULL, 'aktif', '2025-12-22 04:32:18', 'Memberikan dukungan teknis (technical support) kepada seluruh karyawan terkait perangkat IT.\r\nMenangani troubleshooting hardware, software, jaringan, dan sistem operasional.\r\nMelakukan instalasi, konfigurasi, dan perawatan komputer, laptop, printer, dan perangkat jaringan.\r\nMengelola akun pengguna (email, sistem internal, akses jaringan).\r\nMemastikan jaringan LAN, Wi-Fi, dan internet perusahaan berjalan dengan baik.\r\nMelakukan backup data dan menjaga keamanan data perusahaan.\r\nMencatat dan mendokumentasikan setiap permasalahan IT serta solusi yang diberikan.\r\nBerkoordinasi dengan vendor IT atau pihak ketiga jika terjadi masalah teknis lanjutan.\r\nMelakukan pemeliharaan rutin sistem IT untuk mencegah gangguan operasional.\r\nMemberikan edukasi dasar IT kepada pengguna bila diperlukan.', NULL, 'Pendidikan minimal SMK/D3/S1 Teknik Informatika, Sistem Informasi, atau jurusan terkait.\r\nPengalaman minimal 1 tahun sebagai IT Support (fresh graduate dipersilakan melamar).\r\nMenguasai sistem operasi Windows / Linux / MacOS (minimal Windows).\r\nMemahami dasar jaringan (LAN, WAN, TCP/IP, Wi-Fi).\r\nMampu melakukan troubleshooting hardware & software.\r\nMemahami instalasi dan konfigurasi printer, scanner, dan perangkat pendukung lainnya.\r\nMenguasai dasar keamanan IT (antivirus, firewall, backup data).\r\nTerbiasa menggunakan tools remote support (AnyDesk, TeamViewer, dll).', NULL, 'Cibitung', '2026-02-10'),
(14, 'Cek', NULL, NULL, 'aktif', '2025-12-23 01:52:10', 'Cek', NULL, 'Cek', NULL, 'Cibitung', '2025-12-21'),
(15, 'Supir B2', NULL, NULL, 'aktif', '2025-12-23 09:12:24', 'Mengemudikan kendaraan angkutan barang sesuai rute yang telah ditentukan.\r\nMengantarkan barang ke tujuan dengan aman, tepat waktu, dan sesuai prosedur.\r\nMelakukan pengecekan kondisi kendaraan sebelum dan sesudah operasional.\r\nMemastikan muatan tertata rapi dan aman selama perjalanan.\r\nMematuhi peraturan lalu lintas dan standar keselamatan kerja.\r\nMelaporkan kondisi kendaraan, perjalanan, dan kendala di lapangan kepada atasan.\r\nMenjaga kebersihan dan kelayakan kendaraan operasional.', NULL, 'Memiliki SIM B2 aktif dan masih berlaku.\r\nBerpengalaman sebagai driver kendaraan besar (truk/trailer).\r\nMemahami rute perjalanan dalam dan luar kota.\r\nSehat jasmani dan rohani.\r\nDisiplin, jujur, dan bertanggung jawab.\r\nMampu bekerja secara mandiri maupun dalam tim.\r\nBersedia bekerja dengan sistem shift dan lembur bila diperlukan.\r\nMemiliki SKCK aktif.\r\nMemiliki sertifikat pelatihan mengemudi.\r\nBerpengalaman membawa muatan berat atau jarak jauh.\r\nMemahami perawatan dasar kendaraan.', NULL, 'Samarinda', '2026-01-10');

-- --------------------------------------------------------

--
-- Table structure for table `pelamar`
--

CREATE TABLE `pelamar` (
  `id` int(11) NOT NULL,
  `lowongan_id` int(11) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `pendidikan` varchar(50) DEFAULT NULL,
  `jurusan` varchar(150) DEFAULT NULL,
  `favorit` tinyint(1) NOT NULL DEFAULT 0,
  `cv` varchar(100) DEFAULT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `pkwt_status` varchar(20) DEFAULT 'Belum PKWT',
  `pkwt_mulai` date DEFAULT NULL,
  `pkwt_selesai` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pelamar`
--

INSERT INTO `pelamar` (`id`, `lowongan_id`, `nama`, `email`, `telepon`, `kota`, `tgl_lahir`, `pendidikan`, `jurusan`, `favorit`, `cv`, `tanggal`, `pkwt_status`, `pkwt_mulai`, `pkwt_selesai`) VALUES
(15, 12, 'Hidayat Tulloh', 'hidayat@gmail.com', '08569908449', 'Kabupaten Bekasi', '2002-11-11', 'S1', 'Teknik Informatika', 0, '1766453304_12.pdf', '2025-12-23 08:28:24', 'Belum PKWT', NULL, NULL),
(16, 11, 'Jangkung', 'kung@gmail.com', '08994902043', 'Kabupaten Bekasi', '2002-11-11', 'S1', 'Teknik Informatika', 0, '1766453789_11.pdf', '2025-12-23 08:36:29', 'Belum PKWT', NULL, NULL),
(17, 8, 'Malaxiano', 'xiano@gmail.com', '08994902043', 'Kabupaten Bekasi', '1995-01-11', 'S1', 'Akuntansi', 0, '1766453849_8.pdf', '2025-12-23 08:37:29', 'Belum PKWT', NULL, NULL),
(18, 7, 'Siti Afifah', 'afifah@gmail.com', '08994938483747', 'Kab Brebes', '1992-10-10', 'S2', 'Kewirausahaan', 0, '1766453930_7.pdf', '2025-12-23 08:38:50', 'Belum PKWT', NULL, NULL),
(19, 5, 'Muhammad Ali', 'ali@gmail.com', '085768095438', 'Kab Aceh Tamiang', '1998-02-01', 'S1', 'Administrasi', 0, '1766454002_5.pdf', '2025-12-23 08:40:02', 'Belum PKWT', NULL, NULL),
(20, 12, 'Muhammad Habiburahman', 'habib@gmail.com', '08568809760', 'Kab Madiun', '2000-02-10', 'S1', 'Teknik Informatika', 0, '1766454171_12.pdf', '2025-12-23 08:42:51', 'Belum PKWT', NULL, NULL),
(21, 12, 'Abdul Malik', 'malik@gmail.com', '08569908449', 'Kab. Bekasi', '2001-12-01', 'S1', 'Teknik Informatika', 0, '1766455776_12.pdf', '2025-12-23 09:09:36', 'Belum PKWT', NULL, NULL),
(22, 12, 'Mahmud Al Katiri', 'katiri@gmail.com', '08599887760', 'Kabupaten Bekasi', '2001-10-20', 'S1', 'Sistem Informasi Jaringan', 0, '1766460991_12.pdf', '2025-12-23 10:36:31', 'Belum PKWT', NULL, NULL),
(23, 12, 'Muhammad Fakhtur', 'fajhtur@gmail.com', '08599807060', 'Kabupaten Bekasi', '2002-10-10', 'S1', 'Pendidikan Informatika', 0, '1766461049_12.pdf', '2025-12-23 10:37:29', 'Belum PKWT', NULL, NULL),
(24, 12, 'Stevan Austin', 'austin@gmail.com', '085699098702', 'Kabupaten Bekasi', '2003-02-20', 'S1', 'Teknik Informatika', 0, '1766461119_12.pdf', '2025-12-23 10:38:39', 'Belum PKWT', NULL, NULL),
(25, 12, 'Jalaludin Al hamid', 'hamid@gmail.com', '085708090203', 'Kab Kuningan', '2001-01-01', 'S1', 'Sistem Informasi Jaringan', 0, '1766461184_12.pdf', '2025-12-23 10:39:44', 'Belum PKWT', NULL, NULL),
(26, 11, 'Komarudin Al Jamal', 'Jamal@gmail.com', '08569978905', 'Kab Brebes', '1992-02-10', 'SMA / SMK', 'IPS', 0, '1766461890_11.pdf', '2025-12-23 10:51:30', 'Belum PKWT', NULL, NULL),
(27, 11, 'Bilqis jamal', 'jama@gmail.com', '08994902043', 'Kab Kuningan', '1995-01-20', 'SMA / SMK', 'TKR', 0, '1766461950_11.pdf', '2025-12-23 10:52:30', 'Belum PKWT', NULL, NULL),
(28, 11, 'Qomaruddin Lhie', 'lhie@gmail.com', '08994902043', 'Kab Madiun', '1994-01-10', 'SMA / SMK', 'TKR', 0, '1766462064_11.pdf', '2025-12-23 10:54:24', 'Belum PKWT', NULL, NULL),
(29, 11, 'Ahmad Shopian', 'shopian@gmail.com', '08930405321', 'Kab kediri', '1993-01-01', 'SMA / SMK', 'TP', 0, '1766462114_11.pdf', '2025-12-23 10:55:14', 'Belum PKWT', NULL, NULL),
(30, 8, 'Sandrina malaxiano', 'kianowen@gmail.com', '08930204350', 'Kab Samarinda', '1999-02-01', 'D3', 'Akuntansi', 0, '1766462444_8.pdf', '2025-12-23 11:00:44', 'Belum PKWT', NULL, NULL),
(31, 8, 'Juriah Al hasanah', 'hasanah@gmail.com', '089928990234', 'Kab Samarinda', '2000-02-01', 'D3', 'Akuntansi', 0, '1766462499_8.pdf', '2025-12-23 11:01:39', 'Belum PKWT', NULL, NULL),
(32, 8, 'Pelamar 1', '123@gmail.com', '0xxxxxxxxxxx', 'Kota Bekasi', '2000-12-29', 'S1', 'Teknik Sipil', 0, '1766983294_8.pdf', '2025-12-29 11:41:34', 'Belum PKWT', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('hrd','manager') DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verify_token` varchar(100) DEFAULT NULL,
  `access_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `email_verified`, `verify_token`, `access_status`, `requested_at`, `approved_at`) VALUES
(1, 'HRD WGI', 'hrd@wgi.com', 'c02ac8c561805c89065293dfacbe5205', 'hrd', 0, NULL, 'approved', NULL, NULL),
(2, 'Manager WGI', 'manager@wgi.com', 'e10adc3949ba59abbe56e057f20f883e', 'manager', 0, NULL, 'pending', NULL, NULL),
(4, NULL, 'salsa@wgi.com', 'd45ec30e1d821e4706315ae26a84663e', 'hrd', 0, NULL, 'pending', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `lowongan`
--
ALTER TABLE `lowongan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pelamar`
--
ALTER TABLE `pelamar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lowongan`
--
ALTER TABLE `lowongan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `pelamar`
--
ALTER TABLE `pelamar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
