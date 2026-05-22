<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Zoom REST API (Server-to-Server OAuth) — create/update/delete meetings.
 * Credentials: table `zoom_api_credentials` columns s2s_* and zoom_host_* (see installer SQL).
 */
class Zoom_rest_client
{
	/** @var CI_Controller */
	protected $CI;

	public function __construct()
	{
		$this->CI = &get_instance();
		$this->CI->load->model('db_model');
	}

	/**
	 * @return array<string, string>
	 */
	public function get_credential_row()
	{
		$row = $this->CI->db_model->select_data('*', 'zoom_api_credentials', '', 1, array('id', 'desc'));
		return !empty($row[0]) && is_array($row[0]) ? $row[0] : array();
	}

	/**
	 * Trim pasted credentials and strip UTF-8 BOM (common when copying from spreadsheets).
	 */
	private function normalize_credential_field($value)
	{
		$s = trim((string) $value);
		if ($s !== '' && (ord($s[0]) === 0xEF && strlen($s) >= 3 && substr($s, 0, 3) === "\xEF\xBB\xBF")) {
			$s = trim(substr($s, 3));
		}
		return $s;
	}

	public function is_configured()
	{
		$r = $this->get_credential_row();
		$aid = isset($r['s2s_account_id']) ? $this->normalize_credential_field($r['s2s_account_id']) : '';
		$cid = isset($r['s2s_client_id']) ? $this->normalize_credential_field($r['s2s_client_id']) : '';
		$csec = isset($r['s2s_client_secret']) ? $this->normalize_credential_field($r['s2s_client_secret']) : '';
		return $aid !== '' && $cid !== '' && $csec !== '';
	}

	/**
	 * @return array{ok:bool, access_token?:string, expires_at?:int, error?:string}
	 */
	public function get_access_token()
	{
		if (!$this->is_configured()) {
			return array('ok' => false, 'error' => 'Zoom Server-to-Server OAuth is not configured (zoom_api_credentials).');
		}
		$r = $this->get_credential_row();
		$cache_path = APPPATH . 'cache/zoom_s2s_token.json';
		$now = time();
		if (is_file($cache_path)) {
			$raw = @file_get_contents($cache_path);
			$j = json_decode((string) $raw, true);
			if (is_array($j) && !empty($j['access_token']) && isset($j['expires_at']) && (int) $j['expires_at'] > $now + 60) {
				return array('ok' => true, 'access_token' => (string) $j['access_token'], 'expires_at' => (int) $j['expires_at']);
			}
		}

		$account_id = $this->normalize_credential_field($r['s2s_account_id']);
		$client_id = $this->normalize_credential_field($r['s2s_client_id']);
		$client_secret = $this->normalize_credential_field($r['s2s_client_secret']);
		$url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . rawurlencode($account_id);
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret),
				'Content-Type: application/x-www-form-urlencoded',
			),
			CURLOPT_POSTFIELDS => '',
		));
		$body = curl_exec($ch);
		$curl_err = $body === false ? curl_error($ch) : '';
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($body === false || $code < 200 || $code >= 300) {
			$detail = '';
			if ($body !== false && $body !== '') {
				$ej = json_decode((string) $body, true);
				if (is_array($ej)) {
					$parts = array();
					if (!empty($ej['error'])) {
						$parts[] = (string) $ej['error'];
					}
					if (!empty($ej['error_description'])) {
						$parts[] = (string) $ej['error_description'];
					}
					if (!empty($ej['reason'])) {
						$parts[] = (string) $ej['reason'];
					}
					$detail = implode(' — ', $parts);
				} else {
					$detail = trim(substr(preg_replace('/\s+/', ' ', (string) $body), 0, 240));
				}
			}
			if ($detail === '' && $curl_err !== '') {
				$detail = $curl_err;
			}
			$hint = '';
			if ($code === 400 || $code === 401) {
				$hint = ' Use a Server-to-Server OAuth app in Zoom Marketplace; paste Account ID, Client ID, and Client Secret from the App Credentials tab only (not the webhook Secret Token or Meeting SDK keys).';
			}
			$msg = 'Zoom OAuth token request failed (HTTP ' . $code . ')';
			if ($detail !== '') {
				$msg .= ': ' . $detail;
			}
			$msg .= '.' . $hint;
			return array('ok' => false, 'error' => $msg);
		}
		$decoded = json_decode((string) $body, true);
		if (!is_array($decoded) || empty($decoded['access_token'])) {
			return array('ok' => false, 'error' => 'Zoom OAuth token response invalid.');
		}
		$ttl = isset($decoded['expires_in']) ? (int) $decoded['expires_in'] : 3600;
		$expires_at = $now + $ttl;
		@file_put_contents($cache_path, json_encode(array(
			'access_token' => $decoded['access_token'],
			'expires_at' => $expires_at,
		), JSON_UNESCAPED_SLASHES), LOCK_EX);

		return array('ok' => true, 'access_token' => (string) $decoded['access_token'], 'expires_at' => $expires_at);
	}

	/** Remove cached S2S token so the next request fetches a new one (e.g. after scopes change on Zoom). */
	private function invalidate_s2s_token_cache()
	{
		$p = APPPATH . 'cache/zoom_s2s_token.json';
		if (is_file($p)) {
			@unlink($p);
		}
	}

	private function get_env_zoom_host_user_id()
	{
		$v = getenv('ZOOM_HOST_USER_ID');
		if ($v === false || $v === '') {
			return '';
		}
		return $this->normalize_credential_field($v);
	}

	/**
	 * Host Zoom user id from config or env (skips email API when set).
	 */
	private function get_fallback_zoom_host_user_id()
	{
		if (is_file(APPPATH . 'config/zoom.php')) {
			$this->CI->config->load('zoom', true);
			$v = $this->CI->config->item('zoom_host_user_id', 'zoom');
			$cfg = $this->normalize_credential_field(is_string($v) ? $v : '');
			if ($cfg !== '') {
				return $cfg;
			}
		}
		// Also allow $config['zoom_host_user_id'] in application/config/config.php
		$main = $this->CI->config->item('zoom_host_user_id');
		$main = $this->normalize_credential_field(is_string($main) ? $main : '');
		if ($main !== '') {
			return $main;
		}
		return $this->get_env_zoom_host_user_id();
	}

	/**
	 * @return array{ok:bool, id?:string, error?:string}
	 */
	public function resolve_host_user_id()
	{
		$r = $this->get_credential_row();
		$cached = isset($r['zoom_host_user_id']) ? $this->normalize_credential_field($r['zoom_host_user_id']) : '';
		if ($cached !== '') {
			return array('ok' => true, 'id' => $cached);
		}
		$fallback = $this->get_fallback_zoom_host_user_id();
		if ($fallback !== '') {
			return array('ok' => true, 'id' => $fallback);
		}
		$email = isset($r['zoom_host_email']) ? trim((string) $r['zoom_host_email']) : '';
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return array('ok' => false, 'error' => 'Set zoom_host_user_id in zoom_api_credentials, or zoom_host_user_id in application/config/zoom.php, or environment ZOOM_HOST_USER_ID (Zoom Admin → Users → host → User ID). Or set zoom_host_email and add User read scopes on the Zoom app.');
		}
		$tok = $this->get_access_token();
		if (!$tok['ok']) {
			return array('ok' => false, 'error' => $tok['error']);
		}
		$path = 'users/' . rawurlencode('email:' . $email);
		$res = $this->api_request('GET', $path, null, $tok['access_token']);
		if (!$res['ok'] || empty($res['json']['id'])) {
			$err = isset($res['error']) ? $res['error'] : 'Could not resolve Zoom host user.';
			if (stripos($err, 'scopes') !== false || (isset($res['json']['code']) && (int) $res['json']['code'] === 4711)) {
				$err = 'Zoom User read scopes are missing for email-based host lookup. Fix by setting the host Zoom User ID (no User scopes needed): (1) SQL: UPDATE zoom_api_credentials SET zoom_host_user_id = \'PASTE_ID_HERE\' WHERE id = 1; or (2) application/config/zoom.php $config[\'zoom_host_user_id\'] = \'PASTE_ID_HERE\'; or (3) application/config/config.php same key; or (4) env ZOOM_HOST_USER_ID. Find the ID: zoom.us → Admin → Users → open the host user → User ID.';
			}
			return array('ok' => false, 'error' => $err);
		}
		return array('ok' => true, 'id' => (string) $res['json']['id']);
	}

	/**
	 * ZAK token for host start/join (role 1). Requires S2S scope user:read:token:admin on Gradmo app.
	 *
	 * @return array{ok:bool, token?:string, error?:string}
	 */
	public function get_user_zak($user_id = null)
	{
		$user_id = $this->normalize_credential_field($user_id);
		if ($user_id === '') {
			$host = $this->resolve_host_user_id();
			if (!$host['ok']) {
				return array('ok' => false, 'error' => $host['error']);
			}
			$user_id = $host['id'];
		}
		$path = 'users/' . rawurlencode($user_id) . '/token?type=zak';
		$res = $this->api_request('GET', $path);
		if (!$res['ok'] || empty($res['json']['token'])) {
			// Fresh token after scope changes on marketplace (cached token may lack new scopes).
			$this->invalidate_s2s_token_cache();
			$res = $this->api_request('GET', $path);
		}
		if (!$res['ok'] || empty($res['json']['token'])) {
			$msg = isset($res['json']['message']) ? (string) $res['json']['message'] : (isset($res['error']) ? $res['error'] : 'Could not get host ZAK token.');
			if (stripos($msg, 'does not contain scopes') !== false || stripos($msg, 'user:read:token') !== false) {
				$msg .= ' On Gradmo (Server-to-Server OAuth) add scope user:read:token:admin (search "token" under User), click Continue, then Activation → Activate.';
			}
			return array('ok' => false, 'error' => $msg);
		}
		return array('ok' => true, 'token' => (string) $res['json']['token']);
	}

	/**
	 * @param string $method GET|POST|PATCH|DELETE
	 * @param string $path e.g. "meetings/123" (no leading slash)
	 * @param mixed $body
	 * @param string|null $access_token
	 * @param bool $is_retry internal: avoid infinite loop on persistent 4711
	 * @return array{ok:bool, code?:int, json?:array, body?:string, error?:string}
	 */
	public function api_request($method, $path, $body = null, $access_token = null, $is_retry = false)
	{
		if ($access_token === null) {
			$t = $this->get_access_token();
			if (!$t['ok']) {
				return array('ok' => false, 'error' => $t['error']);
			}
			$access_token = $t['access_token'];
		}
		$url = 'https://api.zoom.us/v2/' . ltrim($path, '/');
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);
		$ch = curl_init($url);
		$opts = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 45,
			CURLOPT_HTTPHEADER => $headers,
		);
		$m = strtoupper((string) $method);
		if ($m === 'POST') {
			$opts[CURLOPT_POST] = true;
			$opts[CURLOPT_POSTFIELDS] = $body !== null ? json_encode($body) : '{}';
		} elseif ($m === 'PATCH') {
			$opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
			$opts[CURLOPT_POSTFIELDS] = $body !== null ? json_encode($body) : '{}';
		} elseif ($m === 'DELETE') {
			$opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
		} else {
			$opts[CURLOPT_HTTPHEADER] = array('Authorization: Bearer ' . $access_token);
		}
		curl_setopt_array($ch, $opts);
		$resp = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$raw = $resp === false ? '' : (string) $resp;
		$json = json_decode($raw, true);
		$j = is_array($json) ? $json : array();
		$ok = $code >= 200 && $code < 300;
		if (!$ok && !$is_retry) {
			$scope_issue = ((isset($j['code']) && (int) $j['code'] === 4711)
				|| (isset($j['message']) && stripos((string) $j['message'], 'does not contain scopes') !== false)
				|| stripos($raw, 'does not contain scopes') !== false);
			if ($scope_issue) {
				$this->invalidate_s2s_token_cache();
				return $this->api_request($method, $path, $body, null, true);
			}
		}
		return array(
			'ok' => $ok,
			'code' => $code,
			'json' => $j,
			'body' => $raw,
			'error' => $ok ? '' : $this->format_zoom_api_error($code, $raw, $j),
		);
	}

	/**
	 * Human-readable Zoom v2 error (body shapes vary: message, code, errors[]).
	 */
	private function format_zoom_api_error($http_code, $raw_body, array $json)
	{
		$parts = array();
		if (!empty($json['message'])) {
			$parts[] = (string) $json['message'];
		}
		if (isset($json['reason']) && (string) $json['reason'] !== '') {
			$parts[] = (string) $json['reason'];
		}
		if (isset($json['code'])) {
			$parts[] = 'Zoom code ' . $json['code'];
		}
		if (!empty($json['errors']) && is_array($json['errors'])) {
			foreach ($json['errors'] as $e) {
				if (is_string($e)) {
					$parts[] = $e;
				} elseif (is_array($e)) {
					if (!empty($e['message'])) {
						$parts[] = (string) $e['message'];
					} elseif (!empty($e['field'])) {
						$parts[] = (string) $e['field'];
					}
				}
			}
		}
		$detail = implode(' — ', array_filter(array_map('trim', $parts)));
		if ($detail === '' && $raw_body !== '') {
			$detail = trim(substr(preg_replace('/\s+/', ' ', $raw_body), 0, 320));
		}
		if ($detail === '') {
			return 'Zoom API error HTTP ' . (int) $http_code;
		}
		return 'Zoom API error HTTP ' . (int) $http_code . ': ' . $detail;
	}

	/**
	 * Create a long-lived style meeting (type 3 = recurring, no fixed time) when $scheduled_start is null.
	 *
	 * @return array{ok:bool, data?:array, error?:string}
	 */
	public function create_meeting_for_batch($topic, $agenda = '', $scheduled_start = null, $duration = 60, $timezone = 'UTC')
	{
		$host = $this->resolve_host_user_id();
		if (!$host['ok']) {
			return array('ok' => false, 'error' => $host['error']);
		}
		$type = ($scheduled_start === null || $scheduled_start === '') ? 3 : 2;
		$payload = array(
			'topic' => $topic !== '' ? $topic : 'Batch class',
			'type' => $type,
			'duration' => (int) $duration,
			'timezone' => $timezone !== '' ? $timezone : 'UTC',
			'settings' => array(
				'join_before_host' => true,
				'waiting_room' => false,
			),
		);
		if ($agenda !== '') {
			$payload['agenda'] = $agenda;
		}
		if ($type === 2 && $scheduled_start) {
			$payload['start_time'] = $scheduled_start;
		}
		$res = $this->api_request('POST', 'users/' . rawurlencode($host['id']) . '/meetings', $payload);
		if (!$res['ok']) {
			return array('ok' => false, 'error' => $res['error']);
		}
		return array('ok' => true, 'data' => $res['json']);
	}

	/**
	 * @return array{ok:bool, data?:array, error?:string}
	 */
	public function update_meeting($zoom_meeting_id, array $patch)
	{
		$mid = trim((string) $zoom_meeting_id);
		if ($mid === '') {
			return array('ok' => false, 'error' => 'Invalid meeting id');
		}
		$res = $this->api_request('PATCH', 'meetings/' . rawurlencode($mid), $patch);
		if (!$res['ok']) {
			return array('ok' => false, 'error' => $res['error']);
		}
		return array('ok' => true, 'data' => $res['json']);
	}

	/**
	 * @return array{ok:bool, error?:string}
	 */
	public function delete_meeting($zoom_meeting_id)
	{
		$mid = trim((string) $zoom_meeting_id);
		if ($mid === '') {
			return array('ok' => false, 'error' => 'Invalid meeting id');
		}
		$res = $this->api_request('DELETE', 'meetings/' . rawurlencode($mid));
		if (!$res['ok'] && $res['code'] !== 404) {
			return array('ok' => false, 'error' => $res['error']);
		}
		return array('ok' => true);
	}

	/**
	 * End an in-progress meeting for all participants (host / S2S with meeting:update).
	 *
	 * @return array{ok:bool, error?:string, code?:int}
	 */
	public function end_meeting($zoom_meeting_id)
	{
		$mid = preg_replace('/\D+/', '', trim((string) $zoom_meeting_id));
		if ($mid === '') {
			return array('ok' => false, 'error' => 'Invalid meeting id');
		}
		$res = $this->api_request('PUT', 'meetings/' . rawurlencode($mid) . '/status', array('action' => 'end'));
		if (!$res['ok']) {
			$http = isset($res['code']) ? (int) $res['code'] : 0;
			if ($http === 404) {
				return array('ok' => true);
			}
			return array('ok' => false, 'error' => $res['error'], 'code' => $http);
		}
		return array('ok' => true);
	}

	/**
	 * True when the S2S token lacks a required Zoom scope (HTTP 400 / code 4711).
	 *
	 * @param array{ok?:bool, code?:int, error?:string, json?:array} $api_result
	 */
	public function is_missing_scope_error(array $api_result)
	{
		if (!empty($api_result['ok'])) {
			return false;
		}
		$j = isset($api_result['json']) && is_array($api_result['json']) ? $api_result['json'] : array();
		if (isset($j['code']) && (int) $j['code'] === 4711) {
			return true;
		}
		$err = isset($api_result['error']) ? strtolower((string) $api_result['error']) : '';
		return (strpos($err, 'does not contain scopes') !== false || strpos($err, '4711') !== false);
	}

	/**
	 * Setup hint after adding Cloud Recording scopes on the Zoom Marketplace app.
	 */
	public function cloud_recording_scopes_hint()
	{
		return ' In Zoom Marketplace → your Server-to-Server OAuth app → Scopes → Cloud Recording, add: '
			. 'cloud_recording:read:recording:admin (view meeting recordings), '
			. 'cloud_recording:read:list_user_recordings:admin (optional list fallback). '
			. 'Click Continue → Activation → Activate, delete application/cache/zoom_s2s_token.json, then refresh recordings.';
	}

	/**
	 * True when Zoom reports no cloud recording for the meeting (HTTP 404 / code 3301).
	 *
	 * @param array{ok?:bool, code?:int, error?:string, json?:array} $api_result
	 */
	public function is_recording_not_found_response(array $api_result)
	{
		if (!empty($api_result['ok'])) {
			return false;
		}
		$http = isset($api_result['code']) ? (int) $api_result['code'] : 0;
		if ($http === 404) {
			return true;
		}
		$j = isset($api_result['json']) && is_array($api_result['json']) ? $api_result['json'] : array();
		if (isset($j['code']) && (int) $j['code'] === 3301) {
			return true;
		}
		$err = isset($api_result['error']) ? strtolower((string) $api_result['error']) : '';
		return (strpos($err, 'does not exist') !== false || strpos($err, '3301') !== false);
	}

	/**
	 * Cloud recordings for one meeting id (numeric).
	 *
	 * @return array{ok:bool, data?:array, error?:string, code?:int, json?:array, not_found?:bool}
	 */
	public function get_meeting_recordings($zoom_meeting_id)
	{
		$mid = preg_replace('/\D+/', '', trim((string) $zoom_meeting_id));
		if ($mid === '') {
			return array('ok' => false, 'error' => 'Invalid meeting id');
		}
		$res = $this->api_request('GET', 'meetings/' . rawurlencode($mid) . '/recordings');
		if (!$res['ok']) {
			$out = array(
				'ok' => false,
				'error' => $res['error'],
				'code' => isset($res['code']) ? (int) $res['code'] : 0,
				'json' => isset($res['json']) && is_array($res['json']) ? $res['json'] : array(),
			);
			if ($this->is_recording_not_found_response($res)) {
				$out['not_found'] = true;
			}
			return $out;
		}
		return array('ok' => true, 'data' => is_array($res['json']) ? $res['json'] : array());
	}

	/**
	 * List host user cloud recordings (filter by meeting id in caller).
	 * Optional fallback; requires cloud_recording:read:list_user_recordings:admin.
	 *
	 * @return array{ok:bool, meetings?:array, error?:string, scope_missing?:bool, code?:int}
	 */
	public function list_user_recordings($from_date = null, $to_date = null, $page_size = 100)
	{
		$host = $this->resolve_host_user_id();
		if (!$host['ok']) {
			return array('ok' => false, 'error' => $host['error']);
		}
		$q = array('page_size' => max(1, min(300, (int) $page_size)));
		if ($from_date !== null && $from_date !== '') {
			$q['from'] = (string) $from_date;
		}
		if ($to_date !== null && $to_date !== '') {
			$q['to'] = (string) $to_date;
		}
		$res = $this->api_request('GET', 'users/' . rawurlencode($host['id']) . '/recordings?' . http_build_query($q));
		if (!$res['ok']) {
			$out = array(
				'ok' => false,
				'error' => $res['error'],
				'code' => isset($res['code']) ? (int) $res['code'] : 0,
			);
			if ($this->is_missing_scope_error($res)) {
				$out['scope_missing'] = true;
			}
			return $out;
		}
		$meetings = array();
		if (!empty($res['json']['meetings']) && is_array($res['json']['meetings'])) {
			$meetings = $res['json']['meetings'];
		}
		return array('ok' => true, 'meetings' => $meetings);
	}
}
