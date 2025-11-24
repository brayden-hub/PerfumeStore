-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 06:49 PM
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
-- Database: `db1`
--
CREATE DATABASE IF NOT EXISTS `db1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db1`;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `ProductID` char(5) NOT NULL,
  `Series` varchar(40) NOT NULL,
  `ProductName` varchar(100) NOT NULL,
  `Price` float NOT NULL,
  `Stock` int(10) NOT NULL,
  `Description` varchar(100) NOT NULL,
  `Image` mediumblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProductID`, `Series`, `ProductName`, `Price`, `Stock`, `Description`, `Image`) VALUES
('FH001', 'Fresh', 'N°9 Aqua Breeze', 240, 42, 'A cool aquatic fragrance with sea notes and light citrus.', 0x50696374757265),
('FH002', 'Fresh', 'N°9 Crystal Morning', 260, 28, 'Clean and bright citrus freshness with lemon and bergamot.', 0x50696374757265),
('FH003', 'Fresh', 'N°9 Pure Daylight', 230, 36, 'A mild fresh scent with white tea and soft flowers.', 0x50696374757265),
('FH004', 'Fresh', 'N°9 Mist Horizon', 270, 22, 'Airy freshness with hints of mint and watery florals.', 0x50696374757265),
('FH005', 'Fresh', 'N°9 Spring Drift', 250, 40, 'Light, refreshing, and breezy with green citrus notes.', 0x50696374757265),
('FL001', 'Floral', 'N°9 Bloom Whisper', 280, 30, 'A soft floral blend of rose petals and white jasmine, elegant and romantic.', 0x50696374757265),
('FL002', 'Floral', 'N°9 Petal Symphony', 320, 25, 'A graceful bouquet of peony, lily, and iris, perfect for feminine charm.', 0x50696374757265),
('FL003', 'Floral', 'N°9 Rose Étoile', 300, 22, 'A modern rose fragrance with bright floral tones and subtle sweetness.', 0x50696374757265),
('FL004', 'Floral', 'N°9 Velvet Blossom', 350, 18, 'Warm and luxurious floral scent with velvet rose and creamy magnolia.', 0x50696374757265),
('FL005', 'Floral', 'N°9 Garden Muse', 260, 35, 'A lively blend of garden flowers, fresh and youthful.', 0x50696374757265),
('FR001', 'Fruity', 'N°9 Juicy Mirage', 250, 40, 'A playful mix of peach, apple, and pear with a hint of sweetness.', 0x50696374757265),
('FR002', 'Fruity', 'N°9 Berry Cascade', 270, 28, 'A fresh fruity scent bursting with raspberry, blackberry, and plum.', 0x50696374757265),
('FR003', 'Fruity', 'N°9 Tropical Aura', 260, 32, 'A sunny tropical blend of mango, pineapple, and coconut.', 0x50696374757265),
('FR004', 'Fruity', 'N°9 Sweet Orchard', 230, 45, 'Crisp orchard fruits with a soft floral background; refreshing and light.', 0x50696374757265),
('FR005', 'Fruity', 'N°9 Candy Citrus', 240, 38, 'A bright citrus-fruity fragrance with orange, grapefruit, and sugar notes.', 0x50696374757265),
('GR001', 'Green', 'N°9 Green Leaf Spirit', 200, 55, 'Herbal green scent with fresh-cut leaves and soft florals.', 0x50696374757265),
('GR002', 'Green', 'N°9 Bamboo Whisper', 260, 20, 'Clean bamboo and gentle floral notes, calming and natural.', 0x50696374757265),
('GR003', 'Green', 'N°9 Meadow Fresh', 220, 33, 'Soft grassy scent inspired by morning dew on an open field.', 0x50696374757265),
('GR004', 'Green', 'N°9 Herbal Dew', 240, 27, 'Green herbs with mint and tea-like freshness.', 0x50696374757265),
('GR005', 'Green', 'N°9 Wild Garden', 210, 30, 'A vibrant, natural green fragrance with stems, leaves, and soft flowers.', 0x50696374757265),
('WD001', 'Woody', 'N°9 Sandal Noir', 330, 20, 'A warm woody scent with sandalwood, musk, and soft amber.', 0x50696374757265),
('WD002', 'Woody', 'N°9 Cedar Realm', 310, 15, 'Earthy cedarwood with crisp herbal notes, calm and grounding.', 0x50696374757265),
('WD003', 'Woody', 'N°9 Urban Shadow', 350, 18, 'A modern woody fragrance with smoky notes and masculine depth.', 0x50696374757265),
('WD004', 'Woody', 'N°9 Amber Trail', 380, 14, 'Amber, patchouli, and dry woods create a rich, sensual scent.', 0x50696374757265),
('WD005', 'Woody', 'N°9 Forest Velvet', 300, 24, 'Soft forest woods with a creamy finish, comforting and elegant.', 0x50696374757265);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(100) NOT NULL,
  `role` varchar(30) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `password`, `phone_number`, `role`, `remember_token`) VALUES
(1, 'ali', 'chongzhengzhe@gmail.com', '$2y$10$4Vew8Q.0PKoM74By9ime9.MjXuci6pO/REtKZ.HAnoOWZsMUwfU16', '018000000', 'Member', NULL),
(2, 'Yee Zu Yao', 'yeezy-wp23@student.tarc.edu.my', '$2y$10$RTmdWLSYfQZE5Tk9MBRVAeJnZ7XRetrqd6gDJ.sFwX3AyvzVQR9w6', '0111111111', 'Admin', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
