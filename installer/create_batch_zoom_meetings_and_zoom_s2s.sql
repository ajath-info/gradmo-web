-- Gradmo / Education: batch-level Zoom (REST) + audit log + Server-to-Server credential columns.
-- Run once on your MySQL server after backup. Adjust if `zoom_api_credentials` columns already exist.

CREATE TABLE IF NOT EXISTS `batch_zoom_meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `zoom_meeting_id` varchar(32) NOT NULL DEFAULT '',
  `join_url` varchar(1024) NOT NULL DEFAULT '',
  `start_url` varchar(1024) NOT NULL DEFAULT '',
  `password` varchar(128) NOT NULL DEFAULT '',
  `host_id` varchar(64) NOT NULL DEFAULT '',
  `topic` varchar(500) NOT NULL DEFAULT '',
  `agenda` text,
  `start_time` datetime DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 60,
  `timezone` varchar(64) NOT NULL DEFAULT 'UTC',
  `meeting_type` tinyint(4) NOT NULL DEFAULT 3 COMMENT 'Zoom API type: 1 instant, 2 scheduled, 3 recurring no fixed time',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 active, 0 deleted on Zoom',
  `raw_json` longtext,
  `created_by_uid` int(11) NOT NULL DEFAULT 0,
  `created_by_ut` varchar(32) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_batch_zoom_batch` (`batch_id`),
  KEY `idx_batch_zoom_meeting` (`zoom_meeting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `zoom_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL DEFAULT 0,
  `action` varchar(64) NOT NULL DEFAULT '',
  `http_status` int(11) NOT NULL DEFAULT 0,
  `message` varchar(512) NOT NULL DEFAULT '',
  `request_json` longtext,
  `response_json` longtext,
  `user_uid` int(11) NOT NULL DEFAULT 0,
  `user_ut` varchar(32) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_zoom_logs_batch` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extend app-level Zoom credential store (legacy rows may only have android_* keys).
-- If any ALTER fails because a column exists, skip that line manually.
ALTER TABLE `zoom_api_credentials`
  ADD COLUMN `s2s_account_id` varchar(64) NOT NULL DEFAULT '' COMMENT 'Zoom Server-to-Server OAuth: Account ID' AFTER `android_api_secret`,
  ADD COLUMN `s2s_client_id` varchar(128) NOT NULL DEFAULT '' COMMENT 'S2S OAuth Client ID' AFTER `s2s_account_id`,
  ADD COLUMN `s2s_client_secret` varchar(256) NOT NULL DEFAULT '' COMMENT 'S2S OAuth Client Secret' AFTER `s2s_client_id`,
  ADD COLUMN `zoom_host_email` varchar(255) NOT NULL DEFAULT '' COMMENT 'Licensed Zoom user email (meeting host)' AFTER `s2s_client_secret`,
  ADD COLUMN `zoom_host_user_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'Optional cache of Zoom user id for host' AFTER `zoom_host_email`;
