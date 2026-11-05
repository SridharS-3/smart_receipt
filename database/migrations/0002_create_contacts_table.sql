-- Migration: 0002_create_contacts_table.sql
-- Description: Create contacts directory table for managing beneficiaries

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

