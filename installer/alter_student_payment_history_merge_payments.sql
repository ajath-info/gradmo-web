-- Merge former `payments` ledger fields into `student_payment_history` (single source of truth).
-- Run once on your MySQL/MariaDB. If a column already exists, skip that line or remove the duplicate from this script.

ALTER TABLE `student_payment_history`
	ADD COLUMN `plan_id` int(11) NOT NULL DEFAULT 0 AFTER `batch_id`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `base_amount` decimal(10,2) NULL DEFAULT NULL AFTER `plan_id`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `batch_fee` decimal(10,2) NULL DEFAULT NULL AFTER `base_amount`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `total_amount` decimal(10,2) NULL DEFAULT NULL AFTER `batch_fee`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `promo_code_id` int(11) NULL DEFAULT NULL AFTER `total_amount`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `discount_amount` decimal(10,2) NULL DEFAULT 0.00 AFTER `promo_code_id`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `razorpay_order_id` varchar(100) NULL DEFAULT NULL AFTER `discount_amount`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `razorpay_payment_id` varchar(100) NULL DEFAULT NULL AFTER `razorpay_order_id`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `payment_status` varchar(20) NULL DEFAULT NULL AFTER `razorpay_payment_id`;

ALTER TABLE `student_payment_history`
	ADD COLUMN `payment_date` datetime NULL DEFAULT NULL AFTER `payment_status`;

-- Optional: copy data from legacy `payments` into `student_payment_history` if you still have both tables
-- (adjust column names to match your live `payments` schema before running).
-- INSERT INTO student_payment_history (student_id, batch_id, plan_id, transaction_id, mode, amount, admin_id,
--   base_amount, batch_fee, total_amount, promo_code_id, discount_amount, razorpay_order_id, razorpay_payment_id,
--   payment_status, payment_date, create_at)
-- SELECT p.student_id, p.batch_id, p.plan_id, COALESCE(p.razorpay_payment_id, CONCAT('pay-', p.payment_id)),
--   'razorpay', p.amount, p.admin_id, p.base_amount, p.batch_fee, p.total_amount, p.promo_code_id, p.discount_amount,
--   p.razorpay_order_id, p.razorpay_payment_id, p.payment_status, p.payment_date, COALESCE(p.created_at, NOW())
-- FROM payments p
-- WHERE NOT EXISTS (
--   SELECT 1 FROM student_payment_history h
--   WHERE h.student_id = p.student_id AND h.batch_id = p.batch_id
--     AND (h.razorpay_payment_id <=> p.razorpay_payment_id OR h.transaction_id <=> p.razorpay_payment_id)
-- );
