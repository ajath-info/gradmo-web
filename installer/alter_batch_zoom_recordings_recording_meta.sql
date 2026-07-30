-- Optional columns for cloud recording ownership + sync diagnostics.
-- Your existing columns (play_url, download_url, zoom_*, status, etc.) are already enough
-- for Zoom URL storage. These extras help track who recorded and why sync failed.
--
-- Run once in phpMyAdmin / MySQL. If a column already exists, skip that ADD line.

ALTER TABLE `batch_zoom_recordings`
  ADD COLUMN `teacher_id` int(11) NOT NULL DEFAULT 0 COMMENT 'Teacher/institute user id who started recording' AFTER `live_class_id`,
  ADD COLUMN `source` varchar(32) NOT NULL DEFAULT 'zoom_cloud' COMMENT 'zoom_cloud | local_upload' AFTER `status`,
  ADD COLUMN `sync_error` varchar(512) NOT NULL DEFAULT '' COMMENT 'Last Zoom sync error (empty when OK)' AFTER `source`;

-- Helpful indexes (safe to skip if they already exist)
ALTER TABLE `batch_zoom_recordings`
  ADD KEY `idx_bzr_teacher` (`teacher_id`),
  ADD KEY `idx_bzr_status` (`status`);
