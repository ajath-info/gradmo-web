<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central notification fan-out (push + in-app), MASTER/DETAIL model.
 *
 * Every notification event writes exactly ONE master row in `notifications` and ONE row per
 * recipient in `push_notifications_details` (which also carries the per-recipient `read` flag).
 *   - single recipient  => 1 `notifications` + 1 `push_notifications_details`
 *   - multiple recipients => 1 `notifications` + N `push_notifications_details`
 *
 * `notifications` has NO `student_id`: the recipient list lives entirely in the detail table.
 * FCM is sent to recipients that have a device token; a detail row is written for every
 * recipient regardless (so it appears in their in-app list and read-state can be tracked).
 *
 * All public method signatures are kept stable so existing callers keep working.
 */
class Notification_service
{
	/** @var CI_Controller */
	protected $CI;

	/** push_notifications_details.user_type codes. */
	const UT_STUDENT = 1;
	const UT_TEACHER = 2;
	const UT_INSTITUTE = 3;

	public function __construct()
	{
		$this->CI = &get_instance();
		$this->CI->load->model('db_model');
		$this->CI->load->library('common');
	}

	/**
	 * UNIFIED entry point. Resolve recipients, write 1 master + N detail rows, send FCM.
	 *
	 * Recipient selection via $opts (first match wins):
	 *   - 'student_ids' => int[]   specific students
	 *   - 'batch_id'    => int     enrolled active students of one batch
	 *   - 'batch_ids'   => int[]   enrolled active students of several batches
	 *   - 'all_students'=> true    every active student
	 *
	 * Other $opts: 'batch_id' (also stored on the master), 'send_push' (bool, default true),
	 * 'title' (FCM/master title; falls back to $title arg), 'data' (extra FCM data payload).
	 *
	 * @return array{master_id:int,recipients:int,sent:int,failed:int}
	 */
	public function push_notify($title, $message, $notification_type, $url = '', array $opts = array())
	{
		$title = trim((string) $title);
		$message = trim((string) $message);
		$notification_type = trim((string) $notification_type);
		$result = array('master_id' => 0, 'recipients' => 0, 'sent' => 0, 'failed' => 0);
		if ($notification_type === '' || ($title === '' && $message === '')) {
			return $result;
		}

		$recipients = $this->resolve_student_recipients($opts);
		if (empty($recipients)) {
			return $result;
		}

		$master_batch_id = isset($opts['batch_id']) ? (int) $opts['batch_id'] : 0;
		$send_push = !array_key_exists('send_push', $opts) || !empty($opts['send_push']);
		$extra_data = isset($opts['data']) && is_array($opts['data']) ? $opts['data'] : array();

		return $this->write_event(
			$master_batch_id,
			$notification_type,
			$title,
			$message,
			$url,
			$recipients,
			array(
				'send_push' => $send_push,
				'data' => $extra_data,
				// Per-notification image (opts['image'] wins, else data['image'], else site-logo default).
				'image' => array_key_exists('image', $opts) ? $opts['image'] : null,
			)
		);
	}

	/**
	 * Insert the master row + one detail row per recipient (and optionally push via FCM).
	 *
	 * @param array $recipients each: ['userid'=>int,'user_type'=>int,'device_token'=>string]
	 * @param array $opts       send_push(bool), data(array)
	 * @return array{master_id:int,recipients:int,sent:int,failed:int}
	 */
	/**
	 * Request JSON to store on the detail row. Uses the real FCM request when a push was attempted,
	 * otherwise builds the intended payload so notifcations_request is never blank (e.g. no device token).
	 *
	 * @return string
	 */
	private function detail_request_json($push, $device_token, $title, $body, array $data)
	{
		if ($push !== null && isset($push['request']) && (string) $push['request'] !== '') {
			return (string) $push['request'];
		}
		return (string) json_encode(array('message' => array(
			'token' => (string) $device_token,
			'notification' => array('title' => (string) $title, 'body' => (string) $body),
			'data' => $data,
		)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/**
	 * SQL expression that yields the recipient's FCM token from a table: a non-empty device_token,
	 * else the legacy `token` column. Returns "''" when neither column exists.
	 *
	 * @return string
	 */
	private function device_token_select_expr($table)
	{
		$has_dt = $this->CI->db->field_exists('device_token', $table);
		$has_t = $this->CI->db->field_exists('token', $table);
		if ($has_dt && $has_t) {
			return "COALESCE(NULLIF(TRIM(device_token), ''), token)";
		}
		if ($has_dt) {
			return 'device_token';
		}
		if ($has_t) {
			return 'token';
		}
		return "''";
	}

	/**
	 * Map the internal user_type code to the string the apps expect.
	 *
	 * @return string student|teacher|institute
	 */
	private function user_type_label($ut_code)
	{
		switch ((int) $ut_code) {
			case self::UT_TEACHER:
				return 'teacher';
			case self::UT_INSTITUTE:
				return 'institute';
			default:
				return 'student';
		}
	}

	private function write_event($batch_id, $notification_type, $title, $message, $url, array $recipients, array $opts = array())
	{
		$result = array('master_id' => 0, 'recipients' => 0, 'sent' => 0, 'failed' => 0);
		if (empty($recipients) || !$this->CI->db->table_exists('notifications')) {
			return $result;
		}
		$send_push = !array_key_exists('send_push', $opts) || !empty($opts['send_push']);
		$base_data = isset($opts['data']) && is_array($opts['data']) ? $opts['data'] : array();

		$push_image = array_key_exists('image', $opts) && $opts['image'] !== null
			? (string) $opts['image']
			: (isset($base_data['image']) ? (string) $base_data['image'] : null);

		$msg = $this->clip((string) $message, 250);
		$url = $this->clip((string) $url, 250);

		$master = array(
			'batch_id' => (int) $batch_id,
			'notification_type' => $this->clip((string) $notification_type, 255),
			'msg' => $msg,
			'url' => $url,
			'status' => 0,
			'time' => date('Y-m-d H:i:s'),
			'seen_by' => '',
		);
		// `title` is optional (added by migration); only set it when the column exists.
		if ($this->CI->db->field_exists('title', 'notifications')) {
			$master['title'] = $this->clip((string) $title, 255);
		}
		$master_id = (int) $this->CI->db_model->insert_data('notifications', $master);
		if ($master_id < 1) {
			return $result;
		}
		$result['master_id'] = $master_id;

		$has_details = $this->CI->db->table_exists('push_notifications_details');
		$push_title = $title !== '' ? $title : ucwords(str_replace(array('_', '-'), ' ', $notification_type));
		$data = array_merge($base_data, array(
			'type' => $notification_type,
			'pushnotify_id' => (string) $master_id,
			'url' => $url,
		));
		if ((int) $batch_id > 0) {
			$data['batch_id'] = (string) (int) $batch_id;
		}

		foreach ($recipients as $r) {
			$uid = isset($r['userid']) ? (int) $r['userid'] : 0;
			if ($uid < 1) {
				continue;
			}
			$result['recipients']++;
			$user_type = isset($r['user_type']) ? (int) $r['user_type'] : self::UT_STUDENT;
			$device_token = isset($r['device_token']) ? trim((string) $r['device_token']) : '';

			// Per-recipient data carries who the push is for (string values; v1 requires strings).
			$recipient_data = array_merge($data, array(
				'user_id'   => (string) $uid,
				'user_type' => $this->user_type_label($user_type),
			));

			$push = null;
			if ($send_push && $device_token !== '') {
				$push = $this->CI->common->sendPushNotification($device_token, $push_title, $msg, $recipient_data, $push_image);
				!empty($push['ok']) ? $result['sent']++ : $result['failed']++;
			}

			if ($has_details) {
				$ok = !empty($push['ok']);
				$this->CI->db_model->insert_data('push_notifications_details', array(
					'pushnotify_id' => $master_id,
					'userid' => $uid,
					'user_type' => $user_type,
					'status' => $ok ? 1 : 0,
					'notification_logs' => $push !== null ? (isset($push['response']) ? (string) $push['response'] : ((string) ($push['error'] ?? ''))) : 'Not sent: no device token',
					'notifcations_request' => $this->detail_request_json($push, $device_token, $push_title, $msg, $recipient_data),
					'device_token' => $device_token,
					'events' => ($push !== null && !$ok) ? 2 : 0, // 2 => failed
					'read' => 0,
				));
			}
		}

		return $result;
	}

	/**
	 * In-app fan-out to a batch's enrolled active students (no FCM). 1 master + N details.
	 * Kept for {@see MY_Controller} helpers and the batch-notify endpoint.
	 *
	 * @return int recipients written
	 */
	public function fan_out_batch_students($batch_id, $notification_type, $msg, $url = '')
	{
		$res = $this->push_notify('', $msg, $notification_type, $url, array(
			'batch_id' => (int) $batch_id,
			'send_push' => false,
		));
		return (int) $res['recipients'];
	}

	/**
	 * Single-student in-app notification (no FCM). 1 master + 1 detail.
	 *
	 * @return int master id or 0
	 */
	public function notify_student($student_id, $batch_id, $notification_type, $msg, $url = '')
	{
		$res = $this->push_notify('', $msg, $notification_type, $url, array(
			'student_ids' => array((int) $student_id),
			'batch_id' => (int) $batch_id,
			'send_push' => false,
		));
		return (int) $res['master_id'];
	}

	/**
	 * Push (FCM) + record to every active, push-enabled student of a batch. 1 master + N details.
	 *
	 * @return array{parent_id:int,recipients:int,sent:int,failed:int}
	 */
	public function push_to_batch_students($batch_id, $title, $message, $notification_type, $url = '', array $data = array())
	{
		$res = $this->push_notify($title, $message, $notification_type, $url, array(
			'batch_id' => (int) $batch_id,
			'send_push' => true,
			'data' => $data,
		));
		// Preserve the legacy return shape (parent_id alias).
		return array(
			'parent_id' => $res['master_id'],
			'recipients' => $res['recipients'],
			'sent' => $res['sent'],
			'failed' => $res['failed'],
		);
	}

	/**
	 * Template-driven batch event: EMAIL (via the template) + PUSH + in-app, to every enrolled
	 * active student of a batch. Writes 1 master + N detail rows.
	 *
	 * @return array{parent_id:int,recipients:int,emails_sent:int,push_sent:int,push_failed:int}
	 */
	public function notify_batch_event($batch_id, $purpose, array $vars = array(), array $opts = array())
	{
		$batch_id = (int) $batch_id;
		$purpose = trim((string) $purpose);
		$result = array('parent_id' => 0, 'recipients' => 0, 'emails_sent' => 0, 'push_sent' => 0, 'push_failed' => 0);
		if ($batch_id < 1 || $purpose === '') {
			return $result;
		}

		$do_email = !array_key_exists('email', $opts) || !empty($opts['email']);
		$do_push = !array_key_exists('push', $opts) || !empty($opts['push']);
		$url = isset($opts['url']) ? trim((string) $opts['url']) : '';
		$type = isset($opts['notification_type']) ? trim((string) $opts['notification_type']) : $purpose;
		$name_var = isset($opts['student_name_var']) ? (string) $opts['student_name_var'] : 'STUDENT_NAME';

		$recipients = $this->resolve_student_recipients(array('batch_id' => $batch_id));
		if (empty($recipients)) {
			return $result;
		}

		// Fetch the template row ONCE (SELECT *): this same row drives BOTH the push title/body
		// (title + `notification` column) AND every recipient's email (passed as template_row below,
		// so send_email does not re-query per recipient). Explicit push_title/push_message in $opts win.
		// templates.status is enum('0','1'): compare as STRING '1' (int 1 matches the enum index).
		$tpl_rows = $this->CI->db_model->select_data(
			'*',
			'templates',
			array('purpose' => $purpose, 'template_for' => 'email', 'status' => '1'),
			1,
			array('id', 'desc')
		);
		$email_tpl_row = !empty($tpl_rows[0]) ? $tpl_rows[0] : array();

		$push_title = isset($opts['push_title']) ? (string) $opts['push_title'] : '';
		$push_message = isset($opts['push_message']) ? (string) $opts['push_message'] : '';
		if ($push_title === '') {
			$push_title = $this->apply_vars(!empty($email_tpl_row['title']) ? $email_tpl_row['title'] : $purpose, $vars);
		}
		if ($push_message === '') {
			// Prefer the dedicated notification body; fall back to description, then html_code.
			$body_raw = !empty($email_tpl_row['notification']) ? (string) $email_tpl_row['notification']
				: (!empty($email_tpl_row['description']) ? (string) $email_tpl_row['description']
				: (isset($email_tpl_row['html_code']) ? (string) $email_tpl_row['html_code'] : ''));
			$push_message = trim(preg_replace('/\s+/', ' ', strip_tags($this->apply_vars($body_raw, $vars))));
		}
		if ($push_title === '') {
			$push_title = ucwords(str_replace('_', ' ', $purpose));
		}
		if ($push_message === '') {
			$push_message = $push_title;
		}

		// One master row for the event.
		$master = array(
			'batch_id' => $batch_id,
			'notification_type' => $this->clip($type, 255),
			'msg' => $this->clip($push_message, 250),
			'url' => $this->clip($url, 250),
			'status' => 0,
			'time' => date('Y-m-d H:i:s'),
			'seen_by' => '',
		);
		if ($this->CI->db->field_exists('title', 'notifications')) {
			$master['title'] = $this->clip($push_title, 255);
		}
		$master_id = (int) $this->CI->db_model->insert_data('notifications', $master);
		$result['parent_id'] = $master_id;
		if ($master_id < 1) {
			return $result;
		}

		$has_details = $this->CI->db->table_exists('push_notifications_details');
		$push_data = array('type' => $type, 'pushnotify_id' => (string) $master_id, 'url' => $url, 'batch_id' => (string) $batch_id);

		foreach ($recipients as $r) {
			$uid = (int) $r['userid'];
			if ($uid < 1) {
				continue;
			}
			$result['recipients']++;
			$name = isset($r['name']) ? (string) $r['name'] : '';
			$email = isset($r['email']) ? trim((string) $r['email']) : '';
			$device_token = isset($r['device_token']) ? trim((string) $r['device_token']) : '';
			$ut_code = isset($r['user_type']) ? (int) $r['user_type'] : self::UT_STUDENT;

			// Per-recipient data carries who the push is for (string values; v1 requires strings).
			$recipient_data = array_merge($push_data, array(
				'user_id'   => (string) $uid,
				'user_type' => $this->user_type_label($ut_code),
			));

			if ($do_email && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$email_arr = array(
					'purpose' => $purpose,
					'to_email' => $email,
					'dynamic_var' => array_merge($vars, array($name_var => $name)),
				);
				if (!empty($email_tpl_row)) {
					$email_arr['template_row'] = $email_tpl_row; // reuse the single fetch above
				}
				$res = $this->CI->common->send_email($email_arr);
				if (!empty($res['status'])) {
					$result['emails_sent']++;
				}
			}

			$push = null;
			if ($do_push && $device_token !== '') {
				$push = $this->CI->common->sendPushNotification($device_token, $push_title, $push_message, $recipient_data);
				!empty($push['ok']) ? $result['push_sent']++ : $result['push_failed']++;
			}

			if ($has_details) {
				$ok = !empty($push['ok']);
				$this->CI->db_model->insert_data('push_notifications_details', array(
					'pushnotify_id' => $master_id,
					'userid' => $uid,
					'user_type' => self::UT_STUDENT,
					'status' => $ok ? 1 : 0,
					'notification_logs' => $push !== null ? (isset($push['response']) ? (string) $push['response'] : ((string) ($push['error'] ?? ''))) : 'Not sent: no device token',
					'notifcations_request' => $this->detail_request_json($push, $device_token, $push_title, $push_message, $recipient_data),
					'device_token' => $device_token,
					'events' => ($push !== null && !$ok) ? 2 : 0,
					'read' => 0,
				));
			}
		}

		return $result;
	}

	/**
	 * Single-recipient account-lifecycle notification: EMAIL + PUSH + in-app (1 master + 1 detail),
	 * driven by a `templates.purpose`. Recipient is a student, teacher or institute.
	 *
	 * @param string $user_type 'student' | 'institute' | 'teacher'
	 * @param array  $opts      notification_type, url, name_var, email/push/in_app(bool), note
	 * @return array{email_sent:bool,push_sent:bool,in_app:int}
	 */
	public function notify_account_status($user_type, $user_id, $purpose, array $vars = array(), array $opts = array())
	{
		$user_type = strtolower(trim((string) $user_type));
		$user_id = (int) $user_id;
		$purpose = trim((string) $purpose);
		$result = array('email_sent' => false, 'push_sent' => false, 'in_app' => 0);
		if ($user_id < 1 || $purpose === '') {
			return $result;
		}

		$do_email = !array_key_exists('email', $opts) || !empty($opts['email']);
		$do_push = !array_key_exists('push', $opts) || !empty($opts['push']);
		$do_in_app = !array_key_exists('in_app', $opts) || !empty($opts['in_app']);
		$type = isset($opts['notification_type']) ? trim((string) $opts['notification_type']) : $purpose;
		$url = isset($opts['url']) ? trim((string) $opts['url']) : '';
		$name_var = isset($opts['name_var']) ? (string) $opts['name_var'] : 'STUDENT_NAME';
		$note = isset($opts['note']) ? trim((string) $opts['note']) : '';
		if ($note !== '') {
			$vars = array_merge($vars, array('REASON' => $note, 'ADMIN_NOTE' => $note, 'NOTE' => $note));
		}

		// Resolve recipient + device token. Prefer a non-empty device_token, else fall back to the
		// legacy `token` column (some accounts, e.g. teachers, only have `token` filled).
		if ($user_type === 'student') {
			$ut_code = self::UT_STUDENT;
			$tok_expr = $this->device_token_select_expr('students');
			$row = $this->CI->db_model->select_data('id,name,email,' . $tok_expr . ' AS device_token', 'students use index (id)', array('id' => $user_id), 1);
		} else {
			$ut_code = ($user_type === 'institute') ? self::UT_INSTITUTE : self::UT_TEACHER;
			$tok_expr = $this->device_token_select_expr('users');
			$cols = 'id,name,email' . ($tok_expr !== "''" ? ',' . $tok_expr . ' AS device_token' : '');
			$row = $this->CI->db_model->select_data($cols, 'users use index (id)', array('id' => $user_id), 1);
		}
		if (empty($row[0])) {
			return $result;
		}
		$name = isset($row[0]['name']) ? (string) $row[0]['name'] : '';
		$email = isset($row[0]['email']) ? trim((string) $row[0]['email']) : '';
		$device_token = isset($row[0]['device_token']) ? trim((string) $row[0]['device_token']) : '';

		if ($do_email && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$email_arr = array(
				'purpose' => $purpose,
				'user_type' => $user_type,
				'to_email' => $email,
				'dynamic_var' => array_merge($vars, array($name_var => $name, 'NAME' => $name)),
			);
			if ($note !== '') {
				$email_arr['append_html'] = '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;"><tr>'
					. '<td style="padding:14px 16px;background:#f5f5f5;border-left:4px solid #1e88e5;color:#333333;font-size:14px;line-height:22px;">'
					. '<strong>Message from the administrator:</strong><br>' . nl2br(html_escape($note))
					. '</td></tr></table>';
			}
			$res = $this->CI->common->send_email($email_arr);
			$result['email_sent'] = !empty($res['status']);
		}

		// Notification title/body from the email template row (title => title, body => notification col).
		$msg_vars = array_merge($vars, array($name_var => $name, 'NAME' => $name));
		$push_title = '';
		$push_message = '';
		if ($do_push || $do_in_app) {
			// Raw template strings supplied by the caller (single-fetch path from common_send_email_push):
			// apply vars here so the recipient name/tokens resolve, then flatten to plain text.
			if (isset($opts['push_title_raw']) || isset($opts['push_message_raw'])) {
				$push_title = trim(preg_replace('/\s+/', ' ', strip_tags($this->apply_vars((string) (isset($opts['push_title_raw']) ? $opts['push_title_raw'] : ''), $msg_vars))));
				$push_message = trim(preg_replace('/\s+/', ' ', strip_tags($this->apply_vars((string) (isset($opts['push_message_raw']) ? $opts['push_message_raw'] : ''), $msg_vars))));
			} else {
				$tpl = $this->notification_template_content($purpose, $msg_vars);
				$push_title = $tpl['title'];
				$push_message = $tpl['message'];
			}
			if ($push_title === '') {
				$push_title = ucwords(str_replace('_', ' ', $purpose));
			}
			if ($push_message === '') {
				$push_message = $push_title;
			}
			if ($note !== '') {
				$push_message = trim($push_message . ' Reason: ' . $note);
			}
			$push_message = $this->clip($push_message, 250);
		}

		// Master + single detail row (in-app + push state).
		$master_id = 0;
		if (($do_in_app || $do_push) && $this->CI->db->table_exists('notifications')) {
			$master = array(
				'batch_id' => 0,
				'notification_type' => $this->clip($type, 255),
				'msg' => $push_message,
				'url' => $this->clip($url, 250),
				'status' => 0,
				'time' => date('Y-m-d H:i:s'),
				'seen_by' => '',
			);
			if ($this->CI->db->field_exists('title', 'notifications')) {
				$master['title'] = $this->clip($push_title, 255);
			}
			$master_id = (int) $this->CI->db_model->insert_data('notifications', $master);
			if ($master_id > 0 && $do_in_app) {
				$result['in_app'] = 1;
			}
		}

		if ($master_id > 0 && $this->CI->db->table_exists('push_notifications_details')) {
			$push = null;
			if ($do_push && $device_token !== '') {
				$push = $this->CI->common->sendPushNotification($device_token, $push_title, $push_message, array(
					'type' => $type,
					'pushnotify_id' => (string) $master_id,
					'url' => $url,
					'user_id' => (string) $user_id,
					'user_type' => $this->user_type_label($ut_code),
				));
				$result['push_sent'] = !empty($push['ok']);
			}
			$ok = !empty($push['ok']);
			$this->CI->db_model->insert_data('push_notifications_details', array(
				'pushnotify_id' => $master_id,
				'userid' => $user_id,
				'user_type' => $ut_code,
				'status' => $ok ? 1 : 0,
				'notification_logs' => $push !== null ? (isset($push['response']) ? (string) $push['response'] : ((string) ($push['error'] ?? ''))) : 'Not sent: no device token',
				'notifcations_request' => $this->detail_request_json($push, $device_token, $push_title, $push_message, array('type' => $type, 'pushnotify_id' => (string) $master_id, 'url' => $url, 'user_id' => (string) $user_id, 'user_type' => $this->user_type_label($ut_code))),
				'device_token' => $device_token,
				'events' => ($push !== null && !$ok) ? 2 : 0,
				'read' => 0,
			));
		}

		return $result;
	}

	/**
	 * $a keys:
	 *   purpose       (required)  templates.purpose (email template row; body from notification column)
	 *   user_type     student|teacher|institute (default student)
	 *   user_id       (required)  recipient id
	 *   vars          array       template variables (also accepts 'dynamic_var')
	 *   to_email      optional    override recipient email (else resolved from the user row)
	 *   url           optional    notification link (relative; frontend makes it absolute)
	 *   name_var      optional    var name for the recipient's name (default STUDENT_NAME)
	 *   email/push/in_app  bool   toggles (all default true)
	 *   note, append_html, notification_type, purpose_fallbacks  optional
	 *
	 * @return array|false notify_account_status result, or false on bad input.
	 */
	public function common_send_email_push(array $a)
	{
		$purpose = isset($a['purpose']) ? trim((string) $a['purpose']) : '';
		$user_type = isset($a['user_type']) ? strtolower(trim((string) $a['user_type'])) : 'student';
		$user_id = isset($a['user_id']) ? (int) $a['user_id'] : 0;
		$result = array('status' => false, 'email_sent' => false, 'push_sent' => false, 'in_app' => 0);
		if ($purpose === '') {
			return $result;
		}
		$vars = array();
		if (isset($a['vars']) && is_array($a['vars'])) {
			$vars = $a['vars'];
		} elseif (isset($a['dynamic_var']) && is_array($a['dynamic_var'])) {
			$vars = $a['dynamic_var'];
		}
		$do_email = !array_key_exists('email', $a) || !empty($a['email']);
		$do_push = !array_key_exists('push', $a) || !empty($a['push']);
		$do_in_app = !array_key_exists('in_app', $a) || !empty($a['in_app']);
		$url = isset($a['url']) ? (string) $a['url'] : '';
		$name_var = isset($a['name_var']) ? (string) $a['name_var'] : 'STUDENT_NAME';
		$note = isset($a['note']) ? (string) $a['note'] : '';

		// SINGLE query: this one template row carries both the email content (html_code/description)
		// and the notification body (`notification` column). Using SELECT * means the notification
		// column is present in the row whenever it exists — no separate field_exists() check needed
		// (field_exists metadata can be stale and wrongly report the column as missing).
		// NOTE: templates.status is enum('0','1') — must compare as the STRING '1' (an int 1 matches
		// the enum INDEX, i.e. the wrong value '0', returning no row).
		$rows = $this->CI->db_model->select_data(
			'*',
			'templates',
			array('purpose' => $purpose, 'template_for' => 'email', 'status' => '1'),
			1,
			array('id', 'desc')
		);
		$tpl_row = !empty($rows[0]) ? $rows[0] : array();

		// 1) Email — always (if enabled), for everyone. Reuse the fetched row (no second query).
		if ($do_email) {
			$email_arr = array(
				'purpose' => $purpose,
				'user_type' => $user_type,
				'user_id' => $user_id,
				'dynamic_var' => $vars,
			);
			if (!empty($tpl_row)) {
				$email_arr['template_row'] = $tpl_row;
			}
			if (!empty($a['to_email'])) {
				$email_arr['to_email'] = trim((string) $a['to_email']);
			}
			if (!empty($a['append_html'])) {
				$email_arr['append_html'] = (string) $a['append_html'];
			}
			if (!empty($a['purpose_fallbacks'])) {
				$email_arr['purpose_fallbacks'] = $a['purpose_fallbacks'];
			}
			$er = @$this->CI->common->send_email($email_arr);
			$result['email_sent'] = !empty($er['status']);
			$result['status'] = $result['email_sent'];
		}

		// 2) Notification (push + in-app) ONLY when this template's `notification` column is filled.
		//    Pass the RAW template strings so notify_account_status substitutes vars (incl. the
		//    recipient's name) itself — no extra template fetch.
		$notif_raw = isset($tpl_row['notification']) ? trim((string) $tpl_row['notification']) : '';
		if ($user_id >= 1 && $notif_raw !== '' && ($do_push || $do_in_app)) {
			$title_raw = isset($tpl_row['title']) && trim((string) $tpl_row['title']) !== '' ? (string) $tpl_row['title'] : $purpose;
			$ns = $this->notify_account_status($user_type, $user_id, $purpose, $vars, array(
				'email' => false, // email already handled above
				'push' => $do_push,
				'in_app' => $do_in_app,
				'url' => $url,
				'name_var' => $name_var,
				'note' => $note,
				'notification_type' => isset($a['notification_type']) ? $a['notification_type'] : $purpose,
				'push_title_raw' => $title_raw,
				'push_message_raw' => $notif_raw,
			));
			if (is_array($ns)) {
				$result['push_sent'] = !empty($ns['push_sent']);
				$result['in_app'] = isset($ns['in_app']) ? (int) $ns['in_app'] : 0;
			}
		}

		return $result;
	}

	/**
	 * Legacy data-only Android/FCM push to a batch's students, one specific student, or all students.
	 * Single shared implementation for the (previously duplicated) push_notification_android() methods
	 * in Ajaxcall / Admin_profile / Teacher_profile — those now just delegate here.
	 *
	 * @param string|int $batch_id   batch id(s) for `batch_id IN (...)`, or ''
	 * @param string     $title      notification title
	 * @param string     $where      app routing hint (goes into data.message.body.where)
	 * @param string|int $student_id single student (when $batch_id is empty), or '' for all
	 * @return string last FCM response (kept for backward compatibility)
	 */
	public function android_push($batch_id = '', $title = '', $where = '', $student_id = '')
	{
		return $this->android_push_send($batch_id, $title, $where, $student_id, array());
	}

	/**
	 * Same as {@see android_push()} but carries extra video fields in the data payload.
	 *
	 * @return string
	 */
	public function android_push_video($batch_id = '', $title = '', $where = '', $student_id = '', $videoId = '', $url_video = '', $videoType = '')
	{
		return $this->android_push_send($batch_id, $title, $where, $student_id, array(
			'videoId'   => $videoId,
			'videoName' => $title,
			'url'       => $url_video,
			'videoType' => $videoType,
		));
	}

	/**
	 * Shared body for android_push / android_push_video: resolve tokens, chunk, send via the FCM v1
	 * sender. $extra_body is merged into data.message.body.
	 *
	 * @return string
	 */
	private function android_push_send($batch_id, $title, $where, $student_id, array $extra_body)
	{
		$result = '';
		$batch_data = array();
		if (!empty($batch_id)) {
			$batchCon = "status = 1 AND token !='' AND batch_id in (" . $batch_id . ")";
			$get_token = $this->CI->db_model->select_data('token', 'students', $batchCon, '');
			$batch_data = current($this->CI->db_model->select_data('batch_name', 'batches', array('id' => $batch_id), ''));
		} elseif (!empty($student_id)) {
			$get_token = $this->CI->db_model->select_data('token', 'students', array('status' => 1, 'token !=' => '', 'id' => $student_id), '');
		} else {
			$get_token = $this->CI->db_model->select_data('token', 'students', array('status' => 1, 'token !=' => ''), '');
		}
		if (empty($get_token)) {
			return $result;
		}
		foreach (array_chunk($get_token, 999) as $chunk) {
			$device_id = array();
			foreach ($chunk as $t) {
				if (!empty($t['token'])) {
					$device_id[] = $t['token'];
				}
			}
			if (empty($device_id)) {
				continue;
			}
			$message = array(
				'title' => $title,
				'body' => array_merge(array(
					'where' => $where,
					'batch_name' => (!empty($batch_data['batch_name'])) ? $batch_data['batch_name'] : '',
					'batch_id' => $batch_id,
				), $extra_body),
			);
			$push = $this->CI->common->sendPushNotification($device_id, $title, is_string($where) ? $where : '', array('message' => $message));
			$result = isset($push['response']) ? $push['response'] : '';
		}
		return $result;
	}

	/**
	 * Resolve student recipients (id, name, email, device_token) from a selector in $opts.
	 *
	 * @return array<int,array{userid:int,user_type:int,name:string,email:string,device_token:string}>
	 */
	private function resolve_student_recipients(array $opts)
	{
		$token_col = $this->CI->db->field_exists('device_token', 'students') ? 'device_token' : 'token';
		$this->CI->db->reset_query();
		$this->CI->db->distinct()
			->select('s.id AS userid, s.name AS name, s.email AS email, s.' . $token_col . ' AS device_token', false)
			->from('students s');

		if (!empty($opts['student_ids']) && is_array($opts['student_ids'])) {
			$ids = array_values(array_unique(array_filter(array_map('intval', $opts['student_ids']))));
			if (empty($ids)) {
				return array();
			}
			$this->CI->db->where_in('s.id', $ids)->where('s.status', 1);
		} elseif (!empty($opts['batch_id']) || !empty($opts['batch_ids'])) {
			$batch_ids = !empty($opts['batch_ids']) && is_array($opts['batch_ids'])
				? array_values(array_unique(array_filter(array_map('intval', $opts['batch_ids']))))
				: array((int) $opts['batch_id']);
			if (empty($batch_ids)) {
				return array();
			}
			$this->CI->db->join('student_batchs sb', 'sb.student_id = s.id', 'inner')
				->where_in('sb.batch_id', $batch_ids)
				->where('sb.status', 1)
				->where('s.status', 1);
		} elseif (!empty($opts['all_students'])) {
			$this->CI->db->where('s.status', 1);
		} else {
			return array();
		}

		$rows = $this->CI->db->get()->result_array();
		$out = array();
		foreach ($rows as $r) {
			$uid = isset($r['userid']) ? (int) $r['userid'] : 0;
			if ($uid < 1) {
				continue;
			}
			$out[$uid] = array(
				'userid' => $uid,
				'user_type' => self::UT_STUDENT,
				'name' => isset($r['name']) ? (string) $r['name'] : '',
				'email' => isset($r['email']) ? (string) $r['email'] : '',
				'device_token' => isset($r['device_token']) ? trim((string) $r['device_token']) : '',
			);
		}
		return array_values($out);
	}

	/** Trim a string to a max length (multibyte-safe enough for our latin1/utf8 columns). */
	private function clip($text, $max)
	{
		$text = (string) $text;
		return strlen($text) > $max ? substr($text, 0, $max) : $text;
	}

	/** Minimal {{key}} / {key} replacement (mirrors Common::email_apply_vars for push text). */
	private function apply_vars($text, array $vars)
	{
		$text = (string) $text;
		foreach ($vars as $k => $v) {
			$val = (string) (is_scalar($v) ? $v : '');
			$key = preg_quote((string) $k, '/');
			// Whitespace/case tolerant: {{KEY}}, {{ KEY }}, {KEY}, { KEY }.
			$text = preg_replace_callback('/\{\{\s*' . $key . '\s*\}\}|\{\s*' . $key . '\s*\}/i', function () use ($val) {
				return $val;
			}, $text);
		}
		return $text;
	}

	/**
	 * Notification (push + in-app) title/body from the email template row: title => templates.title,
	 * body => templates.notification (the column added for notifications). Falls back to
	 * description/html_code when the notification column is empty or missing. Variables are applied
	 * and the body is flattened to plain text.
	 *
	 * @return array{title:string,message:string}
	 */
	private function notification_template_content($purpose, array $vars)
	{
		$purpose = trim((string) $purpose);
		// SELECT * so the `notification` column is present whenever it exists (no field_exists check,
		// whose cached metadata can wrongly report the column as missing).
		$rows = $this->CI->db_model->select_data(
			'*',
			'templates',
			array('purpose' => $purpose, 'template_for' => 'email'),
			1
		);
		$tpl = !empty($rows[0]) ? $rows[0] : array();

		$title = $this->apply_vars(isset($tpl['title']) ? $tpl['title'] : $purpose, $vars);

		// Prefer the dedicated notification body; fall back to description, then html_code.
		$body_raw = '';
		if (!empty($tpl['notification'])) {
			$body_raw = (string) $tpl['notification'];
		} elseif (!empty($tpl['description'])) {
			$body_raw = (string) $tpl['description'];
		} elseif (!empty($tpl['html_code'])) {
			$body_raw = (string) $tpl['html_code'];
		}
		$message = trim(preg_replace('/\s+/', ' ', strip_tags($this->apply_vars($body_raw, $vars))));

		return array('title' => $title, 'message' => $message);
	}
}
