-- Gradmo / Education: cached Zoom cloud recordings per batch (synced via api/batch/recorded-meeting-list).
-- Run once after create_batch_zoom_meetings_and_zoom_s2s.sql.
--
-- Zoom Server-to-Server app scopes (Marketplace → Scopes → Cloud Recording):
--   cloud_recording:read:recording:admin           (required — GET /meetings/{id}/recordings)
--   cloud_recording:write:recording:admin          (required — start/stop cloud recording)
--   cloud_recording:read:list_user_recordings:admin (optional — host recording list fallback)
-- After adding scopes: Activate the app, delete application/cache/zoom_s2s_token.json, refresh recordings.

CREATE TABLE IF NOT EXISTS `batch_zoom_recordings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `live_class_id` int(11) NOT NULL DEFAULT 0,
  `teacher_id` int(11) NOT NULL DEFAULT 0,
  `zoom_meeting_id` varchar(32) NOT NULL DEFAULT '',
  `zoom_recording_uuid` varchar(128) NOT NULL DEFAULT '',
  `zoom_file_id` varchar(128) NOT NULL DEFAULT '',
  `topic` varchar(500) NOT NULL DEFAULT '',
  `recording_start` datetime DEFAULT NULL,
  `recording_end` datetime DEFAULT NULL,
  `file_type` varchar(32) NOT NULL DEFAULT '',
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `play_url` varchar(2048) NOT NULL DEFAULT '',
  `download_url` varchar(2048) NOT NULL DEFAULT '',
  `recording_type` varchar(64) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT 'completed',
  `source` varchar(32) NOT NULL DEFAULT 'zoom_cloud',
  `sync_error` varchar(512) NOT NULL DEFAULT '',
  `synced_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_batch_zoom_recording_meeting` (`batch_id`,`zoom_meeting_id`),
  KEY `idx_bzr_batch` (`batch_id`),
  KEY `idx_bzr_live_class` (`live_class_id`),
  KEY `idx_bzr_meeting` (`zoom_meeting_id`),
  KEY `idx_bzr_teacher` (`teacher_id`),
  KEY `idx_bzr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
