-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-12-19 14:39:38
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
('C00002', 22, 'P0021', 3, '2025-12-18 14:48:19');

-- --------------------------------------------------------

--
-- 表的结构 `favorites`
--

CREATE TABLE `favorites` (
  `FavoriteID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ProductID` char(5) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `favorites`
--

INSERT INTO `favorites` (`FavoriteID`, `UserID`, `ProductID`, `CreatedAt`) VALUES
(2, 1, 'P0025', '2025-12-16 09:48:08'),
(5, 1, 'P0021', '2025-12-16 15:51:24'),
(7, 1, 'P0010', '2025-12-16 15:51:26');

-- --------------------------------------------------------

--
-- 表的结构 `order`
--

CREATE TABLE `order` (
  `OrderID` char(6) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ShippingAddressID` int(11) DEFAULT NULL,
  `PurchaseDate` datetime NOT NULL,
  `PaymentMethod` varchar(20) NOT NULL,
  `GiftWrap` varchar(20) DEFAULT NULL COMMENT 'standard or luxury',
  `GiftMessage` text DEFAULT NULL COMMENT 'Gift message text',
  `HidePrice` tinyint(1) DEFAULT 0 COMMENT 'Hide price on receipt',
  `GiftWrapCost` decimal(10,2) DEFAULT 0.00 COMMENT 'Gift wrap cost',
  `ShippingFee` decimal(10,2) NOT NULL DEFAULT 30.00,
  `VoucherID` int(11) DEFAULT NULL,
  `VoucherDiscount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order`
--

INSERT INTO `order` (`OrderID`, `UserID`, `ShippingAddressID`, `PurchaseDate`, `PaymentMethod`, `GiftWrap`, `GiftMessage`, `HidePrice`, `GiftWrapCost`, `ShippingFee`, `VoucherID`, `VoucherDiscount`) VALUES
('O00001', 1, 6, '2025-12-16 00:00:00', 'Cash on Delivery', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00002', 8, 7, '2025-12-16 00:00:00', 'E-Wallet', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00003', 8, 7, '2025-12-16 00:00:00', 'Cash on Delivery', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00004', 8, 7, '2025-12-16 00:00:00', 'E-Wallet', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00005', 8, 7, '2025-12-16 00:00:00', 'Cash on Delivery', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00006', 1, 6, '2025-12-17 00:00:00', 'E-Wallet', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00007', 1, 6, '2025-12-17 00:00:00', 'Cash on Delivery', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00008', 1, 6, '2025-12-17 00:00:00', 'Online Banking', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00009', 1, 6, '2025-12-17 00:00:00', 'Cash on Delivery', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00010', 1, 6, '2025-12-17 00:00:00', 'Online Banking', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00011', 8, 7, '2025-12-17 00:00:00', 'Cash on Delivery', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00012', 8, 7, '2025-12-17 00:00:00', 'Cash on Delivery', NULL, NULL, 0, 0.00, 30.00, NULL, 0.00),
('O00013', 7, 5, '2025-12-18 00:00:00', 'Cash on Delivery', NULL, NULL, 0, 0.00, 0.00, NULL, 0.00),
('O00014', 6, 2, '2025-12-19 00:00:00', 'Credit Card', NULL, NULL, 0, 0.00, 0.00, NULL, 0.00),
('O00015', 6, 2, '2025-12-19 00:00:00', 'Online Banking', NULL, NULL, 0, 0.00, 0.00, NULL, 0.00);

-- --------------------------------------------------------

--
-- 表的结构 `order_status`
--

CREATE TABLE `order_status` (
  `StatusID` int(11) NOT NULL,
  `OrderID` char(6) NOT NULL,
  `Status` enum('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `StatusUpdatedAt` timestamp NULL DEFAULT NULL,
  `ProcessedAt` timestamp NULL DEFAULT NULL,
  `ShippedAt` timestamp NULL DEFAULT NULL,
  `DeliveredAt` timestamp NULL DEFAULT NULL,
  `EstimatedDelivery` date DEFAULT NULL,
  `TrackingNumber` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order_status`
--

INSERT INTO `order_status` (`StatusID`, `OrderID`, `Status`, `StatusUpdatedAt`, `ProcessedAt`, `ShippedAt`, `DeliveredAt`, `EstimatedDelivery`, `TrackingNumber`) VALUES
(8, 'O00001', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'O00002', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'O00003', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'O00004', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'O00005', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(13, 'O00006', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 'O00007', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'O00008', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(16, 'O00009', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'O00010', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'O00011', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'O00012', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'O00013', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'O00014', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL),
(22, 'O00015', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL);

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
  `Image` varchar(100) NOT NULL,
  `Status` varchar(20) NOT NULL DEFAULT '''Available'''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product`
--

INSERT INTO `product` (`ProductID`, `Series`, `ProductName`, `Price`, `Stock`, `Description`, `Image`, `Status`) VALUES
('P0001', 'Floral', 'N°9 Bloom Whisper', 280, 30, 'A soft floral blend of rose petals and white jasmine, elegant and romantic.', 'P0001.png', 'Available'),
('P0002', 'Floral', 'N°9 Petal Symphony', 320, 25, 'A graceful bouquet of peony, lily, and iris, perfect for feminine charm.', 'P0002.png', '\'Available\''),
('P0003', 'Floral', 'N°9 Rose Étoile', 300, 22, 'A modern rose fragrance with bright floral tones and subtle sweetness.', 'P0003.png', '\'Available\''),
('P0004', 'Floral', 'N°9 Velvet Blossom', 350, 18, 'Warm and luxurious floral scent with velvet rose and creamy magnolia.', 'P0004.png', '\'Available\''),
('P0005', 'Floral', 'N°9 Garden Muse', 260, 35, 'A lively blend of garden flowers, fresh and youthful.', 'P0005.png', '\'Available\''),
('P0006', 'Fruity', 'N°9 Juicy Mirage', 250, 37, 'A playful mix of peach, apple, and pear with a hint of sweetness.', 'P0006.png', '\'Available\''),
('P0007', 'Fruity', 'N°9 Berry Cascade', 270, 28, 'A fresh fruity scent bursting with raspberry, blackberry, and plum.', 'P0007.png', '\'Available\''),
('P0008', 'Fruity', 'N°9 Tropical Aura', 260, 31, 'A sunny tropical blend of mango, pineapple, and coconut.', 'P0008.png', '\'Available\''),
('P0009', 'Fruity', 'N°9 Sweet Orchard', 230, 30, 'Crisp orchard fruits with a soft floral background; refreshing and light.', 'P0009.png', '\'Available\''),
('P0010', 'Fruity', 'N°9 Candy Citrus', 240, 31, 'A bright citrus-fruity fragrance with orange, grapefruit, and sugar notes.', 'P0010.png', '\'Available\''),
('P0011', 'Woody', 'N°9 Sandal Noir', 330, 19, 'A warm woody scent with sandalwood, musk, and soft amber.', 'P0011.png', '\'Available\''),
('P0012', 'Woody', 'N°9 Cedar Realm', 310, 13, 'Earthy cedarwood with crisp herbal notes, calm and grounding.', 'P0012.png', '\'Available\''),
('P0013', 'Woody', 'N°9 Urban Shadow', 350, 18, 'A modern woody fragrance with smoky notes and masculine depth.', 'P0013.png', '\'Available\''),
('P0014', 'Woody', 'N°9 Amber Trail', 380, 13, 'Amber, patchouli, and dry woods create a rich, sensual scent.', 'P0014.png', '\'Available\''),
('P0015', 'Woody', 'N°9 Forest Velvet', 300, 22, 'Soft forest woods with a creamy finish, comforting and elegant.', 'P0015.png', '\'Available\''),
('P0016', 'Fresh', 'N°9 Aqua Breeze', 240, 39, 'A cool aquatic fragrance with sea notes and light citrus.', 'P0016.png', '\'Available\''),
('P0017', 'Fresh', 'N°9 Crystal Morning', 260, 28, 'Clean and bright citrus freshness with lemon and bergamot.', 'P0017.png', '\'Available\''),
('P0018', 'Fresh', 'N°9 Pure Daylight', 230, 33, 'A mild fresh scent with white tea and soft flowers.', 'P0018.png', '\'Available\''),
('P0019', 'Fresh', 'N°9 Mist Horizon', 270, 22, 'Airy freshness with hints of mint and watery florals.', 'P0019.png', '\'Available\''),
('P0020', 'Fresh', 'N°9 Spring Drift', 250, 40, 'Light, refreshing, and breezy with green citrus notes.', 'P0020.png', '\'Available\''),
('P0021', 'Green', 'N°9 Green Leaf Spirit', 200, 28, 'Herbal green scent with fresh-cut leaves and soft florals.', 'P0021.png', '\'Available\''),
('P0022', 'Green', 'N°9 Bamboo Whisper', 260, 20, 'Clean bamboo and gentle floral notes, calming and natural.', 'P0022.png', '\'Available\''),
('P0023', 'Green', 'N°9 Meadow Fresh', 220, 18, 'Soft grassy scent inspired by morning dew on an open field.', 'P0023.png', '\'Available\''),
('P0024', 'Green', 'N°9 Herbal Dew', 240, 25, 'Green herbs with mint and tea-like freshness.', 'P0024.png', '\'Available\''),
('P0025', 'Green', 'N°9 Wild Garden', 210, 7, 'A vibrant, natural green fragrance with stems, leaves, and soft flowers.', 'P0025.png', 'Available'),
('P0026', 'Floral', 'N9 Testing', 998.99, 60, 'This for testing only', 'P0026.jpg', 'Available'),
('P0027', 'Fresh', 'N9 Testing2', 22, 221, '2222', 'P0027.jpg', '\'Available\'');

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
('PO00001', 'O00001', 'P0021', 1, 200),
('PO00002', 'O00001', 'P0025', 1, 210),
('PO00003', 'O00001', 'P0023', 1, 220),
('PO00004', 'O00002', 'P0025', 1, 210),
('PO00005', 'O00002', 'P0018', 1, 230),
('PO00006', 'O00002', 'P0024', 1, 240),
('PO00007', 'O00003', 'P0009', 1, 230),
('PO00008', 'O00004', 'P0023', 1, 220),
('PO00009', 'O00005', 'P0014', 1, 380),
('PO00010', 'O00006', 'P0021', 1, 200),
('PO00011', 'O00007', 'P0025', 1, 210),
('PO00012', 'O00008', 'P0021', 1, 200),
('PO00013', 'O00009', 'P0010', 1, 240),
('PO00014', 'O00010', 'P0021', 1, 200),
('PO00015', 'O00011', 'P0009', 1, 230),
('PO00016', 'O00012', 'P0016', 3, 720),
('PO00017', 'O00012', 'P0006', 3, 750),
('PO00018', 'O00012', 'P0015', 2, 600),
('PO00019', 'O00013', 'P0025', 3, 630),
('PO00020', 'O00014', 'P0009', 2, 460),
('PO00021', 'O00015', 'P0023', 1, 220),
('PO00022', 'O00015', 'P0008', 1, 260);

-- --------------------------------------------------------

--
-- 表的结构 `product_images`
--

CREATE TABLE `product_images` (
  `ImageID` int(11) NOT NULL,
  `ProductID` char(5) NOT NULL,
  `Filename` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product_images`
--

INSERT INTO `product_images` (`ImageID`, `ProductID`, `Filename`) VALUES
(1, 'P0001', 'P0001_6942fb287b40b.png'),
(3, 'P0002', 'P0002_6942fc9996f37.png'),
(4, 'P0002', 'P0002_6942fca1143d8.jpg'),
(5, 'P0003', 'P0003_6942fce4c3987.png'),
(6, 'P0003', 'P0003_6942fcef3f130.jpg'),
(7, 'P0004', 'P0004_6942fd16206de.png'),
(8, 'P0004', 'P0004_6942fd2361d06.jpg'),
(9, 'P0005', 'P0005_6942fd37be377.png'),
(10, 'P0005', 'P0005_6942fd4df4153.jpg'),
(13, 'P0007', 'P0007_6942fd8a48387.png'),
(14, 'P0007', 'P0007_6942fd9a80234.jpg'),
(15, 'P0006', 'P0006_6942fdd37f859.png'),
(16, 'P0006', 'P0006_6942fde80fd27.jpg'),
(17, 'P0008', 'P0008_6942fdf863cc7.png'),
(18, 'P0008', 'P0008_6942fe01cf2f7.jpg'),
(19, 'P0009', 'P0009_6942fe17efddd.png'),
(20, 'P0009', 'P0009_6942fe22a721e.jpg'),
(21, 'P0010', 'P0010_6942fe370765d.png'),
(22, 'P0010', 'P0010_6942fe3d98cdd.jpg'),
(23, 'P0011', 'P0011_6942fe4ecba69.png'),
(24, 'P0011', 'P0011_6942fe574b845.jpg'),
(25, 'P0012', 'P0012_6942fe6952aa6.png'),
(26, 'P0012', 'P0012_6942fe740c1ee.jpg'),
(27, 'P0013', 'P0013_6942fe7fa240f.png'),
(28, 'P0013', 'P0013_6942fe8806eaf.jpg'),
(29, 'P0014', 'P0014_6942fe9c7ddb8.png'),
(30, 'P0014', 'P0014_6942fea737872.jpg'),
(31, 'P0025', 'P0025_6942fec222606.png'),
(32, 'P0025', 'P0025_6942fec961187.jpg'),
(33, 'P0024', 'P0024_6942fed66e62a.png'),
(34, 'P0024', 'P0024_6942fedd9813f.jpg'),
(35, 'P0023', 'P0023_6942fef7827a1.png'),
(36, 'P0023', 'P0023_6942ff0019008.jpg'),
(37, 'P0022', 'P0022_6942ff118d938.png'),
(38, 'P0022', 'P0022_6942ff1bf09ac.jpg'),
(39, 'P0021', 'P0021_6942ff2ee15a5.png'),
(40, 'P0021', 'P0021_6942ff3a638ff.jpg'),
(41, 'P0020', 'P0020_6942ff546e3de.png'),
(42, 'P0020', 'P0020_6942ff71552f9.jpg'),
(43, 'P0018', 'P0018_6942ff8236514.png'),
(44, 'P0018', 'P0018_6942ff8be6e5f.jpg'),
(45, 'P0017', 'P0017_6942ffa04e982.png'),
(46, 'P0017', 'P0017_6942ffa9e2e1a.jpg'),
(47, 'P0016', 'P0016_6942ffbac4b99.png'),
(48, 'P0016', 'P0016_6942ffc4241e4.jpg'),
(49, 'P0019', 'P0019_6942ffe4e145f.png'),
(50, 'P0019', 'P0019_6942ffed7f8c8.jpg'),
(51, 'P0001', 'P0001_69430390b1d65.jpg'),
(52, 'P0026', 'P0026_69446eec6aa7c.jpg'),
(53, 'P0027', 'P0027_69446ff8a8c1f.png');

-- --------------------------------------------------------

--
-- 表的结构 `subscriber`
--

CREATE TABLE `subscriber` (
  `email` varchar(100) NOT NULL,
  `token_id` char(40) NOT NULL,
  `status` enum('unconfirmed','subscribed') NOT NULL DEFAULT 'unconfirmed',
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `subscriber`
--

INSERT INTO `subscriber` (`email`, `token_id`, `status`, `subscribed_at`, `created_at`) VALUES
('chongzhengzhe@gmail.com', 'f2d63a53c0052c0925435a1a840b4e262d3e26d6', 'subscribed', '2025-12-14 10:03:29', '2025-12-14 09:44:04');

-- --------------------------------------------------------

--
-- 表的结构 `token`
--

CREATE TABLE `token` (
  `token_id` varchar(100) NOT NULL,
  `expire` datetime NOT NULL,
  `userID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Profile_Photo` varchar(100) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `Points` int(11) NOT NULL DEFAULT 0,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `attempt_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`userID`, `name`, `email`, `password`, `phone_number`, `role`, `remember_token`, `Profile_Photo`, `status`, `Points`, `login_attempts`, `attempt_time`) VALUES
(1, 'ali', 'c@gmail.com', '$2y$10$BbEc4QFyUknPYR.WaaJAyO1W7bn.75sjcY1ukowx.N.7jv05aF0uS', '018000000', 'Member', NULL, '', 'Deactivated', 0, 0, NULL),
(2, 'Yee Zu Yao', 'yeezy-wp23@student.tarc.edu.my', '$2y$10$RTmdWLSYfQZE5Tk9MBRVAeJnZ7XRetrqd6gDJ.sFwX3AyvzVQR9w6', '0111111111', 'Admin', NULL, '', 'Activated', 0, 0, NULL),
(5, 'Brayden Toh Zhi Kang', 'Brayden@gmail.com', '$2y$10$mNrkJaxhI/AzLaVpSx/r/e4lSyOU4K5kDh9hbi1hjDmG9AIRP84Ca', '0111111112', 'Member', NULL, '', 'Activated', 0, 0, NULL),
(6, 'pop@gmail.com', 'pop@gmail.com', '$2y$10$Up7xPG91ut/2LSzwYgcjUOD.v6wDo1esV6kp3IHxdXUy4t8YKmcOW', '01154789654', 'Member', NULL, 'default5.jpg', 'Activated', 0, 0, NULL),
(7, 'raiko', 'donghuanlin25@gmail.com', '$2y$10$losLbM6UXgKtuQiKvyk1auuGoByWZeSqaZmXiosHiGYy.nN0CDUry', '104507792', 'Member', NULL, '7_1765728165.png', 'Activated', 0, 0, NULL),
(8, 's@gmail.com', 's@gmail.com', '$2y$10$yZvBp6JpC8pogwIQhfOZZO8Uy.Yfqxjso5d5zK2WBK5/959hnlyeO', '01233333333', 'Member', NULL, 'default2.jpg', 'Activated', 0, 0, NULL),
(9, 'c', 'css@gmail.com', '$2y$10$SZr4Syll8vGjSc3EWoKpaOWE8OpRpvisHuptnsnJ26ueSim90szu2', '60182222222', 'Member', NULL, 'default2.jpg', 'Activated', 0, 0, NULL),
(10, 'f', 'zz@gmail.com', '$2y$10$Inzw2G4x5NjucoEiYPChq.Sv/.OlfMrkke2SOmhgVuhqKW8xBJVOi', '0156666666', 'Member', NULL, 'default5.jpg', 'Activated', 0, 0, NULL),
(11, 'fgdf', 'f@gmail.com', '$2y$10$onRRZlsyuacS38l7CQVeye4bhlnewt76R/gi2gdqhZPlkVj1vb10u', '604990000000', 'Member', NULL, 'default3.jpg', 'Activated', 0, 0, NULL),
(12, 'fg', 'r@gmail.com', '$2y$10$BrNQ2KLcyfYCSgVpCJT3oenjEMXao5JhiCQMNo8d5Xn1aeyl1HcJa', '0124334433', 'Member', NULL, 'default2.jpg', 'Activated', 0, 0, NULL),
(13, 'c', 'cd@gmail.com', '$2y$10$JmsH.gMTqbD.VeSmT.TCxOe20VK6JW6nO5xOZlKgrSylNCFi/Djfe', '0187777777', 'Member', NULL, 'default5.jpg', 'Activated', 0, 0, NULL),
(14, 'c', 'chong@gmail.com', '$2y$10$LbM2lFaYeGK9OizogL8glePwd6/fFiJatcOhn/60//h0UIH5.ejeW', '01222222222', 'Member', NULL, 'default5.jpg', 'Activated', 0, 0, NULL),
(15, 'gbc bvm', 'cho@gmail.com', '$2y$10$vM0CuK7VXk58tKvd4SKpnOSjBWK.hQARDgj1Q688FAFNh1SH8nlGu', '01920000000', 'Member', NULL, 'default3.jpg', 'Activated', 0, 0, NULL),
(16, 'cfl', 'chongzhengkzhe@gmail.com', '$2y$10$bNWmgIZlmynTlQ3iv3DPvuZMt1XHdQ1nEkJRjibQweIPPL1zK2fNm', '01233333333', 'Member', NULL, 'default5.jpg', 'Activated', 0, 0, NULL),
(17, 'd', 'chongzhengzl\r\nhe@gmail.com', '$2y$10$Yx949ILoT4todMbC2en3Xuo2uBHzNf7y1rMf9JxzgoJPF.89Gtsm.', '0122222222', 'Member', NULL, 'default5.jpg', 'Activated', 0, 0, NULL),
(18, 'fgdf', 'chojngzhengzhe@gmail.com', '$2y$10$Q269KUVD1HKlaoFJfvFhF.9ZjitReumSSylJx2gfNO9tpVWT.CfSm', '01222222222', 'Member', NULL, 'default1.jpg', 'Activated', 0, 0, NULL),
(19, 'fg', 'chongzhqeadengzhe@gmail.com', '$2y$10$HLxge4gOPDJohSPLaFw5iOWEJXtr59SeXWWZIbToSywe7J7dUruDa', '0182222222', 'Member', NULL, 'default3.jpg', 'Activated', 0, 0, NULL),
(20, 'ccccccc', 'chongzfsxfsxhengzhe@gmail.com', '$2y$10$frkYW0gghOQyYtq.CXhQ4.mpiX9t.H6tkTKI30VUBRjZOYpuGVlD2', '01844444444', 'Member', NULL, 'default2.jpg', 'Activated', 0, 0, NULL),
(21, 'cdxc', 'chongzhejnkngzhe@gmail.com', '$2y$10$dWgvbUzv7tnFn/761oqFXe0/6ieY3aGQHTtBxM757Fv5wwfIle1XG', '01222222222', 'Member', NULL, 'default2.jpg', 'Activated', 0, 0, NULL),
(22, 'c', 'chongzhengzhe@gmail.com', '$2y$10$hXBefLI2X4UbdANHq4shFO1shKn59YthG8J.Of6j99gE6CtipVaqC', '01222222222', 'Member', NULL, 'default6.jpg', 'Activated', 0, 0, NULL);

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
(2, 6, 'Home', 'Brayden', '01154789632', 'qqqqqqqqqqqqq', 'qqqqqqqqqqqq', 'qqqqqqq', 'Selangor', '52011', 'Malaysia', 0, '2025-12-14 10:10:18'),
(4, 6, 'word', 'Brayden', '01154789632', 'No998, Jalan Kehantar,', 'Bukit nanti, Tangkak', 'sa', 'Terengganu', '52011', 'Malaysia', 0, '2025-12-14 10:13:26'),
(5, 7, 'Home', 'nn', '0104507792', '11, taman gunung emas 3', '', 'Johor', 'Johor', '84900', 'Malaysia', 0, '2025-12-14 12:35:11'),
(6, 1, 'jsholis', 'dd', 'f', 'f', '', 'f', 'Melaka', 'f', 'Malaysia', 1, '2025-12-15 15:01:12'),
(7, 8, 'home', 'x', 's', 'scac', '', 'ds', 'Sarawak', 'rr', 'Malaysia', 0, '2025-12-15 15:13:46'),
(10, 6, 'SweetHome', 'Seah Ni Jun', '01147859632', 'No108, kashang', 'PV12, condo yap', 'Mar', 'Sarawak', '87541', 'Malaysia', 1, '2025-12-18 22:36:43');

-- --------------------------------------------------------

--
-- 表的结构 `user_voucher`
--

CREATE TABLE `user_voucher` (
  `UserVoucherID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `VoucherID` int(11) NOT NULL,
  `IsUsed` tinyint(1) DEFAULT 0,
  `AssignedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UsedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `user_voucher`
--

INSERT INTO `user_voucher` (`UserVoucherID`, `UserID`, `VoucherID`, `IsUsed`, `AssignedAt`, `UsedAt`) VALUES
(1, 1, 4, 0, '2025-12-18 14:35:52', NULL),
(2, 1, 2, 0, '2025-12-18 14:35:52', NULL),
(3, 1, 3, 0, '2025-12-18 14:35:52', NULL),
(4, 1, 1, 0, '2025-12-18 14:35:52', NULL),
(5, 5, 4, 0, '2025-12-18 14:35:52', NULL),
(6, 5, 2, 0, '2025-12-18 14:35:52', NULL),
(7, 5, 3, 0, '2025-12-18 14:35:52', NULL),
(8, 5, 1, 0, '2025-12-18 14:35:52', NULL),
(9, 6, 4, 1, '2025-12-18 14:35:52', NULL),
(10, 6, 2, 1, '2025-12-18 14:35:52', NULL),
(11, 6, 3, 1, '2025-12-18 14:35:52', NULL),
(12, 6, 1, 1, '2025-12-18 14:35:52', NULL),
(13, 7, 4, 0, '2025-12-18 14:35:52', NULL),
(14, 7, 2, 0, '2025-12-18 14:35:52', NULL),
(15, 7, 3, 0, '2025-12-18 14:35:52', NULL),
(16, 7, 1, 0, '2025-12-18 14:35:52', NULL),
(17, 8, 4, 0, '2025-12-18 14:35:52', NULL),
(18, 8, 2, 0, '2025-12-18 14:35:52', NULL),
(19, 8, 3, 0, '2025-12-18 14:35:52', NULL),
(20, 8, 1, 0, '2025-12-18 14:35:52', NULL),
(21, 9, 4, 0, '2025-12-18 14:35:52', NULL),
(22, 9, 2, 0, '2025-12-18 14:35:52', NULL),
(23, 9, 3, 0, '2025-12-18 14:35:52', NULL),
(24, 9, 1, 0, '2025-12-18 14:35:52', NULL),
(25, 10, 4, 0, '2025-12-18 14:35:52', NULL),
(26, 10, 2, 0, '2025-12-18 14:35:52', NULL),
(27, 10, 3, 0, '2025-12-18 14:35:52', NULL),
(28, 10, 1, 0, '2025-12-18 14:35:52', NULL),
(29, 11, 4, 0, '2025-12-18 14:35:52', NULL),
(30, 11, 2, 0, '2025-12-18 14:35:52', NULL),
(31, 11, 3, 0, '2025-12-18 14:35:52', NULL),
(32, 11, 1, 0, '2025-12-18 14:35:52', NULL),
(33, 12, 4, 0, '2025-12-18 14:35:52', NULL),
(34, 12, 2, 0, '2025-12-18 14:35:52', NULL),
(35, 12, 3, 0, '2025-12-18 14:35:52', NULL),
(36, 12, 1, 0, '2025-12-18 14:35:52', NULL),
(37, 13, 4, 0, '2025-12-18 14:35:52', NULL),
(38, 13, 2, 0, '2025-12-18 14:35:52', NULL),
(39, 13, 3, 0, '2025-12-18 14:35:52', NULL),
(40, 13, 1, 0, '2025-12-18 14:35:52', NULL),
(41, 14, 4, 0, '2025-12-18 14:35:52', NULL),
(42, 14, 2, 0, '2025-12-18 14:35:52', NULL),
(43, 14, 3, 0, '2025-12-18 14:35:52', NULL),
(44, 14, 1, 0, '2025-12-18 14:35:52', NULL),
(45, 15, 4, 0, '2025-12-18 14:35:52', NULL),
(46, 15, 2, 0, '2025-12-18 14:35:52', NULL),
(47, 15, 3, 0, '2025-12-18 14:35:52', NULL),
(48, 15, 1, 0, '2025-12-18 14:35:52', NULL),
(49, 16, 4, 0, '2025-12-18 14:35:52', NULL),
(50, 16, 2, 0, '2025-12-18 14:35:52', NULL),
(51, 16, 3, 0, '2025-12-18 14:35:52', NULL),
(52, 16, 1, 0, '2025-12-18 14:35:52', NULL),
(53, 17, 4, 0, '2025-12-18 14:35:52', NULL),
(54, 17, 2, 0, '2025-12-18 14:35:52', NULL),
(55, 17, 3, 0, '2025-12-18 14:35:52', NULL),
(56, 17, 1, 0, '2025-12-18 14:35:52', NULL),
(57, 18, 4, 0, '2025-12-18 14:35:52', NULL),
(58, 18, 2, 0, '2025-12-18 14:35:52', NULL),
(59, 18, 3, 0, '2025-12-18 14:35:52', NULL),
(60, 18, 1, 0, '2025-12-18 14:35:52', NULL),
(61, 19, 4, 0, '2025-12-18 14:35:52', NULL),
(62, 19, 2, 0, '2025-12-18 14:35:52', NULL),
(63, 19, 3, 0, '2025-12-18 14:35:52', NULL),
(64, 19, 1, 0, '2025-12-18 14:35:52', NULL),
(65, 20, 4, 0, '2025-12-18 14:35:52', NULL),
(66, 20, 2, 0, '2025-12-18 14:35:52', NULL),
(67, 20, 3, 0, '2025-12-18 14:35:52', NULL),
(68, 20, 1, 0, '2025-12-18 14:35:52', NULL),
(69, 21, 4, 0, '2025-12-18 14:35:52', NULL),
(70, 21, 2, 0, '2025-12-18 14:35:52', NULL),
(71, 21, 3, 0, '2025-12-18 14:35:52', NULL),
(72, 21, 1, 0, '2025-12-18 14:35:52', NULL),
(73, 22, 4, 0, '2025-12-18 14:35:52', NULL),
(74, 22, 2, 0, '2025-12-18 14:35:52', NULL),
(75, 22, 3, 0, '2025-12-18 14:35:52', NULL),
(76, 22, 1, 0, '2025-12-18 14:35:52', NULL),
(128, 7, 4, 0, '2025-12-18 14:37:02', NULL),
(129, 6, 4, 0, '2025-12-18 16:37:22', NULL),
(130, 6, 4, 0, '2025-12-18 16:44:09', NULL),
(131, 6, 4, 0, '2025-12-18 17:00:21', NULL),
(132, 6, 4, 0, '2025-12-18 18:27:02', NULL),
(133, 6, 4, 0, '2025-12-18 18:54:00', NULL),
(134, 6, 4, 0, '2025-12-18 18:56:12', NULL),
(135, 6, 4, 0, '2025-12-18 19:15:33', NULL),
(136, 6, 4, 0, '2025-12-18 20:27:09', NULL),
(137, 6, 4, 0, '2025-12-18 20:34:01', NULL),
(138, 6, 4, 0, '2025-12-18 20:42:40', NULL),
(139, 6, 4, 0, '2025-12-18 20:49:00', NULL),
(140, 6, 4, 0, '2025-12-18 21:06:23', NULL),
(141, 6, 4, 0, '2025-12-18 21:07:40', NULL),
(142, 6, 4, 0, '2025-12-18 21:08:50', NULL),
(143, 6, 4, 0, '2025-12-18 22:37:20', NULL),
(144, 6, 4, 0, '2025-12-19 12:48:03', NULL),
(145, 6, 4, 0, '2025-12-19 13:22:18', NULL),
(146, 6, 4, 0, '2025-12-19 13:23:33', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `voucher`
--

CREATE TABLE `voucher` (
  `VoucherID` int(11) NOT NULL,
  `Code` varchar(30) NOT NULL,
  `DiscountType` enum('percent','fixed') NOT NULL,
  `DiscountValue` decimal(10,2) NOT NULL,
  `MinSpend` decimal(10,2) DEFAULT 0.00,
  `ExpiryDate` date DEFAULT NULL,
  `Status` enum('active','inactive') DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `voucher`
--

INSERT INTO `voucher` (`VoucherID`, `Code`, `DiscountType`, `DiscountValue`, `MinSpend`, `ExpiryDate`, `Status`, `CreatedAt`) VALUES
(1, 'WELCOME10', 'percent', 10.00, 0.00, '2026-12-31', 'active', '2025-12-18 14:35:52'),
(2, 'SAVE20', 'fixed', 20.00, 300.00, '2026-12-31', 'active', '2025-12-18 14:35:52'),
(3, 'VIP50', 'fixed', 50.00, 600.00, '2026-12-31', 'active', '2025-12-18 14:35:52'),
(4, 'LUXURY15', 'percent', 15.00, 1000.00, '2026-12-31', 'active', '2025-12-18 14:35:52');

-- --------------------------------------------------------

--
-- 表的结构 `voucher_rule`
--

CREATE TABLE `voucher_rule` (
  `RuleID` int(11) NOT NULL,
  `MinOrderAmount` decimal(10,2) NOT NULL,
  `VoucherID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- 表的索引 `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`FavoriteID`),
  ADD UNIQUE KEY `UserID` (`UserID`,`ProductID`),
  ADD KEY `fk_fav_product` (`ProductID`);

--
-- 表的索引 `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ShippingAddressID` (`ShippingAddressID`),
  ADD KEY `fk_order_voucher` (`VoucherID`);

--
-- 表的索引 `order_status`
--
ALTER TABLE `order_status`
  ADD PRIMARY KEY (`StatusID`),
  ADD UNIQUE KEY `unique_order` (`OrderID`);

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
-- 表的索引 `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`ImageID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- 表的索引 `subscriber`
--
ALTER TABLE `subscriber`
  ADD PRIMARY KEY (`email`);

--
-- 表的索引 `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `userID` (`userID`);

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
-- 表的索引 `user_voucher`
--
ALTER TABLE `user_voucher`
  ADD PRIMARY KEY (`UserVoucherID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `VoucherID` (`VoucherID`);

--
-- 表的索引 `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`VoucherID`),
  ADD UNIQUE KEY `Code` (`Code`);

--
-- 表的索引 `voucher_rule`
--
ALTER TABLE `voucher_rule`
  ADD PRIMARY KEY (`RuleID`),
  ADD KEY `VoucherID` (`VoucherID`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `favorites`
--
ALTER TABLE `favorites`
  MODIFY `FavoriteID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `order_status`
--
ALTER TABLE `order_status`
  MODIFY `StatusID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- 使用表AUTO_INCREMENT `product_images`
--
ALTER TABLE `product_images`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- 使用表AUTO_INCREMENT `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- 使用表AUTO_INCREMENT `user_address`
--
ALTER TABLE `user_address`
  MODIFY `AddressID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用表AUTO_INCREMENT `user_voucher`
--
ALTER TABLE `user_voucher`
  MODIFY `UserVoucherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- 使用表AUTO_INCREMENT `voucher`
--
ALTER TABLE `voucher`
  MODIFY `VoucherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用表AUTO_INCREMENT `voucher_rule`
--
ALTER TABLE `voucher_rule`
  MODIFY `RuleID` int(11) NOT NULL AUTO_INCREMENT;

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
-- 限制表 `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- 限制表 `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `fk_order_voucher` FOREIGN KEY (`VoucherID`) REFERENCES `voucher` (`VoucherID`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`),
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`ShippingAddressID`) REFERENCES `user_address` (`AddressID`) ON DELETE SET NULL;

--
-- 限制表 `order_status`
--
ALTER TABLE `order_status`
  ADD CONSTRAINT `fk_status_order` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE;

--
-- 限制表 `productorder`
--
ALTER TABLE `productorder`
  ADD CONSTRAINT `productorder_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `productorder_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- 限制表 `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- 限制表 `token`
--
ALTER TABLE `token`
  ADD CONSTRAINT `token_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`);

--
-- 限制表 `user_address`
--
ALTER TABLE `user_address`
  ADD CONSTRAINT `user_address_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
