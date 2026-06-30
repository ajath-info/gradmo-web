-- Promo codes managed from the admin panel (Admin / Institute).
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `promo_code_id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) DEFAULT NULL,
  `discount_type` ENUM('PERCENT','FLAT') DEFAULT NULL,
  `discount_value` DECIMAL(10,2) DEFAULT NULL,
  `valid_from` DATE DEFAULT NULL,
  `valid_to` DATE DEFAULT NULL,
  `max_use` INT DEFAULT NULL,
  `used_count` INT DEFAULT 0,
  `status` TINYINT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`promo_code_id`),
  UNIQUE KEY `uq_promo_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
