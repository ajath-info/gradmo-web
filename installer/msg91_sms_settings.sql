-- MSG91 SMS gateway settings (read by Common::send_sms). Fill velue_text with your real values.
--   msg91_authkey      = your MSG91 auth key (required)
--   msg91_sender       = 6-char sender id / DLT header (e.g. GRADMO)
--   msg91_route        = route id (default 4 = transactional)
--   msg91_country      = country code (default 91 for India)
--   msg91_template_id  = optional DLT Flow template id; if set, the v5 Flow API is used and the
--                        full message is sent as the single variable "var1" (template must have 1 var).
INSERT INTO `general_settings` (`title`, `key_text`, `velue_text`) VALUES
('MSG91 Auth Key', 'msg91_authkey', ''),
('MSG91 Sender ID', 'msg91_sender', ''),
('MSG91 Route', 'msg91_route', '4'),
('MSG91 Country', 'msg91_country', '91'),
('MSG91 Flow Template ID', 'msg91_template_id', '');
