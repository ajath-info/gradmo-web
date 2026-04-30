-- Optional: run once on your DB if these columns are missing (institute admin form).
-- MySQL / MariaDB — adjust table prefix if you use one.

ALTER TABLE `users` ADD COLUMN `institute_code` VARCHAR(64) NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `school_college_name` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `grade` VARCHAR(64) NULL DEFAULT NULL;
