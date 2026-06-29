-- One-time: copy rows from `payments` into `student_payment_history` (skip duplicates by student/batch/transaction key).
INSERT INTO student_payment_history (
	student_id,
	batch_id,
	plan_id,
	transaction_id,
	mode,
	amount,
	admin_id,
	base_amount,
	batch_fee,
	total_amount,
	promo_code_id,
	discount_amount,
	razorpay_order_id,
	razorpay_payment_id,
	payment_status,
	payment_date,
	create_at
)
SELECT
	p.student_id,
	p.batch_id,
	p.plan_id,
	COALESCE(NULLIF(TRIM(p.razorpay_payment_id), ''), CONCAT('legacy-payment-', p.payment_id)),
	'razorpay',
	GREATEST(1, CAST(ROUND(p.total_amount) AS UNSIGNED)),
	IFNULL(b.admin_id, 0),
	p.base_amount,
	p.batch_fee,
	p.total_amount,
	p.promo_code_id,
	IFNULL(p.discount_amount, 0.00),
	p.razorpay_order_id,
	p.razorpay_payment_id,
	CAST(p.payment_status AS CHAR),
	COALESCE(p.payment_date, p.created_at, NOW()),
	COALESCE(p.created_at, NOW())
FROM payments p
LEFT JOIN batches b ON b.id = p.batch_id
WHERE NOT EXISTS (
	SELECT 1
	FROM student_payment_history h
	WHERE h.student_id = p.student_id
		AND h.batch_id = p.batch_id
		AND h.transaction_id = COALESCE(NULLIF(TRIM(p.razorpay_payment_id), ''), CONCAT('legacy-payment-', p.payment_id))
);
