-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Waktu pembuatan: 29 Jan 2026 pada 13.00
-- Versi server: 10.4.11-MariaDB
-- Versi PHP: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ujian_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nama_admin` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `admins`
--

INSERT INTO `admins` (`id`, `username`, `nama_admin`, `password`, `created_at`) VALUES
(1, 'gludug', 'Betara', '$2y$10$v6Q.D8Fv5iBQdeHKBpvmyODECjT28ShK34J0nw0ExFLFwkWvQvZO6', '2025-05-05 09:13:31'),
(2, 'rubenatto', 'Administrator', '$2y$10$NCkrFjlpMqhoqxBFjCJiae7jvmXGE8QdHYsjOrCW46U7djkjtcrjG', '2025-10-14 02:20:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `butir_soal`
--

CREATE TABLE `butir_soal` (
  `id_soal` int(11) NOT NULL,
  `nomer_soal` int(11) NOT NULL,
  `kode_soal` varchar(50) NOT NULL,
  `pertanyaan` text NOT NULL,
  `tipe_soal` enum('Pilihan Ganda','Pilihan Ganda Kompleks','Benar/Salah','Uraian','Menjodohkan') NOT NULL,
  `pilihan_1` varchar(255) DEFAULT NULL,
  `pilihan_2` varchar(255) DEFAULT NULL,
  `pilihan_3` varchar(255) DEFAULT NULL,
  `pilihan_4` varchar(255) DEFAULT NULL,
  `jawaban_benar` text DEFAULT NULL,
  `status_soal` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `butir_soal`
--

INSERT INTO `butir_soal` (`id_soal`, `nomer_soal`, `kode_soal`, `pertanyaan`, `tipe_soal`, `pilihan_1`, `pilihan_2`, `pilihan_3`, `pilihan_4`, `jawaban_benar`, `status_soal`, `created_at`) VALUES
(2, 1, 'INFO10', '1+2=.....', 'Pilihan Ganda', '1', '2', '3', '4', 'pilihan_3', 'Aktif', '2025-10-26 11:52:10'),
(3, 2, 'INFO10', 'siapa&nbsp; nama ayah budi?', 'Uraian', NULL, NULL, NULL, NULL, 'budi', 'Aktif', '2026-01-21 15:27:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat`
--

CREATE TABLE `chat` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `pesan` text NOT NULL,
  `waktu` datetime DEFAULT current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0,
  `role` enum('siswa','admin') NOT NULL DEFAULT 'siswa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `faq`
--

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `faq`
--

INSERT INTO `faq` (`id`, `question`, `answer`) VALUES
(1, 'cara Ujian?', 'Siswa dapat mengikuti ujian dengan login ke dashboard, memilih menu \'Ujian\', dan mengikuti instruksi yang tersedia.'),
(2, 'Lupa password', 'Hubungi admin atau Guru untuk reset password'),
(3, 'jawaban hilang', 'Jika koneksi terputus jawaban masih tersimpan dan kamu bisa melanjutkan ujian lagi. Silakan hubungi guru atau admin untuk informasi lebih lanjut.'),
(4, 'hasil ujian', 'Setelah ujian selesai, hasil dapat dilihat pada menu \'Nilai\' di dashboard siswa.'),
(5, 'Perangkat', 'Ujian dapat diakses melalui komputer, laptop, atau perangkat mobile dengan koneksi internet yang stabil.'),
(6, 'Jaringan Terputus', 'Silakan buka kembali aplikasi ujian seperti biasa,  Jika tidak bisa masuk atau muncul pesan error, segera hubungi pengawas atau admin ujian untuk reset login.'),
(13, 'Reset Login.', 'hubungi pengawas atau admin ujian untuk reset login.'),
(20, 'Nilai tersembunyi', 'ya, admin bisa menyembunyikan maupun menampilkan nilai, agar siswa tidak bisa melihat jawaban benar.'),
(21, 'Apa itu CBT?', 'CBT adalah Computer-Based Test atau ujian berbasis komputer.');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jawaban_siswa`
--

CREATE TABLE `jawaban_siswa` (
  `id_jawaban` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `nama_siswa` text NOT NULL,
  `kode_soal` varchar(50) NOT NULL,
  `total_soal` text NOT NULL,
  `jawaban_siswa` text DEFAULT NULL,
  `waktu_sisa` text NOT NULL,
  `waktu_dijawab` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_ujian` enum('Aktif','Non-Aktif','Selesai') DEFAULT 'Aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `jawaban_siswa`
--

INSERT INTO `jawaban_siswa` (`id_jawaban`, `id_siswa`, `nama_siswa`, `kode_soal`, `total_soal`, `jawaban_siswa`, `waktu_sisa`, `waktu_dijawab`, `status_ujian`) VALUES
(1, 1, 'ra', 'INFO10', '', '[1:pilihan_2]', '60', '2025-10-14 02:54:01', 'Selesai'),
(2, 2, 'ae', 'INFO10', '', '[1:pilihan_3]', '58', '2025-10-14 12:02:11', 'Selesai'),
(3, 3, 'Intan', 'INFO10', '', '[1:pilihan_2][2:budi]', '59', '2025-10-21 09:45:24', 'Selesai');

-- --------------------------------------------------------

--
-- Struktur dari tabel `nilai`
--

CREATE TABLE `nilai` (
  `id_nilai` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `nama_siswa` text NOT NULL,
  `kode_soal` varchar(250) NOT NULL,
  `total_soal` int(11) NOT NULL,
  `jawaban_benar` varchar(100) NOT NULL,
  `jawaban_salah` varchar(100) NOT NULL,
  `jawaban_kurang` varchar(100) NOT NULL,
  `jawaban_siswa` text NOT NULL,
  `kunci` text NOT NULL,
  `nilai` decimal(5,2) NOT NULL,
  `nilai_uraian` decimal(5,2) DEFAULT 0.00,
  `detail_uraian` text NOT NULL,
  `tanggal_ujian` datetime NOT NULL,
  `status_penilaian` enum('otomatis','perlu_dinilai','selesai') DEFAULT 'otomatis'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `nilai`
--

INSERT INTO `nilai` (`id_nilai`, `id_siswa`, `nama_siswa`, `kode_soal`, `total_soal`, `jawaban_benar`, `jawaban_salah`, `jawaban_kurang`, `jawaban_siswa`, `kunci`, `nilai`, `nilai_uraian`, `detail_uraian`, `tanggal_ujian`, `status_penilaian`) VALUES
(5, 2, 'ae', 'INFO10', 1, '1', '0', '0', '[1:pilihan_3]', '[1:pilihan_3]', '100.00', '0.00', '', '2025-11-02 15:50:08', 'selesai'),
(6, 3, 'Intan', 'INFO10', 2, '0', '1', '1', '[1:pilihan_2][2:budi]', '[1:pilihan_3],[2:budi]', '0.00', '50.00', '[2:50.00]', '2026-01-21 22:59:47', 'perlu_dinilai');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL DEFAULT 1,
  `nama_aplikasi` varchar(100) DEFAULT 'CBT Siswa',
  `logo_sekolah` varchar(255) DEFAULT '',
  `warna_tema` varchar(10) DEFAULT '#0d6efd',
  `waktu_sinkronisasi` int(11) DEFAULT 60,
  `sembunyikan_nilai` tinyint(1) DEFAULT 0,
  `login_ganda` enum('izinkan','blokir') DEFAULT 'blokir',
  `chat` varchar(100) NOT NULL,
  `versi_aplikasi` varchar(20) DEFAULT '1.0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_aplikasi`, `logo_sekolah`, `warna_tema`, `waktu_sinkronisasi`, `sembunyikan_nilai`, `login_ganda`, `chat`, `versi_aplikasi`) VALUES
(1, 'E- Exam', 'logo_1760448712.png', '#2f90c1', 60, 1, 'izinkan', 'blokir', '1.1.5');

-- --------------------------------------------------------

--
-- Struktur dari tabel `profil`
--

CREATE TABLE `profil` (
  `id` int(11) NOT NULL,
  `encrypt` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `profil`
--

INSERT INTO `profil` (`id`, `encrypt`) VALUES
(1, 'JmNvcHk7IDIwMjUgR2x1ZHVnIGNvZGVsaXRl');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `password` text NOT NULL,
  `username` varchar(200) NOT NULL,
  `kelas` varchar(100) NOT NULL,
  `rombel` varchar(100) NOT NULL,
  `status` text NOT NULL DEFAULT 'Nonaktif',
  `session_token` varchar(255) NOT NULL,
  `last_activity` datetime DEFAULT NULL,
  `page_url` text NOT NULL,
  `force_logout` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nama_siswa`, `password`, `username`, `kelas`, `rombel`, `status`, `session_token`, `last_activity`, `page_url`, `force_logout`) VALUES
(1, 'ra', 'TvCPa+VVMO/rFCnE6qa5RHY5M3J4MXpBWUdOVWl1NmVCZitSeUE9PQ==', 'P24020050', '1', 'A', 'Nonaktif', '', '2025-10-21 16:40:06', 'http://localhost:8080/cbtta/siswa/ujian.php', 0),
(2, 'ae', 'q123EvkCTcUm7Y99foWuym8vWWlweHZZam9VVFpxSlZDMnBFd0E9PQ==', 'ae', '1', 'A', 'Nonaktif', 'dd3bd1e389676fa25fa81f76dd6dc2355661c816b4afd52d23f47c73dec16056', '2026-01-29 14:54:12', 'http://localhost:8080/cbtta/siswa/dashboard.php', 0),
(3, 'Intan', '7yVJo+w+JN56JHUw3qLhKmhRN3F6SUp6emo0T2hTZjlJd0RHVWc9PQ==', '124132', '1', 'A', 'Nonaktif', 'ca5cf7e9dfc1e50213aaacc4a2d239a2f1a08d41b90f7fb1e0ea40c2feeb841e', '2026-01-21 23:00:10', 'http://localhost:8080/cbtta/siswa/preview_hasil.php?id_siswa=3&kode_soal=INFO10', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `skor_game`
--

CREATE TABLE `skor_game` (
  `id` int(11) NOT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `nama_game` varchar(50) DEFAULT NULL,
  `skor` int(11) DEFAULT 0,
  `waktu` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `soal`
--

CREATE TABLE `soal` (
  `id_soal` int(11) NOT NULL,
  `kode_soal` varchar(200) NOT NULL,
  `nama_soal` varchar(255) NOT NULL,
  `mapel` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `waktu_ujian` int(11) DEFAULT 60,
  `tanggal` date DEFAULT curdate(),
  `status` text NOT NULL DEFAULT 'Nonaktif',
  `tampilan_soal` varchar(10) NOT NULL,
  `kunci` text NOT NULL,
  `token` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `soal`
--

INSERT INTO `soal` (`id_soal`, `kode_soal`, `nama_soal`, `mapel`, `kelas`, `waktu_ujian`, `tanggal`, `status`, `tampilan_soal`, `kunci`, `token`) VALUES
(1, 'INFO10', 'Gabungan', 'INFORMATIKA', '1', 120, '2025-11-02', 'Aktif', 'Urut', '[1:pilihan_3],[2:budi]', 'KTFHNR');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `butir_soal`
--
ALTER TABLE `butir_soal`
  ADD PRIMARY KEY (`id_soal`);

--
-- Indeks untuk tabel `chat`
--
ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  ADD PRIMARY KEY (`id_jawaban`),
  ADD UNIQUE KEY `id_jawaban` (`id_jawaban`),
  ADD UNIQUE KEY `unik_jawaban` (`id_siswa`,`kode_soal`),
  ADD KEY `kode_soal` (`kode_soal`);

--
-- Indeks untuk tabel `nilai`
--
ALTER TABLE `nilai`
  ADD PRIMARY KEY (`id_nilai`),
  ADD UNIQUE KEY `unique_siswa_soal` (`id_siswa`,`kode_soal`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `profil`
--
ALTER TABLE `profil`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `skor_game`
--
ALTER TABLE `skor_game`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indeks untuk tabel `soal`
--
ALTER TABLE `soal`
  ADD PRIMARY KEY (`id_soal`),
  ADD UNIQUE KEY `kode_soal` (`kode_soal`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `butir_soal`
--
ALTER TABLE `butir_soal`
  MODIFY `id_soal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `chat`
--
ALTER TABLE `chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT untuk tabel `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `jawaban_siswa`
--
ALTER TABLE `jawaban_siswa`
  MODIFY `id_jawaban` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `nilai`
--
ALTER TABLE `nilai`
  MODIFY `id_nilai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `skor_game`
--
ALTER TABLE `skor_game`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `soal`
--
ALTER TABLE `soal`
  MODIFY `id_soal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `skor_game`
--
ALTER TABLE `skor_game`
  ADD CONSTRAINT `skor_game_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
