-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2025 at 11:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medilink`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_description` text NOT NULL,
  `product_price` double NOT NULL,
  `product_image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `status`) VALUES
(16, 'Luis Gabrielle Estacio', 'estacio.luis.gabrielle@gmail.com', '$2y$10$rNaV0IwAzhoSCMFqIMl8UuuA78ryttdnhGT6JDFX7qpqblTr5Zc/a', 0),
(18, 'Megan Esguerra', '0306meganesguerra20@gmail.com', '$2y$10$qNelLCgbpS.BtiRSWxcdLuVIw.4Js/ifeE5WA2eVhu1pC83MjHQry', 1),
(22, 'Test User', 'test@example.com', '$2y$10$dn1hqzU2MhfzMYuLIC.9yuFzHuZvEIKlGe3xlsUG4SswrmrIpuQ9y', 0),
(23, 'Test User', 'test@example.com', '$2y$10$VeeZA2TxoOo8G0/GC3MqlethURAnt7A5yKiuxu9O2hAjNNH4GEnwq', 0),
(24, 'Test User', 'test@example.com', '$2y$10$DYm1M/lriattUFLJ2NIwYOfL0Ti7iBZR4nkMfyvG25.hK6Ozub.jq', 0),
(25, 'Test User', 'test@example.com', '$2y$10$JisfMX2WdEBafDNjg8Ti5uX5jWbQ2rfsVHAltqmtwqR36rEi/1xDe', 0),
(26, 'Test User', 'test@example.com', '$2y$10$GcizNblazrsXR5u3wifx/.KbanWLHKmLwxlo5qeV0Re9UZs8Qt9J.', 0),
(27, 'Test User', 'test@example.com', '$2y$10$RSRxTE4uMBGRYrjHUPPcyuhBKk511.X.bv0ZpsbaJUYsrzYSzKjAG', 0),
(28, 'Test User', 'test@example.com', '$2y$10$66ekB8XzUOHNSJAbuL6Gfe4ZUtI0eSWzMRbpzAZzzjLlSOwUBM4Jy', 0),
(29, 'Test User', 'test@example.com', '$2y$10$u8KJh0nhgZHJIbMHX70L6uSAIGdwxytiAJpIUdpNTlaxCGLbDgDEW', 0),
(30, 'Test User', 'test@example.com', '$2y$10$GpgcIEVHgZe63JN4GyT18u8ZZhlcOAxqa7tb6vdLBsZR9oMdMbKuq', 0),
(31, 'Test User', 'test@example.com', '$2y$10$M4UezQeEsupOe9CMyTWmu.Qbwa8uwXmR.NeR8HniXqcPBqE6pd7yi', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
