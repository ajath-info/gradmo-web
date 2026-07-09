<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends MY_Controller
{
	/**
	 * GET/POST api/main/site-details
	 */
	public function site_details()
	{
		$site = $this->db_model->select_data(
			'id,site_title,site_logo,timezone',
			'site_details',
			'',
			1,
			array('id', 'desc')
		);

		if (empty($site)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_no_record_msg')
			));
			return;
		}

		$row = $site[0];
		$logo = !empty($row['site_logo']) ? base_url('uploads/site_data/') . $row['site_logo'] : '';

		echo json_encode(array(
			'status' => 'true',
			'msg' => $this->lang->line('ltr_fetch_successfully'),
			'siteDetails' => array(
				'id' => (int) $row['id'],
				'siteTitle' => isset($row['site_title']) ? $row['site_title'] : '',
				'siteLogo' => isset($row['site_logo']) ? $row['site_logo'] : '',
				'siteLogoUrl' => $logo,
				'timezone' => isset($row['timezone']) ? $row['timezone'] : ''
			)
		));
	}

	/**
	 * GET/POST api/main/get_defaults_requirements
	 * Auth: any valid app token (details are available after login).
	 * Returns payment and zoom API credentials from DB defaults.
	 */
	public function get_defaults_requirements()
	{
		$payload = $this->require_auth_payload();
		if ($payload === false) {
			return;
		}

		$payment = $this->get_payment_gateway_api_credentials_row();
		$zoom = $this->get_zoom_api_credentials_row();

		echo json_encode(array(
			'status' => 'true',
			'msg' => $this->lang->line('ltr_fetch_successfully'),
			'payment_gateway_api_credentials' => !empty($payment) ? $payment : array(),
			'zoom_api_credentials' => !empty($zoom) ? $zoom : array()
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Merge request params from GET/POST *and* the raw JSON body. The app posts JSON
	 * (Content-Type: application/json), which PHP does not populate into $_REQUEST — so without
	 * this, `detail_id`/`id`/`notification_type` from the body would be invisible and a single
	 * read/clear would fall through to "affect all".
	 *
	 * @return array
	 */
	private function notifications_request_data()
	{
		$data = $_REQUEST;
		$body = json_decode(file_get_contents('php://input'), true);
		if (is_array($body)) {
			$data = array_merge($data, $body);
		}
		return $data;
	}

	/**
	 * GET/POST api/main/notifications-list
	 * Auth:
	 *  - student: own notifications by student_id
	 *  - teacher: batch notifications for teacher's mapped batches
	 */
	public function notifications_list()
	{
		// Active notifications only (clear = 0): both read and unread, styled by the `read` flag.
		$this->render_notifications_list(false);
	}

	/**
	 * POST/GET api/main/all_notifications-list
	 * Full history for the caller, user-wise — including cleared rows (clear = 1). Each student row
	 * carries a `read` flag (0/1) so the UI can style seen vs unseen differently.
	 */
	public function all_notifications_list()
	{
		$this->render_notifications_list(true);
	}

	private function render_notifications_list($include_cleared)
	{
		$data = $this->notifications_request_data();
		$payload = $this->require_auth_payload();
		if ($payload === false) {
			return;
		}

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$ut = (string) $payload['ut'];
		if ($ut !== 'student' && $ut !== 'teacher') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Notifications are available for student and teacher only'
			));
			return;
		}

		// Resolve teacher batch IDs before touching the notifications query.
		// Db_model::select_data() calls reset_query(), which would wipe a half-built QB.
		$teacher_batch_ids = null;
		if ($ut === 'teacher') {
			$teacher_id = (int) $payload['uid'];
			$rows = $this->db_model->select_data('batch_id', 'batch_subjects', array('teacher_id' => $teacher_id));
			$teacher_batch_ids = array();
			if (!empty($rows)) {
				foreach ($rows as $r) {
					$bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
					if ($bid > 0 && !in_array($bid, $teacher_batch_ids, true)) {
						$teacher_batch_ids[] = $bid;
					}
				}
			}
			if (empty($teacher_batch_ids)) {
				echo json_encode(array(
					'status' => 'true',
					'userType' => 'teacher',
					'notifications' => array(),
					'pagination' => $this->build_api_list_pagination_meta($page, $limit, 0),
					'msg' => $this->lang->line('ltr_no_record_msg')
				));
				return;
			}
		}

		$n = 'notifications';
		$has_title = $this->db->field_exists('title', $n);

		// Build the (filtered) query for either list or count. Students read per-recipient state
		// from push_notifications_details (1 master row -> N detail rows); teachers see batch master rows.
		$build = function ($for_count) use ($n, $ut, $payload, $teacher_batch_ids, $data, $has_title, $include_cleared) {
			$this->db->reset_query();
			if (!$for_count) {
				$cols = $n . '.id, ' . $n . '.batch_id as batchId, ' . $n . '.notification_type as notificationType, ' .
					($has_title ? $n . '.title as title, ' : '') .
					$n . '.msg, ' . $n . '.url, ' . $n . '.time';
				if ($ut === 'student') {
					// detailId = the per-recipient row (push_notifications_details.id) so the UI can
					// read/clear exactly one row even when duplicates share the same pushnotify_id.
					$cols .= ', pd.id as detailId, pd.userid as userId, pd.`read` as `read`, pd.status as pushStatus';
				} else {
					$cols .= ', ' . $n . '.status, ' . $n . '.seen_by as seenBy';
				}
				$this->db->select($cols, false);
			}
			$this->db->from($n);
			if ($ut === 'student') {
				$this->db->join('push_notifications_details pd', 'pd.pushnotify_id = ' . $n . '.id', 'inner')
					->where('pd.userid', (int) $payload['uid'])
					->where('pd.user_type', 1);
				// Active list hides cleared rows; the "all" (history) list includes them.
				if (!$include_cleared && $this->db->field_exists('clear', 'push_notifications_details')) {
					$this->db->where('pd.clear', '0');
				}
			} else {
				$this->db->where_in($n . '.batch_id', $teacher_batch_ids);
			}
			if (!empty($data['notification_type'])) {
				$this->db->where($n . '.notification_type', $data['notification_type']);
			}
		};

		$build(false);
		$this->db->order_by($n . '.id', 'DESC');
		$this->db->limit($limit, $offset);
		$list = $this->db->get()->result_array();

		$build(true);
		$total = (int) $this->db->count_all_results();

		// Unread badge count for students: unread + not cleared, ignoring the type filter.
		$unread = 0;
		if ($ut === 'student') {
			$this->db->reset_query();
			$this->db->from('push_notifications_details pd')
				->where('pd.userid', (int) $payload['uid'])
				->where('pd.user_type', 1)
				->where('pd.`read`', 0);
			if ($this->db->field_exists('clear', 'push_notifications_details')) {
				$this->db->where('pd.clear', '0');
			}
			$unread = (int) $this->db->count_all_results();
		}

		echo json_encode(array(
			'status' => 'true',
			'userType' => $ut,
			'unreadCount' => $unread,
			'notifications' => !empty($list) ? $list : array(),
			'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			'msg' => !empty($list) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * POST/GET api/main/notifications-read
	 * Marks the caller's notification(s) as read (status = 1).
	 * Input: `id` (single) and/or `ids` (array or comma-separated). Omit both to mark ALL read.
	 * Auth:
	 *  - student: own rows (student_id)
	 *  - teacher: rows of the teacher's mapped batches
	 */
	public function notifications_read()
	{
		$this->notifications_mutate('read');
	}

	/**
	 * POST/GET api/main/notifications-delete
	 * Deletes the caller's notification(s).
	 * Input: `id` (single) and/or `ids` (array or comma-separated). Omit both to delete ALL.
	 * Auth scoping identical to {@see notifications_read}.
	 */
	public function notifications_delete()
	{
		$this->notifications_mutate('delete');
	}

	/**
	 * Shared body for notifications-read / notifications-delete: same auth scoping as
	 * notifications-list, same id parsing, only the final DB op differs.
	 *
	 * @param string $action 'read' | 'delete'
	 */
	private function notifications_mutate($action)
	{
		$data = $this->notifications_request_data();
		$payload = $this->require_auth_payload();
		if ($payload === false) {
			return;
		}

		$ut = (string) $payload['ut'];
		if ($ut !== 'student' && $ut !== 'teacher') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Notifications are available for student and teacher only'
			));
			return;
		}

		// Teacher rows are scoped to mapped batches; resolve before building the QB (select_data resets it).
		$teacher_batch_ids = null;
		if ($ut === 'teacher') {
			$teacher_id = (int) $payload['uid'];
			$rows = $this->db_model->select_data('batch_id', 'batch_subjects', array('teacher_id' => $teacher_id));
			$teacher_batch_ids = array();
			if (!empty($rows)) {
				foreach ($rows as $r) {
					$bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
					if ($bid > 0 && !in_array($bid, $teacher_batch_ids, true)) {
						$teacher_batch_ids[] = $bid;
					}
				}
			}
			if (empty($teacher_batch_ids)) {
				echo json_encode(array(
					'status' => 'true',
					'userType' => 'teacher',
					'affected' => 0,
					'msg' => $this->lang->line('ltr_no_record_msg')
				));
				return;
			}
		}

		// Master ids from `id`/`ids` (notifications.id => affects all of the caller's rows for that
		// master) and per-row detail ids from `detail_id`/`detail_ids` (push_notifications_details.id
		// => affects exactly one row). Detail ids win when both are given.
		$collect = function ($single, $plural) use ($data) {
			$out = array();
			if (isset($data[$single]) && $data[$single] !== '') {
				$out[] = (int) $data[$single];
			}
			if (isset($data[$plural]) && $data[$plural] !== '') {
				$raw = is_array($data[$plural]) ? $data[$plural] : explode(',', (string) $data[$plural]);
				foreach ($raw as $v) {
					$out[] = (int) $v;
				}
			}
			return array_values(array_unique(array_filter($out, function ($v) { return $v > 0; })));
		};
		$ids = $collect('id', 'ids');
		$detailIds = $collect('detail_id', 'detail_ids');

		// Was a specific target requested? If any id param was sent but resolved to nothing (e.g. an
		// empty/invalid detail_id), we must NOT fall through to "affect everything". Only a request
		// with NO id params at all means a deliberate bulk (all) action.
		$target_requested = isset($data['id']) || isset($data['ids']) || isset($data['detail_id']) || isset($data['detail_ids']);
		if ($target_requested && empty($ids) && empty($detailIds)) {
			echo json_encode(array(
				'status' => 'false',
				'userType' => $ut,
				'affected' => 0,
				'msg' => 'Invalid or missing notification id'
			));
			return;
		}

		$n = 'notifications';
		$this->db->reset_query();

		if ($ut === 'student') {
			// Per-recipient state lives in push_notifications_details. Prefer per-row detail ids so a
			// single action affects exactly one row; fall back to master pushnotify_id, else all.
			$pd = 'push_notifications_details';
			$this->db->where('userid', (int) $payload['uid'])->where('user_type', 1);
			if (!empty($detailIds)) {
				$this->db->where_in('id', $detailIds);
			} elseif (!empty($ids)) {
				$this->db->where_in('pushnotify_id', $ids);
			}
			if ($action === 'delete') {
				// Soft "clear": keep the row (still visible in the all/history list) but hide it from
				// the active list. Hard-delete only if the clear column is missing.
				if ($this->db->field_exists('clear', $pd)) {
					$this->db->update($pd, array('clear' => '1'));
				} else {
					$this->db->delete($pd);
				}
			} else {
				$this->db->where('`read`', 0)->update($pd, array('read' => 1));
			}
		} else {
			// Teacher: batch-scoped master rows (no per-recipient copy).
			$this->db->where_in($n . '.batch_id', $teacher_batch_ids);
			if (!empty($ids)) {
				$this->db->where_in($n . '.id', $ids);
			}
			if ($action === 'delete') {
				$this->db->delete($n);
			} else {
				$this->db->update($n, array('status' => 1));
			}
		}
		$affected = (int) $this->db->affected_rows();

		echo json_encode(array(
			'status' => 'true',
			'userType' => $ut,
			'affected' => $affected,
			'msg' => $affected > 0
				? ($action === 'delete' ? $this->lang->line('ltr_deleted_msg') : $this->lang->line('ltr_status_msg'))
				: $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * POST/GET api/main/add-review
	 * Auth: any valid app token (student|teacher|institute).
	 * Required: institute_id, rating (1-5), msg
	 */
	protected function review_table_has_auto_increment_id()
	{
		$query = $this->db->query("SHOW COLUMNS FROM `review` LIKE 'id'");
		$row = $query ? $query->row_array() : array();
		$extra = isset($row['Extra']) ? strtolower((string) $row['Extra']) : '';
		return strpos($extra, 'auto_increment') !== false;
	}

	protected function next_review_id()
	{
		$row = $this->db->query("SELECT MAX(`id`) AS max_id FROM `review` WHERE `id` > 0")->row_array();
		$max_id = isset($row['max_id']) ? (int) $row['max_id'] : 0;
		return $max_id > 0 ? ($max_id + 1) : 1;
	}

	protected function normalize_review_primary_keys()
	{
		$rows = $this->db->query("SELECT `id` FROM `review` WHERE `id` <= 0 ORDER BY `created_at` ASC, `user_id` ASC")->result_array();
		if (empty($rows)) {
			return;
		}

		$next_id = $this->next_review_id();
		foreach ($rows as $row) {
			$current_id = isset($row['id']) ? (int) $row['id'] : 0;
			if ($current_id > 0) {
				continue;
			}
			$this->db->where('id', $current_id);
			if ($this->db->update('review', array('id' => $next_id))) {
				$next_id++;
			}
		}
	}

	public function add_review()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$payload = $this->require_auth_payload(array(), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}

		$user_id = (int) $payload['uid'];
		$user_type = strtolower(trim((string) $payload['ut']));
		if ($user_id < 1 || $user_type === '') {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid token user'));
			return;
		}

		$institute_id = isset($data['institute_id']) ? (int) $data['institute_id'] : 0;
		$rating = isset($data['rating']) ? (int) $data['rating'] : 0;
		$msg = isset($data['msg']) ? trim((string) $data['msg']) : '';

		if ($institute_id < 1 || $rating < 1 || $rating > 5 || $msg === '') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'institute_id, rating (1-5), and msg are required'
			));
			return;
		}

		$insert = array(
			'user_id' => $user_id,
			'user_type' => $user_type,
			'institute_id' => $institute_id,
			'rating' => $rating,
			'msg' => $msg,
			'approved_by' => 0,
			'status' => 0,
			'created_at' => date('Y-m-d H:i:s'),
		);

		$this->normalize_review_primary_keys();
		if (!$this->review_table_has_auto_increment_id()) {
			$insert['id'] = $this->next_review_id();
		}

		$insert_ok = $this->db->insert('review', $this->security->xss_clean($insert));
		$review_id = !empty($insert['id']) ? (int) $insert['id'] : (int) $this->db->insert_id();
		if (!$insert_ok) {
			$review_id = 0;
		}
		if (empty($review_id)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Failed to add review'));
			return;
		}

		echo json_encode(array(
			'status' => 'true',
			'msg' => 'Review added successfully',
			'review' => array(
				'id' => (int) $review_id,
				'userId' => $user_id,
				'userType' => $user_type,
				'instituteId' => $institute_id,
				'rating' => $rating,
				'msg' => $msg,
				'approvedBy' => 0,
				'status' => 0,
			)
		));
	}

	/**
	 * POST/GET api/main/approve-review
	 * Auth: institute token only. Body: review_id (int).
	 * Sets status=1 and approved_by to the institute user id; row must belong to this institute.
	 */
	public function approve_review()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$payload = $this->require_auth_payload(array('institute'), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}

		$institute_uid = (int) $payload['uid'];
		$review_id = isset($data['review_id']) ? (int) $data['review_id'] : 0;
		if ($review_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'review_id is required'));
			return;
		}

		$row = $this->db_model->select_data(
			'id,institute_id,status',
			'review',
			array('id' => $review_id, 'institute_id' => $institute_uid),
			1
		);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Review not found or not allowed'));
			return;
		}

		$this->db_model->update_data(
			'review',
			array(
				'approved_by' => $institute_uid,
				'status' => 1,
			),
			array('id' => $review_id, 'institute_id' => $institute_uid)
		);

		echo json_encode(array(
			'status' => 'true',
			'msg' => 'Review approved',
			'reviewId' => $review_id,
			'approvedBy' => $institute_uid,
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST/GET api/main/institute-reviews-list
	 * Auth: institute. Optional: status (0=pending, 1=approved); omit for all reviews for this institute.
	 * Optional: page, limit or per_page (default 20, max 100).
	 */
	public function institute_reviews_list()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$payload = $this->require_auth_payload(array('institute'), $data);
		if ($payload === false) {
			return;
		}

		$institute_uid = (int) $payload['uid'];
		if ($institute_uid < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid token user'));
			return;
		}

		$cond = array('institute_id' => $institute_uid);
		if (isset($data['status']) && $data['status'] !== '') {
			$cond['status'] = (int) $data['status'];
		}

		$pg = $this->parse_api_list_pagination($data);
		$this->db->reset_query();
		$this->db->from('review');
		$this->db->where($cond);
		$total = (int) $this->db->count_all_results();

		$rows = $this->db_model->select_data(
			'id,user_id as userId,user_type as userType,institute_id as instituteId,rating,msg,approved_by as approvedBy,status,created_at as createdAt',
			'review',
			$cond,
			array($pg['limit'], $pg['offset']),
			array('id', 'desc')
		);

		echo json_encode(array(
			'status' => 'true',
			'instituteId' => $institute_uid,
			'reviews' => !empty($rows) ? $rows : array(),
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
			'msg' => !empty($rows) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg'),
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST/GET api/main/reviews-list
	 * Required: institute_id
	 * Optional: page, limit or per_page (default 20, max 100).
	 * Returns: averageRating, totalReviews (all approved), reviews[] (page slice), pagination.
	 */
	public function reviews_list()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$institute_id = isset($data['institute_id']) ? (int) $data['institute_id'] : 0;
		if ($institute_id < 1) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'institute_id is required'
			));
			return;
		}

		$pg = $this->parse_api_list_pagination($data);
		$review_data = $this->fetch_institute_approved_reviews_for_api($institute_id, array(
			'reviews_limit' => $pg['limit'],
			'reviews_offset' => $pg['offset'],
		));
		$rows = $review_data['reviews'];

		echo json_encode(array(
			'status' => 'true',
			'instituteId' => $institute_id,
			'averageRating' => $review_data['averageRating'],
			'totalReviews' => $review_data['totalReviews'],
			'reviews' => !empty($rows) ? $rows : array(),
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $review_data['totalReviews']),
			'msg' => !empty($rows) ? 'Fetch Successfully.' : 'No record found'
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * GET/POST api/main/pages
	 * Optional param: page_type — must match `pages`.`key` (e.g. about_us, privacy_policy).
	 * Response data keys are built dynamically from the `key` column.
	 */
	public function pages()
	{
		$data = $_REQUEST;
		$page_type = isset($data['page_type']) ? trim((string) $data['page_type']) : '';

		$rows = $this->db_model->select_data(
			'id,`key`,subject,content,status,updated_at,created_at',
			'pages',
			array('status' => 1),
			'',
			array('id', 'desc')
		);

		$mapped = array();

		if (!empty($rows)) {
			foreach ($rows as $r) {
				$page_key = trim((string) (isset($r['key']) ? $r['key'] : ''));
				if ($page_key === '') {
					continue;
				}
				if (isset($mapped[$page_key])) {
					continue;
				}
				$mapped[$page_key] = array(
					'id' => (int) $r['id'],
					'key' => $page_key,
					'subject' => isset($r['subject']) ? $r['subject'] : '',
					'content' => isset($r['content']) ? $r['content'] : '',
					'updatedAt' => isset($r['updated_at']) ? $r['updated_at'] : '',
					'createdAt' => isset($r['created_at']) ? $r['created_at'] : '',
				);
			}
		}

		if ($page_type !== '') {
			if (!isset($mapped[$page_type])) {
				$available = !empty($mapped) ? implode(', ', array_keys($mapped)) : 'none';
				echo json_encode(array(
					'status' => 'false',
					'msg' => 'Invalid page_type. Available keys: ' . $available,
				));
				return;
			}

			echo json_encode(array(
				'status' => 'true',
				'pageType' => $page_type,
				'data' => $mapped[$page_type],
				'msg' => $this->lang->line('ltr_fetch_successfully'),
			));
			return;
		}

		echo json_encode(array(
			'status' => 'true',
			'data' => $mapped,
			'msg' => $this->lang->line('ltr_fetch_successfully'),
		));
	}

	/**
	 * POST api/main/post-enquiry
	 * Required: name, mobile, email, subject, message
	 */
	public function post_enquiry()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$name = isset($data['name']) ? trim($data['name']) : '';
		$mobile = isset($data['mobile']) ? trim($data['mobile']) : '';
		$email = isset($data['email']) ? trim($data['email']) : '';
		$subject = isset($data['subject']) ? trim($data['subject']) : '';
		$message = isset($data['message']) ? trim($data['message']) : '';

		if ($name === '' || $mobile === '' || $email === '' || $subject === '' || $message === '') {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_missing_parameters_msg')
			));
			return;
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Please enter a valid email'
			));
			return;
		}

		$insert = array(
			'name' => $name,
			'mobile' => $mobile,
			'email' => $email,
			'subject' => $subject,
			'message' => $message,
			'date' => date('Y-m-d')
		);
		$insert = $this->security->xss_clean($insert);
		$ins_id = $this->db_model->insert_data('enquiry', $insert);

		echo json_encode(array(
			'status' => !empty($ins_id) ? 'true' : 'false',
			'msg' => !empty($ins_id) ? 'Enquiry submitted successfully' : 'Failed to submit enquiry'
		));
	}

	/**
	 * GET/POST api/main/country-list
	 * Optional: id (country id) — returns one country; omit for all countries.
	 * Optional: page, limit or per_page (default 50, max 500) when listing all.
	 */
	public function country_list()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$where = '';
		if (isset($data['id']) && $data['id'] !== '') {
			$cid = (int) $data['id'];
			if ($cid > 0) {
				$where = array('id' => $cid);
			}
		}

		$pg = $this->parse_api_list_pagination($data, 50, 500);
		$this->db->reset_query();
		$this->db->from('countries');
		if ($where !== '') {
			$this->db->where($where);
		}
		$total = (int) $this->db->count_all_results();

		$this->db->reset_query();
		$this->db->select('id,countryCode,name', false);
		$this->db->from('countries');
		if ($where !== '') {
			$this->db->where($where);
		}
		$this->db->order_by('name', 'asc');
		$this->db->limit($pg['limit'], $pg['offset']);
		$rows = $this->db->get()->result_array();

		$out = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$out[] = array(
					'id' => (int) $r['id'],
					'countryCode' => isset($r['countryCode']) ? $r['countryCode'] : (isset($r['countrycode']) ? $r['countrycode'] : ''),
					'name' => isset($r['name']) ? $r['name'] : ''
				);
			}
		}

		echo json_encode(array(
			'status' => !empty($out) ? 'true' : 'false',
			'countries' => $out,
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
			'msg' => !empty($out) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * GET/POST api/main/state-list
	 * Required: country_id
	 * Optional: page, limit or per_page (default 50, max 500).
	 */
	public function state_list()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$country_id = isset($data['country_id']) ? (int) $data['country_id'] : 0;
		if ($country_id < 1) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'country_id is required'
			));
			return;
		}

		$pg = $this->parse_api_list_pagination($data, 50, 500);
		$this->db->reset_query();
		$this->db->from('states');
		$this->db->where('country_id', $country_id);
		$total = (int) $this->db->count_all_results();

		$this->db->reset_query();
		$this->db->select('id,name,country_id', false);
		$this->db->from('states');
		$this->db->where('country_id', $country_id);
		$this->db->order_by('name', 'asc');
		$this->db->limit($pg['limit'], $pg['offset']);
		$rows = $this->db->get()->result_array();

		$out = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$out[] = array(
					'id' => (int) $r['id'],
					'name' => isset($r['name']) ? $r['name'] : '',
					'countryId' => isset($r['country_id']) ? (int) $r['country_id'] : $country_id
				);
			}
		}

		echo json_encode(array(
			'status' => !empty($out) ? 'true' : 'false',
			'countryId' => $country_id,
			'states' => $out,
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
			'msg' => !empty($out) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * GET/POST api/main/city-list
	 * Required: state_id
	 * Optional: page, limit or per_page (default 50, max 500).
	 */
	public function city_list()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$state_id = isset($data['state_id']) ? (int) $data['state_id'] : 0;
		if ($state_id < 1) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'state_id is required'
			));
			return;
		}

		$pg = $this->parse_api_list_pagination($data, 50, 500);
		$this->db->reset_query();
		$this->db->from('cities');
		$this->db->where('state_id', $state_id);
		$total = (int) $this->db->count_all_results();

		$this->db->reset_query();
		$this->db->select('id,city,state_id', false);
		$this->db->from('cities');
		$this->db->where('state_id', $state_id);
		$this->db->order_by('city', 'asc');
		$this->db->limit($pg['limit'], $pg['offset']);
		$rows = $this->db->get()->result_array();

		$out = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$out[] = array(
					'id' => (int) $r['id'],
					'city' => isset($r['city']) ? $r['city'] : '',
					'stateId' => isset($r['state_id']) ? (int) $r['state_id'] : $state_id
				);
			}
		}

		echo json_encode(array(
			'status' => !empty($out) ? 'true' : 'false',
			'stateId' => $state_id,
			'cities' => $out,
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
			'msg' => !empty($out) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * POST/GET api/main/review-detail
	 * Auth: author only. review_id required. Pending (status=0) reviews only.
	 */
	public function review_detail()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}
		$payload = $this->require_auth_payload(array(), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}
		$review_id = isset($data['review_id']) ? (int) $data['review_id'] : 0;
		if ($review_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'review_id is required'));
			return;
		}
		$rows = $this->db_model->select_data('*', 'review', array('id' => $review_id), 1);
		if (empty($rows[0])) {
			echo json_encode(array('status' => 'false', 'msg' => 'Review not found'));
			return;
		}
		$r = $rows[0];
		if ((int) $payload['uid'] !== (int) $r['user_id'] || strtolower(trim((string) $payload['ut'])) !== strtolower(trim((string) $r['user_type']))) {
			echo json_encode(array('status' => 'false', 'msg' => 'Not allowed'));
			return;
		}
		if ((int) $r['status'] !== 0) {
			echo json_encode(array('status' => 'false', 'msg' => 'Only pending reviews can be edited'));
			return;
		}
		echo json_encode(array(
			'status' => 'true',
			'review' => array(
				'id' => (int) $r['id'],
				'instituteId' => (int) $r['institute_id'],
				'rating' => (int) $r['rating'],
				'msg' => isset($r['msg']) ? (string) $r['msg'] : '',
			),
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST/GET api/main/update-review
	 * Auth: author only. review_id, rating (1-5), msg. Pending only.
	 */
	public function update_review()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}
		$payload = $this->require_auth_payload(array(), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}
		$review_id = isset($data['review_id']) ? (int) $data['review_id'] : 0;
		$rating = isset($data['rating']) ? (int) $data['rating'] : 0;
		$msg = isset($data['msg']) ? trim((string) $data['msg']) : '';
		if ($review_id < 1 || $rating < 1 || $rating > 5 || $msg === '') {
			echo json_encode(array('status' => 'false', 'msg' => 'review_id, rating (1-5), and msg are required'));
			return;
		}
		$rows = $this->db_model->select_data('*', 'review', array('id' => $review_id), 1);
		if (empty($rows[0])) {
			echo json_encode(array('status' => 'false', 'msg' => 'Review not found'));
			return;
		}
		$r = $rows[0];
		if ((int) $payload['uid'] !== (int) $r['user_id'] || strtolower(trim((string) $payload['ut'])) !== strtolower(trim((string) $r['user_type']))) {
			echo json_encode(array('status' => 'false', 'msg' => 'Not allowed'));
			return;
		}
		if ((int) $r['status'] !== 0) {
			echo json_encode(array('status' => 'false', 'msg' => 'Only pending reviews can be updated'));
			return;
		}
		$this->db_model->update_data('review', array(
			'rating' => $rating,
			'msg' => $msg,
		), array('id' => $review_id));
		echo json_encode(array(
			'status' => 'true',
			'msg' => 'Review updated',
			'reviewId' => $review_id,
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST/GET api/main/delete-review
	 * Auth: author only. review_id. Pending only.
	 */
	public function delete_review()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}
		$payload = $this->require_auth_payload(array(), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}
		$review_id = isset($data['review_id']) ? (int) $data['review_id'] : 0;
		if ($review_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'review_id is required'));
			return;
		}
		$rows = $this->db_model->select_data('*', 'review', array('id' => $review_id), 1);
		if (empty($rows[0])) {
			echo json_encode(array('status' => 'false', 'msg' => 'Review not found'));
			return;
		}
		$r = $rows[0];
		if ((int) $payload['uid'] !== (int) $r['user_id'] || strtolower(trim((string) $payload['ut'])) !== strtolower(trim((string) $r['user_type']))) {
			echo json_encode(array('status' => 'false', 'msg' => 'Not allowed'));
			return;
		}
		if ((int) $r['status'] !== 0) {
			echo json_encode(array('status' => 'false', 'msg' => 'Only pending reviews can be deleted'));
			return;
		}
		$this->db_model->delete_data('review', array('id' => $review_id), 1);
		echo json_encode(array('status' => 'true', 'msg' => 'Review deleted'), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * POST/GET api/main/global-search
	 * Body: { "search": "" } -> everything, { "search": "sharma" } -> filtered.
	 * Searches batches (batch_name), institutes (role 4) and teachers (role 3).
	 * Institute/teacher search columns: name, last_name, email, mobile, state,
	 * city, address, school_college_name, teach_education.
	 * Returns data: { batchList, instituteList, teacher_list }.
	 */
	public function globalsearch()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$payload = $this->require_auth_payload(array(), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}

		$search = isset($data['search']) ? trim((string) $data['search']) : '';
		$limit = 50;

		// ---- Batches (match batch_name) ----
		$batch_rows = $this->fetch_all_active_batches_raw($search, $limit, 0);
		$batchList = $this->map_batches_to_dashboard_list_cards(is_array($batch_rows) ? $batch_rows : array());

		// ---- Institutes (role 4) and teachers (role 3) ----
		$user_cols = array('name', 'last_name', 'email', 'mobile', 'state', 'city', 'address', 'school_college_name', 'teach_education');

		$institutes = $this->global_search_users(4, $search, $user_cols, $limit);
		$teachers = $this->global_search_users(3, $search, $user_cols, $limit);

		$instituteList = array();
		foreach ($institutes as $u) {
			$img = !empty($u['teach_image']) ? $u['teach_image'] : (isset($u['image']) ? $u['image'] : '');
			$instituteList[] = array(
				'instituteId' => (int) $u['id'],
				'name' => isset($u['name']) ? $u['name'] : '',
				'lastName' => isset($u['last_name']) ? $u['last_name'] : '',
				'email' => isset($u['email']) ? $u['email'] : '',
				'role' => (int) $u['role'],
				'image' => profile_image_url($img, 4, isset($u['user_type']) ? $u['user_type'] : ''),
			);
		}

		$teacher_list = array();
		foreach ($teachers as $u) {
			$img = !empty($u['teach_image']) ? $u['teach_image'] : (isset($u['image']) ? $u['image'] : '');
			$teacher_list[] = array(
				'Id' => (int) $u['id'],
				'name' => isset($u['name']) ? $u['name'] : '',
				'lastName' => isset($u['last_name']) ? $u['last_name'] : '',
				'email' => isset($u['email']) ? $u['email'] : '',
				'role' => (int) $u['role'],
				'image' => profile_image_url($img, 3, isset($u['user_type']) ? $u['user_type'] : ''),
			);
		}

		echo json_encode(array(
			'status' => 'true',
			'msg' => $this->lang->line('ltr_fetch_successfully'),
			'data' => array(
				'batchList' => $batchList,
				'instituteList' => $instituteList,
				'teacher_list' => $teacher_list,
			),
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * GET/POST api/main/home-content
	 * Public curated home-page content (shared by website home + mobile):
	 * showcase batches (admin_id=1) with instructor + schedule, and site details.
	 */
	public function home_content()
	{
		$limit = 3;

		$batches = $this->db_model->select_data(
			'*',
			'batches use index (id)',
			array('status' => '1', 'admin_id' => '1'),
			$limit,
			array('id', 'DESC')
		);
		if (!is_array($batches)) {
			$batches = array();
		}

		// Instructor names per batch (batch_subjects.teacher_id -> users.name).
		$batch_ids = array();
		foreach ($batches as $b) {
			$batch_ids[] = isset($b['id']) ? (int) $b['id'] : 0;
		}
		$instructor_map = $this->home_instructor_map($batch_ids);

		foreach ($batches as $k => $b) {
			$bid = isset($b['id']) ? (int) $b['id'] : 0;
			$start_time = isset($b['start_time']) ? trim((string) $b['start_time']) : '';
			$end_time = isset($b['end_time']) ? trim((string) $b['end_time']) : '';
			$from = ($start_time !== '' && strtotime($start_time)) ? date('g:i A', strtotime($start_time)) : '';
			$to = ($end_time !== '' && strtotime($end_time)) ? date('g:i A', strtotime($end_time)) : '';
			$schedule = '';
			if ($from !== '' || $to !== '') {
				$schedule = $from . (($from !== '' && $to !== '') ? ' - ' : '') . $to;
			}
			$batches[$k]['instructor'] = isset($instructor_map[$bid]) ? $instructor_map[$bid] : '';
			$batches[$k]['schedule'] = $schedule;
		}

		$site = $this->db_model->select_data('*', 'site_details', array('id' => '1'), 1);

		echo json_encode(array(
			'status' => 'true',
			'msg' => $this->lang->line('ltr_fetch_successfully'),
			'data' => array(
				'batches' => $batches,
				'siteDetails' => !empty($site) ? $site : array(),
			),
		), JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Map batch_id -> comma-joined distinct instructor names (batch_subjects.teacher_id).
	 */
	private function home_instructor_map(array $batch_ids)
	{
		$batch_ids = array_values(array_unique(array_filter(array_map('intval', $batch_ids))));
		if (empty($batch_ids)) {
			return array();
		}
		$this->db->reset_query();
		$rows = $this->db->select('batch_subjects.batch_id, users.name')
			->from('batch_subjects')
			->join('users', 'users.id = batch_subjects.teacher_id', 'left')
			->where_in('batch_subjects.batch_id', $batch_ids)
			->where('users.name IS NOT NULL', null, false)
			->where("TRIM(users.name) <> ''", null, false)
			->order_by('users.name', 'ASC')
			->get()
			->result_array();

		$map = array();
		foreach ($rows as $row) {
			$batch_id = isset($row['batch_id']) ? (int) $row['batch_id'] : 0;
			$name = isset($row['name']) ? trim((string) $row['name']) : '';
			if ($batch_id < 1 || $name === '') {
				continue;
			}
			if (!isset($map[$batch_id])) {
				$map[$batch_id] = array();
			}
			if (!in_array($name, $map[$batch_id], true)) {
				$map[$batch_id][] = $name;
			}
		}
		foreach ($map as $batch_id => $names) {
			$map[$batch_id] = implode(', ', $names);
		}
		return $map;
	}

	/**
	 * Search active users of a given role across the configured text columns.
	 * Empty $search returns all matching-role users (capped at $limit).
	 */
	private function global_search_users($role, $search, array $cols, $limit = 50)
	{
		$this->db->reset_query();
		$this->db->select('id, name, last_name, email, role, image, teach_image, user_type', false);
		$this->db->from('users');
		$this->db->where('role', (int) $role);
		$this->db->where('IFNULL(status, 1) = 1', null, false);
		if ($this->db->field_exists('deleted', 'users')) {
			$this->db->where('deleted', '0');
		}

		$search = trim((string) $search);
		if ($search !== '') {
			$this->db->group_start();
			foreach (array_values($cols) as $i => $col) {
				if ($i === 0) {
					$this->db->like($col, $search);
				} else {
					$this->db->or_like($col, $search);
				}
			}
			// Full-name match so a multi-word query like "gajendra singh" matches
			// name + last_name together (each per-column LIKE above only sees one field).
			$like = '%' . $this->db->escape_like_str($search) . '%';
			$this->db->or_where("CONCAT(name, ' ', IFNULL(last_name, '')) LIKE " . $this->db->escape($like), null, false);
			$this->db->group_end();
		}

		$this->db->order_by('name', 'ASC');
		if ((int) $limit > 0) {
			$this->db->limit((int) $limit);
		}

		$rows = $this->db->get()->result_array();
		return is_array($rows) ? $rows : array();
	}


	public function slider_list() {
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}
		$payload = $this->require_auth_payload(array(), is_array($data) ? $data : null);
		if ($payload === false) {
			return;
		}

		$gallery_images = array();
		$rows = $this->db_model->select_data('id, image, title', 'gallery use index (id)', array('status' => 1, 'type' => 'Image', 'purpose' => 'Advertise'), '', array('id', 'desc'));
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$img = isset($r['image']) ? trim((string) $r['image']) : '';
				if ($img === '') {
					continue;
				}
				$gallery_images[] = array(
					'id' => isset($r['id']) ? (int) $r['id'] : 0,
					'type' => 'gallery',
					'image_url' => base_url('uploads/gallery/') . $img,
					'heading' => isset($r['title']) ? (string) $r['title'] : '',
					'subheading' => '',
					'description' => '',
				);
			}
		}

		$pg = $this->parse_api_list_pagination($data, 20, 100);
		$total = count($gallery_images);
		$banners_page = array_slice($gallery_images, $pg['offset'], $pg['limit']);

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'banners' => $banners_page,
				'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
			)
		), JSON_UNESCAPED_SLASHES);
		die;
	}



}
