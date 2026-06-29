-- Migration: Fix batch_zoom_recordings unique key constraint
-- This fixes the "Duplicate entry" error when teachers end class
-- The old constraint (batch_id, zoom_file_id) prevented multiple recordings per batch
-- The new constraint (batch_id, zoom_meeting_id) allows multiple sessions per batch

-- First, check the current table structure
-- Run this to see what constraints exist:
-- SHOW KEYS FROM `batch_zoom_recordings` WHERE Key_name LIKE 'uk_%';

-- IF you have uk_batch_zoom_recording_file constraint, run:
-- ALTER TABLE `batch_zoom_recordings`
--   DROP KEY `uk_batch_zoom_recording_file`;

-- Add the new unique key constraint (this is safe to run even if constraint exists)
-- It will be ignored if uk_batch_zoom_recording_meeting already exists
ALTER TABLE `batch_zoom_recordings`
  ADD CONSTRAINT `uk_batch_zoom_recording_meeting` UNIQUE KEY (`batch_id`, `zoom_meeting_id`);

-- Verification - run this to check constraints after migration:
-- SELECT CONSTRAINT_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
--   WHERE TABLE_NAME='batch_zoom_recordings' AND CONSTRAINT_SCHEMA=DATABASE();
