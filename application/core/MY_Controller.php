<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller for shared API helpers (access tokens, timezone, institute batches/reviews).
 * Used by api/user/Home, api/batch/Batch (batch_list + detail), api/institute/Institute, api/main/Main, and other API controllers.
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
	 * Core token builder (used when {@see generate_access_token()} returns an unexpected type).
	 *
	 * @return array{access_token: string, iat: int}
	 */
	private function _build_access_token_array($user_id, $user_type)
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

	/**
	 * @return array{access_token: string, iat: int}
	 */
	protected function generate_access_token($user_id, $user_type)
	{
		return $this->_build_access_token_array($user_id, $user_type);
	}

	/**
	 * Normalize token output for login/profile flows.
	 * Handles legacy overrides that return only the token string instead of array{access_token, iat}.
	 *
	 * @return array{access_token: string, iat: int}
	 */
	protected function mint_access_credentials($user_id, $user_type)
	{
		$t = $this->generate_access_token($user_id, $user_type);
		if (is_array($t) && !empty($t['access_token'])) {
			return array(
				'access_token' => (string) $t['access_token'],
				'iat' => isset($t['iat']) ? (int) $t['iat'] : time(),
			);
		}
		if (is_string($t) && $t !== '') {
			return array('access_token' => $t, 'iat' => time());
		}
		return $this->_build_access_token_array((int) $user_id, (string) $user_type);
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

	/**
	 * Standard list pagination from request (page, limit, per_page).
	 *
	 * @param array $data Merged request body / query
	 * @return array{ page: int, limit: int, offset: int }
	 */
	protected function parse_api_list_pagination(array $data, $default_limit = 20, $max_limit = 100)
	{
		$page = isset($data['page']) ? (int) $data['page'] : 1;
		if ($page < 1) {
			$page = 1;
		}
		$limit = $default_limit;
		if (isset($data['limit']) && $data['limit'] !== '' && is_numeric($data['limit'])) {
			$limit = (int) $data['limit'];
		} elseif (isset($data['per_page']) && $data['per_page'] !== '' && is_numeric($data['per_page'])) {
			$limit = (int) $data['per_page'];
		}
		if ($limit < 1) {
			$limit = $default_limit;
		}
		if ($limit > $max_limit) {
			$limit = $max_limit;
		}
		$offset = ($page - 1) * $limit;
		return array(
			'page' => $page,
			'limit' => $limit,
			'offset' => $offset,
		);
	}

	/**
	 * Unified pagination block for list APIs (includes `total` for older clients).
	 *
	 * @param int $total_records Full result count before LIMIT
	 * @return array{ page: int, limit: int, totalRecords: int, totalPages: int, total: int }
	 */
	protected function build_api_list_pagination_meta($page, $limit, $total_records)
	{
		$total = (int) $total_records;
		$lim = (int) $limit;
		$total_pages = ($lim > 0) ? (int) ceil($total / $lim) : 0;
		return array(
			'page' => (int) $page,
			'limit' => $lim,
			'totalRecords' => $total,
			'totalPages' => $total_pages,
			'total' => $total,
		);
	}

	/**
	 * Format one `batches` row for API responses (camelCase).
	 */
	protected function format_batch_row_for_api(array $b)
	{
		$img = isset($b['batch_image']) ? trim((string) $b['batch_image']) : '';
		$out = array(
			'batchId' => (int) (isset($b['id']) ? $b['id'] : 0),
			'batchName' => isset($b['batch_name']) ? $b['batch_name'] : '',
			'startDate' => isset($b['start_date']) ? $b['start_date'] : '',
			'endDate' => isset($b['end_date']) ? $b['end_date'] : '',
			'startTime' => isset($b['start_time']) ? $b['start_time'] : '',
			'endTime' => isset($b['end_time']) ? $b['end_time'] : '',
			'batchType' => isset($b['batch_type']) ? (int) $b['batch_type'] : 0,
			'batchPrice' => isset($b['batch_price']) ? $b['batch_price'] : '',
			'batchOfferPrice' => isset($b['batch_offer_price']) ? $b['batch_offer_price'] : '',
			'description' => isset($b['description']) ? $b['description'] : '',
			'batchImage' => $img,
			'batchImageUrl' => $img !== '' ? base_url('uploads/batch_image/') . $img : '',
			'noOfStudents' => isset($b['no_of_student']) ? (int) $b['no_of_student'] : 0,
			'status' => isset($b['status']) ? (int) $b['status'] : 0,
			'catId' => isset($b['cat_id']) ? (int) $b['cat_id'] : 0,
			'subCatId' => isset($b['sub_cat_id']) ? (int) $b['sub_cat_id'] : 0,
		);
		if (isset($b['pay_mode'])) {
			$out['payMode'] = $b['pay_mode'];
		}
		return $out;
	}

	/**
	 * Human-readable time range for batch schedule lines (e.g. batch list / detail).
	 */
	protected function format_time_range($start_time, $end_time)
	{
		$start_ts = strtotime($start_time);
		$end_ts = strtotime($end_time);
		if ($start_ts && $end_ts) {
			return date('g:i a', $start_ts) . ' - ' . date('g:i a', $end_ts);
		}
		return trim((string) $start_time . ' - ' . (string) $end_time);
	}

	/**
	 * Distinct teacher names assigned to a batch (via batch_subjects).
	 */
	protected function teacher_names_for_batch($batch_id)
	{
		$batch_id = (int) $batch_id;
		if ($batch_id < 1) {
			return '';
		}
		$rows = $this->db_model->select_data(
			'users.name',
			'batch_subjects use index (id)',
			array('batch_subjects.batch_id' => $batch_id),
			'',
			array('batch_subjects.id', 'asc'),
			'',
			array('users', 'users.id = batch_subjects.teacher_id')
		);
		if (empty($rows) || !is_array($rows)) {
			return '';
		}
		$names = array();
		foreach ($rows as $r) {
			if (!empty($r['name']) && !in_array($r['name'], $names, true)) {
				$names[] = $r['name'];
			}
		}
		return implode(', ', $names);
	}

	/**
	 * One row for api/batch/batch_list `enrolled_batches` payload.
	 *
	 * @param array $b Raw batch row (may include enrollment_status, enrolled_at from joins)
	 * @param string $instructor Comma-separated teacher names
	 * @param string $schedule From {@see format_time_range()}
	 */
	protected function format_batch_list_card_for_api(array $b, $instructor, $schedule)
	{
		$bid = (int) (isset($b['id']) ? $b['id'] : 0);
		$img = isset($b['batch_image']) ? trim((string) $b['batch_image']) : '';
		$logo = $img !== '' ? base_url('uploads/batch_image/') . $img : '';
		return array(
			'batch_id' => $bid,
			'title' => isset($b['batch_name']) ? $b['batch_name'] : '',
			'batchName' => isset($b['batch_name']) ? $b['batch_name'] : '',
			'instructor' => (string) $instructor,
			'schedule' => (string) $schedule,
			'start_time' => isset($b['start_time']) ? $b['start_time'] : '',
			'end_time' => isset($b['end_time']) ? $b['end_time'] : '',
			'start_date' => isset($b['start_date']) ? $b['start_date'] : '',
			'end_date' => isset($b['end_date']) ? $b['end_date'] : '',
			'logo' => $logo,
			'batchImage' => $logo,
			'batch_type' => isset($b['batch_type']) ? (int) $b['batch_type'] : 0,
			'description' => isset($b['description']) ? $b['description'] : '',
			'enrollment_status' => isset($b['enrollment_status']) ? (int) $b['enrollment_status'] : 0,
			'enrolled_at' => isset($b['enrolled_at']) && $b['enrolled_at'] !== null ? $b['enrolled_at'] : '',
		);
	}

	/**
	 * Count enrolled batches for a student (same filters as {@see fetch_student_enrolled_batches_raw()}).
	 */
	protected function count_student_enrolled_batches_raw($student_id, $search = '')
	{
		$student_id = (int) $student_id;
		if ($student_id < 1) {
			return 0;
		}
		$search = trim((string) $search);
		$this->db->reset_query();
		$this->db->select('COUNT(DISTINCT batches.id) AS c', false);
		$this->db->from('batches');
		$this->db->join('sudent_batchs', 'sudent_batchs.batch_id = batches.id');
		$this->db->where('batches.status', '1');
		$this->db->where('sudent_batchs.student_id', $student_id);
		if ($search !== '') {
			$this->db->like('batches.batch_name', $search);
		}
		$row = $this->db->get()->row_array();
		return !empty($row['c']) ? (int) $row['c'] : 0;
	}

	/**
	 * Raw enrolled batches for a student (batches + sudent_batchs fields).
	 *
	 * @param string $search Optional filter on batches.batch_name
	 * @param int|null $limit null = no limit; positive = max rows
	 * @param int $offset SQL offset when $limit is set
	 * @return array<int, array>
	 */
	protected function fetch_student_enrolled_batches_raw($student_id, $search = '', $limit = null, $offset = 0)
	{
		$student_id = (int) $student_id;
		if ($student_id < 1) {
			return array();
		}
		$search = trim((string) $search);
		$like = ($search !== '') ? array('batches.batch_name', $search) : '';
		$db_limit = '';
		if ($limit !== null && (int) $limit > 0) {
			$db_limit = array((int) $limit, (int) $offset);
		}
		$batches = $this->db_model->select_data(
			'batches.*, sudent_batchs.status as enrollment_status, sudent_batchs.create_at as enrolled_at',
			'batches use index (id)',
			array('batches.status' => '1', 'sudent_batchs.student_id' => $student_id),
			$db_limit,
			array('batches.id', 'desc'),
			$like,
			array('sudent_batchs', 'sudent_batchs.batch_id = batches.id')
		);
		return is_array($batches) ? $batches : array();
	}

	/**
	 * Count batches assigned to a teacher (same filters as {@see fetch_teacher_assigned_batches_raw()}).
	 */
	protected function count_teacher_assigned_batches_raw($teacher_id, $search = '')
	{
		$teacher_id = (int) $teacher_id;
		if ($teacher_id < 1) {
			return 0;
		}
		$search = trim((string) $search);
		$this->db->reset_query();
		$this->db->select('COUNT(DISTINCT b.id) AS c', false);
		$this->db->from('batch_subjects bs');
		$this->db->join('batches b', 'b.id = bs.batch_id');
		$this->db->where('bs.teacher_id', $teacher_id);
		$this->db->where('b.status', 1);
		if ($search !== '') {
			$this->db->like('b.batch_name', $search);
		}
		$row = $this->db->get()->row_array();
		return !empty($row['c']) ? (int) $row['c'] : 0;
	}

	/**
	 * Raw batches assigned to a teacher (DISTINCT via batch_subjects).
	 *
	 * @param string $search Optional filter on batch_name
	 * @param int|null $limit null = no limit
	 * @param int $offset SQL offset when $limit is set
	 * @return array<int, array>
	 */
	protected function fetch_teacher_assigned_batches_raw($teacher_id, $search = '', $limit = null, $offset = 0)
	{
		$teacher_id = (int) $teacher_id;
		if ($teacher_id < 1) {
			return array();
		}
		$search = trim((string) $search);
		$params = array($teacher_id);
		$like_sql = '';
		if ($search !== '') {
			$like_sql = ' AND b.batch_name LIKE ? ';
			$params[] = '%' . $search . '%';
		}
		$sql = 'SELECT DISTINCT b.*, 1 AS enrollment_status, NULL AS enrolled_at
			 FROM batch_subjects bs
			 JOIN batches b ON b.id = bs.batch_id
			 WHERE bs.teacher_id = ?
			   AND b.status = 1
			   ' . $like_sql . '
			 ORDER BY b.id DESC';
		if ($limit !== null && (int) $limit > 0) {
			$sql .= ' LIMIT ? OFFSET ?';
			$params[] = (int) $limit;
			$params[] = (int) $offset;
		}
		$query = $this->db->query($sql, $params);
		if ($query === false) {
			return array();
		}
		$rows = $query->result_array();
		return is_array($rows) ? $rows : array();
	}

	/**
	 * Map raw batch rows to batch_list card objects (instructor + schedule filled).
	 *
	 * @param array $batches Raw rows from {@see fetch_student_enrolled_batches_raw()} or {@see fetch_teacher_assigned_batches_raw()}
	 * @return array<int, array>
	 */
	protected function map_batches_to_dashboard_list_cards(array $batches)
	{
		$list = array();
		if (empty($batches)) {
			return $list;
		}
		foreach ($batches as $b) {
			if (!is_array($b)) {
				continue;
			}
			$bid = (int) (isset($b['id']) ? $b['id'] : 0);
			if ($bid < 1) {
				continue;
			}
			$list[] = $this->format_batch_list_card_for_api(
				$b,
				$this->teacher_names_for_batch($bid),
				$this->format_time_range(
					isset($b['start_time']) ? $b['start_time'] : '',
					isset($b['end_time']) ? $b['end_time'] : ''
				)
			);
		}
		return $list;
	}

	/**
	 * User ids that may appear on `batches.admin_id` for an institute account.
	 * Includes the institute login id, optional `users.parent_id`, and the first numeric id from `users.admin_id` (tenant field, same idea as teacher tenant resolution).
	 *
	 * @param int $institute_user_id users.id for the institute row
	 * @param array|null $user_row Optional institute row with admin_id, parent_id
	 * @return list<int>
	 */
	protected function resolve_institute_batch_owner_user_ids($institute_user_id, array $user_row = null)
	{
		$ids = array();
		$uid = (int) $institute_user_id;
		if ($uid > 0) {
			$ids[] = $uid;
		}
		if ($user_row !== null) {
			if (!empty($user_row['parent_id'])) {
				$pid = (int) $user_row['parent_id'];
				if ($pid > 0) {
					$ids[] = $pid;
				}
			}
			if (isset($user_row['admin_id'])) {
				$raw = trim((string) $user_row['admin_id']);
				if ($raw !== '' && ctype_digit($raw)) {
					$ids[] = (int) $raw;
				} elseif ($raw !== '') {
					$parts = preg_split('/\s*,\s*/', $raw);
					$first = isset($parts[0]) ? trim((string) $parts[0]) : '';
					if ($first !== '' && ctype_digit($first)) {
						$ids[] = (int) $first;
					}
				}
			}
		}
		$out = array();
		foreach ($ids as $x) {
			$x = (int) $x;
			if ($x > 0 && !in_array($x, $out, true)) {
				$out[] = $x;
			}
		}
		return $out;
	}

	/**
	 * Batches tied to an institute (`batches.admin_id` IN resolved owner user ids).
	 *
	 * @param int $institute_user_id Fallback when options.owner_ids omitted
	 * @param array $options
	 *   - owner_ids: int[] — if set, used for where_in(admin_id); else [institute_user_id]
	 *   - active_only (bool, default true) — status 1 or '1'
	 * @return list<array> formatted batch rows
	 */
	protected function fetch_institute_batches_for_api($institute_user_id, array $options = array())
	{
		$institute_user_id = (int) $institute_user_id;
		if (!empty($options['owner_ids']) && is_array($options['owner_ids'])) {
			$owner_ids = array_values(array_filter(array_map('intval', $options['owner_ids']), function ($v) {
				return $v > 0;
			}));
		} else {
			$owner_ids = $institute_user_id > 0 ? array($institute_user_id) : array();
		}
		if (empty($owner_ids)) {
			return array();
		}
		$active_only = !array_key_exists('active_only', $options) || $options['active_only'];
		$this->db->reset_query();
		$this->db->from('batches');
		$this->db->where_in('admin_id', $owner_ids);
		if ($active_only) {
			$this->db->group_start();
			$this->db->where('status', 1);
			$this->db->or_where('status', '1');
			$this->db->group_end();
		}
		$this->db->order_by('id', 'desc');
		$rows = $this->db->get()->result_array();
		if (empty($rows)) {
			return array();
		}
		$out = array();
		foreach ($rows as $b) {
			$out[] = $this->format_batch_row_for_api($b);
		}
		return $out;
	}

	/**
	 * Approved institute reviews (review.status = 1) plus aggregate rating.
	 *
	 * @param int $institute_id
	 * @param array $options
	 *   - reviews_limit: null = no row limit (all approved); int 1..5000 caps list length (aggregate still full set)
	 *   - reviews_offset: int, used only when reviews_limit is set (SQL OFFSET)
	 * @return array Keys: averageRating (float), totalReviews (int), reviews (list of review rows).
	 */
	protected function fetch_institute_approved_reviews_for_api($institute_id, array $options = array())
	{
		$institute_id = (int) $institute_id;
		$empty = array(
			'averageRating' => 0.0,
			'totalReviews' => 0,
			'reviews' => array(),
		);
		if ($institute_id < 1) {
			return $empty;
		}
		$offset = 0;
		if (array_key_exists('reviews_offset', $options)) {
			$offset = (int) $options['reviews_offset'];
			if ($offset < 0) {
				$offset = 0;
			}
		}
		$limit = null;
		if (array_key_exists('reviews_limit', $options)) {
			if ($options['reviews_limit'] === null || $options['reviews_limit'] === '') {
				$limit = null;
			} else {
				$limit = (int) $options['reviews_limit'];
				if ($limit < 1) {
					$limit = null;
				} elseif ($limit > 5000) {
					$limit = 5000;
				}
			}
		}
		$this->db->reset_query();
		$this->db->select('AVG(rating) as avgRating, COUNT(id) as totalReviews', false);
		$this->db->from('review');
		$this->db->where('institute_id', $institute_id);
		$this->db->where('status', 1);
		$agg = $this->db->get()->row_array();
		$avg = 0.0;
		$total = 0;
		if (!empty($agg)) {
			$avg = isset($agg['avgRating']) && $agg['avgRating'] !== null && $agg['avgRating'] !== ''
				? (float) $agg['avgRating'] : 0.0;
			$total = isset($agg['totalReviews']) ? (int) $agg['totalReviews'] : 0;
		}
		$this->db->reset_query();
		$this->db->select('id,user_id,user_type,institute_id,rating,msg,approved_by,status,created_at', false);
		$this->db->from('review');
		$this->db->where('institute_id', $institute_id);
		$this->db->where('status', 1);
		$this->db->order_by('id', 'desc');
		if ($limit !== null) {
			$this->db->limit($limit, $offset);
		}
		$rows = $this->db->get()->result_array();
		$reviews = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$reviews[] = array(
					'id' => (int) (isset($r['id']) ? $r['id'] : 0),
					'userId' => (int) (isset($r['user_id']) ? $r['user_id'] : 0),
					'userType' => isset($r['user_type']) ? $r['user_type'] : '',
					'instituteId' => (int) (isset($r['institute_id']) ? $r['institute_id'] : $institute_id),
					'rating' => isset($r['rating']) ? (int) $r['rating'] : 0,
					'msg' => isset($r['msg']) ? $r['msg'] : '',
					'approvedBy' => isset($r['approved_by']) ? (int) $r['approved_by'] : 0,
					'status' => isset($r['status']) ? (int) $r['status'] : 0,
					'createdAt' => isset($r['created_at']) ? $r['created_at'] : '',
				);
			}
		}
		return array(
			'averageRating' => round($avg, 2),
			'totalReviews' => $total,
			'reviews' => $reviews,
		);
	}
}
