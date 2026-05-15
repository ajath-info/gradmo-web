-- Zoom Meeting SDK (in-app join) — separate from Server-to-Server OAuth.
-- Run once in phpMyAdmin after backup.

ALTER TABLE `zoom_api_credentials`
  ADD COLUMN `meeting_sdk_key` varchar(128) NOT NULL DEFAULT '' COMMENT 'Meeting SDK Client ID / SDK Key' AFTER `zoom_host_user_id`,
  ADD COLUMN `meeting_sdk_secret` varchar(256) NOT NULL DEFAULT '' COMMENT 'Meeting SDK Client Secret' AFTER `meeting_sdk_key`;
