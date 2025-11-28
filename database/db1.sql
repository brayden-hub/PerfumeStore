-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 28, 2025 at 08:18 AM
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
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `OrderID` char(6) NOT NULL,
  `UserID` int(11) NOT NULL,
  `PurchaseDate` date NOT NULL,
  `PaymentMethod` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Description` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProductID`, `Series`, `ProductName`, `Price`, `Stock`, `Description`) VALUES
('FH001', 'Fresh', 'N°9 Aqua Breeze', 240, 42, 'A cool aquatic fragrance with sea notes and light citrus.'),
('FH002', 'Fresh', 'N°9 Crystal Morning', 260, 28, 'Clean and bright citrus freshness with lemon and bergamot.'),
('FH003', 'Fresh', 'N°9 Pure Daylight', 230, 36, 'A mild fresh scent with white tea and soft flowers.'),
('FH004', 'Fresh', 'N°9 Mist Horizon', 270, 22, 'Airy freshness with hints of mint and watery florals.'),
('FH005', 'Fresh', 'N°9 Spring Drift', 250, 40, 'Light, refreshing, and breezy with green citrus notes.'),
('FL001', 'Floral', 'N°9 Bloom Whisper', 280, 30, 'A soft floral blend of rose petals and white jasmine, elegant and romantic.'),
('FL002', 'Floral', 'N°9 Petal Symphony', 320, 25, 'A graceful bouquet of peony, lily, and iris, perfect for feminine charm.'),
('FL003', 'Floral', 'N°9 Rose Étoile', 300, 22, 'A modern rose fragrance with bright floral tones and subtle sweetness.'),
('FL004', 'Floral', 'N°9 Velvet Blossom', 350, 18, 'Warm and luxurious floral scent with velvet rose and creamy magnolia.'),
('FL005', 'Floral', 'N°9 Garden Muse', 260, 35, 'A lively blend of garden flowers, fresh and youthful.'),
('FR001', 'Fruity', 'N°9 Juicy Mirage', 250, 40, 'A playful mix of peach, apple, and pear with a hint of sweetness.'),
('FR002', 'Fruity', 'N°9 Berry Cascade', 270, 28, 'A fresh fruity scent bursting with raspberry, blackberry, and plum.'),
('FR003', 'Fruity', 'N°9 Tropical Aura', 260, 32, 'A sunny tropical blend of mango, pineapple, and coconut.'),
('FR004', 'Fruity', 'N°9 Sweet Orchard', 230, 45, 'Crisp orchard fruits with a soft floral background; refreshing and light.'),
('FR005', 'Fruity', 'N°9 Candy Citrus', 240, 38, 'A bright citrus-fruity fragrance with orange, grapefruit, and sugar notes.'),
('GR001', 'Green', 'N°9 Green Leaf Spirit', 200, 55, 'Herbal green scent with fresh-cut leaves and soft florals.'),
('GR002', 'Green', 'N°9 Bamboo Whisper', 260, 20, 'Clean bamboo and gentle floral notes, calming and natural.'),
('GR003', 'Green', 'N°9 Meadow Fresh', 220, 33, 'Soft grassy scent inspired by morning dew on an open field.'),
('GR004', 'Green', 'N°9 Herbal Dew', 240, 27, 'Green herbs with mint and tea-like freshness.'),
('GR005', 'Green', 'N°9 Wild Garden', 210, 30, 'A vibrant, natural green fragrance with stems, leaves, and soft flowers.'),
('WD001', 'Woody', 'N°9 Sandal Noir', 330, 20, 'A warm woody scent with sandalwood, musk, and soft amber.'),
('WD002', 'Woody', 'N°9 Cedar Realm', 310, 15, 'Earthy cedarwood with crisp herbal notes, calm and grounding.'),
('WD003', 'Woody', 'N°9 Urban Shadow', 350, 18, 'A modern woody fragrance with smoky notes and masculine depth.'),
('WD004', 'Woody', 'N°9 Amber Trail', 380, 14, 'Amber, patchouli, and dry woods create a rich, sensual scent.'),
('WD005', 'Woody', 'N°9 Forest Velvet', 300, 24, 'Soft forest woods with a creamy finish, comforting and elegant.');

-- --------------------------------------------------------

--
-- Table structure for table `productorder`
--

CREATE TABLE `productorder` (
  `ProductOrderID` char(7) NOT NULL,
  `OrderID` char(6) NOT NULL,
  `ProductID` char(5) NOT NULL,
  `Quantity` int(100) NOT NULL,
  `TotalPrice` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(100) NOT NULL,
  `role` varchar(30) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `Profile_Photo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `name`, `email`, `password`, `phone_number`, `role`, `remember_token`, `Profile_Photo`) VALUES
(1, 'ali', 'chongzhengzhe@gmail.com', '$2y$10$4Vew8Q.0PKoM74By9ime9.MjXuci6pO/REtKZ.HAnoOWZsMUwfU16', '018000000', 'Member', NULL, ''),
(2, 'Yee Zu Yao', 'yeezy-wp23@student.tarc.edu.my', '$2y$10$RTmdWLSYfQZE5Tk9MBRVAeJnZ7XRetrqd6gDJ.sFwX3AyvzVQR9w6', '0111111111', 'Admin', NULL, ''),
(3, 'Brayden Toh Zhi Kang', 'Brayden@gmail.com', '$2y$10$mNrkJaxhI/AzLaVpSx/r/e4lSyOU4K5kDh9hbi1hjDmG9AIRP84Ca', '0111111112', 'Member', NULL, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`);

--
-- Indexes for table `productorder`
--
ALTER TABLE `productorder`
  ADD PRIMARY KEY (`ProductOrderID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`);

--
-- Constraints for table `productorder`
--
ALTER TABLE `productorder`
  ADD CONSTRAINT `productorder_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`),
  ADD CONSTRAINT `productorder_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
