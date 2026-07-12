-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2026 at 05:46 PM
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

--xxxxx
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
(1, 'Male Scents', 'Perfumes and colognes for men', '2026-03-24 04:21:48'),
(2, 'Female Scents', 'Perfumes and fragrances for women', '2026-03-24 04:21:48'),
(3, 'Health Products', 'Wellness and health supplements', '2026-03-24 04:21:48'),
(4, 'Soaps & Oils', 'Personal care soaps and oils', '2026-03-24 04:21:48'),
(5, 'Packages', 'Membership starter packages', '2026-03-24 04:21:48');
  
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
(16, 16, 'General Inquiry', NULL, 'open', '2026-04-23 13:39:35', '2026-06-08 00:13:52');

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
('accessories', 'Accessories', 'images/homepage/5acbdc7fefb8e69cac61aa50e390634f.webp', '2026-06-30 14:21:02'),
('dresses', 'Dresses', 'images/homepage/f8efe8e18991818210a51fad464aebc6.jpg', '2026-06-30 14:20:35'),
('featured', 'Featured Outfit', 'images/homepage/c5f5d71835521b20451ae6afb914a9de.jpg', '2026-06-30 14:20:27'),
('preowned', 'Pre-Owned', 'images/homepage/48b89337d819f77b7f6b2d84c752a2ec.png', '2026-06-30 14:20:50'),
('tops', 'Tops & Blouses', 'images/homepage/11e676c5529be15111f98fabba0b1970.jpeg', '2026-06-30 14:20:43');

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
(18, 16, 'customer', 16, 'Hiii sirrr', 1, '2026-04-23 13:39:35'),
(19, 16, 'customer', 16, 'hows my order?', 1, '2026-04-23 13:48:16'),
(20, 16, 'admin', 1, 'DHHayDJH', 1, '2026-04-23 13:52:13'),
(21, 16, 'customer', 16, 'qafsAF', 1, '2026-05-23 14:20:59'),
(22, 16, 'admin', 1, 'uyjgygkjklk', 0, '2026-06-08 00:13:52');

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
(6, 16, 16, '✅ Order Completed!', 'Your Order #16 has been completed. Thank you for shopping with Amazing World Marketing Corporation!', 1, '2026-04-23 13:51:07');

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
  `payment_status` enum('pending','paid') DEFAULT 'pending',
  `order_status` enum('pending','processing','completed') DEFAULT 'pending',
  `queue_number` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `customer_name`, `address`, `contact_number`, `order_method`, `payment_method`, `payment_account_id`, `payment_status`, `order_status`, `queue_number`, `total_amount`, `order_date`) VALUES
(15, 16, 'Vincent Carl Atis', 'Molo', '09482841494', 'shipping', 'cash_on_delivery', NULL, '', 'pending', 101, 750.00, '2026-04-23 05:39:57'),
(16, 16, 'Vincent Carl Atis', 'molo', '09482841494', 'shipping', 'cash_on_delivery', NULL, 'paid', 'completed', 102, 1500.00, '2026-04-23 05:47:45'),
(17, 16, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'pickup', 'gcash', 1, '', 'pending', 103, 300.00, '2026-05-28 14:09:55'),
(18, 16, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'pickup', 'gcash', 1, '', 'pending', 104, 300.00, '2026-05-28 17:29:02'),
(19, 16, 'Vincent Carl Atis', 'Brgy. Bagaygay Sara, Iloilo', '09482841494', 'shipping', 'gcash', 1, '', 'pending', 105, 7500.00, '2026-06-07 16:09:46');

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
(18, 15, 39, 1, 750.00),
(19, 16, 39, 1, 750.00),
(21, 17, 34, 1, 300.00),
(22, 18, 34, 1, 300.00),
(23, 19, 37, 10, 750.00);

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
(10, 'atisvincentcarl1@gmail.com', 'register', '9cbc98161c84ae8ba9128b018bd1aa59dfeeaf3fadce50a8f8f4f3ee031c9d60', '2026-04-08 11:54:58', 1, '2026-04-08 17:44:58'),
(11, 'atisvincentcarl1@gmail.com', 'login', '696ee5696f25582f9f25fe75c02fcb8e3d86610936c16354a06a75635ede998f', '2026-04-08 11:52:08', 1, '2026-04-08 17:47:08'),
(15, 'admin@amazingworld.com', 'login', 'ab6b255f111b42cc2a88a2611d2decb8f6e5ee090804b7096396af4646a42587', '2026-04-08 12:25:42', 0, '2026-04-08 18:20:42'),
(16, 'atisvincentcarl1@gmail.com', 'login', 'af19b9ab53c4f09fca41f0f02b6745057b11010e71ff8a13294a76e165913942', '2026-04-08 12:43:34', 1, '2026-04-08 18:38:34'),
(18, 'haha@gmail.com', 'login', 'f29e1174f760d043fcf1f8ada22dd4d072e73893d67566fef9fdf74064432ef1', '2026-04-08 12:52:08', 0, '2026-04-08 18:47:08'),
(19, 'atisvincentcarl1@gmail.com', 'login', '56e4e272fb9c98f3f12398fd00db688d3b3a46c5d35a82c04edd3dd3e2eb9a97', '2026-04-08 12:52:27', 1, '2026-04-08 18:47:27'),
(21, 'rodu.fenandez.ui@phinmaed.com', 'register', 'e6ed1fc2625ed26546626c275679ddbad868d916a84e728c46a8f7ee9a3b255c', '2026-04-08 13:00:35', 1, '2026-04-08 18:50:35'),
(22, 'rodu.fenandez.ui@phinmaed.com', 'login', 'ddfc62addb613e438a0a54868a9e0675065c3a58b0c0c5f059132d6a03275c7d', '2026-04-08 12:57:36', 0, '2026-04-08 18:52:36'),
(23, 'mabo.naral.ui@phinmaed.com', 'register', '646191ff3f98407d93891ef638d21cd2b56aef9eca7e5bd0f068184033661c20', '2026-04-08 13:14:19', 1, '2026-04-08 19:04:19'),
(30, 'atisvincentcarl1@gmail.com', 'login', 'def47bfdac8c3f34ec87333f9caafaa40a2d9ffbd340e2ee69c6728eb20e6dff', '2026-04-23 07:39:49', 1, '2026-04-23 13:34:49'),
(31, 'vinc.atis.ui@phinmaed.com', 'register', '4740c958ff56e120231dc18fbceec7ed7eaef43f2e48b44eee01620f3b0b1449', '2026-04-23 07:48:16', 1, '2026-04-23 13:38:16'),
(32, 'vinc.atis.ui@phinmaed.com', 'login', 'e4d3165fdbd1e7682530eb3ef698a35ea9180e0154aa29fda761df46f98498a7', '2026-04-23 07:43:42', 1, '2026-04-23 13:38:42'),
(33, 'atisvincentcarl1@gmail.com', 'login', '22ab1abf6c1d1347a15efd2c6c4e99b5c3922df0073ab754a76665d1a88fcfbf', '2026-04-23 07:45:14', 1, '2026-04-23 13:40:14'),
(34, 'vinc.atis.ui@phinmaed.com', 'login', '96f5b03ed5094d119020b16f83df36d4b6c3f35633fb9c6841855e47cd9d10d9', '2026-04-23 07:51:20', 1, '2026-04-23 13:46:20'),
(35, 'atisvincentcarl1@gmail.com', 'login', '2f1df3b103edff62e81c3948296a2bb761b424f8fc3b708bec61b7cc6b3bc621', '2026-04-23 07:54:37', 1, '2026-04-23 13:49:37'),
(36, 'vinc.atis.ui@phinmaed.com', '', 'c98f8faa9df5e49dcf4c6b48cf34dae78f43bf698e9405850e540dbdc47782c7', '2026-04-29 09:37:21', 0, '2026-04-29 15:27:21'),
(37, 'vinc.atis.ui@phinmaed.com', '', '1e0d454f967eecf5901f27cdb8cbfe5e66a55b05ccdb278f33b495cff800afe1', '2026-04-29 09:37:56', 0, '2026-04-29 15:27:56'),
(38, 'vinc.atis.ui@phinmaed.com', '', 'c5ff9506c101b58bdcda8ce8ecf9f19d21bb010b876ea3907c82948e6dc20ba4', '2026-04-29 09:52:19', 0, '2026-04-29 15:42:19'),
(39, 'vinc.atis.ui@phinmaed.com', '', '52d6f294b3d8e9e583b1482d57bf80e42e4cce0f81ecdb3af05019d91d828b5b', '2026-04-29 09:52:27', 0, '2026-04-29 15:42:27'),
(40, 'vinc.atis.ui@phinmaed.com', '', '1dcb3ad0cc427da1749043faaa37a52fde1d83faf2b8eba5464c45be0cbdd4bf', '2026-04-29 09:53:03', 0, '2026-04-29 15:43:03'),
(41, 'vinc.atis.ui@phinmaed.com', '', '884e4179e7a5b6dad70a4b85c724a18f84985d044f3592e9e3abfb13a1553812', '2026-04-29 09:53:25', 0, '2026-04-29 15:43:25'),
(42, 'vinc.atis.ui@phinmaed.com', '', '63de62e84726a193c246a93ab8f5eaee4788cd2a0dd112353e5b11d7a865df18', '2026-04-29 09:53:46', 0, '2026-04-29 15:43:46'),
(43, 'vinc.atis.ui@phinmaed.com', '', '685b21c73d8be97fed10e034f0ad340a363473b2739ab07d7043398c2858519a', '2026-04-29 09:53:48', 0, '2026-04-29 15:43:48'),
(44, 'vinc.atis.ui@phinmaed.com', '', '241750206d510635e87f30067055817a30134f7f4ac1c52da3daa2343cf969a0', '2026-04-29 09:53:48', 0, '2026-04-29 15:43:48'),
(45, 'atisvincentcarl@gmail.com', '', '0721a5d562d3508e57404a2bd19010f9e3b3422832f2cd630d087f4e96cd208b', '2026-04-29 10:03:40', 0, '2026-04-29 15:53:40'),
(46, 'atisvincentcarl@gmail.com', '', 'f4032579217ada0f9bd1ba70c6775187539bffc17451207161e3cb9f73ee2c0a', '2026-04-29 10:08:06', 0, '2026-04-29 15:58:06'),
(47, 'atisvincentcarl@gmail.com', '', '48bbd4a7547d785641de41461175eea41b7054242bd90d1ccc6b115ed8f6abf4', '2026-04-29 10:08:18', 0, '2026-04-29 15:58:18'),
(48, 'vinc.atis.ui@phinmaed.com', '', 'e402bee12716de89765a1c3040e3b23c6dc90294358998b57d70b0702e298ba2', '2026-04-29 10:10:28', 0, '2026-04-29 16:00:28'),
(52, 'atisvincentcarl1@gmail.com', 'login', 'cc5e740e9f5f3781747c9344c21480fbe00f375f2ddc55c707d90e67d0a682f7', '2026-04-29 10:30:59', 1, '2026-04-29 16:25:59'),
(53, 'vinc.atis.ui@phinmaed.com', '', 'a40b7b56e8bccb9b1cc5b9deb70b40a7ca4aa88caa3cd9414b53e479fb482bb5', '2026-04-29 10:38:12', 0, '2026-04-29 16:28:12'),
(54, 'vinc.atis.ui@phinmaed.com', '', '188148ee8f27e99b90520ded2cb9ce529789acb2b65f014a89804f5771ce96cf', '2026-04-29 10:41:48', 0, '2026-04-29 16:31:48'),
(57, 'atisvincentcarl1@gmail.com', 'login', '25f893cad8388ee35d74537eaf8cc25cbceb7b157dea4c92ca3478092ec2a356', '2026-04-30 08:25:38', 1, '2026-04-30 14:20:38'),
(58, 'vinc.atis.ui@phinmaed.com', 'reset', '404777929198d3087af49b0d2ee5ed6aba09708f570fbfc75e86eaed7a27888c', '2026-04-30 08:32:01', 1, '2026-04-30 14:22:01'),
(59, 'vinc.atis.ui@phinmaed.com', 'login', '1d48bb78a8f8e33155a3d175451f1024b37042a62f0f0b81b692342ab0dfc8e9', '2026-04-30 08:32:27', 1, '2026-04-30 14:27:27'),
(63, 'atisvincentcarl1@gmail.com', 'login', 'a45004f9fb71d0fc5aedabef7776b8b841cf90552f0141cd9d351d6d0089c402', '2026-05-05 05:20:13', 1, '2026-05-05 11:15:13'),
(64, 'vinc.atis.ui@phinmaed.com', 'reset', 'bab15da5bdbf49e64e221313b4b046620f53b730951f7c7bb9a58d24f8ff8cbb', '2026-05-05 05:27:49', 1, '2026-05-05 11:17:49'),
(65, 'vinc.atis.ui@phinmaed.com', 'login', '971f2ce482eda11c4dc9f452c07ec95d69d06feebd9fcb00034b9e3cea0993e9', '2026-05-05 05:23:55', 1, '2026-05-05 11:18:55'),
(67, 'vinc.atis.ui@phinmaed.com', 'login', 'd2aae45bfd94c90dbad28556a848932b35db99b358ede5c6e5ac07119173b519', '2026-05-05 08:10:38', 1, '2026-05-05 14:05:38'),
(68, 'vinc.atis.ui@phinmaed.com', 'login', '1bb9bafb60102eb30870d4f397713654dcebea090dc15c098401ade03adc5424', '2026-05-05 08:16:07', 1, '2026-05-05 14:11:07'),
(79, 'atisvincentcarl1@gmail.com', 'reset', '771e672bc447cfeb23a73579b373b29a3a85a22bc19a614ee340346843a55f92', '2026-05-23 07:45:42', 1, '2026-05-23 13:35:42'),
(82, 'atisvincentcarl1@gmail.com', 'login', '718a1e28e2cf314ba7913480373095110c4eaaa93eababdc5ebe55b5fd1a0f81', '2026-05-23 07:44:13', 1, '2026-05-23 13:39:13'),
(83, 'atisvincentcarl1@gmail.com', 'login', '61b9085f7da7e8c3cf12405e7ac64b9f6eb665ce8a567ad667dda72806949a4e', '2026-05-23 08:00:06', 1, '2026-05-23 13:55:06'),
(84, 'atisvincentcarl1@gmail.com', 'login', '3ae7d59e46795022eacb65c7c4b9590ef0162d599dab71b7bd710709035740a0', '2026-05-23 08:22:10', 1, '2026-05-23 14:17:10'),
(85, 'vinc.atis.ui@phinmaed.com', 'login', 'bcd83924c3e5dbd4508b1b9cfc32f44813cf5ec9583e50603a2587cf5b3482a5', '2026-05-23 08:23:25', 1, '2026-05-23 14:18:25'),
(86, 'vinc.atis.ui@phinmaed.com', 'login', '2870e11685a2b0acc7ed7a87e452565f39d2025e5bfee7cceb8bfbe60c491673', '2026-05-23 08:37:49', 1, '2026-05-23 14:32:49'),
(88, 'vinc.atis.ui@phinmaed.com', 'login', '95c3e1256a1c4b775fabfb2fa5c93b23b93804b887d15db55efb668f360ceca1', '2026-05-23 08:47:58', 1, '2026-05-23 14:42:58'),
(89, 'vinc.atis.ui@phinmaed.com', 'login', 'e6e0e5c63632f128b405bc1befe4248f0cfe57cc4bd7204210b5750b2f7a048a', '2026-05-23 08:51:24', 1, '2026-05-23 14:46:24'),
(97, 'atisvincentcarl1@gmail.com', 'login', '0f3ff22c794b1689024ba37e3eaf489e70098ad8509a667ba97a97d2915fcbbc', '2026-05-23 10:13:07', 1, '2026-05-23 16:08:07'),
(98, 'vinc.atis.ui@phinmaed.com', 'login', 'a15b643111dd6f9f7916cd429bbc4018deb96f82d8902047ca81519854aefdf8', '2026-05-23 10:18:11', 1, '2026-05-23 16:13:11'),
(99, 'vinc.atis.ui@phinmaed.com', 'login', 'd3e5bfe3cd618a63e86731e0692eb789771d1e3475786ebdb3eaf714aa55e386', '2026-05-24 17:58:20', 1, '2026-05-24 23:53:20'),
(104, 'vinc.atis.ui@phinmaed.com', 'login', '49de4ea1a94cde7f446e02347476fadb86f7166582f691c62fc6bd7730a7cf22', '2026-05-24 18:07:03', 1, '2026-05-25 00:02:03'),
(105, 'vinc.atis.ui@phinmaed.com', 'login', '5b9d66964cdbe4a8b9583ae8409989d061a94b031eecb93a89a00d11bc31804c', '2026-05-25 13:35:16', 1, '2026-05-25 19:30:16'),
(108, 'vinc.atis.ui@phinmaed.com', 'login', '9c79f86c1d1b626db195598483589b0da8c2e3d7399d59f5b937d55a47d43efe', '2026-05-25 15:05:12', 1, '2026-05-25 21:00:12'),
(116, 'vinc.atis.ui@phinmaed.com', 'login', '68364a65acda0e52f17589f2e8b682bbce853e848dd8e7fcced36d022bfb70b7', '2026-05-26 01:37:21', 1, '2026-05-26 07:32:21'),
(118, 'atisvincentcarl1@gmail.com', 'login', 'c465b2e23d064fcd1e3b404b417326b68fdb47bde41358ddf7b15451ed671910', '2026-05-26 14:21:11', 1, '2026-05-26 20:16:11'),
(119, 'atisvincentcarl1@gmail.com', 'login', '7e218102c1a3edfd8a009b9f89b062ba359fe222bfca70bbc1976b160986bb00', '2026-05-26 14:25:41', 1, '2026-05-26 20:20:41'),
(120, 'atisvincentcarl1@gmail.com', 'login', '9e0099213da4008038dfc16ba03c9cc493d49151438f56afa7e0a1f0dda7c5ce', '2026-05-26 14:28:14', 1, '2026-05-26 20:23:14'),
(122, 'vinc.atis.ui@phinmaed.com', 'login', 'b34f30997e1c4e9a29ab3914a2069c3f5139fd514b8360077bf03a3fa1557b9a', '2026-05-26 15:12:30', 1, '2026-05-26 21:07:30'),
(123, 'atisvincentcarl1@gmail.com', 'login', '2535926cb884a00afdb24c24b24827ca15ed261d7c1609a76a58aa2a1cfad3df', '2026-05-27 16:03:45', 1, '2026-05-27 21:58:45'),
(124, 'vinc.atis.ui@phinmaed.com', 'login', 'fbf8c25bb8222721e95b4e6a0340e439481e4c3ea26bf35cecbc1483912487fe', '2026-05-28 15:56:27', 1, '2026-05-28 21:51:27'),
(126, 'vinc.atis.ui@phinmaed.com', 'login', '24d5273e7c3104e21e42276fc63a2f2b9ec1ff9a80455883ef51ae5b6b8c6c07', '2026-05-28 19:17:52', 1, '2026-05-29 01:12:52'),
(128, 'vinc.atis.ui@phinmaed.com', 'login', '9b020904986bda46f73af14b4f09b7364e50abca55ae768a8d4426fa0157cd78', '2026-05-29 06:47:53', 1, '2026-05-29 12:42:53'),
(130, 'vinc.atis.ui@phinmaed.com', 'login', '33c56a1490144d53c13d7acad38ca1b0b41e539bb03b7a7da02c538a0f7924c5', '2026-05-29 08:39:51', 1, '2026-05-29 14:34:51'),
(131, 'vinc.atis.ui@phinmaed.com', 'login', '0ab3d10642805f19989884aa44a3a86154710bac588ff6f3a2bbb1275e9d49a7', '2026-05-29 16:12:05', 1, '2026-05-29 22:07:05'),
(132, 'atisvincentcarl1@gmail.com', 'login', 'ff4c44fda115ba6637dc269ce0e92cb9dd54d4c1f5f634ccbacc56226bbf6d07', '2026-05-29 16:51:20', 1, '2026-05-29 22:46:20'),
(134, 'vinc.atis.ui@phinmaed.com', 'login', '3b61b0f8d464d98fd3d2d3704c5a6868bfc3d6b15be621d8428ac8dc862b6f1f', '2026-05-29 16:55:01', 1, '2026-05-29 22:50:01'),
(135, 'vinc.atis.ui@phinmaed.com', 'login', '6efd368c7b15735ba847b5ba65b7ac88dde1e276983d5fce17115272e1a085cb', '2026-05-29 16:57:58', 1, '2026-05-29 22:52:58'),
(136, 'atisvincentcarl1@gmail.com', 'login', '87e3eda7873dcd9fe61f34868f3a11c634ceccaa0e4fb9588718ef47117d43d2', '2026-05-30 17:57:44', 1, '2026-05-30 23:52:44'),
(137, 'vinc.atis.ui@phinmaed.com', 'login', '14574bc62f912ccf6b08e9f69c3e98c04af617263ff50f2410e13bab0211a093', '2026-06-01 16:25:01', 1, '2026-06-01 22:20:01'),
(138, 'atisvincentcarl1@gmail.com', 'login', 'fc4904fc2aa6742df3f3cb2ac670b6cf68cfc5ceb8eb51a04183d9eabd45d06a', '2026-06-01 16:35:42', 1, '2026-06-01 22:30:42'),
(139, 'atisvincentcarl1@gmail.com', 'login', 'e5c73d153cac6cc7ad6a08ba16455a5eea720e789d4a3b63d8e3ce5ed1365bbb', '2026-06-02 01:22:06', 1, '2026-06-02 07:17:06'),
(140, 'atisvincentcarl1@gmail.com', 'login', '50f6c08ff7cbf4e068176cf73bf17100b2cee64d71aa8c981d11d34fc31916d0', '2026-06-05 06:16:41', 1, '2026-06-05 12:11:41'),
(141, 'atisvincentcarl1@gmail.com', 'login', '9c6d33d09b6e3ab25ea11279fcbff89a4e8e4e47166df3048432d6e3c36a3cbc', '2026-06-05 06:17:41', 1, '2026-06-05 12:12:41'),
(142, 'atisvincentcarl1@gmail.com', 'login', '30b55a51d13d1b48643bce5a3757d58cab48f28602940dd93a607d6746a131ac', '2026-06-05 08:28:10', 1, '2026-06-05 14:23:10'),
(143, 'atisvincentcarl1@gmail.com', 'login', '896921dfcd1f5915737da58e37c0e1f7e6510bf2ceab19ab14030e6242fd3d02', '2026-06-07 17:17:30', 1, '2026-06-07 23:12:30'),
(145, 'vinc.atis.ui@phinmaed.com', 'login', '1a0127094a1e390777ac813704ce367cc86579592619431d0cb256a414b417dd', '2026-06-07 17:30:10', 1, '2026-06-07 23:25:10'),
(146, 'vinc.atis.ui@phinmaed.com', 'login', '77ded22ee0108a6e289f8a891022573fcfc4567989d459c875977d7ea1aa4c15', '2026-06-07 17:46:29', 1, '2026-06-07 23:41:29'),
(147, 'vinc.atis.ui@phinmaed.com', 'login', 'c76226461e49aae2aaf48ac8506a00797c2b429d2ec997f7632954c8fb0a5f1f', '2026-06-07 18:12:20', 1, '2026-06-08 00:07:20'),
(150, 'atisvincentcarl1@gmail.com', 'login', '4b8a8d9714fa4e435e8508fdbe18d244a070dbcaed191994ece71c146d9583cb', '2026-06-07 18:16:25', 1, '2026-06-08 00:11:25'),
(151, 'atisvincentcarl1@gmail.com', 'login', '2f73fb6e513f8b0102f0b77cfce814f04dc0a824946bdc2513b882c32a571b1e', '2026-06-16 17:12:20', 1, '2026-06-16 23:07:20'),
(152, 'atisvincentcarl1@gmail.com', 'login', '7f989e7f9ea47bbfc31bb3c9f8bf016262b7345429cd22e57f165b7da14a1c32', '2026-06-30 15:57:16', 1, '2026-06-30 21:52:16'),
(153, 'atisvincentcarl1@gmail.com', 'login', '31613fdadc801c29c5826596af35aa5c162d953336d9cb2d655bc855ad766773', '2026-06-30 16:15:20', 1, '2026-06-30 22:10:20'),
(154, 'atisvincentcarl1@gmail.com', 'login', '37c9ed8fbef554ac5c9e88e8b7374a88b74fc097e2bc144701a67352096c0d81', '2026-06-30 16:28:22', 1, '2026-06-30 22:23:22');

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
  `product_type` enum('loose','member','package') DEFAULT 'loose',
  `image` varchar(255) DEFAULT 'images/product-placeholder.jpg',
  `stock` int(11) DEFAULT 100,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price`, `product_type`, `image`, `stock`, `created_at`) VALUES
(33, 4, 'Ardeur Relaxing Essential Oil', 'Ardeur Relaxing Essential Oil (Lavender). Create a warm, calm atmosphere perfect for sleeping and relaxation. 10ml Roller Ball Bottle. SRP ₱300.', 300.00, 'loose', 'images/products/82f5dbfafaaba2fdaa4bca575f434218.jpg', 150, '2026-03-24 03:15:28'),
(34, 4, 'Ardeur Refreshing Essential Oil', 'Ardeur Refreshing Essential Oil (Peppermint). The fresh scent of peppermint oil will wake up your senses each morning. 10ml Roller Ball Bottle. SRP ₱300.', 300.00, 'loose', 'images/products/f14419f7445345985b989f6e07dffe86.jpg', 148, '2026-03-24 03:15:28'),
(37, 3, 'Amazing Organic Green Barley Plus', 'Amazing Healthy Boosters — CLEANSE. Organic Green Barley Plus drink. Enriched with Aloe Vera Content. 10 Sachets x 11g. No Approved Therapeutic Claims.', 750.00, 'loose', 'images/products/6164ca5248224fa58154cf7771fac652.jpg', 87, '2026-03-24 03:15:28'),
(39, 3, 'Formal Attire', 'iaskbcaS', 750.00, 'loose', 'images/products/61e1926a55660b79f6b78f848d3b1060.jpg', 97, '2026-03-24 03:15:28'),
(60, NULL, 'clothes', 'bjh233', 200.00, 'loose', 'images/products/8584ec8715ad5be1a6c6443978b70c91.jpg', 100, '2026-06-07 16:13:22');

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
  `member_status` enum('member','non-member') DEFAULT 'non-member',
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `contact_number`, `address`, `profile_photo`, `member_status`, `role`, `created_at`) VALUES
(1, 'Admin AWMC', 'atisvincentcarl1@gmail.com', '$2y$10$HuaEqtacXKqWcOjLjRHTwOOZ.0BYiW.b6idkn39e3vzv1yRSblE82', '09123456789', 'AWMC Head Office, Philippines', NULL, 'member', 'admin', '2026-03-24 03:15:28'),
(14, 'Rodalen Dulalia Fernandez', 'rodu.fenandez.ui@phinmaed.com', '$2y$10$j5cmm6eXq/5V5XhK4w1EculDcaa2BNz4C.Nr5UKFLYuuYfNj7BPx.', '09511627316', NULL, NULL, 'non-member', 'customer', '2026-04-08 10:51:37'),
(15, 'Mary Kate Naral', 'mabo.naral.ui@phinmaed.com', '$2y$10$5qZsvq.MdD8y.x3WKVa1R.nWKxRD7xojZSS6ITDyHUmrQdWMv4vPq', '09501137991', NULL, NULL, 'non-member', 'customer', '2026-04-08 11:05:37'),
(16, 'Vincent Carl Atis', 'vinc.atis.ui@phinmaed.com', '$2y$10$05X1RX0NTy36ZRCswBk8Z.oBCzK0CXL2aO51TcyCb/sMXetWHrpJW', '09482841494', 'Brgy. Bagaygay Sara, Iloilo', 'uploads/profiles/user_16_1776923341.jpg', 'non-member', 'customer', '2026-04-23 05:38:37');

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
(1, 16, 'gcash', 'Vincent Carl Atis', '09482841494', '', 1, '2026-05-28 14:08:12');

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
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `otp_tokens`
--
ALTER TABLE `otp_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
