-- Migration: 0003_create_receipts_table.sql
-- Description: Create receipts table for generated single and bulk payment receipts

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
  UNIQUE KEY `idx_receipts_receipt_no` (`receipt_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

