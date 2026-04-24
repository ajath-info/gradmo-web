-- DEPRECATED: consolidated into `student_payment_history`.
-- Use installer/alter_student_payment_history_merge_payments.sql instead.
-- The Razorpay APIs now write a single row in `student_payment_history` only.

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL DEFAULT 0,
  `gateway` varchar(32) NOT NULL DEFAULT 'razorpay',
  `order_id` varchar(128) NOT NULL DEFAULT '',
  `payment_id` varchar(128) NOT NULL DEFAULT '',
  `amount` int(11) NOT NULL DEFAULT 0,
  `currency` varchar(12) NOT NULL DEFAULT 'INR',
  `gateway_status` varchar(48) NOT NULL DEFAULT '',
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_batch_payment` (`student_id`,`batch_id`,`payment_id`),
  KEY `idx_batch` (`batch_id`),
  KEY `idx_payment` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
