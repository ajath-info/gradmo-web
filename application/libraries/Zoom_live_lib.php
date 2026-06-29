<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Zoom live class helpers — single credential store: zoom_api_credentials.
 * Per-batch meetings: batch_zoom_meetings.
 */
class Zoom_live_lib
{
	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI = &get_instance();
		$this->CI->load->model('db_model');
	}

	/**
	 * Latest zoom_api_credentials row.
	 *
	 * @return array<string, mixed>
	 */
	public function get_credential_row()
	{
		$row = $this->CI->db_model->select_data('*', 'zoom_api_credentials', '', 1, array('id', 'desc'));
		return !empty($row[0]) && is_array($row[0]) ? $row[0] : array();
	}

	public function normalize_zoom_credential($value)
	{
		$s = trim((string) $value);
		if ($s !== '' && strlen($s) >= 3 && substr($s, 0, 3) === "\xEF\xBB\xBF") {
			$s = trim(substr($s, 3));
		}
		return $s;
	}

	/**
	 * Meeting SDK credentials from zoom_api_credentials only (global).
	 *
	 * @return array{sdk_key:string, sdk_secret:string, signature_mode:string, source:string}
	 */
	public function resolve_meeting_sdk_credentials()
	{
		$key = '';
		$secret = '';
		$source = '';
		if (is_file(APPPATH . 'config/zoom.php')) {
			$this->CI->config->load('zoom', true);
			$cfg_k = $this->normalize_zoom_credential((string) $this->CI->config->item('meeting_sdk_key', 'zoom'));
			$cfg_s = $this->normalize_zoom_credential((string) $this->CI->config->item('meeting_sdk_secret', 'zoom'));
			if ($cfg_k !== '' && $cfg_s !== '') {
				return array(
					'sdk_key' => $cfg_k,
					'sdk_secret' => $cfg_s,
					'signature_mode' => 'jwt',
					'source' => 'config',
				);
			}
		}
		$cols = 'android_api_key,android_api_secret';
		if ($this->CI->db->field_exists('meeting_sdk_key', 'zoom_api_credentials')) {
			$cols = 'meeting_sdk_key,meeting_sdk_secret,android_api_key,android_api_secret';
		}
		$cred = $this->CI->db_model->select_data($cols, 'zoom_api_credentials', '', 1, array('id', 'desc'));
		if (!empty($cred[0])) {
			if (isset($cred[0]['meeting_sdk_key']) && trim((string) $cred[0]['meeting_sdk_key']) !== '') {
				$key = $this->normalize_zoom_credential($cred[0]['meeting_sdk_key']);
				$source = 'meeting_sdk';
			}
			if (isset($cred[0]['meeting_sdk_secret']) && trim((string) $cred[0]['meeting_sdk_secret']) !== '') {
				$secret = $this->normalize_zoom_credential($cred[0]['meeting_sdk_secret']);
			}
			if ($secret === '' && trim((string) $cred[0]['android_api_secret']) !== '') {
				$secret = $this->normalize_zoom_credential($cred[0]['android_api_secret']);
			}
			if ($key === '' && trim((string) $cred[0]['android_api_key']) !== '') {
				$key = $this->normalize_zoom_credential($cred[0]['android_api_key']);
				$source = 'android_api';
			}
		}
		$mode = ($source === 'android_api') ? 'legacy' : 'jwt';
		return array(
			'sdk_key' => $key,
			'sdk_secret' => $secret,
			'signature_mode' => $mode,
			'source' => $source,
		);
	}

	public function meeting_sdk_configured()
	{
		$c = $this->resolve_meeting_sdk_credentials();
		return $c['sdk_key'] !== '' && $c['sdk_secret'] !== '';
	}

	/**
	 * Active Zoom meeting for a batch.
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_batch_zoom_meeting_row($batch_id)
	{
		$batch_id = (int) $batch_id;
		if ($batch_id < 1 || !$this->CI->db->table_exists('batch_zoom_meetings')) {
			return null;
		}
		$bz = $this->CI->db_model->select_data('*', 'batch_zoom_meetings', array('batch_id' => $batch_id, 'status' => 1), 1, array('id', 'desc'));
		return !empty($bz[0]) ? $bz[0] : null;
	}

	public function public_meeting_number_from_batch_zoom_row(array $row)
	{
		$id = preg_replace('/\D+/', '', trim((string) (isset($row['zoom_meeting_id']) ? $row['zoom_meeting_id'] : '')));
		if (strlen($id) >= 9 && strlen($id) <= 12) {
			return $id;
		}
		$join = isset($row['join_url']) ? trim((string) $row['join_url']) : '';
		if ($join !== '' && preg_match('#(?:/j/|/wc/join/|/join/)(\d{9,12})#i', $join, $m)) {
			return preg_replace('/\D+/', '', $m[1]);
		}
		return $id;
	}

	/**
	 * Legacy embedded Zoom view data (admin/teacher/student start-class pages).
	 *
	 * @return array{ok:bool, sdk_key:string, sdk_secret:string, meeting_number:string, password:string, signature:string, msg:string}
	 */
	public function prepare_legacy_embed_view_data($batch_id, $role = 0)
	{
		$batch_id = (int) $batch_id;
		$role = (int) $role;
		$out = array(
			'ok' => false,
			'sdk_key' => '',
			'sdk_secret' => '',
			'meeting_number' => '',
			'password' => '',
			'signature' => '',
			'msg' => 'Zoom is not configured. Set credentials in Admin → Live Class (zoom_api_credentials).',
		);
		$sdk = $this->resolve_meeting_sdk_credentials();
		if ($sdk['sdk_key'] === '' || $sdk['sdk_secret'] === '') {
			return $out;
		}
		$bz = $this->get_batch_zoom_meeting_row($batch_id);
		if (empty($bz)) {
			$out['msg'] = 'No active Zoom meeting for this batch. Start a live class from the batch page or create a meeting via the API.';
			return $out;
		}
		$meeting_number = $this->public_meeting_number_from_batch_zoom_row($bz);
		if ($meeting_number === '') {
			$out['msg'] = 'Batch Zoom meeting has no valid meeting number.';
			return $out;
		}
		$password = trim((string) (isset($bz['password']) ? $bz['password'] : ''));
		$sig = $this->build_signature($sdk['sdk_key'], $sdk['sdk_secret'], $meeting_number, $role, $sdk['signature_mode']);
		$out['ok'] = true;
		$out['sdk_key'] = $sdk['sdk_key'];
		$out['sdk_secret'] = $sdk['sdk_secret'];
		$out['meeting_number'] = $meeting_number;
		$out['password'] = $password;
		$out['signature'] = $sig;
		$out['msg'] = '';
		return $out;
	}

	public function build_signature($sdk_key, $sdk_secret, $meeting_number, $role, $mode = 'auto')
	{
		$sdk_key = $this->normalize_zoom_credential($sdk_key);
		$sdk_secret = $this->normalize_zoom_credential($sdk_secret);
		$meeting_number = preg_replace('/\D+/', '', trim((string) $meeting_number));
		$role = (int) $role;
		if ($sdk_key === '' || $sdk_secret === '' || $meeting_number === '') {
			return '';
		}
		if ($mode === 'auto') {
			$mode = $this->signature_mode_for_credentials($sdk_key, $sdk_secret);
		}
		if ($mode === 'legacy') {
			return $this->signature_legacy($sdk_key, $sdk_secret, $meeting_number, $role);
		}
		return $this->signature_jwt($sdk_key, $sdk_secret, $meeting_number, $role);
	}

	private function signature_mode_for_credentials($sdk_key, $sdk_secret)
	{
		if (strlen($sdk_secret) >= 36 || strlen($sdk_key) >= 22) {
			return 'jwt';
		}
		if (strlen($sdk_key) >= 18 && strlen($sdk_key) <= 24 && strlen($sdk_secret) >= 22 && strlen($sdk_secret) <= 40) {
			return 'legacy';
		}
		return 'jwt';
	}

	private function signature_jwt($sdk_key, $sdk_secret, $meeting_number, $role)
	{
		$iat = time() - 30;
		$exp = $iat + 60 * 60 * 2;
		$header = array('alg' => 'HS256', 'typ' => 'JWT');
		$payload = array(
			'appKey' => $sdk_key,
			'mn' => (string) $meeting_number,
			'role' => (int) $role,
			'iat' => $iat,
			'exp' => $exp,
			'tokenExp' => $exp,
		);
		$segments = array(
			$this->jwt_base64url(json_encode($header)),
			$this->jwt_base64url(json_encode($payload)),
		);
		$signing_input = $segments[0] . '.' . $segments[1];
		$signature = hash_hmac('sha256', $signing_input, $sdk_secret, true);
		$segments[] = $this->jwt_base64url($signature);
		return implode('.', $segments);
	}

	private function signature_legacy($sdk_key, $sdk_secret, $meeting_number, $role)
	{
		$time = (time() - 5 * 60) * 1000;
		$data = base64_encode($sdk_key . $meeting_number . $time . $role);
		$hash = hash_hmac('sha256', $data, $sdk_secret, true);
		$_sig = $sdk_key . '.' . $meeting_number . '.' . $time . '.' . $role . '.' . base64_encode($hash);
		return rtrim(strtr(base64_encode($_sig), '+/', '-_'), '=');
	}

	private function jwt_base64url($data)
	{
		return rtrim(strtr(base64_encode((string) $data), '+/', '-_'), '=');
	}
}
