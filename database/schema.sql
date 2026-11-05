-- ==============================================================================
-- SmartReceipt - Complete Database Schema & Initial Data
-- MySQL / MariaDB (PHP 8.x + LAMPP / XAMPP / Docker / Production)
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. Table structure for table `admins`
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_admins_username` (`username`),
  UNIQUE KEY `idx_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Table structure for table `contacts`
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `account_number` VARCHAR(100) NOT NULL,
  `ifsc_code` VARCHAR(50) DEFAULT NULL,
  `bank_name` VARCHAR(150) DEFAULT NULL,
  `payment_type` VARCHAR(50) DEFAULT 'IMPS',
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_account` (`account_number`),
  KEY `idx_contacts_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Table structure for table `receipts`
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `receipts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `receipt_no` VARCHAR(100) NOT NULL,
  `template_type` VARCHAR(50) DEFAULT 'template1',
  `status` VARCHAR(50) NOT NULL DEFAULT 'Successful',
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `transaction_type` VARCHAR(50) DEFAULT 'IMPS',
  `transaction_id` VARCHAR(100) NOT NULL,
  `banking_name` VARCHAR(255) NOT NULL,
  `account_number` VARCHAR(100) NOT NULL,
  `ifsc_code` VARCHAR(50) DEFAULT NULL,
  `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `generated_type` ENUM('single','bulk') DEFAULT 'single',
  `batch_id` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_receipts_receipt_no` (`receipt_no`),
  KEY `idx_receipts_transaction_id` (`transaction_id`),
  KEY `idx_receipts_batch_id` (`batch_id`),
  KEY `idx_receipts_payment_date` (`payment_date`),
  KEY `idx_receipts_account_number` (`account_number`),
  KEY `idx_receipts_banking_name` (`banking_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Default Seed: Administrator Account
-- Default credentials:
-- Email: admin@example.com (or username: admin)
-- Password: admin123
-- ------------------------------------------------------------------------------
INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `created_at`)
VALUES (1, 'admin', 'admin@example.com', '$2y$10$G4dcvCujWfWDiTWwOvJF2eUqKx.X7TQ4kyPsy9ZeqcjcgfGqt8ZTu', NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

SET FOREIGN_KEY_CHECKS = 1;

