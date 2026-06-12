-- Default email templates (status 1 = Active, 0 = Inactive). Adjust html_code in Admin → Templates.
-- Placeholders: {{name}}, {{email}}, {{password}}, {{link}}, {{verify_link}}, {{otp}}, {{batch_name}}, {{enrollment_id}}, {{mobile}}, {{site_name}}, {{support_email}}, {{year}}
-- NOTE: `register` is the SELF-registration email (app/website) and must NOT contain {{password}}.
--       Admin/Institute-created accounts use the institute_register / teacher_register / student_register
--       templates (those DO include {{password}}).

INSERT INTO `templates` (`template_for`, `purpose`, `title`, `image`, `description`, `html_code`, `status`, `updated_at`, `created_at`) VALUES
('email', 'register', 'Welcome to {{site_name}} — verify your email', NULL,
'Your account has been created. Please verify your email to activate it.',
'<p>Hi {{name}},</p><p>Your account on {{site_name}} has been created.</p><p><strong>Email:</strong> {{email}}<br><strong>Mobile:</strong> {{mobile}}<br><strong>Enrollment ID:</strong> {{enrollment_id}}</p><p><strong>Verification OTP:</strong> {{otp}}</p><p><a href=\"{{verify_link}}\" style=\"display:inline-block;padding:10px 18px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;\">Verify my email</a></p><p>Or copy this link: {{verify_link}}</p><p>After verification you can <a href=\"{{link}}\">log in here</a>.</p><p>{{support_email}}</p>',
'1', NOW(), NOW()),

('email', 'account_verified', 'Your {{site_name}} account is verified', NULL,
'Your email address has been verified.',
'<p>Hi {{name}},</p><p>Your email has been verified and your {{site_name}} account is now active.</p><p><a href=\"{{link}}\">Log in to your account</a></p><p>{{support_email}}</p>',
'1', NOW(), NOW()),

('email', 'forgot_password', 'Reset your password - {{site_name}}', NULL,
'Password reset request.',
'<p>Hi {{name}},</p><p>We received a request to reset your password.</p><p><strong>New password:</strong> {{password}}</p><p><a href=\"{{link}}\">Login here</a></p><p>If you did not request this, please contact {{support_email}}.</p>',
'1', NOW(), NOW()),

('email', 'account_delete', 'Account deactivation - {{site_name}}', NULL,
'Your account has been deactivated.',
'<p>Hi {{name}},</p><p>Your account on {{site_name}} has been deactivated as requested.</p><p>If this was a mistake, contact {{support_email}}.</p>',
'1', NOW(), NOW()),

('email', 'enrolled_batch', 'Batch enrollment - {{site_name}}', NULL,
'You have been enrolled in a batch.',
'<p>Hi {{name}},</p><p>You have been successfully enrolled in <strong>{{batch_name}}</strong>.</p><p><strong>Enrollment ID:</strong> {{enrollment_id}}</p><p><a href=\"{{link}}\">Go to login</a></p>',
'1', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `html_code` = VALUES(`html_code`),
  `status` = VALUES(`status`),
  `updated_at` = NOW();

-- Admin/Institute-created accounts (these DO include {{PASSWORD}}) and the live-class invite.
INSERT INTO `templates` (`template_for`, `purpose`, `title`, `image`, `description`, `html_code`, `status`, `updated_at`, `created_at`) VALUES
('email', 'institute_register', 'Registration', NULL,
'Welcome {{NAME}}, your account has been created.',
'<p>Welcome {{NAME}},</p>\n<p>Your account has been created successfully. Please use the login details below to sign in:</p>\n<p><b>Login Email:</b> {{EMAIL}}<br>\n<b>Password:</b> {{PASSWORD}}</p>\n<p>Activate your account using this link: {{LINK}}</p>\n<p>Thank you.</p>',
'1', NOW(), NOW()),

('email', 'teacher_register', 'Registration', NULL,
'Welcome {{NAME}}, your account has been created.',
'<p>Welcome {{NAME}},</p>\n<p>Your account has been created successfully. Please use the login details below to sign in:</p>\n<p><b>Login Email:</b> {{EMAIL}}<br>\n<b>Password:</b> {{PASSWORD}}</p>\n<p>Activate your account using this link: {{LINK}}</p>\n<p>Thank you.</p>',
'1', NOW(), NOW()),

('email', 'student_register', 'Registration', NULL,
'Welcome {{NAME}}, your account has been created.',
'<p>Welcome {{NAME}},</p>\n<p>Your account has been created successfully. Please use the login details below to sign in:</p>\n<p><b>Login Email:</b> {{EMAIL}}<br>\n<b>Password:</b> {{PASSWORD}}</p>\n<p>Activate your account using this link: {{LINK}}</p>\n<p>Thank you.</p>',
'1', NOW(), NOW()),

('email', 'zoom_class_invitation', 'Live Class Invitation - {{BATCH_NAME}}', NULL,
'Hello {{STUDENT_NAME}}, your live class for {{BATCH_NAME}} is scheduled.',
'<p>Dear <strong>{{STUDENT_NAME}}</strong>,</p>\n<p>Your live class for batch <strong>{{BATCH_NAME}}</strong> with {{TEACHER_NAME}} is scheduled.</p>\n<p><strong>Start time:</strong> {{CLASS_START_TIME}}</p>\n<p style=\"margin:24px 0;\"><a href=\"{{CLASS_LINK}}\" style=\"background:#1e88e5;color:#ffffff;text-decoration:none;padding:12px 25px;border-radius:4px;display:inline-block;\">Join Class on Website</a></p>\n<p>Or open this link: <a href=\"{{CLASS_LINK}}\">{{CLASS_LINK}}</a></p>\n<p><strong>Note:</strong> You must be logged in to join the class. Please do not share this link.</p>\n<p>Best Regards,<br>{{INSTITUTE_NAME}}</p>',
'1', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `html_code` = VALUES(`html_code`),
  `status` = VALUES(`status`),
  `updated_at` = NOW();
