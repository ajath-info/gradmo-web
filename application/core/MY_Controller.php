<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for shared API helpers (access tokens, timezone).
 * Used by api/user/Home, api/batch/Batch, and other API controllers.
 */
class MY_Controller extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$timezoneDB = $this->db_model->select_data('timezone', 'site_details', array('id' => 1));
		if (isset($timezoneDB[0]['timezone']) && !empty($timezoneDB[0]['timezone'])) {
			date_default_timezone_set($timezoneDB[0]['timezone']);
		}
	}

	/**
	 * @return array{access_token: string, iat: int}
	 */
	protected function generate_access_token($user_id, $user_type)
	{
		$secret = $this->config->item('encryption_key');
		if (empty($secret)) {
			$secret = 'education_api_secret_key';
		}

		$iat = time();
		$payload = array(
			'uid' => (int) $user_id,
			'ut' => (string) $user_type,
			'iat' => $iat,
			'exp' => $iat + (60 * 60 * 24 * 30)
		);

		$payload_json = json_encode($payload);
		$payload_b64 = rtrim(strtr(base64_encode($payload_json), '+/', '-_'), '=');
		$signature = hash_hmac('sha256', $payload_b64, $secret);

		return array(
			'access_token' => $payload_b64 . '.' . $signature,
			'iat' => $iat,
		);
	}

	protected function parse_access_token($token)
	{
		$token = trim((string) $token);
		// Accept raw token, "Bearer <token>" and "Bearer:<token>" formats.
		if (preg_match('/^Bearer\s*:?\s*(.+)$/i', $token, $matches)) {
			$token = trim($matches[1]);
		}

		if (empty($token) || strpos($token, '.') === false) {
			return false;
		}

		list($payload_b64, $signature) = explode('.', $token, 2);
		$secret = $this->config->item('encryption_key');
		if (empty($secret)) {
			$secret = 'education_api_secret_key';
		}

		$expected_signature = hash_hmac('sha256', $payload_b64, $secret);
		if (!hash_equals($expected_signature, $signature)) {
			return false;
		}

		$payload_json = base64_decode(strtr($payload_b64, '-_', '+/'));
		$payload = json_decode($payload_json, true);

		if (!is_array($payload) || empty($payload['uid']) || empty($payload['ut']) || empty($payload['exp']) || empty($payload['iat'])) {
			return false;
		}

		if ((int) $payload['exp'] < time()) {
			return false;
		}

		$user_type = (string) $payload['ut'];
		$user_id = (int) $payload['uid'];
		$iat = (int) $payload['iat'];

		// Server-side session validation.
		// Without this, a signed token stays valid until expiry even after logout or re-login.
		if ($user_type === 'student') {
			$rows = $this->db_model->select_data('id, login_status, last_login_app', 'students', array('id' => $user_id), 1);
			if (empty($rows)) {
				return false;
			}

			$db_login_status = isset($rows[0]['login_status']) ? (int) $rows[0]['login_status'] : 0;
			if ($db_login_status !== 1) {
				return false;
			}

			// Only the token from the latest login: last_login_app is set from that token's iat on login.
			// Reject any other token (e.g. after logging in again without logout). Slack covers DB/PHP second alignment.
			$last_login = isset($rows[0]['last_login_app']) ? trim((string) $rows[0]['last_login_app']) : '';
			if ($last_login !== '' && $last_login !== '0000-00-00 00:00:00') {
				$last_login_ts = strtotime($last_login);
				if ($last_login_ts && abs($iat - $last_login_ts) > 2) {
					return false;
				}
			}
		} else {
			// Teacher/Institute/Admin users
			$rows = $this->db_model->select_data('id, login_status, updated_at', 'users', array('id' => $user_id), 1);
			if (empty($rows)) {
				return false;
			}

			// If login_status column exists and is 0, reject.
			if (isset($rows[0]['login_status']) && (int) $rows[0]['login_status'] === 0) {
				return false;
			}

			// Reject tokens issued before the latest login/update timestamp set at login.
			$updated_at = isset($rows[0]['updated_at']) ? trim((string) $rows[0]['updated_at']) : '';
			if ($updated_at !== '' && $updated_at !== '0000-00-00 00:00:00') {
				$updated_ts = strtotime($updated_at);
				if ($updated_ts && $iat < $updated_ts) {
					return false;
				}
			}
		}

		return $payload;
	}

	/**
	 * Resolve bearer token without using $_REQUEST for access_token/token.
	 * $_REQUEST merges cookies (per php.ini): an old access_token cookie would override
	 * a new login when the client does not send Authorization — use GET/POST/JSON only after the header.
	 *
	 * @param array|null $json_body Decoded JSON body (e.g. attendance-list); may contain access_token.
	 */
	protected function get_access_token_from_request(array $json_body = null)
	{
		$auth_header = $this->input->get_request_header('Authorization', true);
		if (!empty($auth_header) && preg_match('/Bearer\s*:?\s*(.+)/i', $auth_header, $matches)) {
			return trim($matches[1]);
		}

		if (!empty($_POST['access_token'])) {
			return trim(preg_replace('/^Bearer\s*:?\s*/i', '', (string) $_POST['access_token']));
		}
		if (!empty($_GET['access_token'])) {
			return trim(preg_replace('/^Bearer\s*:?\s*/i', '', (string) $_GET['access_token']));
		}
		if (!empty($_POST['token'])) {
			return trim(preg_replace('/^Bearer\s*:?\s*/i', '', (string) $_POST['token']));
		}
		if (!empty($_GET['token'])) {
			return trim(preg_replace('/^Bearer\s*:?\s*/i', '', (string) $_GET['token']));
		}

		if (is_array($json_body)) {
			if (!empty($json_body['access_token'])) {
				return trim(preg_replace('/^Bearer\s*:?\s*/i', '', (string) $json_body['access_token']));
			}
			if (!empty($json_body['token'])) {
				return trim(preg_replace('/^Bearer\s*:?\s*/i', '', (string) $json_body['token']));
			}
		}

		return '';
	}

	protected function authorize_student_request($student_id)
	{
		$token = $this->get_access_token_from_request();
		$payload = $this->parse_access_token($token);

		if ($payload === false || $payload['ut'] !== 'student' || (int) $payload['uid'] !== (int) $student_id) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Authentication failed. Please log in again.'
			));
			return false;
		}

		return true;
	}

	/**
	 * Central auth helper to avoid repeating token parsing in every endpoint.
	 * @param array|string $allowed_types Example: ['student'] or ['student','teacher']
	 * @param array|null $json_body Optional decoded JSON body for access_token when using JSON POST without header.
	 * @return array|false Payload array on success, false on failure (response already echoed).
	 */
	protected function require_auth_payload($allowed_types = array(), array $json_body = null)
	{
		$token = $this->get_access_token_from_request($json_body);
		$payload = $this->parse_access_token($token);
		if ($payload === false) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Authentication failed. Please log in again.'
			));
			return false;
		}

		if (!empty($allowed_types)) {
			if (is_string($allowed_types)) {
				$allowed_types = array($allowed_types);
			}
			$ut = isset($payload['ut']) ? (string) $payload['ut'] : '';
			if (!in_array($ut, $allowed_types, true)) {
				echo json_encode(array(
					'status' => 'false',
					'msg' => 'Unauthorized: invalid token user'
				));
				return false;
			}
		}

		return $payload;
	}
}
