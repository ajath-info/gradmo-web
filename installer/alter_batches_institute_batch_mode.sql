-- Run once on existing databases (columns are included in installer/default.sql for fresh installs).
ALTER TABLE `batches`
  ADD COLUMN `institute_id` int(11) NOT NULL DEFAULT 0 AFTER `pay_mode`,
  ADD COLUMN `batch_mode` varchar(20) NOT NULL DEFAULT 'Online' AFTER `institute_id`;
