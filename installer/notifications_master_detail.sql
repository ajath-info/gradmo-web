-- Notifications master/detail model.
--
-- `notifications` becomes the MASTER (one row per event). Recipients + per-recipient read-state
-- live in `push_notifications_details`. The legacy per-student `notifications.student_id` column
-- is removed; a `title` column is added for the push/master heading.
--
-- Run once against an existing install. (Fresh installs get the new shape from default.sql.)
--
-- NOTE: the detail table is spelled `push_notifications_details` (no second "i") everywhere in
-- the code. Do NOT rename it to `push_notifications_details`.

-- 1) Add the master `title` column (idempotent-ish: ignore error if it already exists).
ALTER TABLE `notifications`
  ADD COLUMN `title` VARCHAR(255) NOT NULL DEFAULT '' AFTER `notification_type`;

-- 2) Drop the per-student column now that recipients live in push_notifications_details.
ALTER TABLE `notifications`
  DROP COLUMN `student_id`;

-- 3) Create the per-recipient detail/log table if it doesn't already exist.
--    One row per user the notification was fanned out to (push delivery log + read flag).
CREATE TABLE IF NOT EXISTS `push_notifications_details` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pushnotify_id` int(10) UNSIGNED NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `user_type` tinyint(4) DEFAULT NULL COMMENT '1=>student,2=>teacher,3=>institute',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=>failed,1=>success',
  `notification_logs` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notifcations_request` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivered_status` tinyint(4) NOT NULL DEFAULT 0,
  `delivered_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `events` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=>default,1=>delivered,2=>failed,3=>invalid',
  `read` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pushnotify` (`pushnotify_id`),
  KEY `idx_user` (`userid`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) If the table already existed without it, ensure the per-recipient read flag is present.
--    (Skip/ignore this statement if the column already exists.)
ALTER TABLE `push_notifications_details`
  ADD COLUMN `read` TINYINT(4) NOT NULL DEFAULT 0;
