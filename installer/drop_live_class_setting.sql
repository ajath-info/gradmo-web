-- Remove legacy per-batch Zoom table (replaced by zoom_api_credentials + batch_zoom_meetings).
-- Run once on your MySQL server after backup.

DROP TABLE IF EXISTS `live_class_setting`;
