-- Add columns to track when host joins and class ends
ALTER TABLE `batch_zoom_meetings` ADD COLUMN `host_joined_at` datetime DEFAULT NULL AFTER `start_time`;
ALTER TABLE `batch_zoom_meetings` ADD COLUMN `ended_at` datetime DEFAULT NULL AFTER `host_joined_at`;
