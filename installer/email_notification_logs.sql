-- Tracks one-time / periodic notification emails so crons don't re-send.
CREATE TABLE IF NOT EXISTS `email_notification_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `purpose` VARCHAR(100) NOT NULL DEFAULT '',
  `user_id` INT(11) NOT NULL DEFAULT 0,
  `user_type` VARCHAR(30) NOT NULL DEFAULT '',
  `batch_id` INT(11) NOT NULL DEFAULT 0,
  `period_key` VARCHAR(30) NOT NULL DEFAULT '',
  `email` VARCHAR(200) NOT NULL DEFAULT '',
  `sent_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_notice` (`purpose`,`user_id`,`user_type`,`batch_id`,`period_key`),
  KEY `idx_lookup` (`purpose`,`period_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
