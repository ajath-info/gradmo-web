-- Default email templates (status 0 = Active). Adjust html_code in Admin → Templates.
-- Placeholders: {{name}}, {{email}}, {{password}}, {{link}}, {{verify_link}}, {{otp}}, {{batch_name}}, {{enrollment_id}}, {{mobile}}, {{site_name}}, {{support_email}}, {{year}}

INSERT INTO `templates` (`template_for`, `purpose`, `title`, `image`, `description`, `html_code`, `status`, `updated_at`, `created_at`) VALUES
('email', 'register', 'Welcome to {{site_name}} — verify your email', NULL,
'Your account has been created. Please verify your email to activate it.',
'<p>Hi {{name}},</p><p>Your account on {{site_name}} has been created.</p><p><strong>Email:</strong> {{email}}<br><strong>Mobile:</strong> {{mobile}}<br><strong>Password:</strong> {{password}}<br><strong>Enrollment ID:</strong> {{enrollment_id}}</p><p><a href=\"{{verify_link}}\" style=\"display:inline-block;padding:10px 18px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;\">Verify my email</a></p><p>Or copy this link: {{verify_link}}</p><p>After verification you can <a href=\"{{link}}\">log in here</a>.</p><p>{{support_email}}</p>',
'0', NOW(), NOW()),

('email', 'account_verified', 'Your {{site_name}} account is verified', NULL,
'Your email address has been verified.',
'<p>Hi {{name}},</p><p>Your email has been verified and your {{site_name}} account is now active.</p><p><a href=\"{{link}}\">Log in to your account</a></p><p>{{support_email}}</p>',
'0', NOW(), NOW()),

('email', 'forgot_password', 'Reset your password - {{site_name}}', NULL,
'Password reset request.',
'<p>Hi {{name}},</p><p>We received a request to reset your password.</p><p><strong>New password:</strong> {{password}}</p><p><a href=\"{{link}}\">Login here</a></p><p>If you did not request this, please contact {{support_email}}.</p>',
'0', NOW(), NOW()),

('email', 'account_delete', 'Account deactivation - {{site_name}}', NULL,
'Your account has been deactivated.',
'<p>Hi {{name}},</p><p>Your account on {{site_name}} has been deactivated as requested.</p><p>If this was a mistake, contact {{support_email}}.</p>',
'0', NOW(), NOW()),

('email', 'enrolled_batch', 'Batch enrollment - {{site_name}}', NULL,
'You have been enrolled in a batch.',
'<p>Hi {{name}},</p><p>You have been successfully enrolled in <strong>{{batch_name}}</strong>.</p><p><strong>Enrollment ID:</strong> {{enrollment_id}}</p><p><a href=\"{{link}}\">Go to login</a></p>',
'0', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `html_code` = VALUES(`html_code`),
  `status` = VALUES(`status`),
  `updated_at` = NOW();
