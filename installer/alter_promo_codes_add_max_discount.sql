-- Promo codes: optional cap on the discount amount in rupees (NULL = no cap).
-- e.g. 100% promo with max_discount = 500 gives at most Rs 500 off.
-- max_use stays "how many times the code can be used".
ALTER TABLE `promo_codes`
  ADD COLUMN `max_discount` DECIMAL(10,2) DEFAULT NULL AFTER `discount_value`;
