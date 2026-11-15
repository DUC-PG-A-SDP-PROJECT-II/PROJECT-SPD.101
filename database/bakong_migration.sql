-- Bakong KHQR Migration Script
-- Safe migration: preserves all existing data and creates necessary tables/columns

CREATE TABLE IF NOT EXISTS `tbl_bakong_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(255) NOT NULL,
  `order_number` varchar(100) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `amount_khr` decimal(14,2) NOT NULL DEFAULT 0.00,
  `bakong_account_id` varchar(100) NOT NULL DEFAULT 'rathanychoeur@bkrt',
  `merchant_name` varchar(150) NOT NULL DEFAULT 'Rathany Choeur',
  `merchant_city` varchar(100) NOT NULL DEFAULT 'Phnom Penh',
  `khqr_string` text NOT NULL,
  `khqr_md5` varchar(64) NOT NULL,
  `trx_hash` varchar(128) DEFAULT NULL,
  `from_account_id` varchar(100) DEFAULT NULL,
  `to_account_id` varchar(100) DEFAULT NULL,
  `payment_status` varchar(25) NOT NULL DEFAULT 'PENDING',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `raw_response` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_payment_id` (`payment_id`),
  UNIQUE KEY `idx_khqr_md5` (`khqr_md5`),
  KEY `idx_trx_hash` (`trx_hash`),
  KEY `idx_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

