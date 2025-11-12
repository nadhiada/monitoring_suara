-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 04 Okt 2025 pada 17.53
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
-- Database: `db_monitoring`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `lampu`
--

CREATE TABLE `lampu` (
  `lamp_id` int(11) NOT NULL,
  `lamp_name` varchar(50) NOT NULL,
  `status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lampu`
--

INSERT INTO `lampu` (`lamp_id`, `lamp_name`, `status`) VALUES
(1, 'Lampu Merah', 'OFF'),
(2, 'Lampu Kuning', 'OFF'),
(3, 'Lampu Hijau', 'OFF');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sensors`
--

CREATE TABLE `sensors` (
  `sensor_id` int(11) NOT NULL,
  `sensor_name` varchar(50) NOT NULL,
  `sensor_type` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sensors`
--

INSERT INTO `sensors` (`sensor_id`, `sensor_name`, `sensor_type`) VALUES
(1, 'KY-037', 'Suara');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sensor_log`
--

CREATE TABLE `sensor_log` (
  `id` int(11) NOT NULL,
  `sensor_id` int(11) NOT NULL,
  `lamp_id` int(11) NOT NULL,
  `sound_level` int(11) NOT NULL,
  `sound_status` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sensor_log`
--

INSERT INTO `sensor_log` (`id`, `sensor_id`, `lamp_id`, `sound_level`, `sound_status`, `created_at`) VALUES
(1, 1, 1, 900, 'TINGGI', '2025-09-24 11:37:31'),
(2, 1, 2, 500, 'SEDANG', '2025-09-24 11:37:31'),
(3, 1, 3, 120, 'RENDAH', '2025-09-24 11:37:31'),
(4, 1, 2, 300, 'RENDAH', '2025-10-01 09:35:00'),
(21, 1, 2, 300, 'RENDAH', '2025-10-01 09:55:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFX6f.Q3e.Hn4nsa3lre9YH6Y4X8f5.u', 'admin', '2025-10-02 13:49:03'),
(2, 'user', '$2y$10$u1afD0JMCxjcN46D9XjzYe2o6SdvF2p7M5kHYFYQbknqg6pN2Q2kK', 'user', '2025-10-02 13:49:03'),
(3, 'widya', '$2y$10$QTEO6OQzopvRxq.FS.JDF.2tO5yBQSxUveszxcedumHCYhpx92.9i', 'admin', '2025-10-02 13:49:32'),
(5, 'ratna', '$2y$10$wB6082MwzQ9VCLRKkzLTOuXTbAot1NpKcg9Yo3wPi6eGFFjCoSLJi', 'admin', '2025-10-04 15:31:10');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `lampu`
--
ALTER TABLE `lampu`
  ADD PRIMARY KEY (`lamp_id`);

--
-- Indeks untuk tabel `sensors`
--
ALTER TABLE `sensors`
  ADD PRIMARY KEY (`sensor_id`);

--
-- Indeks untuk tabel `sensor_log`
--
ALTER TABLE `sensor_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sensor_id` (`sensor_id`),
  ADD KEY `lamp_id` (`lamp_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `lampu`
--
ALTER TABLE `lampu`
  MODIFY `lamp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `sensors`
--
ALTER TABLE `sensors`
  MODIFY `sensor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `sensor_log`
--
ALTER TABLE `sensor_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `sensor_log`
--
ALTER TABLE `sensor_log`
  ADD CONSTRAINT `sensor_log_ibfk_1` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`),
  ADD CONSTRAINT `sensor_log_ibfk_2` FOREIGN KEY (`lamp_id`) REFERENCES `lampu` (`lamp_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
