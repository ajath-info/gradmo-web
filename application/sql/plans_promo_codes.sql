-- Run once in your MySQL database (e.g. phpMyAdmin or mysql CLI) before calling the plan APIs.

CREATE TABLE IF NOT EXISTS plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100),
    plan_type ENUM('FIRST_PAYMENT','RENEWAL'),
    amount DECIMAL(10,2) NOT NULL,
    validity_days INT NOT NULL,
    description TEXT,
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS promo_codes (
    promo_code_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    discount_type ENUM('PERCENT','FLAT'),
    discount_value DECIMAL(10,2),
    valid_from DATE,
    valid_to DATE,
    max_use INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
