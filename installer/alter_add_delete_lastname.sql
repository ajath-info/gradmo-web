-- ---------------------------------------------------------------------------
-- Adds:
--   1. `deleted` ENUM('0','1') — account deletion flag (0 = not deleted, 1 = deleted).
--      Kept separate from `status` (which is admin active/inactive).
--   2. `last_name` — user/student last name, managed alongside `name`.
-- For both `users` and `students` tables.
-- ---------------------------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN `last_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `name`,
  ADD COLUMN `deleted` ENUM('0','1') NOT NULL DEFAULT '0' AFTER `status`;

ALTER TABLE `students`
  ADD COLUMN `last_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `name`,
  ADD COLUMN `deleted` ENUM('0','1') NOT NULL DEFAULT '0' AFTER `status`;
