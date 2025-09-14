-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 05:11 PM
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
-- Database: `innovista`
--

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`, `created_at`, `is_read`) VALUES
(1, 'Michael Smith', 'msmith@email.com', 'Question about services', 'Do you offer services in the Kilinochchi area? I am looking to renovate my office space.', '2025-09-09 07:51:51', 0),
(2, 'Laura Wilson', 'laura.w@email.com', 'Urgent: Water Damage Restoration', 'I have urgent water damage in my home. Do you offer emergency services? Please contact me ASAP.', '2025-09-10 03:00:00', 0),
(3, 'James Taylor', 'jtaylor@email.com', 'Request for a specific material', 'Can your providers source Italian marble for a kitchen countertop? I am looking for a specific type.', '2025-09-08 10:30:00', 1),
(5, 'Chris Green', 'chris.g@email.com', 'Quotation follow-up', 'I submitted a quotation request a few days ago and haven\'t heard back yet. My reference is #INV-0005.', '2025-09-10 20:30:00', 0),
(6, 'Anna Johnson', 'anna.j@mail.com', 'Partnership Inquiry', 'We are a local furniture workshop interested in collaborating with your platform. Who should I speak to?', '2025-09-10 23:00:00', 0),
(7, 'Peter Jones', 'peter.j@mail.com', 'Feedback on recent project', 'The interior design service was excellent! Very happy with the outcome. Kudos to David Lee Designs.', '2025-09-11 02:30:00', 0),
(8, 'Sophia Brown', 'sophia.b@mail.com', 'Technical issue', 'I am having trouble uploading images to my portfolio. The upload seems to fail every time. Can you help?', '2025-09-11 03:45:00', 0),
(9, 'Liam Davis', 'liam.d@mail.com', 'Query about pricing', 'Could you provide more details on the pricing structure for restoration services?', '2025-09-11 05:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `custom_quotations`
--

CREATE TABLE `custom_quotations` (
  `id` int(11) NOT NULL,
  `quotation_id` varchar(50) DEFAULT NULL,
  `provider_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `advance` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `validity` int(11) DEFAULT NULL,
  `provider_notes` text DEFAULT NULL,
  `photos` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `project_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `custom_quotations`
--

INSERT INTO `custom_quotations` (`id`, `quotation_id`, `provider_id`, `customer_id`, `amount`, `advance`, `start_date`, `end_date`, `validity`, `provider_notes`, `photos`, `status`, `created_at`, `project_description`) VALUES
(2, '24', 19, 16, 100.00, 25.00, '2025-09-18', '2025-09-30', 30, 'advance before 10 days ', NULL, 'sent', '2025-09-13 14:49:41', NULL),
(3, '30', 19, 16, 300.00, 75.00, '2025-09-15', '2025-09-30', 30, 'uvyvyiuo', NULL, 'sent', '2025-09-14 17:11:53', 'gbhjgkj'),
(4, '31', 19, 16, 3333.00, 833.25, '2025-09-22', '2025-09-29', 45, 'bgnfg', NULL, 'sent', '2025-09-14 17:17:32', 'gbhjgkj'),
(5, '32', 19, 16, 2222.00, 555.50, '2025-09-24', '2025-10-09', 89, 'hhh', NULL, 'sent', '2025-09-14 17:23:40', 'dddd'),
(6, '33', 19, 16, 78888.00, 19722.00, '2025-09-23', '2025-09-30', 67, 'hjgvg', NULL, 'sent', '2025-09-14 17:28:00', 'dddd');

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `reported_by_id` int(11) NOT NULL COMMENT 'Usually the customer ID',
  `reported_against_id` int(11) NOT NULL COMMENT 'Usually the provider ID',
  `reason` text NOT NULL,
  `status` enum('open','under_review','resolved') NOT NULL DEFAULT 'open',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disputes`
--

INSERT INTO `disputes` (`id`, `quotation_id`, `reported_by_id`, `reported_against_id`, `reason`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 8, 11, 12, 'The provider installed the wrong tiles in the bathroom. They are a different color from what we agreed upon and they are refusing to correct it without extra charges.', 'resolved', 'hi', '2025-07-20 13:21:51', '2025-07-21 05:06:39');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('quotation','payment','project','dispute','system','general') DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL COMMENT 'ID of related entity (quotation, project, etc.)',
  `related_type` varchar(50) DEFAULT NULL COMMENT 'Type of related entity',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `priority`, `is_read`, `action_url`, `related_id`, `related_type`, `created_at`, `read_at`) VALUES
(2, 19, 'New Quotation Request', 'You have received a new quotation request for Interior Design,Painting,Restoration services.', 'quotation', 'high', 0, '../provider/manage_quotations.php', 28, 'quotation', '2025-09-14 10:08:00', NULL),
(3, 19, 'New Quotation Request', 'You have received a new quotation request for Interior Design services.', 'quotation', 'high', 0, '../provider/manage_quotations.php', 29, 'quotation', '2025-09-14 10:25:59', NULL),
(4, 19, 'New Quotation Request', 'You have received a new quotation request for Interior Design services.', 'quotation', 'high', 0, '../provider/manage_quotations.php', 30, 'quotation', '2025-09-14 11:27:07', NULL),
(5, 19, 'New Quotation Request', 'You have received a new quotation request for Interior Design services.', 'quotation', 'high', 0, '../provider/manage_quotations.php', 31, 'quotation', '2025-09-14 11:27:07', NULL),
(6, 16, 'New Quotation Received', 'You have received a new quotation from daniell company for Interior Design services. Amount: $300.00', 'quotation', 'high', 1, '../customer/view_quote.php?id=30', 30, 'quotation', '2025-09-14 11:41:53', '2025-09-14 11:43:27'),
(7, 16, 'New Quotation Received', 'You have received a new quotation from daniell company for Interior Design services. Amount: $3,333.00', 'quotation', 'high', 0, '../customer/view_quote.php?id=31', 31, 'quotation', '2025-09-14 11:47:32', NULL),
(8, 19, 'New Quotation Request', 'You have received a new quotation request for Interior Design services.', 'quotation', 'high', 0, '../provider/manage_quotations.php', 32, 'quotation', '2025-09-14 11:52:54', NULL),
(9, 19, 'New Quotation Request', 'You have received a new quotation request for Interior Design services.', 'quotation', 'high', 0, '../provider/manage_quotations.php', 33, 'quotation', '2025-09-14 11:52:54', NULL),
(10, 16, 'New Quotation Received', 'You have received a new quotation from daniell company for Interior Design services. Amount: $2,222.00', 'quotation', 'high', 0, '../customer/view_quote.php?id=32', 32, 'quotation', '2025-09-14 11:53:40', NULL),
(11, 16, 'New Quotation Received', 'You have received a new quotation from daniell company for Interior Design services. Amount: $78,888.00', 'quotation', 'high', 0, '../customer/view_quote.php?id=33', 33, 'quotation', '2025-09-14 11:58:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_type` enum('advance','final') NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `quotation_id`, `amount`, `payment_type`, `transaction_id`, `payment_date`) VALUES
(1, 1, 1875.00, 'advance', 'TRANS-ADV-1A2B3C', '2025-07-20 13:21:51'),
(2, 2, 550.00, 'advance', 'TRANS-ADV-4D5E6F', '2025-07-20 13:21:51'),
(3, 5, 3125.00, 'advance', 'TRANS-ADV-7G8H9I', '2025-07-20 13:21:51'),
(4, 5, 9375.00, 'final', 'TRANS-FIN-7G8H9I', '2025-07-20 13:21:51'),
(5, 8, 450.00, 'advance', 'TRANS-ADV-J1K2L3', '2025-07-20 13:21:51');

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_items`
--

CREATE TABLE `portfolio_items` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portfolio_items`
--

INSERT INTO `portfolio_items` (`id`, `provider_id`, `title`, `description`, `image_path`, `created_at`) VALUES
(1, 6, 'Minimalist Scandinavian Living Room', 'A complete overhaul focusing on clean lines, natural light, and functional furniture.', 'public/uploads/portfolio/sample1.jpg', '2025-07-20 13:21:51'),
(2, 6, 'Modern Kitchen Remodel', 'High-gloss cabinets, quartz countertops, and a smart lighting system.', 'public/uploads/portfolio/sample2.jpg', '2025-07-20 13:21:51'),
(3, 7, '19th Century Oak Wardrobe Restoration', 'Stripped, repaired, and refinished a family heirloom to its former glory.', 'public/uploads/portfolio/sample3.jpg', '2025-07-20 13:21:51'),
(4, 7, 'Victorian Terrace Facade Repair', 'Painstakingly repaired and repainted the exterior of a historic home.', 'public/uploads/portfolio/sample4.jpg', '2025-07-20 13:21:51'),
(5, 8, 'Luxury Hotel Lobby Design', 'Created a welcoming and luxurious space using marble, brass, and custom upholstery.', 'public/uploads/portfolio/sample5.jpg', '2025-07-20 13:21:51'),
(6, 12, 'Urban Loft Conversion', 'Transformed an industrial space into a chic, two-bedroom loft apartment.', 'public/uploads/portfolio/sample6.jpg', '2025-07-20 13:21:51'),
(7, 6, 'Cozy Home Office Setup', 'Designed a functional and inspiring workspace for a remote professional.', 'public/uploads/portfolio/sample7.jpg', '2025-07-20 13:21:51'),
(8, 7, 'Antique Chair Reupholstery', 'Brought a set of antique dining chairs back to life with new fabric and padding.', 'public/uploads/portfolio/sample8.jpg', '2025-07-20 13:21:51'),
(9, 8, 'Commercial Retail Space', 'Designed the interior for a new boutique, focusing on brand identity and customer flow.', 'public/uploads/portfolio/sample9.jpg', '2025-07-20 13:21:51'),
(10, 12, 'Patio and Outdoor Kitchen', 'Built a custom patio with an integrated outdoor kitchen and seating area.', 'public/uploads/portfolio/sample10.jpg', '2025-07-20 13:21:51');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `status` enum('awaiting_advance','in_progress','awaiting_final_payment','completed','disputed') NOT NULL DEFAULT 'awaiting_advance',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `quotation_id`, `status`, `start_date`, `end_date`) VALUES
(1, 1, 'in_progress', NULL, NULL),
(2, 2, 'awaiting_final_payment', NULL, NULL),
(3, 5, 'completed', NULL, NULL),
(4, 6, 'awaiting_advance', NULL, NULL),
(5, 8, 'disputed', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `project_updates`
--

CREATE TABLE `project_updates` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'ID of user who posted',
  `update_text` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_updates`
--

INSERT INTO `project_updates` (`id`, `project_id`, `user_id`, `update_text`, `image_path`, `created_at`) VALUES
(1, 1, 6, 'Great news, we have sourced the flooring and will begin installation on Monday!', NULL, '2025-07-20 13:21:51'),
(2, 1, 2, 'That sounds wonderful! Can\'t wait to see it.', NULL, '2025-07-20 13:21:51'),
(3, 1, 6, 'Here\'s a quick look at the progress today. The new paint color is up!', 'public/uploads/projects/livingroom_progress.jpg', '2025-07-20 13:21:51'),
(4, 2, 7, 'The furniture restoration is complete. The pieces look brand new! We are ready for final payment.', NULL, '2025-07-20 13:21:51'),
(5, 2, 3, 'Wow, that was fast! I will process the payment this evening.', NULL, '2025-07-20 13:21:51');

-- --------------------------------------------------------

--
-- Table structure for table `provider_availability`
--

CREATE TABLE `provider_availability` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `provider_name` varchar(100) DEFAULT NULL,
  `available_date` date NOT NULL,
  `available_time` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provider_availability`
--

INSERT INTO `provider_availability` (`id`, `provider_id`, `provider_name`, `available_date`, `available_time`, `created_at`) VALUES
(9, 19, 'daniel company', '2025-09-12', '12:30 PM', '2025-09-11 11:14:38'),
(10, 19, 'daniel company', '2025-09-12', '03:00 PM', '2025-09-11 11:14:38'),
(11, 19, 'daniell company', '2025-09-15', '10:30 AM', '2025-09-14 18:37:18'),
(12, 19, 'daniell company', '2025-09-15', '10:00 AM', '2025-09-14 18:37:18');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `subcategory` varchar(255) DEFAULT NULL,
  `project_description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `photos` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `customer_id`, `provider_id`, `service_type`, `subcategory`, `project_description`, `status`, `created_at`, `photos`) VALUES
(11, 16, 19, 'Interior Design', NULL, 'same as the image ', 'Awaiting Quote', '2025-09-04 13:20:36', NULL),
(12, 16, 19, 'Interior Design', NULL, 'i want the peacefull feeling attractive', 'Awaiting Quote', '2025-09-04 21:35:29', NULL),
(13, 16, 19, 'Interior Design,Painting,Restoration', NULL, 'done', 'Awaiting Quote', '2025-09-12 23:32:38', NULL),
(14, 16, 19, 'Interior Design,Painting,Restoration', NULL, 'ttyyygfv', 'Awaiting Quote', '2025-09-13 12:50:44', NULL),
(15, 16, 19, 'Interior Design,Painting,Restoration', NULL, 'like this ', 'Awaiting Quote', '2025-09-13 12:59:35', NULL),
(16, 16, 19, 'Interior Design,Painting,Restoration', NULL, 'f,vpofv,pr', 'Awaiting Quote', '2025-09-13 13:14:04', NULL),
(17, 16, 19, 'Interior Design,Painting,Restoration', 'Furniture Design', 'furnituere', 'Awaiting Quote', '2025-09-13 13:34:07', NULL),
(18, 16, 19, 'Interior Design,Painting,Restoration', 'Carpentry & Woodwork', 'carpentatory', 'Awaiting Quote', '2025-09-13 13:38:43', NULL),
(20, 16, 19, 'Interior Design,Painting,Restoration', 'Space Planning', 'hjf hdfio', 'Awaiting Quote', '2025-09-13 13:46:51', NULL),
(21, 16, 19, 'Interior Design,Painting,Restoration', 'Space Planning', 'dfd', 'Awaiting Quote', '2025-09-13 14:07:24', NULL),
(22, 16, 19, 'Interior Design,Painting,Restoration', 'Carpentry & Woodwork', 'dgvfdgv', 'Awaiting Quote', '2025-09-13 14:09:07', NULL),
(23, 16, 19, 'Interior Design', 'Carpentry & Woodwork', 'hb ', 'Awaiting Quote', '2025-09-13 14:26:10', NULL),
(24, 16, 19, 'Interior Design', '', 'fully', 'quote_sent', '2025-09-13 14:27:25', NULL),
(25, 16, 19, 'Interior Design', 'Space Planning', 'i need', 'Awaiting Quote', '2025-09-13 16:32:04', 'uploads/quotations/quote_68c54f2c55e3a.jpg'),
(27, 16, 19, 'Interior Design,Painting,Restoration', NULL, 'pravee', 'Awaiting Quote', '2025-09-14 15:30:44', NULL),
(28, 16, 19, 'Interior Design,Painting,Restoration', 'Ceiling & Lighting', 'jmnjjj', 'Awaiting Quote', '2025-09-14 15:38:00', 'uploads/quotations/quote_68c69400a34ab.jpg'),
(29, 16, 19, 'Interior Design', 'Ceiling & Lighting', 'gggg', 'Awaiting Quote', '2025-09-14 15:55:59', 'uploads/quotations/quote_68c698371ebd1.jpg'),
(30, 16, 19, 'Interior Design', 'Space Planning', 'gbhjgkj', 'Quoted', '2025-09-14 16:57:07', 'uploads/quotations/quote_68c6a68b70f98.jpg'),
(31, 16, 19, 'Interior Design', 'Space Planning', 'gbhjgkj', 'Quoted', '2025-09-14 16:57:07', 'uploads/quotations/quote_68c6a68b7a6e5.jpg'),
(32, 16, 19, 'Interior Design', 'Carpentry & Woodwork', 'dddd', 'Quoted', '2025-09-14 17:22:54', 'uploads/quotations/quote_68c6ac9693026.jpg'),
(33, 16, 19, 'Interior Design', 'Carpentry & Woodwork', 'dddd', 'Quoted', '2025-09-14 17:22:54', 'uploads/quotations/quote_68c6ac9698160.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `quotation_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `provider_name` varchar(100) DEFAULT NULL,
  `provider_email` varchar(100) DEFAULT NULL,
  `main_service` varchar(100) DEFAULT NULL,
  `subcategories` varchar(1000) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `provider_phone` varchar(20) DEFAULT NULL,
  `provider_address` varchar(255) DEFAULT NULL,
  `portfolio` text DEFAULT NULL,
  `provider_bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`id`, `provider_id`, `provider_name`, `provider_email`, `main_service`, `subcategories`, `created_at`, `provider_phone`, `provider_address`, `portfolio`, `provider_bio`) VALUES
(3, 19, 'daniell company', 'daniel@gmail.com', 'Interior Design,Painting,Restoration', 'Interior Design - Ceiling & Lighting,Interior Design - Space Planning,Interior Design - Modular Kitchen,Interior Design - Bathroom Design,Interior Design - Carpentry & Woodwork,Interior Design - Furniture Design,Painting - Interior Painting,Painting - Exterior Painting,Painting - Water & Damp Proofing,Painting - Commercial Painting,Painting - Wall Art & Murals,Painting - Color Consultation,Restoration - Wall Repairs & Plastering,Restoration - Floor Restoration,Restoration - Door & Window Repairs,Restoration - Old Space Transformation,Restoration - Furniture Restoration,Restoration - Full Building Renovation', '2025-09-04 13:12:32', '0764876571', '', 'Interior-Painting-Images-.jpg,Interior-Painting-Images-.jpg,Interior-Painting-Images-.jpg', 'your choices we will do perfect '),
(8, 26, 'Baba', 'baba@gmail.com', 'Interior Design,Painting,Restoration', 'Interior Design - Ceiling & Lighting,Interior Design - Modular Kitchen,Interior Design - Carpentry & Woodwork,Painting - Water & Damp Proofing,Painting - Commercial Painting,Restoration - Old Space Transformation,Restoration - Furniture Restoration', '2025-09-12 22:01:39', '0778484877', 'Ithikandal adampan mannar', '', 'naa summa open panranda');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('homepage_about_text', 'Innovista is a premier platform connecting skilled interior designers and restoration experts with clients seeking quality and reliability. Our mission is to simplify the process of creating beautiful spaces.'),
('homepage_welcome_message', 'Welcome to Innovista! Your one-stop solution for interior design and restoration services.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer','provider') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `provider_status` enum('pending','approved','rejected') DEFAULT NULL,
  `credentials_verified` enum('yes','no') NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `portfolio` text DEFAULT NULL,
  `profile_image_path` varchar(255) NOT NULL DEFAULT 'assets/images/default-avatar.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `provider_status`, `credentials_verified`, `created_at`, `phone`, `address`, `bio`, `portfolio`, `profile_image_path`) VALUES
(1, 'Admin Innovista', 'admin@innovista.com', '$2y$10$zH3LtsmNLEaDkDpholkXL.L5g0LFa9li77CvVT56xj8niU.A.dFUC', 'admin', 'active', NULL, 'no', '2025-07-20 13:21:51', NULL, NULL, NULL, NULL, 'assets/images/default-avatar.jpg'),
(2, 'Alice Johnson', 'customer@test.com', '$2y$10$EiemzC30iYJ3/Ld259QxIeSgoYJOSAb51Iq8mFhEblCGeLg43qQo.', 'customer', 'active', NULL, 'no', '2025-07-20 13:21:51', NULL, NULL, NULL, NULL, 'assets/images/default-avatar.jpg'),
(3, 'Bob Williams', 'bob.w@example.com', '$2y$10$EiemzC30iYJ3/Ld259QxIeSgoYJOSAb51Iq8mFhEblCGeLg43qQo.', 'customer', 'active', NULL, 'no', '2025-07-20 13:21:51', NULL, NULL, NULL, NULL, 'assets/images/default-avatar.jpg'),
(4, 'Charlie Brown', 'charlie.b@example.com', '$2y$10$EiemzC30iYJ3/Ld259QxIeSgoYJOSAb51Iq8mFhEblCGeLg43qQo.', 'customer', 'inactive', NULL, 'no', '2025-07-20 13:21:51', NULL, NULL, NULL, NULL, 'assets/images/default-avatar.jpg'),
(5, 'Diana Miller', 'diana.m@example.com', '$2y$10$EiemzC30iYJ3/Ld259QxIeSgoYJOSAb51Iq8mFhEblCGeLg43qQo.', 'customer', 'active', NULL, 'no', '2025-07-20 13:21:51', NULL, NULL, NULL, NULL, 'assets/images/default-avatar.jpg'),
(13, 'jps', 'admin16@innovista.com', '$2y$10$e.rWG.qQIk5zESXKe3RH8eoVCAvnREGMhQSLqRuVMj8XLeVe.qXO.', 'customer', 'active', NULL, 'no', '2025-07-20 13:23:38', NULL, NULL, NULL, NULL, 'assets/images/default-avatar.jpg'),
(14, 'kisho', 'kishojeyapragash@gmail.com', '$2y$10$m1o8wfKIEJqa/TShindsTO2MZk3g9z6/FuHh./y9./bNboLP0MwYW', 'customer', 'active', NULL, 'no', '2025-07-20 13:25:52', NULL, NULL, NULL, NULL, 'assets/images/default-avatar.jpg'),
(16, 'kristo praveejiny', 'kristokristo323@gmail.com', '$2y$10$L4Ewexb5DZE6XZHMvpetYOL2.yI1W6m6FTopzC/z0d1odYiGbtQLG', 'customer', 'active', NULL, 'no', '2025-08-28 12:19:37', NULL, NULL, NULL, NULL, 'assets/images/default-avatar.jpg'),
(19, 'daniell company', 'daniel@gmail.com', '$2y$10$7Zzxs0loytLXKss4NfDhPeCDuA..XsVAWrzmtnkYukg7ZbZ17IckW', 'provider', 'active', 'approved', 'no', '2025-09-04 07:42:32', '', '', '', 'Interior-Painting-Images-.jpg,Interior-Painting-Images-.jpg,Interior-Painting-Images-.jpg', 'assets/images/default-avatar.jpg'),
(20, 'kiskintharam', 'ram@gmail.com', '$2y$10$TUUxZ2WW/bET9.yQ1pBULORqYolYC/WCxk9ScVa0X/ureA1W1MLUS', 'customer', 'active', NULL, 'no', '2025-09-10 12:06:28', '0764554322', 'jaffna', '', NULL, 'uploads/profiles/user_20_68c43578c1e8f.jpg'),
(26, 'Baba', 'baba@gmail.com', '$2y$10$FB3BNYxiejYycvWnyf0NWugQcH4LjEnbFbJsCmzwoIrS/jat/WGI.', 'provider', 'inactive', 'rejected', 'no', '2025-09-12 16:31:39', '0778484877', 'Ithikandal adampan mannar', 'naa summa open panranda', '', 'assets/images/default-avatar.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_quotations`
--
ALTER TABLE `custom_quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `reported_by_id` (`reported_by_id`),
  ADD KEY `reported_against_id` (`reported_against_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `type` (`type`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `project_updates`
--
ALTER TABLE `project_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `provider_availability`
--
ALTER TABLE `provider_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `custom_quotations`
--
ALTER TABLE `custom_quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `project_updates`
--
ALTER TABLE `project_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `provider_availability`
--
ALTER TABLE `provider_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `custom_quotations`
--
ALTER TABLE `custom_quotations`
  ADD CONSTRAINT `custom_quotations_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `custom_quotations_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
