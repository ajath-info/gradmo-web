-- Run once on existing databases if row id 18 is free (matches default.sql id sequence).
-- If id 18 is taken, insert with the next free id or omit id column if your table is AUTO_INCREMENT.
INSERT INTO `general_settings` (`id`, `title`, `key_text`, `velue_text`)
VALUES (18, 'Razorpay webhook signing secret (Dashboard → Webhooks)', 'razorpay_webhook_secret', '');
