-- Store Zoom cloud recording play passcode so the site can open play_url with ?pwd=
-- without asking teacher/student to type it.
-- Run once. Skip if column already exists.

ALTER TABLE `batch_zoom_recordings`
  ADD COLUMN `recording_passcode` varchar(128) NOT NULL DEFAULT '' COMMENT 'Zoom recording_play_passcode / password for share play' AFTER `download_url`;
