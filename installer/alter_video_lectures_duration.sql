-- Add duration tracking to video lectures for the 330-hour/year per-batch cap.
-- Duration is stored in seconds. URL/YouTube lectures stay 0 and are not counted.
ALTER TABLE `video_lectures`
  ADD `duration_seconds` INT(11) NOT NULL DEFAULT 0;
