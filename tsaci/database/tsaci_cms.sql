-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 02, 2025 at 04:54 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tsaci_cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_content`
--

CREATE TABLE `about_content` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_content`
--

INSERT INTO `about_content` (`id`, `section_name`, `title`, `content`, `image_url`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'page_header_title', 'About Tupi Supreme', NULL, NULL, 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'page_header_subtitle', NULL, 'Leading the way in activated carbon solutions for a sustainable future', NULL, 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'company_story_title', 'Our Story', NULL, NULL, 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'company_story_content', '', '<p>Founded in 2019, Tupi Supreme Activated Carbon, Inc. began with a simple mission: to provide the highest quality activated carbon solutions while protecting our environment. What started as a small family business has grown into one of the most trusted names in the activated carbon industry. Our commitment to innovation, quality, and environmental responsibility has remained constant throughout our journey. Today, we serve clients across multiple industries, from municipal water treatment facilities to pharmaceutical companies, helping them achieve their environmental goals with our premium activated carbon products.</p>\r\n', '', 4, 1, '2025-12-02 13:59:41', '2025-12-02 15:28:23', 1),
(5, 'mission_title', 'Our Mission', NULL, NULL, 6, 1, '2025-12-02 13:59:41', '2025-12-02 15:22:10', NULL),
(6, 'mission_content', NULL, 'To provide premium activated carbon solutions for municipal water treatment facilities and industrial applications, delivering the highest quality 2mm granulated activated carbon while contributing to a sustainable future for generations to come.', NULL, 7, 1, '2025-12-02 13:59:41', '2025-12-02 15:22:10', NULL),
(7, 'vision_title', 'Our Vision', NULL, NULL, 9, 1, '2025-12-02 13:59:41', '2025-12-02 15:22:10', NULL),
(8, 'vision_content', NULL, 'To be the recognized leader in municipal water treatment activated carbon solutions, trusted by water treatment facilities nationwide for our commitment to quality, environmental stewardship, and technical excellence.', NULL, 10, 1, '2025-12-02 13:59:41', '2025-12-02 15:22:10', NULL),
(25, 'timeline_title', 'Our Journey', NULL, NULL, 12, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL),
(26, 'timeline_subtitle', NULL, 'Milestones that shaped our company\'s growth and success', NULL, 13, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL),
(27, 'values_title', 'Our Core Values', NULL, NULL, 14, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL),
(28, 'values_subtitle', NULL, 'The principles that guide everything we do', NULL, 15, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL),
(29, 'team_title', 'Our Leadership Team', NULL, NULL, 16, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL),
(30, 'team_subtitle', NULL, 'Meet the experienced professionals driving our success', NULL, 17, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL),
(31, 'company_story_icon', NULL, 'fas fa-industry', NULL, 5, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL),
(32, 'mission_icon', NULL, 'fas fa-bullseye', NULL, 8, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL),
(33, 'vision_icon', NULL, 'fas fa-eye', NULL, 11, 1, '2025-12-02 14:15:59', '2025-12-02 15:22:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'admin_users', 1, '0', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 13:55:19'),
(2, 1, 'logout', 'admin_users', 1, '0', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 13:55:56'),
(3, 1, 'login', 'admin_users', 1, '0', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 14:16:03'),
(4, 1, 'update', 'about_content', 4, '0', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 15:28:23');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','editor') DEFAULT 'editor',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@tsaci.com', '$2y$10$KEQQ/1/wFMNNxVwBZUBo5ezaqdnOc14F0OseXbdHU6FstZakku0y2', 'Administrator', 'super_admin', 1, '2025-12-02 22:16:03', '2025-12-02 13:49:17', '2025-12-02 14:16:03');

-- --------------------------------------------------------

--
-- Table structure for table `carousel_slides`
--

CREATE TABLE `carousel_slides` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_link` varchar(200) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carousel_slides`
--

INSERT INTO `carousel_slides` (`id`, `title`, `description`, `image_url`, `button_text`, `button_link`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Premium Activated Carbon Solutions', 'Leading provider of high-quality activated carbon products for municipal water treatment facilities', '/images/hero1.jpg', 'Request Technical Consultation', 'contact.php', 1, 1, '2025-12-02 14:12:49', '2025-12-02 14:12:49', NULL),
(2, 'Municipal Water Treatment Excellence', 'Trusted by 200+ municipal water treatment facilities nationwide', '/images/hero2.jpg', 'View Our Products', 'products.php', 2, 1, '2025-12-02 14:12:49', '2025-12-02 14:12:49', NULL),
(3, 'Sustainable Environmental Solutions', 'Committed to environmental responsibility and sustainable business practices', '/images/hero3.jpg', 'Learn More', 'about.php', 3, 1, '2025-12-02 14:12:49', '2025-12-02 14:12:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `case_studies`
--

CREATE TABLE `case_studies` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `challenge` text DEFAULT NULL,
  `solution` text DEFAULT NULL,
  `results` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `case_studies`
--

INSERT INTO `case_studies` (`id`, `title`, `slug`, `client_name`, `location`, `industry`, `challenge`, `solution`, `results`, `image_url`, `display_order`, `is_featured`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Metropolitan City Water Plant - 30% Efficiency Improvement', 'metropolitan-city-water-plant', 'Metropolitan City Water Plant', 'Major Metropolitan Area', 'Municipal Water Treatment', 'The facility was experiencing inconsistent water quality, high operational costs, and frequent filter replacements. They needed a reliable activated carbon solution that could handle high flow rates while maintaining consistent performance.', 'We provided our premium 2mm granulated activated carbon (GAC) with strict quality specifications. Our technical team conducted a comprehensive analysis and designed a custom filtration system optimized for their specific water chemistry and flow requirements.', '30% improvement in filtration efficiency\n$2.5 million in annual operational cost savings\nExtended filter life by 40%\n100% compliance with water quality standards\nReduced maintenance downtime by 50%', NULL, 1, 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'Regional Water Authority - Cost Reduction Success', 'regional-water-authority', 'Regional Water Authority', 'Regional Area', 'Municipal Water Treatment', 'Facing budget constraints and increasing operational costs, the water authority needed to reduce expenses while maintaining water quality standards.', 'We implemented our cost-effective 2mm granulated activated carbon solution with optimized replacement schedules and bulk purchasing arrangements.', '25% reduction in operational costs\nMaintained 100% water quality compliance\nExtended filter replacement intervals\nImproved system reliability', NULL, 2, 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `certifications`
--

CREATE TABLE `certifications` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `issuing_organization` varchar(200) DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `document_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certifications`
--

INSERT INTO `certifications` (`id`, `title`, `issuing_organization`, `certificate_number`, `issue_date`, `expiry_date`, `description`, `image_url`, `document_url`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'ISO 9001:2015', 'International Organization for Standardization', 'ISO-9001-2015', '2010-01-01', NULL, 'Quality Management Systems - Certified since 2010, demonstrating our commitment to consistent quality management and continuous improvement in all our operations.', NULL, NULL, 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'ISO 14001:2015', 'International Organization for Standardization', 'ISO-14001-2015', '2015-01-01', NULL, 'Environmental Management Systems - Certification demonstrating our commitment to environmental responsibility and sustainable operations in activated carbon production.', NULL, NULL, 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'ISO 9001:2015', 'International Organization for Standardization', 'ISO-9001-2015', '2010-01-01', NULL, 'Quality Management Systems - Certified since 2010, demonstrating our commitment to consistent quality management and continuous improvement in all our operations.', NULL, NULL, 1, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(4, 'ISO 14001:2015', 'International Organization for Standardization', 'ISO-14001-2015', '2015-01-01', NULL, 'Environmental Management Systems - Certification demonstrating our commitment to environmental responsibility and sustainable operations in activated carbon production.', NULL, NULL, 2, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(5, 'ISO 9001:2015', 'International Organization for Standardization', 'ISO-9001-2015', '2010-01-01', NULL, 'Quality Management Systems - Certified since 2010, demonstrating our commitment to consistent quality management and continuous improvement in all our operations.', NULL, NULL, 1, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(6, 'ISO 14001:2015', 'International Organization for Standardization', 'ISO-14001-2015', '2015-01-01', NULL, 'Environmental Management Systems - Certification demonstrating our commitment to environmental responsibility and sustainable operations in activated carbon production.', NULL, NULL, 2, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `company_values`
--

CREATE TABLE `company_values` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_values`
--

INSERT INTO `company_values` (`id`, `title`, `description`, `icon`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Quality First', 'We never compromise on quality. Every product meets the highest industry standards.', 'fas fa-check-circle', 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'Environmental Responsibility', 'Committed to sustainable practices and zero-waste operations.', 'fas fa-leaf', 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'Customer Focus', 'Your success is our success. We work closely with clients to meet their specific needs.', 'fas fa-users', 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'Innovation', 'Continuously improving our processes and products through research and development.', 'fas fa-lightbulb', 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(5, 'Sustainability', 'We are committed to environmental responsibility and sustainable business practices in all our operations.', 'fas fa-leaf', 1, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(6, 'Quality', 'We maintain strict quality standards with our 2mm granulated activated carbon specification, ensuring consistent excellence for municipal water treatment facilities.', 'fas fa-award', 2, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(7, 'Innovation', 'We continuously invest in research and development to create cutting-edge solutions for our clients.', 'fas fa-lightbulb', 3, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(8, 'Integrity', 'We conduct business with honesty, transparency, and ethical practices in all our relationships.', 'fas fa-handshake', 4, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(9, 'Customer Focus', 'We specialize in serving municipal water treatment facilities with dedicated expertise, technical support, and solutions tailored to their unique requirements.', 'fas fa-users', 5, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(10, 'Global Impact', 'We work to make a positive impact on the environment and communities worldwide.', 'fas fa-globe', 6, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(11, 'Sustainability', 'We are committed to environmental responsibility and sustainable business practices in all our operations.', 'fas fa-leaf', 1, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(12, 'Quality', 'We maintain strict quality standards with our 2mm granulated activated carbon specification, ensuring consistent excellence for municipal water treatment facilities.', 'fas fa-award', 2, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(13, 'Innovation', 'We continuously invest in research and development to create cutting-edge solutions for our clients.', 'fas fa-lightbulb', 3, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(14, 'Integrity', 'We conduct business with honesty, transparency, and ethical practices in all our relationships.', 'fas fa-handshake', 4, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(15, 'Customer Focus', 'We specialize in serving municipal water treatment facilities with dedicated expertise, technical support, and solutions tailored to their unique requirements.', 'fas fa-users', 5, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(16, 'Global Impact', 'We work to make a positive impact on the environment and communities worldwide.', 'fas fa-globe', 6, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_info`
--

CREATE TABLE `contact_info` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `value` text NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_info`
--

INSERT INTO `contact_info` (`id`, `type`, `label`, `value`, `icon`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'address', 'Visit Us', 'Tupi Supreme Activated Carbon, Inc.\nCoordinates: 6.2938775°N, 124.9873484°E\nPhilippines', 'fa-map-marker-alt', 1, 1, '2025-12-02 14:49:43', '2025-12-02 14:53:46', NULL),
(2, 'phone', 'Main', '+1 (555) 123-4567', 'fa-phone', 1, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL),
(3, 'phone', 'Sales', '+1 (555) 123-4568', 'fa-phone', 2, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL),
(4, 'phone', 'Support', '+1 (555) 123-4569', 'fa-phone', 3, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL),
(5, 'email', 'General', 'info@tupisupreme.com', 'fa-envelope', 1, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL),
(6, 'email', 'Sales', 'sales@tupisupreme.com', 'fa-envelope', 2, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL),
(7, 'email', 'Support', 'support@tupisupreme.com', 'fa-envelope', 3, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(200) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_subject_options`
--

CREATE TABLE `contact_subject_options` (
  `id` int(11) NOT NULL,
  `option_text` varchar(200) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_subject_options`
--

INSERT INTO `contact_subject_options` (`id`, `option_text`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Municipal Water Treatment Inquiry', 1, 1, '2025-12-02 14:50:56', '2025-12-02 14:50:56', NULL),
(2, 'Request Quote', 2, 1, '2025-12-02 14:50:56', '2025-12-02 14:50:56', NULL),
(3, 'Technical Consultation', 3, 1, '2025-12-02 14:50:56', '2025-12-02 14:50:56', NULL),
(4, 'Product Information', 4, 1, '2025-12-02 14:50:56', '2025-12-02 14:50:56', NULL),
(5, 'Custom Formulation Request', 5, 1, '2025-12-02 14:50:56', '2025-12-02 14:50:56', NULL),
(6, 'Other', 6, 1, '2025-12-02 14:50:56', '2025-12-02 14:50:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `category`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'What is the particle size specification for your granulated activated carbon?', 'Our granulated activated carbon is specified at 2mm below (granulated). This particle size is optimized for municipal water treatment applications and provides excellent flow characteristics while maintaining high adsorption capacity.', 'General', 1, 1, '2025-12-02 14:46:17', '2025-12-02 14:46:17', NULL),
(2, 'Do you provide technical data sheets for your products?', 'Yes, we provide comprehensive technical data sheets for all our products, including detailed specifications for our 2mm granulated activated carbon. You can download them from our Resources page or request them through our contact form.', 'General', 2, 1, '2025-12-02 14:46:17', '2025-12-02 14:46:17', NULL),
(3, 'What makes your activated carbon suitable for municipal water treatment?', 'Our 2mm granulated activated carbon is specifically designed for municipal water treatment facilities. We have served 200+ municipal clients with proven results, consistent quality, and strict specifications that meet or exceed industry standards. Our products are certified and backed by comprehensive technical support.', 'Products', 3, 1, '2025-12-02 14:46:17', '2025-12-02 14:46:17', NULL),
(4, 'Can you customize activated carbon formulations for specific applications?', 'Yes, we offer custom formulations tailored to your specific application requirements. Our technical team can work with you to develop activated carbon with custom particle sizes, surface chemistry, and performance characteristics optimized for your needs.', 'Services', 4, 1, '2025-12-02 14:46:17', '2025-12-02 14:46:17', NULL),
(5, 'What certifications do your products have?', 'We maintain ISO certifications and comply with industry standards. Our products meet NSF/ANSI standards for drinking water applications. Please visit our Certifications page for detailed information about our quality certifications and compliance.', 'General', 5, 1, '2025-12-02 14:46:17', '2025-12-02 14:46:17', NULL),
(6, 'What is your lead time for orders?', 'Standard orders typically ship within 5-7 business days. For custom formulations or large orders, lead times may vary. We\'ll provide specific timelines during the quotation process. For municipal water treatment facilities, we prioritize reliable supply chain management.', 'Services', 6, 1, '2025-12-02 14:46:17', '2025-12-02 14:46:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `footer_links`
--

CREATE TABLE `footer_links` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `label` varchar(200) NOT NULL,
  `url` varchar(500) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `footer_links`
--

INSERT INTO `footer_links` (`id`, `category`, `label`, `url`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'quick_links', 'Home', 'index.php', 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'quick_links', 'About Us', 'about.php', 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'quick_links', 'Products', 'products.php', 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'quick_links', 'Services', 'services.php', 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(5, 'quick_links', 'Case Studies', 'case-studies.php', 5, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(6, 'quick_links', 'Gallery', 'gallery.php', 6, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(7, 'quick_links', 'Resources', 'resources.php', 7, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(8, 'products', 'Granulated Activated Carbon', 'products.php#granulated', 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(9, 'products', 'Municipal Water Treatment', 'products.php#municipal', 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(10, 'products', 'Coconut Husk Products', 'products.php#husk', 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(11, 'products', 'Custom Formulations', 'products.php#custom', 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(12, 'legal', 'Privacy Policy', '#', 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(13, 'legal', 'Terms of Service', '#', 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gallery_images`
--

CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `category` enum('facilities','products','process','installations','team') NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_images`
--

INSERT INTO `gallery_images` (`id`, `title`, `description`, `image_url`, `category`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Production Facility', 'State-of-the-art manufacturing plant with advanced equipment for activated carbon production', '', 'facilities', 1, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(2, 'Storage Warehouse', 'Quality controlled storage facilities ensuring optimal conditions for activated carbon products', '', 'facilities', 2, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(3, 'Quality Laboratory', 'ISO-certified testing facility for rigorous quality control and product analysis', '', 'facilities', 3, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(4, 'Main Office Building', 'Corporate headquarters housing our administrative and technical teams', '', 'facilities', 4, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(5, 'Granulated Activated Carbon', 'Premium 2mm granulated activated carbon meeting strict quality specifications', '', 'products', 1, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(6, 'Coconut Husk Products', 'High-quality coconut husk growing medium products for agricultural applications', '', 'products', 2, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(7, 'Product Packaging', 'Quality packaging solutions ensuring product integrity during transport', '', 'products', 3, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(8, 'Bulk Product Storage', 'Large-scale storage of activated carbon products ready for distribution', '', 'products', 4, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(9, 'Activation Process', 'Steam activation technology creating high-surface-area activated carbon', '', 'process', 1, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(10, 'Carbonization', 'High-temperature processing converting raw materials into activated carbon', '', 'process', 2, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(11, 'Quality Control', 'Rigorous testing procedures ensuring consistent product quality and specifications', '', 'process', 3, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(12, 'Screening & Grading', 'Precise particle size screening to meet exact customer specifications', '', 'process', 4, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(13, 'Municipal Water Treatment', 'Installation at municipal water treatment facility showcasing our activated carbon solutions', '', 'installations', 1, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(14, 'Treatment Plant', 'Large-scale municipal facility installation with integrated filtration systems', '', 'installations', 2, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(15, 'Installation Process', 'Professional installation team ensuring proper setup and system integration', '', 'installations', 3, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(16, 'Filtration System', 'Complete activated carbon filtration system in operation at customer facility', '', 'installations', 4, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(17, 'Expert Team', 'Dedicated professionals committed to excellence in activated carbon solutions', '', 'team', 1, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(18, 'Technical Experts', 'Quality assurance team ensuring product specifications and customer satisfaction', '', 'team', 2, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(19, 'Customer Service', 'Client consultation and support team providing personalized service', '', 'team', 3, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(20, 'Awards & Recognition', 'Industry achievements and certifications recognizing our commitment to quality', '', 'team', 4, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL),
(21, 'Training Session', 'Employee training program ensuring continuous improvement and skill development', '', 'team', 5, 1, '2025-12-02 14:30:24', '2025-12-02 14:41:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `homepage_features`
--

CREATE TABLE `homepage_features` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `homepage_features`
--

INSERT INTO `homepage_features` (`id`, `title`, `description`, `icon`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Premium Quality', 'Our 2mm granulated activated carbon products meet the highest industry standards with consistent quality and specifications.', 'fas fa-award', 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'Municipal Water Treatment Expertise', 'Proven track record serving municipal water treatment facilities with reliable, high-performance activated carbon solutions.', 'fas fa-building', 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'Sustainable Zero-Waste Operations', 'Eco-friendly production processes with zero-waste operations, maximizing resource utilization and environmental responsibility.', 'fas fa-leaf', 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'Technical Support & Consultation', 'Expert technical consultation, 24/7 support, and customized solutions for your municipal and industrial needs.', 'fas fa-headset', 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `office_hours`
--

CREATE TABLE `office_hours` (
  `id` int(11) NOT NULL,
  `day_label` varchar(50) NOT NULL,
  `hours` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `office_hours`
--

INSERT INTO `office_hours` (`id`, `day_label`, `hours`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Monday - Friday', '8:00 AM - 6:00 PM', 1, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL),
(2, 'Saturday', '9:00 AM - 2:00 PM', 2, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL),
(3, 'Sunday', 'Closed', 3, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL),
(4, 'Emergency Support', '24/7 Available', 4, 1, '2025-12-02 14:49:43', '2025-12-02 14:49:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `page_content`
--

CREATE TABLE `page_content` (
  `id` int(11) NOT NULL,
  `page_name` varchar(50) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `content_type` enum('text','html','json','image_url') DEFAULT 'text',
  `content` text DEFAULT NULL,
  `hero_icon` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `page_content`
--

INSERT INTO `page_content` (`id`, `page_name`, `section_name`, `content_type`, `content`, `hero_icon`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'index', 'hero_title', 'text', 'Premium Activated Carbon Solutions for Municipal Water Treatment', NULL, 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'index', 'hero_description', 'text', 'Leading provider of high-quality activated carbon products for municipal water treatment facilities, industrial applications, and environmental solutions. Your trusted partner for reliable water purification and sustainable environmental solutions.', NULL, 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'index', 'hero_cta_primary_text', 'text', 'Request Technical Consultation', NULL, 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'index', 'hero_cta_primary_link', 'text', 'contact.php', NULL, 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(5, 'index', 'hero_cta_secondary_text', 'text', 'Download Product Specs', NULL, 5, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(6, 'index', 'hero_cta_secondary_link', 'text', 'resources.php', NULL, 6, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(7, 'index', 'features_title', 'text', 'Why Choose Tupi Supreme?', NULL, 7, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(8, 'index', 'features_subtitle', 'text', 'We deliver excellence in every product and service we provide', NULL, 8, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(9, 'index', 'products_title', 'text', 'Our Premium Products', NULL, 9, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(10, 'index', 'products_subtitle', 'text', 'Discover our range of high-quality activated carbon solutions', NULL, 10, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(11, 'index', 'cta_title', 'text', 'Ready to Get Started?', NULL, 11, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(12, 'index', 'cta_description', 'text', 'Contact us today for a free technical consultation and quote on your activated carbon needs. Serving municipal water treatment facilities nationwide.', NULL, 12, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(37, 'index', 'cta_button_1_text', 'text', 'Request Quote', NULL, 13, 1, '2025-12-02 14:10:11', '2025-12-02 14:10:11', NULL),
(38, 'index', 'cta_button_1_link', 'text', 'contact.php', NULL, 14, 1, '2025-12-02 14:10:11', '2025-12-02 14:10:11', NULL),
(39, 'index', 'cta_button_2_text', 'text', 'Download Technical Specs', NULL, 15, 1, '2025-12-02 14:10:11', '2025-12-02 14:10:11', NULL),
(40, 'index', 'cta_button_2_link', 'text', 'resources.php', NULL, 16, 1, '2025-12-02 14:10:11', '2025-12-02 14:10:11', NULL),
(41, 'index', 'cta_button_3_text', 'text', 'Contact Sales Team', NULL, 17, 1, '2025-12-02 14:10:11', '2025-12-02 14:10:11', NULL),
(42, 'index', 'cta_button_3_link', 'text', 'contact.php', NULL, 18, 1, '2025-12-02 14:10:11', '2025-12-02 14:10:11', NULL),
(43, 'index', 'hero_icon', 'text', 'fas fa-water', NULL, 0, 1, '2025-12-02 14:10:15', '2025-12-02 14:10:15', NULL),
(50, 'products', 'page_header_title', 'text', 'Our Products', NULL, 1, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(51, 'products', 'page_header_subtitle', 'text', 'Premium activated carbon solutions for municipal water treatment and industrial applications', NULL, 2, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(52, 'products', 'section_title', 'text', 'Our Products', NULL, 3, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(53, 'products', 'section_subtitle', 'text', 'Premium activated carbon solutions and sustainable growing mediums', NULL, 4, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(54, 'products', 'specifications_title', 'text', 'Granulated Activated Carbon Specifications', NULL, 5, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(55, 'products', 'specifications_subtitle', 'text', 'Technical specifications for our premium 2mm granulated activated carbon', NULL, 6, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(56, 'products', 'applications_title', 'text', 'Applications', NULL, 7, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(57, 'products', 'applications_subtitle', 'text', 'Our 2mm granulated activated carbon serves diverse industries, with Municipal Water Treatment as our primary application', NULL, 8, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(58, 'products', 'cta_title', 'text', 'Ready to Get Started?', NULL, 9, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(59, 'products', 'cta_description', 'text', 'Our technical team specializes in municipal water treatment applications and can help you find the perfect 2mm granulated activated carbon solution for your facility.', NULL, 10, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(60, 'products', 'cta_button_1_text', 'text', 'Request Technical Consultation', NULL, 11, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(61, 'products', 'cta_button_1_link', 'text', 'contact.php', NULL, 12, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(62, 'products', 'cta_button_2_text', 'text', 'Download Technical Specs', NULL, 13, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(63, 'products', 'cta_button_2_link', 'text', 'resources.php', NULL, 14, 1, '2025-12-02 14:18:25', '2025-12-02 14:18:25', NULL),
(64, 'services', 'page_header_title', 'text', 'Our Services', NULL, 1, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(65, 'services', 'page_header_subtitle', 'text', 'Comprehensive support and solutions for all your activated carbon needs', NULL, 2, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(66, 'services', 'section_title', 'text', 'Comprehensive Services', NULL, 3, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(67, 'services', 'section_subtitle', 'text', 'From technical consultation to system integration, we provide end-to-end solutions', NULL, 4, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(68, 'services', 'process_title', 'text', 'Our Service Process', NULL, 5, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(69, 'services', 'process_subtitle', 'text', 'A systematic approach to delivering exceptional service and solutions', NULL, 6, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(70, 'services', 'features_title', 'text', 'Why Choose Our Services?', NULL, 7, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(71, 'services', 'features_subtitle', 'text', 'We deliver exceptional value through our comprehensive service offerings', NULL, 8, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(72, 'services', 'testimonials_title', 'text', 'Client Testimonials', NULL, 9, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(73, 'services', 'testimonials_subtitle', 'text', 'What our clients say about our services', NULL, 10, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(74, 'services', 'cta_title', 'text', 'Ready to Get Started?', NULL, 11, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(75, 'services', 'cta_description', 'text', 'Contact us today to discuss your specific needs and discover how our services can benefit your operations.', NULL, 12, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(76, 'services', 'cta_button_1_text', 'text', 'Request Consultation', NULL, 13, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(77, 'services', 'cta_button_1_link', 'text', 'contact.php', NULL, 14, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(78, 'services', 'cta_button_2_text', 'text', 'Get Quote', NULL, 15, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(79, 'services', 'cta_button_2_link', 'text', 'contact.php', NULL, 16, 1, '2025-12-02 14:19:49', '2025-12-02 14:19:49', NULL),
(80, 'case-studies', 'page_header_title', 'text', 'Municipal Water Treatment Case Studies', NULL, 1, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(81, 'case-studies', 'page_header_subtitle', 'text', 'Real-world success stories from municipal facilities using our activated carbon solutions', NULL, 2, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(82, 'case-studies', 'section_title', 'text', 'Success Stories', NULL, 3, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(83, 'case-studies', 'section_subtitle', 'text', 'Discover how municipal water treatment facilities have achieved outstanding results with our 2mm granulated activated carbon', NULL, 4, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(84, 'case-studies', 'cta_title', 'text', 'Ready to Achieve Similar Results?', NULL, 5, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(85, 'case-studies', 'cta_description', 'text', 'Contact us today to discuss how our activated carbon solutions can benefit your municipal water treatment facility', NULL, 6, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(86, 'case-studies', 'cta_button_1_text', 'text', 'Request Consultation', NULL, 7, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(87, 'case-studies', 'cta_button_1_link', 'text', 'contact.php', NULL, 8, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(88, 'case-studies', 'cta_button_2_text', 'text', 'Download Case Study PDF', NULL, 9, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(89, 'case-studies', 'cta_button_2_link', 'text', 'resources.php', NULL, 10, 1, '2025-12-02 14:25:10', '2025-12-02 14:25:10', NULL),
(90, 'gallery', 'page_header_title', 'text', 'Photo Gallery', NULL, 1, 1, '2025-12-02 14:27:54', '2025-12-02 14:27:54', NULL),
(91, 'gallery', 'page_header_subtitle', 'text', 'Explore our facilities, products, and operations through our photo collections', NULL, 2, 1, '2025-12-02 14:27:54', '2025-12-02 14:27:54', NULL),
(92, 'resources', 'page_header_title', 'text', 'Resources & Documentation', NULL, 1, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(93, 'resources', 'page_header_subtitle', 'text', 'Download technical data sheets, product specifications, and application guides', NULL, 2, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(94, 'resources', 'data_sheets_title', 'text', 'Technical Data Sheets', NULL, 3, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(95, 'resources', 'data_sheets_subtitle', 'text', 'Comprehensive technical specifications for our activated carbon products', NULL, 4, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(96, 'resources', 'catalogs_title', 'text', 'Product Catalogs', NULL, 5, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(97, 'resources', 'catalogs_subtitle', 'text', 'Comprehensive product catalogs and brochures', NULL, 6, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(98, 'resources', 'guides_title', 'text', 'Application Guides', NULL, 7, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(99, 'resources', 'guides_subtitle', 'text', 'Detailed guides for implementing activated carbon in various applications', NULL, 8, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(100, 'resources', 'faqs_title', 'text', 'Frequently Asked Questions', NULL, 9, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(101, 'resources', 'faqs_subtitle', 'text', 'Common questions about our products and services', NULL, 10, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(102, 'resources', 'cta_title', 'text', 'Need More Information?', NULL, 11, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(103, 'resources', 'cta_description', 'text', 'Contact our technical team for additional documentation or customized information for your specific application', NULL, 12, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(104, 'resources', 'cta_button_1_text', 'text', 'Request Technical Consultation', NULL, 13, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(105, 'resources', 'cta_button_1_link', 'text', 'contact.php', NULL, 14, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(106, 'resources', 'cta_button_2_text', 'text', 'Contact Sales Team', NULL, 15, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(107, 'resources', 'cta_button_2_link', 'text', 'contact.php', NULL, 16, 1, '2025-12-02 14:44:37', '2025-12-02 14:44:37', NULL),
(108, 'resources', 'meta_description', 'text', 'Download technical data sheets, product catalogs, application guides, and resources for TSACI activated carbon products. Essential documentation for municipal water treatment facilities.', NULL, 0, 1, '2025-12-02 14:48:18', '2025-12-02 14:48:18', NULL),
(109, 'resources', 'download_button_text', 'text', 'Download', NULL, 17, 1, '2025-12-02 14:48:18', '2025-12-02 14:48:18', NULL),
(110, 'resources', 'download_guide_text', 'text', 'Download Guide', NULL, 18, 1, '2025-12-02 14:48:18', '2025-12-02 14:48:18', NULL),
(111, 'contact', 'page_header_title', 'text', 'Contact Us', NULL, 1, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(112, 'contact', 'page_header_subtitle', 'text', 'Get in touch with our team for all your activated carbon needs', NULL, 2, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(113, 'contact', 'contact_section_title', 'text', 'Get In Touch', NULL, 3, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(114, 'contact', 'contact_section_subtitle', 'text', 'We\'re here to help with all your activated carbon requirements', NULL, 4, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(115, 'contact', 'form_title', 'text', 'Send Us a Message', NULL, 5, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(116, 'contact', 'form_subtitle', 'text', 'Fill out the form below and we\'ll get back to you as soon as possible', NULL, 6, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(117, 'contact', 'office_hours_title', 'text', 'Office Hours', NULL, 7, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(118, 'contact', 'office_hours_subtitle', 'text', 'When you can reach our team', NULL, 8, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(119, 'contact', 'map_title', 'text', 'Find Us', NULL, 9, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(120, 'contact', 'map_subtitle', 'text', 'Visit our facility or get directions', NULL, 10, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(121, 'contact', 'faqs_title', 'text', 'Frequently Asked Questions', NULL, 11, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(122, 'contact', 'faqs_subtitle', 'text', 'Common questions about our products and services', NULL, 12, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(123, 'contact', 'timezone_note', 'text', 'All times are in Eastern Standard Time (EST)', NULL, 13, 1, '2025-12-02 14:49:55', '2025-12-02 14:49:55', NULL),
(124, 'contact', 'meta_description', 'text', 'Contact Tupi Supreme Activated Carbon, Inc. for all your activated carbon needs. Get in touch with our team for technical consultation, product information, and custom solutions.', NULL, 0, 1, '2025-12-02 14:50:30', '2025-12-02 14:50:30', NULL),
(126, 'contact', 'map_embed_url', 'text', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7703916584064!2d124.9847734749907!3d6.293877493695194!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f78ee4e9ab4bd5%3A0xa78774472d2fc69d!2sTupi%20Supreme%20Activated%20Carbon%2C%20Inc.!5e0!3m2!1sen!2sph!4v1764687549707!5m2!1sen!2sph', NULL, 14, 1, '2025-12-02 14:56:06', '2025-12-02 15:08:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `applications` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `specifications`, `features`, `applications`, `image_url`, `display_order`, `is_featured`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Granulated Activated Carbon (GAC)', 'granulated-activated-carbon', 'Premium 2mm granulated activated carbon optimized for municipal water treatment facilities. Strict quality specifications ensure consistent performance.', '2mm below (granulated) • High surface area • Low ash content • Consistent particle size distribution', 'Premium quality • Strict specifications • Consistent performance • GAC certified', 'Municipal water treatment • Industrial water purification • Wastewater treatment • Air purification', NULL, 1, 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'Municipal Water Treatment Solutions', 'municipal-water-treatment', 'Specialized activated carbon solutions designed specifically for municipal water treatment facilities. Proven track record with 200+ municipal clients.', 'Custom formulations • System integration • Technical support • Compliance guaranteed', 'Municipal expertise • Proven results • Technical consultation • 24/7 support', 'Municipal water treatment • Public water systems • Water quality compliance', NULL, 2, 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'Coconut Husk Chips', 'coconut-husk-chips', 'Sustainable growing medium made from coconut husks. Ideal for horticultural and agricultural applications.', 'Natural organic material • Excellent drainage • High water retention • pH balanced', 'Sustainable • Organic • Renewable resource • Eco-friendly', 'Horticulture • Agriculture • Landscaping • Hydroponics', NULL, 3, 0, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'Coconut Pit', 'coconut-pit', 'Premium horticultural growing medium. Finely processed coconut coir for optimal plant growth.', 'Fine texture • High water retention • Excellent aeration • Nutrient rich', 'Premium quality • Sustainable • Organic • Versatile', 'Horticulture • Greenhouse production • Container gardening • Seed starting', NULL, 4, 0, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `download_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `product_tabs`
--

CREATE TABLE `product_tabs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tab_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-cube',
  `keywords` varchar(200) DEFAULT '',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tab_key` (`tab_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `title`, `description`, `file_url`, `file_type`, `file_size`, `category`, `display_order`, `is_active`, `download_count`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Granulated Activated Carbon (GAC) Technical Data Sheet', '2mm below (granulated) specification • Surface area, iodine number, ash content • Municipal water treatment applications', '#', 'PDF', NULL, 'Technical Data Sheets', 1, 1, 0, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'Coconut Husk Chips Technical Data Sheet', 'Growing medium specifications • Horticultural applications • Packaging information', '#', 'PDF', NULL, 'Technical Data Sheets', 2, 1, 0, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'Coconut Pit Technical Data Sheet', 'Premium horticultural growing medium • Specifications • Application guide', '#', 'PDF', NULL, 'Technical Data Sheets', 3, 1, 0, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'Product Catalog 2024', 'Complete product catalog featuring all our activated carbon products and coconut husk products', '#', 'PDF', NULL, 'Product Catalogs', 4, 1, 0, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(5, 'Municipal Water Treatment Application Guide', 'Comprehensive guide for municipal water treatment facilities using activated carbon', '#', 'PDF', NULL, 'Application Guides', 5, 1, 0, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(6, 'Granulated Activated Carbon (GAC) Technical Data Sheet', '2mm below (granulated) specification • Surface area, iodine number, ash content • Municipal water treatment applications', '#', 'PDF', NULL, 'Technical Data Sheets', 1, 1, 0, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(7, 'Coconut Husk Chips Technical Data Sheet', 'Growing medium specifications • Horticultural applications • Packaging information', '#', 'PDF', NULL, 'Technical Data Sheets', 2, 1, 0, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(8, 'Coconut Pit Technical Data Sheet', 'Premium horticultural growing medium • Specifications • Application guide', '#', 'PDF', NULL, 'Technical Data Sheets', 3, 1, 0, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(9, 'Product Catalog 2024', 'Complete product catalog featuring all our activated carbon products and coconut husk products', '#', 'PDF', NULL, 'Product Catalogs', 4, 1, 0, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(10, 'Municipal Water Treatment Application Guide', 'Comprehensive guide for municipal water treatment facilities using activated carbon', '#', 'PDF', NULL, 'Application Guides', 5, 1, 0, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(11, 'Granulated Activated Carbon (GAC) Technical Data Sheet', '2mm below (granulated) specification • Surface area, iodine number, ash content • Municipal water treatment applications', '#', 'PDF', NULL, 'Technical Data Sheets', 1, 1, 0, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(12, 'Coconut Husk Chips Technical Data Sheet', 'Growing medium specifications • Horticultural applications • Packaging information', '#', 'PDF', NULL, 'Technical Data Sheets', 2, 1, 0, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(13, 'Coconut Pit Technical Data Sheet', 'Premium horticultural growing medium • Specifications • Application guide', '#', 'PDF', NULL, 'Technical Data Sheets', 3, 1, 0, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(14, 'Product Catalog 2024', 'Complete product catalog featuring all our activated carbon products and coconut husk products', '#', 'PDF', NULL, 'Product Catalogs', 4, 1, 0, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(15, 'Municipal Water Treatment Application Guide', 'Comprehensive guide for municipal water treatment facilities using activated carbon', '#', 'PDF', NULL, 'Application Guides', 5, 1, 0, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `title`, `slug`, `description`, `features`, `benefits`, `icon`, `image_url`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Technical Consultation', 'technical-consultation', 'Expert technical support and consultation for all activated carbon applications and system design.', 'Application analysis\nSystem design\nProduct selection\nPerformance optimization', 'Expert guidance\nCustomized solutions\nCost optimization\nImproved efficiency', 'fas fa-headset', NULL, 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'Custom Formulations', 'custom-formulations', 'Tailored activated carbon formulations designed to meet your specific application requirements.', 'Custom specifications\nQuality testing\nBatch consistency\nTechnical documentation', 'Perfect fit for your needs\nOptimized performance\nQuality assurance\nDocumentation provided', 'fas fa-cogs', NULL, 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'System Integration', 'system-integration', 'Complete system design and integration services for activated carbon filtration systems.', 'System design\nInstallation support\nCommissioning\nTraining', 'Seamless integration\nProfessional installation\nSystem optimization\nStaff training', 'fas fa-tools', NULL, 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'Quality Assurance', 'quality-assurance', 'Comprehensive quality testing and certification services for all activated carbon products.', 'Batch testing\nQuality certification\nCompliance verification\nDocumentation', 'Quality guaranteed\nCompliance assured\nCertified products\nFull documentation', 'fas fa-certificate', NULL, 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(5, '24/7 Support', 'support', 'Round-the-clock technical support and customer service for all your activated carbon needs.', '24/7 availability\nTechnical assistance\nEmergency support\nRapid response', 'Always available\nExpert assistance\nPeace of mind\nQuick resolution', 'fas fa-clock', NULL, 5, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`, `updated_by`) VALUES
(1, 'company_name', 'Tupi Supreme Activated Carbon, Inc.', 'text', 'Company full name', '2025-12-02 13:59:41', NULL),
(2, 'company_short_name', 'Tupi Supreme', 'text', 'Company short name', '2025-12-02 13:59:41', NULL),
(3, 'company_tagline', 'Leading provider of premium activated carbon solutions for environmental protection and industrial applications.', 'text', 'Company tagline', '2025-12-02 13:59:41', NULL),
(4, 'contact_address', '123 Industrial Ave, Tupi City', 'text', 'Company address', '2025-12-02 13:59:41', NULL),
(5, 'contact_phone', '+1 (555) 123-4567', 'text', 'Contact phone number', '2025-12-02 13:59:41', NULL),
(6, 'contact_email', 'info@tupisupreme.com', 'text', 'Contact email', '2025-12-02 13:59:41', NULL),
(7, 'copyright_year', '2024', 'text', 'Copyright year', '2025-12-02 13:59:41', NULL),
(8, 'meta_description', 'Premium activated carbon solutions for municipal water treatment facilities. 2mm granulated activated carbon with proven track record.', 'text', 'Default meta description', '2025-12-02 13:59:41', NULL),
(9, 'meta_keywords', 'municipal water treatment activated carbon, granulated activated carbon, 2mm activated carbon, water treatment carbon', 'text', 'Default meta keywords', '2025-12-02 13:59:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `social_media`
--

CREATE TABLE `social_media` (
  `id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_media`
--

INSERT INTO `social_media` (`id`, `platform`, `url`, `icon_class`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Facebook', '#', 'fab fa-facebook', 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'Twitter', '#', 'fab fa-twitter', 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'LinkedIn', '#', 'fab fa-linkedin', 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'Instagram', '#', 'fab fa-instagram', 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `statistics`
--

CREATE TABLE `statistics` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL,
  `value` varchar(50) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `statistics`
--

INSERT INTO `statistics` (`id`, `label`, `value`, `description`, `icon`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Years Experience', '25+', 'Years of industry experience', 'fas fa-calendar', 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 'Happy Clients', '500+', 'Including 200+ Municipal Facilities', 'fas fa-users', 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 'Product Varieties', '50+', 'Different product varieties', 'fas fa-cubes', 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 'Support Available', '24/7', '24/7 customer support', 'fas fa-clock', 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `position` varchar(200) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `linkedin_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `name`, `position`, `bio`, `photo_url`, `email`, `linkedin_url`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'John Smith', 'Chief Executive Officer', '25+ years of experience in the activated carbon industry, leading our company\'s strategic vision and growth.', NULL, NULL, NULL, 1, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(2, 'Sarah Johnson', 'Chief Operations Officer', 'Expert in manufacturing processes and quality control, ensuring our products meet the highest standards.', NULL, NULL, NULL, 2, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(3, 'Michael Chen', 'Chief Technology Officer', 'Leading our R&D efforts and driving innovation in activated carbon technology and applications.', NULL, NULL, NULL, 3, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `position` varchar(200) DEFAULT NULL,
  `company` varchar(200) DEFAULT NULL,
  `testimonial` text NOT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `name`, `position`, `company`, `testimonial`, `photo_url`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'John Davis', 'Water Treatment Plant Manager', 'City Water Authority', 'Tupi Supreme\'s technical consultation services helped us optimize our water treatment system, resulting in 30% improved efficiency and significant cost savings.', NULL, 1, 1, '2025-12-02 14:22:56', '2025-12-02 14:22:56', NULL),
(2, 'Sarah Chen', 'Environmental Engineer', 'Environmental Solutions Inc.', 'Their laboratory testing services provided us with detailed performance data that was crucial for our regulatory compliance and system optimization.', NULL, 2, 1, '2025-12-02 14:22:56', '2025-12-02 14:22:56', NULL),
(3, 'Michael Rodriguez', 'Industrial Plant Director', 'Manufacturing Corp', 'The system integration service was exceptional. Their team handled everything from design to commissioning with professional expertise.', NULL, 3, 1, '2025-12-02 14:22:56', '2025-12-02 14:22:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `timeline_events`
--

CREATE TABLE `timeline_events` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timeline_events`
--

INSERT INTO `timeline_events` (`id`, `year`, `title`, `description`, `display_order`, `is_active`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 1999, 'Company Founded', 'Tupi Supreme Activated Carbon, Inc. was established with a mission to provide quality activated carbon solutions.', 1, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(2, 2005, 'ISO 9001 Certification', 'Achieved ISO 9001:2015 Quality Management Systems certification.', 2, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(3, 2010, 'Municipal Market Expansion', 'Expanded operations to serve municipal water treatment facilities nationwide.', 3, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(4, 2015, 'ISO 14001 Certification', 'Achieved ISO 14001:2015 Environmental Management Systems certification.', 4, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(5, 2020, '200+ Municipal Clients', 'Reached milestone of serving 200+ municipal water treatment facilities.', 5, 1, '2025-12-02 13:59:41', '2025-12-02 13:59:41', NULL),
(6, 1999, 'Company Founded', 'Started as a small family business with a focus on local water treatment solutions.', 1, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(7, 2005, 'First Major Contract', 'Secured our first municipal water treatment contract, marking our entry into large-scale operations.', 2, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(8, 2010, 'ISO Certification', 'Achieved ISO 9001:2008 certification, demonstrating our commitment to quality management.', 3, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(9, 2015, 'International Expansion', 'Expanded operations to serve international markets, establishing partnerships worldwide.', 4, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(10, 2020, 'Innovation Center', 'Opened our state-of-the-art research and development center for product innovation.', 5, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(11, 2024, 'Industry Leader', 'Recognized as one of the top activated carbon manufacturers with 500+ satisfied clients.', 6, 1, '2025-12-02 14:03:41', '2025-12-02 14:03:41', NULL),
(12, 1999, 'Company Founded', 'Started as a small family business with a focus on local water treatment solutions.', 1, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(13, 2005, 'First Major Contract', 'Secured our first municipal water treatment contract, marking our entry into large-scale operations.', 2, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(14, 2010, 'ISO Certification', 'Achieved ISO 9001:2008 certification, demonstrating our commitment to quality management.', 3, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(15, 2015, 'International Expansion', 'Expanded operations to serve international markets, establishing partnerships worldwide.', 4, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(16, 2020, 'Innovation Center', 'Opened our state-of-the-art research and development center for product innovation.', 5, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL),
(17, 2024, 'Industry Leader', 'Recognized as one of the top activated carbon manufacturers with 500+ satisfied clients.', 6, 1, '2025-12-02 14:03:47', '2025-12-02 14:03:47', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_content`
--
ALTER TABLE `about_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_section` (`section_name`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `case_studies`
--
ALTER TABLE `case_studies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_is_featured` (`is_featured`);

--
-- Indexes for table `certifications`
--
ALTER TABLE `certifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_title` (`title`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `company_values`
--
ALTER TABLE `company_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_title` (`title`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `contact_info`
--
ALTER TABLE `contact_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `contact_subject_options`
--
ALTER TABLE `contact_subject_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `footer_links`
--
ALTER TABLE `footer_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_category_label` (`category`,`label`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_title` (`title`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `homepage_features`
--
ALTER TABLE `homepage_features`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_title` (`title`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `office_hours`
--
ALTER TABLE `office_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `page_content`
--
ALTER TABLE `page_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_page_section` (`page_name`,`section_name`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_page_name` (`page_name`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_is_featured` (`is_featured`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_title` (`title`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `social_media`
--
ALTER TABLE `social_media`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_platform` (`platform`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `statistics`
--
ALTER TABLE `statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_label` (`label`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_name` (`name`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_display_order` (`display_order`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `timeline_events`
--
ALTER TABLE `timeline_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_title` (`title`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_content`
--
ALTER TABLE `about_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `carousel_slides`
--
ALTER TABLE `carousel_slides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `case_studies`
--
ALTER TABLE `case_studies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `certifications`
--
ALTER TABLE `certifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `company_values`
--
ALTER TABLE `company_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `contact_info`
--
ALTER TABLE `contact_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_subject_options`
--
ALTER TABLE `contact_subject_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `footer_links`
--
ALTER TABLE `footer_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `gallery_images`
--
ALTER TABLE `gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `homepage_features`
--
ALTER TABLE `homepage_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `office_hours`
--
ALTER TABLE `office_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `page_content`
--
ALTER TABLE `page_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `social_media`
--
ALTER TABLE `social_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `statistics`
--
ALTER TABLE `statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timeline_events`
--
ALTER TABLE `timeline_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `about_content`
--
ALTER TABLE `about_content`
  ADD CONSTRAINT `about_content_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `case_studies`
--
ALTER TABLE `case_studies`
  ADD CONSTRAINT `case_studies_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `certifications`
--
ALTER TABLE `certifications`
  ADD CONSTRAINT `certifications_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `company_values`
--
ALTER TABLE `company_values`
  ADD CONSTRAINT `company_values_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_info`
--
ALTER TABLE `contact_info`
  ADD CONSTRAINT `contact_info_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_subject_options`
--
ALTER TABLE `contact_subject_options`
  ADD CONSTRAINT `contact_subject_options_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `faqs`
--
ALTER TABLE `faqs`
  ADD CONSTRAINT `faqs_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `footer_links`
--
ALTER TABLE `footer_links`
  ADD CONSTRAINT `footer_links_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gallery_images`
--
ALTER TABLE `gallery_images`
  ADD CONSTRAINT `gallery_images_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `homepage_features`
--
ALTER TABLE `homepage_features`
  ADD CONSTRAINT `homepage_features_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `office_hours`
--
ALTER TABLE `office_hours`
  ADD CONSTRAINT `office_hours_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `page_content`
--
ALTER TABLE `page_content`
  ADD CONSTRAINT `page_content_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD CONSTRAINT `site_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `social_media`
--
ALTER TABLE `social_media`
  ADD CONSTRAINT `social_media_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `statistics`
--
ALTER TABLE `statistics`
  ADD CONSTRAINT `statistics_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `timeline_events`
--
ALTER TABLE `timeline_events`
  ADD CONSTRAINT `timeline_events_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
