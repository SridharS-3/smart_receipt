-- Seed: sample_data.sql
-- Description: Realistic sample contacts and receipts for development and testing

-- Sample Beneficiary Contacts
INSERT INTO `contacts` (`name`, `account_number`, `ifsc_code`, `bank_name`, `payment_type`, `email`, `phone`, `notes`)
VALUES 
  ('Rajesh Sharma', '918273645012', 'HDFC0001234', 'HDFC Bank', 'IMPS', 'rajesh.sharma@example.com', '+91 98765 43210', 'Primary vendor for office supplies'),
  ('Priya Patel', '309876543210', 'SBIN0004567', 'State Bank of India', 'NEFT', 'priya.patel@example.com', '+91 98123 45678', 'Consultant retainer'),
  ('Amit Kumar Verma', '50100234567890', 'ICIC0000456', 'ICICI Bank', 'RTGS', 'amit.verma@example.com', '+91 97234 56789', 'Equipment lease payment'),
  ('Sunita Reddy', '012345678901', 'UTIB0000789', 'Axis Bank', 'UPI', 'sunita.reddy@example.com', '+91 99345 67890', 'Freelance UI/UX designer'),
  ('Vikram Malhotra', '112233445566', 'KKBK0000123', 'Kotak Mahindra Bank', 'IMPS', 'vikram.m@example.com', '+91 98456 78901', 'Monthly software subscription')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Sample Transaction Receipts
INSERT INTO `receipts` (`receipt_no`, `template_type`, `status`, `amount`, `transaction_type`, `transaction_id`, `banking_name`, `account_number`, `ifsc_code`, `payment_date`, `generated_type`, `batch_id`)
VALUES
  ('REC-DEMO-001', 'template3', 'Successful', 15450.00, 'IMPS', 'UTR983274619283', 'Rajesh Sharma', '918273645012', 'HDFC0001234', NOW() - INTERVAL 1 DAY, 'single', NULL),
  ('REC-DEMO-002', 'template2', 'Successful', 45000.00, 'NEFT', 'UTR128374659201', 'Priya Patel', '309876543210', 'SBIN0004567', NOW() - INTERVAL 2 DAY, 'single', NULL),
  ('REC-DEMO-003', 'template1', 'Successful', 125000.00, 'RTGS', 'UTR554433221100', 'Amit Kumar Verma', '50100234567890', 'ICIC0000456', NOW() - INTERVAL 3 DAY, 'single', NULL),
  ('REC-DEMO-004', 'template3', 'Successful', 3200.00, 'UPI', 'UPI889977665544', 'Sunita Reddy', '012345678901', 'UTIB0000789', NOW() - INTERVAL 4 DAY, 'single', NULL)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

