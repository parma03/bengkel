-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2025 at 01:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_bengkel`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_barang`
--

CREATE TABLE `tb_barang` (
  `id_barang` bigint(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `stok_barang` int(100) DEFAULT NULL,
  `foto_barang` text DEFAULT NULL,
  `harga_barang` decimal(15,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_barang`
--

INSERT INTO `tb_barang` (`id_barang`, `nama_barang`, `stok_barang`, `foto_barang`, `harga_barang`, `created_at`, `updated_at`) VALUES
(1, 'oli Yamalube', 100, '68716f41d233e.jpg', 10000.00, '2025-07-12 03:07:52', '2025-07-12 03:08:33'),
(2, 'oli Yamalube Merah', 2999, NULL, 100000.00, '2025-07-12 03:08:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_service`
--

CREATE TABLE `tb_service` (
  `id_service` bigint(11) NOT NULL,
  `nama_service` varchar(255) NOT NULL,
  `harga_service` decimal(15,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_service`
--

INSERT INTO `tb_service` (`id_service`, `nama_service`, `harga_service`, `created_at`, `update_at`) VALUES
(1, 'Service Mesin', 10000.00, '2025-07-12 03:32:52', '2025-07-12 03:33:50'),
(2, 'Service CVT', 10000.00, '2025-07-12 03:33:05', NULL),
(3, 'Ganti Oli', 5000.00, '2025-07-12 03:34:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id_transaksi` bigint(11) NOT NULL,
  `id_user` bigint(11) NOT NULL,
  `type_kendaraan` varchar(255) NOT NULL,
  `total_harga` decimal(10,2) DEFAULT NULL,
  `status_pembayaran` enum('pending','paid','failed','cancelled','menunggu','dikerjakan','selesai') NOT NULL,
  `order_id` varchar(100) DEFAULT NULL,
  `snap_token` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`id_transaksi`, `id_user`, `type_kendaraan`, `total_harga`, `status_pembayaran`, `order_id`, `snap_token`, `created_at`, `updated_at`) VALUES
(2, 8, 'Motor', 100000.00, 'selesai', NULL, NULL, '2025-07-14 16:23:24', '2025-07-14 18:13:32');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` bigint(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nohp` int(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('administrator','kasir','mekanik','konsumen') NOT NULL,
  `photo_profile` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `nama`, `nohp`, `email`, `password`, `role`, `photo_profile`, `created_at`, `updated_at`) VALUES
(1, 'tesadmin', 123123, 'admin@gmail.com', '123', 'administrator', '68715ef3968aa.jpg', '2025-07-12 01:58:59', NULL),
(2, 'adadf', 2134234, 'dfsf@gmail.com', '123123', 'administrator', '68716330440d6.png', '2025-07-12 02:17:04', NULL),
(3, 'dfsfsd', 34234234, 'sdfsdf@gmail.com', '123', 'administrator', '68716423012d0.jpg', '2025-07-12 02:21:07', NULL),
(4, 'asdawd', 2131313, 'ada@gmail.com', '123', 'administrator', '687164948e64d.jpg', '2025-07-12 02:23:00', NULL),
(5, 'teskasir', 2147483647, 'teskasir1@gmail.com', '123', 'kasir', '687166d40ecad.jpg', '2025-07-12 02:32:36', '2025-07-12 02:32:45'),
(6, 'tesmekanik1', 8999247, 'tesmekanik1@gmail.com', '123', 'mekanik', '687167c5075ca.jpg', '2025-07-12 02:36:37', '2025-07-12 02:37:11'),
(7, 'tesmekanik2', 8992746, 'tesmekanik2@gmail.com', '123', 'mekanik', NULL, '2025-07-12 02:37:00', NULL),
(8, 'teskonsumen1', 877734664, 'teskonsumen1@gmail.com', '123', 'konsumen', '', '2025-07-12 02:41:31', '2025-07-12 02:41:54'),
(9, 'teskonsumen2', 1097374743, 'teskonsumen2@gmail.com', '123', 'konsumen', '687168fba912e.jpg', '2025-07-12 02:41:47', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_barang`
--
ALTER TABLE `tb_barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `tb_service`
--
ALTER TABLE `tb_service`
  ADD PRIMARY KEY (`id_service`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_barang`
--
ALTER TABLE `tb_barang`
  MODIFY `id_barang` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_service`
--
ALTER TABLE `tb_service`
  MODIFY `id_service` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id_transaksi` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
