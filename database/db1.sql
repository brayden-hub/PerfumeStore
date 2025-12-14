-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-12-14 11:15:17
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `db1`
--
CREATE DATABASE IF NOT EXISTS `db1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db1`;

-- --------------------------------------------------------

--
-- 表的结构 `cart`
--

CREATE TABLE `cart` (
  `CartID` char(6) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ProductID` char(5) NOT NULL,
  `Quantity` int(10) NOT NULL DEFAULT 1,
  `AddedDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `cart`
--

INSERT INTO `cart` (`CartID`, `UserID`, `ProductID`, `Quantity`, `AddedDate`) VALUES
('C00001', 6, 'P0009', 1, '2025-12-14 10:09:48');

-- --------------------------------------------------------

--
-- 表的结构 `order`
--

CREATE TABLE `order` (
  `OrderID` char(6) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ShippingAddressID` int(11) DEFAULT NULL,
  `PurchaseDate` date NOT NULL,
  `PaymentMethod` varchar(20) NOT NULL,
  `GiftWrap` varchar(20) DEFAULT NULL COMMENT 'standard or luxury',
  `GiftMessage` text DEFAULT NULL COMMENT 'Gift message text',
  `HidePrice` tinyint(1) DEFAULT 0 COMMENT 'Hide price on receipt',
  `GiftWrapCost` decimal(10,2) DEFAULT 0.00 COMMENT 'Gift wrap cost',
  `ShippingFee` decimal(10,2) NOT NULL DEFAULT 30.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order`
--

INSERT INTO `order` (`OrderID`, `UserID`, `ShippingAddressID`, `PurchaseDate`, `PaymentMethod`, `GiftWrap`, `GiftMessage`, `HidePrice`, `GiftWrapCost`, `ShippingFee`) VALUES
('O00001', 6, NULL, '2025-12-14', 'Cash on Delivery', 'luxury', '999', 1, 5.00, 30.00),
('O00002', 6, NULL, '2025-12-14', 'Online Banking', 'luxury', 'pikachu', 1, 5.00, 30.00),
('O00003', 6, NULL, '2025-12-14', 'E-Wallet', 'luxury', 'how are you i am fine tq ==', 0, 5.00, 30.00),
('O00004', 6, NULL, '2025-12-14', 'Credit Card', 'luxury', 'ppppp', 1, 5.00, 30.00),
('O00005', 6, NULL, '2025-12-14', 'Online Banking', 'luxury', 'ppppp', 1, 5.00, 30.00),
('O00006', 6, 1, '2025-12-14', 'Credit Card', 'luxury', '999', 1, 5.00, 30.00),
('O00007', 6, 1, '2025-12-14', 'Credit Card', NULL, NULL, 0, 0.00, 30.00);

-- --------------------------------------------------------

--
-- 表的结构 `product`
--

CREATE TABLE `product` (
  `ProductID` char(5) NOT NULL,
  `Series` varchar(40) NOT NULL,
  `ProductName` varchar(100) NOT NULL,
  `Price` float NOT NULL,
  `Stock` int(10) NOT NULL,
  `Description` varchar(100) NOT NULL,
  `Image` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product`
--

INSERT INTO `product` (`ProductID`, `Series`, `ProductName`, `Price`, `Stock`, `Description`, `Image`) VALUES
('P0001', 'Floral', 'N°9 Bloom Whisper', 280, 30, 'A soft floral blend of rose petals and white jasmine, elegant and romantic.', 'P0001.png'),
('P0002', 'Floral', 'N°9 Petal Symphony', 320, 25, 'A graceful bouquet of peony, lily, and iris, perfect for feminine charm.', 'P0002.png'),
('P0003', 'Floral', 'N°9 Rose Étoile', 300, 22, 'A modern rose fragrance with bright floral tones and subtle sweetness.', 'P0003.png'),
('P0004', 'Floral', 'N°9 Velvet Blossom', 350, 18, 'Warm and luxurious floral scent with velvet rose and creamy magnolia.', 'P0004.png'),
('P0005', 'Floral', 'N°9 Garden Muse', 260, 35, 'A lively blend of garden flowers, fresh and youthful.', 'P0005.png'),
('P0006', 'Fruity', 'N°9 Juicy Mirage', 250, 40, 'A playful mix of peach, apple, and pear with a hint of sweetness.', 'P0006.png'),
('P0007', 'Fruity', 'N°9 Berry Cascade', 270, 28, 'A fresh fruity scent bursting with raspberry, blackberry, and plum.', 'P0007.png'),
('P0008', 'Fruity', 'N°9 Tropical Aura', 260, 32, 'A sunny tropical blend of mango, pineapple, and coconut.', 'P0008.png'),
('P0009', 'Fruity', 'N°9 Sweet Orchard', 230, 45, 'Crisp orchard fruits with a soft floral background; refreshing and light.', 'P0009.png'),
('P0010', 'Fruity', 'N°9 Candy Citrus', 240, 38, 'A bright citrus-fruity fragrance with orange, grapefruit, and sugar notes.', 'P0010.png'),
('P0011', 'Woody', 'N°9 Sandal Noir', 330, 20, 'A warm woody scent with sandalwood, musk, and soft amber.', 'P0011.png'),
('P0012', 'Woody', 'N°9 Cedar Realm', 310, 13, 'Earthy cedarwood with crisp herbal notes, calm and grounding.', 'P0012.png'),
('P0013', 'Woody', 'N°9 Urban Shadow', 350, 18, 'A modern woody fragrance with smoky notes and masculine depth.', 'P0013.png'),
('P0014', 'Woody', 'N°9 Amber Trail', 380, 14, 'Amber, patchouli, and dry woods create a rich, sensual scent.', 'P0014.png'),
('P0015', 'Woody', 'N°9 Forest Velvet', 300, 24, 'Soft forest woods with a creamy finish, comforting and elegant.', 'P0015.png'),
('P0016', 'Fresh', 'N°9 Aqua Breeze', 240, 42, 'A cool aquatic fragrance with sea notes and light citrus.', 'P0016.png'),
('P0017', 'Fresh', 'N°9 Crystal Morning', 260, 28, 'Clean and bright citrus freshness with lemon and bergamot.', 'P0017.png'),
('P0018', 'Fresh', 'N°9 Pure Daylight', 230, 36, 'A mild fresh scent with white tea and soft flowers.', 'P0018.png'),
('P0019', 'Fresh', 'N°9 Mist Horizon', 270, 22, 'Airy freshness with hints of mint and watery florals.', 'P0019.png'),
('P0020', 'Fresh', 'N°9 Spring Drift', 250, 40, 'Light, refreshing, and breezy with green citrus notes.', 'P0020.png'),
('P0021', 'Green', 'N°9 Green Leaf Spirit', 200, 53, 'Herbal green scent with fresh-cut leaves and soft florals.', 'P0021.png'),
('P0022', 'Green', 'N°9 Bamboo Whisper', 260, 20, 'Clean bamboo and gentle floral notes, calming and natural.', 'P0022.png'),
('P0023', 'Green', 'N°9 Meadow Fresh', 220, 33, 'Soft grassy scent inspired by morning dew on an open field.', 'P0023.png'),
('P0024', 'Green', 'N°9 Herbal Dew', 240, 27, 'Green herbs with mint and tea-like freshness.', 'P0024.png'),
('P0025', 'Green', 'N°9 Wild Garden', 210, 26, 'A vibrant, natural green fragrance with stems, leaves, and soft flowers.', 'P0025.png');

-- --------------------------------------------------------

--
-- 表的结构 `productorder`
--

CREATE TABLE `productorder` (
  `ProductOrderID` char(7) NOT NULL,
  `OrderID` char(6) NOT NULL,
  `ProductID` char(5) NOT NULL,
  `Quantity` int(100) NOT NULL,
  `TotalPrice` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `productorder`
--

INSERT INTO `productorder` (`ProductOrderID`, `OrderID`, `ProductID`, `Quantity`, `TotalPrice`) VALUES
('PO00001', 'O00001', 'P0025', 1, 210),
('PO00002', 'O00002', 'P0021', 1, 200),
('PO00003', 'O00003', 'P0012', 1, 310),
('PO00004', 'O00004', 'P0012', 1, 310),
('PO00005', 'O00005', 'P0021', 1, 200),
('PO00006', 'O00005', 'P0025', 1, 210),
('PO00007', 'O00006', 'P0025', 1, 210),
('PO00008', 'O00007', 'P0025', 1, 210);

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE `user` (
  `userID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(100) NOT NULL,
  `role` varchar(30) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `Profile_Photo` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`userID`, `name`, `email`, `password`, `phone_number`, `role`, `remember_token`, `Profile_Photo`) VALUES
(1, 'ali', 'chongzhengzhe@gmail.com', '$2y$10$4Vew8Q.0PKoM74By9ime9.MjXuci6pO/REtKZ.HAnoOWZsMUwfU16', '018000000', 'Member', NULL, ''),
(2, 'Yee Zu Yao', 'yeezy-wp23@student.tarc.edu.my', '$2y$10$RTmdWLSYfQZE5Tk9MBRVAeJnZ7XRetrqd6gDJ.sFwX3AyvzVQR9w6', '0111111111', 'Admin', NULL, ''),
(5, 'Brayden Toh Zhi Kang', 'Brayden@gmail.com', '$2y$10$mNrkJaxhI/AzLaVpSx/r/e4lSyOU4K5kDh9hbi1hjDmG9AIRP84Ca', '0111111112', 'Member', NULL, ''),
(6, 'pop@gmail.com', 'pop@gmail.com', '$2y$10$Up7xPG91ut/2LSzwYgcjUOD.v6wDo1esV6kp3IHxdXUy4t8YKmcOW', '01154789654', 'Member', NULL, 'default5.jpg');

-- --------------------------------------------------------

--
-- 表的结构 `user_address`
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
-- 转存表中的数据 `user_address`
--

INSERT INTO `user_address` (`AddressID`, `UserID`, `AddressLabel`, `RecipientName`, `PhoneNumber`, `AddressLine1`, `AddressLine2`, `City`, `State`, `PostalCode`, `Country`, `IsDefault`, `CreatedDate`) VALUES
(1, 6, 'Home', 'Brayden', '01154789632', 'No998, Jalan Kehantar,', 'Bukit nanti, Tangkak', 'sa', 'Johor', '84200', 'Malaysia', 0, '2025-12-14 10:01:31'),
(2, 6, 'Home', 'Brayden', '01154789632', 'qqqqqqqqqqqqq', 'qqqqqqqqqqqq', 'qqqqqqq', 'Selangor', '52011', 'Malaysia', 1, '2025-12-14 10:10:18'),
(3, 6, 'Home', 'Brayden', '01154789632', 'No998, Jalan Kehantar,', 'qqqqqqqqqqqq', 'sa', 'Labuan', '84200', 'Malaysia', 0, '2025-12-14 10:13:08'),
(4, 6, 'word', 'Brayden', '01154789632', 'No998, Jalan Kehantar,', 'Bukit nanti, Tangkak', 'sa', 'Terengganu', '52011', 'Malaysia', 0, '2025-12-14 10:13:26');

--
-- 转储表的索引
--

--
-- 表的索引 `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 表的索引 `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ShippingAddressID` (`ShippingAddressID`);

--
-- 表的索引 `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`);

--
-- 表的索引 `productorder`
--
ALTER TABLE `productorder`
  ADD PRIMARY KEY (`ProductOrderID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 表的索引 `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `user_address`
--
ALTER TABLE `user_address`
  ADD PRIMARY KEY (`AddressID`),
  ADD KEY `UserID` (`UserID`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用表AUTO_INCREMENT `user_address`
--
ALTER TABLE `user_address`
  MODIFY `AddressID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 限制导出的表
--

--
-- 限制表 `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE;

--
-- 限制表 `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`),
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`ShippingAddressID`) REFERENCES `user_address` (`AddressID`) ON DELETE SET NULL;

--
-- 限制表 `productorder`
--
ALTER TABLE `productorder`
  ADD CONSTRAINT `productorder_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `productorder_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- 限制表 `user_address`
--
ALTER TABLE `user_address`
  ADD CONSTRAINT `user_address_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
