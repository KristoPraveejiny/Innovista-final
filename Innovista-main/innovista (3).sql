-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Sep 13, 2025 at 03:02 PM
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
-- Database: `innovista`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('quotation','payment','project','dispute','system','general') DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL COMMENT 'ID of related entity (quotation, project, etc.)',
  `related_type` varchar(50) DEFAULT NULL COMMENT 'Type of related entity',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'abcd jp', 'abcd@gmail.com', 'Query about my Interior Design request', 'I submitted a request for apartment living room design. Has a provider been assigned yet?', '2025-09-13 08:30:00', 0);

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
(1, '3', 5, 16, 65000.00, 16250.00, '2025-09-25', '2025-09-30', 20, 'Table will be fully sanded, repaired, and re-varnished with a durable finish. Client to approve color samples.', NULL, 'sent', '2025-09-13 09:00:00', 'Vintage wooden dining table restoration.'),
(2, '1', 7, 16, 80000.00, 20000.00, '2025-09-18', '2025-09-22', 25, 'High-quality interior paint used. Includes minor wall patching and two coats of paint. Schedule is flexible.', NULL, 'approved', '2025-09-13 10:30:00', 'Office space interior painting.');

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
(1, 2, 16, 7, 'Minor delay in project start date due to unexpected paint delivery issues.', 'resolved', 'Provider communicated delay and offered a small discount. Customer accepted.', '2025-09-17 04:30:00', '2025-09-18 06:30:00');

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
(1, 2, 20000.00, 'advance', 'PYM-AJ-OFF-ADV-001', '2025-09-14 05:30:00'),
(2, 2, 60000.00, 'final', 'PYM-AJ-OFF-FIN-001', '2025-09-23 09:30:00'),
(3, 1, 16250.00, 'advance', 'PYM-AJ-TAB-ADV-001', '2025-09-20 04:30:00');

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
(1, 5, 'Minimalist Scandinavian Living Room', 'A complete overhaul focusing on clean lines, natural light, and functional furniture.', 'https://images.unsplash.com/photo-1615873968403-f0ed14e4142f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHw3fHNhbmRpbmF2aWFuJTIwbGl2aW5nJTIwcm9vbXxlbnwwfHx8fDE3MjU4ODY1NjV8MA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-04 03:40:00'),
(2, 5, 'Modern Kitchen Remodel', 'High-gloss cabinets, quartz countertops, and a smart lighting system.', 'https://images.unsplash.com/photo-1595493014138-0382d6199f7d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHw3fG1vZGVybiUyMGtpdGNoZW4lMjByZW1vZGVsJmZufDB8MHx8fDE3MjU4ODY1NjZ8MA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-04 03:45:00'),
(3, 6, '19th Century Oak Wardrobe Restoration', 'Stripped, repaired, and refinished a family heirloom to its former glory.', 'https://images.unsplash.com/photo-1606787994801-44754a614051?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHwxNXxmdXJuaXR1cmUlMjByZXN0b3JhdGlvbnxlbnwwfHx8fDE3MjU4ODY1Njl8MA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-05 05:10:00'),
(4, 6, 'Victorian Terrace Facade Repair', 'Painstakingly repaired and repainted the exterior of a historic home.', 'https://images.unsplash.com/photo-1569429598284-88481358b534?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHw0fG9sZCUyMGhvdXNlJTIwcmVzdG9yYXRpb258ZW52MHwxfHx8fDE3MjU4ODY1NzB8MA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-05 05:15:00'),
(5, 7, 'Luxury Hotel Lobby Design', 'Created a welcoming and luxurious space using marble, brass, and custom upholstery.', 'https://images.unsplash.com/photo-1579621970795-87fbb2f71617?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHw3fGhvdGVsJTIwbG9iYnl8ZW58MHx8fHwxNzI1ODg2NTc0fDA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-06 07:40:00'),
(6, 7, 'Urban Loft Conversion', 'Transformed an industrial space into a chic, two-bedroom loft apartment.', 'https://images.unsplash.com/photo-1582268482024-5d9c7d81249e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHwxOHxsb2Z0JTIwY29udmVyc2lvbnxlbnwwfHx8fDE3MjU4ODY1NzV8MA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-06 07:45:00'),
(7, 5, 'Cozy Home Office Setup', 'Designed a functional and inspiring workspace for a remote professional.', 'https://images.unsplash.com/photo-1596541223963-7c38520268a7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHw3fGhvbWUlMjBvZmZpY2V8ZW58MHx8fHwxNzI1ODg2NzQxfDA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-07 04:30:00'),
(8, 6, 'Modern Farmhouse Kitchen', 'Blends rustic charm with modern amenities, featuring custom cabinetry and a large island.', 'https://images.unsplash.com/photo-1580173663702-f19b5f93b5d1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHwxNHxraXRjaGVuJTIwcmVtb2RlbHxlbnwwfHx8fDE3MjU4ODY1NzV8MA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-08 05:30:00'),
(9, 5, 'Luxury Bathroom Remodel', 'Spa-like atmosphere with marble finishes, freestanding tub, and smart lighting.', 'https://images.unsplash.com/photo-1580879483863-74b8c9d0f3c5?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHw0fGx1eHVyeSUyMGJhdGhyb29tfGVufDB8fHx8MTcyNTg4Njg1NXww&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-09 08:30:00');

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
(1, 2, 'in_progress', '2025-09-18', '2025-09-22'),
(2, 1, 'completed', '2025-09-25', '2025-09-30');

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
(1, 1, 7, 'Initiated office repainting. Walls prepped and first coat applied.', 'https://images.unsplash.com/photo-1596541223963-7c38520268a7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHwxMHxwYWludGluZyUyMGhvdXNlfGVufDB8fHx8MTcyNTg4Njc0N3ww&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-19 03:30:00'),
(2, 1, 16, 'Looking good so far! Thanks for the update.', NULL, '2025-09-19 05:30:00'),
(3, 1, 7, 'Second coat applied. Project progressing smoothly. Final inspection tomorrow.', 'https://images.unsplash.com/photo-1582046487920-5c7a40f11d9d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTQ5ODh8MHwxfHNlYXJjaHwzfHdlbGwlMjBwYWludGluZ3xlbnwwfHx8fDE3MjU4ODY3NDd8MA&ixlib=rb-4.0.3&q=80&w=1080', '2025-09-21 08:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `provider_availability`
--

CREATE TABLE `provider_availability` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `provider_name` varchar(100) DEFAULT NULL,
  `available_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `project_description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `photos` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `customer_id`, `provider_id`, `service_type`, `project_description`, `status`, `created_at`, `photos`) VALUES
(1, 16, 7, 'Painting', 'Looking for an estimate to repaint my office space. Approx 800 sq ft, modern grey theme. No specific images to attach.', 'Awaiting Quote', '2025-09-12 10:00:00', NULL),
(2, 16, NULL, 'Interior Design', 'Seeking creative ideas for a small apartment living room refresh. Needs to be cozy and budget-friendly. Photos attached of current space.', 'Awaiting Provider Assignment', '2025-09-12 11:30:00', 'uploads/quotation_attachments/quote_attach_16_livingroom1.jpg,uploads/quotation_attachments/quote_attach_16_livingroom2.jpg'),
(3, 16, 5, 'Restoration', 'I have a vintage wooden dining table that needs to be restored. Minor scratches and fading.', 'Awaiting Quote', '2025-09-12 14:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `customer_id`, `provider_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 16, 5, 4, 'David Lee Designs did a decent job restoring my dining table. It looks much better, though it took a little longer than expected.', '2025-10-05 04:30:00');

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
  `subcategories` varchar(255) DEFAULT NULL,
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
(1, 5, 'David Lee Designs', 'david.lee@innovista.com', 'Interior Design', 'Ceiling & Lighting, Space Planning, Bathroom Design, Carpentry & Woodwork', '2025-09-04 03:35:00', '0774567890', '404 Art Gallery St, Jaffna', 'Minimalist Scandinavian Living Room, Modern Kitchen Remodel', 'Specializing in modern and minimalist interior transformations.'),
(2, 6, 'Maria G. Restorations', 'maria.g@innovista.com', 'Restoration', 'Furniture Restoration, Floor Restoration, Door & Window Repairs, Art & Sculpture Repair', '2025-09-05 05:05:00', '0775678901', '505 Heritage Lane, Mannar', '19th Century Oak Wardrobe Restoration, Victorian Terrace Facade Repair', 'Expert in antique furniture and architectural restoration, bringing old pieces back to life with care.'),
(3, 7, 'Robert J. Paints', 'robert.j@innovista.com', 'Painting', 'Interior Painting, Exterior Painting, Water & Damp Proofing, Murals & Decorative Finishes', '2025-09-06 07:35:00', '0776789012', '606 Colourful Rd, Trincomalee', 'Luxury Hotel Lobby Design, Urban Loft Conversion', 'Professional painting services for homes and commercial spaces, ensuring a flawless finish every time.'),
(5, 15, 'kj', 'j@gmail.com', 'Interior Design', 'Interior Design - Ceiling &amp; Lighting,Interior Design - Space Planning,Interior Design - Modular Kitchen,Interior Design - Furniture Design', '2025-09-11 15:56:10', '0773186706', 'batticaloa', '', 'ggjh'),
(6, 16, 'abcd', 'abcd@gmail.com', 'Restoration', 'Restoration - Wall Repairs &amp; Plastering,Restoration - Furniture Restoration,Restoration - Full Building Renovation', '2025-09-11 18:58:24', '0771234567', 'Passara, Badulla', '', 'jpppp');

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
('admin_contact_email', 'contact@innovista.com'),
('facebook_url', 'https://facebook.com/innovista'),
('homepage_about_text', 'Innovista is a premier platform connecting skilled interior designers and restoration experts with clients seeking quality and reliability. Our mission is to simplify the process of creating beautiful spaces.'),
('homepage_cta_description', 'Whether you&#039;re looking to transform your home or grow your service business, the Innovista community is here for you. Join today for a seamless, transparent, and trustworthy experience.'),
('homepage_cta_title', 'Ready to Start Your Next Project?'),
('homepage_faq_title', 'Frequently Asked Questions'),
('homepage_hero_h1', 'Transforming Spaces, Restoring Dreams'),
('homepage_hero_p', 'Your one-stop platform for interior design, painting, and restoration services in the Northern Province'),
('homepage_how_it_works_title', 'How It Works'),
('homepage_our_work_description', 'A glimpse into the spaces we&#039;ve transformed, showcasing our best projects and diverse expertise.'),
('homepage_our_work_title', 'Our Recent Work'),
('homepage_products_description', 'Find high-quality products from trusted brands, all in one place. From paints to furniture, get everything you need for your project delivered.'),
('homepage_products_title', 'Complete Your Project'),
('homepage_services_title', 'Our Core Services'),
('homepage_testimonials_title', 'What Our Clients Say'),
('homepage_welcome_message', 'Welcome to Innovista! Your one-stop solution for interior design and restoration services.'),
('homepage_why_choose_us_title', 'Why Choose Innovista?'),
('instagram_url', 'https://instagram.com/innovista'),
('platform_address', '123 Design Lane, Jaffna, Sri Lanka'),
('platform_name', 'Innovista');

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
  `profile_image_path` varchar(255) DEFAULT 'assets/images/default-avatar.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `provider_status`, `credentials_verified`, `created_at`, `phone`, `address`, `bio`, `portfolio`, `profile_image_path`) VALUES
(1, 'Admin Innovista', 'admin@innovista.com', '$2y$10$kMqZKf5swz5R.kOcN/WMVuDeXuHtGtur3dGTRdESClIBo2MjCDf66', 'admin', 'active', NULL, 'no', '2025-07-20 07:51:51', '', '', 'Lead Administrator for Innovista platform.', NULL, 'uploads/profiles/user_1_68c25f69195d9.jpg'),
(4, 'Denu Jesunesan', 'denujesunesan09@gmail.com', '$2y$10$q9.r1.s3.t5.u7.v0.w2.x4.y6.z8.a0.b2.c4.d6.', 'admin', 'active', NULL, 'no', '2025-09-03 08:45:00', '0773456789', '303 Hilltop Rd, Kilinochchi', '', NULL, 'uploads/profiles/user_4_68c25b6a73ab4.jpg'),
(5, 'David Lee Designs', 'david.lee@innovista.com', '$2y$10$c7.d9.e1.f3.g5.h8.i0.j2.k4.l6.m8.n0.o3.p5.', 'provider', 'active', 'approved', 'yes', '2025-09-04 03:30:00', '0774567890', '404 Art Gallery St, Jaffna', 'Specializing in modern and minimalist interior transformations.', '[]', 'uploads/profiles/provider_5_68c25d7463da1.jpg'),
(6, 'Maria G. Restorations', 'maria.g@innovista.com', '$2y$10$f0.g2.h4.i6.j8.k1.l3.m5.n7.o9.p1.q3.r5.s7.', 'provider', 'active', 'approved', 'yes', '2025-09-05 05:00:00', '0775678901', '505 Heritage Lane, Mannar', 'Expert in antique furniture and architectural restoration, bringing old pieces back to life with care.', '[]', 'uploads/profiles/provider_6_68c25d6664ead.jpg'),
(7, 'Denu Jesunesan', 'denujesu09@gmail.com', '$2y$10$k3.l5.m7.n9.o1.p3.q5.r7.s9.t2.u4.v6.w8.x0.', 'provider', 'active', 'approved', 'yes', '2025-09-06 07:30:00', '0776789012', '606 Colourful Rd, Trincomalee', 'Professional painting services for homes and commercial spaces, ensuring a flawless finish every time.', '[]', 'uploads/profiles/provider_7_68c25d4809a3a.jpg'),
(8, 'Sarah Chen', 'sarah.c@example.com', '$2y$10$e4.f6.g8.h0.i2.j4.k6.l8.m0.n3.o5.p7.q9.r1.', 'customer', 'active', NULL, 'no', '2025-09-07 10:15:00', '0777890123', '707 Sunshine Apt, Batticaloa', '', NULL, 'uploads/profiles/user_8_68c25b42d81f0.jpg'),
(9, 'Michael Green', 'michael.g@example.com', '$2y$10$v2.w4.x6.y8.z0.a3.b5.c7.d9.e1.f3.g5.h7.i9.', 'customer', 'active', NULL, 'no', '2025-09-08 12:00:00', '0778901234', '808 Palm St, Galle', '', NULL, 'uploads/profiles/user_9_68c25b33753cb.jpg'),
(10, 'kisho jeyapragash', 'kishojeyapragash@gmail.com', '$2y$10$8.g51JZMrISZf53iKzZs3ODnGtU1oTd5eEVFS7oC4yYK2CjzM.hZ2', 'provider', 'active', 'approved', 'yes', '2025-09-11 05:36:44', '0773186706', 'batticaloa', 'hello', NULL, 'uploads/profiles/user_10_68c289076c104.png'),
(15, 'kj', 'j@gmail.com', '$2y$10$EBWBiy.KdrpZqElQA4SNJeUx02KoCbuvxgl2.r6WX5xO1TFfMRHOG', 'provider', 'active', 'approved', 'yes', '2025-09-11 10:26:10', '0773186706', 'batticaloa', 'ggjh', '', 'uploads/profiles/user_15_68c2c80c96e01.jpg'),
(16, 'abcd jp', 'abcd@gmail.com', '$2y$10$Z1NlADoLlsSPpUUzOEjGpeRcAAkmhlcKy6czJBhoTcKbdLptnieae', 'customer', 'active', NULL, 'no', '2025-09-11 13:28:24', '0771234567', 'Passara, Badulla', 'jpppp', '', 'uploads/profiles/profile_16_68c56b225ad0e.jpg');

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
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_quotations`
--
ALTER TABLE `custom_quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `project_updates`
--
ALTER TABLE `project_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `provider_availability`
--
ALTER TABLE `provider_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service`
--
ALTER TABLE `service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `custom_quotations`
--
ALTER TABLE `custom_quotations`
  ADD CONSTRAINT `custom_quotations_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `custom_quotations_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
