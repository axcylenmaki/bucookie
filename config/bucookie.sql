-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Apr 2026 pada 10.16
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bucookie`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(100) NOT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `books`
--

INSERT INTO `books` (`id`, `category_id`, `title`, `author`, `publisher`, `year`, `cost_price`, `price`, `stock`, `description`, `cover`, `created_at`) VALUES
(6, 3, 'Bumi', 'Tere Liye', 'SABAKGRIP', '2024', 85000.00, 103000.00, 100, 'mengisahkan petualangan Raib (15 tahun), remaja yang bisa menghilang, bersama dua temannya, Seli (penjinak api) dan Ali (genius), di dunia paralel. Mereka terseret konflik antarklan setelah bertemu Tamus, sosok jahat yang ingin menguasai Klan Bulan dan Bumi', 'cover_69d705c9e7c54.jpg', '2026-04-09 01:50:01'),
(7, 3, 'Bulan', 'Tere Liye', 'SABAKGRIP', '2025', 80000.00, 95000.00, 97, 'Seri kedua dari serial Bumi yang menceritakan petualangan Raib, Seli, dan Ali di dunia paralel, khususnya Klan Matahari. Mereka berpetualang mencari bunga matahari pertama, mengikuti kompetisi, dan berjuang melawan Si Tanpa Mahkota demi persahabatan dan perdamaian.', 'cover_69d70623ab428.jpg', '2026-04-09 01:51:31'),
(8, 9, 'Buku Ekonomi: Suatu Pengantar', 'Sri Suro Adhawati.', '', '2026', 100000.00, 116000.00, 98, 'Buku Pengantar Ekonomi ini merupakan buku pegangan bagi mahasiswa guna strategi pembelajaran yang aktraktif guna memberi bekal pengetahuan dasar dan daya tarik bagi mahasiswa yang belum mempunyai pemahaman dan wawasan yang mendalam tentang teori ekonomi. Supaya Lebih paham, baca Daftar isi Ekonomi terbaik ini Daftar isi Buku Ekonomi yang berjudul Buku Ekonomi: Suatu Pengantar, diantaranya Bab 1 Ruang Lingkup dan Masalah Ekonomi Bab 2 Sistem Perekonomian dan Mekanisme Pasar Bab 3 Teori Permintaan dan Teori Penawaran Bab 4 Bentuk-Bentuk Pasar Bab 5 Kebijakan Pemerintah Bab 6 Uang dan Lembaga Keuangan', 'cover_69d706fcf1780.jpg', '2026-04-09 01:55:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(3, 'Fiksi', 'Kategori ini berisi karya imajinatif, seperti novel dan cerpen. Ini adalah salah satu mesin uang utama di toko buku .', '2026-03-10 01:46:44'),
(4, 'Non-Fiksi', 'Kategori ini berisi buku berdasarkan fakta, data, dan kejadian nyata. Tujuannya untuk menginformasi, mendidik, atau memberikan panduan.', '2026-03-10 01:47:07'),
(5, 'Pendidikan & Akademik', 'Buku-buku yang dirancang khusus untuk mendukung proses belajar mengajar, dari tingkat dasar hingga perguruan tinggi .', '2026-03-10 01:47:28'),
(6, 'Anak-Anak', 'Kategori khusus yang disesuaikan dengan usia, minat, dan pemahaman anak-anak, biasanya kaya akan ilustrasi .', '2026-03-10 01:50:43'),
(7, 'Agama & Spiritualitas', 'Buku-buku yang membahas ajaran agama, nilai-nilai spiritual, dan panduan menjalani kehidupan beragama .', '2026-03-10 01:50:55'),
(8, 'Hobi & Keterampilan', 'Buku panduan praktis untuk membantu pembaca mengembangkan hobi atau mempelajari keterampilan baru yang spesifik .', '2026-03-10 01:51:06'),
(9, 'Bisnis, Ekonomi & Pengembangan Diri', 'Kategori ini sangat populer, terutama di kalangan profesional dan dewasa muda yang ingin meningkatkan kualitas diri dan karier .', '2026-03-10 01:51:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `contact_info`
--

CREATE TABLE `contact_info` (
  `id` int(11) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `contact_info`
--

INSERT INTO `contact_info` (`id`, `whatsapp`, `email`, `updated_at`) VALUES
(2, '6285697011994', 'admin@bucookie.com', '2026-03-10 02:31:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sender` enum('user','admin') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `sender`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'user', 'tes', 1, '2026-04-08 13:41:08'),
(2, 2, 'admin', 'halo, ada yang bisa di bantu?', 1, '2026-04-08 13:41:24'),
(3, 2, 'user', 'dimana pesanan saya?', 1, '2026-04-09 02:27:24'),
(4, 2, 'admin', 'silahkan di lacak melalui link yang tertera', 1, '2026-04-09 02:31:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `reference_id`, `message`, `created_at`) VALUES
(1, 'message', 2, 'Pesan baru dari Yuki: tes', '2026-04-08 13:41:08'),
(2, 'order', 8, 'Pesanan baru #8 dari Yuki', '2026-04-08 14:28:32'),
(3, 'order', 9, 'Pesanan baru #9 dari Yuki', '2026-04-08 14:58:13'),
(4, 'order', 10, 'Pesanan baru #10 dari Yuki', '2026-04-09 02:24:07'),
(5, 'message', 2, 'Pesan baru dari Yuki: dimana pesanan saya?', '2026-04-09 02:27:24'),
(6, 'order', 11, 'Pesanan baru #11 dari Yuki', '2026-04-09 07:56:10'),
(7, 'order', 12, 'Pesanan baru #12 dari Yuki', '2026-04-09 08:01:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `shipping_address` text NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `expedition` varchar(50) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `payment_method` enum('payment_at_delivery') DEFAULT 'payment_at_delivery',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_price`, `shipping_address`, `status`, `expedition`, `tracking_number`, `payment_method`, `created_at`, `updated_at`) VALUES
(1, 2, 160000.00, 'Jalan Duren besar nomor 45 blok A', 'delivered', NULL, NULL, 'payment_at_delivery', '2026-03-10 01:58:54', '2026-03-10 02:13:43'),
(2, 2, 80000.00, 'Jalan Duren besar nomor 45 blok A', 'delivered', NULL, NULL, 'payment_at_delivery', '2026-03-10 02:09:55', '2026-03-10 02:39:02'),
(3, 5, 170000.00, 'Jalan Damai 2 Kota bekasi', 'delivered', NULL, NULL, 'payment_at_delivery', '2026-03-10 02:15:36', '2026-03-10 02:38:50'),
(4, 5, 80000.00, 'Jalan Damai 2 Kota bekasi', 'delivered', NULL, NULL, 'payment_at_delivery', '2026-03-10 02:46:12', '2026-03-10 02:52:24'),
(5, 5, 80000.00, 'Jalan Damai 2 Kota bekasi', 'cancelled', NULL, NULL, 'payment_at_delivery', '2026-03-10 02:47:31', '2026-03-10 02:47:41'),
(6, 5, 80000.00, 'Jalan Damai 2 Kota bekasi', 'delivered', 'sicepat', 'SCPT1234', 'payment_at_delivery', '2026-03-10 02:53:02', '2026-04-09 02:22:49'),
(7, 2, 160000.00, 'Jalan Duren besar nomor 45 blok A', 'cancelled', NULL, NULL, 'payment_at_delivery', '2026-03-10 04:04:49', '2026-03-10 04:04:53'),
(8, 2, 80000.00, 'Jalan Duren besar nomor 45 blok A', 'cancelled', 'sicepat', 'SCPT2345', 'payment_at_delivery', '2026-04-08 14:28:32', '2026-04-09 02:00:55'),
(9, 2, 20000.00, 'Jalan Duren besar nomor 45 blok A', 'cancelled', 'sicepat', 'SCPT1234', 'payment_at_delivery', '2026-04-08 14:58:13', '2026-04-09 02:00:50'),
(10, 2, 306000.00, 'Jalan Duren besar nomor 45 blok A', 'delivered', 'jnt', 'jt45', 'payment_at_delivery', '2026-04-09 02:24:06', '2026-04-09 02:26:22'),
(11, 2, 116000.00, 'Jalan Duren besar nomor 45 blok A', 'delivered', 'jnt', '123', 'payment_at_delivery', '2026-04-09 07:56:10', '2026-04-09 08:02:04'),
(12, 2, 95000.00, 'Jalan Duren besar nomor 45 blok A', 'shipped', 'sicepat', 'SCPT1234', 'payment_at_delivery', '2026-04-09 08:01:49', '2026-04-09 08:05:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `book_id`, `quantity`, `price`) VALUES
(11, 10, 8, 1, 116000.00),
(12, 10, 7, 2, 95000.00),
(13, 11, 8, 1, 116000.00),
(14, 12, 7, 1, 95000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expired` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `avatar`, `role`, `reset_token`, `reset_expired`, `created_at`) VALUES
(2, 'Yuki', 'ayushafira2107@gmail.com', '$2y$10$qh4XkPEVlXMzYKFuiWNL8.SC65VRVRZVwfWtFwzLVRubgaGjncwwW', '095717063811', 'Jalan Duren besar nomor 45 blok A', 'avatar_2_1773111427.png', 'user', NULL, NULL, '2026-03-10 00:56:05'),
(3, 'Administrator', 'admin@bucookie.com', '$2y$10$VJBpE1wKMSD90Sts61Pu0O9cK12d5Fr91n6Ajos3P2dZaUF8Vxzlm', '085697011994', '', 'avatar_3_1773111785.png', 'admin', NULL, NULL, '2026-03-10 01:03:49'),
(5, 'Ayu', 'ayusyafira3003@gmail.com', '$2y$10$yFz9jBi6VF8SoxJXVdPCEeJloC7evvqNI7YEDjR5op.oOaj5irSeG', '085697011994', 'Jalan Damai 2 Kota bekasi', NULL, 'user', NULL, NULL, '2026-03-10 02:14:50');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `contact_info`
--
ALTER TABLE `contact_info`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `contact_info`
--
ALTER TABLE `contact_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
