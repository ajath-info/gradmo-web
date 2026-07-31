-- Store actual Zoom cloud recording length for the shared 330-hour batch quota.
-- Run once on databases that already have batch_zoom_recordings.

ALTER TABLE `batch_zoom_recordings`
  ADD COLUMN `duration_seconds` int(11) NOT NULL DEFAULT 0 AFTER `recording_end`;
