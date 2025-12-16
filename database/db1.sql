-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2025 at 11:04 AM
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
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `CartID` char(6) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ProductID` char(5) NOT NULL,
  `Quantity` int(10) NOT NULL DEFAULT 1,
  `AddedDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`CartID`, `UserID`, `ProductID`, `Quantity`, `AddedDate`) VALUES
('C00001', 6, 'P0009', 1, '2025-12-14 10:09:48'),
('C00002', 7, 'P0025', 1, '2025-12-15 14:11:08');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `FavoriteID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ProductID` char(5) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`FavoriteID`, `UserID`, `ProductID`, `CreatedAt`) VALUES
(1, 1, 'P0021', '2025-12-16 09:48:07'),
(2, 1, 'P0025', '2025-12-16 09:48:08');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `OrderID` char(6) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ShippingAddressID` int(11) DEFAULT NULL,
  `PurchaseDate` date NOT NULL,
  `PaymentMethod` varchar(20) NOT NULL,
  `Status` enum('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `StatusUpdatedAt` timestamp NULL DEFAULT NULL,
  `ProcessedAt` timestamp NULL DEFAULT NULL,
  `ShippedAt` timestamp NULL DEFAULT NULL,
  `DeliveredAt` timestamp NULL DEFAULT NULL,
  `EstimatedDelivery` date DEFAULT NULL,
  `TrackingNumber` varchar(100) DEFAULT NULL,
  `GiftWrap` varchar(20) DEFAULT NULL COMMENT 'standard or luxury',
  `GiftMessage` text DEFAULT NULL COMMENT 'Gift message text',
  `HidePrice` tinyint(1) DEFAULT 0 COMMENT 'Hide price on receipt',
  `GiftWrapCost` decimal(10,2) DEFAULT 0.00 COMMENT 'Gift wrap cost',
  `ShippingFee` decimal(10,2) NOT NULL DEFAULT 30.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`OrderID`, `UserID`, `ShippingAddressID`, `PurchaseDate`, `PaymentMethod`, `Status`, `StatusUpdatedAt`, `ProcessedAt`, `ShippedAt`, `DeliveredAt`, `EstimatedDelivery`, `TrackingNumber`, `GiftWrap`, `GiftMessage`, `HidePrice`, `GiftWrapCost`, `ShippingFee`) VALUES
('O00001', 1, 6, '2025-12-15', 'Credit Card', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, 30.00),
('O00002', 1, 6, '2025-12-15', 'Online Banking', 'Delivered', '2025-12-15 15:22:36', '2025-12-15 15:22:36', '2025-12-15 15:22:36', '2025-12-15 15:22:36', NULL, NULL, NULL, NULL, 0, 0.00, 30.00),
('O00003', 8, 7, '2025-12-15', 'Online Banking', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0.00, 30.00);

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
  `Image` varchar(100) NOT NULL,
  `Status` varchar(20) NOT NULL DEFAULT '''Available'''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProductID`, `Series`, `ProductName`, `Price`, `Stock`, `Description`, `Image`, `Status`) VALUES
('P0001', 'Floral', 'N°9 Bloom Whisper', 280, 30, 'A soft floral blend of rose petals and white jasmine, elegant and romantic.', 'P0001.png', '\'Available\''),
('P0002', 'Floral', 'N°9 Petal Symphony', 320, 25, 'A graceful bouquet of peony, lily, and iris, perfect for feminine charm.', 'P0002.png', '\'Available\''),
('P0003', 'Floral', 'N°9 Rose Étoile', 300, 22, 'A modern rose fragrance with bright floral tones and subtle sweetness.', 'P0003.png', '\'Available\''),
('P0004', 'Floral', 'N°9 Velvet Blossom', 350, 18, 'Warm and luxurious floral scent with velvet rose and creamy magnolia.', 'P0004.png', '\'Available\''),
('P0005', 'Floral', 'N°9 Garden Muse', 260, 35, 'A lively blend of garden flowers, fresh and youthful.', 'P0005.png', '\'Available\''),
('P0006', 'Fruity', 'N°9 Juicy Mirage', 250, 40, 'A playful mix of peach, apple, and pear with a hint of sweetness.', 'P0006.png', '\'Available\''),
('P0007', 'Fruity', 'N°9 Berry Cascade', 270, 28, 'A fresh fruity scent bursting with raspberry, blackberry, and plum.', 'P0007.png', '\'Available\''),
('P0008', 'Fruity', 'N°9 Tropical Aura', 260, 32, 'A sunny tropical blend of mango, pineapple, and coconut.', 'P0008.png', '\'Available\''),
('P0009', 'Fruity', 'N°9 Sweet Orchard', 230, 40, 'Crisp orchard fruits with a soft floral background; refreshing and light.', 'P0009.png', '\'Available\''),
('P0010', 'Fruity', 'N°9 Candy Citrus', 240, 35, 'A bright citrus-fruity fragrance with orange, grapefruit, and sugar notes.', 'P0010.png', '\'Available\''),
('P0011', 'Woody', 'N°9 Sandal Noir', 330, 20, 'A warm woody scent with sandalwood, musk, and soft amber.', 'P0011.png', '\'Available\''),
('P0012', 'Woody', 'N°9 Cedar Realm', 310, 13, 'Earthy cedarwood with crisp herbal notes, calm and grounding.', 'P0012.png', '\'Available\''),
('P0013', 'Woody', 'N°9 Urban Shadow', 350, 18, 'A modern woody fragrance with smoky notes and masculine depth.', 'P0013.png', '\'Available\''),
('P0014', 'Woody', 'N°9 Amber Trail', 380, 14, 'Amber, patchouli, and dry woods create a rich, sensual scent.', 'P0014.png', '\'Available\''),
('P0015', 'Woody', 'N°9 Forest Velvet', 300, 24, 'Soft forest woods with a creamy finish, comforting and elegant.', 'P0015.png', '\'Available\''),
('P0016', 'Fresh', 'N°9 Aqua Breeze', 240, 42, 'A cool aquatic fragrance with sea notes and light citrus.', 'P0016.png', '\'Available\''),
('P0017', 'Fresh', 'N°9 Crystal Morning', 260, 28, 'Clean and bright citrus freshness with lemon and bergamot.', 'P0017.png', '\'Available\''),
('P0018', 'Fresh', 'N°9 Pure Daylight', 230, 35, 'A mild fresh scent with white tea and soft flowers.', 'P0018.png', '\'Available\''),
('P0019', 'Fresh', 'N°9 Mist Horizon', 270, 22, 'Airy freshness with hints of mint and watery florals.', 'P0019.png', '\'Available\''),
('P0020', 'Fresh', 'N°9 Spring Drift', 250, 40, 'Light, refreshing, and breezy with green citrus notes.', 'P0020.png', '\'Available\''),
('P0021', 'Green', 'N°9 Green Leaf Spirit', 200, 48, 'Herbal green scent with fresh-cut leaves and soft florals.', 'P0021.png', '\'Available\''),
('P0022', 'Green', 'N°9 Bamboo Whisper', 260, 20, 'Clean bamboo and gentle floral notes, calming and natural.', 'P0022.png', '\'Available\''),
('P0023', 'Green', 'N°9 Meadow Fresh', 220, 26, 'Soft grassy scent inspired by morning dew on an open field.', 'P0023.png', '\'Available\''),
('P0024', 'Green', 'N°9 Herbal Dew', 240, 26, 'Green herbs with mint and tea-like freshness.', 'P0024.png', '\'Available\''),
('P0025', 'Green', 'N°9 Wild Garden', 210, 22, 'A vibrant, natural green fragrance with stems, leaves, and soft flowers.', 'P0025.png', '\'Available\'');

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

--
-- Dumping data for table `productorder`
--

INSERT INTO `productorder` (`ProductOrderID`, `OrderID`, `ProductID`, `Quantity`, `TotalPrice`) VALUES
('PO00001', 'O00001', 'P0021', 3, 600),
('PO00002', 'O00002', 'P0023', 5, 1100),
('PO00003', 'O00002', 'P0009', 1, 230),
('PO00004', 'O00003', 'P0023', 1, 220),
('PO00005', 'O00003', 'P0009', 1, 230);

-- --------------------------------------------------------

--
-- Table structure for table `subscriber`
--

CREATE TABLE `subscriber` (
  `email` varchar(100) NOT NULL,
  `token_id` char(40) NOT NULL,
  `status` enum('unconfirmed','subscribed') NOT NULL DEFAULT 'unconfirmed',
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriber`
--

INSERT INTO `subscriber` (`email`, `token_id`, `status`, `subscribed_at`, `created_at`) VALUES
('chongzhengzhe@gmail.com', 'f2d63a53c0052c0925435a1a840b4e262d3e26d6', 'subscribed', '2025-12-14 10:03:29', '2025-12-14 09:44:04');

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
  `Profile_Photo` varchar(100) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `Points` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `name`, `email`, `password`, `phone_number`, `role`, `remember_token`, `Profile_Photo`, `status`, `Points`) VALUES
(1, 'ali', 'chongzhengzhe@gmail.com', '$2y$10$4Vew8Q.0PKoM74By9ime9.MjXuci6pO/REtKZ.HAnoOWZsMUwfU16', '018000000', 'Member', NULL, '', 'Activated', 0),
(2, 'Yee Zu Yao', 'yeezy-wp23@student.tarc.edu.my', '$2y$10$RTmdWLSYfQZE5Tk9MBRVAeJnZ7XRetrqd6gDJ.sFwX3AyvzVQR9w6', '0111111111', 'Admin', NULL, '', 'Active', 0),
(5, 'Brayden Toh Zhi Kang', 'Brayden@gmail.com', '$2y$10$mNrkJaxhI/AzLaVpSx/r/e4lSyOU4K5kDh9hbi1hjDmG9AIRP84Ca', '0111111112', 'Member', NULL, '', 'Activated', 0),
(6, 'pop@gmail.com', 'pop@gmail.com', '$2y$10$Up7xPG91ut/2LSzwYgcjUOD.v6wDo1esV6kp3IHxdXUy4t8YKmcOW', '01154789654', 'Member', NULL, 'default5.jpg', 'Activated', 0),
(7, 'raiko', 'donghuanlin25@gmail.com', '$2y$10$losLbM6UXgKtuQiKvyk1auuGoByWZeSqaZmXiosHiGYy.nN0CDUry', '104507792', 'Member', NULL, '7_1765728165.png', 'Activated', 0),
(8, 's@gmail.com', 's@gmail.com', '$2y$10$yZvBp6JpC8pogwIQhfOZZO8Uy.Yfqxjso5d5zK2WBK5/959hnlyeO', '01233333333', 'Member', NULL, 'default2.jpg', 'Activated', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_address`
--

CREATE TABLE `user_address` (
  `AddressID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `AddressLabel` varchar(50) NOT NULL COMMENT 'Home, Office, etc.',
  `RecipientName` varchar(100) NOT NULL,
  `PhoneNumber` varchar(20) NOT NULL,
  `AddressLine1` varchar(255) NOT NULL,
  `AddressLine2` varchar(255) DEFAULT NULL,
  `City` varchar(100) NOT NULL,
  `State` varchar(100) NOT NULL,
  `PostalCode` varchar(10) NOT NULL,
  `Country` varchar(50) NOT NULL DEFAULT 'Malaysia',
  `IsDefault` tinyint(1) NOT NULL DEFAULT 0,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_address`
--

INSERT INTO `user_address` (`AddressID`, `UserID`, `AddressLabel`, `RecipientName`, `PhoneNumber`, `AddressLine1`, `AddressLine2`, `City`, `State`, `PostalCode`, `Country`, `IsDefault`, `CreatedDate`) VALUES
(1, 6, 'Home', 'Brayden', '01154789632', 'No998, Jalan Kehantar,', 'Bukit nanti, Tangkak', 'sa', 'Johor', '84200', 'Malaysia', 0, '2025-12-14 10:01:31'),
(2, 6, 'Home', 'Brayden', '01154789632', 'qqqqqqqqqqqqq', 'qqqqqqqqqqqq', 'qqqqqqq', 'Selangor', '52011', 'Malaysia', 1, '2025-12-14 10:10:18'),
(3, 6, 'Home', 'Brayden', '01154789632', 'No998, Jalan Kehantar,', 'qqqqqqqqqqqq', 'sa', 'Labuan', '84200', 'Malaysia', 0, '2025-12-14 10:13:08'),
(4, 6, 'word', 'Brayden', '01154789632', 'No998, Jalan Kehantar,', 'Bukit nanti, Tangkak', 'sa', 'Terengganu', '52011', 'Malaysia', 0, '2025-12-14 10:13:26'),
(5, 7, 'Home', 'nn', '0104507792', '11, taman gunung emas 3', '', 'Johor', 'Johor', '84900', 'Malaysia', 0, '2025-12-14 12:35:11'),
(6, 1, 'jsholis', 'dd', 'f', 'f', '', 'f', 'Melaka', 'f', 'Malaysia', 1, '2025-12-15 15:01:12'),
(7, 8, 'home', 'x', 's', 'scac', '', 'ds', 'Sarawak', 'rr', 'Malaysia', 0, '2025-12-15 15:13:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`FavoriteID`),
  ADD UNIQUE KEY `UserID` (`UserID`,`ProductID`),
  ADD KEY `fk_fav_product` (`ProductID`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ShippingAddressID` (`ShippingAddressID`);

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
-- Indexes for table `subscriber`
--
ALTER TABLE `subscriber`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_address`
--
ALTER TABLE `user_address`
  ADD PRIMARY KEY (`AddressID`),
  ADD KEY `UserID` (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `FavoriteID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_address`
--
ALTER TABLE `user_address`
  MODIFY `AddressID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`),
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`ShippingAddressID`) REFERENCES `user_address` (`AddressID`) ON DELETE SET NULL;

--
-- Constraints for table `productorder`
--
ALTER TABLE `productorder`
  ADD CONSTRAINT `productorder_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `productorder_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `user_address`
--
ALTER TABLE `user_address`
  ADD CONSTRAINT `user_address_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
