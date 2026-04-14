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
	 * GET/POST api/main/notifications-list
	 * Auth:
	 *  - student: own notifications by student_id
	 *  - teacher: batch notifications for teacher's mapped batches
	 */
	public function notifications_list()
	{
		$data = $_REQUEST;
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

		$this->db->reset_query();
		$n = 'notifications';
		$this->db->select(
			$n . '.id, ' . $n . '.student_id as studentId, ' . $n . '.batch_id as batchId, ' . $n . '.notification_type as notificationType, ' .
			$n . '.msg, ' . $n . '.url, ' . $n . '.status, ' . $n . '.time, ' . $n . '.seen_by as seenBy',
			false
		);
		$this->db->from($n);

		if ($ut === 'student') {
			$this->db->where($n . '.student_id', (int) $payload['uid']);
		} else {
			$this->db->where_in($n . '.batch_id', $teacher_batch_ids);
		}

		if (!empty($data['notification_type'])) {
			$this->db->where($n . '.notification_type', $data['notification_type']);
		}

		$this->db->order_by($n . '.id', 'DESC');
		$this->db->limit($limit, $offset);
		$list = $this->db->get()->result_array();

		// Count query with same filters
		$this->db->reset_query();
		$this->db->from($n);
		if ($ut === 'student') {
			$this->db->where($n . '.student_id', (int) $payload['uid']);
		} else {
			$this->db->where_in($n . '.batch_id', $teacher_batch_ids);
		}
		if (!empty($data['notification_type'])) {
			$this->db->where($n . '.notification_type', $data['notification_type']);
		}
		$total = (int) $this->db->count_all_results();

		echo json_encode(array(
			'status' => 'true',
			'userType' => $ut,
			'notifications' => !empty($list) ? $list : array(),
			'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			'msg' => !empty($list) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
		));
	}

	/**
	 * POST/GET api/main/add-review
	 * Auth: any valid app token (student|teacher|institute).
	 * Required: institute_id, rating (1-5), msg
	 */
	public function add_review()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = $_REQUEST;
		}

		$payload = $this->require_auth_payload();
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

		$review_id = $this->db_model->insert_data('review', $insert);
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

		$payload = $this->require_auth_payload(array('institute'), $data);
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
	 * Optional param: page_type (about_us | privacy_policy | terms_condition)
	 */
	public function pages()
	{
		$data = $_REQUEST;
		$page_type = isset($data['page_type']) ? strtolower(trim($data['page_type'])) : '';

		$rows = $this->db_model->select_data(
			'id,subject,content,status,updated_at,created_at',
			'pages',
			array('status' => 1),
			'',
			array('id', 'desc')
		);

		$mapped = array(
			'about_us' => array(),
			'privacy_policy' => array(),
			'terms_condition' => array()
		);

		if (!empty($rows)) {
			foreach ($rows as $r) {
				$subject = strtolower(trim((string) $r['subject']));
				$item = array(
					'id' => (int) $r['id'],
					'subject' => isset($r['subject']) ? $r['subject'] : '',
					'content' => isset($r['content']) ? $r['content'] : '',
					'updatedAt' => isset($r['updated_at']) ? $r['updated_at'] : '',
					'createdAt' => isset($r['created_at']) ? $r['created_at'] : ''
				);

				if (strpos($subject, 'about') !== false) {
					$mapped['about_us'] = $item;
				} elseif (strpos($subject, 'privacy') !== false || strpos($subject, 'policy') !== false) {
					$mapped['privacy_policy'] = $item;
				} elseif (strpos($subject, 'term') !== false || strpos($subject, 'condition') !== false) {
					$mapped['terms_condition'] = $item;
				}
			}
		}

		if ($page_type !== '') {
			if (!array_key_exists($page_type, $mapped)) {
				echo json_encode(array(
					'status' => 'false',
					'msg' => 'Invalid page_type. Use about_us, privacy_policy, or terms_condition'
				));
				return;
			}

			echo json_encode(array(
				'status' => !empty($mapped[$page_type]) ? 'true' : 'false',
				'pageType' => $page_type,
				'data' => !empty($mapped[$page_type]) ? $mapped[$page_type] : array(),
				'msg' => !empty($mapped[$page_type]) ? $this->lang->line('ltr_fetch_successfully') : $this->lang->line('ltr_no_record_msg')
			));
			return;
		}

		echo json_encode(array(
			'status' => 'true',
			'data' => $mapped,
			'msg' => $this->lang->line('ltr_fetch_successfully')
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
}
