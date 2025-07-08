-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 08, 2025 at 04:06 AM
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
-- Database: `perpustakaan`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','librarian','staff') DEFAULT 'admin',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@perpustakaan.com', '$2y$10$cGqdOmDDpWie6BaXMEU2a.s/WIixWHl11p5RFhmBXBzHxJusRmCr6', 'Administrator Sistem', 'super_admin', 'active', '2025-07-08 02:01:13', '2025-06-13 20:31:25', '2025-07-08 02:01:13'),
(2, 'librarian', 'librarian@perpustakaan.com', '$2y$10$KQ25YUw/GxUAxjWjMZm5AOXaZhngvoMGxM6eGT4xBVSLYrAjoM/iy', 'Pustakawan Utama', 'librarian', 'active', '2025-07-08 01:29:03', '2025-06-13 20:31:25', '2025-07-08 01:29:03'),
(3, 'staff', 'staff@perpustakaan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Perpustakaan', 'staff', 'inactive', NULL, '2025-06-13 20:31:25', '2025-07-08 01:29:55'),
(5, 'guest', 'guest@perpustakaan.com', '$2y$10$LgSCw7ORw4bYrSgeCZv3puA0I/nb.4ij97E6RN6i.UnhHy90UFj0O', 'Tamu Perpustakaan', 'staff', 'inactive', '2025-07-08 01:29:12', '2025-06-13 20:31:37', '2025-07-08 01:34:40'),
(16, 'saya', 'saya@gmail.com', '$2y$10$MxkCsMECkoEph0t6XCJiYev0VgQzCn/5fM4aloVE6ghXHTIsdcPXy', 'Peretas Handal', 'admin', 'suspended', '2025-06-27 09:46:29', '2025-06-13 22:14:14', '2025-07-08 01:31:02'),
(17, 'tester', 'tester@perpustakaan.com', '$2y$10$Ze3jO/UaqIIGXF9ConZIveXRRJuOQ87KGMLrwh9fSUq/IWPwqDBL.', 'Penguji', 'admin', 'inactive', '2025-07-08 01:01:59', '2025-07-08 00:15:31', '2025-07-08 01:27:01'),
(18, 'new_staff', 'staff2@perpustakaan.com', '$2y$10$lwLgUiFMfVsbRkoFdez2B.rhFVY7gqFMX3IfKHo2JOebD6QQ4xUXy', 'Staff Perpustakaan', 'staff', 'active', NULL, '2025-07-08 01:30:49', '2025-07-08 01:34:28'),
(19, 'client', 'client@perpustakaan.com', '$2y$10$b5rFGaNdhA2pQoH5lmC4bOYPlsca2vxvXML/SvEMro4LdR9eNXyKe', 'Klien Reguler', 'librarian', 'active', NULL, '2025-07-08 01:31:57', '2025-07-08 01:31:57'),
(20, 'inspector', 'inspector@perpustakaan.com', '$2y$10$QumAhXIGElrjnYOZ5r0rTuexHdalhwJrrFjjlUz1uAq88TId.Vfce', 'Inspektur', 'admin', 'inactive', NULL, '2025-07-08 01:33:37', '2025-07-08 01:33:43'),
(21, 'old_staff', 'staff1@perpustakaan.com', '$2y$10$dNbfVG7CTpY7kyOVGt3Op.pqKVmy.WMARQxOz6zKRAE/Dmiox9Zhm', 'Staff Perpustakaan', 'staff', 'inactive', NULL, '2025-07-08 01:34:22', '2025-07-08 01:34:33');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publisher` varchar(255) NOT NULL,
  `year_published` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1,
  `location` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00,
  `category` varchar(100) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `publisher`, `year_published`, `isbn`, `category_id`, `total_copies`, `available_copies`, `location`, `description`, `cover_image`, `created_at`, `updated_at`, `price`, `category`) VALUES
(1, 'Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, '9789793062792', 1, 3, 1, 'Rak A1', 'Novel tentang perjuangan anak-anak Belitung untuk mendapatkan pendidikan', 'cover_685e01ad23ab1.png', '2025-06-08 12:10:23', '2025-07-08 01:02:26', 50000.00, 'Fiksi'),
(2, 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, '9789799731234', 1, 2, 2, 'Rak A2', 'Novel sejarah tentang perjuangan bangsa Indonesia', 'cover_686c760ac9dbb.jpg', '2025-06-08 12:10:23', '2025-07-08 01:36:10', 50000.00, 'Fiksi'),
(3, 'Sapiens', 'Yuval Noah Harari', 'Gramedia', 2018, '9786020633176', 2, 2, 2, 'Rak B1', 'Sejarah singkat umat manusia', 'cover_686c75ee8b030.jpg', '2025-06-08 12:10:23', '2025-07-08 01:35:42', 50000.00, 'Non-Fiksi'),
(4, 'Filosofi Teras', 'Henry Manampiring', 'Kompas Gramedia', 2018, '9786020633183', 8, 4, 4, 'Rak B2', 'Filosofi Stoikisme untuk kehidupan modern', 'cover_686c766b818f1.jpg', '2025-06-08 12:10:23', '2025-07-08 01:51:09', 50000.00, 'Psikologi'),
(5, 'Kimia Dasar', 'Hardjono Sastrohamidjojo', 'UGM PRESS', 2012, '979-420-489-7', 3, 5, 5, 'Rak C1', 'Buku teks kimia untuk mahasiswa', 'cover_686c7654a6afc.jpg', '2025-06-08 12:10:23', '2025-07-08 01:37:24', 50000.00, 'Sains & Teknologi'),
(6, 'Matematika SMA XI', 'Sukino', 'Erlangga', 2016, '9786022987932', 4, 10, 10, 'Rak D1', 'Buku pelajaran matematika SMA XI', 'cover_686c767ce8910.jpg', '2025-06-08 12:10:23', '2025-07-08 01:38:07', 50000.00, 'Pendidikan'),
(8, 'Rich Dad Poor Dad', 'Robert Kiyosaki', 'Gramedia', 2017, '9786020633190', 6, 2, 2, 'Rak F1', 'Buku tentang literasi keuangan dan investasi', 'cover_686c768ed736e.jpg', '2025-06-08 12:10:23', '2025-07-08 01:38:22', 50000.00, 'Bisnis & Ekonomi'),
(9, 'Atomic Habits', 'James Clear', 'Gramedia', 2021, '9786020637296', 2, 5, 5, 'Rak B1', 'Panduan membangun kebiasaan kecil yang berdampak besar', 'cover_686c789123354.jpg', '2025-06-21 07:23:00', '2025-07-08 01:57:29', 85000.00, 'Non-Fiksi'),
(10, 'The Psychology of Money', 'Morgan Housel', 'Gramedia', 2022, '9786020645185', 8, 4, 4, 'Rak F2', 'Tentang cara manusia memandang dan mengelola uang', 'cover_686c77b87e209.jpg', '2025-06-18 02:45:00', '2025-07-08 01:52:46', 90000.00, 'Psikologi'),
(11, 'Filosofi Kopi', 'Dee Lestari', 'Bentang Pustaka', 2006, '9789793062730', 1, 3, 3, 'Rak A1', 'Kumpulan cerita dan filosofi tentang kopi dan kehidupan', 'cover_686c78e34b070.jpg', '2025-06-30 04:00:00', '2025-07-08 01:57:25', 60000.00, 'Fiksi'),
(12, 'Thinking, Fast and Slow', 'Daniel Kahneman', 'Farrar, Straus dan Giroux', 2020, '9786020639191', 8, 5, 5, 'Rak B1', 'Buku psikologi tentang cara manusia mengambil keputusan', 'cover_686c77f19364b.jpg', '2025-06-09 10:22:00', '2025-07-08 01:53:02', 95000.00, 'Psikologi'),
(13, 'Negeri 5 Menara', 'Ahmad Fuadi', 'Gramedia', 2009, '9789793062793', 1, 3, 3, 'Rak A2', 'Novel inspirasi tentang persahabatan di pesantren', 'cover_686c775c6c548.jpeg', '2025-06-25 06:10:00', '2025-07-08 01:52:39', 75000.00, 'Fiksi'),
(14, 'Sebuah Seni Untuk Bersikap Bodo Amat', 'Mark Manson', 'Grasindo', 2019, '9786020636848', 2, 6, 6, 'Rak B2', 'Buku motivasi tentang hidup sederhana dan realistis', 'cover_686c78610109b.jpg', '2025-06-16 01:50:00', '2025-07-08 01:57:20', 85000.00, 'Non-Fiksi'),
(15, 'Dunia Sophie', 'Jostein Gaarder', 'Mizan', 2001, '246845141', 8, 2, 2, 'Rak C1', 'Novel filsafat yang memperkenalkan sejarah filsafat dunia', 'cover_686c76f2737c9.jpeg', '2025-06-29 12:40:00', '2025-07-08 01:52:26', 65000.00, 'Psikologi'),
(16, 'Belajar Python untuk Pemula', 'Muhammad Ihsan', 'Informatika', 2022, '9786232458910', 3, 3, 3, 'Rak C2', 'Panduan belajar pemrograman Python dasar hingga mahir', 'cover_686c7828374c5.jpeg', '2025-06-11 03:20:00', '2025-07-08 01:57:14', 120000.00, 'Sains & Teknologi'),
(17, 'Creative Confidence', 'Tom Kelley & David Kelley', 'Gramedia', 2018, '9786020633182', 2, 4, 4, 'Rak F2', 'Buku tentang keberanian dan kreativitas dalam bekerja', 'cover_686c77ddf1dee.jpg', '2025-06-14 09:05:00', '2025-07-08 01:52:54', 88000.00, 'Non-Fiksi'),
(18, 'Sejarah Indonesia Modern', 'MC Ricklefs', 'Serambi', 2007, '9789790242511', 7, 2, 2, 'Rak D1', 'Buku referensi sejarah Indonesia dari abad 13 hingga 20', 'cover_686c78ba4251e.jpg', '2025-06-27 00:35:00', '2025-07-08 01:57:08', 90000.00, 'Sejarah');

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowings`
--

INSERT INTO `borrowings` (`id`, `member_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `fine_amount`, `notes`, `created_at`, `updated_at`) VALUES
(16, 8, 2, '2025-06-13', '2025-06-27', '2025-06-13', 'returned', 0.00, NULL, '2025-06-13 00:41:56', '2025-06-13 04:04:22'),
(17, 8, 1, '2025-06-14', '2025-06-28', '2025-06-14', 'returned', 0.00, NULL, '2025-06-13 04:04:48', '2025-06-13 23:18:55'),
(18, 8, 8, '2025-06-14', '2025-06-28', '2025-06-14', 'returned', 0.00, NULL, '2025-06-13 04:04:57', '2025-06-13 23:18:53'),
(19, 9, 4, '2025-06-13', '2025-06-20', '2025-06-13', 'returned', 0.00, NULL, '2025-06-13 04:09:00', '2025-06-13 20:19:36'),
(20, 9, 2, '2025-06-13', '2025-06-20', '2025-06-13', 'returned', 0.00, NULL, '2025-06-13 20:17:19', '2025-06-13 20:17:56'),
(21, 9, 2, '2025-06-13', '2025-06-20', '2025-06-13', 'returned', 0.00, NULL, '2025-06-13 20:18:03', '2025-06-13 20:18:15'),
(22, 9, 2, '2025-06-13', '2025-06-20', '2025-06-13', 'returned', 0.00, NULL, '2025-06-13 20:18:25', '2025-06-13 20:18:42'),
(23, 9, 5, '2025-06-13', '2025-06-20', '2025-06-13', 'returned', 0.00, NULL, '2025-06-13 20:19:08', '2025-06-13 20:20:28'),
(24, 9, 2, '2025-06-14', '2025-06-28', '2025-06-27', 'returned', 0.00, NULL, '2025-06-13 20:19:57', '2025-06-27 04:01:43'),
(25, 9, 2, '2025-06-14', '2025-06-21', '2025-06-27', 'returned', 6000.00, NULL, '2025-06-13 23:15:31', '2025-06-27 03:44:45'),
(26, 8, 8, '2025-06-14', '2025-06-21', '2025-06-27', 'returned', 6000.00, NULL, '2025-06-13 23:19:19', '2025-06-27 07:28:47'),
(27, 8, 5, '2025-06-27', '2025-07-11', '2025-06-27', 'returned', 0.00, NULL, '2025-06-27 03:57:55', '2025-06-27 04:01:45'),
(28, 9, 2, '2025-06-27', '2025-07-11', '2025-06-27', 'returned', 0.00, NULL, '2025-06-27 03:59:11', '2025-06-27 04:01:46'),
(29, 8, 5, '2025-06-27', '2025-07-04', '2025-07-08', 'returned', 4000.00, NULL, '2025-06-27 04:01:58', '2025-07-08 01:02:10'),
(30, 8, 1, '2025-06-27', '2025-07-11', '2025-07-08', 'returned', 0.00, NULL, '2025-06-27 07:29:12', '2025-07-08 01:02:26'),
(31, 9, 1, '2025-06-27', '2025-07-11', NULL, 'borrowed', 0.00, NULL, '2025-06-27 09:36:57', '2025-06-27 09:36:57'),
(32, 9, 6, '2025-06-27', '2025-07-04', '2025-07-08', 'returned', 4000.00, NULL, '2025-06-27 09:37:04', '2025-07-08 01:02:12'),
(33, 12, 1, '2025-07-03', '2025-07-10', NULL, 'borrowed', 0.00, NULL, '2025-07-03 07:34:56', '2025-07-03 07:34:56'),
(34, 12, 3, '2025-07-03', '2025-07-17', NULL, 'borrowed', 0.00, NULL, '2025-07-03 07:35:03', '2025-07-03 07:35:03'),
(35, 14, 2, '2025-07-08', '2025-07-22', NULL, 'borrowed', 0.00, NULL, '2025-07-08 00:59:47', '2025-07-08 00:59:47'),
(36, 14, 3, '2025-07-08', '2025-07-22', NULL, 'borrowed', 0.00, NULL, '2025-07-08 01:00:17', '2025-07-08 01:00:17');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Fiksi', 'Novel, cerpen, dan karya sastra fiksi', '2025-06-08 12:10:23'),
(2, 'Non-Fiksi', 'Buku faktual, biografi, sejarah', '2025-06-08 12:10:23'),
(3, 'Sains & Teknologi', 'Buku tentang ilmu pengetahuan dan teknologi', '2025-06-08 12:10:23'),
(4, 'Pendidikan', 'Buku pelajaran dan referensi akademik', '2025-06-08 12:10:23'),
(5, 'Agama & Spiritual', 'Buku keagamaan dan spiritual', '2025-06-08 12:10:23'),
(6, 'Bisnis & Ekonomi', 'Buku tentang bisnis, ekonomi, dan manajemen', '2025-06-08 12:10:23'),
(7, 'Sejarah', 'Buku tentang sejarah, peristiwa, dan peradaban dunia', '2025-07-08 04:00:00'),
(8, 'Psikologi', 'Buku tentang ilmu psikologi dan pengembangan diri', '2025-07-08 04:00:00'),
(9, 'Komik & Manga', 'Buku komik, manga, dan novel grafis', '2025-07-08 04:00:00'),
(10, 'Politik & Hukum', 'Buku tentang politik, pemerintahan, dan hukum', '2025-07-08 04:00:00'),
(11, 'Kesehatan & Gaya Hidup', 'Buku tentang kesehatan, kebugaran, dan gaya hidup', '2025-07-08 04:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `member_code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('L','P') NOT NULL,
  `member_type` enum('student','teacher','public') DEFAULT 'public',
  `join_date` date NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `first_login` tinyint(1) DEFAULT 1,
  `max_borrow_limit` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `member_code`, `name`, `email`, `password`, `phone`, `address`, `date_of_birth`, `gender`, `member_type`, `join_date`, `status`, `first_login`, `max_borrow_limit`, `created_at`, `updated_at`) VALUES
(8, 'MBR20250169', 'Kim yoo-Jung', 'k.yoojung@stis.ac.id', '$2y$10$44tRRnwRYFkNK5QlSEaLWuGzmmKm0jmdgOOTXCxzDqTboRyIYIdR.', '081111111111', 'Distrik Seongdong, Seoul, Korea Selatan', '1999-09-22', 'P', 'public', '2025-06-13', 'active', 0, 2, '2025-06-12 21:41:01', '2025-07-08 00:42:13'),
(9, 'MBR20253038', 'Muhammad Fauzan', 'm.fauzan@gmail.com', '$2y$10$KILEERZ7B4NkHnu8Zc9dbe4N/seNTsHFBA3xf/RI3cg9mtvyjywVa', '082211969658', 'Bukittinggi, Sumatera Barat', '2005-06-06', 'L', 'public', '2025-06-13', 'active', 0, 2, '2025-06-12 22:51:25', '2025-06-13 20:14:20'),
(11, 'MBR20252450', 'Zaki Azzuhdi', 'z.azzuhdi@gmail.com', '$2y$10$56J5Y8DsqZOJebJKUu3XMu1q5Xa0U5tGH5aRZBbXhPhtd/1JxSimC', '081231231231', 'Jakarta Barat', '2009-06-18', 'L', 'student', '2025-06-27', 'active', 0, 3, '2025-06-27 04:38:54', '2025-07-08 01:15:37'),
(12, 'MBR20257450', 'Diah Ayu Nur Rahmadani', 'd.ayu@gmail.com', '$2y$10$50GtZDG9YOFWqfwwtse.sOdn6esMU.ckLSyDPkbZ/2Mw2Vtj6kbC.', '089898989898', 'Jakarta Timur', '2004-06-16', 'P', 'public', '2025-07-03', 'active', 0, 2, '2025-07-03 07:32:59', '2025-07-03 07:34:00'),
(13, 'MBR20252002', 'Flarisa Ikhtiasa Saliha', 'f.ikhtiasa@gmail.com', '$2y$10$pMYunK1rqpokPaIFtrCuQ.rw639vcMNLt1RrgkuJzkAZ2BxY3LVyC', '081293852383', 'Bukittinggi, Sumatra Barat', '2005-06-30', 'P', 'student', '2025-07-08', 'active', 0, 3, '2025-07-08 00:52:44', '2025-07-08 01:16:04'),
(14, 'MBR20252476', 'Hendra Kariman', 'h.kariman@gmail.com', '$2y$10$o7FcxHkRZdGI/WElK4Z1/erHrjeOvELXlqBdDUavLpxwzTIHHEL2m', '083176834953', 'Padang, Sumatra Barat', '1975-06-18', 'L', 'teacher', '2025-07-08', 'active', 0, 5, '2025-07-08 00:54:17', '2025-07-08 00:55:43'),
(15, 'MBR20254862', 'Habil Adil', 'h.adil@gmail.com', '$2y$10$3ahrCxayNbTJqXsITj9Kr.iQREEi5XCuuCpIiJ.lQtZjKi3NTVWv6', '082186942384', 'Palembang, Sumatra Selatan', '2005-05-05', 'L', 'student', '2025-07-08', 'active', 0, 3, '2025-07-08 01:13:56', '2025-07-08 01:15:15'),
(16, 'MBR20252471', 'Yasmin Juwahir', 'y.juwahir@gmail.com', '$2y$10$cfvGB1j516lr/sDIU5lQW.7KvWNAPI2LJ6jJMg3e9PatXawrxe2DW', '081385737490', 'Padang, Sumatra Barat', '2005-10-12', 'P', 'public', '2025-07-08', 'active', 0, 2, '2025-07-08 01:18:13', '2025-07-08 01:25:02'),
(17, 'MBR20258862', 'Anthony', 'anthony@gmail.com', '$2y$10$WTpWCqmtmdrhVUEKUX2xzerJzlJHXmrjETwXFxI67OeY1XuvlDbPy', '+441616767770', 'Manchester, England', '2000-02-24', 'L', 'public', '2025-07-08', 'active', 0, 2, '2025-07-08 01:21:17', '2025-07-08 01:26:03'),
(18, 'MBR20251402', 'Putri Naila Safira', 'p.naila@gmail.com', '$2y$10$s/XXS8nnP/..sQ6uf6ERbuWvLwoifkD0x4uBmJOyuFjYIInMXaj9W', '0812987678876', 'Australia', '2005-05-11', 'P', 'student', '2025-07-08', 'active', 0, 3, '2025-07-08 01:23:31', '2025-07-08 01:25:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `member_code` (`member_code`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
