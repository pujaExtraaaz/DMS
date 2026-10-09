-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2026 at 07:42 AM
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
-- Database: `dms`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_name` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `actor_name`, `action`, `subject_type`, `subject_id`, `properties`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 9, 'Client Admin', 'created', 'App\\Domains\\Order\\Models\\Order', 14, NULL, '103.137.152.241', '2026-09-10 10:22:35', '2026-09-10 10:22:35'),
(2, 9, 'Client Admin', 'created', 'App\\Domains\\Sales\\Models\\Quotation', 1, NULL, '103.137.152.241', '2026-09-10 10:31:24', '2026-09-10 10:31:24'),
(3, 9, 'Client Admin', 'sent', 'App\\Domains\\Sales\\Models\\Quotation', 1, NULL, '103.137.152.241', '2026-09-10 10:31:29', '2026-09-10 10:31:29'),
(4, 9, 'Client Admin', 'accepted', 'App\\Domains\\Sales\\Models\\Quotation', 1, NULL, '103.137.152.241', '2026-09-10 10:31:33', '2026-09-10 10:31:33'),
(5, 9, 'Client Admin', 'deposited', 'App\\Domains\\Payment\\Models\\Cheque', 1, NULL, '103.137.152.241', '2026-09-10 10:51:16', '2026-09-10 10:51:16'),
(6, 9, 'Client Admin', 'bounced', 'App\\Domains\\Payment\\Models\\Cheque', 1, '{\"bounce_number\":1,\"party_bounce_count\":1,\"triggered_freeze\":false}', '103.137.152.241', '2026-09-10 10:51:37', '2026-09-10 10:51:37'),
(7, 9, 'Client Admin', 'created', 'App\\Domains\\Deal\\Models\\Deal', 1, NULL, '103.137.152.241', '2026-09-10 10:55:15', '2026-09-10 10:55:15'),
(8, 9, 'Client Admin', 'created', 'App\\Domains\\Hrms\\Models\\Employee', 1, NULL, '103.137.152.241', '2026-09-10 11:07:27', '2026-09-10 11:07:27'),
(9, 9, 'Client Admin', 'approved', 'App\\Domains\\Order\\Models\\Order', 14, '{\"credit_check_status\":\"passed\",\"fulfilment_mode\":\"warehouse\",\"back_order\":true}', '103.89.43.94', '2026-09-10 12:17:24', '2026-09-10 12:17:24'),
(10, 9, 'Client Admin', 'converted', 'App\\Domains\\Order\\Models\\Order', 14, NULL, '103.89.43.94', '2026-09-10 12:17:47', '2026-09-10 12:17:47'),
(11, 9, 'Client Admin', 'created', 'App\\Domains\\Order\\Models\\Order', 15, NULL, '43.242.229.58', '2026-09-17 06:06:58', '2026-09-17 06:06:58'),
(12, 9, 'Client Admin', 'approved', 'App\\Domains\\Order\\Models\\Order', 15, '{\"credit_check_status\":\"passed\",\"fulfilment_mode\":\"warehouse\",\"back_order\":true}', '43.242.229.58', '2026-09-17 06:40:53', '2026-09-17 06:40:53'),
(13, 9, 'Client Admin', 'converted', 'App\\Domains\\Order\\Models\\Order', 15, NULL, '43.242.229.58', '2026-09-17 06:41:06', '2026-09-17 06:41:06'),
(14, 9, 'Client Admin', 'created', 'App\\Domains\\Order\\Models\\Order', 16, NULL, '171.61.31.102', '2026-09-28 09:58:24', '2026-09-28 09:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `approval_logs`
--

CREATE TABLE `approval_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `actor_name` varchar(255) DEFAULT NULL,
  `approvable_type` varchar(255) DEFAULT NULL,
  `approvable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `decision` varchar(30) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'North Zone', 'NORTH', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'South Zone', 'SOUTH', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'Zone A', 'ZONE_A', 1, '2026-09-19 06:42:15', '2026-09-19 06:42:15');

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'present',
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `company_id`, `name`, `code`, `address`, `state`, `pincode`, `phone`, `gstin`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Head Office', 'HO', NULL, NULL, NULL, NULL, NULL, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(2, 2, 'branch 1', '001', NULL, 'goa', NULL, NULL, NULL, 1, '2026-09-10 09:18:54', '2026-09-10 09:19:06');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `detail` text DEFAULT NULL,
  `code` varchar(30) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `company_id`, `name`, `detail`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 'levis', NULL, '001', 1, '2026-09-09 11:22:21', '2026-09-09 11:22:21'),
(2, 2, 'apple', NULL, '002', 1, '2026-09-10 09:25:21', '2026-09-10 09:25:21'),
(3, 2, 'menance', NULL, 'BR-2-00001', 1, '2026-09-16 05:34:45', '2026-09-16 05:34:45'),
(4, 1, 'novic', NULL, 'BR-1-00001', 1, '2026-09-16 05:35:01', '2026-09-16 05:36:01'),
(5, 1, 'TestBrand01', NULL, 'BR-1-00002', 1, '2026-09-17 05:26:50', '2026-09-17 05:26:50'),
(6, 1, 'Acme', NULL, 'BR-1-00003', 1, '2026-09-17 06:20:58', '2026-09-17 06:20:58'),
(7, 4, 'SONY', NULL, 'BR-4-00001', 1, '2026-09-25 11:07:29', '2026-09-25 11:07:29'),
(8, NULL, 'LENOVO', NULL, 'BR-GLOBAL-00001', 1, '2026-09-25 11:07:47', '2026-09-25 11:07:47'),
(9, NULL, 'HISENSE', NULL, 'BR-GLOBAL-00002', 1, '2026-09-25 11:08:05', '2026-09-25 11:08:05'),
(10, NULL, 'BOSE', NULL, 'BR-GLOBAL-00003', 1, '2026-09-25 11:08:36', '2026-09-25 11:08:36'),
(11, NULL, 'FASTRACK', NULL, 'BR-GLOBAL-00004', 1, '2026-09-25 11:08:42', '2026-09-25 11:08:42'),
(12, NULL, 'TITAN', NULL, 'BR-GLOBAL-00005', 1, '2026-09-25 11:08:48', '2026-09-25 11:08:48'),
(13, NULL, 'OPTOMA', NULL, 'BR-GLOBAL-00006', 1, '2026-09-25 11:08:53', '2026-09-25 11:08:53'),
(14, NULL, 'VIEWSONIC', NULL, 'BR-GLOBAL-00007', 1, '2026-09-25 11:08:59', '2026-09-25 11:08:59'),
(15, 4, 'BOSE (BR-GLOBAL-00008)', NULL, 'BR-GLOBAL-00008', 1, '2026-09-25 11:09:03', '2026-09-29 10:10:11'),
(16, NULL, 'YAMAHA', NULL, 'BR-GLOBAL-00009', 1, '2026-09-25 11:09:08', '2026-09-25 11:09:08'),
(17, NULL, 'BOSE (BR-GLOBAL-00010)', NULL, 'BR-GLOBAL-00010', 1, '2026-09-25 11:09:12', '2026-09-25 11:09:12'),
(18, NULL, 'JBL', NULL, 'BR-GLOBAL-00011', 1, '2026-09-25 11:09:18', '2026-09-25 11:09:18'),
(19, NULL, 'BOSCH', NULL, 'BR-GLOBAL-00012', 1, '2026-09-25 11:09:23', '2026-09-25 11:09:23'),
(20, NULL, 'EPSON', NULL, 'BR-GLOBAL-00013', 1, '2026-09-25 11:09:27', '2026-09-25 11:09:27'),
(21, NULL, 'ELIPSON', NULL, 'BR-GLOBAL-00014', 1, '2026-09-25 11:10:09', '2026-09-25 11:10:09'),
(22, NULL, 'ELECTO VOICE', NULL, 'BR-GLOBAL-00015', 1, '2026-09-25 11:10:20', '2026-09-25 11:10:20'),
(23, NULL, 'HYPER X', NULL, 'BR-GLOBAL-00016', 1, '2026-09-25 11:10:25', '2026-09-25 11:10:25'),
(24, 4, 'AMIGO', NULL, 'BR-GLOBAL-00017', 1, '2026-09-25 11:10:30', '2026-09-30 07:30:36'),
(25, NULL, 'NEDIS', NULL, 'BR-GLOBAL-00018', 1, '2026-09-25 11:10:35', '2026-09-25 11:10:35'),
(26, NULL, 'KRYSTAL', NULL, 'BR-GLOBAL-00019', 1, '2026-09-25 11:10:41', '2026-09-25 11:10:41'),
(27, NULL, 'IMPEX', NULL, 'BR-GLOBAL-00020', 1, '2026-09-25 11:10:45', '2026-09-25 11:10:45'),
(28, NULL, 'CABLE WISE', NULL, 'BR-GLOBAL-00021', 1, '2026-09-25 11:10:52', '2026-09-25 11:10:52'),
(29, NULL, 'MARANTZ', NULL, 'BR-GLOBAL-00022', 1, '2026-09-25 11:11:01', '2026-09-25 11:11:33'),
(30, NULL, 'DENON', NULL, 'BR-GLOBAL-00023', 1, '2026-09-25 11:11:06', '2026-09-25 11:11:06'),
(31, NULL, 'CYBERNETYX', NULL, 'BR-GLOBAL-00024', 1, '2026-09-25 11:11:27', '2026-09-25 11:11:27'),
(32, 4, 'POLK AUDIO', NULL, 'BR-4-00002', 1, '2026-09-30 07:31:55', '2026-09-30 07:31:55');

-- --------------------------------------------------------

--
-- Table structure for table `business_groups`
--

CREATE TABLE `business_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_groups`
--

INSERT INTO `business_groups` (`id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Default Group', 'DEFAULT', 'Auto-created business group', 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(2, 'trojan distributors', '002-1', NULL, 1, '2026-09-10 09:24:17', '2026-09-10 09:24:17');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('dms-cache-current_fy_pill', 'O:45:\"App\\Domains\\Organization\\Models\\FinancialYear\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:15:\"financial_years\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:9:{s:2:\"id\";i:1;s:10:\"company_id\";i:1;s:4:\"name\";s:7:\"2026-27\";s:9:\"starts_on\";s:10:\"2026-04-01\";s:7:\"ends_on\";s:10:\"2027-03-31\";s:9:\"is_closed\";i:0;s:10:\"is_current\";i:1;s:10:\"created_at\";s:19:\"2026-09-08 06:59:11\";s:10:\"updated_at\";s:19:\"2026-09-21 06:35:54\";}s:11:\"\0*\0original\";a:9:{s:2:\"id\";i:1;s:10:\"company_id\";i:1;s:4:\"name\";s:7:\"2026-27\";s:9:\"starts_on\";s:10:\"2026-04-01\";s:7:\"ends_on\";s:10:\"2027-03-31\";s:9:\"is_closed\";i:0;s:10:\"is_current\";i:1;s:10:\"created_at\";s:19:\"2026-09-08 06:59:11\";s:10:\"updated_at\";s:19:\"2026-09-21 06:35:54\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:4:{s:9:\"starts_on\";s:4:\"date\";s:7:\"ends_on\";s:4:\"date\";s:9:\"is_closed\";s:7:\"boolean\";s:10:\"is_current\";s:7:\"boolean\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:6:{i:0;s:10:\"company_id\";i:1;s:4:\"name\";i:2;s:9:\"starts_on\";i:3;s:7:\"ends_on\";i:4;s:9:\"is_closed\";i:5;s:10:\"is_current\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}', 1791025808),
('dms-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:127:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:12:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;i:7;i:8;i:8;i:9;i:9;i:10;i:10;i:11;i:11;i:12;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:16:\"dashboard.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"masters.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:9;i:4;i:12;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:14:\"masters.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:11:\"orders.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:12;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:13:\"orders.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:12;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:14:\"inventory.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:5;i:2;i:9;i:3;i:12;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:16:\"inventory.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:5;i:2;i:9;i:3;i:12;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:10:\"sales.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:6;i:4;i:12;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:12:\"sales.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:13:\"payments.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:6;i:2;i:10;i:3;i:12;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:15:\"payments.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:6;i:2;i:10;i:3;i:12;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:19:\"communications.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:21:\"communications.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:14:\"logistics.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:7;i:2;i:8;i:3;i:12;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:16:\"logistics.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:7;i:2;i:12;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:13:\"delivery.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:7;i:2;i:8;i:3;i:12;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:15:\"delivery.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:7;i:2;i:8;i:3;i:12;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:15:\"settlement.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:17:\"settlement.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:12:\"reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:10;i:5;i:11;i:6;i:12;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:14:\"reports.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:11;i:3;i:12;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:14:\"orders.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:12;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:11:\"orders.book\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:12;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:14:\"orders.convert\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:12;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:18:\"payments.reconcile\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:6;i:2;i:10;i:3;i:12;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:16:\"settlement.entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:7;i:2;i:8;i:3;i:12;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:13:\"create orders\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:4;i:2;i:12;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:18:\"manage settlements\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:16:\"dashboard.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:14:\"dashboard.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:17:\"organization.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:11;i:2;i:12;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:19:\"organization.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:17:\"organization.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:19:\"organization.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:14:\"masters.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:12:\"masters.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:13:\"products.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:9;i:4;i:12;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:15:\"products.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:13:\"products.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:15:\"products.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:14:\"customers.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:9;i:4;i:12;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:16:\"customers.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:14:\"customers.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:16:\"customers.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:17:\"price-master.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:12;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:19:\"price-master.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:17:\"price-master.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:19:\"price-master.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:13:\"orders.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:12;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:11:\"orders.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:12;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:15:\"quotations.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:17:\"quotations.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:15:\"quotations.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:17:\"quotations.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:16:\"inventory.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:14:\"inventory.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:10:\"stock.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:5;i:2;i:9;i:3;i:12;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:12:\"stock.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:10:\"stock.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:12:\"stock.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:14:\"purchases.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:5;i:2;i:9;i:3;i:12;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:16:\"purchases.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:9;i:2;i:12;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:14:\"purchases.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:9;i:2;i:12;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:16:\"purchases.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:9;i:2;i:12;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:20:\"purchase-orders.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:9;i:2;i:12;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:22:\"purchase-orders.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:9;i:2;i:12;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:20:\"purchase-orders.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:9;i:2;i:12;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:22:\"purchase-orders.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:9;i:2;i:12;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:12:\"sales.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:10:\"sales.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:13:\"invoices.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:6;i:4;i:10;i:5;i:12;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:15:\"invoices.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:13:\"invoices.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:15:\"invoices.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:15:\"payments.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:6;i:2;i:10;i:3;i:12;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:13:\"payments.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:6;i:2;i:10;i:3;i:12;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:12:\"cheques.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:14:\"cheques.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:12:\"cheques.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:14:\"cheques.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:17:\"credit-notes.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:19:\"credit-notes.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:17:\"credit-notes.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:19:\"credit-notes.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:85;a:4:{s:1:\"a\";i:86;s:1:\"b\";s:21:\"communications.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:86;a:4:{s:1:\"a\";i:87;s:1:\"b\";s:19:\"communications.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:87;a:4:{s:1:\"a\";i:88;s:1:\"b\";s:16:\"logistics.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:88;a:4:{s:1:\"a\";i:89;s:1:\"b\";s:14:\"logistics.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:89;a:4:{s:1:\"a\";i:90;s:1:\"b\";s:15:\"delivery.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:90;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:13:\"delivery.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:91;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:17:\"settlement.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:92;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:15:\"settlement.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:6;i:2;i:12;}}i:93;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:10:\"deals.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:11;i:2;i:12;}}i:94;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:12:\"deals.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:95;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:10:\"deals.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:96;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:12:\"deals.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:97;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:12:\"targets.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:11;i:2;i:12;}}i:98;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:14:\"targets.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:99;a:4:{s:1:\"a\";i:100;s:1:\"b\";s:12:\"targets.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:100;a:4:{s:1:\"a\";i:101;s:1:\"b\";s:14:\"targets.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:101;a:4:{s:1:\"a\";i:102;s:1:\"b\";s:12:\"schemes.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:11;i:2;i:12;}}i:102;a:4:{s:1:\"a\";i:103;s:1:\"b\";s:14:\"schemes.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:103;a:4:{s:1:\"a\";i:104;s:1:\"b\";s:12:\"schemes.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:104;a:4:{s:1:\"a\";i:105;s:1:\"b\";s:14:\"schemes.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:105;a:4:{s:1:\"a\";i:106;s:1:\"b\";s:13:\"interest.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:106;a:4:{s:1:\"a\";i:107;s:1:\"b\";s:15:\"interest.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:107;a:4:{s:1:\"a\";i:108;s:1:\"b\";s:13:\"interest.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:108;a:4:{s:1:\"a\";i:109;s:1:\"b\";s:15:\"interest.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}i:109;a:4:{s:1:\"a\";i:110;s:1:\"b\";s:9:\"hrms.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:110;a:4:{s:1:\"a\";i:111;s:1:\"b\";s:11:\"hrms.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:111;a:4:{s:1:\"a\";i:112;s:1:\"b\";s:9:\"hrms.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:112;a:4:{s:1:\"a\";i:113;s:1:\"b\";s:11:\"hrms.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:113;a:4:{s:1:\"a\";i:114;s:1:\"b\";s:8:\"crm.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:114;a:4:{s:1:\"a\";i:115;s:1:\"b\";s:10:\"crm.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:115;a:4:{s:1:\"a\";i:116;s:1:\"b\";s:8:\"crm.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:116;a:4:{s:1:\"a\";i:117;s:1:\"b\";s:10:\"crm.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:117;a:4:{s:1:\"a\";i:118;s:1:\"b\";s:10:\"tally.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:118;a:4:{s:1:\"a\";i:119;s:1:\"b\";s:12:\"tally.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:119;a:4:{s:1:\"a\";i:120;s:1:\"b\";s:10:\"tally.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:120;a:4:{s:1:\"a\";i:121;s:1:\"b\";s:12:\"tally.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:121;a:4:{s:1:\"a\";i:122;s:1:\"b\";s:14:\"reports.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:122;a:4:{s:1:\"a\";i:123;s:1:\"b\";s:12:\"reports.edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:123;a:4:{s:1:\"a\";i:124;s:1:\"b\";s:22:\"orders.override-credit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:124;a:4:{s:1:\"a\";i:125;s:1:\"b\";s:17:\"orders.back-order\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:125;a:4:{s:1:\"a\";i:126;s:1:\"b\";s:22:\"quotations.view-profit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:12;}}i:126;a:4:{s:1:\"a\";i:127;s:1:\"b\";s:16:\"cheques.unfreeze\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:10;i:2;i:12;}}}s:5:\"roles\";a:12:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"owner\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:11:\"super-admin\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:13:\"sales-manager\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:11:\"salesperson\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:9:\"warehouse\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:7:\"finance\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:6:\"driver\";s:1:\"c\";s:3:\"web\";}i:7;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:15:\"delivery-person\";s:1:\"c\";s:3:\"web\";}i:8;a:3:{s:1:\"a\";i:9;s:1:\"b\";s:8:\"purchase\";s:1:\"c\";s:3:\"web\";}i:9;a:3:{s:1:\"a\";i:10;s:1:\"b\";s:8:\"accounts\";s:1:\"c\";s:3:\"web\";}i:10;a:3:{s:1:\"a\";i:11;s:1:\"b\";s:10:\"management\";s:1:\"c\";s:3:\"web\";}i:11;a:3:{s:1:\"a\";i:12;s:1:\"b\";s:12:\"client-admin\";s:1:\"c\";s:3:\"web\";}}}', 1791112148);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `brand_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'jeans', '001', 1, '2026-09-09 11:22:47', '2026-09-09 11:22:47'),
(2, 2, 'phone', '002', 1, '2026-09-10 09:25:46', '2026-09-10 09:25:46'),
(3, 2, 'earphone', '003', 1, '2026-09-10 09:25:59', '2026-09-10 09:25:59'),
(4, 2, 'adapter', '004', 1, '2026-09-10 09:26:14', '2026-09-10 09:26:14'),
(5, 2, 'cable', '005', 1, '2026-09-10 09:26:33', '2026-09-10 09:26:33'),
(6, 6, 'Widgets', 'CAT-FA0765', 1, '2026-09-17 06:20:58', '2026-09-17 06:20:58'),
(7, 7, 'Sony Professional Display', 'spd', 1, '2026-09-25 11:14:22', '2026-09-25 11:14:22'),
(8, 7, 'Sony Professional Display', 'spda', 1, '2026-09-25 11:14:57', '2026-09-25 11:14:57'),
(9, 7, 'Sony Retail', 'SR', 1, '2026-09-25 11:17:53', '2026-09-25 11:17:53'),
(10, 7, 'Sony Audio', 'SA', 1, '2026-09-25 11:18:32', '2026-09-25 11:18:32'),
(11, 7, 'Sony Accessories', 'saa', 1, '2026-09-25 11:19:03', '2026-09-25 11:19:03'),
(12, 7, 'Sony Projector', 'SP', 1, '2026-09-25 11:19:37', '2026-09-25 11:19:37'),
(13, 14, 'VIEWSONIC PROJECTORS', 'VP', 1, '2026-10-02 10:48:54', '2026-10-02 10:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `cheques`
--

CREATE TABLE `cheques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cheque_no` varchar(50) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `purpose` enum('security','pdc') NOT NULL DEFAULT 'pdc',
  `direction` enum('received_from_client','received_from_vendor','issued_to_vendor') NOT NULL DEFAULT 'received_from_client',
  `amount` decimal(14,2) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `deposit_date` date DEFAULT NULL,
  `clearance_date` date DEFAULT NULL,
  `status` enum('pending','deposited','cleared','bounced','cancelled') NOT NULL DEFAULT 'pending',
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bounce_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `bounced_at` timestamp NULL DEFAULT NULL,
  `bounce_reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cheques`
--

INSERT INTO `cheques` (`id`, `cheque_no`, `customer_id`, `purpose`, `direction`, `amount`, `bank_name`, `branch_name`, `cheque_date`, `deposit_date`, `clearance_date`, `status`, `payment_id`, `bounce_count`, `bounced_at`, `bounce_reason`, `notes`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, '147548', 4, 'security', 'received_from_vendor', 10000.00, 'hdfc bank', 'panjim', '2026-09-10', '2026-09-10', NULL, 'bounced', NULL, 1, '2026-09-10 10:51:37', 'invalid chq', NULL, 9, '2026-09-10 10:50:56', '2026-09-10 10:51:37');

-- --------------------------------------------------------

--
-- Table structure for table `cheque_bounces`
--

CREATE TABLE `cheque_bounces` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cheque_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `bounced_on` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bounce_number` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `triggered_freeze` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cheque_bounces`
--

INSERT INTO `cheque_bounces` (`id`, `cheque_id`, `customer_id`, `bounced_on`, `reason`, `charges`, `bounce_number`, `triggered_freeze`, `recorded_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 4, '2026-09-10', 'invalid chq', 0.00, 1, 0, 9, NULL, '2026-09-10 10:51:37', '2026-09-10 10:51:37');

-- --------------------------------------------------------

--
-- Table structure for table `communication_logs`
--

CREATE TABLE `communication_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'sent',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `sent_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `communication_logs`
--

INSERT INTO `communication_logs` (`id`, `invoice_id`, `customer_id`, `type`, `recipient`, `status`, `payload`, `sent_by`, `created_at`, `updated_at`) VALUES
(1, 15, 6, 'whatsapp_invoice', '9876528217', 'sent', '{\"invoice_no\":\"INV-20260917-0001\",\"grand_total\":\"88851.00\",\"message\":\"Invoice INV-20260917-0001 for \\u20b988,851.00 is ready. Pay: https:\\/\\/dms.extraaaz.com\\/pay\\/Z20h7PLpclYbUxzwRxKF1jeG9P6g1RNP\",\"channel\":\"whatsapp\",\"provider\":\"log_fallback\"}', 9, '2026-09-17 06:38:55', '2026-09-17 06:38:55'),
(2, 15, 6, 'payment_reminder', '9876528217', 'sent', '{\"invoice_no\":\"INV-20260917-0001\",\"outstanding\":88851,\"message\":\"Reminder: Invoice INV-20260917-0001 has outstanding \\u20b988,851.00. Due: 17 Sep 2026.\",\"channel\":\"whatsapp\",\"provider\":\"log_fallback\"}', 9, '2026-09-17 06:39:04', '2026-09-17 06:39:04'),
(3, 17, 7, 'whatsapp_invoice', '7458965489', 'sent', '{\"invoice_no\":\"INV-20260919-0001\",\"grand_total\":\"46.20\",\"message\":\"Invoice INV-20260919-0001 for \\u20b946.20 is ready. Pay: https:\\/\\/dms.extraaaz.com\\/pay\\/Ljdh5rKMQVqu4cuJJ2h4u4PX39ZCESPR\",\"channel\":\"whatsapp\",\"provider\":\"log_fallback\"}', 9, '2026-09-25 10:47:20', '2026-09-25 10:47:20'),
(4, 17, 7, 'whatsapp_invoice', '7458965489', 'sent', '{\"invoice_no\":\"INV-20260919-0001\",\"grand_total\":\"46.20\",\"message\":\"Invoice INV-20260919-0001 for \\u20b946.20 is ready. Pay: https:\\/\\/dms.extraaaz.com\\/pay\\/Ljdh5rKMQVqu4cuJJ2h4u4PX39ZCESPR\",\"channel\":\"whatsapp\",\"provider\":\"log_fallback\"}', 9, '2026-09-25 10:47:23', '2026-09-25 10:47:23'),
(5, 17, 7, 'whatsapp_invoice', '7458965489', 'sent', '{\"invoice_no\":\"INV-20260919-0001\",\"grand_total\":\"46.20\",\"message\":\"Invoice INV-20260919-0001 for \\u20b946.20 is ready. Pay: https:\\/\\/dms.extraaaz.com\\/pay\\/Ljdh5rKMQVqu4cuJJ2h4u4PX39ZCESPR\",\"channel\":\"whatsapp\",\"provider\":\"log_fallback\"}', 9, '2026-09-25 10:47:27', '2026-09-25 10:47:27'),
(6, 17, 7, 'whatsapp_invoice', '7458965489', 'sent', '{\"invoice_no\":\"INV-20260919-0001\",\"grand_total\":\"46.20\",\"message\":\"Invoice INV-20260919-0001 for \\u20b946.20 is ready. Pay: https:\\/\\/dms.extraaaz.com\\/pay\\/Ljdh5rKMQVqu4cuJJ2h4u4PX39ZCESPR\",\"channel\":\"whatsapp\",\"provider\":\"log_fallback\"}', 9, '2026-09-25 10:47:30', '2026-09-25 10:47:30');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_group_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `cin` varchar(30) DEFAULT NULL,
  `tan` varchar(20) DEFAULT NULL,
  `udyam_registration_no` varchar(30) DEFAULT NULL,
  `msme_category` enum('none','micro','small','medium') NOT NULL DEFAULT 'none',
  `msme_registration_no` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `bank_ifsc` varchar(20) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `additional_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_details`)),
  `purchase_terms_and_conditions` text DEFAULT NULL,
  `selling_terms_and_conditions` text DEFAULT NULL,
  `due_date_basis` enum('invoice_date','inward_date') NOT NULL DEFAULT 'invoice_date',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `business_group_id`, `name`, `code`, `logo_path`, `legal_name`, `gstin`, `pan`, `cin`, `tan`, `udyam_registration_no`, `msme_category`, `msme_registration_no`, `address`, `city`, `state`, `pincode`, `country`, `phone`, `alternate_phone`, `email`, `website`, `bank_name`, `bank_account_no`, `bank_ifsc`, `upi_id`, `additional_details`, `purchase_terms_and_conditions`, `selling_terms_and_conditions`, `due_date_basis`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Avit Digital', 'MAIN', 'companies/1/branding/logo_1790682244_6abba484b9be7.jpeg', 'Avit Digital', '03ABGPG1954N1ZN', 'ABGPG1954N', NULL, 'PTLV14425F', 'UDYAM-PB-12-0146707', 'small', NULL, 'B-19/463 Sangat Road Civil Lines Ludhiana -141001', 'LUDHIANA', 'Punjab', '141001', 'India', '8264240288', '9876120738', 'INFO@AVITDIGITAL.IN', 'www.avitdigital.in', 'HDFC BANK LTD.', '50200088458819', 'HDFC0001390', 'avitdigital19@okhdfcbank', NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.', '1. Goods once sold are subject to company policy.\r\n2. Payment within 30 days.\r\n3. Warranty as applicable.', 'invoice_date', 1, '2026-09-08 06:59:11', '2026-09-29 13:32:26'),
(2, 1, 'Sam manufacturing', '002', NULL, 'sameer', NULL, NULL, NULL, NULL, NULL, 'none', NULL, NULL, NULL, 'goa', NULL, 'India', '9457845748', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.', '1. Goods once sold are subject to company policy.\r\n2. Payment within 30 days.\r\n3. Warranty as applicable.', 'invoice_date', 0, '2026-09-10 09:18:11', '2026-09-26 09:16:46'),
(3, 2, 'Test Trade', '9359', NULL, 'Test Name', '05AAAPG7885R002', 'ubaefuod', '6846813546835', '6546865465', '7459616566', 'none', '8156546254655', 'Manorath , Chaitanyawadi , Malkapur', NULL, 'Maharashtra', '443101', 'India', '7458665485', NULL, 'nishadpatil97859@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.', '1. Goods once sold are subject to company policy.\r\n2. Payment within 30 days.\r\n3. Warranty as applicable.', 'invoice_date', 0, '2026-09-17 05:32:25', '2026-09-25 10:15:30'),
(4, NULL, 'AVIT DIGITAL', 'AD', NULL, 'AVIT DIGITAL', '03ABGPG1954N1ZN', 'ABGPG1954N', NULL, 'PTLV14425F', 'UDYAM-PB-12-0146707', 'small', NULL, 'B-19/463 Sangat Road Civil Lines Ludhiana -141001', NULL, 'Punjab', '141001', 'India', '9876120738', NULL, 'info@avitdigital.in', 'www.avitdigital.in', 'HDFC BANK LTD.', '50200088458819', 'HDFC0001390', 'avitdigital19@okhdfcbank', '{\"contacts\":[{\"phone\":\"8264240288\",\"email\":\"accounts@avitdigital.in\",\"website\":null,\"state\":\"Punjab\",\"pincode\":\"141001\",\"address\":null}],\"bank_accounts\":[{\"bank_name\":\"HDFC BANK LTD. OD ACCOUNT\",\"bank_account_no\":\"50200115516401\",\"bank_ifsc\":\"HDFC0001390\",\"upi_id\":null},{\"bank_name\":\"IDFC FIRST Bank (India)\",\"bank_account_no\":\"CLFMI2010100000231\",\"bank_ifsc\":\"6917501980\",\"upi_id\":null}]}', NULL, '1.	Payment is due as per agreed payment terms.\r\n2.	Goods once sold will not be taken back.\r\n3.	Interest @2% per month will be charged on overdue invoices.\r\n4.	Warranty/Guarantee as per OEM.\r\n5.	Delivery charges extra, if applicable.\r\n6.	Kindly check goods at the time of delivery; no claims will be entertained thereafter.\r\n7.	Cheque dishonour charges ₹500 per cheque or as charged by bank, whichever is higher.\r\n8.	All disputes are subject to Ludhiana jurisdiction only.', 'invoice_date', 1, '2026-09-25 10:41:40', '2026-09-29 10:05:45');

-- --------------------------------------------------------

--
-- Table structure for table `company_group_links`
--

CREATE TABLE `company_group_links` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `business_group_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_group_links`
--

INSERT INTO `company_group_links` (`id`, `business_group_id`, `company_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(2, 2, 2, '2026-09-10 09:24:17', '2026-09-10 09:24:17');

-- --------------------------------------------------------

--
-- Table structure for table `credit_notes`
--

CREATE TABLE `credit_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `credit_note_no` varchar(30) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `credit_note_date` date NOT NULL,
  `reason` enum('return','price','scheme','damage','settlement','interest_reversal','other') NOT NULL DEFAULT 'other',
  `status` enum('draft','approved','posted','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `affects_stock` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by_name` varchar(100) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_note_items`
--

CREATE TABLE `credit_note_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `credit_note_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uom_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `party_type` enum('dealer','customer','supplier','both') NOT NULL DEFAULT 'customer',
  `customer_type_id` bigint(20) UNSIGNED NOT NULL,
  `area_id` bigint(20) UNSIGNED DEFAULT NULL,
  `route_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salesperson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sales_manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `shipping_name` varchar(255) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_pincode` varchar(12) DEFAULT NULL,
  `shipping_gstin` varchar(20) DEFAULT NULL,
  `credit_limit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 18.00,
  `credit_period_basis` enum('monthly','quarterly','yearly','cumulative') NOT NULL DEFAULT 'cumulative',
  `payment_terms` varchar(255) DEFAULT NULL,
  `credit_status` enum('open','restricted','frozen') NOT NULL DEFAULT 'open',
  `risk_status` enum('normal','watch','high') NOT NULL DEFAULT 'normal',
  `cheque_bounce_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `credit_notes` text DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `company_id`, `branch_id`, `name`, `code`, `party_type`, `customer_type_id`, `area_id`, `route_id`, `salesperson_id`, `sales_manager_id`, `phone`, `email`, `address`, `state`, `pincode`, `shipping_name`, `shipping_address`, `shipping_state`, `shipping_pincode`, `shipping_gstin`, `credit_limit`, `credit_days`, `interest_rate`, `credit_period_basis`, `payment_terms`, `credit_status`, `risk_status`, `cheque_bounce_count`, `credit_notes`, `gstin`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Metro Retail Mart', 'CUST-001', 'customer', 1, 1, 1, 4, NULL, '9876534270', 'metro.retail.mart@example.com', 'Demo address, Distribution city', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 1, 1, 'Green Valley Wholesalers', 'CUST-002', 'customer', 2, 1, 1, 4, NULL, '9876517725', 'green.valley.wholesalers@example.com', 'Demo address, Distribution city', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 1, 1, 'City Stores Pvt Ltd', 'CUST-003', 'customer', 3, 1, 2, 4, NULL, '9876588294', 'city.stores.pvt.ltd@example.com', 'Demo address, Distribution city', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 1, 1, 'Bulk Foods Depot', 'CUST-004', 'customer', 4, 1, 1, 4, NULL, '9876588658', 'bulk.foods.depot@example.com', 'Demo address, Distribution city', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 1, NULL, '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-09-10 10:51:37'),
(5, 1, 1, 'Sunrise Kirana', 'CUST-005', 'customer', 1, 1, 2, 4, NULL, '9876579871', 'sunrise.kirana@example.com', 'Demo address, Distribution city', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 1, 1, 'Prime Distributors', 'CUST-006', 'customer', 2, 1, 1, 4, NULL, '9876528217', 'prime.distributors@example.com', 'Demo address, Distribution city', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(7, 1, 1, 'Patil Wholeale Mart', '745996', 'customer', 2, 1, 1, 3, NULL, '7458965489', 'pwm@gmail.com', 'suifodoisjdbiosd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, '27AEOPH5168Q1ZM', 1, '2026-09-01 06:00:12', '2026-09-01 06:00:12'),
(8, 1, 1, 'creative store', 'cs001', 'customer', 2, NULL, NULL, NULL, NULL, '8457845784', 'creativestore@gmail.com', NULL, 'delhi', '800708', NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, NULL, 1, '2026-09-02 07:46:44', '2026-09-02 07:46:44'),
(9, 1, 1, 'Test Customer', 'TST - 01', 'customer', 4, 1, 1, 4, NULL, '7458965485', 'nishadpatil9359@gmail.com', 'Manorath , Chaitanyawadi , Malkapur', 'Maharashtra', '443101', NULL, 'Manorath , Chaitanyawadi , Malkapur', 'Maharashtra', '443101', '27AAAPA1234A1Z5', 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, '29ABCDE1234F1Z5', 1, '2026-09-03 09:14:56', '2026-09-03 09:14:56'),
(10, 1, 1, 'dongas', 'CPC001', 'supplier', 4, 1, 2, 4, NULL, '9478457845', 'DONGAS@GMAIL.COM', NULL, 'MAHARASHTRA', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 30, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, NULL, 1, '2026-09-09 11:26:58', '2026-09-09 11:26:58'),
(11, NULL, NULL, 'deepak dealer', '101', 'dealer', 4, NULL, NULL, 3, NULL, '8475484561', 'dd@gmail.com', NULL, 'karnataka', NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, NULL, 1, '2026-09-10 09:32:58', '2026-09-10 09:32:58'),
(12, 1, NULL, 'Sample Distributor', 'PTY-1-00001', 'customer', 5, 3, 3, NULL, NULL, '9999999999', 'ops@sample.co', '#12, MG Road', 'Karnataka', '560001', NULL, NULL, NULL, NULL, NULL, 50000.00, 30, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, NULL, 1, '2026-09-19 06:42:15', '2026-09-19 06:42:15'),
(13, 1, NULL, 'Sample Distributor', 'PTY-1-00002', 'customer', 5, 3, 3, NULL, NULL, '9999999999', 'ops@sample.co', '#12, MG Road', 'Karnataka', '560001', NULL, NULL, NULL, NULL, NULL, 50000.00, 30, 18.00, 'cumulative', NULL, 'open', 'normal', 0, NULL, NULL, 1, '2026-09-21 05:04:14', '2026-09-21 05:04:14');

-- --------------------------------------------------------

--
-- Table structure for table `customer_types`
--

CREATE TABLE `customer_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer_types`
--

INSERT INTO `customer_types` (`id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Retailer', 'RET', 'Small retail outlets', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'Wholesaler', 'WHO', 'Wholesale distributors', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'Company', 'COM', 'Corporate accounts', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 'Bulk', 'BLK', 'Bulk buyers with tier pricing', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 'Retail', 'RETAIL', NULL, 1, '2026-09-19 06:42:15', '2026-09-19 06:42:15'),
(6, 'OFFICE AUTOMATION PARTNER', 'OFFICEAUTO', NULL, 1, '2026-09-30 09:59:43', '2026-09-30 09:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `deals`
--

CREATE TABLE `deals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reference` varchar(40) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `status` enum('draft','active','closed','cancelled') NOT NULL DEFAULT 'draft',
  `sale_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `landed_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `deal_cost_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net_margin` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deals`
--

INSERT INTO `deals` (`id`, `reference`, `customer_id`, `invoice_id`, `order_id`, `site_name`, `status`, `sale_amount`, `landed_cost`, `discount_amount`, `deal_cost_total`, `net_margin`, `notes`, `created_by_name`, `created_at`, `updated_at`) VALUES
(1, 'DEAL-20260910-0001', 10, NULL, NULL, NULL, 'draft', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 'Client Admin', '2026-09-10 10:55:15', '2026-09-10 10:55:15');

-- --------------------------------------------------------

--
-- Table structure for table `deal_expenses`
--

CREATE TABLE `deal_expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `deal_id` bigint(20) UNSIGNED NOT NULL,
  `expense_type_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','rejected','posted') NOT NULL DEFAULT 'draft',
  `created_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `load_sheet_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','out_for_delivery','delivered','partial','returned') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `load_sheet_id`, `customer_id`, `invoice_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, 'delivered', '2026-08-29 04:40:02', '2026-09-03 09:39:46'),
(2, 1, 2, 2, 'pending', '2026-08-29 04:40:02', '2026-08-29 04:40:02'),
(3, 2, 3, 10, 'out_for_delivery', '2026-09-03 09:40:55', '2026-09-03 09:40:58'),
(4, 3, 1, 3, 'out_for_delivery', '2026-09-10 10:48:33', '2026-09-10 10:48:38'),
(5, 3, 3, 10, 'out_for_delivery', '2026-09-10 10:48:33', '2026-09-10 10:48:38');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_items`
--

CREATE TABLE `delivery_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `delivery_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `loaded_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `delivered_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `short_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `returned_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_items`
--

INSERT INTO `delivery_items` (`id`, `delivery_id`, `product_id`, `uom_id`, `loaded_qty`, `delivered_qty`, `short_qty`, `returned_qty`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, 10.0000, 10.0000, 0.0000, 0.0000, '2026-08-29 04:40:02', '2026-08-29 04:40:02'),
(2, 2, 2, 5, 24.0000, 0.0000, 0.0000, 0.0000, '2026-08-29 04:40:02', '2026-08-29 04:40:02'),
(3, 3, 2, 5, 3.0000, 0.0000, 0.0000, 0.0000, '2026-09-03 09:40:55', '2026-09-03 09:40:55'),
(4, 4, 1, 4, 50.0000, 0.0000, 0.0000, 0.0000, '2026-09-10 10:48:33', '2026-09-10 10:48:33'),
(5, 5, 2, 5, 3.0000, 0.0000, 0.0000, 0.0000, '2026-09-10 10:48:33', '2026-09-10 10:48:33');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_persons`
--

CREATE TABLE `delivery_persons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_persons`
--

INSERT INTO `delivery_persons` (`id`, `name`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Suresh Nair', '9876500002', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `demo_stock_notices`
--

CREATE TABLE `demo_stock_notices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `batch_no` varchar(60) DEFAULT NULL,
  `notice_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `status` enum('out','returned','converted','cancelled') NOT NULL DEFAULT 'out',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `company_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Sales', 'SALES', 1, '2026-09-10 11:57:44', '2026-09-10 11:57:44'),
(2, 1, 'Operations', 'OPS', 1, '2026-09-10 11:57:44', '2026-09-10 11:57:44'),
(3, 1, 'Finance', 'FIN', 1, '2026-09-10 11:57:44', '2026-09-10 11:57:44'),
(4, 1, 'HR', 'HR', 1, '2026-09-10 11:57:44', '2026-09-10 11:57:44');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `company_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Manager', 'MGR', 1, '2026-09-10 11:57:44', '2026-09-10 11:57:44'),
(2, 1, 'Executive', 'EXE', 1, '2026-09-10 11:57:44', '2026-09-10 11:57:44'),
(3, 1, 'Salesperson', 'SP', 1, '2026-09-10 11:57:44', '2026-09-10 11:57:44'),
(4, 1, 'Store Keeper', 'SK', 1, '2026-09-10 11:57:44', '2026-09-10 11:57:44');

-- --------------------------------------------------------

--
-- Table structure for table `document_sequences`
--

CREATE TABLE `document_sequences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `financial_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `prefix` varchar(20) NOT NULL,
  `sequence_date` date NOT NULL,
  `last_number` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_sequences`
--

INSERT INTO `document_sequences` (`id`, `company_id`, `branch_id`, `financial_year_id`, `prefix`, `sequence_date`, `last_number`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, 'ORD', '2026-09-03', 2, '2026-09-03 07:50:49', '2026-09-03 09:22:19'),
(2, NULL, NULL, NULL, 'INV', '2026-09-03', 1, '2026-09-03 07:50:55', '2026-09-03 07:50:55'),
(4, NULL, NULL, NULL, 'PO', '2026-09-10', 1, '2026-09-10 09:41:02', '2026-09-10 09:41:02'),
(5, NULL, NULL, NULL, 'GRN', '2026-09-10', 1, '2026-09-10 09:41:55', '2026-09-10 09:41:55'),
(6, NULL, NULL, NULL, 'PI', '2026-09-10', 1, '2026-09-10 09:49:13', '2026-09-10 09:49:13'),
(7, NULL, NULL, NULL, 'FRT', '2026-09-10', 1, '2026-09-10 09:50:59', '2026-09-10 09:50:59'),
(8, NULL, NULL, NULL, 'LC', '2026-09-10', 1, '2026-09-10 09:51:27', '2026-09-10 09:51:27'),
(10, NULL, NULL, NULL, 'TRF', '2026-09-10', 1, '2026-09-10 09:59:32', '2026-09-10 09:59:32'),
(11, NULL, NULL, NULL, 'ADJ', '2026-09-10', 2, '2026-09-10 10:10:40', '2026-09-10 10:11:17'),
(12, NULL, NULL, NULL, 'ORD', '2026-09-10', 1, '2026-09-10 10:22:35', '2026-09-10 10:22:35'),
(13, NULL, NULL, NULL, 'QTN', '2026-09-10', 1, '2026-09-10 10:31:24', '2026-09-10 10:31:24'),
(17, NULL, NULL, NULL, 'DEAL', '2026-09-10', 1, '2026-09-10 10:55:15', '2026-09-10 10:55:15'),
(18, NULL, NULL, NULL, 'INV', '2026-09-10', 1, '2026-09-10 12:17:47', '2026-09-10 12:17:47'),
(19, NULL, NULL, NULL, 'PO', '2026-09-11', 1, '2026-09-11 07:37:18', '2026-09-11 07:37:18'),
(20, NULL, NULL, NULL, 'GRN', '2026-09-11', 1, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(21, NULL, NULL, NULL, 'PI', '2026-09-11', 1, '2026-09-11 07:40:05', '2026-09-11 07:40:05'),
(22, NULL, NULL, NULL, 'FRT', '2026-09-11', 1, '2026-09-11 07:48:08', '2026-09-11 07:48:08'),
(23, NULL, NULL, NULL, 'LC', '2026-09-11', 1, '2026-09-11 08:20:08', '2026-09-11 08:20:08'),
(24, NULL, NULL, NULL, 'PO', '2026-09-17', 3, '2026-09-17 05:36:23', '2026-09-17 07:18:57'),
(25, NULL, NULL, NULL, 'GRN', '2026-09-17', 2, '2026-09-17 05:36:33', '2026-09-17 07:19:15'),
(26, NULL, NULL, NULL, 'ORD', '2026-09-17', 1, '2026-09-17 06:06:58', '2026-09-17 06:06:58'),
(27, NULL, NULL, NULL, 'INV', '2026-09-17', 2, '2026-09-17 06:38:05', '2026-09-17 06:41:06'),
(28, NULL, NULL, NULL, 'LC', '2026-09-17', 2, '2026-09-17 07:19:33', '2026-09-17 07:23:54'),
(29, NULL, NULL, NULL, 'FRT', '2026-09-17', 2, '2026-09-17 07:23:39', '2026-09-17 11:30:34'),
(30, NULL, NULL, NULL, 'INV', '2026-09-19', 1, '2026-09-19 06:45:26', '2026-09-19 06:45:26'),
(31, NULL, NULL, NULL, 'PO', '2026-09-19', 1, '2026-09-19 06:51:08', '2026-09-19 06:51:08'),
(32, NULL, NULL, NULL, 'GRN', '2026-09-19', 1, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(33, NULL, NULL, NULL, 'PI', '2026-09-19', 1, '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(34, NULL, NULL, NULL, 'FRT', '2026-09-19', 1, '2026-09-19 07:01:00', '2026-09-19 07:01:00'),
(35, NULL, NULL, NULL, 'LC', '2026-09-19', 1, '2026-09-19 07:01:33', '2026-09-19 07:01:33'),
(36, NULL, NULL, NULL, 'PI', '2026-09-21', 4, '2026-09-21 05:33:11', '2026-09-21 11:23:37'),
(37, NULL, NULL, NULL, 'LC', '2026-09-21', 2, '2026-09-21 05:34:11', '2026-09-21 11:23:45'),
(38, NULL, NULL, NULL, 'PO', '2026-09-21', 1, '2026-09-21 07:47:20', '2026-09-21 07:47:20'),
(39, NULL, NULL, NULL, 'PI', '2026-09-25', 3, '2026-09-25 06:45:28', '2026-09-25 07:36:54'),
(40, NULL, NULL, NULL, 'FRT', '2026-09-25', 1, '2026-09-25 07:29:51', '2026-09-25 07:29:51'),
(41, NULL, NULL, NULL, 'LC', '2026-09-25', 1, '2026-09-25 07:30:14', '2026-09-25 07:30:14'),
(42, NULL, NULL, NULL, 'PO', '2026-09-25', 1, '2026-09-25 07:42:49', '2026-09-25 07:42:49'),
(43, NULL, NULL, NULL, 'PO', '2026-09-28', 2, '2026-09-28 08:40:08', '2026-09-28 08:43:17'),
(44, NULL, NULL, NULL, 'PI', '2026-09-28', 1, '2026-09-28 09:11:23', '2026-09-28 09:11:23'),
(45, NULL, NULL, NULL, 'ADJ', '2026-09-28', 1, '2026-09-28 09:56:07', '2026-09-28 09:56:07'),
(46, NULL, NULL, NULL, 'ORD', '2026-09-28', 1, '2026-09-28 09:58:24', '2026-09-28 09:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `license_no` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `phone`, `license_no`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Ravi Kumar', '9876500001', 'DL-IND-001', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `designation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_code` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `is_salesperson` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `company_id`, `branch_id`, `user_id`, `department_id`, `designation_id`, `manager_id`, `employee_code`, `name`, `email`, `phone`, `joining_date`, `date_of_birth`, `is_salesperson`, `status`, `address`, `created_at`, `updated_at`) VALUES
(1, 2, 2, NULL, NULL, NULL, NULL, '003', 'MOHIT', NULL, '9474845784', NULL, NULL, 1, 'active', NULL, '2026-09-10 11:07:27', '2026-09-10 11:07:27');

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` varchar(80) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `document_number` varchar(255) DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_exits`
--

CREATE TABLE `employee_exits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `resignation_date` date DEFAULT NULL,
  `last_working_date` date DEFAULT NULL,
  `notice_period_days` int(10) UNSIGNED DEFAULT NULL,
  `exit_type` varchar(40) NOT NULL DEFAULT 'resignation',
  `clearance_status` varchar(30) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_incentives`
--

CREATE TABLE `employee_incentives` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `period_label` varchar(50) DEFAULT NULL,
  `target_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achievement_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `incentive_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'provisional',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_kpis`
--

CREATE TABLE `employee_kpis` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `kpi_name` varchar(255) NOT NULL,
  `period_label` varchar(50) DEFAULT NULL,
  `target_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achievement_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `rating` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_approvals`
--

CREATE TABLE `expense_approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `deal_expense_id` bigint(20) UNSIGNED NOT NULL,
  `approver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver_name` varchar(100) DEFAULT NULL,
  `decision` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_claims`
--

CREATE TABLE `expense_claims` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `claim_date` date NOT NULL,
  `claim_type` varchar(80) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_types`
--

CREATE TABLE `expense_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `accounting_treatment` enum('trade_discount','deal_expense','landed_cost') NOT NULL DEFAULT 'deal_expense',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_types`
--

INSERT INTO `expense_types` (`id`, `name`, `code`, `accounting_treatment`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'installation', '0016', 'deal_expense', 1, NULL, '2026-09-10 10:56:03', '2026-09-10 10:56:03');

-- --------------------------------------------------------

--
-- Table structure for table `e_invoices`
--

CREATE TABLE `e_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','generated','manual') NOT NULL DEFAULT 'pending',
  `provider` varchar(30) NOT NULL DEFAULT 'mastersindia',
  `irn` varchar(255) DEFAULT NULL,
  `ack_no` varchar(40) DEFAULT NULL,
  `ack_date` timestamp NULL DEFAULT NULL,
  `signed_invoice` longtext DEFAULT NULL,
  `signed_qr_base64` longtext DEFAULT NULL,
  `qr_image_path` varchar(255) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `e_invoices`
--

INSERT INTO `e_invoices` (`id`, `invoice_id`, `status`, `provider`, `irn`, `ack_no`, `ack_date`, `signed_invoice`, `signed_qr_base64`, `qr_image_path`, `last_error`, `requested_at`, `payload`, `created_at`, `updated_at`) VALUES
(1, 3, 'manual', 'mastersindia', 'STUB-IRN-98E9252A2865', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"stub\":true,\"generated_at\":\"2026-09-01T05:27:50+00:00\"}', '2026-09-01 05:27:50', '2026-09-01 05:27:50'),
(2, 6, 'manual', 'mastersindia', 'STUB-IRN-F5E5C53C3D58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"stub\":true,\"generated_at\":\"2026-09-01T06:46:47+00:00\"}', '2026-09-01 06:46:47', '2026-09-01 06:46:47'),
(3, 9, 'manual', 'mastersindia', 'MANUAL-AB0FCD8E52BB0C71', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"generated_at\":\"2026-09-01T11:32:51+00:00\"}', '2026-09-01 11:32:51', '2026-09-01 11:32:51'),
(4, 10, 'manual', 'mastersindia', 'MANUAL-6FE8B5ABB3072A6F', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"generated_at\":\"2026-09-03T07:51:03+00:00\"}', '2026-09-03 07:51:03', '2026-09-03 07:51:03'),
(5, 14, 'manual', 'mastersindia', 'MANUAL-A936B91A5EE9E56D', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"generated_at\":\"2026-09-10T12:18:03+00:00\"}', '2026-09-10 12:18:03', '2026-09-10 12:18:03'),
(6, 15, 'manual', 'mastersindia', 'MANUAL-B13DFDB8270CD661', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"generated_at\":\"2026-09-17T06:52:00+00:00\"}', '2026-09-17 06:52:00', '2026-09-17 06:52:00'),
(7, 17, 'manual', 'mastersindia', 'MANUAL-04678363AD64A72C', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"generated_at\":\"2026-09-21T05:38:40+00:00\"}', '2026-09-21 05:38:40', '2026-09-21 05:38:40');

-- --------------------------------------------------------

--
-- Table structure for table `e_way_bills`
--

CREATE TABLE `e_way_bills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','generated','manual','stub') NOT NULL DEFAULT 'pending',
  `provider` varchar(30) NOT NULL DEFAULT 'mastersindia',
  `last_error` text DEFAULT NULL,
  `eway_bill_no` varchar(255) DEFAULT NULL,
  `qr_token` varchar(36) DEFAULT NULL,
  `valid_upto` timestamp NULL DEFAULT NULL,
  `ewb_date` timestamp NULL DEFAULT NULL,
  `distance_km` int(10) UNSIGNED DEFAULT NULL,
  `transporter_id` varchar(20) DEFAULT NULL,
  `transporter_name` varchar(255) DEFAULT NULL,
  `vehicle_no` varchar(40) DEFAULT NULL,
  `transport_mode` varchar(20) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `e_way_bills`
--

INSERT INTO `e_way_bills` (`id`, `invoice_id`, `status`, `provider`, `last_error`, `eway_bill_no`, `qr_token`, `valid_upto`, `ewb_date`, `distance_km`, `transporter_id`, `transporter_name`, `vehicle_no`, `transport_mode`, `payload`, `created_at`, `updated_at`) VALUES
(1, 3, 'manual', 'mastersindia', NULL, 'STUB-EWB-A4A0816E4A', '6c8fcfd0-5434-45bc-a792-7430b3f3dee8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"stub\":true,\"generated_at\":\"2026-09-01T05:27:53+00:00\"}', '2026-09-01 05:27:53', '2026-09-01 05:27:53'),
(2, 6, 'manual', 'mastersindia', NULL, 'STUB-EWB-3082BD5836', '43d9c4a0-7e91-4845-a487-50ac4540fc68', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"stub\":true,\"generated_at\":\"2026-09-01T06:46:55+00:00\"}', '2026-09-01 06:46:53', '2026-09-01 06:46:55'),
(3, 9, 'manual', 'mastersindia', NULL, 'STUB-EWB-2459162307', 'eeedfbba-db6c-473a-853b-c3e15570be83', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"stub\":true,\"generated_at\":\"2026-09-01T11:32:38+00:00\"}', '2026-09-01 11:32:38', '2026-09-01 11:32:38'),
(4, 17, 'stub', 'mastersindia', NULL, '76D5B4FC54D2', '23198209-8676-4a8a-af46-8e0c2f37302c', '2026-09-30 10:38:54', '2026-09-29 10:38:54', NULL, NULL, NULL, NULL, NULL, '{\"note\":\"Deterministic stub because Masters India credentials not configured; using deterministic stub.\"}', '2026-09-21 07:48:29', '2026-09-29 10:38:54');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_years`
--

CREATE TABLE `financial_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `financial_years`
--

INSERT INTO `financial_years` (`id`, `company_id`, `name`, `starts_on`, `ends_on`, `is_closed`, `is_current`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-27', '2026-04-01', '2027-03-31', 0, 1, '2026-09-08 06:59:11', '2026-09-21 06:35:54'),
(2, 2, '2026-27', '2026-04-01', '2027-03-31', 0, 0, '2026-09-10 09:23:02', '2026-09-25 10:19:49'),
(3, 4, 'ad', '2026-04-01', '2027-03-31', 0, 1, '2026-09-25 11:05:55', '2026-09-25 11:06:04');

-- --------------------------------------------------------

--
-- Table structure for table `freight_bills`
--

CREATE TABLE `freight_bills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `freight_no` varchar(40) NOT NULL,
  `bill_date` date NOT NULL,
  `transporter_name` varchar(255) DEFAULT NULL,
  `vehicle_no` varchar(40) DEFAULT NULL,
  `lr_no` varchar(60) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `allocation_basis` varchar(20) NOT NULL DEFAULT 'value',
  `status` enum('draft','posted','allocated','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `freight_bills`
--

INSERT INTO `freight_bills` (`id`, `freight_no`, `bill_date`, `transporter_name`, `vehicle_no`, `lr_no`, `amount`, `tax_amount`, `total_amount`, `allocation_basis`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'FRT-20260910-0001', '2026-09-10', 'vrl logistics', 'mh49ow1432', 'LR0001', 10000.00, 18.00, 10018.00, 'value', 'allocated', NULL, 9, '2026-09-10 09:50:59', '2026-09-10 09:51:27'),
(2, 'FRT-20260911-0001', '2026-09-11', 'VRL', 'MH45GR3214', 'LR005', 15000.00, 0.00, 15000.00, 'value', 'allocated', NULL, 9, '2026-09-11 07:48:08', '2026-09-11 08:20:08'),
(3, 'FRT-20260917-0001', '2026-09-17', 'vrl logistics', 'MH25AD2568', 'LR0002', 10000.00, 0.00, 10000.00, 'qty', 'allocated', NULL, 9, '2026-09-17 07:23:39', '2026-09-17 07:23:54'),
(4, 'FRT-20260917-0002', '2026-09-17', 'vrl logistics', 'MH25AD2568', 'LR0001', 23111.00, 0.00, 23111.00, 'qty', 'posted', NULL, 9, '2026-09-17 11:30:34', '2026-09-17 11:30:34'),
(5, 'FRT-20260919-0001', '2026-09-19', 'Patil Transporters', 'MH25AD2568', '857545655', 500000.00, 10.00, 500010.00, 'volume', 'allocated', NULL, 9, '2026-09-19 07:01:00', '2026-09-19 07:01:33'),
(6, 'FRT-20260925-0001', '2026-09-25', 'truck', 'mh04 jn 8388', 'LR-01010010', 1000.00, 0.00, 1000.00, 'value', 'posted', NULL, 9, '2026-09-25 07:29:51', '2026-09-25 07:29:51');

-- --------------------------------------------------------

--
-- Table structure for table `freight_bill_allocations`
--

CREATE TABLE `freight_bill_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `freight_bill_id` bigint(20) UNSIGNED NOT NULL,
  `allocatable_type` varchar(255) DEFAULT NULL,
  `allocatable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `freight_bill_allocations`
--

INSERT INTO `freight_bill_allocations` (`id`, `freight_bill_id`, `allocatable_type`, `allocatable_id`, `amount`, `created_at`, `updated_at`) VALUES
(1, 1, 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 1, 10018.00, '2026-09-10 09:50:59', '2026-09-10 09:50:59'),
(2, 2, 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 2, 15000.00, '2026-09-11 07:48:08', '2026-09-11 07:48:08'),
(3, 3, 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 2, 10000.00, '2026-09-17 07:23:39', '2026-09-17 07:23:39'),
(4, 4, 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 1, 23111.00, '2026-09-17 11:30:34', '2026-09-17 11:30:34'),
(5, 5, 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 3, 500010.00, '2026-09-19 07:01:00', '2026-09-19 07:01:00'),
(6, 6, 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 3, 1000.00, '2026-09-25 07:29:51', '2026-09-25 07:29:51');

-- --------------------------------------------------------

--
-- Table structure for table `interest_documents`
--

CREATE TABLE `interest_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_no` varchar(40) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `interest_ledger_id` bigint(20) UNSIGNED DEFAULT NULL,
  `document_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_ledgers`
--

CREATE TABLE `interest_ledgers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `interest_rule_id` bigint(20) UNSIGNED DEFAULT NULL,
  `as_of_date` date NOT NULL,
  `overdue_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `overdue_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `annual_rate` decimal(8,2) NOT NULL DEFAULT 18.00,
  `interest_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('preview','posted','waived','reversed') NOT NULL DEFAULT 'preview',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interest_rules`
--

CREATE TABLE `interest_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `annual_rate` decimal(8,2) NOT NULL DEFAULT 18.00,
  `grace_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internal_delivery_challans`
--

CREATE TABLE `internal_delivery_challans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `challan_no` varchar(40) NOT NULL,
  `challan_date` date NOT NULL,
  `from_warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_transfer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `status` enum('draft','dispatched','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_no` varchar(30) NOT NULL,
  `qr_token` varchar(36) DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salesperson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `due_date_basis` enum('invoice_date','inward_date') NOT NULL DEFAULT 'invoice_date',
  `due_date_source_date` date DEFAULT NULL,
  `vehicle_no` varchar(40) DEFAULT NULL,
  `transport_mode` varchar(30) DEFAULT NULL,
  `reference_no` varchar(60) DEFAULT NULL,
  `delivery_state` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `credit_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('draft','issued','paid','partial','cancelled') NOT NULL DEFAULT 'issued',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `universal_discount_type` enum('percent','flat') NOT NULL DEFAULT 'flat',
  `universal_discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `item_discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms_and_conditions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `qr_token`, `customer_id`, `order_id`, `salesperson_id`, `invoice_date`, `due_date`, `due_date_basis`, `due_date_source_date`, `vehicle_no`, `transport_mode`, `reference_no`, `delivery_state`, `payment_terms`, `credit_days`, `status`, `subtotal`, `discount_amount`, `universal_discount_type`, `universal_discount_value`, `item_discount_total`, `tax_amount`, `grand_total`, `paid_amount`, `notes`, `terms_and_conditions`, `created_at`, `updated_at`) VALUES
(1, 'INV-0001', '0f47d499-75f7-404f-be30-0b2d51113e51', 3, 3, 4, '2026-08-29', NULL, 'invoice_date', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'paid', 1000.00, 0.00, 'flat', 0.00, 0.00, 50.00, 1050.00, 1050.00, 'Demo invoice from converted order', NULL, '2026-08-29 04:40:01', '2026-09-02 10:31:33'),
(2, 'INV-0002', 'bb3c6a71-f3bf-4c8b-8072-b4b22c5ed92c', 2, NULL, 4, '2026-08-29', NULL, 'invoice_date', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'paid', 3168.00, 0.00, 'flat', 0.00, 0.00, 380.16, 3548.16, 3548.16, 'Direct billing demo invoice', NULL, '2026-08-29 04:40:01', '2026-09-03 09:31:51'),
(3, 'INV-20260901-0001', '14c48434-4a90-4bf1-a73f-88f65a6d6ed6', 1, 1, 4, '2026-09-01', NULL, 'invoice_date', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'issued', 2600.00, 0.00, 'flat', 0.00, 0.00, 130.00, 2730.00, 0.00, 'Demo order', NULL, '2026-09-01 05:24:22', '2026-09-01 05:24:22'),
(6, 'INV-20260901-0002', 'a1960c90-986e-48fc-87d3-7c66a9af710c', 3, 8, 2, '2026-09-01', NULL, 'invoice_date', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'paid', 47.00, 0.00, 'flat', 0.00, 0.00, 2.35, 49.35, 49.35, 'jblb', NULL, '2026-09-01 06:27:04', '2026-09-01 06:27:34'),
(9, 'INV-20260901-0003', 'd7ffad92-e29d-4e0a-a01c-abf4b0851c9b', 3, 10, 2, '2026-09-01', NULL, 'invoice_date', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'paid', 470.00, 10.00, 'flat', 0.00, 0.00, 23.00, 483.00, 483.00, 'jgcut', NULL, '2026-09-01 11:32:35', '2026-09-01 11:33:19'),
(10, 'INV-20260903-0001', '0105c749-db81-48b5-b64a-e62c463988db', 3, 12, 2, '2026-09-03', NULL, 'invoice_date', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'partial', 384.00, 0.00, 'flat', 0.00, 0.00, 46.08, 430.08, 430.00, NULL, NULL, '2026-09-03 07:50:55', '2026-09-10 10:49:59'),
(14, 'INV-20260910-0001', 'caedb1b5-8dc3-4cf1-8f1e-6a6d8a02c315', 7, 14, 9, '2026-09-10', '2026-09-10', 'invoice_date', '2026-09-10', NULL, NULL, NULL, NULL, NULL, 0, 'issued', 8000000.00, 800000.00, 'flat', 0.00, 0.00, 1296000.00, 8496000.00, 0.00, NULL, NULL, '2026-09-10 12:17:47', '2026-09-10 12:17:47'),
(15, 'INV-20260917-0001', '038d98af-7797-437e-bf2c-2a76ac651c4c', 6, NULL, 9, '2026-09-17', '2026-09-17', 'invoice_date', '2026-09-17', 'MH01DK9867', '1', 'IN00557', 'Maharashtra', NULL, 0, 'issued', 80150.00, 5007.50, 'flat', 1000.00, 4007.50, 13708.50, 88851.00, 0.00, NULL, NULL, '2026-09-17 06:38:05', '2026-09-17 06:38:05'),
(16, 'INV-20260917-0002', 'e8dfce8e-7e95-4b62-886f-bc426cc12217', 3, 15, 9, '2026-09-17', '2026-09-17', 'invoice_date', '2026-09-17', NULL, NULL, NULL, NULL, NULL, 0, 'partial', 80128.00, 4006.40, 'flat', 0.00, 0.00, 13694.59, 89816.19, 50000.00, NULL, NULL, '2026-09-17 06:41:06', '2026-09-28 09:30:47'),
(17, 'INV-20260919-0001', 'f5492619-e047-412c-9c8a-df05fc7d9564', 7, NULL, 9, '2026-09-19', '2026-09-19', 'invoice_date', '2026-09-19', NULL, NULL, NULL, NULL, NULL, 0, 'paid', 44.00, 0.00, 'flat', 0.00, 0.00, 2.20, 46.20, 46.20, NULL, NULL, '2026-09-19 06:45:26', '2026-09-21 05:47:30');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('percent','flat') NOT NULL DEFAULT 'flat',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hsn_code` varchar(20) DEFAULT NULL,
  `batch_no` varchar(60) DEFAULT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `uom_id`, `quantity`, `unit_price`, `discount_amount`, `discount_type`, `discount_value`, `hsn_code`, `batch_no`, `tax_amount`, `line_total`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, 10.0000, 100.00, 0.00, 'flat', 0.00, NULL, NULL, 50.00, 1000.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 2, 2, 5, 24.0000, 132.00, 0.00, 'flat', 0.00, NULL, NULL, 380.16, 3168.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 3, 1, 4, 50.0000, 52.00, 0.00, 'flat', 0.00, NULL, NULL, 0.00, 2600.00, '2026-09-01 05:24:22', '2026-09-01 05:24:22'),
(6, 6, 1, 4, 1.0000, 47.00, 0.00, 'flat', 0.00, NULL, NULL, 0.00, 47.00, '2026-09-01 06:27:04', '2026-09-01 06:27:04'),
(9, 9, 1, 4, 10.0000, 47.00, 0.00, 'flat', 0.00, NULL, NULL, 0.00, 470.00, '2026-09-01 11:32:35', '2026-09-01 11:32:35'),
(10, 10, 2, 5, 3.0000, 128.00, 0.00, 'flat', 0.00, NULL, NULL, 46.08, 384.00, '2026-09-03 07:50:55', '2026-09-03 07:50:55'),
(14, 14, 9, 2, 100.0000, 80000.00, 800000.00, 'flat', 0.00, NULL, NULL, 1296000.00, 8000000.00, '2026-09-10 12:17:47', '2026-09-10 12:17:47'),
(15, 15, 7, 2, 1.0000, 150.00, 7.50, 'percent', 5.00, '8484', NULL, 28.50, 150.00, '2026-09-17 06:38:05', '2026-09-17 06:38:05'),
(16, 15, 9, 2, 1.0000, 80000.00, 4000.00, 'percent', 5.00, NULL, NULL, 13680.00, 80000.00, '2026-09-17 06:38:05', '2026-09-17 06:38:05'),
(17, 16, 9, 2, 1.0000, 80000.00, 4000.00, 'flat', 0.00, NULL, NULL, 13672.71, 80000.00, '2026-09-17 06:41:06', '2026-09-17 06:41:06'),
(18, 16, 2, 5, 1.0000, 128.00, 6.40, 'flat', 0.00, NULL, NULL, 21.88, 128.00, '2026-09-17 06:41:06', '2026-09-17 06:41:06'),
(19, 17, 6, 1, 1.0000, 22.00, 0.00, 'percent', 0.00, NULL, NULL, 1.10, 22.00, '2026-09-19 06:45:26', '2026-09-19 06:45:26'),
(20, 17, 6, 1, 1.0000, 22.00, 0.00, 'percent', 0.00, NULL, NULL, 1.10, 22.00, '2026-09-19 06:45:26', '2026-09-19 06:45:26');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(1, 'default', '{\"uuid\":\"5e20994d-ea50-4312-96be-e05cd6dde2d7\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:1;}\",\"batchId\":null},\"createdAt\":1789627085,\"delay\":null}', 0, NULL, 1789627085, 1789627085),
(2, 'default', '{\"uuid\":\"0639bd03-e336-4389-b543-a03cc699dffd\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:2;}\",\"batchId\":null},\"createdAt\":1789627266,\"delay\":null}', 0, NULL, 1789627266, 1789627266),
(3, 'default', '{\"uuid\":\"d9aad251-aebb-4628-a175-0da951bd5823\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:3;}\",\"batchId\":null},\"createdAt\":1789800326,\"delay\":null}', 0, NULL, 1789800326, 1789800326),
(4, 'default', '{\"uuid\":\"619fc4cd-6116-454a-8623-ee8ad9b610e3\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:4;}\",\"batchId\":null},\"createdAt\":1789800793,\"delay\":null}', 0, NULL, 1789800793, 1789800793),
(5, 'default', '{\"uuid\":\"3abad79f-11aa-4198-99fd-4caf4e8b0537\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:4;}\",\"batchId\":null},\"createdAt\":1789816704,\"delay\":null}', 0, NULL, 1789816704, 1789816704),
(6, 'default', '{\"uuid\":\"d653e65f-a641-4928-8ab7-b928a654c7b7\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:3;}\",\"batchId\":null},\"createdAt\":1789816710,\"delay\":null}', 0, NULL, 1789816710, 1789816710),
(7, 'default', '{\"uuid\":\"7771e48e-705b-4bc4-a729-7e0f4fc97d93\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:2;}\",\"batchId\":null},\"createdAt\":1789816715,\"delay\":null}', 0, NULL, 1789816715, 1789816715),
(8, 'default', '{\"uuid\":\"9c37a6da-1426-4fb7-8baf-5ed8c727f91e\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:1;}\",\"batchId\":null},\"createdAt\":1789816718,\"delay\":null}', 0, NULL, 1789816718, 1789816718),
(9, 'default', '{\"uuid\":\"652b3898-1a1a-4f46-90b9-a458a9fbeca6\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:4;}\",\"batchId\":null},\"createdAt\":1789878413,\"delay\":null}', 0, NULL, 1789878413, 1789878413),
(10, 'default', '{\"uuid\":\"a19e1d7a-39b4-4fb7-9b1b-84dff90ae27b\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:5;}\",\"batchId\":null},\"createdAt\":1789968791,\"delay\":null}', 0, NULL, 1789968791, 1789968791),
(11, 'default', '{\"uuid\":\"7ef6dc8f-edc9-4f3f-a6f5-f991e701c3c1\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:6;}\",\"batchId\":null},\"createdAt\":1789968830,\"delay\":null}', 0, NULL, 1789968830, 1789968830),
(12, 'default', '{\"uuid\":\"487d9b45-2e20-43a1-a4a7-46c95194c33f\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:7;}\",\"batchId\":null},\"createdAt\":1789969650,\"delay\":null}', 0, NULL, 1789969650, 1789969650),
(13, 'default', '{\"uuid\":\"918b16e7-b637-40c0-aad9-59e19daae700\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:8;}\",\"batchId\":null},\"createdAt\":1789969650,\"delay\":null}', 0, NULL, 1789969650, 1789969650),
(14, 'default', '{\"uuid\":\"e36f9af3-69a3-4939-afa9-2335fb0c6dab\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:9;}\",\"batchId\":null},\"createdAt\":1789977046,\"delay\":null}', 0, NULL, 1789977046, 1789977046),
(15, 'default', '{\"uuid\":\"a7ee259f-b2fe-47f4-a11e-9043e97993ae\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:9;}\",\"batchId\":null},\"createdAt\":1789984264,\"delay\":null}', 0, NULL, 1789984264, 1789984264),
(16, 'default', '{\"uuid\":\"72bdb758-dd32-4080-9326-9ea54ccdba0c\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:10;}\",\"batchId\":null},\"createdAt\":1789989817,\"delay\":null}', 0, NULL, 1789989817, 1789989817),
(17, 'default', '{\"uuid\":\"0cb02701-4122-4094-9fcc-3861ac1f9e22\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:10;}\",\"batchId\":null},\"createdAt\":1789993874,\"delay\":null}', 0, NULL, 1789993874, 1789993874),
(18, 'default', '{\"uuid\":\"fbf80e12-add4-468d-a1d9-297fef6ab810\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:10;}\",\"batchId\":null},\"createdAt\":1789993878,\"delay\":null}', 0, NULL, 1789993878, 1789993878),
(19, 'default', '{\"uuid\":\"5cce55f1-f79b-4a6e-8d9a-d793367ea62c\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:10;}\",\"batchId\":null},\"createdAt\":1790318085,\"delay\":null}', 0, NULL, 1790318085, 1790318085),
(20, 'default', '{\"uuid\":\"11ee310d-0e95-475f-a1fe-048d3b74176e\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:11;}\",\"batchId\":null},\"createdAt\":1790318728,\"delay\":null}', 0, NULL, 1790318728, 1790318728),
(21, 'default', '{\"uuid\":\"f62a0592-72db-4396-89d4-7d68bc1f5ac9\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:12;}\",\"batchId\":null},\"createdAt\":1790318784,\"delay\":null}', 0, NULL, 1790318784, 1790318784),
(22, 'default', '{\"uuid\":\"5a92864e-b991-4dbf-9924-dbb5fe6283d4\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:13;}\",\"batchId\":null},\"createdAt\":1790321814,\"delay\":null}', 0, NULL, 1790321814, 1790321814),
(23, 'default', '{\"uuid\":\"68141521-8e40-4b21-b059-39f6d3d326b2\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:14;}\",\"batchId\":null},\"createdAt\":1790322177,\"delay\":null}', 0, NULL, 1790322177, 1790322177),
(24, 'default', '{\"uuid\":\"97be3a24-6357-4d23-b4ce-e3cea3e3ac51\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:14;}\",\"batchId\":null},\"createdAt\":1790575239,\"delay\":null}', 0, NULL, 1790575239, 1790575239),
(25, 'default', '{\"uuid\":\"9c8459a4-efe0-4b0c-88c8-d96c54342ba3\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:15;}\",\"batchId\":null},\"createdAt\":1790586683,\"delay\":null}', 0, NULL, 1790586683, 1790586683),
(26, 'default', '{\"uuid\":\"371da146-2069-4c24-8909-03814596e921\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:16;}\",\"batchId\":null},\"createdAt\":1790587847,\"delay\":null}', 0, NULL, 1790587847, 1790587847),
(27, 'default', '{\"uuid\":\"b672d3e6-7632-49a2-a3ac-2167a6bb7309\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:17;}\",\"batchId\":null},\"createdAt\":1790587847,\"delay\":null}', 0, NULL, 1790587847, 1790587847),
(28, 'default', '{\"uuid\":\"6122cfef-b3ad-4251-a2db-23dabc085db2\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:18;}\",\"batchId\":null},\"createdAt\":1790675419,\"delay\":null}', 0, NULL, 1790675419, 1790675419),
(29, 'default', '{\"uuid\":\"cf46c13d-175a-47fa-b394-753dee7b09eb\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:19;}\",\"batchId\":null},\"createdAt\":1790675419,\"delay\":null}', 0, NULL, 1790675419, 1790675419),
(30, 'default', '{\"uuid\":\"65c1b85d-c37c-4c82-9ce8-9505b43e16d7\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:20;}\",\"batchId\":null},\"createdAt\":1790675911,\"delay\":null}', 0, NULL, 1790675911, 1790675911),
(31, 'default', '{\"uuid\":\"143a3809-c96d-4ddc-b4b2-897e53190ff6\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:21;}\",\"batchId\":null},\"createdAt\":1790676024,\"delay\":null}', 0, NULL, 1790676024, 1790676024),
(32, 'default', '{\"uuid\":\"fe146e24-0054-467b-90c8-2e03dae8a212\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:22;}\",\"batchId\":null},\"createdAt\":1790677059,\"delay\":null}', 0, NULL, 1790677059, 1790677059),
(33, 'default', '{\"uuid\":\"5484de43-ff82-4d04-ab64-8898b353921c\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:23;}\",\"batchId\":null},\"createdAt\":1790682132,\"delay\":null}', 0, NULL, 1790682132, 1790682132),
(34, 'default', '{\"uuid\":\"9e5e1744-5b62-485f-ac1b-9cc7e76e966b\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:24;}\",\"batchId\":null},\"createdAt\":1790682132,\"delay\":null}', 0, NULL, 1790682132, 1790682132),
(35, 'default', '{\"uuid\":\"fe7ecd43-c919-4b2a-a292-7831d7aaa4f6\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:25;}\",\"batchId\":null},\"createdAt\":1790682161,\"delay\":null}', 0, NULL, 1790682161, 1790682161),
(39, 'default', '{\"uuid\":\"2868c4ff-2e38-4d8e-8340-f7d3ee7c86d2\",\"displayName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ProcessTallySyncJob\",\"command\":\"O:28:\\\"App\\\\Jobs\\\\ProcessTallySyncJob\\\":1:{s:7:\\\"queueId\\\";i:29;}\",\"batchId\":null},\"createdAt\":1790938286,\"delay\":null}', 0, NULL, 1790938286, 1790938286);

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landed_costs`
--

CREATE TABLE `landed_costs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `landed_no` varchar(40) NOT NULL,
  `landed_date` date NOT NULL,
  `purchase_invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `freight_bill_id` bigint(20) UNSIGNED DEFAULT NULL,
  `allocation_method` enum('qty','value','weight','volume','equal','manual') NOT NULL DEFAULT 'value',
  `total_additional_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landed_costs`
--

INSERT INTO `landed_costs` (`id`, `landed_no`, `landed_date`, `purchase_invoice_id`, `freight_bill_id`, `allocation_method`, `total_additional_cost`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'LC-20260910-0001', '2026-09-10', 1, 1, 'qty', 10018.00, 'posted', NULL, 9, '2026-09-10 09:51:27', '2026-09-10 09:51:27'),
(2, 'LC-20260911-0001', '2026-09-11', 2, 2, 'qty', 15000.00, 'posted', NULL, 9, '2026-09-11 08:20:08', '2026-09-11 08:20:08'),
(3, 'LC-20260917-0001', '2026-09-17', 2, NULL, 'qty', 15000.00, 'posted', NULL, 9, '2026-09-17 07:19:33', '2026-09-17 07:19:33'),
(4, 'LC-20260917-0002', '2026-09-17', 2, 3, 'qty', 10000.00, 'posted', NULL, 9, '2026-09-17 07:23:54', '2026-09-17 07:23:54'),
(5, 'LC-20260919-0001', '2026-09-19', 3, 5, 'value', 520010.00, 'posted', NULL, 9, '2026-09-19 07:01:33', '2026-09-19 07:01:33'),
(6, 'LC-20260921-0001', '2026-09-21', 5, NULL, 'qty', 1000.00, 'posted', NULL, 9, '2026-09-21 05:34:11', '2026-09-21 05:34:11'),
(7, 'LC-20260921-0002', '2026-09-21', 7, NULL, 'qty', 1200.00, 'posted', NULL, 9, '2026-09-21 11:23:45', '2026-09-21 11:23:45'),
(8, 'LC-20260925-0001', '2026-09-25', 3, NULL, 'qty', 1000.00, 'posted', NULL, 9, '2026-09-25 07:30:14', '2026-09-25 07:30:14');

-- --------------------------------------------------------

--
-- Table structure for table `landed_cost_items`
--

CREATE TABLE `landed_cost_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `landed_cost_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `base_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `weight` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `volume` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `allocated_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `landed_unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landed_cost_items`
--

INSERT INTO `landed_cost_items` (`id`, `landed_cost_id`, `product_id`, `uom_id`, `quantity`, `base_value`, `weight`, `volume`, `allocated_cost`, `landed_unit_cost`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 1, 100.0000, 8260000.00, 0.0000, 0.0000, 10018.00, 70100.1800, '2026-09-10 09:51:27', '2026-09-10 09:51:27'),
(2, 2, 9, 2, 50.0000, 4000000.00, 0.0000, 0.0000, 15000.00, 80300.0000, '2026-09-11 08:20:08', '2026-09-11 08:20:08'),
(3, 3, 9, 2, 50.0000, 4000000.00, 0.0000, 0.0000, 15000.00, 80300.0000, '2026-09-17 07:19:33', '2026-09-17 07:19:33'),
(4, 4, 9, 2, 50.0000, 4000000.00, 0.0000, 0.0000, 10000.00, 80200.0000, '2026-09-17 07:23:54', '2026-09-17 07:23:54'),
(5, 5, 9, 1, 1.0000, 88500.00, 0.0000, 0.0000, 486324.47, 561324.4700, '2026-09-19 07:01:33', '2026-09-19 07:01:33'),
(6, 5, 7, 1, 1.0000, 5100.00, 0.0000, 0.0000, 28025.48, 33025.4800, '2026-09-19 07:01:33', '2026-09-19 07:01:33'),
(7, 5, 5, 1, 1.0000, 1030.00, 0.0000, 0.0000, 5660.05, 6660.0500, '2026-09-19 07:01:33', '2026-09-19 07:01:33'),
(8, 6, 9, 1, 1.0000, 88500.00, 0.0000, 0.0000, 500.00, 75500.0000, '2026-09-21 05:34:11', '2026-09-21 05:34:11'),
(9, 6, 7, 1, 1.0000, 5100.00, 0.0000, 0.0000, 500.00, 5500.0000, '2026-09-21 05:34:11', '2026-09-21 05:34:11'),
(10, 7, 9, 2, 1.0000, 80000.00, 0.0000, 0.0000, 1200.00, 81200.0000, '2026-09-21 11:23:45', '2026-09-21 11:23:45'),
(11, 8, 9, 1, 1.0000, 88500.00, 0.0000, 0.0000, 333.33, 75333.3300, '2026-09-25 07:30:14', '2026-09-25 07:30:14'),
(12, 8, 7, 1, 1.0000, 5100.00, 0.0000, 0.0000, 333.33, 5333.3300, '2026-09-25 07:30:14', '2026-09-25 07:30:14'),
(13, 8, 5, 1, 1.0000, 1030.00, 0.0000, 0.0000, 333.34, 1333.3400, '2026-09-25 07:30:14', '2026-09-25 07:30:14');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lead_source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lead_campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
  `meta_lead_form_id` bigint(20) UNSIGNED DEFAULT NULL,
  `external_lead_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `interested_product` varchar(255) DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `status` varchar(40) NOT NULL DEFAULT 'new',
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `last_contacted_at` timestamp NULL DEFAULT NULL,
  `next_followup_at` timestamp NULL DEFAULT NULL,
  `lead_score` decimal(8,2) DEFAULT NULL,
  `lost_reason` varchar(255) DEFAULT NULL,
  `converted_customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `meta_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_payload`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_activities`
--

CREATE TABLE `lead_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `activity_type` varchar(40) NOT NULL,
  `body` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_assignments`
--

CREATE TABLE `lead_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_to` bigint(20) UNSIGNED NOT NULL,
  `assigned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `method` varchar(40) NOT NULL DEFAULT 'manual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_campaigns`
--

CREATE TABLE `lead_campaigns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `external_campaign_id` varchar(255) DEFAULT NULL,
  `platform` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_conversions`
--

CREATE TABLE `lead_conversions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `converted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `converted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_followups`
--

CREATE TABLE `lead_followups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lead_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `due_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `channel` varchar(40) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_sources`
--

CREATE TABLE `lead_sources` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(40) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `opening_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `used_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `closing_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `days` decimal(5,2) NOT NULL DEFAULT 1.00,
  `reason` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `default_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `load_sheets`
--

CREATE TABLE `load_sheets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `load_sheet_no` varchar(30) NOT NULL,
  `load_date` date NOT NULL,
  `route_id` bigint(20) UNSIGNED DEFAULT NULL,
  `vehicle_id` bigint(20) UNSIGNED DEFAULT NULL,
  `driver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_person_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','dispatched','in_transit','delivered','settled') NOT NULL DEFAULT 'draft',
  `total_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `load_sheets`
--

INSERT INTO `load_sheets` (`id`, `load_sheet_no`, `load_date`, `route_id`, `vehicle_id`, `driver_id`, `delivery_person_id`, `status`, `total_value`, `total_quantity`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'LS-0001', '2026-08-29', 1, 1, 1, 1, 'settled', 4598.16, 34.0000, 5, '2026-08-29 04:40:01', '2026-09-03 09:31:51'),
(2, 'LS-20260903-0001', '2026-09-03', 1, 1, 1, 1, 'settled', 430.08, 3.0000, 2, '2026-09-03 09:40:55', '2026-09-10 10:48:54'),
(3, 'LS-20260910-0001', '2026-09-10', 1, 1, 1, 1, 'settled', 3160.08, 53.0000, 9, '2026-09-10 10:48:33', '2026-09-10 10:48:48');

-- --------------------------------------------------------

--
-- Table structure for table `load_sheet_items`
--

CREATE TABLE `load_sheet_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `load_sheet_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `loaded_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `loaded_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `load_sheet_items`
--

INSERT INTO `load_sheet_items` (`id`, `load_sheet_id`, `invoice_id`, `loaded_quantity`, `loaded_value`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 10.0000, 1050.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 1, 2, 10.0000, 3548.16, '2026-08-29 04:40:02', '2026-08-29 04:40:02'),
(3, 2, 10, 3.0000, 430.08, '2026-09-03 09:40:55', '2026-09-03 09:40:55'),
(4, 3, 3, 50.0000, 2730.00, '2026-09-10 10:48:33', '2026-09-10 10:48:33'),
(5, 3, 10, 3.0000, 430.08, '2026-09-10 10:48:33', '2026-09-10 10:48:33');

-- --------------------------------------------------------

--
-- Table structure for table `meta_lead_forms`
--

CREATE TABLE `meta_lead_forms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `form_id` varchar(255) NOT NULL,
  `form_name` varchar(255) DEFAULT NULL,
  `page_id` varchar(255) DEFAULT NULL,
  `lead_campaign_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meta_lead_logs`
--

CREATE TABLE `meta_lead_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `external_lead_id` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'received',
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `result` text DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_08_29_094243_create_permission_tables', 1),
(5, '2026_08_29_100000_create_dms_tables', 1),
(6, '2026_09_01_130000_add_commercial_and_payment_sync_fields', 2),
(7, '2026_09_02_065206_harden_orders_module', 3),
(8, '2026_09_02_114747_add_unique_order_id_to_invoices_table', 3),
(9, '2026_09_02_122538_create_document_sequences_table', 3),
(10, '2026_09_08_100000_phase1_organization_catalog_party', 4),
(11, '2026_09_08_110000_phase2_purchase_inventory', 5),
(12, '2026_09_08_120000_phase3_sales_hybrid', 6),
(13, '2026_09_08_130000_phase4_receivables', 7),
(14, '2026_09_08_140000_phase5_deal_expenses', 8),
(15, '2026_09_08_150000_phase6_targets_schemes', 8),
(16, '2026_09_08_160000_phase7_interest', 8),
(17, '2026_09_08_170000_phase9_hrms', 8),
(18, '2026_09_08_180000_phase10_meta_crm', 8),
(19, '2026_09_08_190000_phase11_tally', 8),
(20, '2026_09_08_200000_harden_partial_gaps', 9),
(21, '2026_09_10_120000_align_stock_reservations_for_orders', 10),
(22, '2026_09_12_100000_avit_masters_phase1', 11),
(23, '2026_09_12_110000_avit_pricing_and_price_history_phase2', 12),
(24, '2026_09_12_120000_avit_purchasing_phase3', 13),
(25, '2026_09_12_130000_avit_sales_and_einvoice_phase4', 14),
(26, '2026_09_17_055920_add_invoice_terms_to_companies', 15),
(27, '2026_09_17_102242_add_cgst_sgst_to_purchase_order_items', 15),
(28, '2026_09_17_104801_add_cgst_sgst_to_purchase_invoice_items', 15),
(29, '2026_09_17_121510_add_detail_to_brands_table', 15),
(30, '2026_09_18_061630_add_serial_no_to_products', 15),
(31, '2026_09_21_062701_add_stub_status_to_e_way_bills_table', 16),
(32, '2026_09_21_063722_add_qr_token_to_invoices', 16),
(33, '2026_09_21_072129_add_qr_token_to_e_way_bills', 16),
(34, '2026_09_23_102345_create_tally_sync_mappings_table', 17),
(35, '2026_09_24_101603_add_last_response_to_tally_sync_queues_table', 18),
(36, '2026_09_27_180000_update_tracking_type_in_products_table', 18),
(37, '2026_09_27_190000_add_unique_name_to_brands_table', 18),
(38, '2026_09_28_170000_add_sales_manager_id_to_customers_table', 19),
(39, '2026_09_28_180000_create_party_bank_accounts_table', 19),
(40, '2026_09_28_190000_create_party_credit_cheques_table', 19),
(41, '2026_09_29_120000_add_batch_name_to_purchase_order_items_table', 19),
(42, '2026_09_29_130000_add_profile_fields_to_companies_table', 19),
(43, '2026_09_29_160000_add_credit_days_to_purchase_invoices_table', 19);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_permissions`
--

INSERT INTO `model_has_permissions` (`permission_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 2),
(1, 'App\\Models\\User', 3),
(1, 'App\\Models\\User', 4),
(3, 'App\\Models\\User', 4),
(5, 'App\\Models\\User', 4),
(6, 'App\\Models\\User', 4);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4),
(5, 'App\\Models\\User', 5),
(6, 'App\\Models\\User', 6),
(7, 'App\\Models\\User', 7),
(8, 'App\\Models\\User', 8),
(12, 'App\\Models\\User', 9);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_no` varchar(30) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quotation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salesperson_id` bigint(20) UNSIGNED NOT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `updated_by_name` varchar(100) DEFAULT NULL,
  `order_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `fulfilment_mode` enum('warehouse','van') NOT NULL DEFAULT 'van',
  `back_order` tinyint(1) NOT NULL DEFAULT 0,
  `credit_check_status` enum('pending','passed','blocked','overridden') NOT NULL DEFAULT 'pending',
  `blocked_reason` text DEFAULT NULL,
  `status` enum('draft','pending','approved','converted','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by_name` varchar(100) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `converted_by_name` varchar(100) DEFAULT NULL,
  `cancelled_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_no`, `customer_id`, `warehouse_id`, `quotation_id`, `salesperson_id`, `created_by_name`, `updated_by_name`, `order_date`, `due_date`, `fulfilment_mode`, `back_order`, `credit_check_status`, `blocked_reason`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `grand_total`, `notes`, `approved_by`, `approved_by_name`, `approved_at`, `converted_by_name`, `cancelled_by_name`, `created_at`, `updated_at`) VALUES
(1, 'ORD-1001', 1, NULL, NULL, 4, NULL, NULL, '2026-08-29', NULL, 'van', 0, 'pending', NULL, 'converted', 2600.00, 0.00, 130.00, 2730.00, 'Demo order', 2, NULL, '2026-09-01 05:24:20', NULL, NULL, '2026-08-29 04:40:01', '2026-09-01 05:24:22'),
(2, 'ORD-1002', 2, NULL, NULL, 4, NULL, NULL, '2026-08-29', NULL, 'van', 0, 'pending', NULL, 'approved', 3168.00, 0.00, 158.40, 3326.40, 'Demo order', 3, NULL, '2026-08-29 02:40:01', NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'ORD-1003', 3, NULL, NULL, 4, NULL, NULL, '2026-08-28', NULL, 'van', 0, 'pending', NULL, 'converted', 1000.00, 0.00, 50.00, 1050.00, 'Demo order', 3, NULL, '2026-08-29 02:40:01', NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 'ORD-1004', 4, NULL, NULL, 4, NULL, NULL, '2026-08-29', NULL, 'van', 0, 'pending', NULL, 'approved', 9000.00, 0.00, 450.00, 9450.00, 'Demo order', 2, NULL, '2026-09-01 06:33:47', NULL, NULL, '2026-08-29 04:40:01', '2026-09-01 06:33:47'),
(5, 'ORD-1005', 5, NULL, NULL, 4, NULL, NULL, '2026-08-28', NULL, 'van', 0, 'pending', NULL, 'cancelled', 1740.00, 0.00, 87.00, 1827.00, 'Demo order', NULL, NULL, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 'ORD-20260901-0001', 1, NULL, NULL, 2, NULL, NULL, '2026-09-01', NULL, 'van', 0, 'pending', NULL, 'approved', 72.00, 0.00, 3.20, 75.20, 'oinseofnse', 2, NULL, '2026-09-01 06:24:11', NULL, NULL, '2026-09-01 06:24:06', '2026-09-01 06:24:11'),
(7, 'ORD-20260901-0002', 4, NULL, NULL, 2, NULL, NULL, '2026-09-01', NULL, 'van', 0, 'pending', NULL, 'approved', 20.00, 0.00, 0.60, 20.60, 'hjkh', 2, NULL, '2026-09-01 06:25:15', NULL, NULL, '2026-09-01 06:25:12', '2026-09-01 06:25:15'),
(8, 'ORD-20260901-0003', 3, NULL, NULL, 2, NULL, NULL, '2026-09-01', NULL, 'van', 0, 'pending', NULL, 'converted', 47.00, 0.00, 2.35, 49.35, 'jblb', 2, NULL, '2026-09-01 06:27:01', NULL, NULL, '2026-09-01 06:26:56', '2026-09-01 06:27:04'),
(9, 'ORD-20260901-0004', 7, NULL, NULL, 2, NULL, NULL, '2026-09-01', NULL, 'van', 0, 'pending', NULL, 'pending', 48.50, 0.00, 2.43, 50.93, 'jhvuv', NULL, NULL, NULL, NULL, NULL, '2026-09-01 06:34:29', '2026-09-01 06:34:29'),
(10, 'ORD-20260901-0005', 3, NULL, NULL, 2, NULL, NULL, '2026-09-01', NULL, 'van', 0, 'pending', NULL, 'converted', 470.00, 10.00, 23.00, 483.00, 'jgcut', 2, NULL, '2026-09-01 11:31:06', NULL, NULL, '2026-09-01 11:31:02', '2026-09-01 11:32:35'),
(11, 'ORD-20260902-0001', 8, NULL, NULL, 2, NULL, NULL, '2026-09-02', NULL, 'van', 0, 'pending', NULL, 'pending', 800.00, 0.00, 96.00, 896.00, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:47:45', '2026-09-02 07:47:45'),
(12, 'ORD-20260903-0001', 3, NULL, NULL, 2, 'Super Admin', NULL, '2026-09-03', NULL, 'van', 0, 'pending', NULL, 'converted', 384.00, 0.00, 46.08, 430.08, NULL, 2, 'Super Admin', '2026-09-03 07:50:51', 'Super Admin', NULL, '2026-09-03 07:50:49', '2026-09-03 07:50:55'),
(13, 'ORD-20260903-0002', 2, NULL, NULL, 2, 'Super Admin', NULL, '2026-09-03', NULL, 'van', 0, 'pending', NULL, 'pending', 485.00, 0.00, 24.25, 509.25, 'idbsiov', NULL, NULL, NULL, NULL, NULL, '2026-09-03 09:22:19', '2026-09-03 09:22:19'),
(14, 'ORD-20260910-0001', 7, NULL, NULL, 9, 'Client Admin', NULL, '2026-09-10', '2026-09-30', 'warehouse', 1, 'passed', NULL, 'converted', 8000000.00, 800000.00, 1296000.00, 8496000.00, NULL, 9, 'Client Admin', '2026-09-10 12:17:24', 'Client Admin', NULL, '2026-09-10 10:22:35', '2026-09-10 12:17:47'),
(15, 'ORD-20260917-0001', 3, NULL, NULL, 9, 'Client Admin', NULL, '2026-09-17', NULL, 'warehouse', 1, 'passed', NULL, 'converted', 80128.00, 4006.40, 13694.59, 89816.19, NULL, 9, 'Client Admin', '2026-09-17 06:40:53', 'Client Admin', NULL, '2026-09-17 06:06:58', '2026-09-17 06:41:06'),
(16, 'ORD-20260928-0001', 4, NULL, NULL, 9, 'Client Admin', NULL, '2026-09-28', NULL, 'van', 0, 'pending', NULL, 'pending', 780000.00, 100000.00, 122400.00, 802400.00, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-28 09:58:24', '2026-09-28 09:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `reserved_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `delivered_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `back_order_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `uom_id`, `quantity`, `reserved_qty`, `delivered_qty`, `back_order_qty`, `unit_price`, `line_total`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 50.0000, 0.0000, 0.0000, 0.0000, 52.00, 2600.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 2, 2, 5, 24.0000, 0.0000, 0.0000, 0.0000, 132.00, 3168.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 3, 3, 1, 10.0000, 0.0000, 0.0000, 0.0000, 100.00, 1000.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 4, 1, 4, 200.0000, 0.0000, 0.0000, 0.0000, 45.00, 9000.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 5, 2, 5, 12.0000, 0.0000, 0.0000, 0.0000, 145.00, 1740.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 6, 4, 2, 1.0000, 0.0000, 0.0000, 0.0000, 20.00, 20.00, '2026-09-01 06:24:06', '2026-09-01 06:24:06'),
(7, 6, 1, 4, 1.0000, 0.0000, 0.0000, 0.0000, 52.00, 52.00, '2026-09-01 06:24:06', '2026-09-01 06:24:06'),
(8, 7, 4, 2, 1.0000, 0.0000, 0.0000, 0.0000, 20.00, 20.00, '2026-09-01 06:25:12', '2026-09-01 06:25:12'),
(9, 8, 1, 4, 1.0000, 0.0000, 0.0000, 0.0000, 47.00, 47.00, '2026-09-01 06:26:56', '2026-09-01 06:26:56'),
(10, 9, 1, 4, 1.0000, 0.0000, 0.0000, 0.0000, 48.50, 48.50, '2026-09-01 06:34:29', '2026-09-01 06:34:29'),
(11, 10, 1, 4, 10.0000, 0.0000, 0.0000, 0.0000, 47.00, 470.00, '2026-09-01 11:31:02', '2026-09-01 11:31:02'),
(12, 11, 5, 2, 1.0000, 0.0000, 0.0000, 0.0000, 800.00, 800.00, '2026-09-02 07:47:45', '2026-09-02 07:47:45'),
(13, 12, 2, 5, 3.0000, 0.0000, 0.0000, 0.0000, 128.00, 384.00, '2026-09-03 07:50:49', '2026-09-03 07:50:49'),
(14, 13, 1, 4, 10.0000, 0.0000, 0.0000, 0.0000, 48.50, 485.00, '2026-09-03 09:22:19', '2026-09-03 09:22:19'),
(15, 14, 9, 2, 100.0000, 50.0000, 0.0000, 50.0000, 80000.00, 8000000.00, '2026-09-10 10:22:35', '2026-09-10 12:17:24'),
(16, 15, 9, 2, 1.0000, 0.0000, 0.0000, 1.0000, 80000.00, 80000.00, '2026-09-17 06:06:58', '2026-09-17 06:40:53'),
(17, 15, 2, 5, 1.0000, 1.0000, 0.0000, 0.0000, 128.00, 128.00, '2026-09-17 06:06:58', '2026-09-17 06:40:53'),
(18, 16, 9, 2, 10.0000, 0.0000, 0.0000, 0.0000, 78000.00, 780000.00, '2026-09-28 09:58:24', '2026-09-28 09:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `outstanding_ledger`
--

CREATE TABLE `outstanding_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(30) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `outstanding_ledger`
--

INSERT INTO `outstanding_ledger` (`id`, `customer_id`, `type`, `reference_type`, `reference_id`, `debit`, `credit`, `balance`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 1, 1050.00, 0.00, 1050.00, 'Invoice INV-0001', '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 3, 'payment', 'App\\Domains\\Payment\\Models\\Payment', 1, 0.00, 500.00, 550.00, 'Payment PAY-0001', '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 2, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 2, 3548.16, 0.00, 3548.16, 'Invoice INV-0002', '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 1, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 3, 2730.00, 0.00, 2730.00, 'Invoice INV-20260901-0001', '2026-09-01 05:24:22', '2026-09-01 05:24:22'),
(5, 3, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 6, 49.35, 0.00, 599.35, 'Invoice INV-20260901-0002', '2026-09-01 06:27:04', '2026-09-01 06:27:04'),
(6, 3, 'payment', 'App\\Domains\\Payment\\Models\\Payment', 2, 0.00, 49.35, 550.00, 'Payment PAY-20260901-0001', '2026-09-01 06:27:34', '2026-09-01 06:27:34'),
(7, 3, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 9, 483.00, 0.00, 1033.00, 'Invoice INV-20260901-0003', '2026-09-01 11:32:35', '2026-09-01 11:32:35'),
(8, 3, 'payment', 'App\\Domains\\Payment\\Models\\Payment', 3, 0.00, 483.00, 550.00, 'Payment PAY-20260901-0002', '2026-09-01 11:33:19', '2026-09-01 11:33:19'),
(9, 3, 'payment', 'App\\Domains\\Payment\\Models\\Payment', 4, 0.00, 550.00, 0.00, 'Payment PAY-20260902-0001', '2026-09-02 10:31:33', '2026-09-02 10:31:33'),
(10, 3, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 10, 430.08, 0.00, 430.08, 'Invoice INV-20260903-0001', '2026-09-03 07:50:55', '2026-09-03 07:50:55'),
(11, 2, 'payment', 'App\\Domains\\Payment\\Models\\Payment', 5, 0.00, 3548.16, 0.00, 'Payment PAY-20260903-0001', '2026-09-03 09:31:51', '2026-09-03 09:31:51'),
(12, 2, 'settlement', 'App\\Domains\\Settlement\\Models\\Settlement', 1, 0.00, 3548.16, -3548.16, 'Outstanding from settlement SET-20260903-0001', '2026-09-03 09:31:51', '2026-09-03 09:31:51'),
(13, 1, 'settlement', 'App\\Domains\\Settlement\\Models\\Settlement', 2, 0.00, 2730.00, 0.00, 'Outstanding from settlement SET-20260910-0001', '2026-09-10 10:48:48', '2026-09-10 10:48:48'),
(14, 3, 'settlement', 'App\\Domains\\Settlement\\Models\\Settlement', 2, 0.00, 430.08, 0.00, 'Outstanding from settlement SET-20260910-0001', '2026-09-10 10:48:48', '2026-09-10 10:48:48'),
(15, 3, 'settlement', 'App\\Domains\\Settlement\\Models\\Settlement', 3, 0.00, 430.08, -430.08, 'Outstanding from settlement SET-20260910-0002', '2026-09-10 10:48:54', '2026-09-10 10:48:54'),
(16, 3, 'payment', 'App\\Domains\\Payment\\Models\\Payment', 6, 0.00, 430.00, -860.08, 'Payment PAY-20260910-0001', '2026-09-10 10:49:59', '2026-09-10 10:49:59'),
(17, 7, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 14, 8496000.00, 0.00, 8496000.00, 'Invoice INV-20260910-0001', '2026-09-10 12:17:47', '2026-09-10 12:17:47'),
(18, 6, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 15, 88851.00, 0.00, 88851.00, 'Invoice INV-20260917-0001', '2026-09-17 06:38:05', '2026-09-17 06:38:05'),
(19, 3, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 16, 89816.19, 0.00, 88956.11, 'Invoice INV-20260917-0002', '2026-09-17 06:41:06', '2026-09-17 06:41:06'),
(20, 7, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 17, 46.20, 0.00, 8496046.20, 'Invoice INV-20260919-0001', '2026-09-19 06:45:26', '2026-09-19 06:45:26'),
(21, 7, 'payment', 'App\\Domains\\Payment\\Models\\Payment', 7, 0.00, 46.20, 8496000.00, 'Payment PAY-20260921-0001', '2026-09-21 05:47:30', '2026-09-21 05:47:30'),
(22, 3, 'payment', 'App\\Domains\\Payment\\Models\\Payment', 8, 0.00, 50000.00, 38956.11, 'Payment PAY-20260928-0001', '2026-09-28 09:30:47', '2026-09-28 09:30:47');

-- --------------------------------------------------------

--
-- Table structure for table `party_addresses`
--

CREATE TABLE `party_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('billing','shipping','site','warehouse') NOT NULL DEFAULT 'billing',
  `label` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `party_addresses`
--

INSERT INTO `party_addresses` (`id`, `customer_id`, `type`, `label`, `name`, `address`, `state`, `pincode`, `gstin`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 12, 'billing', 'Head Office', 'Sample Distributor', '#12, MG Road', 'Karnataka', '560001', NULL, 1, '2026-09-19 06:42:15', '2026-09-19 06:42:15'),
(2, 13, 'billing', 'Head Office', 'Sample Distributor', '#12, MG Road', 'Karnataka', '560001', NULL, 1, '2026-09-21 05:04:14', '2026-09-21 05:04:14');

-- --------------------------------------------------------

--
-- Table structure for table `party_bank_accounts`
--

CREATE TABLE `party_bank_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(50) NOT NULL,
  `account_type` varchar(30) NOT NULL DEFAULT 'current',
  `ifsc_code` varchar(20) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `branch_address` text DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `party_contacts`
--

CREATE TABLE `party_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `level` enum('party','branch','site','warehouse','transporter','driver','site_incharge') NOT NULL DEFAULT 'party',
  `name` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `party_contacts`
--

INSERT INTO `party_contacts` (`id`, `customer_id`, `level`, `name`, `role`, `phone`, `alternate_phone`, `email`, `location`, `is_primary`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 12, 'party', 'Ravi', 'Owner', '9999888877', NULL, 'ravi@sample.co', NULL, 1, 1, '2026-09-19 06:42:15', '2026-09-19 06:42:15'),
(2, 13, 'party', 'Ravi', 'Owner', '9999888877', NULL, 'ravi@sample.co', NULL, 1, 1, '2026-09-21 05:04:14', '2026-09-21 05:04:14');

-- --------------------------------------------------------

--
-- Table structure for table `party_credit_cheques`
--

CREATE TABLE `party_credit_cheques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `cheque_number` varchar(50) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cheque_type` varchar(40) NOT NULL DEFAULT 'credit_cheque',
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `party_targets`
--

CREATE TABLE `party_targets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `target_period_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salesperson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_no` varchar(30) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `method` enum('cash','upi','bank','cheque','other') NOT NULL DEFAULT 'cash',
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'completed',
  `paid_at` timestamp NULL DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_no`, `reference_no`, `invoice_id`, `customer_id`, `amount`, `method`, `status`, `paid_at`, `recorded_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'PAY-0001', NULL, 1, 3, 500.00, 'upi', 'completed', '2026-08-29 01:40:01', 6, 'Partial UPI collection', '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'PAY-20260901-0001', NULL, 6, 3, 49.35, 'cash', 'completed', '2026-09-01 06:27:00', 2, NULL, '2026-09-01 06:27:34', '2026-09-01 06:27:34'),
(3, 'PAY-20260901-0002', NULL, 9, 3, 483.00, 'cash', 'completed', '2026-09-01 11:33:00', 2, 'rfrgw', '2026-09-01 11:33:19', '2026-09-01 11:33:19'),
(4, 'PAY-20260902-0001', NULL, 1, 3, 550.00, 'cash', 'completed', '2026-09-02 10:31:00', 2, NULL, '2026-09-02 10:31:33', '2026-09-02 10:31:33'),
(5, 'PAY-20260903-0001', NULL, 2, 2, 3548.16, 'other', 'completed', '2026-09-03 09:31:51', 2, 'Settlement SET-20260903-0001', '2026-09-03 09:31:51', '2026-09-03 09:31:51'),
(6, 'PAY-20260910-0001', NULL, 10, 3, 430.00, 'cash', 'completed', '2026-09-10 10:49:00', 9, NULL, '2026-09-10 10:49:59', '2026-09-10 10:49:59'),
(7, 'PAY-20260921-0001', 'INTERNAL-TGH9KDJL2Z6NEUBC', 17, 7, 46.20, 'upi', 'completed', '2026-09-21 05:47:30', 9, 'Payment received through payment link XkOFPYvh2pZozinrZWzJIBl1raoPzPYp', '2026-09-21 05:47:30', '2026-09-21 05:47:30'),
(8, 'PAY-20260928-0001', NULL, 16, 3, 50000.00, 'bank', 'completed', '2026-09-30 09:29:00', 9, NULL, '2026-09-28 09:30:47', '2026-09-28 09:30:47');

-- --------------------------------------------------------

--
-- Table structure for table `payment_allocations`
--

CREATE TABLE `payment_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_allocations`
--

INSERT INTO `payment_allocations` (`id`, `payment_id`, `invoice_id`, `amount`, `created_at`, `updated_at`) VALUES
(1, 6, 10, 430.00, '2026-09-10 10:49:59', '2026-09-10 10:49:59'),
(2, 8, 16, 50000.00, '2026-09-28 09:30:47', '2026-09-28 09:30:47');

-- --------------------------------------------------------

--
-- Table structure for table `payment_links`
--

CREATE TABLE `payment_links` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `provider` varchar(40) NOT NULL DEFAULT 'internal',
  `provider_reference` varchar(100) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` enum('active','paid','expired') NOT NULL DEFAULT 'active',
  `expires_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `webhook_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`webhook_payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_links`
--

INSERT INTO `payment_links` (`id`, `invoice_id`, `token`, `url`, `provider`, `provider_reference`, `amount`, `status`, `expires_at`, `paid_at`, `webhook_payload`, `created_at`, `updated_at`) VALUES
(1, 6, 'T969N7z6RNaJPsMChMfdVURCWIro3osO', 'upi://pay?pa=merchant@upi&pn=DMS&am=0.00&tn=Invoice%20INV-20260901-0002&tr=T969N7z6RNaJPsMChMfdVURCWIro3osO', 'internal', NULL, 0.00, 'paid', '2026-09-03 06:46:57', NULL, NULL, '2026-09-01 06:46:57', '2026-09-01 06:47:00'),
(2, 9, 'EeO399IwICMlUxjpntbAh9shzv7wKTbU', 'https://dms.extraaaz.com/pay/EeO399IwICMlUxjpntbAh9shzv7wKTbU', 'internal', NULL, 483.00, 'active', '2026-09-03 11:32:35', NULL, NULL, '2026-09-01 11:32:35', '2026-09-01 11:32:35'),
(3, 10, 'R7Hc6dCA6dnJUyfkOd5oDTgPoQw5tFLW', 'https://dms.extraaaz.com/pay/R7Hc6dCA6dnJUyfkOd5oDTgPoQw5tFLW', 'internal', NULL, 430.08, 'active', '2026-09-05 07:50:55', NULL, NULL, '2026-09-03 07:50:55', '2026-09-03 07:50:55'),
(4, 14, 'kAwHOM5wEsS0n8QOv4OCK6WipUAaWz7b', 'https://dms.extraaaz.com/pay/kAwHOM5wEsS0n8QOv4OCK6WipUAaWz7b', 'internal', NULL, 8496000.00, 'active', '2026-09-12 12:17:47', NULL, NULL, '2026-09-10 12:17:47', '2026-09-10 12:17:47'),
(5, 15, 'Z20h7PLpclYbUxzwRxKF1jeG9P6g1RNP', 'https://dms.extraaaz.com/pay/Z20h7PLpclYbUxzwRxKF1jeG9P6g1RNP', 'internal', NULL, 88851.00, 'active', '2026-09-19 06:38:05', NULL, NULL, '2026-09-17 06:38:05', '2026-09-17 06:38:05'),
(6, 16, '3q5Yxcg8WPuTskR0y6HW4MJsYQherqF2', 'https://dms.extraaaz.com/pay/3q5Yxcg8WPuTskR0y6HW4MJsYQherqF2', 'internal', NULL, 89816.19, 'active', '2026-09-19 06:41:06', NULL, NULL, '2026-09-17 06:41:06', '2026-09-17 06:41:06'),
(7, 17, 'XkOFPYvh2pZozinrZWzJIBl1raoPzPYp', 'https://dms.extraaaz.com/pay/XkOFPYvh2pZozinrZWzJIBl1raoPzPYp', 'internal', 'INTERNAL-TGH9KDJL2Z6NEUBC', 46.20, 'paid', '2026-09-21 06:45:26', '2026-09-21 05:47:30', '{\"source\":\"customer-payment-page\"}', '2026-09-19 06:45:26', '2026-09-21 05:47:30'),
(8, 17, 'Ljdh5rKMQVqu4cuJJ2h4u4PX39ZCESPR', 'https://dms.extraaaz.com/pay/Ljdh5rKMQVqu4cuJJ2h4u4PX39ZCESPR', 'internal', NULL, 0.00, 'active', '2026-09-27 10:47:20', NULL, NULL, '2026-09-25 10:47:20', '2026-09-25 10:47:20');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_inputs`
--

CREATE TABLE `payroll_inputs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `year` smallint(5) UNSIGNED NOT NULL,
  `month` tinyint(3) UNSIGNED NOT NULL,
  `basic` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `incentives` decimal(12,2) NOT NULL DEFAULT 0.00,
  `advances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_payable` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'dashboard.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(2, 'dashboard.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(3, 'masters.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(4, 'masters.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(5, 'orders.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(6, 'orders.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(7, 'inventory.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(8, 'inventory.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(9, 'sales.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(10, 'sales.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(11, 'payments.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(12, 'payments.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(13, 'communications.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(14, 'communications.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(15, 'logistics.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(16, 'logistics.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(17, 'delivery.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(18, 'delivery.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(19, 'settlement.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(20, 'settlement.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(21, 'reports.view', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(22, 'reports.manage', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(23, 'orders.approve', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(24, 'orders.book', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(25, 'orders.convert', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(26, 'payments.reconcile', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(27, 'settlement.entry', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(28, 'create orders', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(29, 'manage settlements', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(30, 'dashboard.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(31, 'dashboard.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(32, 'organization.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(33, 'organization.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(34, 'organization.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(35, 'organization.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(36, 'masters.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(37, 'masters.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(38, 'products.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(39, 'products.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(40, 'products.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(41, 'products.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(42, 'customers.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(43, 'customers.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(44, 'customers.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(45, 'customers.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(46, 'price-master.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(47, 'price-master.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(48, 'price-master.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(49, 'price-master.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(50, 'orders.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(51, 'orders.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(52, 'quotations.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(53, 'quotations.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(54, 'quotations.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(55, 'quotations.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(56, 'inventory.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(57, 'inventory.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(58, 'stock.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(59, 'stock.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(60, 'stock.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(61, 'stock.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(62, 'purchases.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(63, 'purchases.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(64, 'purchases.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(65, 'purchases.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(66, 'purchase-orders.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(67, 'purchase-orders.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(68, 'purchase-orders.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(69, 'purchase-orders.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(70, 'sales.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(71, 'sales.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(72, 'invoices.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(73, 'invoices.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(74, 'invoices.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(75, 'invoices.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(76, 'payments.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(77, 'payments.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(78, 'cheques.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(79, 'cheques.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(80, 'cheques.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(81, 'cheques.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(82, 'credit-notes.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(83, 'credit-notes.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(84, 'credit-notes.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(85, 'credit-notes.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(86, 'communications.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(87, 'communications.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(88, 'logistics.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(89, 'logistics.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(90, 'delivery.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(91, 'delivery.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(92, 'settlement.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(93, 'settlement.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(94, 'deals.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(95, 'deals.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(96, 'deals.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(97, 'deals.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(98, 'targets.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(99, 'targets.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(100, 'targets.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(101, 'targets.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(102, 'schemes.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(103, 'schemes.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(104, 'schemes.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(105, 'schemes.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(106, 'interest.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(107, 'interest.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(108, 'interest.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(109, 'interest.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(110, 'hrms.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(111, 'hrms.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(112, 'hrms.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(113, 'hrms.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(114, 'crm.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(115, 'crm.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(116, 'crm.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(117, 'crm.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(118, 'tally.view', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(119, 'tally.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(120, 'tally.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(121, 'tally.manage', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(122, 'reports.create', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(123, 'reports.edit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(124, 'orders.override-credit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(125, 'orders.back-order', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(126, 'quotations.view-profit', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(127, 'cheques.unfreeze', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33');

-- --------------------------------------------------------

--
-- Table structure for table `price_masters`
--

CREATE TABLE `price_masters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_type_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `rate` decimal(12,2) NOT NULL,
  `min_qty` decimal(12,2) DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `price_masters`
--

INSERT INTO `price_masters` (`id`, `customer_type_id`, `product_id`, `uom_id`, `rate`, `min_qty`, `effective_from`, `effective_to`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 52.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 1, 2, 5, 145.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 1, 3, 1, 120.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 2, 1, 4, 48.50, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 2, 2, 5, 132.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 2, 3, 1, 105.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(7, 3, 1, 4, 47.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(8, 3, 2, 5, 128.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(9, 3, 3, 1, 100.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(10, 4, 1, 4, 45.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(11, 4, 2, 5, 122.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(12, 4, 3, 1, 95.00, NULL, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(13, 4, 1, 4, 42.00, 500.00, NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(14, 1, 4, 2, 20.00, 100.00, NULL, NULL, '2026-09-01 06:12:18', '2026-09-01 06:12:18'),
(15, 3, 3, 4, 120.00, 50.00, NULL, NULL, '2026-09-01 07:31:57', '2026-09-01 07:31:57'),
(16, 2, 5, 2, 900.00, 50.00, NULL, NULL, '2026-09-02 07:47:14', '2026-09-02 07:47:14'),
(17, 4, 7, 2, 150.00, 50.00, NULL, NULL, '2026-09-03 09:15:47', '2026-09-03 09:16:58'),
(18, 1, 8, 2, 1000.00, 5.00, NULL, NULL, '2026-09-09 11:27:50', '2026-09-09 11:27:50'),
(19, 4, 9, 2, 78000.00, 10.00, NULL, NULL, '2026-09-10 09:33:42', '2026-09-10 09:33:42'),
(20, 5, 11, 2, 150.00, 0.00, NULL, NULL, '2026-09-21 05:04:25', '2026-09-21 05:04:25'),
(21, 3, 12, 1, 500.00, 10.00, NULL, NULL, '2026-09-21 05:15:00', '2026-09-21 05:19:52'),
(22, 1, 17, 2, 31000.00, NULL, NULL, NULL, '2026-09-28 08:33:00', '2026-09-28 08:33:00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sub_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `serial_no` varchar(30) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specification` text DEFAULT NULL,
  `base_uom_id` bigint(20) UNSIGNED NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `trade_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `purchase_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `calculation_mrp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `mrp_calculation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mrp_calculation_rules`)),
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `warranty_months` int(10) UNSIGNED DEFAULT NULL,
  `sender_warranty_months` int(10) UNSIGNED DEFAULT NULL,
  `sender_warranty_terms` text DEFAULT NULL,
  `customer_warranty_months` int(10) UNSIGNED DEFAULT NULL,
  `customer_warranty_terms` text DEFAULT NULL,
  `color_variant` varchar(60) DEFAULT NULL,
  `discount_type` enum('percent','flat') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_discount_type` enum('percent','flat') NOT NULL DEFAULT 'percent',
  `selling_discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `apply_discount_on_payable` tinyint(1) NOT NULL DEFAULT 0,
  `warranty_terms` text DEFAULT NULL,
  `catalog_link` varchar(255) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `tracking_type` varchar(100) NOT NULL DEFAULT 'none',
  `min_stock` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `reorder_level` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `aging_threshold_days` int(10) UNSIGNED DEFAULT NULL,
  `credit_period_days` int(10) UNSIGNED DEFAULT NULL,
  `payment_period_days` int(10) UNSIGNED DEFAULT NULL,
  `lifespan_days` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `company_id`, `brand_id`, `category_id`, `sub_category_id`, `name`, `sku`, `serial_no`, `hsn_code`, `description`, `specification`, `base_uom_id`, `tax_rate`, `selling_price`, `trade_price`, `purchase_price`, `calculation_mrp`, `mrp_calculation_rules`, `discount_percent`, `warranty_months`, `sender_warranty_months`, `sender_warranty_terms`, `customer_warranty_months`, `customer_warranty_terms`, `color_variant`, `discount_type`, `discount_value`, `selling_discount_type`, `selling_discount_value`, `apply_discount_on_payable`, `warranty_terms`, `catalog_link`, `image_path`, `tracking_type`, `min_stock`, `reorder_level`, `aging_threshold_days`, `credit_period_days`, `payment_period_days`, `lifespan_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, 'Premium Basmati Rice 25kg', 'RICE-25KG', 'PRD-000001', NULL, 'Demo product for DMS walkthrough', NULL, 4, 5.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 1, NULL, NULL, NULL, 'Sunflower Cooking Oil 1L', 'OIL-1L', 'PRD-000002', NULL, 'Demo product for DMS walkthrough', NULL, 5, 12.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 1, NULL, NULL, NULL, 'Assorted Biscuits', 'BISC-MIX', 'PRD-000003', NULL, 'Demo product for DMS walkthrough', NULL, 1, 18.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 1, NULL, NULL, NULL, 'Parle G', 'PG - 01', 'PRD-000004', NULL, 'Biscit', NULL, 2, 3.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-01 05:56:35', '2026-09-01 05:56:35'),
(5, 1, NULL, NULL, NULL, 'milton tiffin', 'tiff001', 'PRD-000005', NULL, NULL, NULL, 2, 12.00, 800.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-02 07:45:27', '2026-09-02 07:45:27'),
(6, 1, NULL, NULL, NULL, 'Chips', 'Pa1002', 'PRD-000006', NULL, NULL, NULL, 1, 5.00, 22.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-03 04:57:55', '2026-09-03 04:57:55'),
(7, 1, NULL, NULL, NULL, 'Test Product', 'TST - 01', 'PRD-000007', '8484', 'test product is used for testing', NULL, 2, 20.00, 150.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-03 09:13:01', '2026-09-03 09:16:58'),
(8, 1, 1, 1, 1, 'jeans', '001', 'PRD-000008', NULL, NULL, NULL, 2, 0.00, 1000.00, 900.00, 700.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'serial', 5.0000, 0.0000, NULL, 30, 45, NULL, 1, '2026-09-09 11:25:19', '2026-09-09 11:25:19'),
(9, 1, 2, 2, 4, 'apple iphone 18', '100', 'PRD-000009', NULL, NULL, NULL, 2, 18.00, 80000.00, 75000.00, 70000.00, 0.00, NULL, 10.00, 12, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'serial', 5.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-10 09:29:53', '2026-09-10 09:29:53'),
(10, 1, 2, 2, 5, 'apple iphone 18 pro', '101', 'PRD-000010', NULL, NULL, NULL, 2, 18.00, 190000.00, 180000.00, 150000.00, 0.00, NULL, 10.00, 12, 24, 'xyz', 12, 'abc', NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'serial', 5.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-10 09:31:21', '2026-09-17 05:55:10'),
(11, 1, 3, 3, NULL, 'Test 004', 'PROD-1-00001', 'PRD-000011', NULL, NULL, NULL, 2, 10.00, 6000.00, 6000.00, 5000.00, 7000.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 'Black', 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-17 06:11:27', '2026-09-17 06:11:27'),
(12, 1, 6, 6, NULL, 'Widget X', 'PROD-1-00002', 'PRD-000012', '84713010', NULL, NULL, 2, 18.00, 150.00, 130.00, 100.00, 175.00, NULL, 0.00, NULL, 12, NULL, 24, NULL, 'Red', 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-17 06:20:58', '2026-09-17 06:20:58'),
(13, 1, 6, 6, NULL, 'Widget X', 'PROD-1-00003', 'PRD-GLOBAL-00001', '84713010', NULL, NULL, 2, 18.00, 150.00, 130.00, 100.00, 175.00, NULL, 0.00, NULL, 12, NULL, 24, NULL, 'Red', 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-19 06:42:04', '2026-09-19 06:42:04'),
(14, 1, 6, 6, NULL, 'Widget X', 'PROD-1-00004', 'PRD-GLOBAL-00002', '84713010', NULL, NULL, 2, 18.00, 150.00, 130.00, 100.00, 175.00, NULL, 0.00, NULL, 12, NULL, 24, NULL, 'Red', 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-21 05:04:03', '2026-09-21 05:04:03'),
(15, 1, 2, 5, 6, 'ID crd', 'shsh', 'PRD-GLOBAL-00003', 'ndhdvdh', NULL, NULL, 1, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 'orange', 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-21 11:28:42', '2026-09-21 11:28:42'),
(16, 1, 6, 6, NULL, 'Widget X', 'PROD-1-00005', 'PRD-GLOBAL-00004', '84713010', NULL, NULL, 2, 18.00, 150.00, 130.00, 100.00, 175.00, NULL, 0.00, NULL, 12, NULL, 24, NULL, 'Red', 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-25 07:18:11', '2026-09-25 07:18:11'),
(17, 4, 7, 7, 7, 'SONY FW-43EZ20L', '43EZ20L', 'PRD-GLOBAL-00005', '85285200', '350 nits Brightness, Smart Android OS, 4K UHD Resolution, 16*7, Screen Mirroring through Airplay/Chromecast, Direct LED, 16 Gb storage, Wi-Fi, 178 Degree Viewing Angle, Landscape/Portrait/Tilt Installation, IP/RS232C Control, 10W+10W Speakers, HDMI 2.1 (3), USB (2), 1200:1 Contrast Ratio. 4K Processor.', NULL, 2, 18.00, 0.00, 31000.00, 0.00, 82000.00, NULL, 0.00, NULL, 36, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, 'https://pro.sony/en_IN/products/pro-displays/bravia-ez20l-series', 'products/5TIsJn1vzO4ndvNOvOAbBRYpCi1r0Pko6fRWSnx2.jpg', 'none', 2.0000, 3.0000, NULL, NULL, NULL, NULL, 1, '2026-09-25 12:03:27', '2026-09-25 12:03:27'),
(18, 4, 7, 7, 7, 'SONY FW-50EZ20L', '50EZ20L', 'PRD-GLOBAL-00006', '85285200', NULL, '350 nits Brightness, Smart Android OS, 4K UHD Resolution, 16*7, Screen Mirroring through Airplay/Chromecast, Direct LED, 16 Gb storage, Wi-Fi,   178 Degree Viewing Angle, Landscape/Portrait/Tilt Installation, IP/RS232C Control, 10W+10W Speakers, HDMI 2.1 (3), USB (2), 1200:1 Contrast Ratio. 4K Processor X1, 4K X-Reality PRO, Motionflow XR, HDR10/HLG', 2, 18.00, 0.00, 41000.00, 0.00, 101000.00, NULL, 0.00, NULL, NULL, '36', NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 1, NULL, NULL, NULL, 'serial', 1.0000, 1.0000, NULL, NULL, NULL, NULL, 1, '2026-09-27 10:15:18', '2026-09-27 10:15:18'),
(19, 4, 2, 5, 6, 'LED Bulb 12W', 'LED-12W-WHT-001', 'PRD-GLOBAL-00007', '85395200', 'Standard retail selling price per piece.', '12W LED bulb, cool white, 6500K, B22 base, 230V AC, energy efficient, suitable for residential and commercial lighting', 2, 18.00, 200.00, 150.00, 120.00, 180.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, 'Grey', 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-29 09:50:19', '2026-09-29 10:17:39'),
(20, 4, NULL, NULL, NULL, 'Tubelight', 'hshsh', 'PRD-GLOBAL-00008', 'SJDGDHL1', NULL, NULL, 3, 18.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-29 11:42:12', '2026-09-29 11:42:12'),
(22, 4, NULL, NULL, NULL, 'Tubelight', 'ADADDAd', 'PRD-GLOBAL-00009', '121212', NULL, NULL, 3, 18.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'none', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-09-29 11:42:41', '2026-09-29 11:42:41'),
(23, 1, 14, 13, 12, 'VIEWSONIC PROJECTOR PA503S-3', 'PA503S-3', 'PRD-GLOBAL-00010', '85286200', NULL, NULL, 2, 18.00, 0.00, 0.00, 0.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, 'percent', 0.00, 'percent', 0.00, 0, NULL, NULL, NULL, 'serial,batch', 0.0000, 0.0000, NULL, NULL, NULL, NULL, 1, '2026-10-02 10:51:26', '2026-10-02 10:51:26');

-- --------------------------------------------------------

--
-- Table structure for table `product_batches`
--

CREATE TABLE `product_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uom_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_no` varchar(60) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `mrp` decimal(12,2) DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_media`
--

CREATE TABLE `product_media` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'image',
  `path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_price_histories`
--

CREATE TABLE `product_price_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED DEFAULT NULL,
  `trade_price` decimal(12,2) DEFAULT NULL,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `mrp` decimal(12,2) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `change_reason` varchar(100) DEFAULT NULL,
  `source` varchar(30) NOT NULL DEFAULT 'manual',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_price_histories`
--

INSERT INTO `product_price_histories` (`id`, `product_id`, `uom_id`, `trade_price`, `selling_price`, `purchase_price`, `mrp`, `effective_from`, `effective_to`, `change_reason`, `source`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 8, 2, 900.00, 1000.00, 700.00, 0.00, '2026-09-09', NULL, NULL, 'manual', 2, '2026-09-09 11:25:19', '2026-09-09 11:25:19'),
(2, 9, 2, 75000.00, 80000.00, 70000.00, 0.00, '2026-09-10', NULL, NULL, 'manual', 9, '2026-09-10 09:29:53', '2026-09-10 09:29:53'),
(3, 10, 2, 140000.00, 150000.00, 130000.00, 0.00, '2026-09-10', '2026-09-16', NULL, 'manual', 9, '2026-09-10 09:31:21', '2026-09-17 05:51:17'),
(4, 10, 2, 180000.00, 190000.00, 150000.00, 0.00, '2026-09-17', NULL, 'auto-updated on save', 'update', 9, '2026-09-17 05:51:17', '2026-09-17 05:51:17'),
(5, 11, 2, 6000.00, 6000.00, 5000.00, 7000.00, '2026-09-17', NULL, 'created', 'create', 9, '2026-09-17 06:11:27', '2026-09-17 06:11:27'),
(6, 12, 2, 130.00, 150.00, 100.00, 175.00, '2026-09-17', NULL, 'created', 'create', 9, '2026-09-17 06:20:58', '2026-09-17 06:20:58'),
(7, 13, 2, 130.00, 150.00, 100.00, 175.00, '2026-09-19', NULL, 'created', 'create', 9, '2026-09-19 06:42:04', '2026-09-19 06:42:04'),
(8, 14, 2, 130.00, 150.00, 100.00, 175.00, '2026-09-21', NULL, 'created', 'create', 9, '2026-09-21 05:04:03', '2026-09-21 05:04:03'),
(9, 15, 1, 0.00, 0.00, 0.00, 0.00, '2026-09-21', NULL, 'created', 'create', 9, '2026-09-21 11:28:42', '2026-09-21 11:28:42'),
(10, 16, 2, 130.00, 150.00, 100.00, 175.00, '2026-09-25', NULL, 'created', 'create', 9, '2026-09-25 07:18:11', '2026-09-25 07:18:11'),
(11, 17, 2, 31000.00, 0.00, 0.00, 82000.00, '2026-09-25', NULL, 'created', 'create', 9, '2026-09-25 12:03:27', '2026-09-25 12:03:27'),
(12, 18, 2, 41000.00, 0.00, 0.00, 101000.00, '2026-09-27', NULL, 'created', 'create', 9, '2026-09-27 10:15:18', '2026-09-27 10:15:18'),
(13, 19, 2, 150.00, 200.00, 120.00, 180.00, '2026-09-29', NULL, 'created', 'create', 9, '2026-09-29 09:50:19', '2026-09-29 09:50:19'),
(14, 20, 3, 0.00, 0.00, 0.00, 0.00, '2026-09-29', NULL, 'created', 'create', 9, '2026-09-29 11:42:12', '2026-09-29 11:42:12'),
(15, 22, 3, 0.00, 0.00, 0.00, 0.00, '2026-09-29', NULL, 'created', 'create', 9, '2026-09-29 11:42:41', '2026-09-29 11:42:41'),
(16, 23, 2, 0.00, 0.00, 0.00, 0.00, '2026-10-02', NULL, 'created', 'create', 2, '2026-10-02 10:51:26', '2026-10-02 10:51:26');

-- --------------------------------------------------------

--
-- Table structure for table `product_serials`
--

CREATE TABLE `product_serials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `serial_number` varchar(255) NOT NULL,
  `status` enum('in_stock','reserved','sold','returned','scrapped','in_transit') NOT NULL DEFAULT 'in_stock',
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purchase_inward_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sold_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reservation_note` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reserved_for_type` varchar(255) DEFAULT NULL,
  `reserved_for_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_uoms`
--

CREATE TABLE `product_uoms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `conversion_factor` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `is_base` tinyint(1) NOT NULL DEFAULT 0,
  `label` varchar(60) DEFAULT NULL,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `trade_price` decimal(12,2) DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `mrp` decimal(12,2) DEFAULT NULL,
  `is_default_sales` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_uoms`
--

INSERT INTO `product_uoms` (`id`, `product_id`, `uom_id`, `conversion_factor`, `is_base`, `label`, `selling_price`, `trade_price`, `purchase_price`, `mrp`, `is_default_sales`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 1.0000, 1, 'Base', 0.00, 0.00, 0.00, 0.00, 1, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 1, 3, 25.0000, 0, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 2, 5, 1.0000, 1, 'Base', 0.00, 0.00, 0.00, 0.00, 1, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 2, 3, 12.0000, 0, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 3, 1, 1.0000, 1, 'Base', 0.00, 0.00, 0.00, 0.00, 1, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 3, 2, 0.1000, 0, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(7, 3, 3, 10.0000, 0, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(8, 8, 2, 1.0000, 1, 'Base', 1000.00, 900.00, 700.00, 0.00, 1, 1, '2026-09-09 11:25:19', '2026-09-09 11:25:19'),
(9, 9, 2, 1.0000, 1, 'Base', 80000.00, 75000.00, 70000.00, 0.00, 1, 1, '2026-09-10 09:29:53', '2026-09-10 09:29:53'),
(10, 10, 2, 1.0000, 1, 'Base', 190000.00, 180000.00, 150000.00, 0.00, 1, 1, '2026-09-10 09:31:21', '2026-09-17 05:51:17'),
(11, 10, 1, 12.0000, 0, 'Box of 12', 1000000.00, NULL, NULL, NULL, 0, 1, '2026-09-17 05:55:10', '2026-09-17 05:55:10'),
(12, 11, 2, 1.0000, 1, 'Base', 6000.00, 6000.00, 5000.00, 7000.00, 1, 1, '2026-09-17 06:11:27', '2026-09-17 06:11:27'),
(13, 12, 2, 1.0000, 1, 'Base', 150.00, 130.00, 100.00, 175.00, 1, 1, '2026-09-17 06:20:58', '2026-09-17 06:20:58'),
(14, 13, 2, 1.0000, 1, 'Base', 150.00, 130.00, 100.00, 175.00, 1, 1, '2026-09-19 06:42:04', '2026-09-19 06:42:04'),
(15, 14, 2, 1.0000, 1, 'Base', 150.00, 130.00, 100.00, 175.00, 1, 1, '2026-09-21 05:04:03', '2026-09-21 05:04:03'),
(16, 15, 1, 1.0000, 1, 'Base', 0.00, 0.00, 0.00, 0.00, 1, 1, '2026-09-21 11:28:42', '2026-09-21 11:28:42'),
(17, 16, 2, 1.0000, 1, 'Base', 150.00, 130.00, 100.00, 175.00, 1, 1, '2026-09-25 07:18:11', '2026-09-25 07:18:11'),
(18, 17, 2, 1.0000, 1, 'Base', 0.00, 31000.00, 0.00, 82000.00, 1, 1, '2026-09-25 12:03:27', '2026-09-25 12:03:27'),
(19, 18, 2, 1.0000, 1, 'Base', 0.00, 41000.00, 0.00, 101000.00, 1, 1, '2026-09-27 10:15:18', '2026-09-27 10:15:18'),
(20, 19, 2, 1.0000, 1, 'Base', 200.00, 150.00, 120.00, 180.00, 1, 1, '2026-09-29 09:50:19', '2026-09-29 09:50:19'),
(21, 20, 3, 1.0000, 1, 'Base', 0.00, 0.00, 0.00, 0.00, 1, 1, '2026-09-29 11:42:12', '2026-09-29 11:42:12'),
(22, 22, 3, 1.0000, 1, 'Base', 0.00, 0.00, 0.00, 0.00, 1, 1, '2026-09-29 11:42:41', '2026-09-29 11:42:41'),
(23, 23, 2, 1.0000, 1, 'Base', 0.00, 0.00, 0.00, 0.00, 1, 1, '2026-10-02 10:51:26', '2026-10-02 10:51:26');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_no` varchar(30) NOT NULL,
  `purchase_date` date NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `supplier_party_id` bigint(20) UNSIGNED DEFAULT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','posted') NOT NULL DEFAULT 'posted',
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `purchase_no`, `purchase_date`, `supplier_name`, `supplier_party_id`, `warehouse_id`, `status`, `grand_total`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PUR-0001', '2026-08-22', 'National Foods Supplier', NULL, NULL, 'posted', 51880.00, 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'PUR-20260901-0001', '2026-09-01', '50', NULL, NULL, 'posted', 500.00, 2, '2026-09-01 06:37:18', '2026-09-01 06:37:18'),
(3, 'PUR-20260903-0001', '2026-09-03', 'xyz', NULL, NULL, 'posted', 5000.00, 2, '2026-09-03 09:16:58', '2026-09-03 09:16:58');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoices`
--

CREATE TABLE `purchase_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purchase_inward_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_no` varchar(40) NOT NULL,
  `qr_token` varchar(36) DEFAULT NULL,
  `supplier_invoice_no` varchar(60) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `credit_days` int(10) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `due_date_basis` varchar(30) DEFAULT NULL,
  `due_date_source_date` date DEFAULT NULL,
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'posted',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `rate_override_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms_and_conditions` text DEFAULT NULL,
  `freight_allocation_method` varchar(20) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoices`
--

INSERT INTO `purchase_invoices` (`id`, `purchase_order_id`, `purchase_inward_id`, `supplier_id`, `warehouse_id`, `invoice_no`, `qr_token`, `supplier_invoice_no`, `invoice_date`, `credit_days`, `due_date`, `due_date_basis`, `due_date_source_date`, `status`, `subtotal`, `tax_amount`, `grand_total`, `rate_override_reason`, `notes`, `terms_and_conditions`, `freight_allocation_method`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 10, NULL, 'PI-20260910-0001', 'd70f57ea-021c-4928-8d27-d971be894777', NULL, '2026-09-10', NULL, '2026-10-10', 'invoice_date', '2026-09-10', 'posted', 7000000.00, 1260000.00, 8260000.00, NULL, NULL, NULL, NULL, 9, '2026-09-10 09:49:13', '2026-09-10 09:49:13'),
(2, 2, NULL, 10, NULL, 'PI-20260911-0001', '22933ae0-bd00-4729-a138-6a620f551f66', NULL, '2026-09-11', NULL, '2026-10-11', 'invoice_date', '2026-09-11', 'posted', 4000000.00, 0.00, 4000000.00, NULL, NULL, NULL, NULL, 9, '2026-09-11 07:40:05', '2026-09-11 07:40:05'),
(3, 6, NULL, 10, 1, 'PI-20260919-0001', 'c59acbad-47ab-4d47-8480-08481bc7a46e', NULL, '2026-09-19', NULL, '2026-10-19', 'invoice_date', '2026-09-19', 'posted', 81000.00, 13630.00, 94630.00, NULL, NULL, NULL, NULL, 9, '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(4, 6, NULL, 10, 1, 'PI-20260921-0001', '38ef34b9-eba7-43a0-913e-194673f7ce4f', NULL, '2026-09-21', NULL, '2026-10-21', 'invoice_date', '2026-09-21', 'posted', 80000.00, 13600.00, 93600.00, NULL, NULL, NULL, NULL, 9, '2026-09-21 05:33:11', '2026-09-21 05:33:11'),
(5, 6, NULL, 10, 1, 'PI-20260921-0002', '2e4b2ac9-0cfe-427c-ae8f-536f60423ef6', NULL, '2026-09-21', NULL, '2026-10-21', 'invoice_date', '2026-09-21', 'posted', 80000.00, 13600.00, 93600.00, NULL, NULL, NULL, NULL, 9, '2026-09-21 05:33:50', '2026-09-21 05:33:50'),
(6, NULL, NULL, 10, NULL, 'PI-20260921-0003', 'b0a3b5f7-da97-42bb-bdbc-58dbac83d27e', NULL, '2026-09-21', NULL, '2026-10-21', 'invoice_date', '2026-09-21', 'posted', 26000.00, 3120.00, 29120.00, NULL, NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.', NULL, 9, '2026-09-21 07:50:46', '2026-09-21 07:50:46'),
(7, 7, NULL, 10, NULL, 'PI-20260921-0004', '48798d1e-7d2b-46e0-b82d-391b2a5ae858', NULL, '2026-09-21', NULL, '2026-10-21', 'invoice_date', '2026-09-21', 'posted', 80000.00, 0.00, 80000.00, NULL, NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.', NULL, 9, '2026-09-21 11:23:37', '2026-09-21 11:23:37'),
(8, 6, NULL, 10, 1, 'PI-20260925-0001', '60b60ca2-9d5e-4ab7-a5e3-383aae49b6c7', NULL, '2026-09-25', NULL, '2026-10-25', 'invoice_date', '2026-09-25', 'posted', 75000.00, 13500.00, 88500.00, NULL, NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.', NULL, 9, '2026-09-25 06:45:28', '2026-09-25 06:45:28'),
(9, 6, NULL, 10, 1, 'PI-20260925-0002', 'c745698a-926a-4b4e-b17a-52d8f70d143d', NULL, '2026-09-25', NULL, '2026-10-25', 'invoice_date', '2026-09-25', 'posted', 1000.00, 30.00, 1030.00, NULL, NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.', NULL, 9, '2026-09-25 06:46:24', '2026-09-25 06:46:24'),
(10, NULL, NULL, 10, NULL, 'PI-20260925-0003', 'f04e3265-454a-4750-b78c-e5105af441d2', 'BHY-23242424', '2026-09-25', NULL, '2026-10-25', 'invoice_date', '2026-09-25', 'posted', 100.00, 19.00, 119.00, NULL, NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.\r\n4. test', NULL, 9, '2026-09-25 07:36:54', '2026-09-25 07:36:54'),
(11, NULL, NULL, 10, 2, 'PI-20260928-0001', '6cd203a5-6b54-4102-8265-31c6f0e8fefa', 'BLR/26-275199', '2026-09-28', NULL, '2026-10-28', 'invoice_date', '2026-09-28', 'posted', 31000.00, 5580.00, 36580.00, NULL, NULL, '1. Goods must be inspected at delivery.\r\n2. Shortage must be reported within 24 hours.\r\n3. Payment as agreed.', NULL, 9, '2026-09-28 09:11:23', '2026-09-28 09:11:23');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_invoice_items`
--

CREATE TABLE `purchase_invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `batch_no` varchar(60) DEFAULT NULL,
  `batch_selling_price` decimal(12,2) DEFAULT NULL,
  `batch_mrp` decimal(12,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `tax_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `other_vendor_rate` decimal(14,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_invoice_items`
--

INSERT INTO `purchase_invoice_items` (`id`, `purchase_invoice_id`, `product_id`, `uom_id`, `quantity`, `batch_no`, `batch_selling_price`, `batch_mrp`, `expiry_date`, `unit_cost`, `tax_percent`, `cgst_percent`, `sgst_percent`, `cgst_amount`, `sgst_amount`, `line_total`, `other_vendor_rate`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 1, 100.0000, NULL, NULL, NULL, NULL, 70000.0000, 18.00, 0.00, 0.00, 0.00, 0.00, 8260000.00, NULL, '2026-09-10 09:49:13', '2026-09-10 09:49:13'),
(2, 2, 9, 2, 50.0000, NULL, NULL, NULL, NULL, 80000.0000, 0.00, 0.00, 0.00, 0.00, 0.00, 4000000.00, NULL, '2026-09-11 07:40:05', '2026-09-11 07:40:05'),
(3, 3, 9, 1, 1.0000, NULL, NULL, NULL, NULL, 75000.0000, 18.00, 9.00, 9.00, 6750.00, 6750.00, 88500.00, NULL, '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(4, 3, 7, 1, 1.0000, NULL, NULL, NULL, NULL, 5000.0000, 2.00, 1.00, 1.00, 50.00, 50.00, 5100.00, NULL, '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(5, 3, 5, 1, 1.0000, NULL, NULL, NULL, NULL, 1000.0000, 3.00, 1.50, 1.50, 15.00, 15.00, 1030.00, NULL, '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(6, 4, 9, 1, 1.0000, NULL, NULL, NULL, NULL, 75000.0000, 18.00, 9.00, 9.00, 6750.00, 6750.00, 88500.00, NULL, '2026-09-21 05:33:11', '2026-09-21 05:33:11'),
(7, 4, 7, 1, 1.0000, NULL, NULL, NULL, NULL, 5000.0000, 2.00, 1.00, 1.00, 50.00, 50.00, 5100.00, NULL, '2026-09-21 05:33:11', '2026-09-21 05:33:11'),
(8, 5, 9, 1, 1.0000, NULL, NULL, NULL, NULL, 75000.0000, 18.00, 9.00, 9.00, 6750.00, 6750.00, 88500.00, NULL, '2026-09-21 05:33:50', '2026-09-21 05:33:50'),
(9, 5, 7, 1, 1.0000, NULL, NULL, NULL, NULL, 5000.0000, 2.00, 1.00, 1.00, 50.00, 50.00, 5100.00, NULL, '2026-09-21 05:33:50', '2026-09-21 05:33:50'),
(10, 6, 5, 2, 13.0000, NULL, NULL, NULL, NULL, 2000.0000, 12.00, 6.00, 6.00, 1560.00, 1560.00, 29120.00, NULL, '2026-09-21 07:50:46', '2026-09-21 07:50:46'),
(11, 7, 9, 2, 1.0000, NULL, NULL, NULL, NULL, 80000.0000, 0.00, 0.00, 0.00, 0.00, 0.00, 80000.00, NULL, '2026-09-21 11:23:37', '2026-09-21 11:23:37'),
(12, 8, 9, 1, 1.0000, NULL, NULL, NULL, NULL, 75000.0000, 18.00, 9.00, 9.00, 6750.00, 6750.00, 88500.00, NULL, '2026-09-25 06:45:28', '2026-09-25 06:45:28'),
(13, 9, 5, 1, 1.0000, NULL, NULL, NULL, NULL, 1000.0000, 3.00, 1.50, 1.50, 15.00, 15.00, 1030.00, NULL, '2026-09-25 06:46:24', '2026-09-25 06:46:24'),
(14, 10, 12, 2, 1.0000, NULL, NULL, NULL, NULL, 100.0000, 19.00, 10.00, 9.00, 10.00, 9.00, 119.00, NULL, '2026-09-25 07:36:54', '2026-09-25 07:36:54'),
(15, 11, 17, 2, 1.0000, NULL, NULL, NULL, NULL, 31000.0000, 18.00, 9.00, 9.00, 2790.00, 2790.00, 36580.00, NULL, '2026-09-28 09:11:23', '2026-09-28 09:11:23');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_inwards`
--

CREATE TABLE `purchase_inwards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inward_no` varchar(40) NOT NULL,
  `inward_date` date NOT NULL,
  `supplier_challan_no` varchar(60) DEFAULT NULL,
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'posted',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_inwards`
--

INSERT INTO `purchase_inwards` (`id`, `purchase_order_id`, `warehouse_id`, `supplier_id`, `inward_no`, `inward_date`, `supplier_challan_no`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 10, 'GRN-20260910-0001', '2026-09-10', NULL, 'posted', NULL, 9, '2026-09-10 09:41:55', '2026-09-10 09:41:55'),
(2, 2, 1, 10, 'GRN-20260911-0001', '2026-09-11', NULL, 'posted', NULL, 9, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(3, 3, 1, 10, 'GRN-20260917-0001', '2026-09-17', NULL, 'posted', NULL, 9, '2026-09-17 05:36:33', '2026-09-17 05:36:33'),
(4, 5, 2, 10, 'GRN-20260917-0002', '2026-09-17', NULL, 'posted', NULL, 9, '2026-09-17 07:19:15', '2026-09-17 07:19:15'),
(5, 6, 1, 10, 'GRN-20260919-0001', '2026-09-19', NULL, 'posted', NULL, 9, '2026-09-19 06:52:07', '2026-09-19 06:52:07');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_inward_items`
--

CREATE TABLE `purchase_inward_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_inward_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `ordered_qty` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `received_qty` decimal(14,4) NOT NULL,
  `accepted_qty` decimal(14,4) NOT NULL,
  `rejected_qty` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `batch_no` varchar(60) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `batch_selling_price` decimal(12,2) DEFAULT NULL,
  `batch_mrp` decimal(12,2) DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_inward_items`
--

INSERT INTO `purchase_inward_items` (`id`, `purchase_inward_id`, `purchase_order_item_id`, `product_id`, `uom_id`, `ordered_qty`, `received_qty`, `accepted_qty`, `rejected_qty`, `unit_cost`, `batch_no`, `expiry_date`, `batch_selling_price`, `batch_mrp`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 9, 2, 100.0000, 100.0000, 100.0000, 0.0000, 70000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-10 09:41:55', '2026-09-10 09:41:55'),
(2, 1, 2, 10, 2, 50.0000, 50.0000, 50.0000, 0.0000, 130000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-10 09:41:55', '2026-09-10 09:41:55'),
(3, 2, 3, 9, 2, 100.0000, 100.0000, 100.0000, 0.0000, 80000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(4, 2, 4, 10, 1, 10.0000, 10.0000, 10.0000, 0.0000, 150000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(5, 3, 5, 9, 1, 10.0000, 10.0000, 10.0000, 0.0000, 200000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-17 05:36:33', '2026-09-17 05:36:33'),
(6, 3, 6, 4, 1, 10.0000, 10.0000, 10.0000, 0.0000, 20.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-17 05:36:33', '2026-09-17 05:36:33'),
(7, 4, 8, 9, 1, 1.0000, 1.0000, 1.0000, 0.0000, 80000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-17 07:19:15', '2026-09-17 07:19:15'),
(8, 5, 9, 9, 1, 1.0000, 1.0000, 1.0000, 0.0000, 75000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(9, 5, 10, 7, 1, 1.0000, 1.0000, 1.0000, 0.0000, 5000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(10, 5, 11, 5, 1, 1.0000, 1.0000, 1.0000, 0.0000, 1000.0000, NULL, NULL, NULL, NULL, NULL, '2026-09-19 06:52:07', '2026-09-19 06:52:07');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `uom_id`, `quantity`, `unit_cost`, `line_total`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 500.0000, 38.00, 19000.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 1, 2, 5, 240.0000, 98.00, 23520.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 1, 3, 1, 120.0000, 78.00, 9360.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 2, 1, 4, 10.0000, 50.00, 500.00, '2026-09-01 06:37:18', '2026-09-01 06:37:18'),
(5, 3, 7, 2, 50.0000, 100.00, 5000.00, '2026-09-03 09:16:58', '2026-09-03 09:16:58');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `po_no` varchar(40) NOT NULL,
  `po_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','partially_received','closed','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `company_id`, `branch_id`, `warehouse_id`, `supplier_id`, `po_no`, `po_date`, `expected_date`, `status`, `subtotal`, `tax_amount`, `grand_total`, `notes`, `created_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 2, 10, 'PO-20260910-0001', '2026-09-10', '2026-09-11', 'closed', 13500000.00, 2430000.00, 15930000.00, NULL, 9, 9, '2026-09-10 09:41:23', '2026-09-10 09:41:02', '2026-09-10 09:41:55'),
(2, NULL, NULL, 1, 10, 'PO-20260911-0001', '2026-09-11', '2026-09-11', 'closed', 9500000.00, 1710000.00, 11210000.00, NULL, 9, 9, '2026-09-11 07:37:33', '2026-09-11 07:37:18', '2026-09-11 07:37:59'),
(3, NULL, NULL, 1, 10, 'PO-20260917-0001', '2026-09-17', '2026-09-30', 'closed', 2000200.00, 200036.00, 2200236.00, NULL, 9, 9, '2026-09-17 05:36:27', '2026-09-17 05:36:23', '2026-09-17 05:36:33'),
(4, NULL, NULL, 1, 10, 'PO-20260917-0002', '2026-09-17', NULL, 'draft', 80000.00, 0.00, 80000.00, NULL, 9, NULL, NULL, '2026-09-17 07:17:33', '2026-09-17 07:17:33'),
(5, NULL, NULL, 2, 10, 'PO-20260917-0003', '2026-09-17', NULL, 'closed', 80000.00, 0.00, 80000.00, NULL, 9, 9, '2026-09-17 07:19:07', '2026-09-17 07:18:57', '2026-09-17 07:19:15'),
(6, NULL, NULL, 1, 10, 'PO-20260919-0001', '2026-09-19', NULL, 'closed', 81000.00, 13630.00, 94630.00, NULL, 9, 9, '2026-09-19 06:51:12', '2026-09-19 06:51:08', '2026-09-19 06:52:07'),
(7, NULL, NULL, NULL, 10, 'PO-20260921-0001', '2026-09-21', NULL, 'approved', 1000.00, 0.00, 1000.00, NULL, 9, 9, '2026-09-21 07:47:23', '2026-09-21 07:47:20', '2026-09-21 07:47:23'),
(8, NULL, NULL, 2, 10, 'PO-20260925-0001', '2026-09-25', NULL, 'approved', 80000.00, 14400.00, 94400.00, NULL, 9, 9, '2026-09-25 07:42:57', '2026-09-25 07:42:49', '2026-09-25 07:42:57'),
(9, NULL, NULL, 3, 10, 'PO-20260928-0001', '2026-09-28', NULL, 'draft', 37000.00, 6660.00, 43660.00, NULL, 9, NULL, NULL, '2026-09-28 08:40:08', '2026-09-28 08:40:08'),
(10, NULL, NULL, 3, 10, 'PO-20260928-0002', '2026-09-28', NULL, 'draft', 20000.00, 3600.00, 23600.00, NULL, 9, NULL, NULL, '2026-09-28 08:43:17', '2026-09-28 08:43:17');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `batch_name` varchar(100) DEFAULT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `received_qty` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `tax_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `weight` decimal(14,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `purchase_order_id`, `product_id`, `uom_id`, `batch_name`, `quantity`, `received_qty`, `unit_cost`, `tax_percent`, `cgst_percent`, `sgst_percent`, `cgst_amount`, `sgst_amount`, `line_total`, `weight`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 2, NULL, 100.0000, 100.0000, 70000.0000, 18.00, 0.00, 0.00, 0.00, 0.00, 8260000.00, NULL, '2026-09-10 09:41:02', '2026-09-10 09:41:55'),
(2, 1, 10, 2, NULL, 50.0000, 50.0000, 130000.0000, 18.00, 0.00, 0.00, 0.00, 0.00, 7670000.00, NULL, '2026-09-10 09:41:02', '2026-09-10 09:41:55'),
(3, 2, 9, 2, NULL, 100.0000, 100.0000, 80000.0000, 18.00, 0.00, 0.00, 0.00, 0.00, 9440000.00, NULL, '2026-09-11 07:37:18', '2026-09-11 07:37:59'),
(4, 2, 10, 1, NULL, 10.0000, 10.0000, 150000.0000, 18.00, 0.00, 0.00, 0.00, 0.00, 1770000.00, NULL, '2026-09-11 07:37:18', '2026-09-11 07:37:59'),
(5, 3, 9, 1, NULL, 10.0000, 10.0000, 200000.0000, 10.00, 0.00, 0.00, 0.00, 0.00, 2200000.00, NULL, '2026-09-17 05:36:23', '2026-09-17 05:36:33'),
(6, 3, 4, 1, NULL, 10.0000, 10.0000, 20.0000, 18.00, 0.00, 0.00, 0.00, 0.00, 236.00, NULL, '2026-09-17 05:36:23', '2026-09-17 05:36:33'),
(7, 4, 9, 1, NULL, 1.0000, 0.0000, 80000.0000, 0.00, 0.00, 0.00, 0.00, 0.00, 80000.00, NULL, '2026-09-17 07:17:33', '2026-09-17 07:17:33'),
(8, 5, 9, 1, NULL, 1.0000, 1.0000, 80000.0000, 0.00, 0.00, 0.00, 0.00, 0.00, 80000.00, NULL, '2026-09-17 07:18:57', '2026-09-17 07:19:15'),
(9, 6, 9, 1, NULL, 1.0000, 1.0000, 75000.0000, 18.00, 9.00, 9.00, 6750.00, 6750.00, 88500.00, NULL, '2026-09-19 06:51:08', '2026-09-19 06:52:07'),
(10, 6, 7, 1, NULL, 1.0000, 1.0000, 5000.0000, 2.00, 1.00, 1.00, 50.00, 50.00, 5100.00, NULL, '2026-09-19 06:51:08', '2026-09-19 06:52:07'),
(11, 6, 5, 1, NULL, 1.0000, 1.0000, 1000.0000, 3.00, 1.50, 1.50, 15.00, 15.00, 1030.00, NULL, '2026-09-19 06:51:08', '2026-09-19 06:52:07'),
(12, 7, 13, 1, NULL, 1.0000, 0.0000, 1000.0000, 0.00, 0.00, 0.00, 0.00, 0.00, 1000.00, NULL, '2026-09-21 07:47:20', '2026-09-21 07:47:20'),
(13, 8, 9, 1, NULL, 1.0000, 0.0000, 80000.0000, 18.00, 9.00, 9.00, 7200.00, 7200.00, 94400.00, NULL, '2026-09-25 07:42:49', '2026-09-25 07:42:49'),
(14, 9, 9, 2, NULL, 1.0000, 0.0000, 37000.0000, 18.00, 9.00, 9.00, 3330.00, 3330.00, 43660.00, NULL, '2026-09-28 08:40:08', '2026-09-28 08:40:08'),
(15, 10, 8, 1, NULL, 1.0000, 0.0000, 10000.0000, 18.00, 9.00, 9.00, 900.00, 900.00, 11800.00, NULL, '2026-09-28 08:43:17', '2026-09-28 08:43:17'),
(16, 10, 9, 1, NULL, 1.0000, 0.0000, 10000.0000, 18.00, 9.00, 9.00, 900.00, 900.00, 11800.00, NULL, '2026-09-28 08:43:17', '2026-09-28 08:43:17');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quotation_no` varchar(30) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `salesperson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `status` enum('draft','sent','accepted','rejected','converted','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estimated_profit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `updated_by_name` varchar(100) DEFAULT NULL,
  `converted_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quotation_no`, `customer_id`, `salesperson_id`, `quotation_date`, `valid_until`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `grand_total`, `estimated_profit`, `notes`, `created_by_name`, `updated_by_name`, `converted_order_id`, `created_at`, `updated_at`) VALUES
(1, 'QTN-20260910-0001', 2, 9, '2026-09-10', '2026-11-10', 'accepted', 1600000.00, 0.00, 288000.00, 1888000.00, 200000.00, NULL, 'Client Admin', NULL, NULL, '2026-09-10 10:31:24', '2026-09-10 10:31:33');

-- --------------------------------------------------------

--
-- Table structure for table `quotation_items`
--

CREATE TABLE `quotation_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `quotation_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL,
  `estimated_landed_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estimated_profit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotation_items`
--

INSERT INTO `quotation_items` (`id`, `quotation_id`, `product_id`, `uom_id`, `quantity`, `unit_price`, `discount_amount`, `line_total`, `estimated_landed_cost`, `estimated_profit`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 2, 20.0000, 80000.00, 0.00, 1600000.00, 70000.00, 200000.00, '2026-09-10 10:31:24', '2026-09-10 10:31:24');

-- --------------------------------------------------------

--
-- Table structure for table `region_brand_policies`
--

CREATE TABLE `region_brand_policies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `area_id` bigint(20) UNSIGNED NOT NULL,
  `brand_id` bigint(20) UNSIGNED NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `max_discount_percent` decimal(5,2) DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `region_brand_policies`
--

INSERT INTO `region_brand_policies` (`id`, `area_id`, `brand_id`, `is_allowed`, `max_discount_percent`, `requires_approval`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 15.00, 1, NULL, 1, '2026-09-10 10:45:57', '2026-09-10 10:45:57');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'owner', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(2, 'super-admin', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(3, 'sales-manager', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(4, 'salesperson', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(5, 'warehouse', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(6, 'finance', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(7, 'driver', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(8, 'delivery-person', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59'),
(9, 'purchase', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(10, 'accounts', 'web', '2026-09-08 06:59:33', '2026-09-08 06:59:33'),
(11, 'management', 'web', '2026-09-08 06:59:34', '2026-09-08 06:59:34'),
(12, 'client-admin', 'web', '2026-09-10 07:56:18', '2026-09-10 07:56:18');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(2, 2),
(2, 12),
(3, 2),
(3, 3),
(3, 4),
(3, 9),
(3, 12),
(4, 2),
(4, 12),
(5, 2),
(5, 3),
(5, 4),
(5, 12),
(6, 2),
(6, 3),
(6, 12),
(7, 2),
(7, 5),
(7, 9),
(7, 12),
(8, 2),
(8, 5),
(8, 9),
(8, 12),
(9, 2),
(9, 3),
(9, 4),
(9, 6),
(9, 12),
(10, 2),
(10, 6),
(10, 12),
(11, 2),
(11, 6),
(11, 10),
(11, 12),
(12, 2),
(12, 6),
(12, 10),
(12, 12),
(13, 2),
(13, 12),
(14, 2),
(14, 12),
(15, 2),
(15, 7),
(15, 8),
(15, 12),
(16, 2),
(16, 7),
(16, 12),
(17, 2),
(17, 7),
(17, 8),
(17, 12),
(18, 2),
(18, 7),
(18, 8),
(18, 12),
(19, 2),
(19, 6),
(19, 12),
(20, 2),
(20, 6),
(20, 12),
(21, 1),
(21, 2),
(21, 3),
(21, 6),
(21, 10),
(21, 11),
(21, 12),
(22, 2),
(22, 3),
(22, 11),
(22, 12),
(23, 2),
(23, 3),
(23, 12),
(24, 2),
(24, 3),
(24, 4),
(24, 12),
(25, 2),
(25, 3),
(25, 12),
(26, 2),
(26, 6),
(26, 10),
(26, 12),
(27, 2),
(27, 7),
(27, 8),
(27, 12),
(28, 2),
(28, 4),
(28, 12),
(29, 2),
(29, 6),
(29, 12),
(30, 2),
(30, 12),
(31, 2),
(31, 12),
(32, 2),
(32, 11),
(32, 12),
(33, 2),
(33, 12),
(34, 2),
(34, 12),
(35, 2),
(35, 12),
(36, 2),
(36, 12),
(37, 2),
(37, 12),
(38, 2),
(38, 3),
(38, 4),
(38, 9),
(38, 12),
(39, 2),
(39, 12),
(40, 2),
(40, 12),
(41, 2),
(41, 12),
(42, 2),
(42, 3),
(42, 4),
(42, 9),
(42, 12),
(43, 2),
(43, 12),
(44, 2),
(44, 12),
(45, 2),
(45, 12),
(46, 2),
(46, 3),
(46, 4),
(46, 12),
(47, 2),
(47, 12),
(48, 2),
(48, 12),
(49, 2),
(49, 12),
(50, 2),
(50, 3),
(50, 4),
(50, 12),
(51, 2),
(51, 3),
(51, 4),
(51, 12),
(52, 2),
(52, 12),
(53, 2),
(53, 12),
(54, 2),
(54, 12),
(55, 2),
(55, 12),
(56, 2),
(56, 12),
(57, 2),
(57, 12),
(58, 2),
(58, 5),
(58, 9),
(58, 12),
(59, 2),
(59, 12),
(60, 2),
(60, 12),
(61, 2),
(61, 12),
(62, 2),
(62, 5),
(62, 9),
(62, 12),
(63, 2),
(63, 9),
(63, 12),
(64, 2),
(64, 9),
(64, 12),
(65, 2),
(65, 9),
(65, 12),
(66, 2),
(66, 9),
(66, 12),
(67, 2),
(67, 9),
(67, 12),
(68, 2),
(68, 9),
(68, 12),
(69, 2),
(69, 9),
(69, 12),
(70, 2),
(70, 6),
(70, 12),
(71, 2),
(71, 6),
(71, 12),
(72, 2),
(72, 3),
(72, 4),
(72, 6),
(72, 10),
(72, 12),
(73, 2),
(73, 6),
(73, 12),
(74, 2),
(74, 6),
(74, 12),
(75, 2),
(75, 6),
(75, 12),
(76, 2),
(76, 6),
(76, 10),
(76, 12),
(77, 2),
(77, 6),
(77, 10),
(77, 12),
(78, 2),
(78, 10),
(78, 12),
(79, 2),
(79, 10),
(79, 12),
(80, 2),
(80, 10),
(80, 12),
(81, 2),
(81, 10),
(81, 12),
(82, 2),
(82, 10),
(82, 12),
(83, 2),
(83, 10),
(83, 12),
(84, 2),
(84, 10),
(84, 12),
(85, 2),
(85, 10),
(85, 12),
(86, 2),
(86, 12),
(87, 2),
(87, 12),
(88, 2),
(88, 12),
(89, 2),
(89, 12),
(90, 2),
(90, 12),
(91, 2),
(91, 12),
(92, 2),
(92, 6),
(92, 12),
(93, 2),
(93, 6),
(93, 12),
(94, 2),
(94, 11),
(94, 12),
(95, 2),
(95, 12),
(96, 2),
(96, 12),
(97, 2),
(97, 12),
(98, 2),
(98, 11),
(98, 12),
(99, 2),
(99, 12),
(100, 2),
(100, 12),
(101, 2),
(101, 12),
(102, 2),
(102, 11),
(102, 12),
(103, 2),
(103, 12),
(104, 2),
(104, 12),
(105, 2),
(105, 12),
(106, 2),
(106, 10),
(106, 12),
(107, 2),
(107, 12),
(108, 2),
(108, 12),
(109, 2),
(109, 10),
(109, 12),
(110, 2),
(110, 12),
(111, 2),
(111, 12),
(112, 2),
(112, 12),
(113, 2),
(113, 12),
(114, 2),
(114, 12),
(115, 2),
(115, 12),
(116, 2),
(116, 12),
(117, 2),
(117, 12),
(118, 2),
(118, 12),
(119, 2),
(119, 12),
(120, 2),
(120, 12),
(121, 2),
(121, 12),
(122, 2),
(122, 12),
(123, 2),
(123, 12),
(124, 2),
(124, 12),
(125, 2),
(125, 12),
(126, 2),
(126, 12),
(127, 2),
(127, 10),
(127, 12);

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `area_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `name`, `code`, `area_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Route A - North', 'RT-A', 1, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'Route B - South', 'RT-B', 2, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'Route 1', 'ROUTE_1', NULL, 1, '2026-09-19 06:42:15', '2026-09-19 06:42:15');

-- --------------------------------------------------------

--
-- Table structure for table `salary_structures`
--

CREATE TABLE `salary_structures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `basic` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hra` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schemes`
--

CREATE TABLE `schemes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand_id` bigint(20) UNSIGNED DEFAULT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `status` enum('draft','active','closed','cancelled') NOT NULL DEFAULT 'draft',
  `basis` enum('quantity','value') NOT NULL DEFAULT 'value',
  `net_credit_notes` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheme_achievements`
--

CREATE TABLE `scheme_achievements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scheme_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qualified_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `benefit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('provisional','final','cancelled') NOT NULL DEFAULT 'provisional',
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot`)),
  `calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheme_products`
--

CREATE TABLE `scheme_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scheme_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheme_settlements`
--

CREATE TABLE `scheme_settlements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scheme_id` bigint(20) UNSIGNED NOT NULL,
  `scheme_achievement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` enum('pending','settled','cancelled') NOT NULL DEFAULT 'pending',
  `settlement_ref` varchar(255) DEFAULT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scheme_slabs`
--

CREATE TABLE `scheme_slabs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scheme_id` bigint(20) UNSIGNED NOT NULL,
  `from_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `to_value` decimal(14,2) DEFAULT NULL,
  `benefit_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `benefit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('2AEj1ESjrfguXosBIkRZTlc2V8prxUz5NMd1luto', 2, '103.48.101.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoicWpDaTg1REh4Rm5yam1melFHNkpHR2R6OEJZeFE4NndTMGhJYVZqZCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHBzOi8vZG1zLmV4dHJhYWF6LmNvbS9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1791025748),
('bqFJjd3V5hjs0p65FT1xlF2LFgd3AxJyxEwuuZxS', 1, '171.61.29.120', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidXlBVU00MXI2TGxjUUg5NFNuc1hZejI4UEsxUXp4d0JBWUVFZ01qNCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo1NDoiaHR0cHM6Ly9kbXMuZXh0cmFhYXouY29tL2NvbXBhbmllcy8xL2xvZ28/dj0xNzkwNjg4NzQ2IjtzOjU6InJvdXRlIjtzOjI3OiJvcmdhbml6YXRpb24uY29tcGFuaWVzLmxvZ28iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1791022152),
('fQiwvTlmrIa2mCfn6n7opwIn37EVZpvzJ2o7dUlk', 2, '2404:bd00:3:d35f:2018:86ec:7e19:af17', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOVI1bzltR09DMTVLUGRIRk90bFhvcW51WHJxN2hheWlOT014ckZwRCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTQ6Imh0dHBzOi8vZG1zLmV4dHJhYWF6LmNvbS9jb21wYW5pZXMvMS9sb2dvP3Y9MTc5MDY4ODc0NiI7czo1OiJyb3V0ZSI7czoyNzoib3JnYW5pemF0aW9uLmNvbXBhbmllcy5sb2dvIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9', 1791010171),
('Mr24ZtcBrl8taBvkoG4IXTYOckD2JD1eNQ5kJeco', NULL, '103.48.101.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiS2tJV1pBVGpjcFVtYjJucThmMnVybEx2Y2VqeWV3UDBUN095V1FociI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNDoiaHR0cHM6Ly9kbXMuZXh0cmFhYXouY29tL2Rhc2hib2FyZCI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjMwOiJodHRwczovL2Rtcy5leHRyYWFhei5jb20vbG9naW4iO3M6NToicm91dGUiO3M6NToibG9naW4iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1791009285),
('OGSrc43R97qwUesQEp4HZBmWlJdoyRFD0Vubq5cW', 9, '2401:4900:57d5:4fdc:d8f0:8d02:4363:ce27', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoidEpYYUJ0QW1VUWUwdHdzSVVtZUpSNUFvbGx3UkRqNDlsbmozMXlkSCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjU0OiJodHRwczovL2Rtcy5leHRyYWFhei5jb20vY29tcGFuaWVzLzEvbG9nbz92PTE3OTA2ODg3NDYiO3M6NToicm91dGUiO3M6Mjc6Im9yZ2FuaXphdGlvbi5jb21wYW5pZXMubG9nbyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjk7fQ==', 1791010454),
('PBkbxsh33cpKXKcXIw5G8aXrW8oNzYywuL4uCFO0', 1, '171.61.29.120', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSUUxUE1TQ05EbWRVcXgxamtNQmJLN1VTczZsbWRZVjZtUXZPbm8xWSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo1NDoiaHR0cHM6Ly9kbXMuZXh0cmFhYXouY29tL2NvbXBhbmllcy8xL2xvZ28/dj0xNzkwNjg4NzQ2IjtzOjU6InJvdXRlIjtzOjI3OiJvcmdhbml6YXRpb24uY29tcGFuaWVzLmxvZ28iO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1791005363),
('xPtBtRQ5grpM5AihuzzF3hvGbuHYP8DZY0OAGBLt', 9, '103.48.101.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNVF3YkJpYkNmZEcyd2JDemtLT3Uyd293YUwzVHNaQjNFblU3T29EWiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzQ6Imh0dHBzOi8vZG1zLmV4dHJhYWF6LmNvbS9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6OToiZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6OTt9', 1791007948);

-- --------------------------------------------------------

--
-- Table structure for table `settlements`
--

CREATE TABLE `settlements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `settlement_no` varchar(30) NOT NULL,
  `load_sheet_id` bigint(20) UNSIGNED NOT NULL,
  `cash_collected` decimal(14,2) NOT NULL DEFAULT 0.00,
  `upi_collected` decimal(14,2) NOT NULL DEFAULT 0.00,
  `outstanding_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','completed') NOT NULL DEFAULT 'completed',
  `settled_by` bigint(20) UNSIGNED NOT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settlements`
--

INSERT INTO `settlements` (`id`, `settlement_no`, `load_sheet_id`, `cash_collected`, `upi_collected`, `outstanding_amount`, `status`, `settled_by`, `settled_at`, `created_at`, `updated_at`) VALUES
(1, 'SET-20260903-0001', 1, 548.16, 3000.00, 3548.16, 'completed', 2, '2026-09-03 09:31:51', '2026-09-03 09:31:51', '2026-09-03 09:31:51'),
(2, 'SET-20260910-0001', 3, 0.00, 0.00, 3160.08, 'completed', 9, '2026-09-10 10:48:48', '2026-09-10 10:48:48', '2026-09-10 10:48:48'),
(3, 'SET-20260910-0002', 2, 0.00, 0.00, 430.08, 'completed', 9, '2026-09-10 10:48:54', '2026-09-10 10:48:54', '2026-09-10 10:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `settlement_lines`
--

CREATE TABLE `settlement_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `settlement_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cash_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `upi_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `outstanding_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settlement_lines`
--

INSERT INTO `settlement_lines` (`id`, `settlement_id`, `customer_id`, `invoice_id`, `cash_amount`, `upi_amount`, `outstanding_amount`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, 0.00, 0.00, 0.00, '2026-09-03 09:31:51', '2026-09-03 09:31:51'),
(2, 1, 2, 2, 548.16, 3000.00, 3548.16, '2026-09-03 09:31:51', '2026-09-03 09:31:51'),
(3, 2, 1, 3, 0.00, 0.00, 2730.00, '2026-09-10 10:48:48', '2026-09-10 10:48:48'),
(4, 2, 3, 10, 0.00, 0.00, 430.08, '2026-09-10 10:48:48', '2026-09-10 10:48:48'),
(5, 3, 3, 10, 0.00, 0.00, 430.08, '2026-09-10 10:48:54', '2026-09-10 10:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `adjustment_no` varchar(40) NOT NULL,
  `adjustment_date` date NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reason` enum('opening','damage','shrinkage','found','recount','other') NOT NULL DEFAULT 'other',
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `adjustment_no`, `adjustment_date`, `warehouse_id`, `reason`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'ADJ-20260910-0001', '2026-09-10', 1, 'damage', 'posted', NULL, 9, '2026-09-10 10:10:40', '2026-09-10 10:10:40'),
(2, 'ADJ-20260910-0002', '2026-09-10', 1, 'other', 'posted', NULL, 9, '2026-09-10 10:11:17', '2026-09-10 10:11:17'),
(3, 'ADJ-20260928-0001', '2026-09-28', 3, 'opening', 'posted', NULL, 9, '2026-09-28 09:56:07', '2026-09-28 09:56:07');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustment_items`
--

CREATE TABLE `stock_adjustment_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stock_adjustment_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_adjustment_items`
--

INSERT INTO `stock_adjustment_items` (`id`, `stock_adjustment_id`, `product_id`, `uom_id`, `quantity`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 9, 2, -10.0000, NULL, '2026-09-10 10:10:40', '2026-09-10 10:10:40'),
(2, 2, 9, 2, 10.0000, NULL, '2026-09-10 10:11:17', '2026-09-10 10:11:17'),
(3, 3, 9, 1, -1.0000, NULL, '2026-09-28 09:56:07', '2026-09-28 09:56:07');

-- --------------------------------------------------------

--
-- Table structure for table `stock_cost_consumptions`
--

CREATE TABLE `stock_cost_consumptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stock_cost_layer_id` bigint(20) UNSIGNED NOT NULL,
  `stock_movement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_cost_consumptions`
--

INSERT INTO `stock_cost_consumptions` (`id`, `stock_cost_layer_id`, `stock_movement_id`, `product_id`, `quantity`, `unit_cost`, `reference_type`, `reference_id`, `created_at`, `updated_at`) VALUES
(1, 1, 12, 9, 50.0000, 70000.0000, 'App\\Domains\\Inventory\\Models\\StockTransfer', 2, '2026-09-10 09:59:32', '2026-09-10 09:59:32'),
(2, 3, 14, 9, 10.0000, 70000.0000, 'App\\Domains\\Inventory\\Models\\StockAdjustment', 1, '2026-09-10 10:10:40', '2026-09-10 10:10:40');

-- --------------------------------------------------------

--
-- Table structure for table `stock_cost_layers`
--

CREATE TABLE `stock_cost_layers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity_remaining` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `landed_unit_cost` decimal(14,4) DEFAULT NULL,
  `received_on` date DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `batch_no` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_cost_layers`
--

INSERT INTO `stock_cost_layers` (`id`, `company_id`, `warehouse_id`, `product_id`, `uom_id`, `quantity_remaining`, `unit_cost`, `landed_unit_cost`, `received_on`, `source_type`, `source_id`, `batch_no`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 9, 2, 50.0000, 70000.0000, NULL, '2026-09-10', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 1, NULL, '2026-09-10 09:41:55', '2026-09-10 09:59:32'),
(2, 1, 2, 10, 2, 50.0000, 130000.0000, NULL, '2026-09-10', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 1, NULL, '2026-09-10 09:41:55', '2026-09-10 09:41:55'),
(3, 1, 1, 9, 2, 40.0000, 70000.0000, NULL, '2026-09-10', 'App\\Domains\\Inventory\\Models\\StockTransfer', 2, NULL, '2026-09-10 09:59:45', '2026-09-10 10:10:40'),
(4, 1, 1, 9, 2, 10.0000, 70000.0000, NULL, '2026-09-10', 'App\\Domains\\Inventory\\Models\\StockAdjustment', 2, NULL, '2026-09-10 10:11:17', '2026-09-10 10:11:17'),
(5, 1, 1, 9, 2, 100.0000, 70000.0000, NULL, '2026-09-11', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 2, NULL, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(6, 1, 1, 10, 1, 10.0000, 130000.0000, NULL, '2026-09-11', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 2, NULL, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(7, 1, 1, 9, 1, 10.0000, 70000.0000, NULL, '2026-09-17', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 3, NULL, '2026-09-17 05:36:33', '2026-09-17 05:36:33'),
(8, 1, 1, 4, 1, 10.0000, 0.0000, NULL, '2026-09-17', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 3, NULL, '2026-09-17 05:36:33', '2026-09-17 05:36:33'),
(9, 1, 2, 9, 1, 1.0000, 70000.0000, NULL, '2026-09-17', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 4, NULL, '2026-09-17 07:19:15', '2026-09-17 07:19:15'),
(10, 1, 1, 9, 1, 1.0000, 70000.0000, NULL, '2026-09-19', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 5, NULL, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(11, 1, 1, 7, 1, 1.0000, 0.0000, NULL, '2026-09-19', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 5, NULL, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(12, 1, 1, 5, 1, 1.0000, 0.0000, NULL, '2026-09-19', 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 5, NULL, '2026-09-19 06:52:07', '2026-09-19 06:52:07');

-- --------------------------------------------------------

--
-- Table structure for table `stock_levels`
--

CREATE TABLE `stock_levels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_levels`
--

INSERT INTO `stock_levels` (`id`, `warehouse_id`, `product_id`, `uom_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 4, 449.0000, '2026-08-29 04:40:01', '2026-09-01 11:32:35'),
(2, NULL, 2, 5, 236.0000, '2026-08-29 04:40:01', '2026-09-17 06:41:06'),
(3, NULL, 3, 1, 8.0000, '2026-08-29 04:40:01', '2026-08-29 04:40:02'),
(8, NULL, 7, 2, 49.0000, '2026-09-03 09:16:58', '2026-09-17 06:38:05'),
(9, 2, 9, 2, 50.0000, '2026-09-10 09:41:55', '2026-09-10 09:59:32'),
(10, 2, 10, 2, 50.0000, '2026-09-10 09:41:55', '2026-09-10 09:41:55'),
(12, 1, 9, 2, 150.0000, '2026-09-10 09:59:45', '2026-09-11 07:37:59'),
(16, NULL, 9, 2, -102.0000, '2026-09-10 12:17:47', '2026-09-17 06:41:06'),
(17, 1, 10, 1, 10.0000, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(18, 1, 9, 1, 11.0000, '2026-09-17 05:36:33', '2026-09-19 06:52:07'),
(19, 1, 4, 1, 10.0000, '2026-09-17 05:36:33', '2026-09-17 05:36:33'),
(20, 2, 9, 1, 1.0000, '2026-09-17 07:19:15', '2026-09-17 07:19:15'),
(21, NULL, 6, 1, -2.0000, '2026-09-19 06:45:26', '2026-09-19 06:45:26'),
(22, 1, 7, 1, 1.0000, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(23, 1, 5, 1, 1.0000, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(24, 3, 9, 1, -1.0000, '2026-09-28 09:56:07', '2026-09-28 09:56:07');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('purchase','sale','adjustment','return','delivery_short','transfer_in','transfer_out','opening','inward') NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `balance_after` decimal(14,4) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `warehouse_id`, `product_id`, `uom_id`, `type`, `quantity`, `balance_after`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 4, 'purchase', 500.0000, 500.0000, 'App\\Domains\\Inventory\\Models\\Purchase', 1, 'Initial demo stock purchase', 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, NULL, 2, 5, 'purchase', 240.0000, 240.0000, 'App\\Domains\\Inventory\\Models\\Purchase', 1, 'Initial demo stock purchase', 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, NULL, 3, 1, 'purchase', 120.0000, 120.0000, 'App\\Domains\\Inventory\\Models\\Purchase', 1, 'Initial demo stock purchase', 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, NULL, 1, 4, 'sale', -50.0000, 450.0000, 'App\\Domains\\Sales\\Models\\Invoice', 3, 'Sale from order ORD-1001', NULL, '2026-09-01 05:24:22', '2026-09-01 05:24:22'),
(5, NULL, 1, 4, 'sale', -1.0000, 449.0000, 'App\\Domains\\Sales\\Models\\Invoice', 6, 'Sale from order ORD-20260901-0003', NULL, '2026-09-01 06:27:04', '2026-09-01 06:27:04'),
(6, NULL, 1, 4, 'purchase', 10.0000, 459.0000, 'App\\Domains\\Inventory\\Models\\Purchase', 2, 'Purchase PUR-20260901-0001', 2, '2026-09-01 06:37:18', '2026-09-01 06:37:18'),
(7, NULL, 1, 4, 'sale', -10.0000, 449.0000, 'App\\Domains\\Sales\\Models\\Invoice', 9, 'Sale from order ORD-20260901-0005', NULL, '2026-09-01 11:32:35', '2026-09-01 11:32:35'),
(8, NULL, 2, 5, 'sale', -3.0000, 237.0000, 'App\\Domains\\Sales\\Models\\Invoice', 10, 'Sale from order ORD-20260903-0001: 3 Litre = 3 Litre', 2, '2026-09-03 07:50:55', '2026-09-03 07:50:55'),
(9, NULL, 7, 2, 'purchase', 50.0000, 50.0000, 'App\\Domains\\Inventory\\Models\\Purchase', 3, 'Purchase PUR-20260903-0001', 2, '2026-09-03 09:16:58', '2026-09-03 09:16:58'),
(10, 2, 9, 2, 'inward', 100.0000, 100.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 1, 'GRN GRN-20260910-0001', 9, '2026-09-10 09:41:55', '2026-09-10 09:41:55'),
(11, 2, 10, 2, 'inward', 50.0000, 50.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 1, 'GRN GRN-20260910-0001', 9, '2026-09-10 09:41:55', '2026-09-10 09:41:55'),
(12, 2, 9, 2, 'transfer_out', -50.0000, 50.0000, 'App\\Domains\\Inventory\\Models\\StockTransfer', 2, 'Transfer TRF-20260910-0001 out', 9, '2026-09-10 09:59:32', '2026-09-10 09:59:32'),
(13, 1, 9, 2, 'transfer_in', 50.0000, 50.0000, 'App\\Domains\\Inventory\\Models\\StockTransfer', 2, 'Transfer TRF-20260910-0001 in', 9, '2026-09-10 09:59:45', '2026-09-10 09:59:45'),
(14, 1, 9, 2, 'adjustment', -10.0000, 40.0000, 'App\\Domains\\Inventory\\Models\\StockAdjustment', 1, 'Adjustment ADJ-20260910-0001', 9, '2026-09-10 10:10:40', '2026-09-10 10:10:40'),
(15, 1, 9, 2, 'adjustment', 10.0000, 50.0000, 'App\\Domains\\Inventory\\Models\\StockAdjustment', 2, 'Adjustment ADJ-20260910-0002', 9, '2026-09-10 10:11:17', '2026-09-10 10:11:17'),
(16, NULL, 9, 2, 'sale', -100.0000, -100.0000, 'App\\Domains\\Sales\\Models\\Invoice', 14, 'Sale from order ORD-20260910-0001: 100 Piece = 100 Piece', 9, '2026-09-10 12:17:47', '2026-09-10 12:17:47'),
(17, 1, 9, 2, 'inward', 100.0000, 150.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 2, 'GRN GRN-20260911-0001', 9, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(18, 1, 10, 1, 'inward', 10.0000, 10.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 2, 'GRN GRN-20260911-0001', 9, '2026-09-11 07:37:59', '2026-09-11 07:37:59'),
(19, 1, 9, 1, 'inward', 10.0000, 10.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 3, 'GRN GRN-20260917-0001', 9, '2026-09-17 05:36:33', '2026-09-17 05:36:33'),
(20, 1, 4, 1, 'inward', 10.0000, 10.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 3, 'GRN GRN-20260917-0001', 9, '2026-09-17 05:36:33', '2026-09-17 05:36:33'),
(21, NULL, 7, 2, 'sale', -1.0000, 49.0000, 'App\\Domains\\Sales\\Models\\Invoice', 15, 'Direct invoice INV-20260917-0001', 9, '2026-09-17 06:38:05', '2026-09-17 06:38:05'),
(22, NULL, 9, 2, 'sale', -1.0000, -101.0000, 'App\\Domains\\Sales\\Models\\Invoice', 15, 'Direct invoice INV-20260917-0001', 9, '2026-09-17 06:38:05', '2026-09-17 06:38:05'),
(23, NULL, 9, 2, 'sale', -1.0000, -102.0000, 'App\\Domains\\Sales\\Models\\Invoice', 16, 'Sale from order ORD-20260917-0001: 1 Piece = 1 Piece', 9, '2026-09-17 06:41:06', '2026-09-17 06:41:06'),
(24, NULL, 2, 5, 'sale', -1.0000, 236.0000, 'App\\Domains\\Sales\\Models\\Invoice', 16, 'Sale from order ORD-20260917-0001: 1 Litre = 1 Litre', 9, '2026-09-17 06:41:06', '2026-09-17 06:41:06'),
(25, 2, 9, 1, 'inward', 1.0000, 1.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 4, 'GRN GRN-20260917-0002', 9, '2026-09-17 07:19:15', '2026-09-17 07:19:15'),
(26, NULL, 6, 1, 'sale', -1.0000, -1.0000, 'App\\Domains\\Sales\\Models\\Invoice', 17, 'Direct invoice INV-20260919-0001', 9, '2026-09-19 06:45:26', '2026-09-19 06:45:26'),
(27, NULL, 6, 1, 'sale', -1.0000, -2.0000, 'App\\Domains\\Sales\\Models\\Invoice', 17, 'Direct invoice INV-20260919-0001', 9, '2026-09-19 06:45:26', '2026-09-19 06:45:26'),
(28, 1, 9, 1, 'inward', 1.0000, 11.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 5, 'GRN GRN-20260919-0001', 9, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(29, 1, 7, 1, 'inward', 1.0000, 1.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 5, 'GRN GRN-20260919-0001', 9, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(30, 1, 5, 1, 'inward', 1.0000, 1.0000, 'App\\Domains\\Purchasing\\Models\\PurchaseInward', 5, 'GRN GRN-20260919-0001', 9, '2026-09-19 06:52:07', '2026-09-19 06:52:07'),
(31, 3, 9, 1, 'opening', -1.0000, -1.0000, 'App\\Domains\\Inventory\\Models\\StockAdjustment', 3, 'Adjustment ADJ-20260928-0001', 9, '2026-09-28 09:56:07', '2026-09-28 09:56:07');

-- --------------------------------------------------------

--
-- Table structure for table `stock_reservations`
--

CREATE TABLE `stock_reservations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `status` enum('active','released','fulfilled','partial','consumed','cancelled') NOT NULL DEFAULT 'active',
  `due_date` date DEFAULT NULL,
  `reserved_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_reservations`
--

INSERT INTO `stock_reservations` (`id`, `order_id`, `order_item_id`, `product_id`, `uom_id`, `warehouse_id`, `quantity`, `status`, `due_date`, `reserved_at`, `released_at`, `notes`, `reference_type`, `reference_id`, `expires_at`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 14, 15, 9, 2, NULL, 50.0000, 'active', '2026-09-30', '2026-09-10 12:17:24', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-10 12:17:24', '2026-09-10 12:17:24'),
(3, 15, 17, 2, 5, NULL, 1.0000, 'active', NULL, '2026-09-17 06:40:53', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-17 06:40:53', '2026-09-17 06:40:53');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transfer_no` varchar(40) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `to_warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('draft','in_transit','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_transfers`
--

INSERT INTO `stock_transfers` (`id`, `transfer_no`, `transfer_date`, `from_warehouse_id`, `to_warehouse_id`, `status`, `notes`, `created_by`, `received_at`, `created_at`, `updated_at`) VALUES
(2, 'TRF-20260910-0001', '2026-09-10', 2, 1, 'received', NULL, 9, '2026-09-10 09:59:45', '2026-09-10 09:59:32', '2026-09-10 09:59:45');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stock_transfer_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_transfer_items`
--

INSERT INTO `stock_transfer_items` (`id`, `stock_transfer_id`, `product_id`, `uom_id`, `quantity`, `created_at`, `updated_at`) VALUES
(2, 2, 9, 2, 50.0000, '2026-09-10 09:59:32', '2026-09-10 09:59:32');

-- --------------------------------------------------------

--
-- Table structure for table `stock_valuation_settings`
--

CREATE TABLE `stock_valuation_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `financial_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `method` enum('fifo','lifo','weighted_avg') NOT NULL DEFAULT 'fifo',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_valuation_settings`
--

INSERT INTO `stock_valuation_settings` (`id`, `company_id`, `financial_year_id`, `method`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'fifo', 1, '2026-09-10 10:19:26', '2026-09-10 10:19:26'),
(2, 1, NULL, 'fifo', 1, '2026-09-10 10:19:35', '2026-09-10 10:19:35');

-- --------------------------------------------------------

--
-- Table structure for table `sub_categories`
--

CREATE TABLE `sub_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sub_categories`
--

INSERT INTO `sub_categories` (`id`, `category_id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'straight fit', '003', 1, '2026-09-09 11:23:10', '2026-09-09 11:23:10'),
(2, 1, 'bellbottom', '002', 1, '2026-09-09 11:23:24', '2026-09-09 11:23:24'),
(3, 1, 'slim fit', '001', 1, '2026-09-09 11:23:43', '2026-09-09 11:23:43'),
(4, 2, '18', '0018', 1, '2026-09-10 09:27:07', '2026-09-10 09:27:07'),
(5, 2, '18pro', '0018p', 1, '2026-09-10 09:27:22', '2026-09-10 09:27:22'),
(6, 2, '18 pro max', '0018pm', 1, '2026-09-10 09:27:43', '2026-09-10 09:27:43'),
(7, 7, 'EZ20L', 'EZ20L', 1, '2026-09-25 11:20:23', '2026-09-25 11:20:23'),
(8, 7, 'BZ30L', 'BZ30L', 1, '2026-09-25 11:22:30', '2026-09-25 11:22:30'),
(9, 7, 'BZ40L', 'BZ40L', 1, '2026-09-25 11:23:07', '2026-09-25 11:23:07'),
(10, 12, 'SONY HPJ', 'HPJ', 1, '2026-09-26 11:44:38', '2026-09-26 11:44:38'),
(11, 12, 'SONY BPJ', 'BPJ', 1, '2026-09-26 11:45:52', '2026-09-26 11:45:52'),
(12, 12, 'PROJECTORS', 'PROJECTORS', 1, '2026-10-02 10:47:36', '2026-10-02 10:47:36'),
(13, 13, 'PROJECTORS', 'VPRO', 1, '2026-10-02 10:49:22', '2026-10-02 10:49:22');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payables`
--

CREATE TABLE `supplier_payables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('invoice','payment','adjustment','debit_note','credit_note') NOT NULL DEFAULT 'invoice',
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_payables`
--

INSERT INTO `supplier_payables` (`id`, `supplier_id`, `type`, `reference_type`, `reference_id`, `debit`, `credit`, `balance`, `notes`, `created_at`, `updated_at`) VALUES
(1, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 1, 8260000.00, 0.00, 8260000.00, 'Purchase invoice PI-20260910-0001', '2026-09-10 09:49:13', '2026-09-10 09:49:13'),
(2, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 2, 4000000.00, 0.00, 12260000.00, 'Purchase invoice PI-20260911-0001', '2026-09-11 07:40:05', '2026-09-11 07:40:05'),
(3, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 3, 94630.00, 0.00, 12354630.00, 'Purchase invoice PI-20260919-0001', '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(4, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 4, 93600.00, 0.00, 12448230.00, 'Purchase invoice PI-20260921-0001', '2026-09-21 05:33:11', '2026-09-21 05:33:11'),
(5, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 5, 93600.00, 0.00, 12541830.00, 'Purchase invoice PI-20260921-0002', '2026-09-21 05:33:50', '2026-09-21 05:33:50'),
(6, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 6, 29120.00, 0.00, 12570950.00, 'Purchase invoice PI-20260921-0003', '2026-09-21 07:50:46', '2026-09-21 07:50:46'),
(7, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 7, 80000.00, 0.00, 12650950.00, 'Purchase invoice PI-20260921-0004', '2026-09-21 11:23:37', '2026-09-21 11:23:37'),
(8, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 8, 88500.00, 0.00, 12739450.00, 'Purchase invoice PI-20260925-0001', '2026-09-25 06:45:28', '2026-09-25 06:45:28'),
(9, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 9, 1030.00, 0.00, 12740480.00, 'Purchase invoice PI-20260925-0002', '2026-09-25 06:46:24', '2026-09-25 06:46:24'),
(10, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 10, 119.00, 0.00, 12740599.00, 'Purchase invoice PI-20260925-0003', '2026-09-25 07:36:54', '2026-09-25 07:36:54'),
(11, 10, 'invoice', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 11, 36580.00, 0.00, 12777179.00, 'Purchase invoice PI-20260928-0001', '2026-09-28 09:11:23', '2026-09-28 09:11:23');

-- --------------------------------------------------------

--
-- Table structure for table `tally_sync_mappings`
--

CREATE TABLE `tally_sync_mappings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `tally_type` varchar(60) NOT NULL,
  `tally_guid` varchar(255) DEFAULT NULL,
  `tally_name` varchar(255) DEFAULT NULL,
  `sync_status` varchar(30) NOT NULL DEFAULT 'synced',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tally_sync_mappings`
--

INSERT INTO `tally_sync_mappings` (`id`, `entity_type`, `entity_id`, `tally_type`, `tally_guid`, `tally_name`, `sync_status`, `last_synced_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Domains\\Master\\Models\\Uom', 2, 'uom', NULL, 'Piece', 'synced', '2026-09-29 09:53:39', '2026-09-29 09:53:39', '2026-09-29 09:53:39'),
(2, 'App\\Domains\\Master\\Models\\Product', 19, 'stock_item', NULL, 'LED Bulb 12W', 'synced', '2026-09-29 10:17:48', '2026-09-29 09:53:39', '2026-09-29 10:17:48');

-- --------------------------------------------------------

--
-- Table structure for table `tally_sync_queues`
--

CREATE TABLE `tally_sync_queues` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_type` varchar(60) NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `payload` longtext DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `last_response` longtext DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tally_sync_queues`
--

INSERT INTO `tally_sync_queues` (`id`, `document_type`, `document_id`, `payload`, `status`, `attempts`, `last_error`, `last_response`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, 'invoice', 15, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: INVOICE -->\n     <VOUCHER VCHTYPE=\"Sales\" ACTION=\"Create\">\n <DATE>20260917</DATE>\n <VOUCHERNUMBER>INV-20260917-0001</VOUCHERNUMBER>\n <PARTYLEDGERNAME>Prime Distributors</PARTYLEDGERNAME>\n <AMOUNT>88851.00</AMOUNT>\n <ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>Test Product</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>150.00</RATE><AMOUNT>150.00</AMOUNT></ALLINVENTORYENTRIES.LIST><ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>apple iphone 18</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>80000.00</RATE><AMOUNT>80000.00</AMOUNT></ALLINVENTORYENTRIES.LIST>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 320, NULL, NULL, '2026-09-21 10:14:36', '2026-09-17 06:38:05', '2026-09-21 10:14:36'),
(2, 'invoice', 16, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: INVOICE -->\n     <VOUCHER VCHTYPE=\"Sales\" ACTION=\"Create\">\n <DATE>20260917</DATE>\n <VOUCHERNUMBER>INV-20260917-0002</VOUCHERNUMBER>\n <PARTYLEDGERNAME>City Stores Pvt Ltd</PARTYLEDGERNAME>\n <AMOUNT>89816.19</AMOUNT>\n <ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>apple iphone 18</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>80000.00</RATE><AMOUNT>80000.00</AMOUNT></ALLINVENTORYENTRIES.LIST><ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>Sunflower Cooking Oil 1L</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>128.00</RATE><AMOUNT>128.00</AMOUNT></ALLINVENTORYENTRIES.LIST>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 320, NULL, NULL, '2026-09-21 10:14:36', '2026-09-17 06:41:06', '2026-09-21 10:14:36'),
(3, 'invoice', 17, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: INVOICE -->\n     <VOUCHER VCHTYPE=\"Sales\" ACTION=\"Create\">\n <DATE>20260919</DATE>\n <VOUCHERNUMBER>INV-20260919-0001</VOUCHERNUMBER>\n <PARTYLEDGERNAME>Patil Wholeale Mart</PARTYLEDGERNAME>\n <AMOUNT>46.20</AMOUNT>\n <ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>Chips</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>22.00</RATE><AMOUNT>22.00</AMOUNT></ALLINVENTORYENTRIES.LIST><ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>Chips</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>22.00</RATE><AMOUNT>22.00</AMOUNT></ALLINVENTORYENTRIES.LIST>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 320, NULL, NULL, '2026-09-21 10:14:36', '2026-09-19 06:45:26', '2026-09-21 10:14:36'),
(4, 'purchase_invoice', 3, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n <DATE>20260919</DATE>\n <VOUCHERNUMBER>PI-20260919-0001</VOUCHERNUMBER>\n <PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n <AMOUNT>94630.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 320, NULL, NULL, '2026-09-21 10:14:36', '2026-09-19 06:53:13', '2026-09-21 10:14:36'),
(5, 'purchase_invoice', 4, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n <DATE>20260921</DATE>\n <VOUCHERNUMBER>PI-20260921-0001</VOUCHERNUMBER>\n <PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n <AMOUNT>93600.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 9, NULL, NULL, '2026-09-21 10:14:37', '2026-09-21 05:33:11', '2026-09-21 10:14:37'),
(6, 'purchase_invoice', 5, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n <DATE>20260921</DATE>\n <VOUCHERNUMBER>PI-20260921-0002</VOUCHERNUMBER>\n <PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n <AMOUNT>93600.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 9, NULL, NULL, '2026-09-21 10:14:37', '2026-09-21 05:33:50', '2026-09-21 10:14:37'),
(7, 'payment', 7, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PAYMENT -->\n     <VOUCHER VCHTYPE=\"Receipt\" ACTION=\"Create\">\n <DATE>20260921</DATE>\n <VOUCHERNUMBER>PAY-20260921-0001</VOUCHERNUMBER>\n <PARTYLEDGERNAME>Patil Wholeale Mart</PARTYLEDGERNAME>\n <AMOUNT>46.20</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 9, NULL, NULL, '2026-09-21 10:14:37', '2026-09-21 05:47:30', '2026-09-21 10:14:37'),
(8, 'invoice', 17, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: INVOICE -->\n     <VOUCHER VCHTYPE=\"Sales\" ACTION=\"Create\">\n <DATE>20260919</DATE>\n <VOUCHERNUMBER>INV-20260919-0001</VOUCHERNUMBER>\n <PARTYLEDGERNAME>Patil Wholeale Mart</PARTYLEDGERNAME>\n <AMOUNT>46.20</AMOUNT>\n <ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>Chips</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>22.00</RATE><AMOUNT>22.00</AMOUNT></ALLINVENTORYENTRIES.LIST><ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>Chips</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>22.00</RATE><AMOUNT>22.00</AMOUNT></ALLINVENTORYENTRIES.LIST>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 9, NULL, NULL, '2026-09-21 10:14:37', '2026-09-21 05:47:30', '2026-09-21 10:14:37'),
(9, 'purchase_invoice', 6, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n <DATE>20260921</DATE>\n <VOUCHERNUMBER>PI-20260921-0003</VOUCHERNUMBER>\n <PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n <AMOUNT>29120.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 9, NULL, NULL, '2026-09-21 10:14:37', '2026-09-21 07:50:46', '2026-09-21 10:14:37'),
(10, 'purchase_invoice', 7, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n <DATE>20260921</DATE>\n <VOUCHERNUMBER>PI-20260921-0004</VOUCHERNUMBER>\n <PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n <AMOUNT>80000.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'failed', 323, 'Tally HTTP 200', '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>1</EXCEPTIONS>\r\n</RESPONSE>', NULL, '2026-09-21 11:23:37', '2026-09-28 05:59:49'),
(11, 'purchase_invoice', 8, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n<DATE>20260925</DATE>\n<VOUCHERNUMBER>PI-20260925-0001</VOUCHERNUMBER>\n<PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n<AMOUNT>88500.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'failed', 1, 'Tally HTTP 200', '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>1</EXCEPTIONS>\r\n</RESPONSE>', NULL, '2026-09-25 06:45:28', '2026-09-28 05:59:50'),
(12, 'purchase_invoice', 9, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n<DATE>20260925</DATE>\n<VOUCHERNUMBER>PI-20260925-0002</VOUCHERNUMBER>\n<PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n<AMOUNT>1030.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'failed', 1, 'Tally HTTP 200', '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>1</EXCEPTIONS>\r\n</RESPONSE>', NULL, '2026-09-25 06:46:24', '2026-09-28 05:59:50'),
(13, 'purchase_invoice', 10, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n<DATE>20260925</DATE>\n<VOUCHERNUMBER>PI-20260925-0003</VOUCHERNUMBER>\n<PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n<AMOUNT>119.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'failed', 1, 'Tally HTTP 200', '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>1</EXCEPTIONS>\r\n</RESPONSE>', NULL, '2026-09-25 07:36:54', '2026-09-28 05:59:50'),
(14, 'purchase_order', 8, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_ORDER -->\n     <VOUCHER VCHTYPE=\"Purchase Order\" ACTION=\"Create\">\n<DATE>20260925</DATE>\n<VOUCHERNUMBER></VOUCHERNUMBER>\n<VOUCHERTYPENAME>Purchase Order</VOUCHERTYPENAME>\n<PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW>\n<REFERENCE>PO-20260925-0001</REFERENCE>\n<PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n<ALLINVENTORYENTRIES.LIST>\n<STOCKITEMNAME>apple iphone 18</STOCKITEMNAME>\n<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>\n<ACTUALQTY>1 Box</ACTUALQTY>\n<BILLEDQTY>1 Box</BILLEDQTY>\n<RATE>80000</RATE>\n<AMOUNT>-80000.00</AMOUNT>\n<BATCHALLOCATIONS.LIST>\n<GODOWNNAME>midc pune warehouse</GODOWNNAME>\n<BATCHNAME>Primary Batch</BATCHNAME>\n<ORDERNO>PO-20260925-0001</ORDERNO>\n<ORDERDUEDATE>20260925</ORDERDUEDATE>\n<AMOUNT>-80000.00</AMOUNT>\n<ACTUALQTY>1 Box</ACTUALQTY>\n<BILLEDQTY>1 Box</BILLEDQTY>\n</BATCHALLOCATIONS.LIST>\n</ALLINVENTORYENTRIES.LIST>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'failed', 2, 'Tally HTTP 200', '<RESPONSE>\r\n <LINEERROR>Godown &apos;midc pune warehouse&apos; does not exist!</LINEERROR>\r\n <CREATED>0</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>1</EXCEPTIONS>\r\n</RESPONSE>', NULL, '2026-09-25 07:42:57', '2026-09-28 06:00:51'),
(15, 'purchase_invoice', 11, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PURCHASE_INVOICE -->\n     <VOUCHER VCHTYPE=\"Purchase\" ACTION=\"Create\">\n <DATE>20260928</DATE>\n <VOUCHERNUMBER>PI-20260928-0001</VOUCHERNUMBER>\n <VOUCHERTYPENAME>Purchase</VOUCHERTYPENAME>\n <PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW>\n <PARTYLEDGERNAME>dongas</PARTYLEDGERNAME>\n <AMOUNT>36580.00</AMOUNT>\n \n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'failed', 1, 'Tally HTTP 200', '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>1</EXCEPTIONS>\r\n</RESPONSE>', NULL, '2026-09-28 09:11:23', '2026-09-28 09:56:51'),
(16, 'payment', 8, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: PAYMENT -->\n     <VOUCHER VCHTYPE=\"Receipt\" ACTION=\"Create\">\n <DATE>20260930</DATE>\n <VOUCHERNUMBER>PAY-20260928-0001</VOUCHERNUMBER>\n <PARTYLEDGERNAME>City Stores Pvt Ltd</PARTYLEDGERNAME>\n <AMOUNT>50000.00</AMOUNT>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'failed', 1, 'Tally HTTP 200', '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>1</EXCEPTIONS>\r\n</RESPONSE>', NULL, '2026-09-28 09:30:47', '2026-09-28 09:56:51'),
(17, 'invoice', 16, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>Vouchers</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS document type: INVOICE -->\n     <VOUCHER VCHTYPE=\"Sales\" ACTION=\"Create\">\n <DATE>20260917</DATE>\n <VOUCHERNUMBER>INV-20260917-0002</VOUCHERNUMBER>\n <PARTYLEDGERNAME>City Stores Pvt Ltd</PARTYLEDGERNAME>\n <AMOUNT>89816.19</AMOUNT>\n <ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>apple iphone 18</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>80000.00</RATE><AMOUNT>80000.00</AMOUNT></ALLINVENTORYENTRIES.LIST><ALLINVENTORYENTRIES.LIST><STOCKITEMNAME>Sunflower Cooking Oil 1L</STOCKITEMNAME><ACTUALQTY>1.0000</ACTUALQTY><RATE>128.00</RATE><AMOUNT>128.00</AMOUNT></ALLINVENTORYENTRIES.LIST>\n</VOUCHER>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'failed', 1, 'Tally HTTP 200', '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>1</EXCEPTIONS>\r\n</RESPONSE>', NULL, '2026-09-28 09:30:47', '2026-09-28 09:56:51'),
(18, 'uom', 2, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: UOM -->\n     <UNIT NAME=\"Piece\" ACTION=\"Create\">\n <NAME>Piece</NAME>\n <ISSIMPLEUNIT>Yes</ISSIMPLEUNIT>\n</UNIT>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 1, NULL, '<RESPONSE>\r\n <CREATED>1</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>0</EXCEPTIONS>\r\n</RESPONSE>', '2026-09-29 09:53:39', '2026-09-29 09:50:19', '2026-09-29 09:53:39'),
(19, 'product', 19, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: PRODUCT -->\n     <STOCKITEM NAME=\"LED Bulb 12W\" ACTION=\"Create\">\n <NAME>LED Bulb 12W</NAME>\n <BASEUNITS>Piece</BASEUNITS>\n <HSNDETAILS.LIST>\n  <HSNCODE>85395200</HSNCODE>\n  <GSTRATE>18.00</GSTRATE>\n </HSNDETAILS.LIST>\n</STOCKITEM>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 1, NULL, '<RESPONSE>\r\n <CREATED>1</CREATED>\r\n <ALTERED>0</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>0</EXCEPTIONS>\r\n</RESPONSE>', '2026-09-29 09:53:39', '2026-09-29 09:50:19', '2026-09-29 09:53:39'),
(20, 'product', 19, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: PRODUCT -->\n     <STOCKITEM NAME=\"LED Bulb 12W\" ACTION=\"Alter\">\n <NAME>LED Bulb 12W</NAME>\n <BASEUNITS>Piece</BASEUNITS>\n <HSNDETAILS.LIST>\n  <HSNCODE>85395200</HSNCODE>\n  <GSTRATE>18.00</GSTRATE>\n </HSNDETAILS.LIST>\n</STOCKITEM>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 1, NULL, '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>1</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>0</EXCEPTIONS>\r\n</RESPONSE>', '2026-09-29 09:58:36', '2026-09-29 09:58:31', '2026-09-29 09:58:36'),
(21, 'product', 19, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: PRODUCT -->\n     <STOCKITEM NAME=\"LED Bulb 12W\" ACTION=\"Alter\">\n <NAME>LED Bulb 12W</NAME>\n <BASEUNITS>Piece</BASEUNITS>\n <HSNDETAILS.LIST>\n  <HSNCODE>85395200</HSNCODE>\n  <GSTRATE>18.00</GSTRATE>\n </HSNDETAILS.LIST>\n</STOCKITEM>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 1, NULL, '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>1</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>0</EXCEPTIONS>\r\n</RESPONSE>', '2026-09-29 10:00:59', '2026-09-29 10:00:24', '2026-09-29 10:00:59'),
(22, 'product', 19, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: PRODUCT -->\n     <STOCKITEM NAME=\"LED Bulb 12W\" ACTION=\"Alter\">\n <NAME>LED Bulb 12W</NAME>\n <BASEUNITS>Piece</BASEUNITS>\n <HSNDETAILS.LIST>\n  <HSNCODE>85395200</HSNCODE>\n  <GSTRATE>18.00</GSTRATE>\n </HSNDETAILS.LIST>\n</STOCKITEM>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'sent', 1, NULL, '<RESPONSE>\r\n <CREATED>0</CREATED>\r\n <ALTERED>1</ALTERED>\r\n <DELETED>0</DELETED>\r\n <LASTVCHID>0</LASTVCHID>\r\n <LASTMID>0</LASTMID>\r\n <COMBINED>0</COMBINED>\r\n <IGNORED>0</IGNORED>\r\n <ERRORS>0</ERRORS>\r\n <CANCELLED>0</CANCELLED>\r\n <EXCEPTIONS>0</EXCEPTIONS>\r\n</RESPONSE>', '2026-09-29 10:17:48', '2026-09-29 10:17:39', '2026-09-29 10:17:48'),
(23, 'uom', 3, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: UOM -->\n     <UNIT NAME=\"Case\" ACTION=\"Create\">\n <NAME>Case</NAME>\n <ISSIMPLEUNIT>Yes</ISSIMPLEUNIT>\n</UNIT>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'pending', 0, NULL, NULL, NULL, '2026-09-29 11:42:12', '2026-09-29 11:42:12'),
(24, 'product', 20, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: PRODUCT -->\n     <STOCKITEM NAME=\"Tubelight\" ACTION=\"Create\">\n <NAME>Tubelight</NAME>\n <BASEUNITS>Case</BASEUNITS>\n <HSNDETAILS.LIST>\n  <HSNCODE>SJDGDHL1</HSNCODE>\n  <GSTRATE>18.00</GSTRATE>\n </HSNDETAILS.LIST>\n</STOCKITEM>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'pending', 0, NULL, NULL, NULL, '2026-09-29 11:42:12', '2026-09-29 11:42:12'),
(25, 'product', 22, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: PRODUCT -->\n     <STOCKITEM NAME=\"Tubelight\" ACTION=\"Create\">\n <NAME>Tubelight</NAME>\n <BASEUNITS>Case</BASEUNITS>\n <HSNDETAILS.LIST>\n  <HSNCODE>121212</HSNCODE>\n  <GSTRATE>18.00</GSTRATE>\n </HSNDETAILS.LIST>\n</STOCKITEM>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'pending', 0, NULL, NULL, NULL, '2026-09-29 11:42:41', '2026-09-29 11:42:41'),
(29, 'product', 23, '<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<ENVELOPE>\n <HEADER>\n  <TALLYREQUEST>Import Data</TALLYREQUEST>\n </HEADER>\n <BODY>\n  <IMPORTDATA>\n   <REQUESTDESC>\n    <REPORTNAME>All Masters</REPORTNAME>\n    <STATICVARIABLES>\n     <SVCURRENTCOMPANY>AVIT DIGITAL</SVCURRENTCOMPANY>\n    </STATICVARIABLES>\n   </REQUESTDESC>\n   <REQUESTDATA>\n    <TALLYMESSAGE xmlns:UDF=\"TallyUDF\">\n     <!-- DMS master type: PRODUCT -->\n     <STOCKITEM NAME=\"VIEWSONIC PROJECTOR PA503S-3\" ACTION=\"Create\">\n <NAME>VIEWSONIC PROJECTOR PA503S-3</NAME>\n <BASEUNITS>Piece</BASEUNITS>\n <HSNDETAILS.LIST>\n  <HSNCODE>85286200</HSNCODE>\n  <GSTRATE>18.00</GSTRATE>\n </HSNDETAILS.LIST>\n</STOCKITEM>\n    </TALLYMESSAGE>\n   </REQUESTDATA>\n  </IMPORTDATA>\n </BODY>\n</ENVELOPE>', 'pending', 0, NULL, NULL, NULL, '2026-10-02 10:51:26', '2026-10-02 10:51:26');

-- --------------------------------------------------------

--
-- Table structure for table `target_achievements`
--

CREATE TABLE `target_achievements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `party_target_id` bigint(20) UNSIGNED NOT NULL,
  `target_period_id` bigint(20) UNSIGNED NOT NULL,
  `achieved_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achievement_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `target_periods`
--

CREATE TABLE `target_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `period_type` enum('monthly','quarterly','annual') NOT NULL DEFAULT 'monthly',
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_rates`
--

CREATE TABLE `tax_rates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tax_rates`
--

INSERT INTO `tax_rates` (`id`, `name`, `rate`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'GST 5%', 5.00, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'GST 12%', 12.00, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'GST 18%', 18.00, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `uoms`
--

CREATE TABLE `uoms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `uoms`
--

INSERT INTO `uoms` (`id`, `name`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Box', 'BOX', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'Piece', 'PCS', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'Case', 'CASE', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 'Kg', 'KG', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 'Litre', 'LTR', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `company_id`, `branch_id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Owner User', 'owner@dms.test', '2026-09-10 07:49:17', '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', 'OQMHjrA6hloUsErmvbdGlr61D4a12UP45HcPYOuOHMgt34vITbKyIOUKCvyc', '2026-08-29 04:40:00', '2026-09-10 07:49:17'),
(2, 1, 1, 'Super Admin', 'superadmin@dms.test', '2026-09-10 07:49:17', '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', 'pjOUMo1FBIQ6Eq8qeabtNLWICu1EOZFO2oQFNAIDyWJv0Ga7MlY0yKuQnIAv', '2026-08-29 04:40:00', '2026-09-10 07:49:17'),
(3, 1, 1, 'Sales Manager', 'salesmanager@dms.test', '2026-09-10 07:49:17', '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', 'JUYhUizy7qLEgp6LjnOol2p4L3ThjpB1QHE5eWEXC7rcyCvwKxOH1wJIkb43', '2026-08-29 04:40:00', '2026-09-10 07:49:17'),
(4, 1, 1, 'Sales Person', 'salesperson@dms.test', '2026-09-10 07:49:18', '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', NULL, '2026-08-29 04:40:00', '2026-09-10 07:49:18'),
(5, 1, 1, 'Warehouse Manager', 'warehouse@dms.test', '2026-09-10 07:49:18', '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', NULL, '2026-08-29 04:40:01', '2026-09-10 07:49:18'),
(6, 1, 1, 'Finance Manager', 'finance@dms.test', '2026-09-10 07:49:18', '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', NULL, '2026-08-29 04:40:01', '2026-09-10 07:49:18'),
(7, 1, 1, 'Driver One', 'driver@dms.test', '2026-09-10 07:49:18', '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', NULL, '2026-08-29 04:40:01', '2026-09-10 07:49:18'),
(8, 1, 1, 'Delivery Person', 'delivery@dms.test', '2026-09-10 07:49:19', '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', NULL, '2026-08-29 04:40:01', '2026-09-10 07:49:19'),
(9, NULL, NULL, 'Client Admin', 'client@extraaaz.com', NULL, '$2y$12$auwHwckPoPZ9zx//vhQPtOyFihkcQKiBgtBtSd/.lWRNWCY4I8TCW', 'MJIysdQO2Isrbx4eM28oc90uyLEjVG5eq0pXwZ3NVh4ThfxaURCcy2YRf2F8', '2026-09-10 07:55:38', '2026-09-10 07:56:18');

-- --------------------------------------------------------

--
-- Table structure for table `user_branch_access`
--

CREATE TABLE `user_branch_access` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_branch_access`
--

INSERT INTO `user_branch_access` (`id`, `user_id`, `branch_id`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(2, 2, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(3, 3, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(4, 4, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(5, 5, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(6, 6, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(7, 7, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(8, 8, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `registration_no` varchar(20) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `capacity` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `name`, `registration_no`, `type`, `capacity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Tata Ace', 'KA01AB1234', 'Mini Truck', 750.00, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_price_histories`
--

CREATE TABLE `vendor_price_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `effective_from` date NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendor_price_histories`
--

INSERT INTO `vendor_price_histories` (`id`, `supplier_id`, `product_id`, `uom_id`, `unit_cost`, `effective_from`, `reference_type`, `reference_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 10, 9, 1, 70000.0000, '2026-09-10', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 1, 9, '2026-09-10 09:49:13', '2026-09-10 09:49:13'),
(2, 10, 9, 2, 80000.0000, '2026-09-11', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 2, 9, '2026-09-11 07:40:05', '2026-09-11 07:40:05'),
(3, 10, 9, 1, 75000.0000, '2026-09-19', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 3, 9, '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(4, 10, 7, 1, 5000.0000, '2026-09-19', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 3, 9, '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(5, 10, 5, 1, 1000.0000, '2026-09-19', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 3, 9, '2026-09-19 06:53:13', '2026-09-19 06:53:13'),
(6, 10, 9, 1, 75000.0000, '2026-09-21', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 4, 9, '2026-09-21 05:33:11', '2026-09-21 05:33:11'),
(7, 10, 7, 1, 5000.0000, '2026-09-21', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 4, 9, '2026-09-21 05:33:11', '2026-09-21 05:33:11'),
(8, 10, 9, 1, 75000.0000, '2026-09-21', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 5, 9, '2026-09-21 05:33:50', '2026-09-21 05:33:50'),
(9, 10, 7, 1, 5000.0000, '2026-09-21', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 5, 9, '2026-09-21 05:33:50', '2026-09-21 05:33:50'),
(10, 10, 5, 2, 2000.0000, '2026-09-21', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 6, 9, '2026-09-21 07:50:46', '2026-09-21 07:50:46'),
(11, 10, 9, 2, 80000.0000, '2026-09-21', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 7, 9, '2026-09-21 11:23:37', '2026-09-21 11:23:37'),
(12, 10, 9, 1, 75000.0000, '2026-09-25', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 8, 9, '2026-09-25 06:45:28', '2026-09-25 06:45:28'),
(13, 10, 5, 1, 1000.0000, '2026-09-25', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 9, 9, '2026-09-25 06:46:24', '2026-09-25 06:46:24'),
(14, 10, 12, 2, 100.0000, '2026-09-25', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 10, 9, '2026-09-25 07:36:54', '2026-09-25 07:36:54'),
(15, 10, 17, 2, 31000.0000, '2026-09-28', 'App\\Domains\\Purchasing\\Models\\PurchaseInvoice', 11, 9, '2026-09-28 09:11:23', '2026-09-28 09:11:23');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `address` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `company_id`, `branch_id`, `name`, `code`, `address`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Main Warehouse', 'WH1', NULL, 1, 1, '2026-09-08 06:59:11', '2026-09-08 06:59:11'),
(2, 2, 2, 'midc pune warehouse', '001', NULL, 1, 1, '2026-09-10 09:20:49', '2026-09-10 09:20:49'),
(3, 4, 1, 'MAIN LOCATION', 'ML', NULL, 1, 1, '2026-09-25 11:02:02', '2026-09-25 11:02:02'),
(4, 4, 1, 'RESIDENCE', 'RS', NULL, 0, 1, '2026-09-25 11:02:26', '2026-09-25 11:02:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_user_id_foreign` (`user_id`),
  ADD KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`);

--
-- Indexes for table `approval_logs`
--
ALTER TABLE `approval_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approval_logs_user_id_foreign` (`user_id`),
  ADD KEY `approval_logs_approvable_type_approvable_id_index` (`approvable_type`,`approvable_id`);

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `areas_code_unique` (`code`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendances_employee_id_attendance_date_unique` (`employee_id`,`attendance_date`),
  ADD KEY `attendances_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branches_company_id_code_unique` (`company_id`,`code`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brands_code_unique` (`code`),
  ADD UNIQUE KEY `brands_name_unique` (`name`),
  ADD KEY `brands_company_id_foreign` (`company_id`);

--
-- Indexes for table `business_groups`
--
ALTER TABLE `business_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `business_groups_code_unique` (`code`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_code_unique` (`code`),
  ADD KEY `categories_brand_id_foreign` (`brand_id`);

--
-- Indexes for table `cheques`
--
ALTER TABLE `cheques`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cheques_payment_id_foreign` (`payment_id`),
  ADD KEY `cheques_recorded_by_foreign` (`recorded_by`),
  ADD KEY `cheques_customer_id_status_index` (`customer_id`,`status`),
  ADD KEY `cheques_cheque_no_bank_name_index` (`cheque_no`,`bank_name`);

--
-- Indexes for table `cheque_bounces`
--
ALTER TABLE `cheque_bounces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cheque_bounces_cheque_id_foreign` (`cheque_id`),
  ADD KEY `cheque_bounces_customer_id_foreign` (`customer_id`),
  ADD KEY `cheque_bounces_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `communication_logs`
--
ALTER TABLE `communication_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `communication_logs_invoice_id_foreign` (`invoice_id`),
  ADD KEY `communication_logs_customer_id_foreign` (`customer_id`),
  ADD KEY `communication_logs_sent_by_foreign` (`sent_by`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `companies_code_unique` (`code`),
  ADD KEY `companies_business_group_id_foreign` (`business_group_id`);

--
-- Indexes for table `company_group_links`
--
ALTER TABLE `company_group_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_group_links_business_group_id_company_id_unique` (`business_group_id`,`company_id`),
  ADD KEY `company_group_links_company_id_foreign` (`company_id`);

--
-- Indexes for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `credit_notes_credit_note_no_unique` (`credit_note_no`),
  ADD KEY `credit_notes_customer_id_foreign` (`customer_id`),
  ADD KEY `credit_notes_invoice_id_foreign` (`invoice_id`),
  ADD KEY `credit_notes_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `credit_note_items`
--
ALTER TABLE `credit_note_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `credit_note_items_credit_note_id_foreign` (`credit_note_id`),
  ADD KEY `credit_note_items_product_id_foreign` (`product_id`),
  ADD KEY `credit_note_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_code_unique` (`code`),
  ADD KEY `customers_customer_type_id_foreign` (`customer_type_id`),
  ADD KEY `customers_area_id_foreign` (`area_id`),
  ADD KEY `customers_route_id_foreign` (`route_id`),
  ADD KEY `customers_salesperson_id_foreign` (`salesperson_id`),
  ADD KEY `customers_company_id_foreign` (`company_id`),
  ADD KEY `customers_branch_id_foreign` (`branch_id`),
  ADD KEY `customers_sales_manager_id_foreign` (`sales_manager_id`);

--
-- Indexes for table `customer_types`
--
ALTER TABLE `customer_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_types_name_unique` (`name`),
  ADD UNIQUE KEY `customer_types_code_unique` (`code`);

--
-- Indexes for table `deals`
--
ALTER TABLE `deals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `deals_reference_unique` (`reference`),
  ADD KEY `deals_invoice_id_foreign` (`invoice_id`),
  ADD KEY `deals_order_id_foreign` (`order_id`),
  ADD KEY `deals_customer_id_status_index` (`customer_id`,`status`);

--
-- Indexes for table `deal_expenses`
--
ALTER TABLE `deal_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deal_expenses_expense_type_id_foreign` (`expense_type_id`),
  ADD KEY `deal_expenses_deal_id_status_index` (`deal_id`,`status`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deliveries_load_sheet_id_foreign` (`load_sheet_id`),
  ADD KEY `deliveries_customer_id_foreign` (`customer_id`),
  ADD KEY `deliveries_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `delivery_items`
--
ALTER TABLE `delivery_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_items_delivery_id_foreign` (`delivery_id`),
  ADD KEY `delivery_items_product_id_foreign` (`product_id`),
  ADD KEY `delivery_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `delivery_persons`
--
ALTER TABLE `delivery_persons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `demo_stock_notices`
--
ALTER TABLE `demo_stock_notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `demo_stock_notices_product_id_foreign` (`product_id`),
  ADD KEY `demo_stock_notices_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `demo_stock_notices_customer_id_foreign` (`customer_id`),
  ADD KEY `demo_stock_notices_created_by_foreign` (`created_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `departments_company_id_foreign` (`company_id`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `designations_company_id_foreign` (`company_id`);

--
-- Indexes for table `document_sequences`
--
ALTER TABLE `document_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_sequences_prefix_date_unique` (`prefix`,`sequence_date`),
  ADD KEY `document_sequences_company_id_foreign` (`company_id`),
  ADD KEY `document_sequences_branch_id_foreign` (`branch_id`),
  ADD KEY `document_sequences_financial_year_id_foreign` (`financial_year_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employees_employee_code_unique` (`employee_code`),
  ADD KEY `employees_company_id_foreign` (`company_id`),
  ADD KEY `employees_branch_id_foreign` (`branch_id`),
  ADD KEY `employees_user_id_foreign` (`user_id`),
  ADD KEY `employees_department_id_foreign` (`department_id`),
  ADD KEY `employees_designation_id_foreign` (`designation_id`),
  ADD KEY `employees_manager_id_foreign` (`manager_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_documents_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employee_exits`
--
ALTER TABLE `employee_exits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_exits_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employee_incentives`
--
ALTER TABLE `employee_incentives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_incentives_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `employee_kpis`
--
ALTER TABLE `employee_kpis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_kpis_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_approvals_deal_expense_id_foreign` (`deal_expense_id`),
  ADD KEY `expense_approvals_approver_id_foreign` (`approver_id`);

--
-- Indexes for table `expense_claims`
--
ALTER TABLE `expense_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_claims_employee_id_foreign` (`employee_id`),
  ADD KEY `expense_claims_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `expense_types`
--
ALTER TABLE `expense_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_types_code_unique` (`code`);

--
-- Indexes for table `e_invoices`
--
ALTER TABLE `e_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `e_invoices_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `e_way_bills`
--
ALTER TABLE `e_way_bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `e_way_bills_qr_token_unique` (`qr_token`),
  ADD KEY `e_way_bills_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `financial_years`
--
ALTER TABLE `financial_years`
  ADD PRIMARY KEY (`id`),
  ADD KEY `financial_years_company_id_foreign` (`company_id`);

--
-- Indexes for table `freight_bills`
--
ALTER TABLE `freight_bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `freight_bills_freight_no_unique` (`freight_no`),
  ADD KEY `freight_bills_created_by_foreign` (`created_by`);

--
-- Indexes for table `freight_bill_allocations`
--
ALTER TABLE `freight_bill_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `freight_bill_allocations_freight_bill_id_foreign` (`freight_bill_id`),
  ADD KEY `freight_bill_allocations_allocatable_type_allocatable_id_index` (`allocatable_type`,`allocatable_id`);

--
-- Indexes for table `interest_documents`
--
ALTER TABLE `interest_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `interest_documents_document_no_unique` (`document_no`),
  ADD KEY `interest_documents_customer_id_foreign` (`customer_id`),
  ADD KEY `interest_documents_interest_ledger_id_foreign` (`interest_ledger_id`);

--
-- Indexes for table `interest_ledgers`
--
ALTER TABLE `interest_ledgers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interest_ledgers_interest_rule_id_foreign` (`interest_rule_id`),
  ADD KEY `interest_ledgers_customer_id_as_of_date_status_index` (`customer_id`,`as_of_date`,`status`),
  ADD KEY `interest_ledgers_invoice_id_as_of_date_index` (`invoice_id`,`as_of_date`);

--
-- Indexes for table `interest_rules`
--
ALTER TABLE `interest_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interest_rules_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `internal_delivery_challans`
--
ALTER TABLE `internal_delivery_challans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `internal_delivery_challans_challan_no_unique` (`challan_no`),
  ADD KEY `internal_delivery_challans_from_warehouse_id_foreign` (`from_warehouse_id`),
  ADD KEY `internal_delivery_challans_to_warehouse_id_foreign` (`to_warehouse_id`),
  ADD KEY `internal_delivery_challans_stock_transfer_id_foreign` (`stock_transfer_id`),
  ADD KEY `internal_delivery_challans_created_by_foreign` (`created_by`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_invoice_no_unique` (`invoice_no`),
  ADD UNIQUE KEY `invoices_order_id_unique` (`order_id`),
  ADD UNIQUE KEY `invoices_qr_token_unique` (`qr_token`),
  ADD KEY `invoices_customer_id_foreign` (`customer_id`),
  ADD KEY `invoices_order_id_foreign` (`order_id`),
  ADD KEY `invoices_salesperson_id_foreign` (`salesperson_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  ADD KEY `invoice_items_product_id_foreign` (`product_id`),
  ADD KEY `invoice_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landed_costs`
--
ALTER TABLE `landed_costs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `landed_costs_landed_no_unique` (`landed_no`),
  ADD KEY `landed_costs_purchase_invoice_id_foreign` (`purchase_invoice_id`),
  ADD KEY `landed_costs_freight_bill_id_foreign` (`freight_bill_id`),
  ADD KEY `landed_costs_created_by_foreign` (`created_by`);

--
-- Indexes for table `landed_cost_items`
--
ALTER TABLE `landed_cost_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `landed_cost_items_landed_cost_id_foreign` (`landed_cost_id`),
  ADD KEY `landed_cost_items_product_id_foreign` (`product_id`),
  ADD KEY `landed_cost_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leads_external_lead_id_unique` (`external_lead_id`),
  ADD KEY `leads_company_id_foreign` (`company_id`),
  ADD KEY `leads_branch_id_foreign` (`branch_id`),
  ADD KEY `leads_lead_source_id_foreign` (`lead_source_id`),
  ADD KEY `leads_lead_campaign_id_foreign` (`lead_campaign_id`),
  ADD KEY `leads_meta_lead_form_id_foreign` (`meta_lead_form_id`),
  ADD KEY `leads_assigned_to_foreign` (`assigned_to`),
  ADD KEY `leads_converted_customer_id_foreign` (`converted_customer_id`),
  ADD KEY `leads_mobile_index` (`mobile`),
  ADD KEY `leads_email_index` (`email`);

--
-- Indexes for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_activities_lead_id_foreign` (`lead_id`),
  ADD KEY `lead_activities_user_id_foreign` (`user_id`);

--
-- Indexes for table `lead_assignments`
--
ALTER TABLE `lead_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_assignments_lead_id_foreign` (`lead_id`),
  ADD KEY `lead_assignments_assigned_to_foreign` (`assigned_to`),
  ADD KEY `lead_assignments_assigned_by_foreign` (`assigned_by`);

--
-- Indexes for table `lead_campaigns`
--
ALTER TABLE `lead_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_campaigns_external_campaign_id_index` (`external_campaign_id`);

--
-- Indexes for table `lead_conversions`
--
ALTER TABLE `lead_conversions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_conversions_lead_id_foreign` (`lead_id`),
  ADD KEY `lead_conversions_customer_id_foreign` (`customer_id`),
  ADD KEY `lead_conversions_converted_by_foreign` (`converted_by`);

--
-- Indexes for table `lead_followups`
--
ALTER TABLE `lead_followups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lead_followups_lead_id_foreign` (`lead_id`),
  ADD KEY `lead_followups_user_id_foreign` (`user_id`);

--
-- Indexes for table `lead_sources`
--
ALTER TABLE `lead_sources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lead_sources_code_unique` (`code`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_balances_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  ADD KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_requests_employee_id_foreign` (`employee_id`),
  ADD KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  ADD KEY `leave_requests_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_types_company_id_foreign` (`company_id`);

--
-- Indexes for table `load_sheets`
--
ALTER TABLE `load_sheets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `load_sheets_load_sheet_no_unique` (`load_sheet_no`),
  ADD KEY `load_sheets_route_id_foreign` (`route_id`),
  ADD KEY `load_sheets_vehicle_id_foreign` (`vehicle_id`),
  ADD KEY `load_sheets_driver_id_foreign` (`driver_id`),
  ADD KEY `load_sheets_delivery_person_id_foreign` (`delivery_person_id`),
  ADD KEY `load_sheets_created_by_foreign` (`created_by`);

--
-- Indexes for table `load_sheet_items`
--
ALTER TABLE `load_sheet_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `load_sheet_items_load_sheet_id_invoice_id_unique` (`load_sheet_id`,`invoice_id`),
  ADD KEY `load_sheet_items_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `meta_lead_forms`
--
ALTER TABLE `meta_lead_forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `meta_lead_forms_form_id_unique` (`form_id`),
  ADD KEY `meta_lead_forms_lead_campaign_id_foreign` (`lead_campaign_id`);

--
-- Indexes for table `meta_lead_logs`
--
ALTER TABLE `meta_lead_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `meta_lead_logs_external_lead_id_index` (`external_lead_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_no_unique` (`order_no`),
  ADD KEY `orders_customer_id_foreign` (`customer_id`),
  ADD KEY `orders_salesperson_id_foreign` (`salesperson_id`),
  ADD KEY `orders_approved_by_foreign` (`approved_by`),
  ADD KEY `orders_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `orders_quotation_id_foreign` (`quotation_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`),
  ADD KEY `order_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `outstanding_ledger`
--
ALTER TABLE `outstanding_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `outstanding_ledger_customer_id_foreign` (`customer_id`),
  ADD KEY `outstanding_ledger_reference_type_reference_id_index` (`reference_type`,`reference_id`);

--
-- Indexes for table `party_addresses`
--
ALTER TABLE `party_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_addresses_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `party_bank_accounts`
--
ALTER TABLE `party_bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_bank_accounts_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `party_contacts`
--
ALTER TABLE `party_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_contacts_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `party_credit_cheques`
--
ALTER TABLE `party_credit_cheques`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_credit_cheques_customer_id_foreign` (`customer_id`),
  ADD KEY `party_credit_cheques_cheque_number_index` (`cheque_number`);

--
-- Indexes for table `party_targets`
--
ALTER TABLE `party_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `party_targets_customer_id_foreign` (`customer_id`),
  ADD KEY `party_targets_salesperson_id_foreign` (`salesperson_id`),
  ADD KEY `party_targets_brand_id_foreign` (`brand_id`),
  ADD KEY `party_targets_target_period_id_customer_id_index` (`target_period_id`,`customer_id`),
  ADD KEY `party_targets_target_period_id_salesperson_id_index` (`target_period_id`,`salesperson_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_payment_no_unique` (`payment_no`),
  ADD UNIQUE KEY `payments_reference_no_unique` (`reference_no`),
  ADD KEY `payments_invoice_id_foreign` (`invoice_id`),
  ADD KEY `payments_customer_id_foreign` (`customer_id`),
  ADD KEY `payments_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_allocations_payment_id_invoice_id_unique` (`payment_id`,`invoice_id`),
  ADD KEY `payment_allocations_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `payment_links`
--
ALTER TABLE `payment_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_links_token_unique` (`token`),
  ADD UNIQUE KEY `payment_links_provider_reference_unique` (`provider_reference`),
  ADD KEY `payment_links_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `payroll_inputs`
--
ALTER TABLE `payroll_inputs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_inputs_employee_id_year_month_unique` (`employee_id`,`year`,`month`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `price_masters`
--
ALTER TABLE `price_masters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `price_master_unique` (`customer_type_id`,`product_id`,`uom_id`,`min_qty`),
  ADD KEY `price_masters_product_id_foreign` (`product_id`),
  ADD KEY `price_masters_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_sku_unique` (`sku`),
  ADD UNIQUE KEY `products_serial_no_unique` (`serial_no`),
  ADD KEY `products_base_uom_id_foreign` (`base_uom_id`),
  ADD KEY `products_company_id_foreign` (`company_id`),
  ADD KEY `products_brand_id_foreign` (`brand_id`),
  ADD KEY `products_category_id_foreign` (`category_id`),
  ADD KEY `products_sub_category_id_foreign` (`sub_category_id`),
  ADD KEY `products_color_variant_index` (`color_variant`);

--
-- Indexes for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_batches_lookup_unique` (`product_id`,`warehouse_id`,`batch_no`),
  ADD KEY `product_batches_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `product_batches_uom_id_foreign` (`uom_id`),
  ADD KEY `product_batches_source_type_source_id_index` (`source_type`,`source_id`);

--
-- Indexes for table `product_media`
--
ALTER TABLE `product_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_media_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_price_histories`
--
ALTER TABLE `product_price_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_price_histories_product_id_foreign` (`product_id`),
  ADD KEY `product_price_histories_uom_id_foreign` (`uom_id`),
  ADD KEY `product_price_histories_created_by_foreign` (`created_by`);

--
-- Indexes for table `product_serials`
--
ALTER TABLE `product_serials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_serials_serial_number_unique` (`serial_number`),
  ADD KEY `product_serials_product_id_foreign` (`product_id`),
  ADD KEY `product_serials_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `product_serials_source_type_source_id_index` (`source_type`,`source_id`),
  ADD KEY `product_serials_purchase_inward_item_id_foreign` (`purchase_inward_item_id`),
  ADD KEY `product_serials_reserved_for_type_reserved_for_id_index` (`reserved_for_type`,`reserved_for_id`);

--
-- Indexes for table `product_uoms`
--
ALTER TABLE `product_uoms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_uoms_product_id_uom_id_unique` (`product_id`,`uom_id`),
  ADD KEY `product_uoms_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchases_purchase_no_unique` (`purchase_no`),
  ADD KEY `purchases_created_by_foreign` (`created_by`),
  ADD KEY `purchases_supplier_party_id_foreign` (`supplier_party_id`),
  ADD KEY `purchases_warehouse_id_foreign` (`warehouse_id`);

--
-- Indexes for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_invoices_invoice_no_unique` (`invoice_no`),
  ADD UNIQUE KEY `purchase_invoices_qr_token_unique` (`qr_token`),
  ADD KEY `purchase_invoices_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_invoices_purchase_inward_id_foreign` (`purchase_inward_id`),
  ADD KEY `purchase_invoices_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_invoices_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `purchase_invoices_created_by_foreign` (`created_by`);

--
-- Indexes for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_invoice_items_purchase_invoice_id_foreign` (`purchase_invoice_id`),
  ADD KEY `purchase_invoice_items_product_id_foreign` (`product_id`),
  ADD KEY `purchase_invoice_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `purchase_inwards`
--
ALTER TABLE `purchase_inwards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_inwards_inward_no_unique` (`inward_no`),
  ADD KEY `purchase_inwards_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_inwards_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `purchase_inwards_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_inwards_created_by_foreign` (`created_by`);

--
-- Indexes for table `purchase_inward_items`
--
ALTER TABLE `purchase_inward_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_inward_items_purchase_inward_id_foreign` (`purchase_inward_id`),
  ADD KEY `purchase_inward_items_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  ADD KEY `purchase_inward_items_product_id_foreign` (`product_id`),
  ADD KEY `purchase_inward_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  ADD KEY `purchase_items_product_id_foreign` (`product_id`),
  ADD KEY `purchase_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_po_no_unique` (`po_no`),
  ADD KEY `purchase_orders_company_id_foreign` (`company_id`),
  ADD KEY `purchase_orders_branch_id_foreign` (`branch_id`),
  ADD KEY `purchase_orders_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_orders_created_by_foreign` (`created_by`),
  ADD KEY `purchase_orders_approved_by_foreign` (`approved_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_order_items_product_id_foreign` (`product_id`),
  ADD KEY `purchase_order_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotations_quotation_no_unique` (`quotation_no`),
  ADD KEY `quotations_customer_id_foreign` (`customer_id`),
  ADD KEY `quotations_salesperson_id_foreign` (`salesperson_id`),
  ADD KEY `quotations_converted_order_id_foreign` (`converted_order_id`);

--
-- Indexes for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_items_quotation_id_foreign` (`quotation_id`),
  ADD KEY `quotation_items_product_id_foreign` (`product_id`),
  ADD KEY `quotation_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `region_brand_policies`
--
ALTER TABLE `region_brand_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `region_brand_policies_area_id_brand_id_unique` (`area_id`,`brand_id`),
  ADD KEY `region_brand_policies_brand_id_foreign` (`brand_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `routes_code_unique` (`code`),
  ADD KEY `routes_area_id_foreign` (`area_id`);

--
-- Indexes for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salary_structures_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `schemes`
--
ALTER TABLE `schemes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `schemes_code_unique` (`code`),
  ADD KEY `schemes_brand_id_foreign` (`brand_id`);

--
-- Indexes for table `scheme_achievements`
--
ALTER TABLE `scheme_achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheme_achievements_customer_id_foreign` (`customer_id`),
  ADD KEY `scheme_achievements_invoice_id_foreign` (`invoice_id`),
  ADD KEY `scheme_achievements_scheme_id_customer_id_status_index` (`scheme_id`,`customer_id`,`status`);

--
-- Indexes for table `scheme_products`
--
ALTER TABLE `scheme_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scheme_products_scheme_id_product_id_unique` (`scheme_id`,`product_id`),
  ADD KEY `scheme_products_product_id_foreign` (`product_id`);

--
-- Indexes for table `scheme_settlements`
--
ALTER TABLE `scheme_settlements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheme_settlements_scheme_id_foreign` (`scheme_id`),
  ADD KEY `scheme_settlements_scheme_achievement_id_foreign` (`scheme_achievement_id`),
  ADD KEY `scheme_settlements_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `scheme_slabs`
--
ALTER TABLE `scheme_slabs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheme_slabs_scheme_id_foreign` (`scheme_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settlements`
--
ALTER TABLE `settlements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settlements_settlement_no_unique` (`settlement_no`),
  ADD KEY `settlements_load_sheet_id_foreign` (`load_sheet_id`),
  ADD KEY `settlements_settled_by_foreign` (`settled_by`);

--
-- Indexes for table `settlement_lines`
--
ALTER TABLE `settlement_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `settlement_lines_settlement_id_foreign` (`settlement_id`),
  ADD KEY `settlement_lines_customer_id_foreign` (`customer_id`),
  ADD KEY `settlement_lines_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_adjustments_adjustment_no_unique` (`adjustment_no`),
  ADD KEY `stock_adjustments_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `stock_adjustments_created_by_foreign` (`created_by`);

--
-- Indexes for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  ADD KEY `stock_adjustment_items_product_id_foreign` (`product_id`),
  ADD KEY `stock_adjustment_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `stock_cost_consumptions`
--
ALTER TABLE `stock_cost_consumptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_cost_consumptions_stock_cost_layer_id_foreign` (`stock_cost_layer_id`),
  ADD KEY `stock_cost_consumptions_stock_movement_id_foreign` (`stock_movement_id`),
  ADD KEY `stock_cost_consumptions_product_id_foreign` (`product_id`),
  ADD KEY `stock_cost_consumptions_reference_type_reference_id_index` (`reference_type`,`reference_id`);

--
-- Indexes for table `stock_cost_layers`
--
ALTER TABLE `stock_cost_layers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_cost_layers_company_id_foreign` (`company_id`),
  ADD KEY `stock_cost_layers_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `stock_cost_layers_uom_id_foreign` (`uom_id`),
  ADD KEY `stock_cost_layers_source_type_source_id_index` (`source_type`,`source_id`),
  ADD KEY `stock_cost_layers_lookup_idx` (`product_id`,`warehouse_id`,`received_on`);

--
-- Indexes for table `stock_levels`
--
ALTER TABLE `stock_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_levels_warehouse_product_uom_unique` (`warehouse_id`,`product_id`,`uom_id`),
  ADD KEY `stock_levels_uom_id_foreign` (`uom_id`),
  ADD KEY `stock_levels_product_id_index` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_product_id_foreign` (`product_id`),
  ADD KEY `stock_movements_uom_id_foreign` (`uom_id`),
  ADD KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `stock_movements_created_by_foreign` (`created_by`),
  ADD KEY `stock_movements_warehouse_id_foreign` (`warehouse_id`);

--
-- Indexes for table `stock_reservations`
--
ALTER TABLE `stock_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_reservations_product_id_foreign` (`product_id`),
  ADD KEY `stock_reservations_uom_id_foreign` (`uom_id`),
  ADD KEY `stock_reservations_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `stock_reservations_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `stock_reservations_created_by_foreign` (`created_by`),
  ADD KEY `stock_reservations_order_id_foreign` (`order_id`),
  ADD KEY `stock_reservations_order_item_id_foreign` (`order_item_id`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_transfers_transfer_no_unique` (`transfer_no`),
  ADD KEY `stock_transfers_from_warehouse_id_foreign` (`from_warehouse_id`),
  ADD KEY `stock_transfers_to_warehouse_id_foreign` (`to_warehouse_id`),
  ADD KEY `stock_transfers_created_by_foreign` (`created_by`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transfer_items_stock_transfer_id_foreign` (`stock_transfer_id`),
  ADD KEY `stock_transfer_items_product_id_foreign` (`product_id`),
  ADD KEY `stock_transfer_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `stock_valuation_settings`
--
ALTER TABLE `stock_valuation_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_valuation_company_fy_unique` (`company_id`,`financial_year_id`),
  ADD KEY `stock_valuation_settings_financial_year_id_foreign` (`financial_year_id`);

--
-- Indexes for table `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sub_categories_category_id_code_unique` (`category_id`,`code`);

--
-- Indexes for table `supplier_payables`
--
ALTER TABLE `supplier_payables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_payables_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `supplier_payables_supplier_id_id_index` (`supplier_id`,`id`);

--
-- Indexes for table `tally_sync_mappings`
--
ALTER TABLE `tally_sync_mappings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tally_mapping_dms_unique` (`entity_type`,`entity_id`,`tally_type`),
  ADD KEY `tally_sync_mappings_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  ADD KEY `tally_sync_mappings_tally_type_tally_guid_index` (`tally_type`,`tally_guid`);

--
-- Indexes for table `tally_sync_queues`
--
ALTER TABLE `tally_sync_queues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tally_sync_queues_document_type_document_id_index` (`document_type`,`document_id`),
  ADD KEY `tally_sync_queues_status_created_at_index` (`status`,`created_at`);

--
-- Indexes for table `target_achievements`
--
ALTER TABLE `target_achievements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `target_achievements_party_target_id_unique` (`party_target_id`),
  ADD KEY `target_achievements_target_period_id_foreign` (`target_period_id`);

--
-- Indexes for table `target_periods`
--
ALTER TABLE `target_periods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_periods_period_type_starts_on_ends_on_index` (`period_type`,`starts_on`,`ends_on`);

--
-- Indexes for table `tax_rates`
--
ALTER TABLE `tax_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `uoms`
--
ALTER TABLE `uoms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uoms_code_unique` (`code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_company_id_foreign` (`company_id`),
  ADD KEY `users_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `user_branch_access`
--
ALTER TABLE `user_branch_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_branch_access_user_id_branch_id_unique` (`user_id`,`branch_id`),
  ADD KEY `user_branch_access_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicles_registration_no_unique` (`registration_no`);

--
-- Indexes for table `vendor_price_histories`
--
ALTER TABLE `vendor_price_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_price_histories_supplier_id_foreign` (`supplier_id`),
  ADD KEY `vendor_price_histories_uom_id_foreign` (`uom_id`),
  ADD KEY `vendor_price_histories_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `vendor_price_histories_created_by_foreign` (`created_by`),
  ADD KEY `vendor_price_hist_lookup_idx` (`product_id`,`supplier_id`,`effective_from`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouses_branch_id_code_unique` (`branch_id`,`code`),
  ADD KEY `warehouses_company_id_foreign` (`company_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `approval_logs`
--
ALTER TABLE `approval_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `business_groups`
--
ALTER TABLE `business_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cheques`
--
ALTER TABLE `cheques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cheque_bounces`
--
ALTER TABLE `cheque_bounces`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `communication_logs`
--
ALTER TABLE `communication_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `company_group_links`
--
ALTER TABLE `company_group_links`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `credit_notes`
--
ALTER TABLE `credit_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `credit_note_items`
--
ALTER TABLE `credit_note_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `customer_types`
--
ALTER TABLE `customer_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deals`
--
ALTER TABLE `deals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deal_expenses`
--
ALTER TABLE `deal_expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `delivery_items`
--
ALTER TABLE `delivery_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `delivery_persons`
--
ALTER TABLE `delivery_persons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `demo_stock_notices`
--
ALTER TABLE `demo_stock_notices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `document_sequences`
--
ALTER TABLE `document_sequences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_exits`
--
ALTER TABLE `employee_exits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_incentives`
--
ALTER TABLE `employee_incentives`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_kpis`
--
ALTER TABLE `employee_kpis`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_claims`
--
ALTER TABLE `expense_claims`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_types`
--
ALTER TABLE `expense_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `e_invoices`
--
ALTER TABLE `e_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `e_way_bills`
--
ALTER TABLE `e_way_bills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_years`
--
ALTER TABLE `financial_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `freight_bills`
--
ALTER TABLE `freight_bills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `freight_bill_allocations`
--
ALTER TABLE `freight_bill_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `interest_documents`
--
ALTER TABLE `interest_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interest_ledgers`
--
ALTER TABLE `interest_ledgers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interest_rules`
--
ALTER TABLE `interest_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internal_delivery_challans`
--
ALTER TABLE `internal_delivery_challans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `landed_costs`
--
ALTER TABLE `landed_costs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `landed_cost_items`
--
ALTER TABLE `landed_cost_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_activities`
--
ALTER TABLE `lead_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_assignments`
--
ALTER TABLE `lead_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_campaigns`
--
ALTER TABLE `lead_campaigns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_conversions`
--
ALTER TABLE `lead_conversions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_followups`
--
ALTER TABLE `lead_followups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_sources`
--
ALTER TABLE `lead_sources`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `load_sheets`
--
ALTER TABLE `load_sheets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `load_sheet_items`
--
ALTER TABLE `load_sheet_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `meta_lead_forms`
--
ALTER TABLE `meta_lead_forms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meta_lead_logs`
--
ALTER TABLE `meta_lead_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `outstanding_ledger`
--
ALTER TABLE `outstanding_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `party_addresses`
--
ALTER TABLE `party_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `party_bank_accounts`
--
ALTER TABLE `party_bank_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `party_contacts`
--
ALTER TABLE `party_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `party_credit_cheques`
--
ALTER TABLE `party_credit_cheques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `party_targets`
--
ALTER TABLE `party_targets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_links`
--
ALTER TABLE `payment_links`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payroll_inputs`
--
ALTER TABLE `payroll_inputs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `price_masters`
--
ALTER TABLE `price_masters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `product_batches`
--
ALTER TABLE `product_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_media`
--
ALTER TABLE `product_media`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_price_histories`
--
ALTER TABLE `product_price_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product_serials`
--
ALTER TABLE `product_serials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_uoms`
--
ALTER TABLE `product_uoms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `purchase_inwards`
--
ALTER TABLE `purchase_inwards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_inward_items`
--
ALTER TABLE `purchase_inward_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quotation_items`
--
ALTER TABLE `quotation_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `region_brand_policies`
--
ALTER TABLE `region_brand_policies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `salary_structures`
--
ALTER TABLE `salary_structures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schemes`
--
ALTER TABLE `schemes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheme_achievements`
--
ALTER TABLE `scheme_achievements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheme_products`
--
ALTER TABLE `scheme_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheme_settlements`
--
ALTER TABLE `scheme_settlements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scheme_slabs`
--
ALTER TABLE `scheme_slabs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settlements`
--
ALTER TABLE `settlements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settlement_lines`
--
ALTER TABLE `settlement_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_cost_consumptions`
--
ALTER TABLE `stock_cost_consumptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_cost_layers`
--
ALTER TABLE `stock_cost_layers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `stock_levels`
--
ALTER TABLE `stock_levels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `stock_reservations`
--
ALTER TABLE `stock_reservations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_valuation_settings`
--
ALTER TABLE `stock_valuation_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sub_categories`
--
ALTER TABLE `sub_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `supplier_payables`
--
ALTER TABLE `supplier_payables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tally_sync_mappings`
--
ALTER TABLE `tally_sync_mappings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tally_sync_queues`
--
ALTER TABLE `tally_sync_queues`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `target_achievements`
--
ALTER TABLE `target_achievements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `target_periods`
--
ALTER TABLE `target_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tax_rates`
--
ALTER TABLE `tax_rates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `uoms`
--
ALTER TABLE `uoms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_branch_access`
--
ALTER TABLE `user_branch_access`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vendor_price_histories`
--
ALTER TABLE `vendor_price_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `approval_logs`
--
ALTER TABLE `approval_logs`
  ADD CONSTRAINT `approval_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cheques`
--
ALTER TABLE `cheques`
  ADD CONSTRAINT `cheques_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cheques_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cheques_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cheque_bounces`
--
ALTER TABLE `cheque_bounces`
  ADD CONSTRAINT `cheque_bounces_cheque_id_foreign` FOREIGN KEY (`cheque_id`) REFERENCES `cheques` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cheque_bounces_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cheque_bounces_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `communication_logs`
--
ALTER TABLE `communication_logs`
  ADD CONSTRAINT `communication_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `communication_logs_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `communication_logs_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `companies`
--
ALTER TABLE `companies`
  ADD CONSTRAINT `companies_business_group_id_foreign` FOREIGN KEY (`business_group_id`) REFERENCES `business_groups` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `company_group_links`
--
ALTER TABLE `company_group_links`
  ADD CONSTRAINT `company_group_links_business_group_id_foreign` FOREIGN KEY (`business_group_id`) REFERENCES `business_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_group_links_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD CONSTRAINT `credit_notes_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `credit_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `credit_notes_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `credit_note_items`
--
ALTER TABLE `credit_note_items`
  ADD CONSTRAINT `credit_note_items_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_note_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `credit_note_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_customer_type_id_foreign` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`),
  ADD CONSTRAINT `customers_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_sales_manager_id_foreign` FOREIGN KEY (`sales_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deals`
--
ALTER TABLE `deals`
  ADD CONSTRAINT `deals_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deals_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `deals_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deal_expenses`
--
ALTER TABLE `deal_expenses`
  ADD CONSTRAINT `deal_expenses_deal_id_foreign` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deal_expenses_expense_type_id_foreign` FOREIGN KEY (`expense_type_id`) REFERENCES `expense_types` (`id`);

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `deliveries_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `deliveries_load_sheet_id_foreign` FOREIGN KEY (`load_sheet_id`) REFERENCES `load_sheets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_items`
--
ALTER TABLE `delivery_items`
  ADD CONSTRAINT `delivery_items_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `delivery_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);

--
-- Constraints for table `demo_stock_notices`
--
ALTER TABLE `demo_stock_notices`
  ADD CONSTRAINT `demo_stock_notices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `demo_stock_notices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `demo_stock_notices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demo_stock_notices_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `designations`
--
ALTER TABLE `designations`
  ADD CONSTRAINT `designations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `document_sequences`
--
ALTER TABLE `document_sequences`
  ADD CONSTRAINT `document_sequences_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `document_sequences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `document_sequences_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_exits`
--
ALTER TABLE `employee_exits`
  ADD CONSTRAINT `employee_exits_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_incentives`
--
ALTER TABLE `employee_incentives`
  ADD CONSTRAINT `employee_incentives_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_kpis`
--
ALTER TABLE `employee_kpis`
  ADD CONSTRAINT `employee_kpis_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  ADD CONSTRAINT `expense_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expense_approvals_deal_expense_id_foreign` FOREIGN KEY (`deal_expense_id`) REFERENCES `deal_expenses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expense_claims`
--
ALTER TABLE `expense_claims`
  ADD CONSTRAINT `expense_claims_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expense_claims_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `e_invoices`
--
ALTER TABLE `e_invoices`
  ADD CONSTRAINT `e_invoices_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `e_way_bills`
--
ALTER TABLE `e_way_bills`
  ADD CONSTRAINT `e_way_bills_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_years`
--
ALTER TABLE `financial_years`
  ADD CONSTRAINT `financial_years_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `freight_bills`
--
ALTER TABLE `freight_bills`
  ADD CONSTRAINT `freight_bills_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `freight_bill_allocations`
--
ALTER TABLE `freight_bill_allocations`
  ADD CONSTRAINT `freight_bill_allocations_freight_bill_id_foreign` FOREIGN KEY (`freight_bill_id`) REFERENCES `freight_bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `interest_documents`
--
ALTER TABLE `interest_documents`
  ADD CONSTRAINT `interest_documents_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interest_documents_interest_ledger_id_foreign` FOREIGN KEY (`interest_ledger_id`) REFERENCES `interest_ledgers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interest_ledgers`
--
ALTER TABLE `interest_ledgers`
  ADD CONSTRAINT `interest_ledgers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interest_ledgers_interest_rule_id_foreign` FOREIGN KEY (`interest_rule_id`) REFERENCES `interest_rules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `interest_ledgers_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interest_rules`
--
ALTER TABLE `interest_rules`
  ADD CONSTRAINT `interest_rules_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `internal_delivery_challans`
--
ALTER TABLE `internal_delivery_challans`
  ADD CONSTRAINT `internal_delivery_challans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `internal_delivery_challans_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `internal_delivery_challans_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `internal_delivery_challans_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `invoice_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);

--
-- Constraints for table `landed_costs`
--
ALTER TABLE `landed_costs`
  ADD CONSTRAINT `landed_costs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `landed_costs_freight_bill_id_foreign` FOREIGN KEY (`freight_bill_id`) REFERENCES `freight_bills` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `landed_costs_purchase_invoice_id_foreign` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `landed_cost_items`
--
ALTER TABLE `landed_cost_items`
  ADD CONSTRAINT `landed_cost_items_landed_cost_id_foreign` FOREIGN KEY (`landed_cost_id`) REFERENCES `landed_costs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `landed_cost_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `landed_cost_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `leads_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_converted_customer_id_foreign` FOREIGN KEY (`converted_customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_lead_campaign_id_foreign` FOREIGN KEY (`lead_campaign_id`) REFERENCES `lead_campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_lead_source_id_foreign` FOREIGN KEY (`lead_source_id`) REFERENCES `lead_sources` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leads_meta_lead_form_id_foreign` FOREIGN KEY (`meta_lead_form_id`) REFERENCES `meta_lead_forms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD CONSTRAINT `lead_activities_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lead_assignments`
--
ALTER TABLE `lead_assignments`
  ADD CONSTRAINT `lead_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lead_assignments_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_assignments_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_conversions`
--
ALTER TABLE `lead_conversions`
  ADD CONSTRAINT `lead_conversions_converted_by_foreign` FOREIGN KEY (`converted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lead_conversions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_conversions_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lead_followups`
--
ALTER TABLE `lead_followups`
  ADD CONSTRAINT `lead_followups_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lead_followups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD CONSTRAINT `leave_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `load_sheets`
--
ALTER TABLE `load_sheets`
  ADD CONSTRAINT `load_sheets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `load_sheets_delivery_person_id_foreign` FOREIGN KEY (`delivery_person_id`) REFERENCES `delivery_persons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `load_sheets_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `load_sheets_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `load_sheets_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `load_sheet_items`
--
ALTER TABLE `load_sheet_items`
  ADD CONSTRAINT `load_sheet_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `load_sheet_items_load_sheet_id_foreign` FOREIGN KEY (`load_sheet_id`) REFERENCES `load_sheets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meta_lead_forms`
--
ALTER TABLE `meta_lead_forms`
  ADD CONSTRAINT `meta_lead_forms_lead_campaign_id_foreign` FOREIGN KEY (`lead_campaign_id`) REFERENCES `lead_campaigns` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `orders_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `order_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);

--
-- Constraints for table `outstanding_ledger`
--
ALTER TABLE `outstanding_ledger`
  ADD CONSTRAINT `outstanding_ledger_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `party_addresses`
--
ALTER TABLE `party_addresses`
  ADD CONSTRAINT `party_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `party_bank_accounts`
--
ALTER TABLE `party_bank_accounts`
  ADD CONSTRAINT `party_bank_accounts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `party_contacts`
--
ALTER TABLE `party_contacts`
  ADD CONSTRAINT `party_contacts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `party_credit_cheques`
--
ALTER TABLE `party_credit_cheques`
  ADD CONSTRAINT `party_credit_cheques_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `party_targets`
--
ALTER TABLE `party_targets`
  ADD CONSTRAINT `party_targets_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `party_targets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `party_targets_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `party_targets_target_period_id_foreign` FOREIGN KEY (`target_period_id`) REFERENCES `target_periods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD CONSTRAINT `payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_links`
--
ALTER TABLE `payment_links`
  ADD CONSTRAINT `payment_links_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_inputs`
--
ALTER TABLE `payroll_inputs`
  ADD CONSTRAINT `payroll_inputs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `price_masters`
--
ALTER TABLE `price_masters`
  ADD CONSTRAINT `price_masters_customer_type_id_foreign` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `price_masters_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `price_masters_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_base_uom_id_foreign` FOREIGN KEY (`base_uom_id`) REFERENCES `uoms` (`id`),
  ADD CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_batches`
--
ALTER TABLE `product_batches`
  ADD CONSTRAINT `product_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_batches_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_batches_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_media`
--
ALTER TABLE `product_media`
  ADD CONSTRAINT `product_media_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_price_histories`
--
ALTER TABLE `product_price_histories`
  ADD CONSTRAINT `product_price_histories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_price_histories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_price_histories_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_serials`
--
ALTER TABLE `product_serials`
  ADD CONSTRAINT `product_serials_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_serials_purchase_inward_item_id_foreign` FOREIGN KEY (`purchase_inward_item_id`) REFERENCES `purchase_inward_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_serials_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_uoms`
--
ALTER TABLE `product_uoms`
  ADD CONSTRAINT `product_uoms_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_uoms_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_supplier_party_id_foreign` FOREIGN KEY (`supplier_party_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchases_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_invoices`
--
ALTER TABLE `purchase_invoices`
  ADD CONSTRAINT `purchase_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_invoices_purchase_inward_id_foreign` FOREIGN KEY (`purchase_inward_id`) REFERENCES `purchase_inwards` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_invoices_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_invoices_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_invoices_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_invoice_items`
--
ALTER TABLE `purchase_invoice_items`
  ADD CONSTRAINT `purchase_invoice_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_invoice_items_purchase_invoice_id_foreign` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_invoice_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_inwards`
--
ALTER TABLE `purchase_inwards`
  ADD CONSTRAINT `purchase_inwards_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_inwards_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_inwards_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_inwards_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_inward_items`
--
ALTER TABLE `purchase_inward_items`
  ADD CONSTRAINT `purchase_inward_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_inward_items_purchase_inward_id_foreign` FOREIGN KEY (`purchase_inward_id`) REFERENCES `purchase_inwards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_inward_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_inward_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quotations`
--
ALTER TABLE `quotations`
  ADD CONSTRAINT `quotations_converted_order_id_foreign` FOREIGN KEY (`converted_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `quotations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `quotations_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `quotation_items`
--
ALTER TABLE `quotation_items`
  ADD CONSTRAINT `quotation_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `quotation_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quotation_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);

--
-- Constraints for table `region_brand_policies`
--
ALTER TABLE `region_brand_policies`
  ADD CONSTRAINT `region_brand_policies_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `region_brand_policies_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `routes`
--
ALTER TABLE `routes`
  ADD CONSTRAINT `routes_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD CONSTRAINT `salary_structures_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schemes`
--
ALTER TABLE `schemes`
  ADD CONSTRAINT `schemes_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `scheme_achievements`
--
ALTER TABLE `scheme_achievements`
  ADD CONSTRAINT `scheme_achievements_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scheme_achievements_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scheme_achievements_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scheme_products`
--
ALTER TABLE `scheme_products`
  ADD CONSTRAINT `scheme_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scheme_products_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scheme_settlements`
--
ALTER TABLE `scheme_settlements`
  ADD CONSTRAINT `scheme_settlements_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scheme_settlements_scheme_achievement_id_foreign` FOREIGN KEY (`scheme_achievement_id`) REFERENCES `scheme_achievements` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scheme_settlements_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scheme_slabs`
--
ALTER TABLE `scheme_slabs`
  ADD CONSTRAINT `scheme_slabs_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settlements`
--
ALTER TABLE `settlements`
  ADD CONSTRAINT `settlements_load_sheet_id_foreign` FOREIGN KEY (`load_sheet_id`) REFERENCES `load_sheets` (`id`),
  ADD CONSTRAINT `settlements_settled_by_foreign` FOREIGN KEY (`settled_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `settlement_lines`
--
ALTER TABLE `settlement_lines`
  ADD CONSTRAINT `settlement_lines_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `settlement_lines_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `settlement_lines_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `stock_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_adjustments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD CONSTRAINT `stock_adjustment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_adjustment_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_cost_consumptions`
--
ALTER TABLE `stock_cost_consumptions`
  ADD CONSTRAINT `stock_cost_consumptions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_cost_consumptions_stock_cost_layer_id_foreign` FOREIGN KEY (`stock_cost_layer_id`) REFERENCES `stock_cost_layers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_cost_consumptions_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_cost_layers`
--
ALTER TABLE `stock_cost_layers`
  ADD CONSTRAINT `stock_cost_layers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_cost_layers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_cost_layers_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_cost_layers_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_levels`
--
ALTER TABLE `stock_levels`
  ADD CONSTRAINT `stock_levels_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_levels_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_levels_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_movements_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`),
  ADD CONSTRAINT `stock_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_reservations`
--
ALTER TABLE `stock_reservations`
  ADD CONSTRAINT `stock_reservations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_reservations_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_reservations_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_reservations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_reservations_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_reservations_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transfers_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfers_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD CONSTRAINT `stock_transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfer_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_valuation_settings`
--
ALTER TABLE `stock_valuation_settings`
  ADD CONSTRAINT `stock_valuation_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_valuation_settings_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD CONSTRAINT `sub_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_payables`
--
ALTER TABLE `supplier_payables`
  ADD CONSTRAINT `supplier_payables_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `target_achievements`
--
ALTER TABLE `target_achievements`
  ADD CONSTRAINT `target_achievements_party_target_id_foreign` FOREIGN KEY (`party_target_id`) REFERENCES `party_targets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `target_achievements_target_period_id_foreign` FOREIGN KEY (`target_period_id`) REFERENCES `target_periods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_branch_access`
--
ALTER TABLE `user_branch_access`
  ADD CONSTRAINT `user_branch_access_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_branch_access_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_price_histories`
--
ALTER TABLE `vendor_price_histories`
  ADD CONSTRAINT `vendor_price_histories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vendor_price_histories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_price_histories_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vendor_price_histories_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD CONSTRAINT `warehouses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
