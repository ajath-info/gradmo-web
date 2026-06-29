-- Adds a banner image column for institutes (admin/institute-manage).
-- Safe to run once. If the column already exists, MySQL will report an error you can ignore.
ALTER TABLE `users` ADD COLUMN `banner` VARCHAR(255) NULL DEFAULT NULL AFTER `teach_image`;
