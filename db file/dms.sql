-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 06:53 AM
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
(2, 'South Zone', 'SOUTH', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
('dms-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:29:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;i:7;i:8;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:16:\"dashboard.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:12:\"masters.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:14:\"masters.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:11:\"orders.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:13:\"orders.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:14:\"inventory.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:5;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:16:\"inventory.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:5;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:10:\"sales.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:6;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:12:\"sales.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:6;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:13:\"payments.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:6;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:15:\"payments.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:6;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:19:\"communications.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:21:\"communications.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:14:\"logistics.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:7;i:2;i:8;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:16:\"logistics.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:7;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:13:\"delivery.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:7;i:2;i:8;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:15:\"delivery.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:7;i:2;i:8;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:15:\"settlement.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:17:\"settlement.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:12:\"reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:14:\"reports.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:14:\"orders.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:3;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:11:\"orders.book\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:14:\"orders.convert\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:18:\"payments.reconcile\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:6;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:16:\"settlement.entry\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:7;i:2;i:8;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:13:\"create orders\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:4;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:18:\"manage settlements\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:2;i:1;i:6;}}}s:5:\"roles\";a:8:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"owner\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:11:\"super-admin\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:13:\"sales-manager\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:11:\"salesperson\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:9:\"warehouse\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:7:\"finance\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:6:\"driver\";s:1:\"c\";s:3:\"web\";}i:7;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:15:\"delivery-person\";s:1:\"c\";s:3:\"web\";}}}', 1788085156);

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
-- Table structure for table `communication_logs`
--

CREATE TABLE `communication_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('whatsapp_invoice','payment_link','payment_reminder') NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'sent',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `sent_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `customer_type_id` bigint(20) UNSIGNED NOT NULL,
  `area_id` bigint(20) UNSIGNED DEFAULT NULL,
  `route_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salesperson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `code`, `customer_type_id`, `area_id`, `route_id`, `salesperson_id`, `phone`, `email`, `address`, `gstin`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Metro Retail Mart', 'CUST-001', 1, 1, 1, 4, '9876534270', 'metro.retail.mart@example.com', 'Demo address, Distribution city', '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'Green Valley Wholesalers', 'CUST-002', 2, 1, 1, 4, '9876517725', 'green.valley.wholesalers@example.com', 'Demo address, Distribution city', '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'City Stores Pvt Ltd', 'CUST-003', 3, 1, 2, 4, '9876588294', 'city.stores.pvt.ltd@example.com', 'Demo address, Distribution city', '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 'Bulk Foods Depot', 'CUST-004', 4, 1, 1, 4, '9876588658', 'bulk.foods.depot@example.com', 'Demo address, Distribution city', '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 'Sunrise Kirana', 'CUST-005', 1, 1, 2, 4, '9876579871', 'sunrise.kirana@example.com', 'Demo address, Distribution city', '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 'Prime Distributors', 'CUST-006', 2, 1, 1, 4, '9876528217', 'prime.distributors@example.com', 'Demo address, Distribution city', '29ABCDE1234F1Z5', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
(4, 'Bulk', 'BLK', 'Bulk buyers with tier pricing', 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
(1, 1, 3, 1, 'out_for_delivery', '2026-08-29 04:40:02', '2026-08-29 04:40:02'),
(2, 1, 2, 2, 'pending', '2026-08-29 04:40:02', '2026-08-29 04:40:02');

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
(2, 2, 2, 5, 24.0000, 0.0000, 0.0000, 0.0000, '2026-08-29 04:40:02', '2026-08-29 04:40:02');

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
-- Table structure for table `e_invoices`
--

CREATE TABLE `e_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','generated','manual') NOT NULL DEFAULT 'pending',
  `irn` varchar(255) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `e_way_bills`
--

CREATE TABLE `e_way_bills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','generated','manual') NOT NULL DEFAULT 'pending',
  `eway_bill_no` varchar(255) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_no` varchar(30) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salesperson_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `status` enum('draft','issued','paid','partial','cancelled') NOT NULL DEFAULT 'issued',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `customer_id`, `order_id`, `salesperson_id`, `invoice_date`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `grand_total`, `paid_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'INV-0001', 3, 3, 4, '2026-08-29', 'partial', 1000.00, 0.00, 50.00, 1050.00, 500.00, 'Demo invoice from converted order', '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'INV-0002', 2, NULL, 4, '2026-08-29', 'issued', 3168.00, 0.00, 380.16, 3548.16, 0.00, 'Direct billing demo invoice', '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `uom_id`, `quantity`, `unit_price`, `discount_amount`, `tax_amount`, `line_total`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 1, 10.0000, 100.00, 0.00, 50.00, 1000.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 2, 2, 5, 24.0000, 132.00, 0.00, 380.16, 3168.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
(1, 'LS-0001', '2026-08-29', 1, 1, 1, 1, 'in_transit', 4598.16, 34.0000, 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
(2, 1, 2, 10.0000, 3548.16, '2026-08-29 04:40:02', '2026-08-29 04:40:02');

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
(5, '2026_08_29_100000_create_dms_tables', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(8, 'App\\Models\\User', 8);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_no` varchar(30) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `salesperson_id` bigint(20) UNSIGNED NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('draft','pending','approved','converted','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_no`, `customer_id`, `salesperson_id`, `order_date`, `status`, `subtotal`, `discount_amount`, `tax_amount`, `grand_total`, `notes`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 'ORD-1001', 1, 4, '2026-08-29', 'pending', 2600.00, 0.00, 130.00, 2730.00, 'Demo order', NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'ORD-1002', 2, 4, '2026-08-29', 'approved', 3168.00, 0.00, 158.40, 3326.40, 'Demo order', 3, '2026-08-29 02:40:01', '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'ORD-1003', 3, 4, '2026-08-28', 'converted', 1000.00, 0.00, 50.00, 1050.00, 'Demo order', 3, '2026-08-29 02:40:01', '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 'ORD-1004', 4, 4, '2026-08-29', 'draft', 9000.00, 0.00, 450.00, 9450.00, 'Demo order', NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 'ORD-1005', 5, 4, '2026-08-28', 'cancelled', 1740.00, 0.00, 87.00, 1827.00, 'Demo order', NULL, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `uom_id`, `quantity`, `unit_price`, `line_total`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 50.0000, 52.00, 2600.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 2, 2, 5, 24.0000, 132.00, 3168.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 3, 3, 1, 10.0000, 100.00, 1000.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 4, 1, 4, 200.0000, 45.00, 9000.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 5, 2, 5, 12.0000, 145.00, 1740.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `outstanding_ledger`
--

CREATE TABLE `outstanding_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('invoice','payment','settlement','adjustment') NOT NULL,
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
(3, 2, 'invoice', 'App\\Domains\\Sales\\Models\\Invoice', 2, 3548.16, 0.00, 3548.16, 'Invoice INV-0002', '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `method` enum('cash','upi','bank','other') NOT NULL DEFAULT 'cash',
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

INSERT INTO `payments` (`id`, `payment_no`, `invoice_id`, `customer_id`, `amount`, `method`, `status`, `paid_at`, `recorded_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'PAY-0001', 1, 3, 500.00, 'upi', 'completed', '2026-08-29 01:40:01', 6, 'Partial UPI collection', '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `payment_links`
--

CREATE TABLE `payment_links` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` enum('active','paid','expired') NOT NULL DEFAULT 'active',
  `expires_at` timestamp NULL DEFAULT NULL,
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
(29, 'manage settlements', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59');

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `price_masters`
--

INSERT INTO `price_masters` (`id`, `customer_type_id`, `product_id`, `uom_id`, `rate`, `min_qty`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 52.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 1, 2, 5, 145.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 1, 3, 1, 120.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 2, 1, 4, 48.50, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 2, 2, 5, 132.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 2, 3, 1, 105.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(7, 3, 1, 4, 47.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(8, 3, 2, 5, 128.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(9, 3, 3, 1, 100.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(10, 4, 1, 4, 45.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(11, 4, 2, 5, 122.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(12, 4, 3, 1, 95.00, NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(13, 4, 1, 4, 42.00, 500.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `base_uom_id` bigint(20) UNSIGNED NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `sku`, `description`, `base_uom_id`, `tax_rate`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Premium Basmati Rice 25kg', 'RICE-25KG', 'Demo product for DMS walkthrough', 4, 5.00, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 'Sunflower Cooking Oil 1L', 'OIL-1L', 'Demo product for DMS walkthrough', 5, 12.00, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 'Assorted Biscuits', 'BISC-MIX', 'Demo product for DMS walkthrough', 1, 18.00, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_uoms`
--

INSERT INTO `product_uoms` (`id`, `product_id`, `uom_id`, `conversion_factor`, `is_base`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 1.0000, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 1, 3, 25.0000, 0, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 2, 5, 1.0000, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(4, 2, 3, 12.0000, 0, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(5, 3, 1, 1.0000, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 3, 2, 0.1000, 0, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(7, 3, 3, 10.0000, 0, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_no` varchar(30) NOT NULL,
  `purchase_date` date NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `status` enum('draft','posted') NOT NULL DEFAULT 'posted',
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `purchase_no`, `purchase_date`, `supplier_name`, `status`, `grand_total`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PUR-0001', '2026-08-22', 'National Foods Supplier', 'posted', 51880.00, 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
(3, 1, 3, 1, 120.0000, 78.00, 9360.00, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
(8, 'delivery-person', 'web', '2026-08-29 04:39:59', '2026-08-29 04:39:59');

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
(2, 2),
(3, 2),
(3, 3),
(3, 4),
(4, 2),
(5, 2),
(5, 3),
(5, 4),
(6, 2),
(6, 3),
(6, 4),
(7, 2),
(7, 5),
(8, 2),
(8, 5),
(9, 2),
(9, 6),
(10, 2),
(10, 6),
(11, 2),
(11, 6),
(12, 2),
(12, 6),
(13, 2),
(14, 2),
(15, 2),
(15, 7),
(15, 8),
(16, 2),
(16, 7),
(17, 2),
(17, 7),
(17, 8),
(18, 2),
(18, 7),
(18, 8),
(19, 2),
(20, 2),
(21, 1),
(21, 2),
(21, 3),
(21, 6),
(22, 2),
(22, 3),
(23, 2),
(23, 3),
(24, 2),
(24, 4),
(25, 2),
(26, 2),
(26, 6),
(27, 2),
(27, 7),
(27, 8),
(28, 2),
(28, 4),
(29, 2),
(29, 6);

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
(2, 'Route B - South', 'RT-B', 2, 1, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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
('sFPJU7P4vqn3FwaZ41134dxsSih31RghOcXJkHdr', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiODB3NG1WejJQdndjVThMeWhMWG5nU0NLWnBBN3pCdVdPQXpjVFl1NSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9sb2NhbGhvc3QvZG1zL3B1YmxpYy9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1788151971),
('zTejKS8vqwgqCahfbjzEWwXLWPq16bRWi4009EDM', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQ0pmcWZzYXpzaDlhV0lGeU1ENlBvak1lY1laNUdKQktUQ2g3d3ZSMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDk6Imh0dHA6Ly9sb2NhbGhvc3QvZG1zL3B1YmxpYy9tYXN0ZXJzL3ByaWNlLW1hc3RlcnMiO3M6NToicm91dGUiO3M6Mjc6Im1hc3RlcnMucHJpY2UtbWFzdGVycy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjI7fQ==', 1787998783);

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

-- --------------------------------------------------------

--
-- Table structure for table `stock_levels`
--

CREATE TABLE `stock_levels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_levels`
--

INSERT INTO `stock_levels` (`id`, `product_id`, `uom_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 500.0000, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 2, 5, 240.0000, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 3, 1, 8.0000, '2026-08-29 04:40:01', '2026-08-29 04:40:02');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('purchase','sale','adjustment','return','delivery_short') NOT NULL,
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

INSERT INTO `stock_movements` (`id`, `product_id`, `uom_id`, `type`, `quantity`, `balance_after`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'purchase', 500.0000, 500.0000, 'App\\Domains\\Inventory\\Models\\Purchase', 1, 'Initial demo stock purchase', 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(2, 2, 5, 'purchase', 240.0000, 240.0000, 'App\\Domains\\Inventory\\Models\\Purchase', 1, 'Initial demo stock purchase', 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(3, 3, 1, 'purchase', 120.0000, 120.0000, 'App\\Domains\\Inventory\\Models\\Purchase', 1, 'Initial demo stock purchase', 5, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Owner User', 'owner@dms.test', '2026-08-29 04:40:00', '$2y$12$eKJU/zr5jPVQ6yo36db74uGe5PTflbP5goaiSdqmx0QeFLsvIkiyy', NULL, '2026-08-29 04:40:00', '2026-08-29 04:40:00'),
(2, 'Super Admin', 'superadmin@dms.test', '2026-08-29 04:40:00', '$2y$12$1VfCf3FW1WH4nE9Sjl7rse5z79I6ubA6gir5yzAhWjikfX0O8N9ZC', NULL, '2026-08-29 04:40:00', '2026-08-29 04:40:00'),
(3, 'Sales Manager', 'salesmanager@dms.test', '2026-08-29 04:40:00', '$2y$12$eg.rKZHI0XaUFQIa8S5a1OjESxptdONHKzECub3NbH.4rQd0lMOzm', NULL, '2026-08-29 04:40:00', '2026-08-29 04:40:00'),
(4, 'Sales Person', 'salesperson@dms.test', '2026-08-29 04:40:00', '$2y$12$GAWTIWlfayxYyueru8s0Qu2XsqqB9pJ3BaGI6M31q8WAWtjiUPqkq', NULL, '2026-08-29 04:40:00', '2026-08-29 04:40:00'),
(5, 'Warehouse Manager', 'warehouse@dms.test', '2026-08-29 04:40:01', '$2y$12$zdfbTwy9mm2Jh/Va.OdvJ.We84MZK7gQ.S8.wX39jG54GAxHoJDpS', NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(6, 'Finance Manager', 'finance@dms.test', '2026-08-29 04:40:01', '$2y$12$lns5/IArutKCOzk9NjEkxu4mwW1Z6wzINx9F3/AjgckhsXsLwhG7O', NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(7, 'Driver One', 'driver@dms.test', '2026-08-29 04:40:01', '$2y$12$kQt9Deorg6KSVno1WxtXyeJJ5NutdGNM5PLApQhs6lFaQHLIF2Jbm', NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01'),
(8, 'Delivery Person', 'delivery@dms.test', '2026-08-29 04:40:01', '$2y$12$c6EHUqgh71HYJqH26F7xxORKphGdpsf7jWhUqVp/qWwa8IZI0ERHu', NULL, '2026-08-29 04:40:01', '2026-08-29 04:40:01');

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `areas_code_unique` (`code`);

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
-- Indexes for table `communication_logs`
--
ALTER TABLE `communication_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `communication_logs_invoice_id_foreign` (`invoice_id`),
  ADD KEY `communication_logs_customer_id_foreign` (`customer_id`),
  ADD KEY `communication_logs_sent_by_foreign` (`sent_by`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_code_unique` (`code`),
  ADD KEY `customers_customer_type_id_foreign` (`customer_type_id`),
  ADD KEY `customers_area_id_foreign` (`area_id`),
  ADD KEY `customers_route_id_foreign` (`route_id`),
  ADD KEY `customers_salesperson_id_foreign` (`salesperson_id`);

--
-- Indexes for table `customer_types`
--
ALTER TABLE `customer_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_types_name_unique` (`name`),
  ADD UNIQUE KEY `customer_types_code_unique` (`code`);

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
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `e_way_bills_invoice_id_foreign` (`invoice_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_invoice_no_unique` (`invoice_no`),
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
  ADD KEY `orders_approved_by_foreign` (`approved_by`);

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
  ADD KEY `payments_invoice_id_foreign` (`invoice_id`),
  ADD KEY `payments_customer_id_foreign` (`customer_id`),
  ADD KEY `payments_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `payment_links`
--
ALTER TABLE `payment_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_links_token_unique` (`token`),
  ADD KEY `payment_links_invoice_id_foreign` (`invoice_id`);

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
  ADD KEY `products_base_uom_id_foreign` (`base_uom_id`);

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
  ADD KEY `purchases_created_by_foreign` (`created_by`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  ADD KEY `purchase_items_product_id_foreign` (`product_id`),
  ADD KEY `purchase_items_uom_id_foreign` (`uom_id`);

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
-- Indexes for table `stock_levels`
--
ALTER TABLE `stock_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_levels_product_id_uom_id_unique` (`product_id`,`uom_id`),
  ADD KEY `stock_levels_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_product_id_foreign` (`product_id`),
  ADD KEY `stock_movements_uom_id_foreign` (`uom_id`),
  ADD KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `stock_movements_created_by_foreign` (`created_by`);

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
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicles_registration_no_unique` (`registration_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `communication_logs`
--
ALTER TABLE `communication_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `customer_types`
--
ALTER TABLE `customer_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `delivery_items`
--
ALTER TABLE `delivery_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `delivery_persons`
--
ALTER TABLE `delivery_persons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `e_invoices`
--
ALTER TABLE `e_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `e_way_bills`
--
ALTER TABLE `e_way_bills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `load_sheets`
--
ALTER TABLE `load_sheets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `load_sheet_items`
--
ALTER TABLE `load_sheet_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `outstanding_ledger`
--
ALTER TABLE `outstanding_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_links`
--
ALTER TABLE `payment_links`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `price_masters`
--
ALTER TABLE `price_masters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_uoms`
--
ALTER TABLE `product_uoms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settlements`
--
ALTER TABLE `settlements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settlement_lines`
--
ALTER TABLE `settlement_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_levels`
--
ALTER TABLE `stock_levels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `communication_logs`
--
ALTER TABLE `communication_logs`
  ADD CONSTRAINT `communication_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `communication_logs_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `communication_logs_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_customer_type_id_foreign` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`),
  ADD CONSTRAINT `customers_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `customers_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `orders_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`);

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
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_links`
--
ALTER TABLE `payment_links`
  ADD CONSTRAINT `payment_links_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `products_base_uom_id_foreign` FOREIGN KEY (`base_uom_id`) REFERENCES `uoms` (`id`);

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
  ADD CONSTRAINT `purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);

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
-- Constraints for table `stock_levels`
--
ALTER TABLE `stock_levels`
  ADD CONSTRAINT `stock_levels_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_levels_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_movements_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
