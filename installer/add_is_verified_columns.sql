-- Email verification columns (run once if missing).
-- Safe to run: duplicate column errors can be ignored on re-run.

ALTER TABLE `users` ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `students` ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0;
