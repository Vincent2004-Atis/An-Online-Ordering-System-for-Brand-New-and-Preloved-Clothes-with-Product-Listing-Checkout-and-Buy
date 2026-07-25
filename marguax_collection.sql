-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2026 at 06:55 PM
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
-- Database: `marguax_collection`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `created_at`) VALUES
(1, 'Dress', 'Dresses for all occasions', '2026-03-24 04:21:48'),
(3, 'Top', 'Tops and blouses', '2026-03-24 04:21:48'),
(5, 'Accessories', 'Fashion accessories', '2026-03-24 04:21:48'),
(6, 'Bikini', 'Swimwear and bikinis', '2026-07-19 15:14:38'),
(7, 'Bottom', NULL, '2026-07-24 08:13:12'),
(8, 'Pair', NULL, '2026-07-24 08:13:12');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL DEFAULT 'General Inquiry',
  `order_id` int(11) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`conversation_id`, `user_id`, `subject`, `order_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'General Inquiry', NULL, 'open', '2026-07-21 22:37:58', '2026-07-24 11:27:37');

-- --------------------------------------------------------

--
-- Table structure for table `homepage_slots`
--

CREATE TABLE `homepage_slots` (
  `slot_id` varchar(30) NOT NULL,
  `label` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homepage_slots`
--

INSERT INTO `homepage_slots` (`slot_id`, `label`, `image_path`, `updated_at`) VALUES
('accessories', 'Accessories', 'images/homepage/06d27a210814f4e05c17609981cd668a.jpg', '2026-07-22 14:07:24'),
('dresses', 'Dresses', 'images/homepage/6ab2a9a8fdd1f0f11d9d5f5fa28d07e5.jpg', '2026-07-22 14:06:48'),
('featured', 'Featured Outfit', 'images/homepage/2f2c8cc93672b97f551383ad3c3d0734.jpg', '2026-07-22 14:06:30'),
('preowned', 'Pre-Owned', 'images/homepage/187484c2a809c772f511ab668f84ecb3.jpg', '2026-07-22 14:07:16'),
('tops', 'Tops & Blouses', 'images/homepage/fd67b9217ff463856dae4f7f994a6ff2.jpg', '2026-07-22 14:07:02');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('customer','admin') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `conversation_id`, `sender_type`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'customer', 2, 'hiii', 1, '2026-07-21 22:37:58'),
(2, 1, 'customer', 2, 'diin ka', 1, '2026-07-22 16:45:57'),
(3, 1, 'admin', 1, 'hiii', 1, '2026-07-23 15:40:39'),
(4, 1, 'admin', 1, 'good eve sir what can help you', 1, '2026-07-24 11:27:37');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `order_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 1, '✅ Order Completed!', 'Your Order #1 has been completed. Thank you for shopping with Marguax_Collectionoration!', 0, '2026-07-21 16:45:00'),
(2, 2, 7, '📦 Your order is on its way!', 'Your Order #7 is now being processed and will be delivered to your address soon. Please prepare your payment upon delivery.', 0, '2026-07-22 18:11:06'),
(3, 2, 7, '💳 Payment Verified!', 'We\'ve confirmed your GCash payment for Order #7. Your order is now being processed.', 0, '2026-07-22 18:11:06'),
(4, 2, 6, '✅ Order Completed!', 'Your Order #6 has been completed. Thank you for shopping with Marguax_Collectionoration!', 0, '2026-07-22 18:32:55'),
(5, 2, 6, '💳 Payment Verified!', 'We\'ve confirmed your GCash payment for Order #6. Your order is now being processed.', 0, '2026-07-22 18:32:55'),
(6, 2, 7, '✅ Order Completed!', 'Your Order #7 has been completed. Thank you for shopping with Marguax_Collectionoration!', 0, '2026-07-22 18:33:01'),
(7, 2, 5, '✅ Order Completed!', 'Your Order #5 has been completed. Thank you for shopping with Marguax_Collectionoration!', 0, '2026-07-22 18:33:09'),
(8, 2, 5, '💳 Payment Verified!', 'We\'ve confirmed your GCash payment for Order #5. Your order is now being processed.', 0, '2026-07-22 18:33:09'),
(9, 2, 4, '✅ Order Completed!', 'Your Order #4 has been completed. Thank you for shopping with Marguax_Collectionoration!', 0, '2026-07-22 18:33:20'),
(10, 2, 4, '💳 Payment Verified!', 'We\'ve confirmed your GCash payment for Order #4. Your order is now being processed.', 0, '2026-07-22 18:33:20'),
(11, 2, 3, '✅ Order Completed!', 'Your Order #3 has been completed. Thank you for shopping with Marguax_Collectionoration!', 0, '2026-07-22 18:33:29'),
(12, 2, 3, '💳 Payment Verified!', 'We\'ve confirmed your GCash payment for Order #3. Your order is now being processed.', 0, '2026-07-22 18:33:29'),
(13, 2, 2, '✅ Order Completed!', 'Your Order #2 has been completed. Thank you for shopping with Marguax_Collectionoration!', 0, '2026-07-22 18:33:36'),
(14, 2, 2, '💳 Payment Verified!', 'We\'ve confirmed your GCash payment for Order #2. Your order is now being processed.', 0, '2026-07-22 18:33:36'),
(15, 2, 12, '✅ Order Completed!', 'Your Order #12 has been completed. Thank you for shopping with Marguax_Collectionoration!', 0, '2026-07-24 16:47:44'),
(16, 2, 12, '💳 Payment Verified!', 'We\'ve confirmed your GCash payment for Order #12. Your order is now being processed.', 0, '2026-07-24 16:47:44'),
(17, 2, 10, '📦 Your order is on its way!', 'Your Order #10 is now being processed and will be delivered to your address soon. Please prepare your payment upon delivery.', 0, '2026-07-24 16:48:49'),
(18, 2, 11, '📦 Your order is on its way!', 'Your Order #11 is now being processed and will be delivered to your address soon. Please prepare your payment upon delivery.', 0, '2026-07-24 16:48:57'),
(19, 2, 11, '💳 Payment Verified!', 'We\'ve confirmed your GCash payment for Order #11. Your order is now being processed.', 0, '2026-07-24 16:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `order_method` enum('pickup','shipping') DEFAULT 'pickup',
  `payment_method` enum('cash_on_pickup','cash_on_delivery','gcash','paymaya') DEFAULT 'cash_on_pickup',
  `payment_account_id` int(11) DEFAULT NULL,
  `payment_status` enum('pending','pending_verification','paid') NOT NULL DEFAULT 'pending',
  `gcash_reference` varchar(50) DEFAULT NULL,
  `order_status` enum('pending','processing','completed') DEFAULT 'pending',
  `queue_number` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `customer_name`, `address`, `contact_number`, `order_method`, `payment_method`, `payment_account_id`, `payment_status`, `gcash_reference`, `order_status`, `queue_number`, `total_amount`, `order_date`) VALUES
(1, 2, 'Vincent Carl Atis', 'sara', '09482841494', 'shipping', 'gcash', NULL, 'paid', NULL, 'completed', 101, 750.00, '2026-07-21 08:15:16'),
(2, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'shipping', 'gcash', NULL, 'paid', NULL, 'completed', 102, 150.00, '2026-07-22 09:03:16'),
(3, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'shipping', 'gcash', NULL, 'paid', NULL, 'completed', 103, 750.00, '2026-07-22 09:17:19'),
(4, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'shipping', 'gcash', NULL, 'paid', NULL, 'completed', 104, 750.00, '2026-07-22 09:17:51'),
(5, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'pickup', 'cash_on_pickup', NULL, 'paid', NULL, 'completed', 105, 750.00, '2026-07-22 09:47:50'),
(6, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'pickup', 'cash_on_pickup', NULL, 'paid', NULL, 'completed', 106, 300.00, '2026-07-22 09:50:12'),
(7, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', '', 'gcash', NULL, 'paid', NULL, 'completed', 107, 750.00, '2026-07-22 10:09:49'),
(8, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'pickup', 'cash_on_pickup', NULL, '', NULL, 'pending', 108, 300.00, '2026-07-23 10:01:27'),
(9, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', '', 'gcash', NULL, '', NULL, 'pending', 109, 450.00, '2026-07-23 10:30:32'),
(10, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'pickup', 'cash_on_pickup', NULL, 'pending_verification', NULL, 'processing', 110, 750.00, '2026-07-23 14:32:19'),
(11, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', '', 'gcash', NULL, 'paid', NULL, 'processing', 111, 500.00, '2026-07-23 14:37:48'),
(12, 2, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', '', 'gcash', NULL, 'paid', '1234567890123', 'completed', 112, 500.00, '2026-07-23 14:55:37');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 3, 1, 750.00),
(2, 2, 8, 1, 150.00),
(3, 3, 3, 1, 750.00),
(4, 4, 3, 1, 750.00),
(5, 5, 3, 1, 750.00),
(6, 6, 1, 1, 300.00),
(7, 7, 3, 1, 750.00),
(8, 8, 1, 1, 300.00),
(9, 9, 7, 1, 150.00),
(10, 9, 2, 1, 300.00),
(11, 10, 3, 1, 750.00),
(12, 11, 6, 1, 500.00),
(13, 12, 6, 1, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `otp_tokens`
--

CREATE TABLE `otp_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `identifier` varchar(150) NOT NULL COMMENT 'email address',
  `type` enum('login','register','reset') NOT NULL,
  `token_hash` varchar(64) NOT NULL COMMENT 'SHA-256 of the 6-digit OTP',
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_tokens`
--

INSERT INTO `otp_tokens` (`id`, `identifier`, `type`, `token_hash`, `expires_at`, `used`, `created_at`) VALUES
(1, 'atisvincentcarl1@gmail.com', 'login', '563de9f66a6804a8aaf5f702efb2eb8a0ff148f3d5fa201f5663156c770237e6', '2026-07-20 17:31:32', 1, '2026-07-20 23:26:32'),
(2, 'atisvincentcarl1@gmail.com', 'login', 'c6f66f61651a50f9577042781ac2677572e639c8f67c9ccd9182b5870adef757', '2026-07-21 04:14:03', 1, '2026-07-21 10:09:03'),
(3, 'atisvincentcarl1@gmail.com', 'login', 'cbd22cc24ea9cb317e64ae14e3faa11e53242da88a2ccec654812c13f02ad27e', '2026-07-21 09:26:24', 1, '2026-07-21 15:21:24'),
(4, 'vinc.atis.ui@phinmaed.com', 'reset', '8a09d6858455ebcff0ae9e2789228f8e8b7678f90fa514761fb76f22a00f31b3', '2026-07-21 10:18:23', 1, '2026-07-21 16:08:23'),
(5, 'vinc.atis.ui@phinmaed.com', 'register', 'baff632fa7261cb92fc90095a1254ffa78441340a3c871fa80d232fd9e40a425', '2026-07-21 10:22:10', 1, '2026-07-21 16:12:10'),
(6, 'vinc.atis.ui@phinmaed.com', 'login', 'f8f76d2e153e1d712f30e058b7276996be8c85cf7897a8edc137699ef0c80517', '2026-07-21 10:17:43', 1, '2026-07-21 16:12:43'),
(7, 'vinc.atis.ui@phinmaed.com', 'reset', '35afa2593c135eb7a8d07286b28ef4cdd71e86874f9604feffabe82c7f75e675', '2026-07-21 10:46:39', 0, '2026-07-21 16:36:39'),
(9, 'atisvincentcarl1@gmail.com', 'login', 'abddf6e6d14275e9398aef96c49d666508d8979162d0baa3e238d548d1afe9cc', '2026-07-21 10:49:08', 1, '2026-07-21 16:44:08'),
(10, 'vinc.atis.ui@phinmaed.com', 'login', '1dab9d7f88d3ddb2a07bb5ab01ad97022d68f812a49bbc27575f765c1c39c2b2', '2026-07-21 11:00:56', 1, '2026-07-21 16:55:56'),
(11, 'vinc.atis.ui@phinmaed.com', 'login', 'a329cccbff81f4e9e6172820eacdcd55f0921bda2876ce4b96155ce65e1e29af', '2026-07-21 16:37:51', 1, '2026-07-21 22:32:51'),
(12, 'atisvincentcarl1@gmail.com', 'login', '3e4bd98bf280e7adbc253729e51e45b8aaf6e0261fedb4451ce3d9023147c4fa', '2026-07-22 09:10:25', 1, '2026-07-22 15:05:25'),
(13, 'vinc.atis.ui@phinmaed.com', 'login', '79c7a3d003a9757700273b1c9cfcc28e5a414a6fe87f54e8a7497d6d8a6c61c8', '2026-07-22 09:11:24', 1, '2026-07-22 15:06:24'),
(14, 'atisvincentcarl1@gmail.com', 'login', '30d480b2020034f5557a4b6b40584681d4f124db3846d2d727cd0bfb381143af', '2026-07-22 09:14:11', 1, '2026-07-22 15:09:11'),
(15, 'vinc.atis.ui@phinmaed.com', 'login', 'b11b3fbd5ff0b1bca0a0041fb7d9392b11a1d899fe713830d29967d37e76fae6', '2026-07-22 09:29:39', 1, '2026-07-22 15:24:39'),
(16, 'atisvincentcarl1@gmail.com', 'login', 'e73c08a4c504471db99dca2b7a1293721d9d1c3b60986d8c16351943aab4b136', '2026-07-22 09:41:59', 1, '2026-07-22 15:36:59'),
(17, 'vinc.atis.ui@phinmaed.com', 'login', '06378a0ef39ea01bee41a176529911dbc5446cc04ffcf3b96be99b2c1767c546', '2026-07-22 09:47:57', 1, '2026-07-22 15:42:57'),
(19, 'atisvincentcarl1@gmail.com', 'login', 'e906498da8729728dfb9973b06e1b8871ca21e1330611e4b25703c83920661c5', '2026-07-22 10:24:38', 1, '2026-07-22 16:19:38'),
(20, 'vinc.atis.ui@phinmaed.com', 'login', 'f1a1aaf06c8e8a7bec859a7044e1b6f9e54c0b934d1c232486df0fd96d176c9d', '2026-07-22 10:40:31', 1, '2026-07-22 16:35:31'),
(21, 'atisvincentcarl1@gmail.com', 'login', '6770d2d183b2d249466823c77133bc4db57b46edace8b66ce3ba731c4edf5362', '2026-07-22 12:15:07', 1, '2026-07-22 18:10:07'),
(22, 'vinc.atis.ui@phinmaed.com', 'login', '14337a3f390fde50de54c1291fc0189050501b6c170df7bf5c8321ab2a9883a4', '2026-07-22 12:16:24', 1, '2026-07-22 18:11:24'),
(23, 'atisvincentcarl1@gmail.com', 'login', '83a4abab8b075b40c9d8b15a4841550e18f17288e02184b0e9ffcbcdeb577ed5', '2026-07-22 12:36:41', 1, '2026-07-22 18:31:41'),
(24, 'vinc.atis.ui@phinmaed.com', 'login', '2b8beecb611c81a49e7683b4dde0f7269e95e3cd21525d9852b913d5bfd5ce28', '2026-07-22 12:41:49', 1, '2026-07-22 18:36:49'),
(27, 'atisvincentcarl1@gmail.com', 'login', 'a696264e79d3bda82e47b8724d84d7adb2efd4ae4aaa96f2733cb6237c694d07', '2026-07-22 12:45:44', 1, '2026-07-22 18:40:44'),
(28, 'vinc.atis.ui@phinmaed.com', 'login', '4dc8394b4b2480237e3a9c68a69838a4b0bafc6e8e1c0ff3a80a333c93b855d2', '2026-07-22 12:47:10', 1, '2026-07-22 18:42:10'),
(29, 'vinc.atis.ui@phinmaed.com', 'login', 'a665f65c942af9f24ec60c5deff9a16fec956b3a68e892d5241aea5a2a2efc45', '2026-07-22 15:25:10', 1, '2026-07-22 21:20:10'),
(30, 'atisvincentcarl1@gmail.com', 'login', 'ec2999463467611a50298fea46f51533c2ba649d4e543da2ba3aa49e5b34f1f4', '2026-07-22 15:30:03', 1, '2026-07-22 21:25:03'),
(31, 'atisvincentcarl1@gmail.com', 'login', '987e98c35e1f39838bc352e9fcd087abfd0f9e74b0264a22c60557497c9b87f4', '2026-07-22 16:16:14', 1, '2026-07-22 22:11:14'),
(32, 'atisvincentcarl1@gmail.com', 'login', 'd5e76886cb80a0fc5b252897bcd05ca87794a9f75edc720db170077f441d52d1', '2026-07-23 09:44:36', 1, '2026-07-23 15:39:36'),
(33, 'vinc.atis.ui@phinmaed.com', 'login', '099dd42d0c285cc6f7c3b7466f4cd0ba6a9f81a95d63289bb2f8d99e08836c7d', '2026-07-23 09:46:08', 1, '2026-07-23 15:41:08'),
(34, 'vinc.atis.ui@phinmaed.com', 'login', '510b9bd2702b25e749ed1d6c1516f7b19ed25ba73e3b43484814b206176a182e', '2026-07-23 10:55:58', 1, '2026-07-23 16:50:58'),
(35, 'vinc.atis.ui@phinmaed.com', 'login', 'ea8bd23dce798303fc3ec522ef63818eb309030bd9ea9892e22c8a2ea94edeef', '2026-07-23 11:10:58', 1, '2026-07-23 17:05:58'),
(37, 'vinc.atis.ui@phinmaed.com', 'login', '33d361713d0be492cd4e1d370c24731a629f1136d86e0aeb6ba3de33bf8dbf54', '2026-07-23 12:06:39', 1, '2026-07-23 18:01:39'),
(39, 'vinc.atis.ui@phinmaed.com', 'login', 'e43db07d5de5ea35eae764e3eaf0c35bed8eade2ec155c4a70c242a7bad39e09', '2026-07-23 16:33:52', 1, '2026-07-23 22:28:52'),
(40, 'atisvincentcarl1@gmail.com', 'login', 'bb259d3e62783dcc319540a663c77db3a95b1186617e751fdea97e2766b0763c', '2026-07-23 17:01:31', 1, '2026-07-23 22:56:31'),
(41, 'atisvincentcarl1@gmail.com', 'login', 'a6ca755056db5765709cc0049a1a18650fc9e5f366ec49595afe88e2dd0a40da', '2026-07-23 17:32:58', 1, '2026-07-23 23:27:58'),
(43, 'atisvincentcarl1@gmail.com', 'login', '8ae367282aaac2467e0bb33ef6a7f4405c6f5ffddc459d0ea1a6f701904c24a4', '2026-07-23 17:38:49', 1, '2026-07-23 23:33:49'),
(44, 'atisvincentcarl1@gmail.com', 'login', '78e71d389e95c2b48b75cc7ed132b2db2ec06421657c18238afe5d50abf5663f', '2026-07-23 18:06:14', 1, '2026-07-24 00:01:14'),
(45, 'atisvincentcarl1@gmail.com', 'login', '17e4ce40b9fcc8d1504fd536ac455e19f38cd975a4a096aa5d470a4865f77fe7', '2026-07-24 05:26:41', 1, '2026-07-24 11:21:41'),
(46, 'atisvincentcarl1@gmail.com', 'login', '52de79ea43620b31a5b6169745c651ba3855c669bbc4acd438c3ef869a6c7f25', '2026-07-24 05:33:11', 1, '2026-07-24 11:28:11'),
(47, 'casimirojerico723@gmail.com', 'register', 'b71779d154997003a437cef71890ff7add1554337196860123724eda9a215eba', '2026-07-24 05:43:50', 0, '2026-07-24 11:33:50'),
(48, 'vinc.atis.ui@phinmaed.com', 'login', '7f76ee61750b0d75752966f973c2567ad49905b38c0c3b748f84fabc8e3e2d5e', '2026-07-24 05:45:52', 1, '2026-07-24 11:40:52'),
(49, 'vinc.atis.ui@phinmaed.com', 'login', '772053053d85e8771d0a258e4045d9d9e8d03303611929b5c62796f1e4edfb68', '2026-07-24 10:09:16', 1, '2026-07-24 16:04:16'),
(50, 'atisvincentcarl1@gmail.com', 'login', '9592b3f14dbf8a6a494bd3284b3379a5fa79720d15500dc5130e69a3ff49ad71', '2026-07-24 10:20:08', 1, '2026-07-24 16:15:08'),
(51, 'vinc.atis.ui@phinmaed.com', 'login', '879d8f427639a8c0d507aed5bc8a5eb5a9fce7fbb19467e547345b5c11491146', '2026-07-24 10:24:12', 1, '2026-07-24 16:19:12'),
(52, 'atisvincentcarl1@gmail.com', 'login', '6e1c267e063661aa45129bde95dae6d498c45243ef49d0b0778e23a3c066bedd', '2026-07-24 10:51:57', 1, '2026-07-24 16:46:57'),
(53, 'atisvincentcarl1@gmail.com', 'login', '6cf09df378df23b82c9a5e41317007befd8bddae82391d4e5991adf8389147c2', '2026-07-24 10:53:18', 1, '2026-07-24 16:48:18'),
(54, 'vinc.atis.ui@phinmaed.com', 'login', '0a41f5784c56223d42fd5e1b60616ed30cc67ed926d8a517fc91225167b381b4', '2026-07-24 10:54:08', 1, '2026-07-24 16:49:08');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `product_type` enum('loose','package') DEFAULT 'loose',
  `image` varchar(255) DEFAULT 'images/product-placeholder.jpg',
  `stock` int(11) DEFAULT 100,
  `sold_out_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price`, `product_type`, `image`, `stock`, `sold_out_at`, `created_at`) VALUES
(1, 6, 'Bikini 1', '', 300.00, 'loose', 'images/products/375095d884c45a0188572c1ff8722c5a.jpg', 1, NULL, '2026-03-24 03:15:28'),
(2, 1, 'Dress', '', 300.00, 'loose', 'images/products/96b2fa79be7883f02efe4fa9d9a68a63.jpg', 1, NULL, '2026-03-24 03:15:28'),
(3, 3, 'Terno 2', '', 750.00, 'loose', 'images/products/e88a3e273714a0b5d81ab8d1362388f8.jpg', 1, NULL, '2026-03-24 03:15:28'),
(4, 3, 'Top 1', '', 750.00, 'loose', 'images/products/adb307ffcf2b63ea012ebd7a2a841851.jpg', 1, NULL, '2026-03-24 03:15:28'),
(5, 1, 'Dress 1', '', 500.00, 'loose', 'images/products/fc9a9e5528f528e7a681f631c745c814.jpg', 1, NULL, '2026-07-22 07:15:11'),
(6, 8, 'Terno 1', '', 500.00, 'loose', 'images/products/e9d6102f917a7328c470cefa05fe6529.jpg', 1, NULL, '2026-07-22 07:21:27'),
(7, 5, 'Bracelet', '', 150.00, 'loose', 'images/products/7db8b38c6125be9264116286c3fb9369.jpg', 1, NULL, '2026-07-22 07:23:14'),
(8, 5, 'Accesories', '', 150.00, 'loose', 'images/products/547c0a756c99535295d25b219c5ebec3.jpg', 1, NULL, '2026-07-22 07:23:50'),
(9, 8, 'TERNO 2', '', 400.00, 'loose', 'images/products/113109a4e7c2f09a789a71eb6015d2d5.jpg', 1, NULL, '2026-07-24 08:17:52'),
(10, 7, 'Bottom 1', '', 550.00, 'loose', 'images/products/96e9224d6370a584debaa9ea1f8f4947.jpg', 1, NULL, '2026-07-24 08:19:00');

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `trg_products_track_soldout` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.stock = 0 AND OLD.stock <> 0 THEN
        SET NEW.sold_out_at = NOW();
    ELSEIF NEW.stock > 0 THEN
        SET NEW.sold_out_at = NULL;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `contact_number`, `address`, `profile_photo`, `role`, `created_at`) VALUES
(1, 'Marguax Admin', 'atisvincentcarl1@gmail.com', '$2b$10$fxOnAfIXsYBgkgFraECg1O99O4AV45InmCOCOFpoJhY.1eLHk.kle', NULL, NULL, NULL, 'admin', '2026-07-20 14:34:07'),
(2, 'Vincent Carl Atis', 'vinc.atis.ui@phinmaed.com', '$2y$10$w66KpzvfeC1rzEvfXq3up.h9zak3eR75oB9jAx3hrQaEcoj/fCPg.', '09482841494', 'Brgy. Bagaygay Sara, Iloilo', 'uploads/profiles/user_2_1784621637.webp', 'customer', '2026-07-21 08:12:40');

-- --------------------------------------------------------

--
-- Table structure for table `user_payment_accounts`
--

CREATE TABLE `user_payment_accounts` (
  `account_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_type` enum('gcash','paymaya') NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_payment_accounts`
--

INSERT INTO `user_payment_accounts` (`account_id`, `user_id`, `account_type`, `account_name`, `account_number`, `bank_name`, `is_default`, `created_at`) VALUES
(1, 2, 'gcash', 'Vincent Carl Atis', '09482841494', NULL, 1, '2026-07-21 14:35:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `homepage_slots`
--
ALTER TABLE `homepage_slots`
  ADD PRIMARY KEY (`slot_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `otp_tokens`
--
ALTER TABLE `otp_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_identifier_type` (`identifier`,`type`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_payment_accounts`
--
ALTER TABLE `user_payment_accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `otp_tokens`
--
ALTER TABLE `otp_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_payment_accounts`
--
ALTER TABLE `user_payment_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_payment_accounts`
--
ALTER TABLE `user_payment_accounts`
  ADD CONSTRAINT `user_payment_accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
