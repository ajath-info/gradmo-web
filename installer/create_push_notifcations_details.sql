-- Per-recipient push delivery rows. Parent (one row per event) lives in `notifications`;
-- `pushnotify_id` references `notifications`.`id`. One row per user the push was sent to.
CREATE TABLE IF NOT EXISTS `push_notifications_details` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pushnotify_id` int(10) UNSIGNED NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `user_type` tinyint(4) DEFAULT NULL COMMENT '1=>student,2=>teacher,3=>institute',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=>failed,1=>succes',
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
