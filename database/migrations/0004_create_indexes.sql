-- Migration: 0004_create_indexes.sql
-- Description: Create query performance and lookup indexes for receipts, contacts, and admins

-- Receipts indexes
CREATE INDEX IF NOT EXISTS `idx_receipts_transaction_id` ON `receipts` (`transaction_id`);
CREATE INDEX IF NOT EXISTS `idx_receipts_batch_id` ON `receipts` (`batch_id`);
CREATE INDEX IF NOT EXISTS `idx_receipts_payment_date` ON `receipts` (`payment_date`);
CREATE INDEX IF NOT EXISTS `idx_receipts_account_number` ON `receipts` (`account_number`);
CREATE INDEX IF NOT EXISTS `idx_receipts_banking_name` ON `receipts` (`banking_name`);

-- Contacts indexes
CREATE INDEX IF NOT EXISTS `idx_contacts_account` ON `contacts` (`account_number`);
CREATE INDEX IF NOT EXISTS `idx_contacts_name` ON `contacts` (`name`);

-- Admins indexes
CREATE INDEX IF NOT EXISTS `idx_admins_email` ON `admins` (`email`);

