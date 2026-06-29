-- Single-session enforcement: record the issued-at (iat) of the latest app access token.
-- Any presented token whose iat is older than this value is rejected (You are not authorized / 1008).
ALTER TABLE `students` ADD COLUMN `app_token_iat` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `app_token_iat` INT UNSIGNED NOT NULL DEFAULT 0;
