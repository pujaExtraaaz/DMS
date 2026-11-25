-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: dms
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accounting_company_links`
--

DROP TABLE IF EXISTS `accounting_company_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_company_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `organization_company_id` bigint(20) unsigned NOT NULL,
  `acct_company_id` bigint(20) unsigned NOT NULL,
  `acct_branch_id` bigint(20) unsigned DEFAULT NULL,
  `acct_financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_company_links_organization_company_id_unique` (`organization_company_id`),
  UNIQUE KEY `accounting_company_links_acct_company_id_unique` (`acct_company_id`),
  CONSTRAINT `accounting_company_links_organization_company_id_foreign` FOREIGN KEY (`organization_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_account_groups`
--

DROP TABLE IF EXISTS `acct_account_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_account_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `nature` varchar(20) NOT NULL,
  `affects_gross_profit` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_account_groups_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_account_groups_company_id_code_unique` (`company_id`,`code`),
  KEY `acct_account_groups_parent_id_foreign` (`parent_id`),
  KEY `acct_account_groups_company_id_parent_id_index` (`company_id`,`parent_id`),
  KEY `acct_account_groups_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `acct_account_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_account_groups_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `acct_account_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_accounting_periods`
--

DROP TABLE IF EXISTS `acct_accounting_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_accounting_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `name` varchar(60) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_periods_unique_start` (`company_id`,`financial_year_id`,`start_date`),
  KEY `acct_accounting_periods_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_accounting_periods_locked_by_foreign` (`locked_by`),
  KEY `acct_accounting_periods_company_id_start_date_end_date_index` (`company_id`,`start_date`,`end_date`),
  CONSTRAINT `acct_accounting_periods_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_accounting_periods_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_accounting_periods_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_accrued_expenses`
--

DROP TABLE IF EXISTS `acct_accrued_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_accrued_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `accrual_date` date NOT NULL,
  `expense_ledger_id` bigint(20) unsigned NOT NULL,
  `accrual_ledger_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `goods_receipt_id` bigint(20) unsigned DEFAULT NULL,
  `is_uninvoiced_receipt` tinyint(1) NOT NULL DEFAULT 0,
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `accrual_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `reversal_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_accrued_expenses_company_id_foreign` (`company_id`),
  KEY `acct_accrued_expenses_branch_id_foreign` (`branch_id`),
  KEY `acct_accrued_expenses_expense_ledger_id_foreign` (`expense_ledger_id`),
  KEY `acct_accrued_expenses_accrual_ledger_id_foreign` (`accrual_ledger_id`),
  KEY `acct_accrued_expenses_goods_receipt_id_foreign` (`goods_receipt_id`),
  KEY `acct_accrued_expenses_accrual_voucher_id_foreign` (`accrual_voucher_id`),
  KEY `acct_accrued_expenses_reversal_voucher_id_foreign` (`reversal_voucher_id`),
  CONSTRAINT `acct_accrued_expenses_accrual_ledger_id_foreign` FOREIGN KEY (`accrual_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_accrued_expenses_accrual_voucher_id_foreign` FOREIGN KEY (`accrual_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_accrued_expenses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_accrued_expenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_accrued_expenses_expense_ledger_id_foreign` FOREIGN KEY (`expense_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_accrued_expenses_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `acct_goods_receipts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_accrued_expenses_reversal_voucher_id_foreign` FOREIGN KEY (`reversal_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_anomaly_flags`
--

DROP TABLE IF EXISTS `acct_anomaly_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_anomaly_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `flag_type` varchar(40) NOT NULL,
  `document_type` varchar(40) DEFAULT NULL,
  `document_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL,
  `severity` varchar(80) NOT NULL DEFAULT 'medium',
  `message` varchar(500) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_anomaly_idx` (`company_id`,`status`,`flag_type`),
  CONSTRAINT `acct_anomaly_flags_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_api_request_logs`
--

DROP TABLE IF EXISTS `acct_api_request_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_api_request_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `api_token_id` bigint(20) unsigned DEFAULT NULL,
  `method` varchar(10) NOT NULL,
  `path` varchar(255) NOT NULL,
  `status` smallint(5) unsigned NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `duration_ms` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `acct_api_request_logs_user_id_foreign` (`user_id`),
  KEY `acct_api_request_logs_api_token_id_foreign` (`api_token_id`),
  KEY `acct_api_request_logs_path_created_at_index` (`path`,`created_at`),
  CONSTRAINT `acct_api_request_logs_api_token_id_foreign` FOREIGN KEY (`api_token_id`) REFERENCES `acct_api_tokens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_api_request_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_api_tokens`
--

DROP TABLE IF EXISTS `acct_api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_api_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_api_tokens_token_hash_unique` (`token_hash`),
  KEY `acct_api_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `acct_api_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_approval_policies`
--

DROP TABLE IF EXISTS `acct_approval_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_approval_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `document_type` varchar(40) NOT NULL,
  `min_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `max_amount` decimal(18,2) DEFAULT NULL,
  `levels` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `approver_role_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`approver_role_ids`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_approval_policies_company_id_foreign` (`company_id`),
  CONSTRAINT `acct_approval_policies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_approval_requests`
--

DROP TABLE IF EXISTS `acct_approval_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_approval_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `approval_policy_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(40) NOT NULL,
  `document_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `current_level` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_approval_requests_approval_policy_id_foreign` (`approval_policy_id`),
  KEY `acct_approval_requests_requested_by_foreign` (`requested_by`),
  KEY `acct_approval_requests_decided_by_foreign` (`decided_by`),
  KEY `acct_approval_doc_idx` (`company_id`,`document_type`,`document_id`),
  CONSTRAINT `acct_approval_requests_approval_policy_id_foreign` FOREIGN KEY (`approval_policy_id`) REFERENCES `acct_approval_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_approval_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_approval_requests_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_approval_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_asset_verifications`
--

DROP TABLE IF EXISTS `acct_asset_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_asset_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `fixed_asset_id` bigint(20) unsigned NOT NULL,
  `verification_date` date NOT NULL,
  `verified_by` varchar(120) DEFAULT NULL,
  `condition` varchar(40) NOT NULL DEFAULT 'good',
  `found` tinyint(1) NOT NULL DEFAULT 1,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_asset_verifications_company_id_foreign` (`company_id`),
  KEY `acct_asset_verifications_fixed_asset_id_foreign` (`fixed_asset_id`),
  CONSTRAINT `acct_asset_verifications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_asset_verifications_fixed_asset_id_foreign` FOREIGN KEY (`fixed_asset_id`) REFERENCES `acct_fixed_assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_attendances`
--

DROP TABLE IF EXISTS `acct_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_type` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_attendances_employee_id_attendance_date_unique` (`employee_id`,`attendance_date`),
  KEY `acct_attendances_company_id_foreign` (`company_id`),
  CONSTRAINT `acct_attendances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `acct_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_audit_logs`
--

DROP TABLE IF EXISTS `acct_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `module` varchar(40) NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `previous_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`previous_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `source` varchar(16) NOT NULL DEFAULT 'web',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `acct_audit_logs_user_id_foreign` (`user_id`),
  KEY `acct_audit_logs_branch_id_foreign` (`branch_id`),
  KEY `acct_audit_logs_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_audit_logs_module_action_index` (`module`,`action`),
  KEY `acct_audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `acct_audit_logs_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `audit_logs_action_index` (`action`,`created_at`),
  CONSTRAINT `acct_audit_logs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_audit_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_audit_logs_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_balance_sheet_reconciliations`
--

DROP TABLE IF EXISTS `acct_balance_sheet_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_balance_sheet_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `as_of_date` date NOT NULL,
  `gl_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `substantiated_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `difference` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `evidence_note` varchar(500) DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_bs_recon_uq` (`company_id`,`ledger_id`,`as_of_date`),
  KEY `acct_balance_sheet_reconciliations_ledger_id_foreign` (`ledger_id`),
  KEY `acct_balance_sheet_reconciliations_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `acct_balance_sheet_reconciliations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_balance_sheet_reconciliations_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_balance_sheet_reconciliations_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bank_accounts`
--

DROP TABLE IF EXISTS `acct_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(30) NOT NULL,
  `ifsc` varchar(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_accounts_number_unique` (`company_id`,`account_number`),
  UNIQUE KEY `acct_bank_accounts_ledger_id_unique` (`ledger_id`),
  KEY `acct_bank_accounts_company_id_ifsc_index` (`company_id`,`ifsc`),
  CONSTRAINT `acct_bank_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_bank_accounts_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bank_categorization_rules`
--

DROP TABLE IF EXISTS `acct_bank_categorization_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bank_categorization_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `match_field` varchar(40) NOT NULL DEFAULT 'narration',
  `match_type` varchar(20) NOT NULL DEFAULT 'contains',
  `match_value` varchar(255) NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `priority` smallint(5) unsigned NOT NULL DEFAULT 100,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_bank_categorization_rules_company_id_foreign` (`company_id`),
  KEY `acct_bank_categorization_rules_ledger_id_foreign` (`ledger_id`),
  CONSTRAINT `acct_bank_categorization_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_bank_categorization_rules_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bank_instruments`
--

DROP TABLE IF EXISTS `acct_bank_instruments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bank_instruments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `party_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `number` varchar(30) NOT NULL,
  `instrument_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `favouring` varchar(160) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `narration` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bank_instruments_number_unique` (`company_id`,`number`),
  KEY `acct_bank_instruments_branch_id_foreign` (`branch_id`),
  KEY `acct_bank_instruments_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_bank_instruments_bank_account_id_foreign` (`bank_account_id`),
  KEY `acct_bank_instruments_voucher_id_foreign` (`voucher_id`),
  KEY `acct_bank_instruments_party_ledger_id_foreign` (`party_ledger_id`),
  KEY `bank_instruments_date_index` (`company_id`,`financial_year_id`,`instrument_date`),
  KEY `acct_bank_instruments_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `acct_bank_instruments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_bank_instruments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_bank_instruments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_bank_instruments_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_bank_instruments_party_ledger_id_foreign` FOREIGN KEY (`party_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_bank_instruments_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bank_reconciliations`
--

DROP TABLE IF EXISTS `acct_bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bank_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `voucher_entry_id` bigint(20) unsigned NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `book_amount` decimal(15,2) NOT NULL,
  `bank_amount` decimal(15,2) NOT NULL,
  `status` varchar(20) NOT NULL,
  `reconciled_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_bank_reconciliations_voucher_entry_id_unique` (`voucher_entry_id`),
  KEY `acct_bank_reconciliations_ledger_id_foreign` (`ledger_id`),
  KEY `bank_reconciliations_status_index` (`company_id`,`ledger_id`,`status`),
  CONSTRAINT `acct_bank_reconciliations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_bank_reconciliations_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_bank_reconciliations_voucher_entry_id_foreign` FOREIGN KEY (`voucher_entry_id`) REFERENCES `acct_voucher_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bank_signatory_limits`
--

DROP TABLE IF EXISTS `acct_bank_signatory_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bank_signatory_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `single_limit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `daily_limit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `requires_dual` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_bank_signatory_uq` (`bank_account_id`,`user_id`),
  KEY `acct_bank_signatory_limits_company_id_foreign` (`company_id`),
  KEY `acct_bank_signatory_limits_user_id_foreign` (`user_id`),
  CONSTRAINT `acct_bank_signatory_limits_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_bank_signatory_limits_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_bank_signatory_limits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bank_statement_lines`
--

DROP TABLE IF EXISTS `acct_bank_statement_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bank_statement_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bank_account_id` bigint(20) unsigned NOT NULL,
  `statement_date` date NOT NULL,
  `value_date` date DEFAULT NULL,
  `narration` varchar(500) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference` varchar(80) DEFAULT NULL,
  `transaction_id` varchar(80) DEFAULT NULL,
  `fingerprint` varchar(64) NOT NULL,
  `voucher_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `categorized_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `investigation_note` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_bank_statement_lines_bank_account_id_fingerprint_unique` (`bank_account_id`,`fingerprint`),
  KEY `acct_bank_statement_lines_company_id_foreign` (`company_id`),
  KEY `acct_bank_statement_lines_voucher_entry_id_foreign` (`voucher_entry_id`),
  KEY `acct_bank_statement_lines_categorized_ledger_id_foreign` (`categorized_ledger_id`),
  CONSTRAINT `acct_bank_statement_lines_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_bank_statement_lines_categorized_ledger_id_foreign` FOREIGN KEY (`categorized_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_bank_statement_lines_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_bank_statement_lines_voucher_entry_id_foreign` FOREIGN KEY (`voucher_entry_id`) REFERENCES `acct_voucher_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bill_allocations`
--

DROP TABLE IF EXISTS `acct_bill_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bill_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bill_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `reference_type` varchar(20) NOT NULL DEFAULT 'agst_ref',
  `reference_name` varchar(40) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_bill_allocations_company_id_foreign` (`company_id`),
  KEY `acct_bill_allocations_ledger_id_foreign` (`ledger_id`),
  KEY `acct_bill_allocations_voucher_id_ledger_id_index` (`voucher_id`,`ledger_id`),
  KEY `acct_bill_allocations_bill_id_ledger_id_index` (`bill_id`,`ledger_id`),
  CONSTRAINT `acct_bill_allocations_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `acct_bills` (`id`),
  CONSTRAINT `acct_bill_allocations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_bill_allocations_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_bill_allocations_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bills`
--

DROP TABLE IF EXISTS `acct_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `voucher_entry_id` bigint(20) unsigned DEFAULT NULL,
  `bill_number` varchar(40) NOT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `original_amount` decimal(15,2) NOT NULL,
  `side` varchar(6) NOT NULL,
  `is_opening` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bills_number_unique` (`company_id`,`ledger_id`,`bill_number`),
  UNIQUE KEY `acct_bills_voucher_entry_id_unique` (`voucher_entry_id`),
  KEY `acct_bills_branch_id_foreign` (`branch_id`),
  KEY `acct_bills_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_bills_ledger_id_foreign` (`ledger_id`),
  KEY `bills_party_date_index` (`company_id`,`ledger_id`,`bill_date`),
  CONSTRAINT `acct_bills_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_bills_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_bills_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_bills_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_bills_voucher_entry_id_foreign` FOREIGN KEY (`voucher_entry_id`) REFERENCES `acct_voucher_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bills_of_materials`
--

DROP TABLE IF EXISTS `acct_bills_of_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bills_of_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `finished_product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `wastage_percent` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_bills_of_materials_company_id_name_unique` (`company_id`,`name`),
  KEY `acct_bills_of_materials_finished_product_id_foreign` (`finished_product_id`),
  CONSTRAINT `acct_bills_of_materials_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_bills_of_materials_finished_product_id_foreign` FOREIGN KEY (`finished_product_id`) REFERENCES `acct_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bom_byproducts`
--

DROP TABLE IF EXISTS `acct_bom_byproducts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bom_byproducts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bill_of_material_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_bom_byproducts_bill_of_material_id_foreign` (`bill_of_material_id`),
  KEY `acct_bom_byproducts_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_bom_byproducts_bill_of_material_id_foreign` FOREIGN KEY (`bill_of_material_id`) REFERENCES `acct_bills_of_materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_bom_byproducts_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_bom_lines`
--

DROP TABLE IF EXISTS `acct_bom_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_bom_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bill_of_material_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `wastage_percent` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_bom_lines_bill_of_material_id_foreign` (`bill_of_material_id`),
  KEY `acct_bom_lines_product_id_foreign` (`product_id`),
  KEY `acct_bom_lines_unit_id_foreign` (`unit_id`),
  CONSTRAINT `acct_bom_lines_bill_of_material_id_foreign` FOREIGN KEY (`bill_of_material_id`) REFERENCES `acct_bills_of_materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_bom_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`),
  CONSTRAINT `acct_bom_lines_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `acct_units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_branches`
--

DROP TABLE IF EXISTS `acct_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_branches_company_id_code_unique` (`company_id`,`code`),
  KEY `acct_branches_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `acct_branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_budgets`
--

DROP TABLE IF EXISTS `acct_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `ledger_id` bigint(20) unsigned DEFAULT NULL,
  `account_group_id` bigint(20) unsigned DEFAULT NULL,
  `cost_centre_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_budgets_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_budgets_ledger_id_foreign` (`ledger_id`),
  KEY `acct_budgets_account_group_id_foreign` (`account_group_id`),
  KEY `acct_budgets_cost_centre_id_foreign` (`cost_centre_id`),
  KEY `budgets_period_index` (`company_id`,`financial_year_id`,`period_start`),
  CONSTRAINT `acct_budgets_account_group_id_foreign` FOREIGN KEY (`account_group_id`) REFERENCES `acct_account_groups` (`id`),
  CONSTRAINT `acct_budgets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_budgets_cost_centre_id_foreign` FOREIGN KEY (`cost_centre_id`) REFERENCES `acct_cost_centres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_budgets_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_budgets_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_business_units`
--

DROP TABLE IF EXISTS `acct_business_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_business_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_business_units_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `acct_business_units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_cash_forecasts`
--

DROP TABLE IF EXISTS `acct_cash_forecasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_cash_forecasts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `as_of_date` date NOT NULL,
  `horizon_days` smallint(5) unsigned NOT NULL DEFAULT 30,
  `opening_cash` decimal(18,2) NOT NULL DEFAULT 0.00,
  `projected_inflows` decimal(18,2) NOT NULL DEFAULT 0.00,
  `projected_outflows` decimal(18,2) NOT NULL DEFAULT 0.00,
  `closing_cash` decimal(18,2) NOT NULL DEFAULT 0.00,
  `scenario` varchar(40) NOT NULL DEFAULT 'base',
  `lines_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`lines_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_cash_forecasts_company_id_foreign` (`company_id`),
  CONSTRAINT `acct_cash_forecasts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_close_checklist_tasks`
--

DROP TABLE IF EXISTS `acct_close_checklist_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_close_checklist_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `close_checklist_id` bigint(20) unsigned NOT NULL,
  `task_key` varchar(80) NOT NULL,
  `title` varchar(160) NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_close_checklist_tasks_close_checklist_id_foreign` (`close_checklist_id`),
  KEY `acct_close_checklist_tasks_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `acct_close_checklist_tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_close_checklist_tasks_close_checklist_id_foreign` FOREIGN KEY (`close_checklist_id`) REFERENCES `acct_close_checklists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_close_checklists`
--

DROP TABLE IF EXISTS `acct_close_checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_close_checklists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `accounting_period_id` bigint(20) unsigned DEFAULT NULL,
  `period_label` varchar(40) NOT NULL,
  `kind` varchar(20) NOT NULL DEFAULT 'month',
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_close_checklists_company_id_foreign` (`company_id`),
  KEY `acct_close_checklists_accounting_period_id_foreign` (`accounting_period_id`),
  KEY `acct_close_checklists_owner_id_foreign` (`owner_id`),
  CONSTRAINT `acct_close_checklists_accounting_period_id_foreign` FOREIGN KEY (`accounting_period_id`) REFERENCES `acct_accounting_periods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_close_checklists_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_close_checklists_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_collection_reminders`
--

DROP TABLE IF EXISTS `acct_collection_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_collection_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bill_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `channel` varchar(40) NOT NULL DEFAULT 'queue',
  `status` varchar(20) NOT NULL DEFAULT 'queued',
  `message` varchar(500) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_collection_reminders_bill_id_foreign` (`bill_id`),
  KEY `acct_collection_reminders_ledger_id_foreign` (`ledger_id`),
  KEY `acct_collection_reminders_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `acct_collection_reminders_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `acct_bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_collection_reminders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_collection_reminders_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_companies`
--

DROP TABLE IF EXISTS `acct_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `data_path` varchar(255) DEFAULT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `mailing_name` varchar(255) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `fax` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `gst_registration_type` varchar(20) DEFAULT NULL,
  `tax_pricing` varchar(12) NOT NULL DEFAULT 'exclusive',
  `tax_rounding` varchar(12) NOT NULL DEFAULT 'paisa',
  `allow_negative_stock` tinyint(1) NOT NULL DEFAULT 0,
  `pan` varchar(10) DEFAULT NULL,
  `sales_terms` text DEFAULT NULL,
  `purchase_terms` text DEFAULT NULL,
  `financial_year_start` date NOT NULL,
  `financial_year_end` date NOT NULL,
  `books_beginning_from` date DEFAULT NULL,
  `accounting_basis` varchar(20) NOT NULL DEFAULT 'accrual',
  `use_cost_centres` tinyint(1) NOT NULL DEFAULT 1,
  `use_bill_wise` tinyint(1) NOT NULL DEFAULT 1,
  `maintain_inventory` tinyint(1) NOT NULL DEFAULT 1,
  `integrate_accounts_with_inventory` tinyint(1) NOT NULL DEFAULT 1,
  `cost_centres_for_inventory` tinyint(1) NOT NULL DEFAULT 0,
  `enable_gst` tinyint(1) NOT NULL DEFAULT 1,
  `enable_tds` tinyint(1) NOT NULL DEFAULT 0,
  `enable_tcs` tinyint(1) NOT NULL DEFAULT 0,
  `lock_closed_financial_year` tinyint(1) NOT NULL DEFAULT 1,
  `default_credit_days` smallint(5) unsigned DEFAULT NULL,
  `inventory_valuation_method` varchar(40) NOT NULL DEFAULT 'weighted_average',
  `retained_earnings_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `currency_symbol` varchar(8) NOT NULL DEFAULT '₹',
  `currency_formal_name` varchar(20) NOT NULL DEFAULT 'INR',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_advance_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `bad_debt_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `doubtful_debt_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `retention_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `commission_expense_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `commission_payable_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `round_off_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `collection_reminder_days_before` smallint(5) unsigned DEFAULT NULL,
  `collection_reminder_days_after` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_companies_gstin_unique` (`gstin`),
  KEY `acct_companies_name_index` (`name`),
  KEY `acct_companies_is_active_index` (`is_active`),
  KEY `acct_companies_retained_earnings_ledger_id_foreign` (`retained_earnings_ledger_id`),
  KEY `acct_companies_customer_advance_ledger_id_foreign` (`customer_advance_ledger_id`),
  KEY `acct_companies_bad_debt_ledger_id_foreign` (`bad_debt_ledger_id`),
  KEY `acct_companies_doubtful_debt_ledger_id_foreign` (`doubtful_debt_ledger_id`),
  KEY `acct_companies_retention_ledger_id_foreign` (`retention_ledger_id`),
  KEY `acct_companies_commission_expense_ledger_id_foreign` (`commission_expense_ledger_id`),
  KEY `acct_companies_commission_payable_ledger_id_foreign` (`commission_payable_ledger_id`),
  KEY `acct_companies_round_off_ledger_id_foreign` (`round_off_ledger_id`),
  CONSTRAINT `acct_companies_bad_debt_ledger_id_foreign` FOREIGN KEY (`bad_debt_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_companies_commission_expense_ledger_id_foreign` FOREIGN KEY (`commission_expense_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_companies_commission_payable_ledger_id_foreign` FOREIGN KEY (`commission_payable_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_companies_customer_advance_ledger_id_foreign` FOREIGN KEY (`customer_advance_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_companies_doubtful_debt_ledger_id_foreign` FOREIGN KEY (`doubtful_debt_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_companies_retained_earnings_ledger_id_foreign` FOREIGN KEY (`retained_earnings_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_companies_retention_ledger_id_foreign` FOREIGN KEY (`retention_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_companies_round_off_ledger_id_foreign` FOREIGN KEY (`round_off_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_consolidation_maps`
--

DROP TABLE IF EXISTS `acct_consolidation_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_consolidation_maps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_company_id` bigint(20) unsigned NOT NULL,
  `subsidiary_company_id` bigint(20) unsigned NOT NULL,
  `ownership_percent` decimal(8,4) NOT NULL DEFAULT 100.0000,
  `reporting_currency` varchar(3) NOT NULL DEFAULT 'INR',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_consol_map_uq` (`parent_company_id`,`subsidiary_company_id`),
  KEY `acct_consolidation_maps_subsidiary_company_id_foreign` (`subsidiary_company_id`),
  CONSTRAINT `acct_consolidation_maps_parent_company_id_foreign` FOREIGN KEY (`parent_company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_consolidation_maps_subsidiary_company_id_foreign` FOREIGN KEY (`subsidiary_company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_contingent_liabilities`
--

DROP TABLE IF EXISTS `acct_contingent_liabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_contingent_liabilities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(40) NOT NULL DEFAULT 'guarantee',
  `description` varchar(255) NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `as_of_date` date NOT NULL,
  `counterparty` varchar(160) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_contingent_liabilities_company_id_foreign` (`company_id`),
  CONSTRAINT `acct_contingent_liabilities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_cost_categories`
--

DROP TABLE IF EXISTS `acct_cost_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_cost_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_cost_categories_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_cost_categories_company_id_code_unique` (`company_id`,`code`),
  CONSTRAINT `acct_cost_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_cost_centres`
--

DROP TABLE IF EXISTS `acct_cost_centres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_cost_centres` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `cost_category_id` bigint(20) unsigned DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_cost_centres_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_cost_centres_company_id_code_unique` (`company_id`,`code`),
  KEY `acct_cost_centres_cost_category_id_foreign` (`cost_category_id`),
  KEY `acct_cost_centres_company_id_cost_category_id_index` (`company_id`,`cost_category_id`),
  KEY `acct_cost_centres_parent_id_foreign` (`parent_id`),
  CONSTRAINT `acct_cost_centres_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_cost_centres_cost_category_id_foreign` FOREIGN KEY (`cost_category_id`) REFERENCES `acct_cost_categories` (`id`),
  CONSTRAINT `acct_cost_centres_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `acct_cost_centres` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_currencies`
--

DROP TABLE IF EXISTS `acct_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(8) NOT NULL,
  `symbol` varchar(8) NOT NULL,
  `decimal_places` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `exchange_rate` decimal(15,6) NOT NULL DEFAULT 1.000000,
  `is_base` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_currencies_company_id_code_unique` (`company_id`,`code`),
  CONSTRAINT `acct_currencies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_custom_fields`
--

DROP TABLE IF EXISTS `acct_custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_custom_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(40) NOT NULL,
  `field_key` varchar(60) NOT NULL,
  `label` varchar(120) NOT NULL,
  `field_type` varchar(20) NOT NULL DEFAULT 'text',
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_custom_field_uq` (`company_id`,`entity_type`,`field_key`),
  CONSTRAINT `acct_custom_fields_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_data_backups`
--

DROP TABLE IF EXISTS `acct_data_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_data_backups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `disk` varchar(32) NOT NULL DEFAULT 'local',
  `path` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `size_bytes` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `table_counts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`table_counts`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_data_backups_status_created_at_index` (`status`,`created_at`),
  KEY `acct_data_backups_user_id_index` (`user_id`),
  CONSTRAINT `acct_data_backups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_data_restores`
--

DROP TABLE IF EXISTS `acct_data_restores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_data_restores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `data_backup_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `safety_backup_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `confirmation` varchar(40) NOT NULL,
  `table_counts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`table_counts`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_data_restores_data_backup_id_foreign` (`data_backup_id`),
  KEY `acct_data_restores_user_id_foreign` (`user_id`),
  KEY `acct_data_restores_safety_backup_id_foreign` (`safety_backup_id`),
  KEY `acct_data_restores_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `acct_data_restores_data_backup_id_foreign` FOREIGN KEY (`data_backup_id`) REFERENCES `acct_data_backups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_data_restores_safety_backup_id_foreign` FOREIGN KEY (`safety_backup_id`) REFERENCES `acct_data_backups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_data_restores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_deduction_sections`
--

DROP TABLE IF EXISTS `acct_deduction_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_deduction_sections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(8) NOT NULL,
  `name` varchar(255) NOT NULL,
  `section_code` varchar(20) NOT NULL,
  `rate` decimal(8,4) NOT NULL,
  `threshold_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `party_role` varchar(12) NOT NULL DEFAULT 'any',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_deduction_sections_company_id_section_code_unique` (`company_id`,`section_code`),
  KEY `acct_deduction_sections_company_id_kind_is_active_index` (`company_id`,`kind`,`is_active`),
  CONSTRAINT `acct_deduction_sections_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_deferred_revenue_schedules`
--

DROP TABLE IF EXISTS `acct_deferred_revenue_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_deferred_revenue_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `customer_ledger_id` bigint(20) unsigned NOT NULL,
  `revenue_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `deferred_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `total_amount` decimal(18,2) NOT NULL,
  `recognized_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `frequency` varchar(20) NOT NULL DEFAULT 'monthly',
  `next_recognize_on` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_deferred_revenue_schedules_company_id_foreign` (`company_id`),
  KEY `acct_deferred_revenue_schedules_invoice_id_foreign` (`invoice_id`),
  KEY `acct_deferred_revenue_schedules_customer_ledger_id_foreign` (`customer_ledger_id`),
  KEY `acct_deferred_revenue_schedules_revenue_ledger_id_foreign` (`revenue_ledger_id`),
  KEY `acct_deferred_revenue_schedules_deferred_ledger_id_foreign` (`deferred_ledger_id`),
  CONSTRAINT `acct_deferred_revenue_schedules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_deferred_revenue_schedules_customer_ledger_id_foreign` FOREIGN KEY (`customer_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_deferred_revenue_schedules_deferred_ledger_id_foreign` FOREIGN KEY (`deferred_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_deferred_revenue_schedules_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_deferred_revenue_schedules_revenue_ledger_id_foreign` FOREIGN KEY (`revenue_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_departments`
--

DROP TABLE IF EXISTS `acct_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_departments_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `acct_departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_deposit_slip_lines`
--

DROP TABLE IF EXISTS `acct_deposit_slip_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_deposit_slip_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deposit_slip_id` bigint(20) unsigned NOT NULL,
  `bank_instrument_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deposit_slip_lines_unique` (`deposit_slip_id`,`bank_instrument_id`),
  KEY `acct_deposit_slip_lines_bank_instrument_id_foreign` (`bank_instrument_id`),
  CONSTRAINT `acct_deposit_slip_lines_bank_instrument_id_foreign` FOREIGN KEY (`bank_instrument_id`) REFERENCES `acct_bank_instruments` (`id`),
  CONSTRAINT `acct_deposit_slip_lines_deposit_slip_id_foreign` FOREIGN KEY (`deposit_slip_id`) REFERENCES `acct_deposit_slips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_deposit_slips`
--

DROP TABLE IF EXISTS `acct_deposit_slips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_deposit_slips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `bank_account_id` bigint(20) unsigned NOT NULL,
  `slip_number` varchar(30) NOT NULL,
  `slip_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deposit_slips_number_unique` (`company_id`,`slip_number`),
  KEY `acct_deposit_slips_branch_id_foreign` (`branch_id`),
  KEY `acct_deposit_slips_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_deposit_slips_bank_account_id_foreign` (`bank_account_id`),
  CONSTRAINT `acct_deposit_slips_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`),
  CONSTRAINT `acct_deposit_slips_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_deposit_slips_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_deposit_slips_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_depreciation_entries`
--

DROP TABLE IF EXISTS `acct_depreciation_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_depreciation_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `fixed_asset_id` bigint(20) unsigned NOT NULL,
  `period` varchar(7) NOT NULL,
  `entry_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `method` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_depr_asset_period_uq` (`fixed_asset_id`,`period`),
  KEY `acct_depreciation_entries_company_id_foreign` (`company_id`),
  KEY `acct_depreciation_entries_voucher_id_foreign` (`voucher_id`),
  CONSTRAINT `acct_depreciation_entries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_depreciation_entries_fixed_asset_id_foreign` FOREIGN KEY (`fixed_asset_id`) REFERENCES `acct_fixed_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_depreciation_entries_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_duplicate_bill_checks`
--

DROP TABLE IF EXISTS `acct_duplicate_bill_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_duplicate_bill_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `supplier_ledger_id` bigint(20) unsigned NOT NULL,
  `bill_number` varchar(80) NOT NULL,
  `bill_date` date DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `match_reason` varchar(120) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'flagged',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_duplicate_bill_checks_supplier_ledger_id_foreign` (`supplier_ledger_id`),
  KEY `acct_duplicate_bill_checks_invoice_id_foreign` (`invoice_id`),
  KEY `acct_dup_bill_lookup_idx` (`company_id`,`supplier_ledger_id`,`bill_number`),
  CONSTRAINT `acct_duplicate_bill_checks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_duplicate_bill_checks_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_duplicate_bill_checks_supplier_ledger_id_foreign` FOREIGN KEY (`supplier_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_electronic_payments`
--

DROP TABLE IF EXISTS `acct_electronic_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_electronic_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `payment_batch_id` bigint(20) unsigned DEFAULT NULL,
  `channel` varchar(20) NOT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `utr` varchar(80) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `beneficiary` varchar(160) DEFAULT NULL,
  `beneficiary_account` varchar(40) DEFAULT NULL,
  `beneficiary_ifsc` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'initiated',
  `cleared_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_electronic_payments_bank_account_id_foreign` (`bank_account_id`),
  KEY `acct_electronic_payments_voucher_id_foreign` (`voucher_id`),
  KEY `acct_electronic_payments_company_id_channel_status_index` (`company_id`,`channel`,`status`),
  KEY `acct_electronic_payments_payment_batch_id_foreign` (`payment_batch_id`),
  CONSTRAINT `acct_electronic_payments_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_electronic_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_electronic_payments_payment_batch_id_foreign` FOREIGN KEY (`payment_batch_id`) REFERENCES `acct_payment_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_electronic_payments_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_employee_advances`
--

DROP TABLE IF EXISTS `acct_employee_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_employee_advances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `employee_ledger_id` bigint(20) unsigned NOT NULL,
  `advance_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `settled_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `purpose` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `payment_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_employee_advances_company_id_foreign` (`company_id`),
  KEY `acct_employee_advances_employee_id_foreign` (`employee_id`),
  KEY `acct_employee_advances_employee_ledger_id_foreign` (`employee_ledger_id`),
  KEY `acct_employee_advances_payment_voucher_id_foreign` (`payment_voucher_id`),
  CONSTRAINT `acct_employee_advances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_employee_advances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `acct_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_employee_advances_employee_ledger_id_foreign` FOREIGN KEY (`employee_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_employee_advances_payment_voucher_id_foreign` FOREIGN KEY (`payment_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_employee_loans`
--

DROP TABLE IF EXISTS `acct_employee_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_employee_loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `loan_date` date NOT NULL,
  `principal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `tenure_months` smallint(5) unsigned NOT NULL DEFAULT 12,
  `emi_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `outstanding` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_employee_loans_company_id_foreign` (`company_id`),
  KEY `acct_employee_loans_employee_id_foreign` (`employee_id`),
  CONSTRAINT `acct_employee_loans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_employee_loans_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `acct_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_employees`
--

DROP TABLE IF EXISTS `acct_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `employee_code` varchar(20) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `group_name` varchar(80) DEFAULT NULL,
  `pan` varchar(10) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `ledger_id` bigint(20) unsigned DEFAULT NULL,
  `monthly_earnings` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monthly_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `salary_structure_id` bigint(20) unsigned DEFAULT NULL,
  `basic_salary` decimal(18,2) DEFAULT NULL,
  `uan` varchar(20) DEFAULT NULL,
  `esi_number` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_employees_company_id_employee_code_unique` (`company_id`,`employee_code`),
  KEY `acct_employees_branch_id_foreign` (`branch_id`),
  KEY `acct_employees_ledger_id_foreign` (`ledger_id`),
  KEY `acct_employees_salary_structure_id_foreign` (`salary_structure_id`),
  CONSTRAINT `acct_employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_employees_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_employees_salary_structure_id_foreign` FOREIGN KEY (`salary_structure_id`) REFERENCES `acct_salary_structures` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_equity_transactions`
--

DROP TABLE IF EXISTS `acct_equity_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_equity_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(40) NOT NULL,
  `txn_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `ledger_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_equity_transactions_company_id_foreign` (`company_id`),
  KEY `acct_equity_transactions_ledger_id_foreign` (`ledger_id`),
  KEY `acct_equity_transactions_voucher_id_foreign` (`voucher_id`),
  CONSTRAINT `acct_equity_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_equity_transactions_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_equity_transactions_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_exchange_rates`
--

DROP TABLE IF EXISTS `acct_exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_exchange_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `currency_id` bigint(20) unsigned NOT NULL,
  `rate_date` date NOT NULL,
  `rate` decimal(18,6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_exchange_rates_company_id_currency_id_rate_date_unique` (`company_id`,`currency_id`,`rate_date`),
  KEY `acct_exchange_rates_currency_id_foreign` (`currency_id`),
  CONSTRAINT `acct_exchange_rates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_exchange_rates_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `acct_currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_expense_claim_lines`
--

DROP TABLE IF EXISTS `acct_expense_claim_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_expense_claim_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expense_claim_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL DEFAULT 1,
  `expense_date` date DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `expense_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bill_attached` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_expense_claim_lines_expense_claim_id_foreign` (`expense_claim_id`),
  KEY `acct_expense_claim_lines_expense_ledger_id_foreign` (`expense_ledger_id`),
  CONSTRAINT `acct_expense_claim_lines_expense_claim_id_foreign` FOREIGN KEY (`expense_claim_id`) REFERENCES `acct_expense_claims` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_expense_claim_lines_expense_ledger_id_foreign` FOREIGN KEY (`expense_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_expense_claims`
--

DROP TABLE IF EXISTS `acct_expense_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_expense_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `number` varchar(40) NOT NULL,
  `claim_date` date NOT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `employee_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_reimbursement` tinyint(1) NOT NULL DEFAULT 1,
  `policy_flag` varchar(40) DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `payment_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `accrual_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_expense_claims_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_expense_claims_branch_id_foreign` (`branch_id`),
  KEY `acct_expense_claims_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_expense_claims_employee_id_foreign` (`employee_id`),
  KEY `acct_expense_claims_employee_ledger_id_foreign` (`employee_ledger_id`),
  KEY `acct_expense_claims_payment_voucher_id_foreign` (`payment_voucher_id`),
  KEY `acct_expense_claims_created_by_foreign` (`created_by`),
  KEY `acct_expense_claims_accrual_voucher_id_foreign` (`accrual_voucher_id`),
  KEY `acct_expense_claims_approved_by_foreign` (`approved_by`),
  CONSTRAINT `acct_expense_claims_accrual_voucher_id_foreign` FOREIGN KEY (`accrual_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_expense_claims_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_expense_claims_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_expense_claims_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_expense_claims_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_expense_claims_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `acct_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_expense_claims_employee_ledger_id_foreign` FOREIGN KEY (`employee_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_expense_claims_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_expense_claims_payment_voucher_id_foreign` FOREIGN KEY (`payment_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_financial_years`
--

DROP TABLE IF EXISTS `acct_financial_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_financial_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(40) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closing_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `opening_profit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_financial_years_company_id_name_unique` (`company_id`,`name`),
  KEY `acct_financial_years_company_id_start_date_end_date_index` (`company_id`,`start_date`,`end_date`),
  KEY `acct_financial_years_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `acct_financial_years_closed_by_foreign` (`closed_by`),
  KEY `acct_financial_years_closing_voucher_id_foreign` (`closing_voucher_id`),
  CONSTRAINT `acct_financial_years_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_financial_years_closing_voucher_id_foreign` FOREIGN KEY (`closing_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_financial_years_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_fixed_asset_categories`
--

DROP TABLE IF EXISTS `acct_fixed_asset_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_fixed_asset_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `depreciation_method` varchar(40) NOT NULL DEFAULT 'straight_line',
  `useful_life_years` decimal(8,2) NOT NULL DEFAULT 5.00,
  `residual_percent` decimal(8,2) NOT NULL DEFAULT 5.00,
  `asset_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `accum_depr_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `depr_expense_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_fixed_asset_categories_company_id_name_unique` (`company_id`,`name`),
  KEY `acct_fixed_asset_categories_asset_ledger_id_foreign` (`asset_ledger_id`),
  KEY `acct_fixed_asset_categories_accum_depr_ledger_id_foreign` (`accum_depr_ledger_id`),
  KEY `acct_fixed_asset_categories_depr_expense_ledger_id_foreign` (`depr_expense_ledger_id`),
  CONSTRAINT `acct_fixed_asset_categories_accum_depr_ledger_id_foreign` FOREIGN KEY (`accum_depr_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_fixed_asset_categories_asset_ledger_id_foreign` FOREIGN KEY (`asset_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_fixed_asset_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_fixed_asset_categories_depr_expense_ledger_id_foreign` FOREIGN KEY (`depr_expense_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_fixed_assets`
--

DROP TABLE IF EXISTS `acct_fixed_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_fixed_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `asset_code` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `acquisition_date` date NOT NULL,
  `capitalization_date` date DEFAULT NULL,
  `gross_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `residual_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `accumulated_depreciation` decimal(18,2) NOT NULL DEFAULT 0.00,
  `book_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `location` varchar(120) DEFAULT NULL,
  `department` varchar(120) DEFAULT NULL,
  `custodian` varchar(120) DEFAULT NULL,
  `is_cip` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `disposal_date` date DEFAULT NULL,
  `disposal_proceeds` decimal(18,2) DEFAULT NULL,
  `disposal_gain_loss` decimal(18,2) DEFAULT NULL,
  `parent_asset_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_fixed_assets_company_id_asset_code_unique` (`company_id`,`asset_code`),
  KEY `acct_fixed_assets_category_id_foreign` (`category_id`),
  KEY `acct_fixed_assets_parent_asset_id_foreign` (`parent_asset_id`),
  CONSTRAINT `acct_fixed_assets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `acct_fixed_asset_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_fixed_assets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_fixed_assets_parent_asset_id_foreign` FOREIGN KEY (`parent_asset_id`) REFERENCES `acct_fixed_assets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_gateway_settlements`
--

DROP TABLE IF EXISTS `acct_gateway_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_gateway_settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `merchant_profile_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `reference` varchar(40) NOT NULL,
  `settlement_date` date NOT NULL,
  `gross_amount` decimal(15,2) NOT NULL,
  `charges` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gateway_settlements_reference_unique` (`company_id`,`reference`),
  KEY `acct_gateway_settlements_branch_id_foreign` (`branch_id`),
  KEY `acct_gateway_settlements_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_gateway_settlements_merchant_profile_id_foreign` (`merchant_profile_id`),
  KEY `acct_gateway_settlements_bank_account_id_foreign` (`bank_account_id`),
  KEY `acct_gateway_settlements_voucher_id_foreign` (`voucher_id`),
  CONSTRAINT `acct_gateway_settlements_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_gateway_settlements_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_gateway_settlements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_gateway_settlements_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_gateway_settlements_merchant_profile_id_foreign` FOREIGN KEY (`merchant_profile_id`) REFERENCES `acct_merchant_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_gateway_settlements_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_godowns`
--

DROP TABLE IF EXISTS `acct_godowns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_godowns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_godowns_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_godowns_company_id_code_unique` (`company_id`,`code`),
  KEY `acct_godowns_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `acct_godowns_parent_id_foreign` (`parent_id`),
  CONSTRAINT `acct_godowns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_godowns_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `acct_godowns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_goods_in_transit`
--

DROP TABLE IF EXISTS `acct_goods_in_transit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_goods_in_transit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `from_godown_id` bigint(20) unsigned DEFAULT NULL,
  `to_godown_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `dispatch_date` date NOT NULL,
  `expected_receipt_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'in_transit',
  `reference` varchar(80) DEFAULT NULL,
  `is_consignment` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_goods_in_transit_company_id_foreign` (`company_id`),
  KEY `acct_goods_in_transit_from_godown_id_foreign` (`from_godown_id`),
  KEY `acct_goods_in_transit_to_godown_id_foreign` (`to_godown_id`),
  KEY `acct_goods_in_transit_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_goods_in_transit_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_goods_in_transit_from_godown_id_foreign` FOREIGN KEY (`from_godown_id`) REFERENCES `acct_godowns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_goods_in_transit_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_goods_in_transit_to_godown_id_foreign` FOREIGN KEY (`to_godown_id`) REFERENCES `acct_godowns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_goods_receipt_lines`
--

DROP TABLE IF EXISTS `acct_goods_receipt_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_goods_receipt_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL DEFAULT 1,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `ordered_qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `received_qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `rate` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_goods_receipt_lines_goods_receipt_id_foreign` (`goods_receipt_id`),
  KEY `acct_goods_receipt_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_goods_receipt_lines_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `acct_goods_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_goods_receipt_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_goods_receipts`
--

DROP TABLE IF EXISTS `acct_goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_goods_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `number` varchar(40) NOT NULL,
  `receipt_date` date NOT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_ledger_id` bigint(20) unsigned NOT NULL,
  `godown_id` bigint(20) unsigned DEFAULT NULL,
  `stock_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `receipt_kind` varchar(20) NOT NULL DEFAULT 'goods',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `is_invoiced` tinyint(1) NOT NULL DEFAULT 0,
  `purchase_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_goods_receipts_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_goods_receipts_branch_id_foreign` (`branch_id`),
  KEY `acct_goods_receipts_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_goods_receipts_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `acct_goods_receipts_supplier_ledger_id_foreign` (`supplier_ledger_id`),
  KEY `acct_goods_receipts_godown_id_foreign` (`godown_id`),
  KEY `acct_goods_receipts_purchase_invoice_id_foreign` (`purchase_invoice_id`),
  KEY `acct_goods_receipts_created_by_foreign` (`created_by`),
  KEY `acct_goods_receipts_stock_transaction_id_foreign` (`stock_transaction_id`),
  CONSTRAINT `acct_goods_receipts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_goods_receipts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_goods_receipts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_goods_receipts_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_goods_receipts_godown_id_foreign` FOREIGN KEY (`godown_id`) REFERENCES `acct_godowns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_goods_receipts_purchase_invoice_id_foreign` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_goods_receipts_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `acct_purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_goods_receipts_stock_transaction_id_foreign` FOREIGN KEY (`stock_transaction_id`) REFERENCES `acct_stock_transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_goods_receipts_supplier_ledger_id_foreign` FOREIGN KEY (`supplier_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_gst_challans`
--

DROP TABLE IF EXISTS `acct_gst_challans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_gst_challans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `return_period` varchar(7) NOT NULL,
  `challan_number` varchar(40) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `cgst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `sgst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `igst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cess` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest` decimal(18,2) NOT NULL DEFAULT 0.00,
  `penalty` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `payment_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'paid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_gst_challans_company_id_foreign` (`company_id`),
  KEY `acct_gst_challans_payment_voucher_id_foreign` (`payment_voucher_id`),
  CONSTRAINT `acct_gst_challans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_gst_challans_payment_voucher_id_foreign` FOREIGN KEY (`payment_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_gst_registrations`
--

DROP TABLE IF EXISTS `acct_gst_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_gst_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `gstin` varchar(15) NOT NULL,
  `registration_type` varchar(20) NOT NULL,
  `state` varchar(255) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_gst_registrations_company_id_gstin_unique` (`company_id`,`gstin`),
  KEY `acct_gst_registrations_branch_id_foreign` (`branch_id`),
  KEY `acct_gst_registrations_location_id_foreign` (`location_id`),
  CONSTRAINT `acct_gst_registrations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_gst_registrations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_gst_registrations_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `acct_locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_gstr2b_rows`
--

DROP TABLE IF EXISTS `acct_gstr2b_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_gstr2b_rows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `return_period` varchar(7) NOT NULL,
  `supplier_gstin` varchar(15) DEFAULT NULL,
  `supplier_name` varchar(160) DEFAULT NULL,
  `invoice_number` varchar(80) NOT NULL,
  `invoice_date` date DEFAULT NULL,
  `taxable_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `igst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cgst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `sgst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cess` decimal(18,2) NOT NULL DEFAULT 0.00,
  `matched_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `match_status` varchar(20) NOT NULL DEFAULT 'unmatched',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_gstr2b_rows_matched_invoice_id_foreign` (`matched_invoice_id`),
  KEY `acct_gstr2b_period_idx` (`company_id`,`return_period`,`match_status`),
  CONSTRAINT `acct_gstr2b_rows_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_gstr2b_rows_matched_invoice_id_foreign` FOREIGN KEY (`matched_invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_hsn_sacs`
--

DROP TABLE IF EXISTS `acct_hsn_sacs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_hsn_sacs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `tax_rate_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(16) NOT NULL,
  `kind` varchar(8) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_hsn_sacs_company_id_code_unique` (`company_id`,`code`),
  KEY `acct_hsn_sacs_tax_rate_id_foreign` (`tax_rate_id`),
  KEY `acct_hsn_sacs_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `acct_hsn_sacs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_hsn_sacs_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `acct_tax_rates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_idempotency_keys`
--

DROP TABLE IF EXISTS `acct_idempotency_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_idempotency_keys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `key` varchar(80) NOT NULL,
  `request_fingerprint` varchar(64) NOT NULL,
  `status_code` smallint(5) unsigned NOT NULL,
  `response_body` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_idempotency_keys_user_id_key_unique` (`user_id`,`key`),
  CONSTRAINT `acct_idempotency_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_industry_contracts`
--

DROP TABLE IF EXISTS `acct_industry_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_industry_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `industry` varchar(40) NOT NULL,
  `contract_number` varchar(40) NOT NULL,
  `name` varchar(160) NOT NULL,
  `party_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `contract_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `retention_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `recognized_revenue` decimal(18,2) NOT NULL DEFAULT 0.00,
  `billing_frequency` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_industry_contracts_company_id_contract_number_unique` (`company_id`,`contract_number`),
  KEY `acct_industry_contracts_party_ledger_id_foreign` (`party_ledger_id`),
  CONSTRAINT `acct_industry_contracts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_industry_contracts_party_ledger_id_foreign` FOREIGN KEY (`party_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_integration_endpoints`
--

DROP TABLE IF EXISTS `acct_integration_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_integration_endpoints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `kind` varchar(40) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `auth_header` varchar(500) DEFAULT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `last_error` varchar(500) DEFAULT NULL,
  `retry_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_integration_endpoints_company_id_foreign` (`company_id`),
  KEY `acct_integration_endpoints_bank_account_id_foreign` (`bank_account_id`),
  CONSTRAINT `acct_integration_endpoints_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_integration_endpoints_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_integration_references`
--

DROP TABLE IF EXISTS `acct_integration_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_integration_references` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `referenceable_type` varchar(255) NOT NULL,
  `referenceable_id` bigint(20) unsigned NOT NULL,
  `source` varchar(80) NOT NULL,
  `external_reference_id` varchar(255) NOT NULL,
  `sync_status` varchar(20) NOT NULL DEFAULT 'synced',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `integration_reference_unique` (`company_id`,`source`,`referenceable_type`,`external_reference_id`),
  KEY `acct_int_ref_morph` (`referenceable_type`,`referenceable_id`),
  CONSTRAINT `acct_integration_references_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_integration_syncs`
--

DROP TABLE IF EXISTS `acct_integration_syncs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_integration_syncs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `integration_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `integration_syncs_status_index` (`integration_id`,`status`,`created_at`),
  CONSTRAINT `acct_integration_syncs_integration_id_foreign` FOREIGN KEY (`integration_id`) REFERENCES `acct_integrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_integrations`
--

DROP TABLE IF EXISTS `acct_integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_integrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `webhook_endpoint_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(40) NOT NULL,
  `direction` varchar(20) NOT NULL,
  `external_system` varchar(80) DEFAULT NULL,
  `credentials` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_status` varchar(20) DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_integrations_webhook_endpoint_id_foreign` (`webhook_endpoint_id`),
  KEY `acct_integrations_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `acct_integrations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_integrations_webhook_endpoint_id_foreign` FOREIGN KEY (`webhook_endpoint_id`) REFERENCES `acct_webhook_endpoints` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_interest_charges`
--

DROP TABLE IF EXISTS `acct_interest_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_interest_charges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bill_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `voucher_id` bigint(20) unsigned NOT NULL,
  `as_on` date NOT NULL,
  `kind` varchar(20) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_interest_charges_bill_id_as_on_unique` (`bill_id`,`as_on`),
  KEY `acct_interest_charges_company_id_foreign` (`company_id`),
  KEY `acct_interest_charges_ledger_id_foreign` (`ledger_id`),
  KEY `acct_interest_charges_voucher_id_foreign` (`voucher_id`),
  CONSTRAINT `acct_interest_charges_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `acct_bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_interest_charges_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_interest_charges_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_interest_charges_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_inventory_impairments`
--

DROP TABLE IF EXISTS `acct_inventory_impairments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_inventory_impairments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `entry_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `expense_ledger_id` bigint(20) unsigned NOT NULL,
  `provision_ledger_id` bigint(20) unsigned NOT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_inventory_impairments_branch_id_foreign` (`branch_id`),
  KEY `acct_inventory_impairments_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_inventory_impairments_product_id_foreign` (`product_id`),
  KEY `acct_inventory_impairments_expense_ledger_id_foreign` (`expense_ledger_id`),
  KEY `acct_inventory_impairments_provision_ledger_id_foreign` (`provision_ledger_id`),
  KEY `acct_inventory_impairments_voucher_id_foreign` (`voucher_id`),
  KEY `acct_inventory_impairments_created_by_foreign` (`created_by`),
  KEY `acct_inv_imp_co_date` (`company_id`,`entry_date`),
  CONSTRAINT `acct_inventory_impairments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_inventory_impairments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_inventory_impairments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_inventory_impairments_expense_ledger_id_foreign` FOREIGN KEY (`expense_ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_inventory_impairments_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_inventory_impairments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_inventory_impairments_provision_ledger_id_foreign` FOREIGN KEY (`provision_ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_inventory_impairments_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_invoice_lines`
--

DROP TABLE IF EXISTS `acct_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `godown_id` bigint(20) unsigned DEFAULT NULL,
  `batch_number` varchar(40) DEFAULT NULL,
  `manufactured_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `serial_number` varchar(60) DEFAULT NULL,
  `tax_rate_id` bigint(20) unsigned DEFAULT NULL,
  `hsn_sac_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cess_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_invoice_lines_invoice_id_line_number_unique` (`invoice_id`,`line_number`),
  KEY `acct_invoice_lines_product_id_foreign` (`product_id`),
  KEY `acct_invoice_lines_godown_id_foreign` (`godown_id`),
  KEY `acct_invoice_lines_tax_rate_id_foreign` (`tax_rate_id`),
  KEY `acct_invoice_lines_hsn_sac_id_foreign` (`hsn_sac_id`),
  CONSTRAINT `acct_invoice_lines_godown_id_foreign` FOREIGN KEY (`godown_id`) REFERENCES `acct_godowns` (`id`),
  CONSTRAINT `acct_invoice_lines_hsn_sac_id_foreign` FOREIGN KEY (`hsn_sac_id`) REFERENCES `acct_hsn_sacs` (`id`),
  CONSTRAINT `acct_invoice_lines_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_invoice_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`),
  CONSTRAINT `acct_invoice_lines_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `acct_tax_rates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_invoices`
--

DROP TABLE IF EXISTS `acct_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(20) NOT NULL,
  `invoice_category` varchar(30) NOT NULL DEFAULT 'tax_invoice',
  `billing_mode` varchar(20) NOT NULL DEFAULT 'goods',
  `invoice_number` varchar(30) NOT NULL,
  `bill_name` varchar(40) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `party_ledger_id` bigint(20) unsigned NOT NULL,
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `account_ledger_id` bigint(20) unsigned NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `narration` varchar(1000) DEFAULT NULL,
  `supply_type` varchar(10) DEFAULT NULL,
  `place_of_supply` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `ims_status` varchar(20) NOT NULL DEFAULT 'pending',
  `subtotal` decimal(15,2) NOT NULL,
  `discount_total` decimal(15,2) NOT NULL,
  `tax_total` decimal(15,2) NOT NULL,
  `grand_total` decimal(15,2) NOT NULL,
  `round_off` decimal(15,2) NOT NULL DEFAULT 0.00,
  `retention_percent` decimal(8,2) DEFAULT NULL,
  `retention_amount` decimal(18,2) DEFAULT NULL,
  `project_milestone_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `sales_order_id` bigint(20) unsigned DEFAULT NULL,
  `goods_receipt_id` bigint(20) unsigned DEFAULT NULL,
  `against_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `converted_from_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `gst_registration_id` bigint(20) unsigned DEFAULT NULL,
  `reverse_charge` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_number_unique` (`company_id`,`branch_id`,`financial_year_id`,`kind`,`invoice_number`),
  UNIQUE KEY `acct_invoices_voucher_id_unique` (`voucher_id`),
  KEY `acct_invoices_branch_id_foreign` (`branch_id`),
  KEY `acct_invoices_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_invoices_party_ledger_id_foreign` (`party_ledger_id`),
  KEY `acct_invoices_account_ledger_id_foreign` (`account_ledger_id`),
  KEY `acct_invoices_created_by_foreign` (`created_by`),
  KEY `invoices_context_date_index` (`company_id`,`branch_id`,`financial_year_id`,`invoice_date`),
  KEY `acct_invoices_company_id_kind_status_index` (`company_id`,`kind`,`status`),
  KEY `invoices_report_index` (`company_id`,`status`,`invoice_date`),
  KEY `acct_invoices_sales_order_id_foreign` (`sales_order_id`),
  KEY `acct_invoices_gst_registration_id_foreign` (`gst_registration_id`),
  KEY `acct_invoices_billing_address_id_foreign` (`billing_address_id`),
  KEY `acct_invoices_shipping_address_id_foreign` (`shipping_address_id`),
  KEY `acct_invoices_against_invoice_id_foreign` (`against_invoice_id`),
  KEY `acct_invoices_converted_from_invoice_id_foreign` (`converted_from_invoice_id`),
  KEY `acct_invoices_project_milestone_id_foreign` (`project_milestone_id`),
  KEY `acct_invoices_goods_receipt_id_foreign` (`goods_receipt_id`),
  CONSTRAINT `acct_invoices_account_ledger_id_foreign` FOREIGN KEY (`account_ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_invoices_against_invoice_id_foreign` FOREIGN KEY (`against_invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_billing_address_id_foreign` FOREIGN KEY (`billing_address_id`) REFERENCES `acct_party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_invoices_converted_from_invoice_id_foreign` FOREIGN KEY (`converted_from_invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_invoices_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `acct_goods_receipts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_gst_registration_id_foreign` FOREIGN KEY (`gst_registration_id`) REFERENCES `acct_gst_registrations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_party_ledger_id_foreign` FOREIGN KEY (`party_ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_invoices_project_milestone_id_foreign` FOREIGN KEY (`project_milestone_id`) REFERENCES `acct_project_milestones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `acct_sales_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `acct_party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_invoices_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_itc_entries`
--

DROP TABLE IF EXISTS `acct_itc_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_itc_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `return_period` varchar(7) DEFAULT NULL,
  `eligibility` varchar(40) NOT NULL DEFAULT 'eligible',
  `igst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cgst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `sgst` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cess` decimal(18,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_itc_entries_company_id_foreign` (`company_id`),
  KEY `acct_itc_entries_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `acct_itc_entries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_itc_entries_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_job_works`
--

DROP TABLE IF EXISTS `acct_job_works`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_job_works` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `number` varchar(40) NOT NULL,
  `issue_date` date NOT NULL,
  `vendor_ledger_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `issued_qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `received_qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `job_charges` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `narration` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_job_works_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_job_works_branch_id_foreign` (`branch_id`),
  KEY `acct_job_works_vendor_ledger_id_foreign` (`vendor_ledger_id`),
  KEY `acct_job_works_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_job_works_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_job_works_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_job_works_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_job_works_vendor_ledger_id_foreign` FOREIGN KEY (`vendor_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_keyboard_shortcuts`
--

DROP TABLE IF EXISTS `acct_keyboard_shortcuts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_keyboard_shortcuts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shortcut_id` varchar(255) NOT NULL,
  `keys` varchar(40) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_keyboard_shortcuts_shortcut_id_unique` (`shortcut_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_kpi_snapshots`
--

DROP TABLE IF EXISTS `acct_kpi_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_kpi_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `as_of_date` date NOT NULL,
  `dso` decimal(12,2) DEFAULT NULL,
  `dpo` decimal(12,2) DEFAULT NULL,
  `inventory_turnover` decimal(12,4) DEFAULT NULL,
  `cash_runway_days` decimal(12,2) DEFAULT NULL,
  `working_capital` decimal(18,2) DEFAULT NULL,
  `gross_margin_percent` decimal(8,4) DEFAULT NULL,
  `extras_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extras_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_kpi_snapshots_company_id_as_of_date_unique` (`company_id`,`as_of_date`),
  CONSTRAINT `acct_kpi_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_landed_cost_allocations`
--

DROP TABLE IF EXISTS `acct_landed_cost_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_landed_cost_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `landed_cost_voucher_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(200) DEFAULT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `base_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `allocated_charges` decimal(18,2) NOT NULL DEFAULT 0.00,
  `landed_unit_cost` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_landed_cost_allocations_landed_cost_voucher_id_foreign` (`landed_cost_voucher_id`),
  KEY `acct_landed_cost_allocations_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_landed_cost_allocations_landed_cost_voucher_id_foreign` FOREIGN KEY (`landed_cost_voucher_id`) REFERENCES `acct_landed_cost_vouchers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_landed_cost_allocations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_landed_cost_vouchers`
--

DROP TABLE IF EXISTS `acct_landed_cost_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_landed_cost_vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `number` varchar(40) NOT NULL,
  `voucher_date` date NOT NULL,
  `purchase_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `goods_receipt_id` bigint(20) unsigned DEFAULT NULL,
  `freight` decimal(18,2) NOT NULL DEFAULT 0.00,
  `duty` decimal(18,2) NOT NULL DEFAULT 0.00,
  `other_charges` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_charges` decimal(18,2) NOT NULL DEFAULT 0.00,
  `allocation_basis` varchar(40) NOT NULL DEFAULT 'value',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_landed_cost_vouchers_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_landed_cost_vouchers_branch_id_foreign` (`branch_id`),
  KEY `acct_landed_cost_vouchers_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_landed_cost_vouchers_purchase_invoice_id_foreign` (`purchase_invoice_id`),
  KEY `acct_landed_cost_vouchers_goods_receipt_id_foreign` (`goods_receipt_id`),
  KEY `acct_landed_cost_vouchers_voucher_id_foreign` (`voucher_id`),
  CONSTRAINT `acct_landed_cost_vouchers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_landed_cost_vouchers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_landed_cost_vouchers_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_landed_cost_vouchers_goods_receipt_id_foreign` FOREIGN KEY (`goods_receipt_id`) REFERENCES `acct_goods_receipts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_landed_cost_vouchers_purchase_invoice_id_foreign` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_landed_cost_vouchers_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_ledger_mirrors`
--

DROP TABLE IF EXISTS `acct_ledger_mirrors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_ledger_mirrors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_company_id` bigint(20) unsigned NOT NULL,
  `source_ledger_id` bigint(20) unsigned NOT NULL,
  `counterparty_company_id` bigint(20) unsigned NOT NULL,
  `counterparty_ledger_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_ledger_mirrors_src_cp_unique` (`source_ledger_id`,`counterparty_company_id`),
  KEY `acct_ledger_mirrors_source_company_id_foreign` (`source_company_id`),
  KEY `acct_ledger_mirrors_counterparty_company_id_foreign` (`counterparty_company_id`),
  KEY `acct_ledger_mirrors_counterparty_ledger_id_foreign` (`counterparty_ledger_id`),
  CONSTRAINT `acct_ledger_mirrors_counterparty_company_id_foreign` FOREIGN KEY (`counterparty_company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_ledger_mirrors_counterparty_ledger_id_foreign` FOREIGN KEY (`counterparty_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_ledger_mirrors_source_company_id_foreign` FOREIGN KEY (`source_company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_ledger_mirrors_source_ledger_id_foreign` FOREIGN KEY (`source_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_ledger_openings`
--

DROP TABLE IF EXISTS `acct_ledger_openings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_ledger_openings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_balance_type` varchar(6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_ledger_openings_company_id_foreign` (`company_id`),
  KEY `acct_ledger_openings_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_ledger_openings_branch_id_foreign` (`branch_id`),
  KEY `ledger_openings_lookup` (`ledger_id`,`financial_year_id`,`branch_id`),
  CONSTRAINT `acct_ledger_openings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_ledger_openings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_ledger_openings_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_ledger_openings_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_ledgers`
--

DROP TABLE IF EXISTS `acct_ledgers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_ledgers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_group_id` bigint(20) unsigned NOT NULL,
  `parent_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `mailing_name` varchar(255) DEFAULT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `code` varchar(32) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_balance_type` varchar(6) NOT NULL,
  `address` varchar(500) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `gst_registration_type` varchar(20) DEFAULT NULL,
  `pan` varchar(10) DEFAULT NULL,
  `credit_limit` decimal(15,2) DEFAULT NULL,
  `credit_days` smallint(5) unsigned DEFAULT NULL,
  `maintain_bill_wise` tinyint(1) NOT NULL DEFAULT 1,
  `cost_centres_applicable` tinyint(1) NOT NULL DEFAULT 0,
  `calculate_interest` tinyint(1) NOT NULL DEFAULT 0,
  `interest_rate` decimal(8,4) DEFAULT NULL,
  `interest_style` varchar(20) NOT NULL DEFAULT '365_day_year',
  `payment_term_id` bigint(20) unsigned DEFAULT NULL,
  `deduction_section_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_intercompany` tinyint(1) NOT NULL DEFAULT 0,
  `intercompany_company_id` bigint(20) unsigned DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_control_account` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cash_flow_class` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_ledgers_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_ledgers_company_id_code_unique` (`company_id`,`code`),
  UNIQUE KEY `acct_ledgers_company_id_gstin_unique` (`company_id`,`gstin`),
  KEY `acct_ledgers_account_group_id_foreign` (`account_group_id`),
  KEY `acct_ledgers_company_id_account_group_id_index` (`company_id`,`account_group_id`),
  KEY `acct_ledgers_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `acct_ledgers_deduction_section_id_foreign` (`deduction_section_id`),
  KEY `acct_ledgers_intercompany_company_id_foreign` (`intercompany_company_id`),
  KEY `acct_ledgers_parent_ledger_id_foreign` (`parent_ledger_id`),
  KEY `acct_ledgers_payment_term_id_foreign` (`payment_term_id`),
  CONSTRAINT `acct_ledgers_account_group_id_foreign` FOREIGN KEY (`account_group_id`) REFERENCES `acct_account_groups` (`id`),
  CONSTRAINT `acct_ledgers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_ledgers_deduction_section_id_foreign` FOREIGN KEY (`deduction_section_id`) REFERENCES `acct_deduction_sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_ledgers_intercompany_company_id_foreign` FOREIGN KEY (`intercompany_company_id`) REFERENCES `acct_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_ledgers_parent_ledger_id_foreign` FOREIGN KEY (`parent_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_ledgers_payment_term_id_foreign` FOREIGN KEY (`payment_term_id`) REFERENCES `acct_payment_terms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_loan_schedules`
--

DROP TABLE IF EXISTS `acct_loan_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_loan_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `installment_no` smallint(5) unsigned NOT NULL,
  `due_date` date NOT NULL,
  `principal_component` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest_component` decimal(18,2) NOT NULL DEFAULT 0.00,
  `emi_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `outstanding_after` decimal(18,2) NOT NULL DEFAULT 0.00,
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `paid_date` date DEFAULT NULL,
  `payment_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_loan_schedules_loan_id_installment_no_unique` (`loan_id`,`installment_no`),
  KEY `acct_loan_schedules_payment_voucher_id_foreign` (`payment_voucher_id`),
  CONSTRAINT `acct_loan_schedules_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `acct_loans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_loan_schedules_payment_voucher_id_foreign` FOREIGN KEY (`payment_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_loans`
--

DROP TABLE IF EXISTS `acct_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `loan_number` varchar(40) NOT NULL,
  `lender_name` varchar(160) NOT NULL,
  `loan_type` varchar(40) NOT NULL DEFAULT 'term',
  `is_long_term` tinyint(1) NOT NULL DEFAULT 1,
  `disbursement_date` date NOT NULL,
  `principal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `tenure_months` smallint(5) unsigned NOT NULL DEFAULT 12,
  `emi_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `outstanding_principal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `loan_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `interest_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `overdraft_limit` decimal(18,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `disbursement_voucher_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_loans_company_id_loan_number_unique` (`company_id`,`loan_number`),
  KEY `acct_loans_loan_ledger_id_foreign` (`loan_ledger_id`),
  KEY `acct_loans_interest_ledger_id_foreign` (`interest_ledger_id`),
  KEY `acct_loans_bank_account_id_foreign` (`bank_account_id`),
  KEY `acct_loans_disbursement_voucher_id_foreign` (`disbursement_voucher_id`),
  CONSTRAINT `acct_loans_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_loans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_loans_disbursement_voucher_id_foreign` FOREIGN KEY (`disbursement_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_loans_interest_ledger_id_foreign` FOREIGN KEY (`interest_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_loans_loan_ledger_id_foreign` FOREIGN KEY (`loan_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_locations`
--

DROP TABLE IF EXISTS `acct_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(30) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state` varchar(80) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_locations_company_id_name_unique` (`company_id`,`name`),
  KEY `acct_locations_branch_id_foreign` (`branch_id`),
  CONSTRAINT `acct_locations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_locations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_manufacturing_orders`
--

DROP TABLE IF EXISTS `acct_manufacturing_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_manufacturing_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `bill_of_material_id` bigint(20) unsigned NOT NULL,
  `number` varchar(30) NOT NULL,
  `manufactured_on` date NOT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `source_godown_id` bigint(20) unsigned NOT NULL,
  `destination_godown_id` bigint(20) unsigned NOT NULL,
  `material_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `narration` varchar(1000) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `manufacturing_orders_number_unique` (`company_id`,`branch_id`,`financial_year_id`,`number`),
  KEY `acct_manufacturing_orders_branch_id_foreign` (`branch_id`),
  KEY `acct_manufacturing_orders_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_manufacturing_orders_bill_of_material_id_foreign` (`bill_of_material_id`),
  KEY `acct_manufacturing_orders_source_godown_id_foreign` (`source_godown_id`),
  KEY `acct_manufacturing_orders_destination_godown_id_foreign` (`destination_godown_id`),
  KEY `acct_manufacturing_orders_voucher_id_foreign` (`voucher_id`),
  KEY `acct_manufacturing_orders_created_by_foreign` (`created_by`),
  KEY `manufacturing_orders_report_index` (`company_id`,`financial_year_id`,`manufactured_on`),
  CONSTRAINT `acct_manufacturing_orders_bill_of_material_id_foreign` FOREIGN KEY (`bill_of_material_id`) REFERENCES `acct_bills_of_materials` (`id`),
  CONSTRAINT `acct_manufacturing_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_manufacturing_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_manufacturing_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_manufacturing_orders_destination_godown_id_foreign` FOREIGN KEY (`destination_godown_id`) REFERENCES `acct_godowns` (`id`),
  CONSTRAINT `acct_manufacturing_orders_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_manufacturing_orders_source_godown_id_foreign` FOREIGN KEY (`source_godown_id`) REFERENCES `acct_godowns` (`id`),
  CONSTRAINT `acct_manufacturing_orders_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_merchant_profiles`
--

DROP TABLE IF EXISTS `acct_merchant_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_merchant_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `merchant_code` varchar(40) NOT NULL,
  `settlement_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `notes` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_merchant_profiles_company_id_merchant_code_unique` (`company_id`,`merchant_code`),
  KEY `acct_merchant_profiles_branch_id_foreign` (`branch_id`),
  KEY `acct_merchant_profiles_settlement_ledger_id_foreign` (`settlement_ledger_id`),
  KEY `acct_merchant_profiles_bank_account_id_foreign` (`bank_account_id`),
  CONSTRAINT `acct_merchant_profiles_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_merchant_profiles_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_merchant_profiles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_merchant_profiles_settlement_ledger_id_foreign` FOREIGN KEY (`settlement_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_parties`
--

DROP TABLE IF EXISTS `acct_parties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_parties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `billing_address` varchar(500) DEFAULT NULL,
  `shipping_address` varchar(500) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `pan` varchar(10) DEFAULT NULL,
  `gst_registration_type` varchar(20) DEFAULT NULL,
  `credit_limit` decimal(15,2) DEFAULT NULL,
  `credit_days` smallint(5) unsigned DEFAULT NULL,
  `payment_term_id` bigint(20) unsigned DEFAULT NULL,
  `default_discount_percent` decimal(8,2) DEFAULT NULL,
  `salesperson_name` varchar(120) DEFAULT NULL,
  `commission_percent` decimal(8,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_msme` tinyint(1) NOT NULL DEFAULT 0,
  `msme_registration_number` varchar(40) DEFAULT NULL,
  `msme_type` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_parties_ledger_id_unique` (`ledger_id`),
  UNIQUE KEY `acct_parties_company_id_gstin_unique` (`company_id`,`gstin`),
  KEY `acct_parties_branch_id_foreign` (`branch_id`),
  KEY `acct_parties_company_id_type_is_active_index` (`company_id`,`type`,`is_active`),
  KEY `acct_parties_payment_term_id_foreign` (`payment_term_id`),
  CONSTRAINT `acct_parties_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_parties_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_parties_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_parties_payment_term_id_foreign` FOREIGN KEY (`payment_term_id`) REFERENCES `acct_payment_terms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_party_addresses`
--

DROP TABLE IF EXISTS `acct_party_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_party_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `party_id` bigint(20) unsigned NOT NULL,
  `label` varchar(80) DEFAULT NULL,
  `kind` varchar(20) NOT NULL DEFAULT 'billing',
  `line1` varchar(255) NOT NULL,
  `line2` varchar(255) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state` varchar(80) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `gstin` varchar(15) DEFAULT NULL,
  `place_of_supply` varchar(80) DEFAULT NULL,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_party_addresses_company_id_foreign` (`company_id`),
  KEY `acct_party_addresses_party_id_kind_index` (`party_id`,`kind`),
  CONSTRAINT `acct_party_addresses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_party_addresses_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `acct_parties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_party_price_lists`
--

DROP TABLE IF EXISTS `acct_party_price_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_party_price_lists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `party_id` bigint(20) unsigned NOT NULL,
  `price_list_id` bigint(20) unsigned NOT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_party_price_lists_unique` (`party_id`,`price_list_id`),
  KEY `acct_party_price_lists_company_id_foreign` (`company_id`),
  KEY `acct_party_price_lists_price_list_id_foreign` (`price_list_id`),
  CONSTRAINT `acct_party_price_lists_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_party_price_lists_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `acct_parties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_party_price_lists_price_list_id_foreign` FOREIGN KEY (`price_list_id`) REFERENCES `acct_price_lists` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_pay_heads`
--

DROP TABLE IF EXISTS `acct_pay_heads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_pay_heads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `nature` varchar(20) NOT NULL,
  `calculation` varchar(20) NOT NULL DEFAULT 'flat',
  `rate_or_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_pay_heads_company_id_foreign` (`company_id`),
  KEY `acct_pay_heads_ledger_id_foreign` (`ledger_id`),
  KEY `acct_pay_heads_employee_id_foreign` (`employee_id`),
  CONSTRAINT `acct_pay_heads_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_pay_heads_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `acct_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_pay_heads_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_payment_advices`
--

DROP TABLE IF EXISTS `acct_payment_advices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_payment_advices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `bank_account_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `party_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `advice_number` varchar(30) NOT NULL,
  `advice_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_advices_number_unique` (`company_id`,`advice_number`),
  KEY `acct_payment_advices_branch_id_foreign` (`branch_id`),
  KEY `acct_payment_advices_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_payment_advices_bank_account_id_foreign` (`bank_account_id`),
  KEY `acct_payment_advices_voucher_id_foreign` (`voucher_id`),
  KEY `acct_payment_advices_party_ledger_id_foreign` (`party_ledger_id`),
  CONSTRAINT `acct_payment_advices_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `acct_bank_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payment_advices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_payment_advices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_payment_advices_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_payment_advices_party_ledger_id_foreign` FOREIGN KEY (`party_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payment_advices_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_payment_batch_lines`
--

DROP TABLE IF EXISTS `acct_payment_batch_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_payment_batch_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_batch_id` bigint(20) unsigned NOT NULL,
  `supplier_ledger_id` bigint(20) unsigned NOT NULL,
  `bill_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tds_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `reference` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_payment_batch_lines_payment_batch_id_foreign` (`payment_batch_id`),
  KEY `acct_payment_batch_lines_supplier_ledger_id_foreign` (`supplier_ledger_id`),
  KEY `acct_payment_batch_lines_bill_id_foreign` (`bill_id`),
  CONSTRAINT `acct_payment_batch_lines_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `acct_bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payment_batch_lines_payment_batch_id_foreign` FOREIGN KEY (`payment_batch_id`) REFERENCES `acct_payment_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_payment_batch_lines_supplier_ledger_id_foreign` FOREIGN KEY (`supplier_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_payment_batches`
--

DROP TABLE IF EXISTS `acct_payment_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_payment_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `number` varchar(40) NOT NULL,
  `batch_date` date NOT NULL,
  `bank_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_payment_batches_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_payment_batches_branch_id_foreign` (`branch_id`),
  KEY `acct_payment_batches_bank_ledger_id_foreign` (`bank_ledger_id`),
  KEY `acct_payment_batches_approved_by_foreign` (`approved_by`),
  CONSTRAINT `acct_payment_batches_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payment_batches_bank_ledger_id_foreign` FOREIGN KEY (`bank_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payment_batches_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payment_batches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_payment_requests`
--

DROP TABLE IF EXISTS `acct_payment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_payment_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `narration` varchar(1000) DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_payment_requests_company_id_foreign` (`company_id`),
  KEY `acct_payment_requests_branch_id_foreign` (`branch_id`),
  KEY `acct_payment_requests_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_payment_requests_ledger_id_foreign` (`ledger_id`),
  KEY `acct_payment_requests_voucher_id_foreign` (`voucher_id`),
  KEY `acct_payment_requests_created_by_foreign` (`created_by`),
  CONSTRAINT `acct_payment_requests_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_payment_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_payment_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payment_requests_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_payment_requests_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_payment_requests_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_payment_terms`
--

DROP TABLE IF EXISTS `acct_payment_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_payment_terms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `due_on_receipt` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_payment_terms_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `acct_payment_terms_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_payroll_lines`
--

DROP TABLE IF EXISTS `acct_payroll_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_payroll_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `earnings` decimal(15,2) NOT NULL,
  `deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `pf_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `esi_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payable_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `net` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `employer_pf_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_esi_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `acct_payroll_lines_payroll_run_id_foreign` (`payroll_run_id`),
  KEY `acct_payroll_lines_employee_id_foreign` (`employee_id`),
  CONSTRAINT `acct_payroll_lines_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `acct_employees` (`id`),
  CONSTRAINT `acct_payroll_lines_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `acct_payroll_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_payroll_provisions`
--

DROP TABLE IF EXISTS `acct_payroll_provisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_payroll_provisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `kind` varchar(40) NOT NULL,
  `period` varchar(7) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `expense_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `liability_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_payroll_provisions_company_id_foreign` (`company_id`),
  KEY `acct_payroll_provisions_expense_ledger_id_foreign` (`expense_ledger_id`),
  KEY `acct_payroll_provisions_liability_ledger_id_foreign` (`liability_ledger_id`),
  KEY `acct_payroll_provisions_voucher_id_foreign` (`voucher_id`),
  CONSTRAINT `acct_payroll_provisions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_payroll_provisions_expense_ledger_id_foreign` FOREIGN KEY (`expense_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payroll_provisions_liability_ledger_id_foreign` FOREIGN KEY (`liability_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payroll_provisions_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_payroll_runs`
--

DROP TABLE IF EXISTS `acct_payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_payroll_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `salary_ledger_id` bigint(20) unsigned NOT NULL,
  `deduction_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `statutory_voucher_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_payroll_runs_company_id_foreign` (`company_id`),
  KEY `acct_payroll_runs_branch_id_foreign` (`branch_id`),
  KEY `acct_payroll_runs_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_payroll_runs_salary_ledger_id_foreign` (`salary_ledger_id`),
  KEY `acct_payroll_runs_deduction_ledger_id_foreign` (`deduction_ledger_id`),
  KEY `acct_payroll_runs_voucher_id_foreign` (`voucher_id`),
  KEY `acct_payroll_runs_created_by_foreign` (`created_by`),
  KEY `acct_payroll_runs_statutory_voucher_id_foreign` (`statutory_voucher_id`),
  CONSTRAINT `acct_payroll_runs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_payroll_runs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payroll_runs_deduction_ledger_id_foreign` FOREIGN KEY (`deduction_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payroll_runs_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_payroll_runs_salary_ledger_id_foreign` FOREIGN KEY (`salary_ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_payroll_runs_statutory_voucher_id_foreign` FOREIGN KEY (`statutory_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_payroll_runs_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_preferences`
--

DROP TABLE IF EXISTS `acct_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `key` varchar(80) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_preferences_company_id_user_id_key_unique` (`company_id`,`user_id`,`key`),
  KEY `acct_preferences_user_id_foreign` (`user_id`),
  CONSTRAINT `acct_preferences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_prepaid_expenses`
--

DROP TABLE IF EXISTS `acct_prepaid_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_prepaid_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `prepaid_ledger_id` bigint(20) unsigned NOT NULL,
  `expense_ledger_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `recognized_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `frequency` varchar(20) NOT NULL DEFAULT 'monthly',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `source_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_prepaid_expenses_company_id_foreign` (`company_id`),
  KEY `acct_prepaid_expenses_branch_id_foreign` (`branch_id`),
  KEY `acct_prepaid_expenses_prepaid_ledger_id_foreign` (`prepaid_ledger_id`),
  KEY `acct_prepaid_expenses_expense_ledger_id_foreign` (`expense_ledger_id`),
  KEY `acct_prepaid_expenses_source_voucher_id_foreign` (`source_voucher_id`),
  CONSTRAINT `acct_prepaid_expenses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_prepaid_expenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_prepaid_expenses_expense_ledger_id_foreign` FOREIGN KEY (`expense_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_prepaid_expenses_prepaid_ledger_id_foreign` FOREIGN KEY (`prepaid_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_prepaid_expenses_source_voucher_id_foreign` FOREIGN KEY (`source_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_price_list_lines`
--

DROP TABLE IF EXISTS `acct_price_list_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_price_list_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `price_list_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_price_list_lines_price_list_id_product_id_unique` (`price_list_id`,`product_id`),
  KEY `acct_price_list_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_price_list_lines_price_list_id_foreign` FOREIGN KEY (`price_list_id`) REFERENCES `acct_price_lists` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_price_list_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_price_lists`
--

DROP TABLE IF EXISTS `acct_price_lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_price_lists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_price_lists_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `acct_price_lists_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_product_barcodes`
--

DROP TABLE IF EXISTS `acct_product_barcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_product_barcodes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `barcode` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_product_barcodes_company_id_barcode_unique` (`company_id`,`barcode`),
  KEY `acct_product_barcodes_product_id_foreign` (`product_id`),
  KEY `acct_product_barcodes_company_id_product_id_index` (`company_id`,`product_id`),
  CONSTRAINT `acct_product_barcodes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_product_barcodes_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_product_groups`
--

DROP TABLE IF EXISTS `acct_product_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_product_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_product_groups_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_product_groups_company_id_code_unique` (`company_id`,`code`),
  KEY `acct_product_groups_parent_id_foreign` (`parent_id`),
  KEY `acct_product_groups_company_id_parent_id_index` (`company_id`,`parent_id`),
  KEY `acct_product_groups_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `acct_product_groups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_product_groups_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `acct_product_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_products`
--

DROP TABLE IF EXISTS `acct_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_group_id` bigint(20) unsigned NOT NULL,
  `primary_unit_id` bigint(20) unsigned NOT NULL,
  `alternate_unit_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `code` varchar(32) NOT NULL,
  `part_number` varchar(40) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `type_of_supply` varchar(16) NOT NULL DEFAULT 'goods',
  `barcode` varchar(64) DEFAULT NULL,
  `hsn_sac_id` bigint(20) unsigned DEFAULT NULL,
  `tax_rate_id` bigint(20) unsigned DEFAULT NULL,
  `conversion_factor` decimal(18,6) DEFAULT NULL,
  `purchase_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sales_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `opening_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opening_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `minimum_stock` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `reorder_level` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `maximum_stock` decimal(15,4) DEFAULT NULL,
  `track_batch` tinyint(1) NOT NULL DEFAULT 0,
  `track_serial` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_products_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_products_company_id_code_unique` (`company_id`,`code`),
  UNIQUE KEY `acct_products_company_id_barcode_unique` (`company_id`,`barcode`),
  KEY `acct_products_product_group_id_foreign` (`product_group_id`),
  KEY `acct_products_primary_unit_id_foreign` (`primary_unit_id`),
  KEY `acct_products_alternate_unit_id_foreign` (`alternate_unit_id`),
  KEY `acct_products_company_id_product_group_id_index` (`company_id`,`product_group_id`),
  KEY `acct_products_company_id_primary_unit_id_index` (`company_id`,`primary_unit_id`),
  KEY `acct_products_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `acct_products_hsn_sac_id_foreign` (`hsn_sac_id`),
  KEY `acct_products_tax_rate_id_foreign` (`tax_rate_id`),
  CONSTRAINT `acct_products_alternate_unit_id_foreign` FOREIGN KEY (`alternate_unit_id`) REFERENCES `acct_units` (`id`),
  CONSTRAINT `acct_products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_products_hsn_sac_id_foreign` FOREIGN KEY (`hsn_sac_id`) REFERENCES `acct_hsn_sacs` (`id`),
  CONSTRAINT `acct_products_primary_unit_id_foreign` FOREIGN KEY (`primary_unit_id`) REFERENCES `acct_units` (`id`),
  CONSTRAINT `acct_products_product_group_id_foreign` FOREIGN KEY (`product_group_id`) REFERENCES `acct_product_groups` (`id`),
  CONSTRAINT `acct_products_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `acct_tax_rates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_profitability_snapshots`
--

DROP TABLE IF EXISTS `acct_profitability_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_profitability_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `dimension` varchar(40) NOT NULL,
  `dimension_key` varchar(80) NOT NULL,
  `dimension_label` varchar(160) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `revenue` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(18,2) NOT NULL DEFAULT 0.00,
  `contribution` decimal(18,2) NOT NULL DEFAULT 0.00,
  `margin_percent` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_profit_snap_idx` (`company_id`,`dimension`,`to_date`),
  CONSTRAINT `acct_profitability_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_project_milestones`
--

DROP TABLE IF EXISTS `acct_project_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_project_milestones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(160) NOT NULL,
  `percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `billed_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_project_milestones_project_id_foreign` (`project_id`),
  KEY `acct_project_milestones_billed_invoice_id_foreign` (`billed_invoice_id`),
  CONSTRAINT `acct_project_milestones_billed_invoice_id_foreign` FOREIGN KEY (`billed_invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_project_milestones_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `acct_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_projects`
--

DROP TABLE IF EXISTS `acct_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `customer_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(40) DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `contract_value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_projects_company_id_name_unique` (`company_id`,`name`),
  KEY `acct_projects_customer_ledger_id_foreign` (`customer_ledger_id`),
  CONSTRAINT `acct_projects_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_projects_customer_ledger_id_foreign` FOREIGN KEY (`customer_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_purchase_order_lines`
--

DROP TABLE IF EXISTS `acct_purchase_order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_purchase_order_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `line_number` int(10) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `tax_rate_id` bigint(20) unsigned DEFAULT NULL,
  `hsn_sac_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cess_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_purchase_order_lines_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `acct_purchase_order_lines_product_id_foreign` (`product_id`),
  KEY `acct_purchase_order_lines_tax_rate_id_foreign` (`tax_rate_id`),
  KEY `acct_purchase_order_lines_hsn_sac_id_foreign` (`hsn_sac_id`),
  CONSTRAINT `acct_purchase_order_lines_hsn_sac_id_foreign` FOREIGN KEY (`hsn_sac_id`) REFERENCES `acct_hsn_sacs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_order_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_order_lines_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `acct_purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_purchase_order_lines_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `acct_tax_rates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_purchase_orders`
--

DROP TABLE IF EXISTS `acct_purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `number` varchar(32) NOT NULL,
  `order_date` date NOT NULL,
  `supplier_ledger_id` bigint(20) unsigned NOT NULL,
  `purchase_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `narration` varchar(1000) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `delivery_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `purchase_requisition_id` bigint(20) unsigned DEFAULT NULL,
  `vendor_quotation_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_number_unique` (`company_id`,`financial_year_id`,`number`),
  KEY `acct_purchase_orders_branch_id_foreign` (`branch_id`),
  KEY `acct_purchase_orders_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_purchase_orders_supplier_ledger_id_foreign` (`supplier_ledger_id`),
  KEY `acct_purchase_orders_created_by_foreign` (`created_by`),
  KEY `acct_purchase_orders_purchase_ledger_id_foreign` (`purchase_ledger_id`),
  KEY `acct_purchase_orders_purchase_requisition_id_foreign` (`purchase_requisition_id`),
  KEY `acct_purchase_orders_vendor_quotation_id_foreign` (`vendor_quotation_id`),
  KEY `acct_purchase_orders_approved_by_foreign` (`approved_by`),
  CONSTRAINT `acct_purchase_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_purchase_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_orders_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_purchase_orders_purchase_ledger_id_foreign` FOREIGN KEY (`purchase_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_orders_purchase_requisition_id_foreign` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `acct_purchase_requisitions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_orders_supplier_ledger_id_foreign` FOREIGN KEY (`supplier_ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_purchase_orders_vendor_quotation_id_foreign` FOREIGN KEY (`vendor_quotation_id`) REFERENCES `acct_vendor_quotations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_purchase_requisition_lines`
--

DROP TABLE IF EXISTS `acct_purchase_requisition_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_purchase_requisition_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_requisition_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL DEFAULT 1,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `unit` varchar(40) DEFAULT NULL,
  `required_by` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_purchase_requisition_lines_purchase_requisition_id_foreign` (`purchase_requisition_id`),
  KEY `acct_purchase_requisition_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_purchase_requisition_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_requisition_lines_purchase_requisition_id_foreign` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `acct_purchase_requisitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_purchase_requisitions`
--

DROP TABLE IF EXISTS `acct_purchase_requisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_purchase_requisitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `number` varchar(40) NOT NULL,
  `requisition_date` date NOT NULL,
  `requested_by` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `narration` varchar(500) DEFAULT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_purchase_requisitions_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_purchase_requisitions_branch_id_foreign` (`branch_id`),
  KEY `acct_purchase_requisitions_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_purchase_requisitions_requested_by_foreign` (`requested_by`),
  KEY `acct_purchase_requisitions_department_id_foreign` (`department_id`),
  KEY `acct_purchase_requisitions_purchase_order_id_foreign` (`purchase_order_id`),
  CONSTRAINT `acct_purchase_requisitions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_purchase_requisitions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_purchase_requisitions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `acct_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_requisitions_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_purchase_requisitions_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `acct_purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_purchase_requisitions_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_quotation_lines`
--

DROP TABLE IF EXISTS `acct_quotation_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_quotation_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL DEFAULT 1,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `hsn_sac` varchar(20) DEFAULT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `rate` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_quotation_lines_quotation_id_foreign` (`quotation_id`),
  KEY `acct_quotation_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_quotation_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_quotation_lines_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `acct_quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_quotations`
--

DROP TABLE IF EXISTS `acct_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `number` varchar(40) NOT NULL,
  `quote_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `customer_ledger_id` bigint(20) unsigned NOT NULL,
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `reference_number` varchar(80) DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `sales_order_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_quotations_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_quotations_branch_id_foreign` (`branch_id`),
  KEY `acct_quotations_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_quotations_customer_ledger_id_foreign` (`customer_ledger_id`),
  KEY `acct_quotations_billing_address_id_foreign` (`billing_address_id`),
  KEY `acct_quotations_shipping_address_id_foreign` (`shipping_address_id`),
  KEY `acct_quotations_sales_order_id_foreign` (`sales_order_id`),
  KEY `acct_quotations_created_by_foreign` (`created_by`),
  CONSTRAINT `acct_quotations_billing_address_id_foreign` FOREIGN KEY (`billing_address_id`) REFERENCES `acct_party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_quotations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_quotations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_quotations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_quotations_customer_ledger_id_foreign` FOREIGN KEY (`customer_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_quotations_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_quotations_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `acct_sales_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_quotations_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `acct_party_addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_recurring_invoice_lines`
--

DROP TABLE IF EXISTS `acct_recurring_invoice_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_recurring_invoice_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recurring_invoice_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL DEFAULT 1,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `rate` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `discount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_recurring_invoice_lines_recurring_invoice_id_foreign` (`recurring_invoice_id`),
  KEY `acct_recurring_invoice_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_recurring_invoice_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_recurring_invoice_lines_recurring_invoice_id_foreign` FOREIGN KEY (`recurring_invoice_id`) REFERENCES `acct_recurring_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_recurring_invoices`
--

DROP TABLE IF EXISTS `acct_recurring_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_recurring_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `name` varchar(160) NOT NULL,
  `customer_ledger_id` bigint(20) unsigned NOT NULL,
  `account_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `frequency` varchar(20) NOT NULL DEFAULT 'monthly',
  `day_of_month` tinyint(3) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `next_run_on` date DEFAULT NULL,
  `invoice_category` varchar(30) NOT NULL DEFAULT 'tax_invoice',
  `narration` varchar(500) DEFAULT NULL,
  `auto_post` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_recurring_invoices_company_id_foreign` (`company_id`),
  KEY `acct_recurring_invoices_branch_id_foreign` (`branch_id`),
  KEY `acct_recurring_invoices_customer_ledger_id_foreign` (`customer_ledger_id`),
  KEY `acct_recurring_invoices_account_ledger_id_foreign` (`account_ledger_id`),
  CONSTRAINT `acct_recurring_invoices_account_ledger_id_foreign` FOREIGN KEY (`account_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_recurring_invoices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_recurring_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_recurring_invoices_customer_ledger_id_foreign` FOREIGN KEY (`customer_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_recurring_voucher_lines`
--

DROP TABLE IF EXISTS `acct_recurring_voucher_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_recurring_voucher_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recurring_voucher_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cost_centre_id` bigint(20) unsigned DEFAULT NULL,
  `business_unit_id` bigint(20) unsigned DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_recurring_voucher_lines_recurring_voucher_id_foreign` (`recurring_voucher_id`),
  KEY `acct_recurring_voucher_lines_ledger_id_foreign` (`ledger_id`),
  KEY `acct_recurring_voucher_lines_cost_centre_id_foreign` (`cost_centre_id`),
  KEY `acct_recurring_voucher_lines_business_unit_id_foreign` (`business_unit_id`),
  KEY `acct_recurring_voucher_lines_department_id_foreign` (`department_id`),
  CONSTRAINT `acct_recurring_voucher_lines_business_unit_id_foreign` FOREIGN KEY (`business_unit_id`) REFERENCES `acct_business_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_recurring_voucher_lines_cost_centre_id_foreign` FOREIGN KEY (`cost_centre_id`) REFERENCES `acct_cost_centres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_recurring_voucher_lines_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `acct_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_recurring_voucher_lines_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_recurring_voucher_lines_recurring_voucher_id_foreign` FOREIGN KEY (`recurring_voucher_id`) REFERENCES `acct_recurring_vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_recurring_vouchers`
--

DROP TABLE IF EXISTS `acct_recurring_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_recurring_vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `voucher_type` varchar(20) NOT NULL DEFAULT 'journal',
  `frequency` varchar(20) NOT NULL DEFAULT 'monthly',
  `day_of_month` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_run_on` date NOT NULL,
  `narration` varchar(1000) DEFAULT NULL,
  `auto_post` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_recurring_vouchers_branch_id_foreign` (`branch_id`),
  KEY `acct_recurring_vouchers_created_by_foreign` (`created_by`),
  KEY `acct_recurring_vouchers_company_id_next_run_on_is_active_index` (`company_id`,`next_run_on`,`is_active`),
  CONSTRAINT `acct_recurring_vouchers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_recurring_vouchers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_recurring_vouchers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_related_parties`
--

DROP TABLE IF EXISTS `acct_related_parties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_related_parties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `party_ledger_id` bigint(20) unsigned NOT NULL,
  `relationship` varchar(80) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_related_parties_company_id_party_ledger_id_unique` (`company_id`,`party_ledger_id`),
  KEY `acct_related_parties_party_ledger_id_foreign` (`party_ledger_id`),
  CONSTRAINT `acct_related_parties_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_related_parties_party_ledger_id_foreign` FOREIGN KEY (`party_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_request_for_quotation_lines`
--

DROP TABLE IF EXISTS `acct_request_for_quotation_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_request_for_quotation_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_for_quotation_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL DEFAULT 1,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `unit` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_request_for_quotation_lines_product_id_foreign` (`product_id`),
  KEY `acct_rfq_lines_rfq_fk` (`request_for_quotation_id`),
  CONSTRAINT `acct_request_for_quotation_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_rfq_lines_rfq_fk` FOREIGN KEY (`request_for_quotation_id`) REFERENCES `acct_request_for_quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_request_for_quotations`
--

DROP TABLE IF EXISTS `acct_request_for_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_request_for_quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `number` varchar(40) NOT NULL,
  `rfq_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `purchase_requisition_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `narration` varchar(500) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_request_for_quotations_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_request_for_quotations_branch_id_foreign` (`branch_id`),
  KEY `acct_request_for_quotations_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_request_for_quotations_purchase_requisition_id_foreign` (`purchase_requisition_id`),
  KEY `acct_request_for_quotations_created_by_foreign` (`created_by`),
  CONSTRAINT `acct_request_for_quotations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_request_for_quotations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_request_for_quotations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_request_for_quotations_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_request_for_quotations_purchase_requisition_id_foreign` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `acct_purchase_requisitions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_roles`
--

DROP TABLE IF EXISTS `acct_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_salary_structure_lines`
--

DROP TABLE IF EXISTS `acct_salary_structure_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_salary_structure_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `salary_structure_id` bigint(20) unsigned NOT NULL,
  `pay_head_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `percent_of_basic` decimal(8,2) DEFAULT NULL,
  `is_earning` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_salary_structure_lines_salary_structure_id_foreign` (`salary_structure_id`),
  KEY `acct_salary_structure_lines_pay_head_id_foreign` (`pay_head_id`),
  CONSTRAINT `acct_salary_structure_lines_pay_head_id_foreign` FOREIGN KEY (`pay_head_id`) REFERENCES `acct_pay_heads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_salary_structure_lines_salary_structure_id_foreign` FOREIGN KEY (`salary_structure_id`) REFERENCES `acct_salary_structures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_salary_structures`
--

DROP TABLE IF EXISTS `acct_salary_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_salary_structures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_salary_structures_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `acct_salary_structures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_sales_commissions`
--

DROP TABLE IF EXISTS `acct_sales_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_sales_commissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `party_id` bigint(20) unsigned DEFAULT NULL,
  `salesperson_name` varchar(120) DEFAULT NULL,
  `base_amount` decimal(18,2) NOT NULL,
  `percent` decimal(8,2) NOT NULL,
  `commission_amount` decimal(18,2) NOT NULL,
  `accrual_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'accrued',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_sales_commissions_company_id_foreign` (`company_id`),
  KEY `acct_sales_commissions_invoice_id_foreign` (`invoice_id`),
  KEY `acct_sales_commissions_party_id_foreign` (`party_id`),
  KEY `acct_sales_commissions_accrual_voucher_id_foreign` (`accrual_voucher_id`),
  CONSTRAINT `acct_sales_commissions_accrual_voucher_id_foreign` FOREIGN KEY (`accrual_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_sales_commissions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_sales_commissions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_sales_commissions_party_id_foreign` FOREIGN KEY (`party_id`) REFERENCES `acct_parties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_sales_order_lines`
--

DROP TABLE IF EXISTS `acct_sales_order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_sales_order_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_order_id` bigint(20) unsigned NOT NULL,
  `line_number` int(10) unsigned NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `tax_rate_id` bigint(20) unsigned DEFAULT NULL,
  `hsn_sac_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `fulfilled_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `cancelled_quantity` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `rate` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cess_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_sales_order_lines_sales_order_id_foreign` (`sales_order_id`),
  KEY `acct_sales_order_lines_product_id_foreign` (`product_id`),
  KEY `acct_sales_order_lines_tax_rate_id_foreign` (`tax_rate_id`),
  KEY `acct_sales_order_lines_hsn_sac_id_foreign` (`hsn_sac_id`),
  CONSTRAINT `acct_sales_order_lines_hsn_sac_id_foreign` FOREIGN KEY (`hsn_sac_id`) REFERENCES `acct_hsn_sacs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_sales_order_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_sales_order_lines_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `acct_sales_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_sales_order_lines_tax_rate_id_foreign` FOREIGN KEY (`tax_rate_id`) REFERENCES `acct_tax_rates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_sales_orders`
--

DROP TABLE IF EXISTS `acct_sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_sales_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `number` varchar(32) NOT NULL,
  `order_date` date NOT NULL,
  `customer_ledger_id` bigint(20) unsigned NOT NULL,
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `narration` varchar(1000) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `quotation_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_orders_number_unique` (`company_id`,`financial_year_id`,`number`),
  KEY `acct_sales_orders_branch_id_foreign` (`branch_id`),
  KEY `acct_sales_orders_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_sales_orders_customer_ledger_id_foreign` (`customer_ledger_id`),
  KEY `acct_sales_orders_created_by_foreign` (`created_by`),
  KEY `acct_sales_orders_quotation_id_foreign` (`quotation_id`),
  KEY `acct_sales_orders_billing_address_id_foreign` (`billing_address_id`),
  KEY `acct_sales_orders_shipping_address_id_foreign` (`shipping_address_id`),
  CONSTRAINT `acct_sales_orders_billing_address_id_foreign` FOREIGN KEY (`billing_address_id`) REFERENCES `acct_party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_sales_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_sales_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_sales_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_sales_orders_customer_ledger_id_foreign` FOREIGN KEY (`customer_ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_sales_orders_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_sales_orders_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `acct_quotations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_sales_orders_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `acct_party_addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_statutory_due_dates`
--

DROP TABLE IF EXISTS `acct_statutory_due_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_statutory_due_dates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `obligation` varchar(120) NOT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'gst',
  `due_date` date NOT NULL,
  `period_label` varchar(40) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `alert_sent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_stat_due_idx` (`company_id`,`due_date`,`status`),
  CONSTRAINT `acct_statutory_due_dates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_batches`
--

DROP TABLE IF EXISTS `acct_stock_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `batch_number` varchar(40) NOT NULL,
  `manufactured_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_batches_number_unique` (`company_id`,`product_id`,`batch_number`),
  KEY `acct_stock_batches_product_id_foreign` (`product_id`),
  KEY `stock_batches_expiry_index` (`company_id`,`expires_on`),
  CONSTRAINT `acct_stock_batches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_stock_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_cost_consumptions`
--

DROP TABLE IF EXISTS `acct_stock_cost_consumptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_cost_consumptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `stock_cost_layer_id` bigint(20) unsigned NOT NULL,
  `stock_movement_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `value` decimal(18,2) NOT NULL DEFAULT 0.00,
  `consumed_on` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_stock_cost_consumptions_company_id_foreign` (`company_id`),
  KEY `acct_stock_cost_consumptions_stock_cost_layer_id_foreign` (`stock_cost_layer_id`),
  KEY `acct_stock_cost_consumptions_stock_movement_id_foreign` (`stock_movement_id`),
  CONSTRAINT `acct_stock_cost_consumptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_stock_cost_consumptions_stock_cost_layer_id_foreign` FOREIGN KEY (`stock_cost_layer_id`) REFERENCES `acct_stock_cost_layers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_stock_cost_consumptions_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `acct_stock_movements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_cost_layers`
--

DROP TABLE IF EXISTS `acct_stock_cost_layers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_cost_layers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `godown_id` bigint(20) unsigned DEFAULT NULL,
  `stock_movement_id` bigint(20) unsigned DEFAULT NULL,
  `received_on` date NOT NULL,
  `quantity_in` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `quantity_remaining` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_stock_cost_layers_product_id_foreign` (`product_id`),
  KEY `acct_stock_cost_layers_godown_id_foreign` (`godown_id`),
  KEY `acct_stock_cost_layers_stock_movement_id_foreign` (`stock_movement_id`),
  KEY `acct_fifo_layer_idx` (`company_id`,`product_id`,`godown_id`,`received_on`),
  CONSTRAINT `acct_stock_cost_layers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_stock_cost_layers_godown_id_foreign` FOREIGN KEY (`godown_id`) REFERENCES `acct_godowns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_stock_cost_layers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_stock_cost_layers_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `acct_stock_movements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_count_lines`
--

DROP TABLE IF EXISTS `acct_stock_count_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_count_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_count_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `system_qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `counted_qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `variance_qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_stock_count_lines_stock_count_id_foreign` (`stock_count_id`),
  KEY `acct_stock_count_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_stock_count_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_stock_count_lines_stock_count_id_foreign` FOREIGN KEY (`stock_count_id`) REFERENCES `acct_stock_counts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_counts`
--

DROP TABLE IF EXISTS `acct_stock_counts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_counts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `godown_id` bigint(20) unsigned DEFAULT NULL,
  `number` varchar(40) NOT NULL,
  `count_date` date NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `narration` varchar(500) DEFAULT NULL,
  `adjustment_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_stock_counts_company_id_number_unique` (`company_id`,`number`),
  KEY `acct_stock_counts_branch_id_foreign` (`branch_id`),
  KEY `acct_stock_counts_godown_id_foreign` (`godown_id`),
  CONSTRAINT `acct_stock_counts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_stock_counts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_stock_counts_godown_id_foreign` FOREIGN KEY (`godown_id`) REFERENCES `acct_godowns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_godown_openings`
--

DROP TABLE IF EXISTS `acct_stock_godown_openings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_godown_openings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `godown_id` bigint(20) unsigned NOT NULL,
  `batch_name` varchar(40) NOT NULL DEFAULT '',
  `quantity` decimal(15,4) NOT NULL,
  `rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_godown_openings_unique` (`product_id`,`godown_id`,`batch_name`),
  KEY `acct_stock_godown_openings_company_id_foreign` (`company_id`),
  KEY `acct_stock_godown_openings_godown_id_foreign` (`godown_id`),
  CONSTRAINT `acct_stock_godown_openings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_stock_godown_openings_godown_id_foreign` FOREIGN KEY (`godown_id`) REFERENCES `acct_godowns` (`id`),
  CONSTRAINT `acct_stock_godown_openings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_movements`
--

DROP TABLE IF EXISTS `acct_stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `godown_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `serial_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `value` decimal(15,2) NOT NULL,
  `movement_type` varchar(20) NOT NULL,
  `reference_type` varchar(255) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `movement_date` date NOT NULL,
  `is_reversal` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_stock_movements_branch_id_foreign` (`branch_id`),
  KEY `acct_stock_movements_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_stock_movements_product_id_foreign` (`product_id`),
  KEY `acct_stock_movements_godown_id_foreign` (`godown_id`),
  KEY `stock_movements_balance_index` (`company_id`,`product_id`,`godown_id`),
  KEY `acct_stock_movements_company_id_movement_date_index` (`company_id`,`movement_date`),
  KEY `acct_stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `acct_stock_movements_batch_id_foreign` (`batch_id`),
  KEY `acct_stock_movements_serial_id_foreign` (`serial_id`),
  KEY `stock_movements_report_index` (`company_id`,`financial_year_id`,`product_id`,`movement_date`),
  CONSTRAINT `acct_stock_movements_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `acct_stock_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_stock_movements_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_stock_movements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_stock_movements_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_stock_movements_godown_id_foreign` FOREIGN KEY (`godown_id`) REFERENCES `acct_godowns` (`id`),
  CONSTRAINT `acct_stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`),
  CONSTRAINT `acct_stock_movements_serial_id_foreign` FOREIGN KEY (`serial_id`) REFERENCES `acct_stock_serials` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_serials`
--

DROP TABLE IF EXISTS `acct_stock_serials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_serials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `serial_number` varchar(60) NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_stock_serials_company_id_serial_number_unique` (`company_id`,`serial_number`),
  KEY `acct_stock_serials_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_stock_serials_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_stock_serials_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_transaction_lines`
--

DROP TABLE IF EXISTS `acct_stock_transaction_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_transaction_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_transaction_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `godown_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `rate` decimal(15,2) NOT NULL,
  `value` decimal(15,2) NOT NULL,
  `batch_number` varchar(40) DEFAULT NULL,
  `manufactured_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `serial_number` varchar(60) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_transaction_lines_number_unique` (`stock_transaction_id`,`line_number`),
  KEY `acct_stock_transaction_lines_product_id_foreign` (`product_id`),
  KEY `acct_stock_transaction_lines_godown_id_foreign` (`godown_id`),
  CONSTRAINT `acct_stock_transaction_lines_godown_id_foreign` FOREIGN KEY (`godown_id`) REFERENCES `acct_godowns` (`id`),
  CONSTRAINT `acct_stock_transaction_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`),
  CONSTRAINT `acct_stock_transaction_lines_stock_transaction_id_foreign` FOREIGN KEY (`stock_transaction_id`) REFERENCES `acct_stock_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_stock_transactions`
--

DROP TABLE IF EXISTS `acct_stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_stock_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `number` varchar(30) NOT NULL,
  `transaction_date` date NOT NULL,
  `source_godown_id` bigint(20) unsigned DEFAULT NULL,
  `destination_godown_id` bigint(20) unsigned DEFAULT NULL,
  `narration` varchar(1000) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_transactions_number_unique` (`company_id`,`branch_id`,`financial_year_id`,`type`,`number`),
  KEY `acct_stock_transactions_branch_id_foreign` (`branch_id`),
  KEY `acct_stock_transactions_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_stock_transactions_source_godown_id_foreign` (`source_godown_id`),
  KEY `acct_stock_transactions_destination_godown_id_foreign` (`destination_godown_id`),
  KEY `acct_stock_transactions_created_by_foreign` (`created_by`),
  KEY `stock_transactions_context_date_index` (`company_id`,`branch_id`,`financial_year_id`,`transaction_date`),
  CONSTRAINT `acct_stock_transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_stock_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_stock_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_stock_transactions_destination_godown_id_foreign` FOREIGN KEY (`destination_godown_id`) REFERENCES `acct_godowns` (`id`),
  CONSTRAINT `acct_stock_transactions_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_stock_transactions_source_godown_id_foreign` FOREIGN KEY (`source_godown_id`) REFERENCES `acct_godowns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_tally_links`
--

DROP TABLE IF EXISTS `acct_tally_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_tally_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `tally_type` varchar(40) NOT NULL,
  `linkable_type` varchar(255) NOT NULL,
  `linkable_id` bigint(20) unsigned NOT NULL,
  `guid` varchar(64) NOT NULL,
  `alter_id` bigint(20) unsigned DEFAULT NULL,
  `master_id` bigint(20) unsigned DEFAULT NULL,
  `remote_id` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_tally_links_guid` (`company_id`,`tally_type`,`guid`),
  UNIQUE KEY `acct_tally_links_local` (`linkable_type`,`linkable_id`),
  CONSTRAINT `acct_tally_links_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_tax_accounts`
--

DROP TABLE IF EXISTS `acct_tax_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_tax_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `component` varchar(8) NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_tax_accounts_company_id_component_unique` (`company_id`,`component`),
  KEY `acct_tax_accounts_ledger_id_foreign` (`ledger_id`),
  KEY `acct_tax_accounts_company_id_ledger_id_index` (`company_id`,`ledger_id`),
  CONSTRAINT `acct_tax_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_tax_accounts_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_tax_categories`
--

DROP TABLE IF EXISTS `acct_tax_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_tax_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_tax_categories_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_tax_categories_company_id_code_unique` (`company_id`,`code`),
  KEY `acct_tax_categories_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `acct_tax_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_tax_rates`
--

DROP TABLE IF EXISTS `acct_tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_tax_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `tax_category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `cgst_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `sgst_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `igst_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `cess_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_tax_rates_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_tax_rates_company_id_code_unique` (`company_id`,`code`),
  KEY `acct_tax_rates_tax_category_id_foreign` (`tax_category_id`),
  KEY `acct_tax_rates_company_id_tax_category_id_index` (`company_id`,`tax_category_id`),
  KEY `acct_tax_rates_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `acct_tax_rates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_tax_rates_tax_category_id_foreign` FOREIGN KEY (`tax_category_id`) REFERENCES `acct_tax_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_tds_certificates`
--

DROP TABLE IF EXISTS `acct_tds_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_tds_certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `party_ledger_id` bigint(20) unsigned NOT NULL,
  `certificate_number` varchar(40) NOT NULL,
  `form_type` varchar(20) NOT NULL DEFAULT '16A',
  `financial_year` varchar(9) NOT NULL,
  `quarter` varchar(2) DEFAULT NULL,
  `tds_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `issue_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_tds_cert_uq` (`company_id`,`certificate_number`),
  KEY `acct_tds_certificates_party_ledger_id_foreign` (`party_ledger_id`),
  CONSTRAINT `acct_tds_certificates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_tds_certificates_party_ledger_id_foreign` FOREIGN KEY (`party_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_tds_challans`
--

DROP TABLE IF EXISTS `acct_tds_challans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_tds_challans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `return_period` varchar(7) NOT NULL,
  `section` varchar(20) DEFAULT NULL,
  `challan_number` varchar(40) DEFAULT NULL,
  `bsr_code` varchar(20) DEFAULT NULL,
  `deposit_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest` decimal(18,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'deposited',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_tds_challans_company_id_foreign` (`company_id`),
  CONSTRAINT `acct_tds_challans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_units`
--

DROP TABLE IF EXISTS `acct_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(16) NOT NULL,
  `gst_uqc` varchar(8) DEFAULT NULL,
  `decimal_places` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_units_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `acct_units_company_id_symbol_unique` (`company_id`,`symbol`),
  KEY `acct_units_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `acct_units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_user_branches`
--

DROP TABLE IF EXISTS `acct_user_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_user_branches` (
  `user_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`branch_id`),
  KEY `acct_user_branches_branch_id_foreign` (`branch_id`),
  CONSTRAINT `acct_user_branches_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_user_branches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_user_companies`
--

DROP TABLE IF EXISTS `acct_user_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_user_companies` (
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`company_id`),
  KEY `acct_user_companies_company_id_foreign` (`company_id`),
  CONSTRAINT `acct_user_companies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_user_companies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_vendor_quotation_lines`
--

DROP TABLE IF EXISTS `acct_vendor_quotation_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_vendor_quotation_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_quotation_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL DEFAULT 1,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `quantity` decimal(18,4) NOT NULL DEFAULT 1.0000,
  `rate` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_vendor_quotation_lines_vendor_quotation_id_foreign` (`vendor_quotation_id`),
  KEY `acct_vendor_quotation_lines_product_id_foreign` (`product_id`),
  CONSTRAINT `acct_vendor_quotation_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vendor_quotation_lines_vendor_quotation_id_foreign` FOREIGN KEY (`vendor_quotation_id`) REFERENCES `acct_vendor_quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_vendor_quotations`
--

DROP TABLE IF EXISTS `acct_vendor_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_vendor_quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `request_for_quotation_id` bigint(20) unsigned NOT NULL,
  `supplier_ledger_id` bigint(20) unsigned NOT NULL,
  `quote_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `tax_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(18,2) NOT NULL DEFAULT 0.00,
  `lead_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `payment_terms_note` varchar(120) DEFAULT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'received',
  `notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_vendor_quotes_rfq_supplier_uq` (`request_for_quotation_id`,`supplier_ledger_id`),
  KEY `acct_vendor_quotations_company_id_foreign` (`company_id`),
  KEY `acct_vendor_quotations_supplier_ledger_id_foreign` (`supplier_ledger_id`),
  CONSTRAINT `acct_vendor_quotations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_vendor_quotations_supplier_ledger_id_foreign` FOREIGN KEY (`supplier_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_vendor_quotes_rfq_fk` FOREIGN KEY (`request_for_quotation_id`) REFERENCES `acct_request_for_quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_vendor_retentions`
--

DROP TABLE IF EXISTS `acct_vendor_retentions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_vendor_retentions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `supplier_ledger_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `retention_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `retention_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `released_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `retention_date` date NOT NULL,
  `release_due_date` date DEFAULT NULL,
  `kind` varchar(40) NOT NULL DEFAULT 'retention',
  `status` varchar(20) NOT NULL DEFAULT 'held',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `hold_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `release_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `retention_ledger_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_vendor_retentions_company_id_foreign` (`company_id`),
  KEY `acct_vendor_retentions_supplier_ledger_id_foreign` (`supplier_ledger_id`),
  KEY `acct_vendor_retentions_invoice_id_foreign` (`invoice_id`),
  KEY `acct_vendor_retentions_hold_voucher_id_foreign` (`hold_voucher_id`),
  KEY `acct_vendor_retentions_release_voucher_id_foreign` (`release_voucher_id`),
  KEY `acct_vendor_retentions_retention_ledger_id_foreign` (`retention_ledger_id`),
  CONSTRAINT `acct_vendor_retentions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_vendor_retentions_hold_voucher_id_foreign` FOREIGN KEY (`hold_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vendor_retentions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `acct_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vendor_retentions_release_voucher_id_foreign` FOREIGN KEY (`release_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vendor_retentions_retention_ledger_id_foreign` FOREIGN KEY (`retention_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vendor_retentions_supplier_ledger_id_foreign` FOREIGN KEY (`supplier_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_voucher_classes`
--

DROP TABLE IF EXISTS `acct_voucher_classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_voucher_classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `voucher_type` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `default_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_voucher_classes_company_id_voucher_type_name_unique` (`company_id`,`voucher_type`,`name`),
  KEY `acct_voucher_classes_default_ledger_id_foreign` (`default_ledger_id`),
  CONSTRAINT `acct_voucher_classes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `acct_voucher_classes_default_ledger_id_foreign` FOREIGN KEY (`default_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_voucher_entries`
--

DROP TABLE IF EXISTS `acct_voucher_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_voucher_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint(20) unsigned NOT NULL,
  `ledger_id` bigint(20) unsigned NOT NULL,
  `cost_centre_id` bigint(20) unsigned DEFAULT NULL,
  `business_unit_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `line_number` smallint(5) unsigned NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `base_debit` decimal(15,2) DEFAULT NULL,
  `base_credit` decimal(15,2) DEFAULT NULL,
  `narration` varchar(500) DEFAULT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `bank_transaction_type` varchar(30) DEFAULT NULL,
  `instrument_number` varchar(30) DEFAULT NULL,
  `instrument_date` date DEFAULT NULL,
  `payment_favouring` varchar(160) DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `bank_account_number` varchar(30) DEFAULT NULL,
  `bank_ifsc` varchar(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_voucher_entries_voucher_id_line_number_index` (`voucher_id`,`line_number`),
  KEY `acct_voucher_entries_ledger_id_index` (`ledger_id`),
  KEY `acct_voucher_entries_cost_centre_id_foreign` (`cost_centre_id`),
  KEY `acct_voucher_entries_business_unit_id_foreign` (`business_unit_id`),
  KEY `acct_voucher_entries_department_id_foreign` (`department_id`),
  CONSTRAINT `acct_voucher_entries_business_unit_id_foreign` FOREIGN KEY (`business_unit_id`) REFERENCES `acct_business_units` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_voucher_entries_cost_centre_id_foreign` FOREIGN KEY (`cost_centre_id`) REFERENCES `acct_cost_centres` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_voucher_entries_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `acct_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_voucher_entries_ledger_id_foreign` FOREIGN KEY (`ledger_id`) REFERENCES `acct_ledgers` (`id`),
  CONSTRAINT `acct_voucher_entries_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_voucher_inventory_lines`
--

DROP TABLE IF EXISTS `acct_voucher_inventory_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_voucher_inventory_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint(20) unsigned NOT NULL,
  `line_number` smallint(5) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `godown_id` bigint(20) unsigned DEFAULT NULL,
  `destination_godown_id` bigint(20) unsigned DEFAULT NULL,
  `batch_name` varchar(40) DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `rate` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_inventory_lines_unique` (`voucher_id`,`line_number`),
  KEY `acct_voucher_inventory_lines_product_id_foreign` (`product_id`),
  KEY `acct_voucher_inventory_lines_godown_id_foreign` (`godown_id`),
  KEY `acct_voucher_inventory_lines_destination_godown_id_foreign` (`destination_godown_id`),
  CONSTRAINT `acct_voucher_inventory_lines_destination_godown_id_foreign` FOREIGN KEY (`destination_godown_id`) REFERENCES `acct_godowns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_voucher_inventory_lines_godown_id_foreign` FOREIGN KEY (`godown_id`) REFERENCES `acct_godowns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_voucher_inventory_lines_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `acct_products` (`id`),
  CONSTRAINT `acct_voucher_inventory_lines_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_voucher_sequences`
--

DROP TABLE IF EXISTS `acct_voucher_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_voucher_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `voucher_type` varchar(20) NOT NULL,
  `period_key` varchar(16) NOT NULL DEFAULT '',
  `prefix` varchar(10) NOT NULL,
  `suffix` varchar(12) DEFAULT NULL,
  `next_number` int(10) unsigned NOT NULL DEFAULT 1,
  `padding` tinyint(3) unsigned NOT NULL DEFAULT 6,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_sequences_scope_unique` (`company_id`,`branch_id`,`financial_year_id`,`voucher_type`,`period_key`),
  KEY `acct_voucher_sequences_branch_id_foreign` (`branch_id`),
  KEY `acct_voucher_sequences_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `acct_voucher_sequences_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_voucher_sequences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_voucher_sequences_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_voucher_type_masters`
--

DROP TABLE IF EXISTS `acct_voucher_type_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_voucher_type_masters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(12) NOT NULL,
  `category` varchar(32) NOT NULL,
  `numbering_method` varchar(24) NOT NULL DEFAULT 'automatic',
  `prefix` varchar(12) NOT NULL,
  `suffix` varchar(12) DEFAULT NULL,
  `next_number` int(10) unsigned NOT NULL DEFAULT 1,
  `padding` tinyint(3) unsigned NOT NULL DEFAULT 6,
  `restart` varchar(16) NOT NULL DEFAULT 'yearly',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `acct_voucher_type_masters_company_id_abbreviation_unique` (`company_id`,`abbreviation`),
  CONSTRAINT `acct_voucher_type_masters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_vouchers`
--

DROP TABLE IF EXISTS `acct_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `voucher_type` varchar(20) NOT NULL,
  `voucher_number` varchar(30) NOT NULL,
  `voucher_date` date NOT NULL,
  `effective_date` date DEFAULT NULL,
  `party_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `reference_date` date DEFAULT NULL,
  `place_of_supply` varchar(80) DEFAULT NULL,
  `narration` varchar(1000) DEFAULT NULL,
  `name_on_receipt` varchar(160) DEFAULT NULL,
  `is_post_dated` tinyint(1) NOT NULL DEFAULT 0,
  `is_optional` tinyint(1) NOT NULL DEFAULT 0,
  `is_invoice` tinyint(1) NOT NULL DEFAULT 0,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `exchange_rate` decimal(15,6) DEFAULT NULL,
  `foreign_total` decimal(15,2) DEFAULT NULL,
  `payment_request_id` bigint(20) unsigned DEFAULT NULL,
  `merchant_profile_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `approval_status` varchar(20) NOT NULL DEFAULT 'none',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `total_debit` decimal(15,2) NOT NULL,
  `total_credit` decimal(15,2) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `voucher_class_id` bigint(20) unsigned DEFAULT NULL,
  `nature_of_payment` varchar(255) DEFAULT NULL,
  `is_memo` tinyint(1) NOT NULL DEFAULT 0,
  `reverses_on` date DEFAULT NULL,
  `reversed_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `corrected_voucher_id` bigint(20) unsigned DEFAULT NULL,
  `intercompany_pair_id` char(36) DEFAULT NULL,
  `counterparty_company_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vouchers_number_unique` (`company_id`,`branch_id`,`financial_year_id`,`voucher_type`,`voucher_number`),
  KEY `acct_vouchers_branch_id_foreign` (`branch_id`),
  KEY `acct_vouchers_financial_year_id_foreign` (`financial_year_id`),
  KEY `acct_vouchers_created_by_foreign` (`created_by`),
  KEY `vouchers_context_date_index` (`company_id`,`branch_id`,`financial_year_id`,`voucher_date`),
  KEY `acct_vouchers_company_id_status_index` (`company_id`,`status`),
  KEY `acct_vouchers_company_id_voucher_type_index` (`company_id`,`voucher_type`),
  KEY `vouchers_report_index` (`company_id`,`financial_year_id`,`status`,`voucher_date`),
  KEY `acct_vouchers_currency_id_foreign` (`currency_id`),
  KEY `acct_vouchers_payment_request_id_foreign` (`payment_request_id`),
  KEY `acct_vouchers_merchant_profile_id_foreign` (`merchant_profile_id`),
  KEY `acct_vouchers_voucher_class_id_foreign` (`voucher_class_id`),
  KEY `acct_vouchers_reversed_voucher_id_foreign` (`reversed_voucher_id`),
  KEY `acct_vouchers_corrected_voucher_id_foreign` (`corrected_voucher_id`),
  KEY `acct_vouchers_intercompany_pair_id_index` (`intercompany_pair_id`),
  KEY `acct_vouchers_counterparty_company_id_foreign` (`counterparty_company_id`),
  KEY `acct_vouchers_approved_by_foreign` (`approved_by`),
  KEY `acct_vouchers_party_ledger_id_foreign` (`party_ledger_id`),
  CONSTRAINT `acct_vouchers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `acct_branches` (`id`),
  CONSTRAINT `acct_vouchers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`),
  CONSTRAINT `acct_vouchers_corrected_voucher_id_foreign` FOREIGN KEY (`corrected_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_counterparty_company_id_foreign` FOREIGN KEY (`counterparty_company_id`) REFERENCES `acct_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `acct_currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `acct_financial_years` (`id`),
  CONSTRAINT `acct_vouchers_merchant_profile_id_foreign` FOREIGN KEY (`merchant_profile_id`) REFERENCES `acct_merchant_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_party_ledger_id_foreign` FOREIGN KEY (`party_ledger_id`) REFERENCES `acct_ledgers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_payment_request_id_foreign` FOREIGN KEY (`payment_request_id`) REFERENCES `acct_payment_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_reversed_voucher_id_foreign` FOREIGN KEY (`reversed_voucher_id`) REFERENCES `acct_vouchers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_vouchers_voucher_class_id_foreign` FOREIGN KEY (`voucher_class_id`) REFERENCES `acct_voucher_classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_webhook_deliveries`
--

DROP TABLE IF EXISTS `acct_webhook_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_webhook_deliveries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `webhook_endpoint_id` bigint(20) unsigned NOT NULL,
  `event` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `response_status` smallint(5) unsigned DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `next_attempt_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_webhook_deliveries_webhook_endpoint_id_foreign` (`webhook_endpoint_id`),
  KEY `acct_webhook_deliveries_status_next_attempt_at_index` (`status`,`next_attempt_at`),
  CONSTRAINT `acct_webhook_deliveries_webhook_endpoint_id_foreign` FOREIGN KEY (`webhook_endpoint_id`) REFERENCES `acct_webhook_endpoints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `acct_webhook_endpoints`
--

DROP TABLE IF EXISTS `acct_webhook_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acct_webhook_endpoints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `secret` text NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acct_webhook_endpoints_user_id_foreign` (`user_id`),
  KEY `acct_webhook_endpoints_company_id_foreign` (`company_id`),
  CONSTRAINT `acct_webhook_endpoints_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `acct_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_webhook_endpoints_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actor_name` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `approval_logs`
--

DROP TABLE IF EXISTS `approval_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actor_name` varchar(255) DEFAULT NULL,
  `approvable_type` varchar(255) DEFAULT NULL,
  `approvable_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `decision` varchar(30) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approval_logs_user_id_foreign` (`user_id`),
  KEY `approval_logs_approvable_type_approvable_id_index` (`approvable_type`,`approvable_id`),
  CONSTRAINT `approval_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `areas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `areas_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attendance_punches`
--

DROP TABLE IF EXISTS `attendance_punches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_punches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned DEFAULT NULL,
  `biometric_code` varchar(50) DEFAULT NULL,
  `punched_at` datetime NOT NULL,
  `direction` varchar(10) DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'essl',
  `device_id` varchar(80) DEFAULT NULL,
  `external_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_punches_source_external_id_unique` (`source`,`external_id`),
  KEY `attendance_punches_employee_id_punched_at_index` (`employee_id`,`punched_at`),
  KEY `attendance_punches_biometric_code_punched_at_index` (`biometric_code`,`punched_at`),
  CONSTRAINT `attendance_punches_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attendance_regularisations`
--

DROP TABLE IF EXISTS `attendance_regularisations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_regularisations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'missing_punch',
  `requested_check_in` time DEFAULT NULL,
  `requested_check_out` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_regularisations_employee_id_foreign` (`employee_id`),
  KEY `attendance_regularisations_approved_by_foreign` (`approved_by`),
  CONSTRAINT `attendance_regularisations_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_regularisations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'present',
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `late_minutes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `early_minutes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `ot_minutes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `source` varchar(20) NOT NULL DEFAULT 'manual',
  `is_regularised` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_employee_id_attendance_date_unique` (`employee_id`,`attendance_date`),
  KEY `attendances_recorded_by_foreign` (`recorded_by`),
  KEY `attendances_shift_id_foreign` (`shift_id`),
  CONSTRAINT `attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendances_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bank_account_transactions`
--

DROP TABLE IF EXISTS `bank_account_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_account_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_number` varchar(64) NOT NULL,
  `od_account_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `transaction_no` varchar(80) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `transaction_type` varchar(50) NOT NULL DEFAULT 'debit',
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_account_transactions_od_account_id_foreign` (`od_account_id`),
  KEY `bank_account_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `bank_account_transactions_created_by_foreign` (`created_by`),
  KEY `bat_comp_acc_date_idx` (`company_id`,`account_number`,`transaction_date`),
  KEY `bank_account_transactions_transaction_date_index` (`transaction_date`),
  CONSTRAINT `bank_account_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_account_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bank_account_transactions_od_account_id_foreign` FOREIGN KEY (`od_account_id`) REFERENCES `od_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `branch_types`
--

DROP TABLE IF EXISTS `branch_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(30) DEFAULT NULL,
  `branch_type` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `alternate_email` varchar(255) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_company_id_code_unique` (`company_id`,`code`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `detail` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_name_unique` (`name`),
  KEY `brands_company_id_foreign` (`company_id`),
  CONSTRAINT `brands_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `business_groups`
--

DROP TABLE IF EXISTS `business_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `proprietor_name` varchar(191) DEFAULT NULL,
  `proprietor_relationship` varchar(191) DEFAULT 'Common ownership and business oversight',
  `proprietor_email` varchar(191) DEFAULT NULL,
  `proprietor_phone` varchar(50) DEFAULT NULL,
  `proprietor_pan` varchar(20) DEFAULT NULL,
  `code` varchar(30) NOT NULL,
  `parent_company_id` bigint(20) unsigned DEFAULT NULL,
  `incorporation_date` date DEFAULT NULL,
  `headquarters` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `industry` varchar(150) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `logo_emoji` varchar(20) DEFAULT '?',
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_groups_code_unique` (`code`),
  KEY `business_groups_parent_company_id_foreign` (`parent_company_id`),
  CONSTRAINT `business_groups_parent_company_id_foreign` FOREIGN KEY (`parent_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_brand_id_name_unique` (`brand_id`,`name`),
  CONSTRAINT `categories_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cheque_books`
--

DROP TABLE IF EXISTS `cheque_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cheque_books` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `bank_account_no` varchar(64) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_book_name` varchar(150) DEFAULT NULL,
  `from_number` varchar(30) NOT NULL,
  `to_number` varchar(30) NOT NULL,
  `from_numeric` bigint(20) unsigned NOT NULL,
  `to_numeric` bigint(20) unsigned NOT NULL,
  `total_cheques` int(10) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `issue_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheque_books_created_by_foreign` (`created_by`),
  KEY `cheque_books_company_id_bank_account_no_index` (`company_id`,`bank_account_no`),
  KEY `cheque_books_company_id_bank_account_no_status_index` (`company_id`,`bank_account_no`,`status`),
  KEY `cb_comp_acc_range_idx` (`company_id`,`bank_account_no`,`from_numeric`,`to_numeric`),
  CONSTRAINT `cheque_books_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cheque_books_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cheque_bounces`
--

DROP TABLE IF EXISTS `cheque_bounces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cheque_bounces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cheque_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `bounced_on` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `charges` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bounce_number` int(10) unsigned NOT NULL DEFAULT 1,
  `triggered_freeze` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheque_bounces_cheque_id_foreign` (`cheque_id`),
  KEY `cheque_bounces_customer_id_foreign` (`customer_id`),
  KEY `cheque_bounces_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `cheque_bounces_cheque_id_foreign` FOREIGN KEY (`cheque_id`) REFERENCES `cheques` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cheque_bounces_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cheque_bounces_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cheques`
--

DROP TABLE IF EXISTS `cheques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cheques` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_no` varchar(64) DEFAULT NULL,
  `cheque_book_id` bigint(20) unsigned DEFAULT NULL,
  `cheque_no` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `purpose` enum('security','pdc','credit') NOT NULL DEFAULT 'pdc',
  `direction` varchar(40) NOT NULL DEFAULT 'received_from_client',
  `cheque_type` varchar(20) NOT NULL DEFAULT 'received',
  `amount` decimal(14,2) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `deposit_date` date DEFAULT NULL,
  `clearance_date` date DEFAULT NULL,
  `stale_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'received',
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `reference_voucher` varchar(100) DEFAULT NULL,
  `voucher_type` varchar(50) DEFAULT NULL,
  `voucher_id` bigint(20) unsigned DEFAULT NULL,
  `bounce_count` int(10) unsigned NOT NULL DEFAULT 0,
  `bounced_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_reason` varchar(255) DEFAULT NULL,
  `bounce_reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cheques_payment_id_foreign` (`payment_id`),
  KEY `cheques_recorded_by_foreign` (`recorded_by`),
  KEY `cheques_customer_id_status_index` (`customer_id`,`status`),
  KEY `cheques_cheque_no_bank_name_index` (`cheque_no`,`bank_name`),
  KEY `cheques_company_id_foreign` (`company_id`),
  KEY `cheques_cheque_book_id_foreign` (`cheque_book_id`),
  CONSTRAINT `cheques_cheque_book_id_foreign` FOREIGN KEY (`cheque_book_id`) REFERENCES `cheque_books` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cheques_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cheques_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cheques_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cheques_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_logs`
--

DROP TABLE IF EXISTS `communication_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(40) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'sent',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `sent_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_logs_invoice_id_foreign` (`invoice_id`),
  KEY `communication_logs_customer_id_foreign` (`customer_id`),
  KEY `communication_logs_sent_by_foreign` (`sent_by`),
  CONSTRAINT `communication_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `communication_logs_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `communication_logs_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_group_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `company_type` varchar(50) DEFAULT NULL,
  `business_type` varchar(100) DEFAULT 'Existing Business',
  `incorporation_no` varchar(50) DEFAULT NULL,
  `incorporation_date` date DEFAULT NULL,
  `logo_emoji` varchar(20) DEFAULT '?',
  `gstin` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `cin` varchar(30) DEFAULT NULL,
  `tan` varchar(20) DEFAULT NULL,
  `udyam_registration_no` varchar(30) DEFAULT NULL,
  `msme_category` enum('none','micro','small','medium') NOT NULL DEFAULT 'none',
  `msme_registration_no` varchar(30) DEFAULT NULL,
  `iec_code` varchar(30) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `pincode` varchar(12) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `nature_of_business` varchar(255) DEFAULT NULL,
  `main_work` text DEFAULT NULL,
  `authorized_capital` decimal(15,2) DEFAULT NULL,
  `paid_up_capital` decimal(15,2) DEFAULT NULL,
  `annual_turnover` decimal(15,2) DEFAULT NULL,
  `financial_year` varchar(30) DEFAULT NULL,
  `accounting_software` varchar(50) DEFAULT NULL,
  `branches_count_manual` int(10) unsigned DEFAULT 0,
  `employees_count_manual` int(10) unsigned DEFAULT 0,
  `relationship_type` varchar(50) DEFAULT 'Sister Concern',
  `shareholding_percentage` decimal(5,2) DEFAULT 0.00,
  `compliance_status` varchar(50) DEFAULT 'Compliant',
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `bank_ifsc` varchar(20) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `swift_code` varchar(50) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `bsr_code` varchar(50) DEFAULT NULL,
  `client_code` varchar(50) DEFAULT NULL,
  `od_limit` decimal(15,2) DEFAULT 0.00,
  `interest_rate` decimal(6,2) DEFAULT 0.00,
  `additional_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_details`)),
  `purchase_terms_and_conditions` text DEFAULT NULL,
  `selling_terms_and_conditions` text DEFAULT NULL,
  `due_date_basis` enum('invoice_date','inward_date') NOT NULL DEFAULT 'invoice_date',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_code_unique` (`code`),
  KEY `companies_business_group_id_foreign` (`business_group_id`),
  CONSTRAINT `companies_business_group_id_foreign` FOREIGN KEY (`business_group_id`) REFERENCES `business_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_delivery_addresses`
--

DROP TABLE IF EXISTS `company_delivery_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_delivery_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `address_type` varchar(120) NOT NULL,
  `mailing_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `pincode` varchar(20) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_delivery_addresses_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `company_delivery_addresses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_group_links`
--

DROP TABLE IF EXISTS `company_group_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_group_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_group_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_group_links_business_group_id_company_id_unique` (`business_group_id`,`company_id`),
  KEY `company_group_links_company_id_foreign` (`company_id`),
  CONSTRAINT `company_group_links_business_group_id_foreign` FOREIGN KEY (`business_group_id`) REFERENCES `business_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_group_links_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `credit_note_items`
--

DROP TABLE IF EXISTS `credit_note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_note_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `uom_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_items_credit_note_id_foreign` (`credit_note_id`),
  KEY `credit_note_items_product_id_foreign` (`product_id`),
  KEY `credit_note_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `credit_note_items_credit_note_id_foreign` FOREIGN KEY (`credit_note_id`) REFERENCES `credit_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_note_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_note_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `credit_notes`
--

DROP TABLE IF EXISTS `credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_no` varchar(30) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `credit_note_date` date NOT NULL,
  `reason` enum('return','price','scheme','damage','settlement','interest_reversal','other') NOT NULL DEFAULT 'other',
  `status` enum('draft','approved','posted','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `affects_stock` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by_name` varchar(100) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_notes_credit_note_no_unique` (`credit_note_no`),
  KEY `credit_notes_customer_id_foreign` (`customer_id`),
  KEY `credit_notes_invoice_id_foreign` (`invoice_id`),
  KEY `credit_notes_approved_by_foreign` (`approved_by`),
  CONSTRAINT `credit_notes_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `credit_notes_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_cities`
--

DROP TABLE IF EXISTS `custom_cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_cities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_cities_state_name_unique` (`state`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customer_types`
--

DROP TABLE IF EXISTS `customer_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_types_name_unique` (`name`),
  UNIQUE KEY `customer_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `trade_name` varchar(255) DEFAULT NULL,
  `code` varchar(30) NOT NULL,
  `party_type` varchar(50) NOT NULL DEFAULT 'sundry_debtors',
  `customer_type_id` bigint(20) unsigned DEFAULT NULL,
  `business_group_id` bigint(20) unsigned DEFAULT NULL,
  `area_id` bigint(20) unsigned DEFAULT NULL,
  `route_id` bigint(20) unsigned DEFAULT NULL,
  `salesperson_id` bigint(20) unsigned DEFAULT NULL,
  `sales_manager_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `opened_on` date DEFAULT NULL,
  `reference_by` varchar(255) DEFAULT NULL,
  `udyam_number` varchar(50) DEFAULT NULL,
  `gem_registered` tinyint(1) NOT NULL DEFAULT 0,
  `gem_authorization_code` varchar(100) DEFAULT NULL,
  `gem_valid_until` date DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `location_link` varchar(500) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `shipping_name` varchar(255) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_pincode` varchar(12) DEFAULT NULL,
  `shipping_gstin` varchar(20) DEFAULT NULL,
  `credit_limit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit_days` int(10) unsigned NOT NULL DEFAULT 0,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 18.00,
  `credit_period_basis` varchar(30) NOT NULL DEFAULT 'cumulative',
  `target_amount` decimal(12,2) DEFAULT NULL,
  `target_start_date` date DEFAULT NULL,
  `target_end_date` date DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `credit_status` varchar(30) NOT NULL DEFAULT 'open',
  `risk_status` enum('normal','watch','high') NOT NULL DEFAULT 'normal',
  `cheque_bounce_count` int(10) unsigned NOT NULL DEFAULT 0,
  `credit_notes` text DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `gst_registration_type` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_code_unique` (`code`),
  KEY `customers_area_id_foreign` (`area_id`),
  KEY `customers_route_id_foreign` (`route_id`),
  KEY `customers_salesperson_id_foreign` (`salesperson_id`),
  KEY `customers_company_id_foreign` (`company_id`),
  KEY `customers_branch_id_foreign` (`branch_id`),
  KEY `customers_sales_manager_id_foreign` (`sales_manager_id`),
  KEY `customers_business_group_id_foreign` (`business_group_id`),
  KEY `customers_customer_type_id_foreign` (`customer_type_id`),
  CONSTRAINT `customers_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_business_group_id_foreign` FOREIGN KEY (`business_group_id`) REFERENCES `business_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_customer_type_id_foreign` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_sales_manager_id_foreign` FOREIGN KEY (`sales_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deal_expenses`
--

DROP TABLE IF EXISTS `deal_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deal_expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deal_id` bigint(20) unsigned NOT NULL,
  `expense_type_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `party_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','rejected','posted') NOT NULL DEFAULT 'draft',
  `created_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deal_expenses_expense_type_id_foreign` (`expense_type_id`),
  KEY `deal_expenses_deal_id_status_index` (`deal_id`,`status`),
  CONSTRAINT `deal_expenses_deal_id_foreign` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deal_expenses_expense_type_id_foreign` FOREIGN KEY (`expense_type_id`) REFERENCES `expense_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deals`
--

DROP TABLE IF EXISTS `deals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(40) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deals_reference_unique` (`reference`),
  KEY `deals_invoice_id_foreign` (`invoice_id`),
  KEY `deals_order_id_foreign` (`order_id`),
  KEY `deals_customer_id_status_index` (`customer_id`,`status`),
  CONSTRAINT `deals_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deals_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deals_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deliveries`
--

DROP TABLE IF EXISTS `deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliveries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `load_sheet_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `status` enum('pending','out_for_delivery','delivered','partial','returned') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deliveries_load_sheet_id_foreign` (`load_sheet_id`),
  KEY `deliveries_customer_id_foreign` (`customer_id`),
  KEY `deliveries_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `deliveries_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `deliveries_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `deliveries_load_sheet_id_foreign` FOREIGN KEY (`load_sheet_id`) REFERENCES `load_sheets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_items`
--

DROP TABLE IF EXISTS `delivery_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `loaded_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `delivered_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `short_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `returned_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_items_delivery_id_foreign` (`delivery_id`),
  KEY `delivery_items_product_id_foreign` (`product_id`),
  KEY `delivery_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `delivery_items_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `delivery_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `delivery_persons`
--

DROP TABLE IF EXISTS `delivery_persons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_persons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `demo_stock_notices`
--

DROP TABLE IF EXISTS `demo_stock_notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `demo_stock_notices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `batch_no` varchar(60) DEFAULT NULL,
  `notice_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `status` enum('out','returned','converted','cancelled') NOT NULL DEFAULT 'out',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demo_stock_notices_product_id_foreign` (`product_id`),
  KEY `demo_stock_notices_warehouse_id_foreign` (`warehouse_id`),
  KEY `demo_stock_notices_customer_id_foreign` (`customer_id`),
  KEY `demo_stock_notices_created_by_foreign` (`created_by`),
  CONSTRAINT `demo_stock_notices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `demo_stock_notices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `demo_stock_notices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `demo_stock_notices_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departments_company_id_foreign` (`company_id`),
  CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `designations`
--

DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `designations_company_id_foreign` (`company_id`),
  CONSTRAINT `designations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `document_number_series`
--

DROP TABLE IF EXISTS `document_number_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_number_series` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(40) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `prefix` varchar(40) NOT NULL DEFAULT '',
  `suffix` varchar(40) DEFAULT NULL,
  `include_financial_year` tinyint(1) NOT NULL DEFAULT 1,
  `financial_year_style` varchar(20) NOT NULL DEFAULT 'yy_yy',
  `separator` varchar(5) NOT NULL DEFAULT '-',
  `padding` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `starting_number` int(10) unsigned NOT NULL DEFAULT 1,
  `prefix_zero` tinyint(1) NOT NULL DEFAULT 1,
  `numbering_method` varchar(50) NOT NULL DEFAULT 'automatic',
  `restart_each_fy` tinyint(1) NOT NULL DEFAULT 1,
  `restart_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`restart_rules`)),
  `prefix_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prefix_rules`)),
  `suffix_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suffix_rules`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_number_series_company_id_document_type_unique` (`company_id`,`document_type`),
  CONSTRAINT `document_number_series_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `document_sequences`
--

DROP TABLE IF EXISTS `document_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document_sequences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `prefix` varchar(20) NOT NULL,
  `series_key` varchar(120) DEFAULT NULL,
  `sequence_date` date NOT NULL,
  `last_number` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_sequences_prefix_date_unique` (`prefix`,`sequence_date`),
  KEY `document_sequences_company_id_foreign` (`company_id`),
  KEY `document_sequences_branch_id_foreign` (`branch_id`),
  KEY `document_sequences_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `document_sequences_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `document_sequences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `document_sequences_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `drivers`
--

DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drivers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `license_no` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `e_invoices`
--

DROP TABLE IF EXISTS `e_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `e_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `provider` varchar(30) NOT NULL DEFAULT 'mastersindia',
  `irn` varchar(255) DEFAULT NULL,
  `ack_no` varchar(40) DEFAULT NULL,
  `ack_date` timestamp NULL DEFAULT NULL,
  `signed_invoice` longtext DEFAULT NULL,
  `signed_qr_base64` longtext DEFAULT NULL,
  `qr_image_path` varchar(255) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `cancel_reason` varchar(50) DEFAULT NULL,
  `cancel_remarks` varchar(255) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `e_invoices_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `e_invoices_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `e_way_bills`
--

DROP TABLE IF EXISTS `e_way_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `e_way_bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `provider` varchar(30) NOT NULL DEFAULT 'mastersindia',
  `last_error` text DEFAULT NULL,
  `cancel_reason` varchar(50) DEFAULT NULL,
  `cancel_remarks` varchar(255) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `eway_bill_no` varchar(255) DEFAULT NULL,
  `qr_token` varchar(36) DEFAULT NULL,
  `valid_upto` timestamp NULL DEFAULT NULL,
  `ewb_date` timestamp NULL DEFAULT NULL,
  `distance_km` int(10) unsigned DEFAULT NULL,
  `transporter_id` varchar(20) DEFAULT NULL,
  `transporter_name` varchar(255) DEFAULT NULL,
  `vehicle_no` varchar(40) DEFAULT NULL,
  `vehicle_type` varchar(10) NOT NULL DEFAULT 'R',
  `from_pincode` varchar(10) DEFAULT NULL,
  `to_pincode` varchar(10) DEFAULT NULL,
  `from_state` varchar(100) DEFAULT NULL,
  `to_state` varchar(100) DEFAULT NULL,
  `transport_mode` varchar(20) DEFAULT NULL,
  `doc_no` varchar(60) DEFAULT NULL,
  `doc_date` date DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `e_way_bills_qr_token_unique` (`qr_token`),
  KEY `e_way_bills_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `e_way_bills_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_documents`
--

DROP TABLE IF EXISTS `employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(80) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `document_number` varchar(255) DEFAULT NULL,
  `issued_on` date DEFAULT NULL,
  `expires_on` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_documents_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_exits`
--

DROP TABLE IF EXISTS `employee_exits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_exits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `resignation_date` date DEFAULT NULL,
  `last_working_date` date DEFAULT NULL,
  `notice_period_days` int(10) unsigned DEFAULT NULL,
  `exit_type` varchar(40) NOT NULL DEFAULT 'resignation',
  `clearance_status` varchar(30) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_exits_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_exits_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_incentives`
--

DROP TABLE IF EXISTS `employee_incentives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_incentives` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `period_label` varchar(50) DEFAULT NULL,
  `target_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achievement_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `incentive_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'provisional',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_incentives_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_incentives_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_kpis`
--

DROP TABLE IF EXISTS `employee_kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_kpis` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `kpi_name` varchar(255) NOT NULL,
  `period_label` varchar(50) DEFAULT NULL,
  `target_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achievement_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `rating` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_kpis_employee_id_foreign` (`employee_id`),
  CONSTRAINT `employee_kpis_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_shifts`
--

DROP TABLE IF EXISTS `employee_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `weekly_offs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`weekly_offs`)),
  `weekly_off_mode` varchar(20) NOT NULL DEFAULT 'fixed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_shifts_employee_id_foreign` (`employee_id`),
  KEY `employee_shifts_shift_id_foreign` (`shift_id`),
  CONSTRAINT `employee_shifts_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_shifts_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `employee_code` varchar(40) NOT NULL,
  `biometric_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `personal_email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `alternate_contact_number` varchar(20) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `aadhaar_number` varchar(20) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `is_salesperson` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `address` text DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(80) DEFAULT NULL,
  `bank_account_holder` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(40) DEFAULT NULL,
  `bank_account_type` varchar(40) DEFAULT NULL,
  `bank_ifsc` varchar(20) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `cancelled_cheque_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_employee_code_unique` (`employee_code`),
  KEY `employees_company_id_foreign` (`company_id`),
  KEY `employees_branch_id_foreign` (`branch_id`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_department_id_foreign` (`department_id`),
  KEY `employees_designation_id_foreign` (`designation_id`),
  KEY `employees_manager_id_foreign` (`manager_id`),
  KEY `employees_biometric_code_index` (`biometric_code`),
  CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `essl_sync_logs`
--

DROP TABLE IF EXISTS `essl_sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `essl_sync_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `finished_at` timestamp NULL DEFAULT NULL,
  `fetched` int(10) unsigned NOT NULL DEFAULT 0,
  `imported` int(10) unsigned NOT NULL DEFAULT 0,
  `unmatched` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'running',
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expense_approvals`
--

DROP TABLE IF EXISTS `expense_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deal_expense_id` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `approver_name` varchar(100) DEFAULT NULL,
  `decision` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_approvals_deal_expense_id_foreign` (`deal_expense_id`),
  KEY `expense_approvals_approver_id_foreign` (`approver_id`),
  CONSTRAINT `expense_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_approvals_deal_expense_id_foreign` FOREIGN KEY (`deal_expense_id`) REFERENCES `deal_expenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expense_claims`
--

DROP TABLE IF EXISTS `expense_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `claim_date` date NOT NULL,
  `claim_type` varchar(80) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `settlement_amount` decimal(12,2) DEFAULT NULL,
  `settlement_reference` varchar(100) DEFAULT NULL,
  `settlement_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `settled_by` bigint(20) unsigned DEFAULT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_claims_employee_id_foreign` (`employee_id`),
  KEY `expense_claims_approved_by_foreign` (`approved_by`),
  KEY `expense_claims_settled_by_foreign` (`settled_by`),
  CONSTRAINT `expense_claims_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `expense_claims_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `expense_claims_settled_by_foreign` FOREIGN KEY (`settled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `expense_types`
--

DROP TABLE IF EXISTS `expense_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `accounting_treatment` enum('trade_discount','deal_expense','landed_cost') NOT NULL DEFAULT 'deal_expense',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `financial_years`
--

DROP TABLE IF EXISTS `financial_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `financial_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financial_years_company_id_foreign` (`company_id`),
  KEY `financial_years_closed_by_foreign` (`closed_by`),
  CONSTRAINT `financial_years_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_years_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `freight_bill_allocations`
--

DROP TABLE IF EXISTS `freight_bill_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `freight_bill_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `freight_bill_id` bigint(20) unsigned NOT NULL,
  `allocatable_type` varchar(255) DEFAULT NULL,
  `allocatable_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `freight_bill_allocations_freight_bill_id_foreign` (`freight_bill_id`),
  KEY `freight_bill_allocations_allocatable_type_allocatable_id_index` (`allocatable_type`,`allocatable_id`),
  CONSTRAINT `freight_bill_allocations_freight_bill_id_foreign` FOREIGN KEY (`freight_bill_id`) REFERENCES `freight_bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `freight_bills`
--

DROP TABLE IF EXISTS `freight_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `freight_bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `freight_bills_freight_no_unique` (`freight_no`),
  KEY `freight_bills_created_by_foreign` (`created_by`),
  CONSTRAINT `freight_bills_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `holiday_calendars`
--

DROP TABLE IF EXISTS `holiday_calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `holiday_calendars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holiday_calendars_scope_unique` (`company_id`,`branch_id`,`year`,`name`),
  KEY `holiday_calendars_branch_id_foreign` (`branch_id`),
  CONSTRAINT `holiday_calendars_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `holiday_calendars_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `holidays` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `holiday_calendar_id` bigint(20) unsigned NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'festival',
  `kind` varchar(30) NOT NULL DEFAULT 'compulsory',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holidays_calendar_date_name_unique` (`holiday_calendar_id`,`holiday_date`,`name`),
  CONSTRAINT `holidays_holiday_calendar_id_foreign` FOREIGN KEY (`holiday_calendar_id`) REFERENCES `holiday_calendars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inter_company_transactions`
--

DROP TABLE IF EXISTS `inter_company_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inter_company_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_no` varchar(40) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `from_company_id` bigint(20) unsigned NOT NULL,
  `to_company_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `exchange_rate` decimal(10,4) NOT NULL DEFAULT 1.0000,
  `is_gst_applicable` tinyint(1) NOT NULL DEFAULT 0,
  `igst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_tds_applicable` tinyint(1) NOT NULL DEFAULT 0,
  `tds_section` varchar(50) DEFAULT NULL,
  `tds_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tds_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL,
  `reference_invoice` varchar(100) DEFAULT NULL,
  `reference_po` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Pending',
  `narration` text DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inter_company_transactions_transaction_no_unique` (`transaction_no`),
  KEY `inter_company_transactions_to_company_id_foreign` (`to_company_id`),
  KEY `inter_company_transactions_approved_by_foreign` (`approved_by`),
  KEY `inter_company_transactions_created_by_foreign` (`created_by`),
  KEY `inter_company_transactions_updated_by_foreign` (`updated_by`),
  KEY `inter_company_transactions_transaction_date_status_index` (`transaction_date`,`status`),
  KEY `inter_company_transactions_from_company_id_to_company_id_index` (`from_company_id`,`to_company_id`),
  KEY `inter_company_transactions_transaction_type_index` (`transaction_type`),
  CONSTRAINT `inter_company_transactions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inter_company_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inter_company_transactions_from_company_id_foreign` FOREIGN KEY (`from_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inter_company_transactions_to_company_id_foreign` FOREIGN KEY (`to_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inter_company_transactions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `interest_documents`
--

DROP TABLE IF EXISTS `interest_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interest_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_no` varchar(40) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `interest_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `document_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interest_documents_document_no_unique` (`document_no`),
  KEY `interest_documents_customer_id_foreign` (`customer_id`),
  KEY `interest_documents_interest_ledger_id_foreign` (`interest_ledger_id`),
  CONSTRAINT `interest_documents_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interest_documents_interest_ledger_id_foreign` FOREIGN KEY (`interest_ledger_id`) REFERENCES `interest_ledgers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `interest_ledgers`
--

DROP TABLE IF EXISTS `interest_ledgers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interest_ledgers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `interest_rule_id` bigint(20) unsigned DEFAULT NULL,
  `as_of_date` date NOT NULL,
  `overdue_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `overdue_days` int(10) unsigned NOT NULL DEFAULT 0,
  `annual_rate` decimal(8,2) NOT NULL DEFAULT 18.00,
  `interest_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('preview','posted','waived','reversed') NOT NULL DEFAULT 'preview',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interest_ledgers_interest_rule_id_foreign` (`interest_rule_id`),
  KEY `interest_ledgers_customer_id_as_of_date_status_index` (`customer_id`,`as_of_date`,`status`),
  KEY `interest_ledgers_invoice_id_as_of_date_index` (`invoice_id`,`as_of_date`),
  CONSTRAINT `interest_ledgers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interest_ledgers_interest_rule_id_foreign` FOREIGN KEY (`interest_rule_id`) REFERENCES `interest_rules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interest_ledgers_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `interest_rules`
--

DROP TABLE IF EXISTS `interest_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interest_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `annual_rate` decimal(8,2) NOT NULL DEFAULT 18.00,
  `grace_days` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interest_rules_customer_id_foreign` (`customer_id`),
  CONSTRAINT `interest_rules_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `internal_delivery_challans`
--

DROP TABLE IF EXISTS `internal_delivery_challans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internal_delivery_challans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `challan_no` varchar(40) NOT NULL,
  `challan_date` date NOT NULL,
  `from_warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `to_warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `stock_transfer_id` bigint(20) unsigned DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `status` enum('draft','dispatched','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `internal_delivery_challans_challan_no_unique` (`challan_no`),
  KEY `internal_delivery_challans_from_warehouse_id_foreign` (`from_warehouse_id`),
  KEY `internal_delivery_challans_to_warehouse_id_foreign` (`to_warehouse_id`),
  KEY `internal_delivery_challans_stock_transfer_id_foreign` (`stock_transfer_id`),
  KEY `internal_delivery_challans_created_by_foreign` (`created_by`),
  CONSTRAINT `internal_delivery_challans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internal_delivery_challans_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internal_delivery_challans_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internal_delivery_challans_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `price_type` varchar(20) NOT NULL DEFAULT 'exclusive',
  `entered_unit_price` decimal(12,2) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('percent','flat') NOT NULL DEFAULT 'flat',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `cgst_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `sgst_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `igst_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `hsn_code` varchar(20) DEFAULT NULL,
  `batch_no` varchar(60) DEFAULT NULL,
  `serial_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`serial_numbers`)),
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_items_product_id_foreign` (`product_id`),
  KEY `invoice_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `invoice_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(30) NOT NULL,
  `qr_token` varchar(36) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `scheme_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `salesperson_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `due_date_basis` enum('invoice_date','inward_date') NOT NULL DEFAULT 'invoice_date',
  `due_date_source_date` date DEFAULT NULL,
  `vehicle_no` varchar(40) DEFAULT NULL,
  `vehicle_type` varchar(10) NOT NULL DEFAULT 'R',
  `transport_mode` varchar(30) DEFAULT NULL,
  `reference_no` varchar(60) DEFAULT NULL,
  `delivery_note_no` varchar(255) DEFAULT NULL,
  `delivery_note_na` tinyint(1) NOT NULL DEFAULT 0,
  `dispatch_doc_no` varchar(100) DEFAULT NULL,
  `dispatched_through` varchar(150) DEFAULT NULL,
  `destination` varchar(150) DEFAULT NULL,
  `distance_km` int(10) unsigned DEFAULT NULL,
  `carrier_name` varchar(150) DEFAULT NULL,
  `transporter_id` varchar(25) DEFAULT NULL,
  `transporter_name` varchar(255) DEFAULT NULL,
  `lr_rr_no` varchar(100) DEFAULT NULL,
  `lr_rr_date` date DEFAULT NULL,
  `order_no` varchar(100) DEFAULT NULL,
  `other_references` varchar(255) DEFAULT NULL,
  `terms_of_delivery` text DEFAULT NULL,
  `delivery_state` varchar(100) DEFAULT NULL,
  `bill_to_place` varchar(100) DEFAULT NULL,
  `ship_to_place` varchar(100) DEFAULT NULL,
  `place_of_supply_state` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `credit_days` int(10) unsigned NOT NULL DEFAULT 0,
  `price_type` varchar(20) NOT NULL DEFAULT 'exclusive',
  `tax_type` varchar(20) DEFAULT NULL,
  `status` enum('draft','issued','paid','partial','cancelled') NOT NULL DEFAULT 'issued',
  `einvoice_status` varchar(25) NOT NULL DEFAULT 'pending',
  `eway_bill_status` varchar(25) NOT NULL DEFAULT 'pending',
  `eway_bill_required` varchar(20) NOT NULL DEFAULT 'auto',
  `eway_bill_no` varchar(50) DEFAULT NULL,
  `eway_bill_date` date DEFAULT NULL,
  `eway_sub_type` varchar(50) NOT NULL DEFAULT 'Supply',
  `eway_doc_type` varchar(50) NOT NULL DEFAULT 'Tax Invoice',
  `eway_supply_type` varchar(50) NOT NULL DEFAULT 'Outward',
  `consignor_name` varchar(255) DEFAULT NULL,
  `consignor_gstin` varchar(25) DEFAULT NULL,
  `consignor_state` varchar(100) DEFAULT NULL,
  `consignor_address_type` varchar(100) DEFAULT NULL,
  `consignor_address_line_1` varchar(255) DEFAULT NULL,
  `consignor_address_line_2` varchar(255) DEFAULT NULL,
  `consignor_pincode` varchar(20) DEFAULT NULL,
  `consignor_city` varchar(100) DEFAULT NULL,
  `consignor_actual_state` varchar(100) DEFAULT NULL,
  `consignee_name` varchar(255) DEFAULT NULL,
  `consignee_gstin` varchar(25) DEFAULT NULL,
  `consignee_state` varchar(100) DEFAULT NULL,
  `consignee_address_type` varchar(100) DEFAULT NULL,
  `consignee_address_line_1` varchar(255) DEFAULT NULL,
  `consignee_address_line_2` varchar(255) DEFAULT NULL,
  `consignee_pincode` varchar(20) DEFAULT NULL,
  `consignee_city` varchar(100) DEFAULT NULL,
  `consignee_actual_state` varchar(100) DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `universal_discount_type` enum('percent','flat') NOT NULL DEFAULT 'flat',
  `universal_discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `item_discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `round_off` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `scheme_benefit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms_and_conditions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_no_unique` (`invoice_no`),
  UNIQUE KEY `invoices_order_id_unique` (`order_id`),
  UNIQUE KEY `invoices_qr_token_unique` (`qr_token`),
  KEY `invoices_customer_id_foreign` (`customer_id`),
  KEY `invoices_salesperson_id_foreign` (`salesperson_id`),
  KEY `invoices_billing_address_id_foreign` (`billing_address_id`),
  KEY `invoices_shipping_address_id_foreign` (`shipping_address_id`),
  KEY `invoices_scheme_id_foreign` (`scheme_id`),
  KEY `invoices_company_id_foreign` (`company_id`),
  KEY `invoices_einvoice_status_index` (`einvoice_status`),
  KEY `invoices_eway_bill_status_index` (`eway_bill_status`),
  CONSTRAINT `invoices_billing_address_id_foreign` FOREIGN KEY (`billing_address_id`) REFERENCES `party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `party_addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `landed_cost_items`
--

DROP TABLE IF EXISTS `landed_cost_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `landed_cost_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `landed_cost_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `base_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `weight` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `volume` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `allocated_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `landed_unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `landed_cost_items_landed_cost_id_foreign` (`landed_cost_id`),
  KEY `landed_cost_items_product_id_foreign` (`product_id`),
  KEY `landed_cost_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `landed_cost_items_landed_cost_id_foreign` FOREIGN KEY (`landed_cost_id`) REFERENCES `landed_costs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `landed_cost_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `landed_cost_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `landed_costs`
--

DROP TABLE IF EXISTS `landed_costs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `landed_costs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `landed_no` varchar(40) NOT NULL,
  `landed_date` date NOT NULL,
  `purchase_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `freight_bill_id` bigint(20) unsigned DEFAULT NULL,
  `allocation_method` enum('qty','value','weight','volume','equal','manual') NOT NULL DEFAULT 'value',
  `total_additional_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `landed_costs_landed_no_unique` (`landed_no`),
  KEY `landed_costs_purchase_invoice_id_foreign` (`purchase_invoice_id`),
  KEY `landed_costs_freight_bill_id_foreign` (`freight_bill_id`),
  KEY `landed_costs_created_by_foreign` (`created_by`),
  CONSTRAINT `landed_costs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `landed_costs_freight_bill_id_foreign` FOREIGN KEY (`freight_bill_id`) REFERENCES `freight_bills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `landed_costs_purchase_invoice_id_foreign` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lead_activities`
--

DROP TABLE IF EXISTS `lead_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `activity_type` varchar(40) NOT NULL,
  `body` text DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_activities_lead_id_foreign` (`lead_id`),
  KEY `lead_activities_user_id_foreign` (`user_id`),
  CONSTRAINT `lead_activities_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lead_assignments`
--

DROP TABLE IF EXISTS `lead_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `method` varchar(40) NOT NULL DEFAULT 'manual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_assignments_lead_id_foreign` (`lead_id`),
  KEY `lead_assignments_assigned_to_foreign` (`assigned_to`),
  KEY `lead_assignments_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `lead_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_assignments_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_assignments_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lead_campaigns`
--

DROP TABLE IF EXISTS `lead_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `external_campaign_id` varchar(255) DEFAULT NULL,
  `platform` varchar(40) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_campaigns_external_campaign_id_index` (`external_campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lead_conversions`
--

DROP TABLE IF EXISTS `lead_conversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_conversions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `converted_by` bigint(20) unsigned DEFAULT NULL,
  `converted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_conversions_lead_id_foreign` (`lead_id`),
  KEY `lead_conversions_customer_id_foreign` (`customer_id`),
  KEY `lead_conversions_converted_by_foreign` (`converted_by`),
  CONSTRAINT `lead_conversions_converted_by_foreign` FOREIGN KEY (`converted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_conversions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_conversions_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lead_followups`
--

DROP TABLE IF EXISTS `lead_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_followups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `due_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `channel` varchar(40) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_followups_lead_id_foreign` (`lead_id`),
  KEY `lead_followups_user_id_foreign` (`user_id`),
  CONSTRAINT `lead_followups_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_followups_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lead_segments`
--

DROP TABLE IF EXISTS `lead_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_segments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`criteria`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_segments_branch_id_foreign` (`branch_id`),
  KEY `lead_segments_created_by_foreign` (`created_by`),
  KEY `lead_segments_updated_by_foreign` (`updated_by`),
  KEY `lead_segments_company_id_created_at_index` (`company_id`,`created_at`),
  CONSTRAINT `lead_segments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_segments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_segments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lead_segments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lead_sources`
--

DROP TABLE IF EXISTS `lead_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(40) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_sources_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `lead_source_id` bigint(20) unsigned DEFAULT NULL,
  `lead_campaign_id` bigint(20) unsigned DEFAULT NULL,
  `meta_lead_form_id` bigint(20) unsigned DEFAULT NULL,
  `external_lead_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `secondary_mobile` varchar(50) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `landline` varchar(50) DEFAULT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `sub_category_id` bigint(20) unsigned DEFAULT NULL,
  `sub_category` varchar(255) DEFAULT NULL,
  `street` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `secondary_email` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip` varchar(30) DEFAULT NULL,
  `interested_product` varchar(255) DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `status` varchar(40) NOT NULL DEFAULT 'new',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `last_contacted_at` timestamp NULL DEFAULT NULL,
  `next_followup_at` timestamp NULL DEFAULT NULL,
  `lead_score` decimal(8,2) DEFAULT NULL,
  `lost_reason` varchar(255) DEFAULT NULL,
  `converted_customer_id` bigint(20) unsigned DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `meta_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_payload`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leads_external_lead_id_unique` (`external_lead_id`),
  KEY `leads_company_id_foreign` (`company_id`),
  KEY `leads_branch_id_foreign` (`branch_id`),
  KEY `leads_lead_source_id_foreign` (`lead_source_id`),
  KEY `leads_lead_campaign_id_foreign` (`lead_campaign_id`),
  KEY `leads_meta_lead_form_id_foreign` (`meta_lead_form_id`),
  KEY `leads_assigned_to_foreign` (`assigned_to`),
  KEY `leads_converted_customer_id_foreign` (`converted_customer_id`),
  KEY `leads_mobile_index` (`mobile`),
  KEY `leads_email_index` (`email`),
  KEY `leads_sub_category_id_foreign` (`sub_category_id`),
  KEY `leads_category_index` (`category`),
  KEY `leads_industry_index` (`industry`),
  CONSTRAINT `leads_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_converted_customer_id_foreign` FOREIGN KEY (`converted_customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_lead_campaign_id_foreign` FOREIGN KEY (`lead_campaign_id`) REFERENCES `lead_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_lead_source_id_foreign` FOREIGN KEY (`lead_source_id`) REFERENCES `lead_sources` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_meta_lead_form_id_foreign` FOREIGN KEY (`meta_lead_form_id`) REFERENCES `meta_lead_forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leads_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `opening_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `carried_forward` decimal(8,2) NOT NULL DEFAULT 0.00,
  `accrued_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `used_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `encashed_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `closing_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_balances_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`),
  CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leave_encashments`
--

DROP TABLE IF EXISTS `leave_encashments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_encashments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `days` decimal(8,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_encashments_employee_id_foreign` (`employee_id`),
  KEY `leave_encashments_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_encashments_approved_by_foreign` (`approved_by`),
  CONSTRAINT `leave_encashments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_encashments_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_encashments_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leave_policies`
--

DROP TABLE IF EXISTS `leave_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `annual_entitlement` decimal(8,2) NOT NULL DEFAULT 0.00,
  `accrual_frequency` varchar(20) NOT NULL DEFAULT 'yearly',
  `carry_forward_max` decimal(8,2) NOT NULL DEFAULT 0.00,
  `allow_encashment` tinyint(1) NOT NULL DEFAULT 0,
  `encashment_max` decimal(8,2) DEFAULT NULL,
  `allow_half_day` tinyint(1) NOT NULL DEFAULT 1,
  `allow_short_leave` tinyint(1) NOT NULL DEFAULT 0,
  `short_leave_hours` decimal(4,2) NOT NULL DEFAULT 2.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_policies_company_id_foreign` (`company_id`),
  KEY `leave_policies_leave_type_id_foreign` (`leave_type_id`),
  CONSTRAINT `leave_policies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_policies_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leave_policy_rules`
--

DROP TABLE IF EXISTS `leave_policy_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_policy_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `leave_policy_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `employee_category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_policy_rules_leave_policy_id_foreign` (`leave_policy_id`),
  KEY `leave_policy_rules_department_id_foreign` (`department_id`),
  KEY `leave_policy_rules_designation_id_foreign` (`designation_id`),
  CONSTRAINT `leave_policy_rules_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_policy_rules_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_policy_rules_leave_policy_id_foreign` FOREIGN KEY (`leave_policy_id`) REFERENCES `leave_policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `days` decimal(5,2) NOT NULL DEFAULT 1.00,
  `is_half_day` tinyint(1) NOT NULL DEFAULT 0,
  `is_short_leave` tinyint(1) NOT NULL DEFAULT 0,
  `short_leave_hours` decimal(4,2) DEFAULT NULL,
  `session` varchar(20) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_employee_id_foreign` (`employee_id`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `default_days` int(10) unsigned NOT NULL DEFAULT 0,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_types_company_id_foreign` (`company_id`),
  CONSTRAINT `leave_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `load_sheet_items`
--

DROP TABLE IF EXISTS `load_sheet_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `load_sheet_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `load_sheet_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `loaded_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `loaded_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `load_sheet_items_load_sheet_id_invoice_id_unique` (`load_sheet_id`,`invoice_id`),
  KEY `load_sheet_items_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `load_sheet_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `load_sheet_items_load_sheet_id_foreign` FOREIGN KEY (`load_sheet_id`) REFERENCES `load_sheets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `load_sheets`
--

DROP TABLE IF EXISTS `load_sheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `load_sheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `load_sheet_no` varchar(30) NOT NULL,
  `load_date` date NOT NULL,
  `route_id` bigint(20) unsigned DEFAULT NULL,
  `vehicle_id` bigint(20) unsigned DEFAULT NULL,
  `driver_id` bigint(20) unsigned DEFAULT NULL,
  `delivery_person_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('draft','dispatched','in_transit','delivered','settled') NOT NULL DEFAULT 'draft',
  `total_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `total_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `load_sheets_load_sheet_no_unique` (`load_sheet_no`),
  KEY `load_sheets_route_id_foreign` (`route_id`),
  KEY `load_sheets_vehicle_id_foreign` (`vehicle_id`),
  KEY `load_sheets_driver_id_foreign` (`driver_id`),
  KEY `load_sheets_delivery_person_id_foreign` (`delivery_person_id`),
  KEY `load_sheets_created_by_foreign` (`created_by`),
  CONSTRAINT `load_sheets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `load_sheets_delivery_person_id_foreign` FOREIGN KEY (`delivery_person_id`) REFERENCES `delivery_persons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `load_sheets_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `load_sheets_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `load_sheets_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meta_lead_forms`
--

DROP TABLE IF EXISTS `meta_lead_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_lead_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` varchar(255) NOT NULL,
  `form_name` varchar(255) DEFAULT NULL,
  `page_id` varchar(255) DEFAULT NULL,
  `lead_campaign_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_lead_forms_form_id_unique` (`form_id`),
  KEY `meta_lead_forms_lead_campaign_id_foreign` (`lead_campaign_id`),
  CONSTRAINT `meta_lead_forms_lead_campaign_id_foreign` FOREIGN KEY (`lead_campaign_id`) REFERENCES `lead_campaigns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `meta_lead_logs`
--

DROP TABLE IF EXISTS `meta_lead_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_lead_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_lead_id` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'received',
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `result` text DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_lead_logs_external_lead_id_index` (`external_lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `od_accounts`
--

DROP TABLE IF EXISTS `od_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `od_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `account_number` varchar(64) NOT NULL,
  `bank_name` varchar(150) NOT NULL,
  `ifsc_code` varchar(30) DEFAULT NULL,
  `od_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(6,2) NOT NULL DEFAULT 0.00,
  `interest_calculation_method` varchar(50) NOT NULL DEFAULT 'daily_simple',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `od_accounts_branch_id_foreign` (`branch_id`),
  KEY `od_accounts_created_by_foreign` (`created_by`),
  KEY `od_accounts_updated_by_foreign` (`updated_by`),
  KEY `od_accounts_company_id_status_index` (`company_id`,`status`),
  KEY `od_accounts_company_id_account_number_index` (`company_id`,`account_number`),
  CONSTRAINT `od_accounts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `od_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `od_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `od_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `reserved_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `delivered_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `back_order_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  `serial_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`serial_numbers`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `order_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(30) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `quotation_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `salesperson_id` bigint(20) unsigned DEFAULT NULL,
  `salesperson_name` varchar(100) DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `updated_by_name` varchar(100) DEFAULT NULL,
  `order_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `fulfilment_mode` varchar(50) DEFAULT NULL,
  `terms_of_delivery` varchar(50) DEFAULT NULL,
  `caretaker_phone` varchar(20) DEFAULT NULL,
  `architect_name` varchar(255) DEFAULT NULL,
  `architect_phone` varchar(20) DEFAULT NULL,
  `back_order` tinyint(1) NOT NULL DEFAULT 0,
  `credit_check_status` enum('pending','passed','blocked','overridden') NOT NULL DEFAULT 'pending',
  `blocked_reason` text DEFAULT NULL,
  `status` enum('draft','pending','approved','converted','cancelled') NOT NULL DEFAULT 'pending',
  `price_type` varchar(20) NOT NULL DEFAULT 'exclusive',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `round_off` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `advance_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `advance_percentage` decimal(5,2) DEFAULT NULL,
  `balance_amount` decimal(15,2) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by_name` varchar(100) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `converted_by_name` varchar(100) DEFAULT NULL,
  `cancelled_by_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_no_unique` (`order_no`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_salesperson_id_foreign` (`salesperson_id`),
  KEY `orders_approved_by_foreign` (`approved_by`),
  KEY `orders_warehouse_id_foreign` (`warehouse_id`),
  KEY `orders_quotation_id_foreign` (`quotation_id`),
  KEY `orders_billing_address_id_foreign` (`billing_address_id`),
  KEY `orders_shipping_address_id_foreign` (`shipping_address_id`),
  CONSTRAINT `orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_billing_address_id_foreign` FOREIGN KEY (`billing_address_id`) REFERENCES `party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `orders_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `outstanding_ledger`
--

DROP TABLE IF EXISTS `outstanding_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outstanding_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `type` varchar(30) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `outstanding_ledger_customer_id_foreign` (`customer_id`),
  KEY `outstanding_ledger_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `outstanding_ledger_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `party_addresses`
--

DROP TABLE IF EXISTS `party_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `party_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'both',
  `label` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_billing` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_delivery` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `party_addresses_customer_id_foreign` (`customer_id`),
  CONSTRAINT `party_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `party_bank_accounts`
--

DROP TABLE IF EXISTS `party_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `party_bank_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_type` varchar(255) NOT NULL DEFAULT 'current',
  `ifsc_code` varchar(20) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `party_bank_accounts_customer_id_foreign` (`customer_id`),
  CONSTRAINT `party_bank_accounts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `party_contacts`
--

DROP TABLE IF EXISTS `party_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `party_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `phone_directory_contact_id` bigint(20) unsigned DEFAULT NULL,
  `level` enum('party','branch','site','warehouse','transporter','driver','site_incharge') NOT NULL DEFAULT 'party',
  `name` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `whatsapp_number` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `party_contacts_customer_id_foreign` (`customer_id`),
  KEY `party_contacts_designation_id_foreign` (`designation_id`),
  KEY `party_contacts_phone_directory_contact_id_foreign` (`phone_directory_contact_id`),
  CONSTRAINT `party_contacts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `party_contacts_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `party_contacts_phone_directory_contact_id_foreign` FOREIGN KEY (`phone_directory_contact_id`) REFERENCES `phone_directory_contacts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `party_credit_cheques`
--

DROP TABLE IF EXISTS `party_credit_cheques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `party_credit_cheques` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `cheque_number` varchar(50) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cheque_type` varchar(255) DEFAULT NULL,
  `status` enum('pending','received','deposited','cleared','cancelled','bounced') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `party_credit_cheques_customer_id_foreign` (`customer_id`),
  CONSTRAINT `party_credit_cheques_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `party_gem_authorizations`
--

DROP TABLE IF EXISTS `party_gem_authorizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `party_gem_authorizations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `gem_registered` tinyint(1) NOT NULL DEFAULT 0,
  `gem_authorized` tinyint(1) NOT NULL DEFAULT 0,
  `authorization_number` varchar(100) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `date_of_issue` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `party_gem_authorizations_brand_id_foreign` (`brand_id`),
  KEY `party_gem_authorizations_customer_id_brand_id_index` (`customer_id`,`brand_id`),
  CONSTRAINT `party_gem_authorizations_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `party_gem_authorizations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `party_gem_documents`
--

DROP TABLE IF EXISTS `party_gem_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `party_gem_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `party_gem_authorization_id` bigint(20) unsigned NOT NULL,
  `disk` varchar(20) NOT NULL DEFAULT 'public',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `party_gem_documents_party_gem_authorization_id_foreign` (`party_gem_authorization_id`),
  CONSTRAINT `party_gem_documents_party_gem_authorization_id_foreign` FOREIGN KEY (`party_gem_authorization_id`) REFERENCES `party_gem_authorizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `party_targets`
--

DROP TABLE IF EXISTS `party_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `party_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `target_period_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `salesperson_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `party_targets_customer_id_foreign` (`customer_id`),
  KEY `party_targets_salesperson_id_foreign` (`salesperson_id`),
  KEY `party_targets_brand_id_foreign` (`brand_id`),
  KEY `party_targets_target_period_id_customer_id_index` (`target_period_id`,`customer_id`),
  KEY `party_targets_target_period_id_salesperson_id_index` (`target_period_id`,`salesperson_id`),
  CONSTRAINT `party_targets_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `party_targets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `party_targets_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `party_targets_target_period_id_foreign` FOREIGN KEY (`target_period_id`) REFERENCES `target_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pay_heads`
--

DROP TABLE IF EXISTS `pay_heads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_heads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(40) DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'earning',
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 100,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pay_heads_company_id_code_unique` (`company_id`,`code`),
  CONSTRAINT `pay_heads_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_allocations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_allocations_payment_id_invoice_id_unique` (`payment_id`,`invoice_id`),
  KEY `payment_allocations_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `payment_allocations_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payment_links`
--

DROP TABLE IF EXISTS `payment_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_links_token_unique` (`token`),
  UNIQUE KEY `payment_links_provider_reference_unique` (`provider_reference`),
  KEY `payment_links_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `payment_links_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_no` varchar(30) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `method` enum('cash','upi','bank','cheque','other') NOT NULL DEFAULT 'cash',
  `bank_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'completed',
  `paid_at` timestamp NULL DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_payment_no_unique` (`payment_no`),
  UNIQUE KEY `payments_reference_no_unique` (`reference_no`),
  KEY `payments_customer_id_foreign` (`customer_id`),
  KEY `payments_recorded_by_foreign` (`recorded_by`),
  KEY `payments_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payroll_inputs`
--

DROP TABLE IF EXISTS `payroll_inputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_inputs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `basic` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `incentives` decimal(12,2) NOT NULL DEFAULT 0.00,
  `advances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_payable` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_inputs_employee_id_year_month_unique` (`employee_id`,`year`,`month`),
  CONSTRAINT `payroll_inputs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payroll_run_lines`
--

DROP TABLE IF EXISTS `payroll_run_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_run_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `payable_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `present_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `leave_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `lwp_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `ot_minutes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `gross_earnings` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_payable` decimal(12,2) NOT NULL DEFAULT 0.00,
  `earnings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`earnings_json`)),
  `deductions_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deductions_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_run_lines_payroll_run_id_employee_id_unique` (`payroll_run_id`,`employee_id`),
  KEY `payroll_run_lines_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payroll_run_lines_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_run_lines_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_runs_period_unique` (`company_id`,`branch_id`,`year`,`month`),
  KEY `payroll_runs_branch_id_foreign` (`branch_id`),
  KEY `payroll_runs_created_by_foreign` (`created_by`),
  KEY `payroll_runs_approved_by_foreign` (`approved_by`),
  CONSTRAINT `payroll_runs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `phone_directory_categories`
--

DROP TABLE IF EXISTS `phone_directory_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phone_directory_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL DEFAULT 'violet',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone_directory_categories_name_unique` (`name`),
  KEY `phone_directory_categories_created_by_foreign` (`created_by`),
  KEY `phone_directory_categories_updated_by_foreign` (`updated_by`),
  CONSTRAINT `phone_directory_categories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phone_directory_categories_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `phone_directory_contacts`
--

DROP TABLE IF EXISTS `phone_directory_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phone_directory_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contact_name` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `contact_type` varchar(255) NOT NULL DEFAULT 'other',
  `designation` varchar(255) DEFAULT NULL,
  `designation_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `customer_type_id` bigint(20) unsigned DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `company_website` varchar(500) DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `mobile_number` varchar(255) NOT NULL,
  `alternate_mobile` varchar(255) DEFAULT NULL,
  `office_phone` varchar(255) DEFAULT NULL,
  `extension_number` varchar(255) DEFAULT NULL,
  `whatsapp_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `alternate_email` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'India',
  `state` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `area` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `phone_directory_contacts_department_id_foreign` (`department_id`),
  KEY `phone_directory_contacts_category_id_foreign` (`category_id`),
  KEY `phone_directory_contacts_created_by_foreign` (`created_by`),
  KEY `phone_directory_contacts_updated_by_foreign` (`updated_by`),
  KEY `phone_directory_contacts_contact_name_index` (`contact_name`),
  KEY `phone_directory_contacts_mobile_number_index` (`mobile_number`),
  KEY `phone_directory_contacts_email_index` (`email`),
  KEY `phone_directory_contacts_contact_type_index` (`contact_type`),
  KEY `phone_directory_contacts_is_important_index` (`is_important`),
  KEY `phone_directory_contacts_status_index` (`status`),
  KEY `phone_directory_contacts_archived_at_index` (`archived_at`),
  KEY `phone_directory_contacts_company_name_index` (`company_name`),
  KEY `phone_directory_contacts_state_index` (`state`),
  KEY `phone_directory_contacts_city_index` (`city`),
  KEY `phone_directory_contacts_designation_id_foreign` (`designation_id`),
  KEY `phone_directory_contacts_company_id_foreign` (`company_id`),
  KEY `phone_directory_contacts_customer_type_id_foreign` (`customer_type_id`),
  CONSTRAINT `phone_directory_contacts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `phone_directory_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phone_directory_contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phone_directory_contacts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phone_directory_contacts_customer_type_id_foreign` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phone_directory_contacts_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `phone_directory_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phone_directory_contacts_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phone_directory_contacts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `phone_directory_departments`
--

DROP TABLE IF EXISTS `phone_directory_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phone_directory_departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone_directory_departments_name_unique` (`name`),
  KEY `phone_directory_departments_created_by_foreign` (`created_by`),
  KEY `phone_directory_departments_updated_by_foreign` (`updated_by`),
  CONSTRAINT `phone_directory_departments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `phone_directory_departments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `price_masters`
--

DROP TABLE IF EXISTS `price_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_masters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL DEFAULT 'customer_type',
  `name` varchar(255) DEFAULT NULL,
  `customer_type_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `rate` decimal(12,2) NOT NULL,
  `rate_includes_gst` tinyint(1) NOT NULL DEFAULT 0,
  `entered_rate` decimal(12,2) DEFAULT NULL,
  `dealer_price` decimal(12,2) DEFAULT NULL,
  `dealer_price_entered` decimal(12,2) DEFAULT NULL,
  `dealer_price_gst_mode` varchar(20) NOT NULL DEFAULT 'exclusive',
  `retail_price` decimal(12,2) DEFAULT NULL,
  `retail_price_entered` decimal(12,2) DEFAULT NULL,
  `retail_price_gst_mode` varchar(20) NOT NULL DEFAULT 'exclusive',
  `min_qty` decimal(12,2) DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_master_unique` (`customer_type_id`,`product_id`,`uom_id`,`min_qty`),
  KEY `price_masters_product_id_foreign` (`product_id`),
  KEY `price_masters_uom_id_foreign` (`uom_id`),
  KEY `price_masters_brand_id_foreign` (`brand_id`),
  KEY `price_masters_company_id_foreign` (`company_id`),
  CONSTRAINT `price_masters_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `price_masters_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `price_masters_customer_type_id_foreign` FOREIGN KEY (`customer_type_id`) REFERENCES `customer_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `price_masters_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `price_masters_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `uom_id` bigint(20) unsigned DEFAULT NULL,
  `batch_no` varchar(60) NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `mrp` decimal(12,2) DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_batches_lookup_unique` (`product_id`,`warehouse_id`,`batch_no`),
  KEY `product_batches_warehouse_id_foreign` (`warehouse_id`),
  KEY `product_batches_uom_id_foreign` (`uom_id`),
  KEY `product_batches_source_type_source_id_index` (`source_type`,`source_id`),
  CONSTRAINT `product_batches_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_batches_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_batches_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_media`
--

DROP TABLE IF EXISTS `product_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'image',
  `path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_media_product_id_foreign` (`product_id`),
  CONSTRAINT `product_media_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_price_histories`
--

DROP TABLE IF EXISTS `product_price_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_price_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned DEFAULT NULL,
  `trade_price` decimal(12,2) DEFAULT NULL,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `mrp` decimal(12,2) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `change_reason` varchar(100) DEFAULT NULL,
  `source` varchar(30) NOT NULL DEFAULT 'manual',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_price_histories_product_id_foreign` (`product_id`),
  KEY `product_price_histories_uom_id_foreign` (`uom_id`),
  KEY `product_price_histories_created_by_foreign` (`created_by`),
  CONSTRAINT `product_price_histories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_price_histories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_price_histories_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_serials`
--

DROP TABLE IF EXISTS `product_serials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_serials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `serial_number` varchar(255) NOT NULL,
  `status` enum('in_stock','reserved','sold','returned','scrapped','in_transit','delivered') NOT NULL DEFAULT 'in_stock',
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_inward_item_id` bigint(20) unsigned DEFAULT NULL,
  `sold_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `warranty_label` varchar(100) DEFAULT NULL,
  `warranty_months` int(10) unsigned DEFAULT NULL,
  `warranty_start_on` date DEFAULT NULL,
  `warranty_expires_on` date DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `sales_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `reservation_note` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reserved_for_type` varchar(255) DEFAULT NULL,
  `reserved_for_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_serials_serial_number_unique` (`serial_number`),
  KEY `product_serials_product_id_foreign` (`product_id`),
  KEY `product_serials_warehouse_id_foreign` (`warehouse_id`),
  KEY `product_serials_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `product_serials_purchase_inward_item_id_foreign` (`purchase_inward_item_id`),
  KEY `product_serials_reserved_for_type_reserved_for_id_index` (`reserved_for_type`,`reserved_for_id`),
  KEY `product_serials_customer_id_foreign` (`customer_id`),
  KEY `product_serials_sales_invoice_id_foreign` (`sales_invoice_id`),
  CONSTRAINT `product_serials_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_serials_purchase_inward_item_id_foreign` FOREIGN KEY (`purchase_inward_item_id`) REFERENCES `purchase_inward_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_serials_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_types`
--

DROP TABLE IF EXISTS `product_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `track_serial` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_types_name_unique` (`name`),
  KEY `product_types_company_id_foreign` (`company_id`),
  CONSTRAINT `product_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_uoms`
--

DROP TABLE IF EXISTS `product_uoms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_uoms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_uoms_product_id_uom_id_unique` (`product_id`,`uom_id`),
  KEY `product_uoms_uom_id_foreign` (`uom_id`),
  CONSTRAINT `product_uoms_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_uoms_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `sub_category_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `series` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `product_type_id` bigint(20) unsigned DEFAULT NULL,
  `product_type` varchar(100) DEFAULT NULL,
  `serial_no` varchar(30) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specification` text DEFAULT NULL,
  `warranty` varchar(100) DEFAULT NULL,
  `height_cm` decimal(10,2) DEFAULT NULL,
  `length_cm` decimal(10,2) DEFAULT NULL,
  `width_cm` decimal(10,2) DEFAULT NULL,
  `height_in` decimal(10,2) DEFAULT NULL,
  `length_in` decimal(10,2) DEFAULT NULL,
  `width_in` decimal(10,2) DEFAULT NULL,
  `net_weight` decimal(10,3) DEFAULT NULL,
  `weight_unit` varchar(20) DEFAULT 'KG',
  `base_uom_id` bigint(20) unsigned NOT NULL,
  `alternate_uom_id` bigint(20) unsigned DEFAULT NULL,
  `conversion_factor` decimal(12,4) DEFAULT 1.0000,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `trade_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `purchase_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `calculation_mrp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `alt_purchase_price` decimal(12,2) DEFAULT NULL,
  `alt_trade_price` decimal(12,2) DEFAULT NULL,
  `alt_selling_price` decimal(12,2) DEFAULT NULL,
  `alt_calculation_mrp` decimal(12,2) DEFAULT NULL,
  `mrp_calculation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mrp_calculation_rules`)),
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `warranty_months` int(10) unsigned DEFAULT NULL,
  `sender_warranty_months` int(10) unsigned DEFAULT NULL,
  `sender_warranty_terms` text DEFAULT NULL,
  `customer_warranty_months` int(10) unsigned DEFAULT NULL,
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
  `aging_threshold_days` int(10) unsigned DEFAULT NULL,
  `credit_period_days` int(10) unsigned DEFAULT NULL,
  `payment_period_days` int(10) unsigned DEFAULT NULL,
  `lifespan_days` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `supply_type` varchar(30) NOT NULL DEFAULT 'goods',
  `purchase_price_tax_basis` varchar(20) NOT NULL DEFAULT 'exclusive',
  `costing_method` varchar(40) DEFAULT NULL,
  `market_valuation_method` varchar(40) NOT NULL DEFAULT 'avg_price',
  `provide_behaviour` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_physical_count_diff` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_negative_balances` tinyint(1) NOT NULL DEFAULT 0,
  `treat_sales_as_new_manufacture` tinyint(1) NOT NULL DEFAULT 0,
  `treat_purchases_as_consumed` tinyint(1) NOT NULL DEFAULT 0,
  `treat_rejections_inward_as_scrap` tinyint(1) NOT NULL DEFAULT 0,
  `last_sale_price` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_serial_no_unique` (`serial_no`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_base_uom_id_foreign` (`base_uom_id`),
  KEY `products_company_id_foreign` (`company_id`),
  KEY `products_brand_id_foreign` (`brand_id`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_sub_category_id_foreign` (`sub_category_id`),
  KEY `products_color_variant_index` (`color_variant`),
  KEY `products_product_type_id_foreign` (`product_type_id`),
  KEY `products_alternate_uom_id_foreign` (`alternate_uom_id`),
  KEY `products_is_hidden_index` (`is_hidden`),
  CONSTRAINT `products_alternate_uom_id_foreign` FOREIGN KEY (`alternate_uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_base_uom_id_foreign` FOREIGN KEY (`base_uom_id`) REFERENCES `uoms` (`id`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_product_type_id_foreign` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_comparison_events`
--

DROP TABLE IF EXISTS `purchase_comparison_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_comparison_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_comparison_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_comparison_events_purchase_comparison_id_foreign` (`purchase_comparison_id`),
  KEY `purchase_comparison_events_user_id_foreign` (`user_id`),
  CONSTRAINT `purchase_comparison_events_purchase_comparison_id_foreign` FOREIGN KEY (`purchase_comparison_id`) REFERENCES `purchase_comparisons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_comparison_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_comparison_files`
--

DROP TABLE IF EXISTS `purchase_comparison_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_comparison_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_comparison_vendor_id` bigint(20) unsigned NOT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime` varchar(120) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_comparison_files_purchase_comparison_vendor_id_foreign` (`purchase_comparison_vendor_id`),
  CONSTRAINT `purchase_comparison_files_purchase_comparison_vendor_id_foreign` FOREIGN KEY (`purchase_comparison_vendor_id`) REFERENCES `purchase_comparison_vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_comparison_items`
--

DROP TABLE IF EXISTS `purchase_comparison_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_comparison_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_comparison_id` bigint(20) unsigned NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 1,
  `category_name` varchar(255) DEFAULT NULL,
  `subcategory_name` varchar(255) DEFAULT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `uom_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `model_sku` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `required_quantity` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `selected_vendor_id` bigint(20) unsigned DEFAULT NULL,
  `selection_reason` text DEFAULT NULL,
  `final_rate` decimal(15,4) DEFAULT NULL,
  `final_freight` decimal(15,2) DEFAULT NULL,
  `final_amount` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_comparison_items_purchase_comparison_id_foreign` (`purchase_comparison_id`),
  KEY `purchase_comparison_items_product_id_foreign` (`product_id`),
  KEY `purchase_comparison_items_uom_id_foreign` (`uom_id`),
  KEY `purchase_comparison_items_selected_vendor_id_foreign` (`selected_vendor_id`),
  CONSTRAINT `purchase_comparison_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_comparison_items_purchase_comparison_id_foreign` FOREIGN KEY (`purchase_comparison_id`) REFERENCES `purchase_comparisons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_comparison_items_selected_vendor_id_foreign` FOREIGN KEY (`selected_vendor_id`) REFERENCES `purchase_comparison_vendors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_comparison_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_comparison_orders`
--

DROP TABLE IF EXISTS `purchase_comparison_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_comparison_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_comparison_id` bigint(20) unsigned NOT NULL,
  `purchase_comparison_vendor_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_comparison_orders_purchase_comparison_id_foreign` (`purchase_comparison_id`),
  KEY `purchase_comparison_orders_purchase_comparison_vendor_id_foreign` (`purchase_comparison_vendor_id`),
  KEY `purchase_comparison_orders_purchase_order_id_foreign` (`purchase_order_id`),
  CONSTRAINT `purchase_comparison_orders_purchase_comparison_id_foreign` FOREIGN KEY (`purchase_comparison_id`) REFERENCES `purchase_comparisons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_comparison_orders_purchase_comparison_vendor_id_foreign` FOREIGN KEY (`purchase_comparison_vendor_id`) REFERENCES `purchase_comparison_vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_comparison_orders_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_comparison_quotes`
--

DROP TABLE IF EXISTS `purchase_comparison_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_comparison_quotes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_comparison_item_id` bigint(20) unsigned NOT NULL,
  `purchase_comparison_vendor_id` bigint(20) unsigned NOT NULL,
  `is_quoted` tinyint(1) NOT NULL DEFAULT 0,
  `quoted_quantity` decimal(15,4) DEFAULT NULL,
  `basic_rate` decimal(15,4) DEFAULT NULL,
  `discount_type` varchar(10) NOT NULL DEFAULT 'percent',
  `discount_value` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `cash_discount_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `gst_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `tax_basis` varchar(20) NOT NULL DEFAULT 'exclusive',
  `freight_type` varchar(10) NOT NULL DEFAULT 'free',
  `freight_mode` varchar(10) NOT NULL DEFAULT 'amount',
  `freight_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `packing_charges` decimal(15,2) NOT NULL DEFAULT 0.00,
  `insurance_charges` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_charges` decimal(15,2) NOT NULL DEFAULT 0.00,
  `financing_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `warranty_period` varchar(60) DEFAULT NULL,
  `warranty_type` varchar(20) DEFAULT NULL,
  `stock_availability` varchar(20) DEFAULT NULL,
  `delivery_time` varchar(60) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `price_validity` varchar(60) DEFAULT NULL,
  `country_of_origin` varchar(60) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comparison_quote_item_vendor_unique` (`purchase_comparison_item_id`,`purchase_comparison_vendor_id`),
  KEY `purchase_comparison_quotes_purchase_comparison_vendor_id_foreign` (`purchase_comparison_vendor_id`),
  CONSTRAINT `purchase_comparison_quotes_purchase_comparison_item_id_foreign` FOREIGN KEY (`purchase_comparison_item_id`) REFERENCES `purchase_comparison_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_comparison_quotes_purchase_comparison_vendor_id_foreign` FOREIGN KEY (`purchase_comparison_vendor_id`) REFERENCES `purchase_comparison_vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_comparison_vendors`
--

DROP TABLE IF EXISTS `purchase_comparison_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_comparison_vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_comparison_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 1,
  `billing_address` text DEFAULT NULL,
  `dispatch_address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `quotation_number` varchar(60) DEFAULT NULL,
  `quotation_date` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `credit_period` varchar(40) DEFAULT NULL,
  `advance_payment` varchar(80) DEFAULT NULL,
  `payment_mode` varchar(30) DEFAULT NULL,
  `credit_days` smallint(5) unsigned DEFAULT NULL,
  `payment_due_date` date DEFAULT NULL,
  `pdc_details` varchar(255) DEFAULT NULL,
  `cdc_details` varchar(255) DEFAULT NULL,
  `cash_discount_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cash_discount_days` smallint(5) unsigned DEFAULT NULL,
  `bank_interest_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `bank_interest_days` smallint(5) unsigned DEFAULT NULL,
  `financing_notes` varchar(255) DEFAULT NULL,
  `credit_limit_note` varchar(255) DEFAULT NULL,
  `replacement_policy` varchar(255) DEFAULT NULL,
  `return_policy` varchar(255) DEFAULT NULL,
  `installation_support` varchar(255) DEFAULT NULL,
  `transporter` varchar(255) DEFAULT NULL,
  `freight_paid_by` varchar(20) NOT NULL DEFAULT 'vendor',
  `delivery_location` varchar(255) DEFAULT NULL,
  `partial_delivery` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_comparison_vendors_purchase_comparison_id_foreign` (`purchase_comparison_id`),
  KEY `purchase_comparison_vendors_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `purchase_comparison_vendors_purchase_comparison_id_foreign` FOREIGN KEY (`purchase_comparison_id`) REFERENCES `purchase_comparisons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_comparison_vendors_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_comparisons`
--

DROP TABLE IF EXISTS `purchase_comparisons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_comparisons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `number` varchar(30) NOT NULL,
  `comparison_date` date NOT NULL,
  `prepared_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `indent_no` varchar(60) DEFAULT NULL,
  `customer_project` varchar(255) DEFAULT NULL,
  `required_delivery_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `executive_remarks` text DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_comparisons_number_unique` (`number`),
  KEY `purchase_comparisons_company_id_foreign` (`company_id`),
  KEY `purchase_comparisons_branch_id_foreign` (`branch_id`),
  KEY `purchase_comparisons_prepared_by_foreign` (`prepared_by`),
  KEY `purchase_comparisons_updated_by_foreign` (`updated_by`),
  KEY `purchase_comparisons_approved_by_foreign` (`approved_by`),
  CONSTRAINT `purchase_comparisons_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_comparisons_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_comparisons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_comparisons_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_comparisons_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_invoice_items`
--

DROP TABLE IF EXISTS `purchase_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `purchase_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `receipt_note_item_id` bigint(20) unsigned DEFAULT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `received_qty` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `batch_no` varchar(60) DEFAULT NULL,
  `batch_selling_price` decimal(12,2) DEFAULT NULL,
  `batch_mrp` decimal(12,2) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `purchase_price_inclusive` decimal(15,4) DEFAULT NULL,
  `purchase_discount_percent` decimal(8,2) DEFAULT NULL,
  `price_type` varchar(20) NOT NULL DEFAULT 'exclusive',
  `entered_unit_cost` decimal(14,4) DEFAULT NULL,
  `tax_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `igst_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `other_vendor_rate` decimal(14,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_invoice_items_purchase_invoice_id_foreign` (`purchase_invoice_id`),
  KEY `purchase_invoice_items_product_id_foreign` (`product_id`),
  KEY `purchase_invoice_items_uom_id_foreign` (`uom_id`),
  KEY `purchase_invoice_items_receipt_note_item_id_foreign` (`receipt_note_item_id`),
  KEY `purchase_invoice_items_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  CONSTRAINT `purchase_invoice_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_invoice_items_purchase_invoice_id_foreign` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_invoice_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoice_items_receipt_note_item_id_foreign` FOREIGN KEY (`receipt_note_item_id`) REFERENCES `receipt_note_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoice_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_invoices`
--

DROP TABLE IF EXISTS `purchase_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `receipt_note_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_inward_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `od_account_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_no` varchar(40) NOT NULL,
  `qr_token` varchar(36) DEFAULT NULL,
  `supplier_invoice_no` varchar(60) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `purchase_receive_date` date DEFAULT NULL,
  `material_dispatch_date` date DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `actual_material_receipt_date` date DEFAULT NULL,
  `payment_order_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `credit_days` int(10) unsigned DEFAULT NULL,
  `due_date_basis` varchar(30) DEFAULT NULL,
  `due_date_source_date` date DEFAULT NULL,
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'posted',
  `material_receipt_status` varchar(32) NOT NULL DEFAULT 'in_transit',
  `price_type` varchar(20) NOT NULL DEFAULT 'exclusive',
  `tax_type` varchar(20) DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `freight_charge` decimal(14,2) NOT NULL DEFAULT 0.00,
  `is_freight_applicable` tinyint(1) NOT NULL DEFAULT 0,
  `freight_mode` varchar(10) NOT NULL DEFAULT 'amount',
  `freight_percent` decimal(8,4) DEFAULT NULL,
  `freight_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `freight_tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `freight_tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `freight_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `freight_notes` varchar(1000) DEFAULT NULL,
  `receipt_note_nos` varchar(255) DEFAULT NULL,
  `receipt_doc_no` varchar(100) DEFAULT NULL,
  `dispatched_through` varchar(150) DEFAULT NULL,
  `carrier_name` varchar(150) DEFAULT NULL,
  `lr_number` varchar(100) DEFAULT NULL,
  `lr_date` date DEFAULT NULL,
  `vehicle_number` varchar(50) DEFAULT NULL,
  `order_nos` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `other_references` varchar(255) DEFAULT NULL,
  `terms_of_delivery` varchar(255) DEFAULT NULL,
  `other_charges` decimal(14,2) NOT NULL DEFAULT 0.00,
  `round_off` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `rate_override_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms_and_conditions` text DEFAULT NULL,
  `freight_allocation_method` varchar(20) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_invoices_invoice_no_unique` (`invoice_no`),
  UNIQUE KEY `purchase_invoices_qr_token_unique` (`qr_token`),
  KEY `purchase_invoices_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_invoices_purchase_inward_id_foreign` (`purchase_inward_id`),
  KEY `purchase_invoices_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_invoices_warehouse_id_foreign` (`warehouse_id`),
  KEY `purchase_invoices_created_by_foreign` (`created_by`),
  KEY `purchase_invoices_billing_address_id_foreign` (`billing_address_id`),
  KEY `purchase_invoices_shipping_address_id_foreign` (`shipping_address_id`),
  KEY `purchase_invoices_company_id_foreign` (`company_id`),
  KEY `purchase_invoices_od_account_id_foreign` (`od_account_id`),
  KEY `purchase_invoices_receipt_note_id_foreign` (`receipt_note_id`),
  CONSTRAINT `purchase_invoices_billing_address_id_foreign` FOREIGN KEY (`billing_address_id`) REFERENCES `party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoices_od_account_id_foreign` FOREIGN KEY (`od_account_id`) REFERENCES `od_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoices_purchase_inward_id_foreign` FOREIGN KEY (`purchase_inward_id`) REFERENCES `purchase_inwards` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoices_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoices_receipt_note_id_foreign` FOREIGN KEY (`receipt_note_id`) REFERENCES `receipt_notes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoices_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_invoices_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_invoices_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_inward_items`
--

DROP TABLE IF EXISTS `purchase_inward_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_inward_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_inward_id` bigint(20) unsigned NOT NULL,
  `purchase_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_inward_items_purchase_inward_id_foreign` (`purchase_inward_id`),
  KEY `purchase_inward_items_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `purchase_inward_items_product_id_foreign` (`product_id`),
  KEY `purchase_inward_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `purchase_inward_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_inward_items_purchase_inward_id_foreign` FOREIGN KEY (`purchase_inward_id`) REFERENCES `purchase_inwards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_inward_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_inward_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_inwards`
--

DROP TABLE IF EXISTS `purchase_inwards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_inwards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `inward_no` varchar(40) NOT NULL,
  `inward_date` date NOT NULL,
  `supplier_challan_no` varchar(60) DEFAULT NULL,
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'posted',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_inwards_inward_no_unique` (`inward_no`),
  KEY `purchase_inwards_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_inwards_warehouse_id_foreign` (`warehouse_id`),
  KEY `purchase_inwards_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_inwards_created_by_foreign` (`created_by`),
  CONSTRAINT `purchase_inwards_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_inwards_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_inwards_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_inwards_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `line_total` decimal(14,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_product_id_foreign` (`product_id`),
  KEY `purchase_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `received_qty` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `mrp` decimal(15,2) DEFAULT NULL,
  `purchase_price_inclusive` decimal(15,4) DEFAULT NULL,
  `purchase_discount_percent` decimal(8,2) DEFAULT NULL,
  `tax_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `sgst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `batch_no` varchar(60) DEFAULT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `weight` decimal(14,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_items_product_id_foreign` (`product_id`),
  KEY `purchase_order_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `po_no` varchar(40) NOT NULL,
  `po_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `credit_days` int(10) unsigned NOT NULL DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','partially_received','closed','cancelled') NOT NULL DEFAULT 'draft',
  `price_type` varchar(20) NOT NULL DEFAULT 'exclusive',
  `is_freight_applicable` tinyint(1) NOT NULL DEFAULT 0,
  `freight_mode` varchar(10) NOT NULL DEFAULT 'amount',
  `freight_percent` decimal(8,4) DEFAULT NULL,
  `freight_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `freight_tax_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `freight_tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `freight_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `freight_notes` varchar(500) DEFAULT NULL,
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `round_off` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `company_delivery_address_id` bigint(20) unsigned DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_no_unique` (`po_no`),
  KEY `purchase_orders_company_id_foreign` (`company_id`),
  KEY `purchase_orders_branch_id_foreign` (`branch_id`),
  KEY `purchase_orders_warehouse_id_foreign` (`warehouse_id`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_orders_created_by_foreign` (`created_by`),
  KEY `purchase_orders_approved_by_foreign` (`approved_by`),
  KEY `purchase_orders_billing_address_id_foreign` (`billing_address_id`),
  KEY `purchase_orders_shipping_address_id_foreign` (`shipping_address_id`),
  KEY `purchase_orders_company_delivery_address_id_foreign` (`company_delivery_address_id`),
  CONSTRAINT `purchase_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_billing_address_id_foreign` FOREIGN KEY (`billing_address_id`) REFERENCES `party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_company_delivery_address_id_foreign` FOREIGN KEY (`company_delivery_address_id`) REFERENCES `company_delivery_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_shipping_address_id_foreign` FOREIGN KEY (`shipping_address_id`) REFERENCES `party_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_no` varchar(30) NOT NULL,
  `purchase_date` date NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `supplier_party_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('draft','posted') NOT NULL DEFAULT 'posted',
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_purchase_no_unique` (`purchase_no`),
  KEY `purchases_created_by_foreign` (`created_by`),
  KEY `purchases_supplier_party_id_foreign` (`supplier_party_id`),
  KEY `purchases_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `purchases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `purchases_supplier_party_id_foreign` FOREIGN KEY (`supplier_party_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quotation_items`
--

DROP TABLE IF EXISTS `quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `hsn_code` varchar(32) DEFAULT NULL,
  `mrp` decimal(12,2) DEFAULT NULL,
  `tax_rate` decimal(8,2) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `quantity` decimal(12,4) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(14,2) NOT NULL,
  `estimated_landed_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estimated_profit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_items_quotation_id_foreign` (`quotation_id`),
  KEY `quotation_items_product_id_foreign` (`product_id`),
  KEY `quotation_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `quotation_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `quotation_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotation_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `quotation_no` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `salesperson_id` bigint(20) unsigned DEFAULT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `status` enum('draft','sent','accepted','rejected','converted','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estimated_profit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `updated_by_name` varchar(100) DEFAULT NULL,
  `converted_order_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotations_quotation_no_unique` (`quotation_no`),
  KEY `quotations_customer_id_foreign` (`customer_id`),
  KEY `quotations_salesperson_id_foreign` (`salesperson_id`),
  KEY `quotations_converted_order_id_foreign` (`converted_order_id`),
  KEY `quotations_company_id_foreign` (`company_id`),
  CONSTRAINT `quotations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_converted_order_id_foreign` FOREIGN KEY (`converted_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `quotations_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `receipt_note_items`
--

DROP TABLE IF EXISTS `receipt_note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipt_note_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_note_id` bigint(20) unsigned NOT NULL,
  `purchase_order_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `ordered_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `challan_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `received_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `invoiced_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `returned_qty` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `batch_no` varchar(80) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipt_note_items_purchase_order_item_id_foreign` (`purchase_order_item_id`),
  KEY `receipt_note_items_product_id_foreign` (`product_id`),
  KEY `receipt_note_items_uom_id_foreign` (`uom_id`),
  KEY `receipt_note_items_receipt_note_id_product_id_index` (`receipt_note_id`,`product_id`),
  CONSTRAINT `receipt_note_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_note_items_purchase_order_item_id_foreign` FOREIGN KEY (`purchase_order_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_note_items_receipt_note_id_foreign` FOREIGN KEY (`receipt_note_id`) REFERENCES `receipt_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_note_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `receipt_note_return_items`
--

DROP TABLE IF EXISTS `receipt_note_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipt_note_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `receipt_note_return_id` bigint(20) unsigned NOT NULL,
  `receipt_note_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipt_note_return_items_receipt_note_return_id_foreign` (`receipt_note_return_id`),
  KEY `receipt_note_return_items_receipt_note_item_id_foreign` (`receipt_note_item_id`),
  KEY `receipt_note_return_items_product_id_foreign` (`product_id`),
  KEY `receipt_note_return_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `receipt_note_return_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_note_return_items_receipt_note_item_id_foreign` FOREIGN KEY (`receipt_note_item_id`) REFERENCES `receipt_note_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_note_return_items_receipt_note_return_id_foreign` FOREIGN KEY (`receipt_note_return_id`) REFERENCES `receipt_note_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_note_return_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `receipt_note_returns`
--

DROP TABLE IF EXISTS `receipt_note_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipt_note_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `receipt_note_id` bigint(20) unsigned NOT NULL,
  `return_no` varchar(60) NOT NULL,
  `return_date` date NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_note_returns_return_no_unique` (`return_no`),
  KEY `receipt_note_returns_receipt_note_id_foreign` (`receipt_note_id`),
  KEY `receipt_note_returns_warehouse_id_foreign` (`warehouse_id`),
  KEY `receipt_note_returns_created_by_foreign` (`created_by`),
  KEY `receipt_note_returns_company_id_return_date_index` (`company_id`,`return_date`),
  CONSTRAINT `receipt_note_returns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_note_returns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_note_returns_receipt_note_id_foreign` FOREIGN KEY (`receipt_note_id`) REFERENCES `receipt_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_note_returns_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `receipt_notes`
--

DROP TABLE IF EXISTS `receipt_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipt_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `receipt_note_no` varchar(60) NOT NULL,
  `receipt_date` date NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_challan_no` varchar(100) DEFAULT NULL,
  `supplier_challan_date` date DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'draft',
  `attachment_path` varchar(255) DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `receipt_notes_branch_id_foreign` (`branch_id`),
  KEY `receipt_notes_warehouse_id_foreign` (`warehouse_id`),
  KEY `receipt_notes_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `receipt_notes_confirmed_by_foreign` (`confirmed_by`),
  KEY `receipt_notes_cancelled_by_foreign` (`cancelled_by`),
  KEY `receipt_notes_created_by_foreign` (`created_by`),
  KEY `receipt_notes_company_id_status_index` (`company_id`,`status`),
  KEY `receipt_notes_supplier_id_receipt_date_index` (`supplier_id`,`receipt_date`),
  KEY `receipt_notes_receipt_note_no_index` (`receipt_note_no`),
  CONSTRAINT `receipt_notes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_notes_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_notes_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_notes_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `receipt_notes_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_notes_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `region_brand_policies`
--

DROP TABLE IF EXISTS `region_brand_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `region_brand_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `area_id` bigint(20) unsigned NOT NULL,
  `brand_id` bigint(20) unsigned NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `max_discount_percent` decimal(5,2) DEFAULT NULL,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_brand_policies_area_id_brand_id_unique` (`area_id`,`brand_id`),
  KEY `region_brand_policies_brand_id_foreign` (`brand_id`),
  CONSTRAINT `region_brand_policies_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `region_brand_policies_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `routes`
--

DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `routes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `area_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `routes_code_unique` (`code`),
  KEY `routes_area_id_foreign` (`area_id`),
  CONSTRAINT `routes_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salary_structure_lines`
--

DROP TABLE IF EXISTS `salary_structure_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_structure_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `salary_structure_id` bigint(20) unsigned NOT NULL,
  `pay_head_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salary_structure_lines_salary_structure_id_pay_head_id_unique` (`salary_structure_id`,`pay_head_id`),
  KEY `salary_structure_lines_pay_head_id_foreign` (`pay_head_id`),
  CONSTRAINT `salary_structure_lines_pay_head_id_foreign` FOREIGN KEY (`pay_head_id`) REFERENCES `pay_heads` (`id`),
  CONSTRAINT `salary_structure_lines_salary_structure_id_foreign` FOREIGN KEY (`salary_structure_id`) REFERENCES `salary_structures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salary_structures`
--

DROP TABLE IF EXISTS `salary_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_structures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `basic` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hra` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_structures_employee_id_foreign` (`employee_id`),
  CONSTRAINT `salary_structures_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales_target_achievements`
--

DROP TABLE IF EXISTS `sales_target_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_target_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_target_id` bigint(20) unsigned NOT NULL,
  `achieved_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achieved_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `pending_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `pending_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `achievement_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `quantity_achievement_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `is_achieved` tinyint(1) NOT NULL DEFAULT 0,
  `invoices_count` int(10) unsigned NOT NULL DEFAULT 0,
  `last_calculated_at` timestamp NULL DEFAULT NULL,
  `breakdown_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`breakdown_snapshot`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_target_achievements_sales_target_id_unique` (`sales_target_id`),
  CONSTRAINT `sales_target_achievements_sales_target_id_foreign` FOREIGN KEY (`sales_target_id`) REFERENCES `sales_targets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales_target_alerts`
--

DROP TABLE IF EXISTS `sales_target_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_target_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_target_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `alert_type` varchar(40) NOT NULL,
  `threshold_percent` decimal(8,2) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_target_alerts_sales_target_id_alert_type_index` (`sales_target_id`,`alert_type`),
  KEY `sales_target_alerts_user_id_is_read_index` (`user_id`,`is_read`),
  CONSTRAINT `sales_target_alerts_sales_target_id_foreign` FOREIGN KEY (`sales_target_id`) REFERENCES `sales_targets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_target_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales_target_audits`
--

DROP TABLE IF EXISTS `sales_target_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_target_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_target_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_target_audits_user_id_foreign` (`user_id`),
  KEY `sales_target_audits_sales_target_id_action_index` (`sales_target_id`,`action`),
  CONSTRAINT `sales_target_audits_sales_target_id_foreign` FOREIGN KEY (`sales_target_id`) REFERENCES `sales_targets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_target_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales_target_items`
--

DROP TABLE IF EXISTS `sales_target_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_target_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_target_id` bigint(20) unsigned NOT NULL,
  `target_type` varchar(30) NOT NULL DEFAULT 'product',
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `sub_category_id` bigint(20) unsigned DEFAULT NULL,
  `target_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `target_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `achieved_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achieved_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_target_items_brand_id_foreign` (`brand_id`),
  KEY `sales_target_items_product_id_foreign` (`product_id`),
  KEY `sales_target_items_category_id_foreign` (`category_id`),
  KEY `sales_target_items_sub_category_id_foreign` (`sub_category_id`),
  KEY `sales_target_items_sales_target_id_target_type_index` (`sales_target_id`,`target_type`),
  CONSTRAINT `sales_target_items_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_target_items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_target_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_target_items_sales_target_id_foreign` FOREIGN KEY (`sales_target_id`) REFERENCES `sales_targets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_target_items_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales_targets`
--

DROP TABLE IF EXISTS `sales_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `target_type` varchar(30) NOT NULL DEFAULT 'value',
  `owner_type` varchar(30) NOT NULL DEFAULT 'individual',
  `salesperson_id` bigint(20) unsigned DEFAULT NULL,
  `sales_team_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `period_type` enum('monthly','quarterly','half_yearly','annual','custom') NOT NULL DEFAULT 'monthly',
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `target_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `target_quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `sub_category_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('draft','pending_approval','active','completed','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `revision_number` int(10) unsigned NOT NULL DEFAULT 0,
  `revised_from_id` bigint(20) unsigned DEFAULT NULL,
  `revision_reason` text DEFAULT NULL,
  `carried_forward_from_id` bigint(20) unsigned DEFAULT NULL,
  `carried_forward_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_targets_salesperson_id_foreign` (`salesperson_id`),
  KEY `sales_targets_sales_team_id_foreign` (`sales_team_id`),
  KEY `sales_targets_branch_id_foreign` (`branch_id`),
  KEY `sales_targets_company_id_foreign` (`company_id`),
  KEY `sales_targets_financial_year_id_foreign` (`financial_year_id`),
  KEY `sales_targets_brand_id_foreign` (`brand_id`),
  KEY `sales_targets_product_id_foreign` (`product_id`),
  KEY `sales_targets_category_id_foreign` (`category_id`),
  KEY `sales_targets_sub_category_id_foreign` (`sub_category_id`),
  KEY `sales_targets_created_by_foreign` (`created_by`),
  KEY `sales_targets_approved_by_foreign` (`approved_by`),
  KEY `sales_targets_revised_from_id_foreign` (`revised_from_id`),
  KEY `sales_targets_carried_forward_from_id_foreign` (`carried_forward_from_id`),
  KEY `sales_targets_owner_type_salesperson_id_index` (`owner_type`,`salesperson_id`),
  KEY `sales_targets_owner_type_sales_team_id_index` (`owner_type`,`sales_team_id`),
  KEY `sales_targets_owner_type_branch_id_index` (`owner_type`,`branch_id`),
  KEY `sales_targets_starts_on_ends_on_index` (`starts_on`,`ends_on`),
  KEY `sales_targets_status_target_type_index` (`status`,`target_type`),
  KEY `sales_targets_customer_id_foreign` (`customer_id`),
  KEY `sales_targets_owner_type_customer_id_index` (`owner_type`,`customer_id`),
  CONSTRAINT `sales_targets_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_carried_forward_from_id_foreign` FOREIGN KEY (`carried_forward_from_id`) REFERENCES `sales_targets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_revised_from_id_foreign` FOREIGN KEY (`revised_from_id`) REFERENCES `sales_targets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_sales_team_id_foreign` FOREIGN KEY (`sales_team_id`) REFERENCES `sales_teams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_salesperson_id_foreign` FOREIGN KEY (`salesperson_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_targets_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales_team_members`
--

DROP TABLE IF EXISTS `sales_team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_team_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_team_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'member',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_team_members_sales_team_id_user_id_unique` (`sales_team_id`,`user_id`),
  KEY `sales_team_members_user_id_foreign` (`user_id`),
  CONSTRAINT `sales_team_members_sales_team_id_foreign` FOREIGN KEY (`sales_team_id`) REFERENCES `sales_teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_team_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sales_teams`
--

DROP TABLE IF EXISTS `sales_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `leader_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_teams_code_unique` (`code`),
  KEY `sales_teams_branch_id_foreign` (`branch_id`),
  KEY `sales_teams_leader_id_foreign` (`leader_id`),
  KEY `sales_teams_company_id_branch_id_index` (`company_id`,`branch_id`),
  CONSTRAINT `sales_teams_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_teams_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_teams_leader_id_foreign` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scheme_achievements`
--

DROP TABLE IF EXISTS `scheme_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheme_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scheme_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `qualified_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `benefit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('provisional','final','cancelled') NOT NULL DEFAULT 'provisional',
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot`)),
  `calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheme_achievements_customer_id_foreign` (`customer_id`),
  KEY `scheme_achievements_invoice_id_foreign` (`invoice_id`),
  KEY `scheme_achievements_scheme_id_customer_id_status_index` (`scheme_id`,`customer_id`,`status`),
  CONSTRAINT `scheme_achievements_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheme_achievements_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scheme_achievements_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scheme_products`
--

DROP TABLE IF EXISTS `scheme_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheme_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scheme_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scheme_products_scheme_id_product_id_unique` (`scheme_id`,`product_id`),
  KEY `scheme_products_product_id_foreign` (`product_id`),
  CONSTRAINT `scheme_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheme_products_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scheme_settlements`
--

DROP TABLE IF EXISTS `scheme_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheme_settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scheme_id` bigint(20) unsigned NOT NULL,
  `scheme_achievement_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `status` enum('pending','settled','cancelled') NOT NULL DEFAULT 'pending',
  `settlement_ref` varchar(255) DEFAULT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheme_settlements_scheme_id_foreign` (`scheme_id`),
  KEY `scheme_settlements_scheme_achievement_id_foreign` (`scheme_achievement_id`),
  KEY `scheme_settlements_customer_id_foreign` (`customer_id`),
  CONSTRAINT `scheme_settlements_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheme_settlements_scheme_achievement_id_foreign` FOREIGN KEY (`scheme_achievement_id`) REFERENCES `scheme_achievements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scheme_settlements_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scheme_slabs`
--

DROP TABLE IF EXISTS `scheme_slabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheme_slabs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scheme_id` bigint(20) unsigned NOT NULL,
  `from_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `to_value` decimal(14,2) DEFAULT NULL,
  `benefit_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `benefit_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheme_slabs_scheme_id_foreign` (`scheme_id`),
  CONSTRAINT `scheme_slabs_scheme_id_foreign` FOREIGN KEY (`scheme_id`) REFERENCES `schemes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `schemes`
--

DROP TABLE IF EXISTS `schemes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schemes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `scheme_type` varchar(30) DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `status` enum('draft','active','closed','cancelled') NOT NULL DEFAULT 'draft',
  `basis` enum('quantity','value') NOT NULL DEFAULT 'value',
  `net_credit_notes` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by_name` varchar(100) DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schemes_code_unique` (`code`),
  KEY `schemes_brand_id_foreign` (`brand_id`),
  CONSTRAINT `schemes_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settlement_lines`
--

DROP TABLE IF EXISTS `settlement_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settlement_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `settlement_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `cash_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `upi_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `outstanding_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `settlement_lines_settlement_id_foreign` (`settlement_id`),
  KEY `settlement_lines_customer_id_foreign` (`customer_id`),
  KEY `settlement_lines_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `settlement_lines_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `settlement_lines_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `settlement_lines_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settlements`
--

DROP TABLE IF EXISTS `settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `settlement_no` varchar(30) NOT NULL,
  `load_sheet_id` bigint(20) unsigned NOT NULL,
  `cash_collected` decimal(14,2) NOT NULL DEFAULT 0.00,
  `upi_collected` decimal(14,2) NOT NULL DEFAULT 0.00,
  `outstanding_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','completed') NOT NULL DEFAULT 'completed',
  `settled_by` bigint(20) unsigned NOT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settlements_settlement_no_unique` (`settlement_no`),
  KEY `settlements_load_sheet_id_foreign` (`load_sheet_id`),
  KEY `settlements_settled_by_foreign` (`settled_by`),
  CONSTRAINT `settlements_load_sheet_id_foreign` FOREIGN KEY (`load_sheet_id`) REFERENCES `load_sheets` (`id`),
  CONSTRAINT `settlements_settled_by_foreign` FOREIGN KEY (`settled_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_minutes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `grace_minutes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `half_day_minutes` smallint(5) unsigned NOT NULL DEFAULT 240,
  `full_day_minutes` smallint(5) unsigned NOT NULL DEFAULT 480,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shifts_company_id_foreign` (`company_id`),
  KEY `shifts_department_id_foreign` (`department_id`),
  CONSTRAINT `shifts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_adjustment_items`
--

DROP TABLE IF EXISTS `stock_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustment_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  KEY `stock_adjustment_items_product_id_foreign` (`product_id`),
  KEY `stock_adjustment_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `stock_adjustment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_adjustment_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_no` varchar(40) NOT NULL,
  `adjustment_date` date NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `reason` enum('opening','damage','shrinkage','found','recount','other') NOT NULL DEFAULT 'other',
  `status` enum('draft','posted','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_adjustments_adjustment_no_unique` (`adjustment_no`),
  KEY `stock_adjustments_warehouse_id_foreign` (`warehouse_id`),
  KEY `stock_adjustments_created_by_foreign` (`created_by`),
  CONSTRAINT `stock_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_adjustments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_cost_consumptions`
--

DROP TABLE IF EXISTS `stock_cost_consumptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_cost_consumptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_cost_layer_id` bigint(20) unsigned NOT NULL,
  `stock_movement_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_cost_consumptions_stock_cost_layer_id_foreign` (`stock_cost_layer_id`),
  KEY `stock_cost_consumptions_stock_movement_id_foreign` (`stock_movement_id`),
  KEY `stock_cost_consumptions_product_id_foreign` (`product_id`),
  KEY `stock_cost_consumptions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `stock_cost_consumptions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_cost_consumptions_stock_cost_layer_id_foreign` FOREIGN KEY (`stock_cost_layer_id`) REFERENCES `stock_cost_layers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_cost_consumptions_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_cost_layers`
--

DROP TABLE IF EXISTS `stock_cost_layers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_cost_layers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity_remaining` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `unit_cost` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `landed_unit_cost` decimal(14,4) DEFAULT NULL,
  `received_on` date DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `batch_no` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_cost_layers_company_id_foreign` (`company_id`),
  KEY `stock_cost_layers_warehouse_id_foreign` (`warehouse_id`),
  KEY `stock_cost_layers_uom_id_foreign` (`uom_id`),
  KEY `stock_cost_layers_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `stock_cost_layers_lookup_idx` (`product_id`,`warehouse_id`,`received_on`),
  CONSTRAINT `stock_cost_layers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_cost_layers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_cost_layers_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_cost_layers_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_levels`
--

DROP TABLE IF EXISTS `stock_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_levels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_levels_warehouse_product_uom_unique` (`warehouse_id`,`product_id`,`uom_id`),
  KEY `stock_levels_uom_id_foreign` (`uom_id`),
  KEY `stock_levels_product_id_index` (`product_id`),
  CONSTRAINT `stock_levels_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_levels_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_levels_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `type` enum('purchase','sale','adjustment','return','delivery_short','transfer_in','transfer_out','opening','inward','reclassification','purchase_invoice','consumption','manufacture','scrap','receipt_note','receipt_note_return') NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `balance_after` decimal(14,4) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_product_id_foreign` (`product_id`),
  KEY `stock_movements_uom_id_foreign` (`uom_id`),
  KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `stock_movements_created_by_foreign` (`created_by`),
  KEY `stock_movements_warehouse_id_foreign` (`warehouse_id`),
  CONSTRAINT `stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_movements_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`),
  CONSTRAINT `stock_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_reclassifications`
--

DROP TABLE IF EXISTS `stock_reclassifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_reclassifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reclassification_no` varchar(40) NOT NULL,
  `reclassification_date` date NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `from_product_id` bigint(20) unsigned NOT NULL,
  `to_product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_reclassifications_reclassification_no_unique` (`reclassification_no`),
  KEY `stock_reclassifications_warehouse_id_foreign` (`warehouse_id`),
  KEY `stock_reclassifications_from_product_id_foreign` (`from_product_id`),
  KEY `stock_reclassifications_to_product_id_foreign` (`to_product_id`),
  KEY `stock_reclassifications_uom_id_foreign` (`uom_id`),
  KEY `stock_reclassifications_created_by_foreign` (`created_by`),
  CONSTRAINT `stock_reclassifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_reclassifications_from_product_id_foreign` FOREIGN KEY (`from_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_reclassifications_to_product_id_foreign` FOREIGN KEY (`to_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_reclassifications_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_reclassifications_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_reservations`
--

DROP TABLE IF EXISTS `stock_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_reservations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `status` enum('active','released','fulfilled','partial','consumed','cancelled') NOT NULL DEFAULT 'active',
  `due_date` date DEFAULT NULL,
  `reserved_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_reservations_product_id_foreign` (`product_id`),
  KEY `stock_reservations_uom_id_foreign` (`uom_id`),
  KEY `stock_reservations_warehouse_id_foreign` (`warehouse_id`),
  KEY `stock_reservations_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `stock_reservations_created_by_foreign` (`created_by`),
  KEY `stock_reservations_order_id_foreign` (`order_id`),
  KEY `stock_reservations_order_item_id_foreign` (`order_item_id`),
  CONSTRAINT `stock_reservations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_reservations_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_reservations_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_reservations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_reservations_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_reservations_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_transfer_items`
--

DROP TABLE IF EXISTS `stock_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stock_transfer_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(14,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transfer_items_stock_transfer_id_foreign` (`stock_transfer_id`),
  KEY `stock_transfer_items_product_id_foreign` (`product_id`),
  KEY `stock_transfer_items_uom_id_foreign` (`uom_id`),
  CONSTRAINT `stock_transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfer_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_no` varchar(40) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_warehouse_id` bigint(20) unsigned NOT NULL,
  `to_warehouse_id` bigint(20) unsigned NOT NULL,
  `status` enum('draft','in_transit','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_transfers_transfer_no_unique` (`transfer_no`),
  KEY `stock_transfers_from_warehouse_id_foreign` (`from_warehouse_id`),
  KEY `stock_transfers_to_warehouse_id_foreign` (`to_warehouse_id`),
  KEY `stock_transfers_created_by_foreign` (`created_by`),
  CONSTRAINT `stock_transfers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_transfers_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfers_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_valuation_settings`
--

DROP TABLE IF EXISTS `stock_valuation_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_valuation_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `method` enum('fifo','lifo','weighted_avg') NOT NULL DEFAULT 'fifo',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_valuation_company_fy_unique` (`company_id`,`financial_year_id`),
  KEY `stock_valuation_settings_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `stock_valuation_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_valuation_settings_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sub_categories`
--

DROP TABLE IF EXISTS `sub_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sub_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `sub_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `supplier_payables`
--

DROP TABLE IF EXISTS `supplier_payables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_payables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `type` enum('invoice','payment','adjustment','debit_note','credit_note') NOT NULL DEFAULT 'invoice',
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `debit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_payables_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `supplier_payables_supplier_id_id_index` (`supplier_id`,`id`),
  CONSTRAINT `supplier_payables_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sync_entity_links`
--

DROP TABLE IF EXISTS `sync_entity_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_entity_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_key` varchar(60) NOT NULL,
  `dms_type` varchar(120) NOT NULL,
  `dms_id` bigint(20) unsigned NOT NULL,
  `acct_type` varchar(120) NOT NULL,
  `acct_id` bigint(20) unsigned NOT NULL,
  `sync_status` varchar(30) NOT NULL DEFAULT 'synced',
  `sync_version` int(10) unsigned NOT NULL DEFAULT 1,
  `last_error` text DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sync_entity_dms_unique` (`dms_type`,`dms_id`,`entity_key`),
  UNIQUE KEY `sync_entity_acct_unique` (`acct_type`,`acct_id`,`entity_key`),
  KEY `sync_entity_links_entity_key_sync_status_index` (`entity_key`,`sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sync_failures`
--

DROP TABLE IF EXISTS `sync_failures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sync_failures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_key` varchar(60) NOT NULL,
  `direction` varchar(20) NOT NULL,
  `source_type` varchar(120) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sync_failures_entity_key_resolved_at_index` (`entity_key`,`resolved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tally_inbound_logs`
--

DROP TABLE IF EXISTS `tally_inbound_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tally_inbound_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `collection` varchar(40) NOT NULL,
  `item` varchar(255) DEFAULT NULL,
  `action` varchar(20) NOT NULL DEFAULT 'synced',
  `created_count` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_count` int(10) unsigned NOT NULL DEFAULT 0,
  `conflict_count` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_count` int(10) unsigned NOT NULL DEFAULT 0,
  `alter_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tally_inbound_logs_collection_index` (`collection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tally_report_snapshots`
--

DROP TABLE IF EXISTS `tally_report_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tally_report_snapshots` (
  `report` varchar(40) NOT NULL,
  `title` varchar(80) NOT NULL,
  `period` varchar(40) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ok',
  `rows` int(10) unsigned NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`report`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tally_sync_conflicts`
--

DROP TABLE IF EXISTS `tally_sync_conflicts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tally_sync_conflicts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `collection` varchar(40) NOT NULL,
  `remote_id` varchar(120) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `reason` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tally_sync_conflicts_status_id_index` (`status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tally_sync_cursors`
--

DROP TABLE IF EXISTS `tally_sync_cursors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tally_sync_cursors` (
  `collection` varchar(40) NOT NULL,
  `alter_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`collection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tally_sync_mappings`
--

DROP TABLE IF EXISTS `tally_sync_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tally_sync_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(60) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `tally_type` varchar(60) NOT NULL,
  `tally_guid` varchar(255) DEFAULT NULL,
  `tally_name` varchar(255) DEFAULT NULL,
  `sync_status` varchar(30) NOT NULL DEFAULT 'synced',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tally_mapping_dms_unique` (`entity_type`,`entity_id`,`tally_type`),
  KEY `tally_sync_mappings_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `tally_sync_mappings_tally_type_tally_guid_index` (`tally_type`,`tally_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tally_sync_queues`
--

DROP TABLE IF EXISTS `tally_sync_queues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tally_sync_queues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(60) NOT NULL,
  `document_id` bigint(20) unsigned NOT NULL,
  `payload` longtext DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `last_response` longtext DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tally_sync_queues_document_type_document_id_index` (`document_type`,`document_id`),
  KEY `tally_sync_queues_status_created_at_index` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `target_achievements`
--

DROP TABLE IF EXISTS `target_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `target_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `party_target_id` bigint(20) unsigned NOT NULL,
  `target_period_id` bigint(20) unsigned NOT NULL,
  `achieved_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `achievement_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `is_final` tinyint(1) NOT NULL DEFAULT 0,
  `calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `target_achievements_party_target_id_unique` (`party_target_id`),
  KEY `target_achievements_target_period_id_foreign` (`target_period_id`),
  CONSTRAINT `target_achievements_party_target_id_foreign` FOREIGN KEY (`party_target_id`) REFERENCES `party_targets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `target_achievements_target_period_id_foreign` FOREIGN KEY (`target_period_id`) REFERENCES `target_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `target_periods`
--

DROP TABLE IF EXISTS `target_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `target_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `period_type` enum('monthly','quarterly','annual') NOT NULL DEFAULT 'monthly',
  `starts_on` date NOT NULL,
  `ends_on` date NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `target_category` varchar(30) DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `target_periods_period_type_starts_on_ends_on_index` (`period_type`,`starts_on`,`ends_on`),
  KEY `target_periods_brand_id_foreign` (`brand_id`),
  KEY `target_periods_product_id_foreign` (`product_id`),
  KEY `target_periods_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `target_periods_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `target_periods_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `target_periods_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tax_rates`
--

DROP TABLE IF EXISTS `tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tax_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uom_mappings`
--

DROP TABLE IF EXISTS `uom_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uom_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `imported_uom` varchar(100) NOT NULL,
  `uom_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uom_mappings_imported_uom_unique` (`imported_uom`),
  KEY `uom_mappings_uom_id_foreign` (`uom_id`),
  CONSTRAINT `uom_mappings_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uoms`
--

DROP TABLE IF EXISTS `uoms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uoms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uoms_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_branch_access`
--

DROP TABLE IF EXISTS `user_branch_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_branch_access` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_branch_access_user_id_branch_id_unique` (`user_id`,`branch_id`),
  KEY `user_branch_access_branch_id_foreign` (`branch_id`),
  CONSTRAINT `user_branch_access_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_branch_access_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `key` varchar(80) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_preferences_user_id_key_unique` (`user_id`,`key`),
  CONSTRAINT `user_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_id_foreign` (`company_id`),
  KEY `users_branch_id_foreign` (`branch_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `registration_no` varchar(20) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `capacity` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_registration_no_unique` (`registration_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vendor_price_histories`
--

DROP TABLE IF EXISTS `vendor_price_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_price_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `uom_id` bigint(20) unsigned DEFAULT NULL,
  `unit_cost` decimal(14,4) NOT NULL,
  `effective_from` date NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_price_histories_supplier_id_foreign` (`supplier_id`),
  KEY `vendor_price_histories_uom_id_foreign` (`uom_id`),
  KEY `vendor_price_histories_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `vendor_price_histories_created_by_foreign` (`created_by`),
  KEY `vendor_price_hist_lookup_idx` (`product_id`,`supplier_id`,`effective_from`),
  CONSTRAINT `vendor_price_histories_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_price_histories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_price_histories_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_price_histories_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `warehouse_types`
--

DROP TABLE IF EXISTS `warehouse_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(30) NOT NULL,
  `address` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `warehouse_type` varchar(100) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `pincode` varchar(12) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_branch_id_code_unique` (`branch_id`,`code`),
  KEY `warehouses_company_id_foreign` (`company_id`),
  CONSTRAINT `warehouses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-25 12:37:53
