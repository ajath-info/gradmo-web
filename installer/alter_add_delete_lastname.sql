-- ---------------------------------------------------------------------------
-- Adds:
--   1. `delete` ENUM('0','1') — account deletion flag (0 = not deleted, 1 = deleted).
--      Kept separate from `status` (which is admin active/inactive).
--   2. `last_name` — user/student last name, managed alongside `name`.
-- For both `users` and `students` tables.
--
-- Note: `delete` is a reserved word, always reference it back-quoted: `delete`.
-- ---------------------------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN `last_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `name`,
  ADD COLUMN `delete` ENUM('0','1') NOT NULL DEFAULT '0' AFTER `status`;

ALTER TABLE `students`
  ADD COLUMN `last_name` VARCHAR(255) NOT NULL DEFAULT '' AFTER `name`,
  ADD COLUMN `delete` ENUM('0','1') NOT NULL DEFAULT '0' AFTER `status`;
