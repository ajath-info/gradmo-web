-- ---------------------------------------------------------------------------
-- Email template updates
--
-- Run this on the PRODUCTION database to match the local changes:
--   1. Admin/Institute-created accounts (institute / teacher / student) get an
--      email that includes the login Email AND Password ({{PASSWORD}}).
--   2. Self-registration (API: app/website) gets a generic `register` email
--      WITHOUT the password (OTP + welcome only).
--   3. The live-class invitation email no longer exposes the raw Zoom link.
--      It is activated (status='0') and points students to the website
--      "Join Class" page ({{CLASS_LINK}}) where access is gated.
--
-- Safe to run more than once (UPDATEs / upsert are idempotent).
-- ---------------------------------------------------------------------------

-- NOTE: templates.status convention is 1 = ACTIVE, 0 = INACTIVE.
--       The email engine only sends templates with status='1'.

-- 1) Admin/Institute-created accounts: activate + include login email + password
UPDATE templates
SET status = '1',
    html_code = '<p>Welcome {{NAME}},</p>\n<p>Your account has been created successfully. Please use the login details below to sign in:</p>\n<p><b>Login Email:</b> {{EMAIL}}<br>\n<b>Password:</b> {{PASSWORD}}</p>\n<p>Activate your account using this link: {{LINK}}</p>\n<p>Thank you.</p>',
    description = 'Welcome {{NAME}}, your account has been created.'
WHERE purpose IN ('institute_register','teacher_register','student_register')
  AND template_for = 'email';

-- 1b) Self-registration email (purpose `register`): WITHOUT password ----------
--     Created if missing; if it already exists it is activated. Never edits the
--     html_code on an existing row so any custom wording you set is preserved.
INSERT INTO templates (purpose, template_for, status, title, description, html_code, image)
VALUES (
  'register', 'email', '1', 'Welcome to {{site_name}}',
  'Welcome {{NAME}}, your account has been created.',
  '<p>Welcome {{NAME}},</p>\n<p>Your account has been created successfully with the email <b>{{EMAIL}}</b>.</p>\n<p>Your verification OTP is: <b>{{OTP}}</b></p>\n<p>Please enter this OTP in the app or website to verify your account.</p>\n<p>Thank you.</p>',
  ''
)
ON DUPLICATE KEY UPDATE status = '1';

-- 2) Live-class invitation: activate + send website page instead of Zoom link -
UPDATE templates SET
  status = '1',
  title = 'Live Class Invitation - {{BATCH_NAME}}',
  description = 'Hello {{STUDENT_NAME}}, your live class for {{BATCH_NAME}} is scheduled.',
  html_code = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Online Class Invitation</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f6f8">
        <tr>
            <td align="center" style="padding:30px 15px;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:8px; overflow:hidden;">
                    <tr>
                        <td align="center" bgcolor="#1e88e5" style="padding:25px;">
                            <h1 style="margin:0; color:#ffffff; font-size:24px;">Online Class Invitation</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px; color:#333333; font-size:15px; line-height:24px;">
                            <p>Dear <strong>{{STUDENT_NAME}}</strong>,</p>
                            <p>This is a reminder about your upcoming online class. Please find the details below:</p>
                            <table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse; margin:20px 0;">
                                <tr>
                                    <td width="35%" bgcolor="#f5f5f5"><strong>Teacher Name</strong></td>
                                    <td>{{TEACHER_NAME}}</td>
                                </tr>
                                <tr>
                                    <td bgcolor="#f5f5f5"><strong>Batch Name</strong></td>
                                    <td>{{BATCH_NAME}}</td>
                                </tr>
                                <tr>
                                    <td bgcolor="#f5f5f5"><strong>Class Start Time</strong></td>
                                    <td>{{CLASS_START_TIME}}</td>
                                </tr>
                            </table>
                            <p style="text-align:center; margin:30px 0;">
                                <a href="{{CLASS_LINK}}" style="background:#1e88e5; color:#ffffff; text-decoration:none; padding:12px 25px; border-radius:4px; display:inline-block;">Join Class on Website</a>
                            </p>
                            <p style="text-align:center; font-size:13px; color:#777777;">Or open this link: <a href="{{CLASS_LINK}}">{{CLASS_LINK}}</a></p>
                            <p><strong>Note:</strong> You must be logged in to your account to join the class. Please do not share this link with anyone.</p>
                            <p>Best Regards,<br><strong>{{INSTITUTE_NAME}}</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" bgcolor="#f4f6f8" style="padding:15px; color:#777777; font-size:12px;">Copyright {{CURRENT_YEAR}}. All Rights Reserved.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>'
WHERE purpose = 'zoom_class_invitation'
  AND template_for = 'email';
