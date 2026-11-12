-- Optional batch link for gallery banners: home slider "Enroll now" opens batch details.
ALTER TABLE gallery ADD COLUMN batch_id INT(11) NULL DEFAULT NULL AFTER purpose;
