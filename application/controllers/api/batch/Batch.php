<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Student batch APIs: enrolled list (dashboard) and batch detail with module summaries.
 * Auth: Bearer access_token from multi_user_login (student).
 * Shared token helpers: {@see MY_Controller}.
 */
class Batch extends MY_Controller
{
	private function api_json($status, $msg, $data = array(), $http_code = 200)
	{
		$this->output->set_status_header((int) $http_code);
		$this->output->set_content_type('application/json')->set_output(json_encode(array(
			'status' => $status ? 'true' : 'false',
			'msg' => (string) $msg,
			'data' => is_array($data) ? $data : array(),
		), JSON_UNESCAPED_SLASHES));
	}

	private function read_request_data()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		if (!is_array($data)) {
			$data = array();
		}
		return array_merge($data, $this->input->post(), $this->input->get());
	}

	private function build_slider_banners()
	{
		$banners = array();
		$row = $this->db_model->select_data('slider_details', 'frontend_details', array('id' => 1), 1);
		if (empty($row[0]['slider_details'])) {
			return $banners;
		}
		$sliders = json_decode($row[0]['slider_details'], true);
		if (!is_array($sliders) || empty($sliders['slider_img'])) {
			return $banners;
		}
		$count = count($sliders['slider_img']);
		for ($i = 0; $i < $count; $i++) {
			$img = isset($sliders['slider_img'][$i]) ? $sliders['slider_img'][$i] : '';
			if ($img === '') {
				continue;
			}
			$banners[] = array(
				'id' => $i + 1,
				'image_url' => base_url('uploads/site_data/') . $img,
				'heading' => isset($sliders['slider_heading'][$i]) ? $sliders['slider_heading'][$i] : '',
				'subheading' => isset($sliders['slider_subheading'][$i]) ? $sliders['slider_subheading'][$i] : '',
				'description' => isset($sliders['slider_desc'][$i]) ? $sliders['slider_desc'][$i] : ''
			);
		}
		return $banners;
	}

	/**
	 * book_pdf.batch is stored in different shapes in the wild:
	 * plain id ("1"), JSON string containing "1", or comma-separated ids ("1,2,3").
	 */
	private function apply_book_pdf_batch_filter($batch_id)
	{
		$bid = (int) $batch_id;
		$this->db->group_start();
		$this->db->like('batch', '"' . $bid . '"');
		$this->db->or_where('batch', (string) $bid);
		$this->db->or_where('batch', $bid);
		if ($bid > 0) {
			$this->db->or_where('FIND_IN_SET(' . (int) $bid . ', batch) > 0', null, false);
		}
		$this->db->group_end();
	}

	private function apply_text_batch_filter($column, $batch_id)
	{
		$bid = (int) $batch_id;
		$this->db->group_start();
		$this->db->like($column, '"' . $bid . '"');
		$this->db->or_where($column, (string) $bid);
		$this->db->or_where($column, $bid);
		if ($bid > 0) {
			$this->db->or_where('FIND_IN_SET(' . (int) $bid . ', ' . $column . ') > 0', null, false);
		}
		$this->db->group_end();
	}

	private function zoom_signature($sdk_key, $sdk_secret, $meeting_number, $role)
	{
		$sdk_key = trim((string) $sdk_key);
		$sdk_secret = trim((string) $sdk_secret);
		$meeting_number = trim((string) $meeting_number);
		$role = (int) $role;
		if ($sdk_key === '' || $sdk_secret === '' || $meeting_number === '') {
			return '';
		}
		$time = (time() - 5 * 60) * 1000;
		$data = base64_encode($sdk_key . $meeting_number . $time . $role);
		$hash = hash_hmac('sha256', $data, $sdk_secret, true);
		$_sig = $sdk_key . "." . $meeting_number . "." . $time . "." . $role . "." . base64_encode($hash);
		return rtrim(strtr(base64_encode($_sig), '+/', '-_'), '=');
	}

	private function zoom_display_name_from_payload(array $payload)
	{
		$uid = isset($payload['uid']) ? (int) $payload['uid'] : 0;
		$ut = strtolower(trim((string) (isset($payload['ut']) ? $payload['ut'] : '')));
		if ($uid < 1) {
			return '';
		}
		if ($ut === 'student') {
			$s = $this->db_model->select_data('name', 'students', array('id' => $uid), 1);
			return !empty($s[0]['name']) ? (string) $s[0]['name'] : '';
		}
		$u = $this->db_model->select_data('name', 'users', array('id' => $uid), 1);
		return !empty($u[0]['name']) ? (string) $u[0]['name'] : '';
	}

	/**
	 * Enrolled student or teacher assigned in batch_subjects for this batch_id.
	 */
	private function assert_batch_access_student_or_teacher(array $payload, $batch_id)
	{
		$batch_id = (int) $batch_id;
		if ($batch_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid batch'));
			return false;
		}
		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];
		if ($ut === 'student') {
			if ($uid < 1 || $this->authorize_student_request($uid) === false) {
				return false;
			}
			$enrollment = $this->db_model->select_data('id', 'student_batchs', array('student_id' => $uid, 'batch_id' => $batch_id), 1);
			if (empty($enrollment)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in this batch'));
				return false;
			}
			return true;
		}
		if ($ut === 'teacher') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return false;
			}
			$assigned = $this->db_model->select_data('id', 'batch_subjects', array('teacher_id' => $uid, 'batch_id' => $batch_id), 1);
			if (empty($assigned)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this batch'));
				return false;
			}
			return true;
		}
		echo json_encode(array('status' => 'false', 'msg' => 'This action is available for student and teacher only'));
		return false;
	}

	/**
	 * Teacher assigned to the batch, or institute that owns the batch (batches.admin_id).
	 */
	private function assert_batch_access_teacher_or_institute(array $payload, $batch_id)
	{
		$batch_id = (int) $batch_id;
		if ($batch_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid batch'));
			return false;
		}
		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];
		if ($ut === 'teacher') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return false;
			}
			$assigned = $this->db_model->select_data('id', 'batch_subjects', array('teacher_id' => $uid, 'batch_id' => $batch_id), 1);
			if (empty($assigned)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this batch'));
				return false;
			}
			return true;
		}
		if ($ut === 'institute') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Institute not found'));
				return false;
			}
			$batch = $this->db_model->select_data('id,admin_id', 'batches use index (id)', array('id' => $batch_id), 1);
			if (empty($batch) || (int) $batch[0]['admin_id'] !== $uid) {
				echo json_encode(array('status' => 'false', 'msg' => 'This batch does not belong to your institute'));
				return false;
			}
			return true;
		}
		echo json_encode(array('status' => 'false', 'msg' => 'This action is available for teacher and institute only'));
		return false;
	}

	/**
	 * Single book_pdf row linked to batch_id (same matching rules as library list).
	 *
	 * @param bool $active_only If true, only status = 1.
	 * @return array|null
	 */
	private function get_book_pdf_for_batch($book_id, $batch_id, $active_only = true)
	{
		$book_id = (int) $book_id;
		$batch_id = (int) $batch_id;
		if ($book_id < 1 || $batch_id < 1) {
			return null;
		}
		$this->db->reset_query();
		$this->db->from('book_pdf');
		$this->db->where('id', $book_id);
		if ($active_only) {
			$this->db->where('status', 1);
		}
		$this->apply_book_pdf_batch_filter($batch_id);
		$row = $this->db->get()->row_array();
		return !empty($row) ? $row : null;
	}

	private function get_notes_pdf_for_batch($notes_id, $batch_id, $active_only = true)
	{
		$notes_id = (int) $notes_id;
		$batch_id = (int) $batch_id;
		if ($notes_id < 1 || $batch_id < 1) {
			return null;
		}
		$this->db->reset_query();
		$this->db->from('notes_pdf');
		$this->db->where('id', $notes_id);
		if ($active_only) {
			$this->db->where('status', 1);
		}
		$this->apply_text_batch_filter('batch', $batch_id);
		$row = $this->db->get()->row_array();
		return !empty($row) ? $row : null;
	}

	/** Video visible to teacher if it is mapped to any batch they teach. */
	private function video_accessible_to_teacher($video_id, $teacher_id)
	{
		$video_id = (int) $video_id;
		$teacher_id = (int) $teacher_id;
		if ($video_id < 1 || $teacher_id < 1) {
			return false;
		}
		$rows = $this->db_model->select_data('batch_id', 'batch_subjects', array('teacher_id' => $teacher_id), '');
		if (empty($rows)) {
			return false;
		}
		foreach ($rows as $r) {
			$bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
			if ($bid < 1) {
				continue;
			}
			$this->db->from('video_lectures');
			$this->db->where('id', $video_id);
			$this->db->where('status', 1);
			$this->apply_text_batch_filter('batch', $bid);
			if ((int) $this->db->count_all_results() > 0) {
				return true;
			}
		}
		return false;
	}


	/**
	 * POST/GET api/batch/batch-list
	 * Optional: search (filters batch_name); page (default 1); limit or per_page (default 20, max 100).
	 * Optional: list — when set to "All" (case-insensitive), returns all active batches (status = 1) for any valid token.
	 * When set to "my" (case-insensitive), same as empty/absent: student = enrolled batches only; teacher = assigned batches only.
	 * When list is empty/absent: student = enrolled batches; teacher = assigned batches; other roles get an error.
	 */
	public function batch_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$list_flag = isset($data['list']) ? trim((string) $data['list']) : '';
		$want_all_active = (strcasecmp($list_flag, 'All') === 0);

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$total_records = 0;
		$batches = array();

		if ($want_all_active) {
			$total_records = $this->count_all_active_batches_raw($search);
			$batches = $this->fetch_all_active_batches_raw($search, $limit, $offset);
		}
		// STUDENT FLOW: existing behavior (enrolled batches)
		elseif ($payload['ut'] === 'student') {
			$student_id = (int) $payload['uid'];
			if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
				return;
			}

			$student = $this->db_model->select_data(
				'id,name,image,email',
				'students use index (id)',
				array('id' => $student_id, 'status' => 1),
				1
			);
			if (empty($student)) {
				echo json_encode(array('status' => 'false', 'msg' => 'Student not found'));
				return;
			}

			$total_records = $this->count_student_enrolled_batches_raw($student_id, $search);
			$batches = $this->fetch_student_enrolled_batches_raw($student_id, $search, $limit, $offset);
		}
		// TEACHER FLOW: batches assigned via batch_subjects.teacher_id
		elseif ($payload['ut'] === 'teacher') {
			$teacher_id = (int) $payload['uid'];
			if ($teacher_id < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return;
			}

			$total_records = $this->count_teacher_assigned_batches_raw($teacher_id, $search);
			$batches = $this->fetch_teacher_assigned_batches_raw($teacher_id, $search, $limit, $offset);
		} else {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Batch list is available for student and teacher only (or pass list=All for all active batches)'
			));
			return;
		}

		$list = $this->map_batches_to_dashboard_list_cards(is_array($batches) ? $batches : array());

		$arr = array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'enrolled_batches' => $list,
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total_records),
			)
		);

		echo json_encode($arr,JSON_UNESCAPED_SLASHES);
        die;

	}

	/**
	 * POST/GET api/batch/slider-list
	 * Auth: any valid app token.
	 */
	public function slider_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		$banners = $this->build_slider_banners();
		$pg = $this->parse_api_list_pagination($data, 20, 100);
		$total = is_array($banners) ? count($banners) : 0;
		$banners_page = is_array($banners) ? array_slice($banners, $pg['offset'], $pg['limit']) : array();

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

	/**
	 * POST/GET api/batch/batch-details
	 * Required: batch_id
	 * Auth: student (enrolled) or teacher (assigned via batch_subjects).
	 */
	public function batch_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}

		$batch_id = (int) $data['batch_id'];
		$student_id = 0;
		$enrollment = array();

		if ($ut === 'student') {
			$student_id = $uid;
			if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
				return;
			}
			$enrollment = $this->db_model->select_data(
				'*',
				'student_batchs',
				array('student_id' => $student_id, 'batch_id' => $batch_id),
				1
			);
			// For web "all active batches" flow, allow opening details even if not enrolled yet.
			// Client can then show "Enroll to Unlock" and continue to payment.
			if (empty($enrollment)) {
				$enrollment = array(array(
					'status' => 0,
					'create_at' => '',
					'added_by' => ''
				));
			}
		} elseif ($ut === 'teacher') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return;
			}
			$assigned = $this->db_model->select_data('id', 'batch_subjects', array('teacher_id' => $uid, 'batch_id' => $batch_id), 1);
			if (empty($assigned)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this batch'));
				return;
			}
			$enrollment = array(array(
				'status' => 1,
				'create_at' => '',
				'added_by' => 'teacher',
			));
		} else {
			echo json_encode(array(
				'status' => 'false',
				'msg' => 'Batch details are available for student and teacher only'
			));
			return;
		}

		$batch = $this->db_model->select_data('*', 'batches use index (id)', array('id' => $batch_id), 1);
		if (empty($batch)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Batch not found'));
			return;
		}

		$b = $batch[0];
		$admin_id = (int) $b['admin_id'];

		$logo = '';
		if (!empty($b['batch_image'])) {
			$logo = batch_image_url($b['batch_image']);
		}

		$batch_fecherd = $this->db_model->select_data(
			'batch_specification_heading as batchSpecification, batch_fecherd as fecherd',
			'batch_fecherd',
			array('batch_id' => $batch_id)
		);
		if (empty($batch_fecherd)) {
			$batch_fecherd = array();
		}

		$live_row = $this->db_model->select_data(
			'id,end_time',
			'live_class_history',
			array('batch_id' => $batch_id),
			'1',
			array('id', 'desc')
		);
		$is_live = false;
		$current_session_id = null;
		if (!empty($live_row[0])) {
			$et = isset($live_row[0]['end_time']) ? trim((string) $live_row[0]['end_time']) : '';
			if ($et === '' || $et === '0000-00-00 00:00:00') {
				$is_live = true;
				$current_session_id = (int) $live_row[0]['id'];
			}
		}

		$video_count = (int) $this->db_model->countAll(
			'video_lectures use index (id)',
			array('status' => 1, 'batch' => $batch_id)
		);
		$this->db->from('book_pdf');
		$this->db->where('status', 1);
		$this->apply_book_pdf_batch_filter($batch_id);
		$book_count = (int) $this->db->count_all_results();
		$this->db->from('notes_pdf');
		$this->db->where('status', 1);
		$this->apply_text_batch_filter('batch', $batch_id);
		$notes_count = (int) $this->db->count_all_results();

		if ($ut === 'student') {
			$attendance_marked = (int) $this->db_model->countAll(
				'attendance',
				array('student_id' => $student_id, 'batch_id' => $batch_id)
			);
		} else {
			$attendance_marked = (int) $this->db_model->countAll(
				'attendance',
				array('batch_id' => $batch_id)
			);
		}

		$upcoming_exams = (int) $this->db_model->countAll(
			'exams use index (id)',
			array(
				'batch_id' => $batch_id,
				'status' => 1,
				'type' => 1,
				'mock_sheduled_date >=' => date('Y-m-d')
			)
		);

		$today = date('Y-m-d');
		$homework_total = (int) $this->db_model->countAll(
			'homeworks use index (id)',
			array('batch_id' => $batch_id)
		);
		$homework_today = (int) $this->db_model->countAll(
			'homeworks use index (id)',
			array('batch_id' => $batch_id, 'date' => $today)
		);
		$homework_upcoming = (int) $this->db_model->countAll(
			'homeworks use index (id)',
			array('batch_id' => $batch_id, 'date >=' => $today)
		);
		if ($homework_today < 1 && $homework_total > 0) {
			// Batch details tile should reflect available homework in listing, not only today's rows.
			$homework_today = $homework_total;
		}

		$category = $this->db_model->select_data('name', 'batch_category use index (id)', array('id' => $b['cat_id']), 1);
		$subcategory = $this->db_model->select_data('name', 'batch_subcategory use index (id)', array('id' => $b['sub_cat_id']), 1);
		$can_enroll = false;
		if ($ut === 'student') {
			$batch_type = isset($b['batch_type']) ? (int) $b['batch_type'] : 0;
			// Paid batch: allow pay only when no payment history exists for this student+batch.
			if ($batch_type === 2) {
				$paid_rows = $this->db_model->select_data(
					'id',
					'student_payment_history',
					array('student_id' => $student_id, 'batch_id' => $batch_id),
					1
				);
				$can_enroll = empty($paid_rows);
			} else {
				$can_enroll = ((int) $enrollment[0]['status'] !== 1);
			}
		}

		$data = array(
			'batch_id' => $batch_id,
			'title' => $b['batch_name'],
			'batchName' => $b['batch_name'],
			'instructor' => $this->teacher_names_for_batch($batch_id),
			'schedule' => $this->format_time_range($b['start_time'], $b['end_time']),
			'start_time' => $b['start_time'],
			'end_time' => $b['end_time'],
			'start_date' => $b['start_date'],
			'end_date' => $b['end_date'],
			'logo' => $logo,
			'batchImage' => $logo,
			'description' => $b['description'],
			'batch_type' => (int) $b['batch_type'],
			'batch_price' => $b['batch_price'],
			'batch_offer_price' => $b['batch_offer_price'],
			'pay_mode' => $b['pay_mode'],
			'category_name' => !empty($category[0]['name']) ? $category[0]['name'] : '',
			'subcategory_name' => !empty($subcategory[0]['name']) ? $subcategory[0]['name'] : '',
			'batchFecherd' => $batch_fecherd,
			'enrollment' => array(
				'status' => (int) $enrollment[0]['status'],
				'create_at' => isset($enrollment[0]['create_at']) ? $enrollment[0]['create_at'] : '',
				'added_by' => isset($enrollment[0]['added_by']) ? $enrollment[0]['added_by'] : ''
			),
			'canEnroll' => $can_enroll,
			'modules' => array(
				'live_classes' => array(
					'is_live' => $is_live,
					'current_session_id' => $current_session_id,
					'icon' => 'icofont-video-cam'
				),
				'video_lectures' => array('count' => $video_count, 'icon' => 'icofont-file-alt'),
				'library' => array(
					'book_count' => $book_count,
					'notes_count' => $notes_count,
					'has_new_content' => false,
					'icon' => 'icofont-book'
				),
				'attendance' => array(
					'marked' => $attendance_marked,
					'marked_records' => $attendance_marked,
					'icon' => 'icofont-check-circled'
				),
				'upcoming_exams' => array('count' => $upcoming_exams, 'icon' => 'icofont-exam'),
				'homework' => array(
					'today_count' => $homework_today,
					'pending_count' => $homework_upcoming,
					'icon' => 'icofont-file-alt'
				)
			)
		);

		$arr = array(
			'status' => 'true',
			'message' => 'Success',
			'batch_details' => $data
		);
		echo json_encode($arr,JSON_UNESCAPED_SLASHES);
        die;
	}

	/**
	 * POST/GET api/batch/library-list
	 * Books (PDF) for a batch from book_pdf.
	 * Auth: student (enrolled) or teacher (assigned via batch_subjects).
	 * Optional: search, sort_by, sort_dir, page, limit.
	 */
	public function library_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}

		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$batch = $this->db_model->select_data('*', 'batches use index (id)', array('id' => $batch_id), 1);
		if (empty($batch)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Batch not found'));
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'added_at';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'desc';
		if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
			$sort_dir = 'desc';
		}
		$order_columns = array(
			'added_at' => 'added_at',
			'date_added' => 'added_at',
			'title' => 'title',
			'subject' => 'subject',
			'topic' => 'topic',
			'file_name' => 'file_name'
		);
		if (!isset($order_columns[$sort_by])) {
			$sort_by = 'added_at';
		}
		$order_col = $order_columns[$sort_by];

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$this->db->from('book_pdf');
		$this->db->where('status', 1);
		$this->apply_book_pdf_batch_filter($batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('file_name', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->from('book_pdf');
		$this->db->where('status', 1);
		$this->apply_book_pdf_batch_filter($batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('file_name', $search);
			$this->db->group_end();
		}
		$this->db->order_by($order_col, $sort_dir);
		$this->db->limit($limit, $offset);
		$rows = $this->db->get()->result_array();

		$base_path = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'book' . DIRECTORY_SEPARATOR;
		$items = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$file = isset($r['file_name']) ? $r['file_name'] : '';
				$download_url = $file !== '' ? base_url('uploads/book/') . $file : '';
				$file_size_bytes = null;
				$file_size_label = '';
				if ($file !== '' && is_file($base_path . $file)) {
					$file_size_bytes = (int) filesize($base_path . $file);
					if ($file_size_bytes >= 1048576) {
						$file_size_label = round($file_size_bytes / 1048576, 2) . ' MB';
					} elseif ($file_size_bytes >= 1024) {
						$file_size_label = round($file_size_bytes / 1024) . ' KB';
					} else {
						$file_size_label = $file_size_bytes . ' B';
					}
				}

				$items[] = array(
					'id' => (int) $r['id'],
					'title' => isset($r['title']) ? $r['title'] : '',
					'topic' => isset($r['topic']) ? $r['topic'] : '',
					'subject' => isset($r['subject']) ? $r['subject'] : '',
					'fileName' => $file,
					'downloadUrl' => $download_url,
					'fileSizeBytes' => $file_size_bytes,
					'fileSize' => $file_size_label,
					'addedAt' => isset($r['added_at']) ? $r['added_at'] : ''
				);
			}
		}

		$arr = array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'batch_id' => $batch_id,
				'library' => $items,
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			)
		);
		echo json_encode($arr, JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST api/batch/library-add-book (multipart recommended: pdf_file)
	 * Auth: teacher | institute only.
	 */
	public function library_add_book()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['batch_id']) || empty($data['title'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id and title are required'));
			return;
		}
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$batch = $this->db_model->select_data('admin_id', 'batches use index (id)', array('id' => $batch_id), 1);
		if (empty($batch)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Batch not found'));
			return;
		}
		$admin_id = (int) $batch[0]['admin_id'];
		if (empty($_FILES['pdf_file']['name'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'pdf_file is required'));
			return;
		}
		$config['upload_path'] = './uploads/book/';
		$config['allowed_types'] = '*';
		$config['max_size'] = '0';
		$this->load->library('upload', $config);
		if (!$this->upload->do_upload('pdf_file')) {
			echo json_encode(array('status' => 'false', 'msg' => strip_tags($this->upload->display_errors('', ''))));
			return;
		}
		$uploaddata = $this->upload->data();
		$pic = $uploaddata['raw_name'];
		$pic_ext = $uploaddata['file_ext'];
		$image = $pic . date('ymdHis') . $pic_ext;
		$old_path = './uploads/book/' . $pic . $pic_ext;
		$new_path = './uploads/book/' . $image;
		if (is_file($old_path)) {
			@rename($old_path, $new_path);
		} else {
			$image = $uploaddata['file_name'];
		}
		$subject = isset($data['subject']) ? trim((string) $data['subject']) : '';
		$topic = isset($data['topic']) ? trim((string) $data['topic']) : '';
		$insert = $this->security->xss_clean(array(
			'admin_id' => $admin_id,
			'title' => trim((string) $data['title']),
			'batch' => (string) $batch_id,
			'topic' => $topic,
			'subject' => $subject,
			'file_name' => $image,
			'status' => 1,
			'added_by' => (int) $payload['uid'],
			'added_at' => date('Y-m-d H:i:s'),
		));
		$new_id = $this->db_model->insert_data('book_pdf', $insert);
		if (empty($new_id)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Failed to add book'));
			return;
		}
		$download_url = $image !== '' ? base_url('uploads/book/') . $image : '';
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'id' => (int) $new_id,
				'batch_id' => $batch_id,
				'title' => $insert['title'],
				'topic' => $insert['topic'],
				'subject' => $insert['subject'],
				'fileName' => $image,
				'downloadUrl' => $download_url,
			),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/attendance-roster
	 * Auth: teacher|institute. Required: batch_id, date(YYYY-MM-DD optional -> today).
	 */
	public function attendance_roster()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($batch_id < 1) {
			$this->api_json(false, 'batch_id is required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$date = isset($data['date']) ? trim((string) $data['date']) : '';
		if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			$date = date('Y-m-d');
		}
		$pg = $this->parse_api_list_pagination($data);

		$this->db->from('student_batchs sb');
		$this->db->join('students s', 's.id = sb.student_id', 'inner');
		$this->db->where('sb.batch_id', $batch_id);
		$this->db->where('sb.status', 1);
		$this->db->where('s.status', 1);
		$total = (int) $this->db->count_all_results();

		$this->db->select('s.id as studentId,s.name,s.image,s.email,s.contact_no as mobile,a.id as attendanceId,a.time,a.date');
		$this->db->from('student_batchs sb');
		$this->db->join('students s', 's.id = sb.student_id', 'inner');
		$this->db->join('attendance a', 'a.student_id = s.id AND a.batch_id = ' . (int) $batch_id . ' AND a.date = ' . $this->db->escape($date), 'left');
		$this->db->where('sb.batch_id', $batch_id);
		$this->db->where('sb.status', 1);
		$this->db->where('s.status', 1);
		$this->db->order_by('s.name', 'asc');
		$this->db->limit($pg['limit'], $pg['offset']);
		$rows = $this->db->get()->result_array();

		$list = array();
		foreach ($rows as $r) {
			$img = isset($r['image']) ? (string) $r['image'] : '';
			$list[] = array(
				'studentId' => (int) $r['studentId'],
				'name' => isset($r['name']) ? $r['name'] : '',
				'image' => $img,
				'imageUrl' => $img !== '' ? profile_image_url($img, 2, 'student') : '',
				'email' => isset($r['email']) ? $r['email'] : '',
				'mobile' => isset($r['mobile']) ? $r['mobile'] : '',
				'isPresent' => !empty($r['attendanceId']) ? 1 : 0,
				'attendanceId' => !empty($r['attendanceId']) ? (int) $r['attendanceId'] : 0,
				'time' => !empty($r['time']) ? $r['time'] : '',
				'date' => $date,
			);
		}
		$this->api_json(true, 'Success', array(
			'batch_id' => $batch_id,
			'date' => $date,
			'students' => $list,
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
		));
	}

	/** @var bool */
	private $batch_attendance_day_status_checked = false;

	private function batch_ensure_attendance_day_status_column()
	{
		if ($this->batch_attendance_day_status_checked) {
			return;
		}
		$this->batch_attendance_day_status_checked = true;
		if ($this->db->field_exists('day_status', 'attendance')) {
			return;
		}
		@$this->db->query('ALTER TABLE `attendance` ADD COLUMN `day_status` VARCHAR(20) NOT NULL DEFAULT \'\' AFTER `time`');
	}

	private function batch_attendance_minutes_from_midnight($t)
	{
		$t = trim((string) $t);
		if ($t === '') {
			return null;
		}
		if (preg_match('/^\d{1,2}\.\d{2}$/', $t)) {
			$t = preg_replace('/^(\d{1,2})\.(\d{2})$/', '$1:$2', $t);
		} else {
			$t = preg_replace('/^(\d{1,2}:\d{2}(?::\d{2})?)\.\d+$/', '$1', $t);
		}
		if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $t, $m)) {
			return (int) $m[1] * 60 + (int) $m[2];
		}
		$ts = strtotime('1970-01-01 ' . $t);
		if ($ts) {
			return ((int) date('G', $ts)) * 60 + (int) date('i', $ts);
		}
		return null;
	}

	private function batch_attendance_is_late($attendance_time, $batch_start_time)
	{
		$att = $this->batch_attendance_minutes_from_midnight($attendance_time);
		$start = $this->batch_attendance_minutes_from_midnight($batch_start_time);
		if ($att === null || $start === null) {
			return 0;
		}
		return $att > $start ? 1 : 0;
	}

	private function batch_attendance_student_enrolled_in_batch($student_id, $batch_id)
	{
		$student_id = (int) $student_id;
		$batch_id = (int) $batch_id;
		if ($student_id < 1 || $batch_id < 1) {
			return false;
		}
		if (!empty($this->db_model->select_data('id', 'student_batchs', array('student_id' => $student_id, 'batch_id' => $batch_id), 1))) {
			return true;
		}
		$rows = $this->db_model->select_data('batch_id, multi_batch', 'students', array('id' => $student_id, 'status' => 1), 1);
		if (empty($rows)) {
			return false;
		}
		$r = $rows[0];
		$sb = isset($r['batch_id']) ? trim((string) $r['batch_id']) : '';
		if ($sb !== '' && ((int) $sb === $batch_id || preg_match('/\b' . $batch_id . '\b/', $sb))) {
			return true;
		}
		$mb = isset($r['multi_batch']) ? trim((string) $r['multi_batch']) : '';
		if ($mb !== '') {
			$dec = json_decode($mb, true);
			if (is_array($dec)) {
				foreach ($dec as $v) {
					if ((int) $v === $batch_id) {
						return true;
					}
				}
			}
			if (strpos($mb, '"' . $batch_id . '"') !== false || strpos($mb, (string) $batch_id) !== false) {
				return true;
			}
		}
		return false;
	}

	private function batch_normalize_attendance_day_status($raw)
	{
		$s = strtolower(trim((string) $raw));
		if ($s === 'halfday') {
			$s = 'half_day';
		}
		$allowed = array('present', 'late', 'absent', 'half_day');
		return in_array($s, $allowed, true) ? $s : '';
	}

	/**
	 * Map DB row + late flag to matrix status: present | late | half | absent | empty.
	 */
	private function batch_matrix_status_from_row($time, $day_status, $batch_start_time)
	{
		$ds = strtolower(trim((string) $day_status));
		if ($ds === 'absent') {
			return 'absent';
		}
		if ($ds === 'half_day' || $ds === 'halfday') {
			return 'half';
		}
		if ($ds === 'late') {
			return 'late';
		}
		if ($ds === 'present') {
			return 'present';
		}
		if (trim((string) $time) === '') {
			return 'empty';
		}
		return ((int) $this->batch_attendance_is_late($time, $batch_start_time) === 1) ? 'late' : 'present';
	}

	/**
	 * POST/GET api/batch/attendance-roster-matrix
	 * Auth: teacher|institute. Required: batch_id, year, month (1–12).
	 * Returns students (rows), dates (columns), cells keyed "studentId_YYYY-MM-DD".
	 */
	public function attendance_roster_matrix()
	{
		$this->batch_ensure_attendance_day_status_column();
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($batch_id < 1) {
			$this->api_json(false, 'batch_id is required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$year = isset($data['year']) ? (int) $data['year'] : (int) date('Y');
		$month = isset($data['month']) ? (int) $data['month'] : (int) date('n');
		if ($year < 2000 || $year > 2100) {
			$this->api_json(false, 'Invalid year');
			return;
		}
		if ($month < 1 || $month > 12) {
			$this->api_json(false, 'Invalid month');
			return;
		}
		$dim = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$date_from = sprintf('%04d-%02d-01', $year, $month);
		$date_to = sprintf('%04d-%02d-%02d', $year, $month, $dim);

		$batch_row = $this->db_model->select_data('id,start_time', 'batches', array('id' => $batch_id, 'status' => 1), 1);
		$batch_start = !empty($batch_row[0]['start_time']) ? (string) $batch_row[0]['start_time'] : '';

		$this->db->select('s.id as studentId,s.name,s.email,s.contact_no as mobile');
		$this->db->from('student_batchs sb');
		$this->db->join('students s', 's.id = sb.student_id', 'inner');
		$this->db->where('sb.batch_id', $batch_id);
		$this->db->where('sb.status', 1);
		$this->db->where('s.status', 1);
		$this->db->order_by('s.name', 'asc');
		$stu_rows = $this->db->get()->result_array();
		$students = array();
		foreach ($stu_rows as $r) {
			$students[] = array(
				'studentId' => (int) $r['studentId'],
				'name' => isset($r['name']) ? $r['name'] : '',
				'email' => isset($r['email']) ? $r['email'] : '',
				'mobile' => isset($r['mobile']) ? $r['mobile'] : '',
			);
		}

		$this->db->select('a.id as attendanceId,a.student_id as studentId,a.date,a.time,TRIM(IFNULL(a.day_status, \'\')) as day_status', false);
		$this->db->from('attendance a');
		$this->db->where('a.batch_id', $batch_id);
		$this->db->where('a.date >=', $date_from);
		$this->db->where('a.date <=', $date_to);
		$this->db->order_by('a.date', 'asc');
		$att_rows = $this->db->get()->result_array();

		$cells = array();
		foreach ($att_rows as $ar) {
			$sid = isset($ar['studentId']) ? (int) $ar['studentId'] : 0;
			$d = isset($ar['date']) ? (string) $ar['date'] : '';
			if ($sid < 1 || $d === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
				continue;
			}
			$time = isset($ar['time']) ? (string) $ar['time'] : '';
			$ds = isset($ar['day_status']) ? (string) $ar['day_status'] : '';
			$st = $this->batch_matrix_status_from_row($time, $ds, $batch_start);
			$key = $sid . '_' . $d;
			$cells[$key] = array(
				'status' => $st,
				'time' => $time,
				'attendanceId' => !empty($ar['attendanceId']) ? (int) $ar['attendanceId'] : 0,
				'dayStatus' => $ds,
			);
		}

		$dates = array();
		for ($d = 1; $d <= $dim; $d++) {
			$ymd = sprintf('%04d-%02d-%02d', $year, $month, $d);
			$ts = strtotime($ymd);
			$dates[] = array(
				'date' => $ymd,
				'day' => $d,
				'weekday' => $ts ? (int) date('N', $ts) : 0,
				'label' => $d . ' ' . date('D', $ts),
			);
		}

		$this->api_json(true, 'Success', array(
			'batch_id' => $batch_id,
			'year' => $year,
			'month' => $month,
			'dateFrom' => $date_from,
			'dateTo' => $date_to,
			'batchStartTime' => $batch_start,
			'students' => $students,
			'dates' => $dates,
			'cells' => $cells,
		));
	}

	/**
	 * POST api/batch/attendance-matrix-save
	 * Auth: teacher|institute. JSON: batch_id, entries: [{ student_id, date, status: present|late|half|absent|empty, time? }], default_time?.
	 */
	public function attendance_matrix_save()
	{
		$this->batch_ensure_attendance_day_status_column();
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$ut = strtolower(trim((string) $payload['ut']));
		$actor_id = (int) $payload['uid'];
		if ($actor_id < 1) {
			$this->api_json(false, 'Invalid user');
			return;
		}

		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($batch_id < 1) {
			$this->api_json(false, 'batch_id is required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		if ($ut === 'teacher' && !$this->teacher_assigned_for_attendance_batch($actor_id, $batch_id)) {
			$this->api_json(false, 'You are not assigned to this batch');
			return;
		}

		$batch_row = $this->db_model->select_data('id,admin_id,start_time', 'batches', array('id' => $batch_id, 'status' => 1), 1);
		if (empty($batch_row)) {
			$this->api_json(false, 'Batch not found');
			return;
		}
		$admin_id = isset($batch_row[0]['admin_id']) ? (int) $batch_row[0]['admin_id'] : 0;
		$batch_start_t = !empty($batch_row[0]['start_time']) ? (string) $batch_row[0]['start_time'] : '';

		$default_time = isset($data['default_time']) ? trim((string) $data['default_time']) : '';
		if ($default_time === '') {
			$default_time = $batch_start_t !== '' ? $batch_start_t : '09:00';
		}

		$entries = isset($data['entries']) && is_array($data['entries']) ? $data['entries'] : array();
		if (empty($entries)) {
			$this->api_json(false, 'entries array is required');
			return;
		}

		$results = array();
		$any_ok = false;
		foreach ($entries as $e) {
			if (!is_array($e)) {
				continue;
			}
			$student_id = isset($e['student_id']) ? (int) $e['student_id'] : (isset($e['studentId']) ? (int) $e['studentId'] : 0);
			$date = isset($e['date']) ? trim((string) $e['date']) : '';
			$status_raw = isset($e['status']) ? strtolower(trim((string) $e['status'])) : '';
			if ($student_id < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
				$results[] = array('studentId' => $student_id, 'date' => $date, 'status' => 'false', 'msg' => 'Invalid student_id or date');
				continue;
			}

			if ($status_raw === 'empty' || $status_raw === 'clear' || $status_raw === 'none' || $status_raw === '') {
				$this->db_model->delete_data('attendance', array(
					'student_id' => $student_id,
					'batch_id' => $batch_id,
					'date' => $date,
				));
				$results[] = array('studentId' => $student_id, 'date' => $date, 'status' => 'true', 'msg' => 'cleared');
				$any_ok = true;
				continue;
			}

			$map = array('present' => 'present', 'late' => 'late', 'half' => 'half_day', 'half_day' => 'half_day', 'absent' => 'absent');
			if (!isset($map[$status_raw])) {
				$results[] = array('studentId' => $student_id, 'date' => $date, 'status' => 'false', 'msg' => 'Invalid status');
				continue;
			}
			$db_ds = $map[$status_raw];

			$time_raw = isset($e['time']) ? trim((string) $e['time']) : '';
			$time = $time_raw !== '' ? $time_raw : $default_time;
			if ($time === '') {
				$time = '09:00';
			}

			$ds_norm = $this->batch_normalize_attendance_day_status($db_ds);
			$auto_status = ((int) $this->batch_attendance_is_late($time, $batch_start_t) === 1) ? 'late' : 'present';
			$final_day_status = $ds_norm !== '' ? $ds_norm : $auto_status;

			$student = $this->db_model->select_data('id,admin_id', 'students', array('id' => $student_id, 'status' => 1), 1);
			if (empty($student)) {
				$results[] = array('studentId' => $student_id, 'date' => $date, 'status' => 'false', 'msg' => 'Student not found');
				continue;
			}
			if (!$this->batch_attendance_student_enrolled_in_batch($student_id, $batch_id)) {
				$prior = $this->db_model->select_data('id', 'attendance', array('student_id' => $student_id, 'batch_id' => $batch_id), 1);
				if (empty($prior)) {
					$results[] = array('studentId' => $student_id, 'date' => $date, 'status' => 'false', 'msg' => 'Student is not enrolled in this batch');
					continue;
				}
			}
			$use_admin = $admin_id > 0 ? $admin_id : (int) $student[0]['admin_id'];

			$existing = $this->db_model->select_data('id', 'attendance', array(
				'student_id' => $student_id,
				'date' => $date,
				'batch_id' => $batch_id,
			), 1);

			if (!empty($existing)) {
				$att_id = (int) $existing[0]['id'];
				$upd = array(
					'time' => $time,
					'added_id' => $actor_id,
					'admin_id' => $use_admin,
				);
				if ($this->db->field_exists('day_status', 'attendance')) {
					$upd['day_status'] = $final_day_status;
				}
				$this->db_model->update_data_limit('attendance', $upd, array('id' => $att_id), 1);
				$results[] = array(
					'studentId' => $student_id,
					'date' => $date,
					'status' => 'true',
					'msg' => 'updated',
					'attendanceId' => $att_id,
					'time' => $time,
					'dayStatus' => $final_day_status,
				);
			} else {
				$ins_row = $this->security->xss_clean(array(
					'student_id' => $student_id,
					'added_id' => $actor_id,
					'date' => $date,
					'time' => $time,
					'batch_id' => $batch_id,
					'admin_id' => $use_admin > 0 ? $use_admin : 1,
				));
				if ($this->db->field_exists('day_status', 'attendance')) {
					$ins_row['day_status'] = $final_day_status;
				}
				$this->db_model->insert_data('attendance', $ins_row);
				$att_id = (int) $this->db->insert_id();
				$results[] = array(
					'studentId' => $student_id,
					'date' => $date,
					'status' => 'true',
					'msg' => 'added',
					'attendanceId' => $att_id,
					'time' => $time,
					'dayStatus' => $final_day_status,
				);
			}
			$any_ok = true;
		}

		$ok_count = count(array_filter($results, function ($r) {
			return isset($r['status']) && $r['status'] === 'true';
		}));
		$this->api_json($any_ok, $any_ok ? 'Saved' : 'No changes saved', array(
			'batch_id' => $batch_id,
			'results' => $results,
			'savedCount' => $ok_count,
		));
	}

	/**
	 * POST/GET api/batch/batch-subjects
	 * Auth: teacher|institute. Required: batch_id.
	 */
	public function batch_subjects()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($batch_id < 1) {
			$this->api_json(false, 'batch_id is required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		$this->db->distinct();
		$this->db->select('bs.subject_id as subjectId,s.subject_name as subjectName');
		$this->db->from('batch_subjects bs');
		$this->db->join('subjects s', 's.id = bs.subject_id', 'left');
		$this->db->where('bs.batch_id', $batch_id);
		if (strtolower(trim((string) $payload['ut'])) === 'teacher') {
			$this->db->where('bs.teacher_id', $teacher_id);
		}
		$this->db->order_by('s.subject_name', 'asc');
		$rows = $this->db->get()->result_array();
		$list = array();
		foreach ($rows as $r) {
			$sid = isset($r['subjectId']) ? (int) $r['subjectId'] : 0;
			if ($sid < 1) {
				continue;
			}
			$list[] = array(
				'subjectId' => $sid,
				'subjectName' => isset($r['subjectName']) ? (string) $r['subjectName'] : ('Subject #' . $sid),
			);
		}
		$this->api_json(true, 'Success', array('batch_id' => $batch_id, 'subjects' => $list));
	}

	public function notes_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('student', 'teacher'), $data);
		if ($payload === false) {
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($batch_id < 1) {
			$this->api_json(false, 'batch_id is required');
			return;
		}
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}
		$search = isset($data['search']) ? trim((string) $data['search']) : '';
		$pg = $this->parse_api_list_pagination($data);

		$this->db->from('notes_pdf');
		$this->db->where('status', 1);
		$this->apply_text_batch_filter('batch', $batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('file_name', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->select('id,title,topic,subject,file_name as fileName,added_at as addedAt');
		$this->db->from('notes_pdf');
		$this->db->where('status', 1);
		$this->apply_text_batch_filter('batch', $batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('file_name', $search);
			$this->db->group_end();
		}
		$this->db->order_by('id', 'desc');
		$this->db->limit($pg['limit'], $pg['offset']);
		$rows = $this->db->get()->result_array();
		foreach ($rows as &$r) {
			$file = isset($r['fileName']) ? (string) $r['fileName'] : '';
			$r['downloadUrl'] = $file !== '' ? base_url('uploads/notes/') . $file : '';
		}
		unset($r);

		$this->api_json(true, 'Success', array(
			'batch_id' => $batch_id,
			'notes' => $rows,
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
		));
	}

	public function notes_add()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		$title = isset($data['title']) ? trim((string) $data['title']) : '';
		if ($batch_id < 1 || $title === '') {
			$this->api_json(false, 'batch_id and title are required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		if (empty($_FILES['pdf_file']['name'])) {
			$this->api_json(false, 'pdf_file is required');
			return;
		}
		$batch = $this->db_model->select_data('admin_id', 'batches use index (id)', array('id' => $batch_id), 1);
		$admin_id = !empty($batch[0]['admin_id']) ? (int) $batch[0]['admin_id'] : 0;

		$config['upload_path'] = './uploads/notes/';
		if (!is_dir($config['upload_path'])) {
			@mkdir($config['upload_path'], 0777, true);
		}
		$config['allowed_types'] = '*';
		$config['max_size'] = '0';
		$this->load->library('upload', $config);
		if (!$this->upload->do_upload('pdf_file')) {
			$this->api_json(false, strip_tags($this->upload->display_errors('', '')));
			return;
		}
		$uploaddata = $this->upload->data();
		$raw = isset($uploaddata['raw_name']) ? (string) $uploaddata['raw_name'] : '';
		$ext = isset($uploaddata['file_ext']) ? (string) $uploaddata['file_ext'] : '';
		$image = $raw . date('ymdHis') . $ext;
		$old_path = './uploads/notes/' . $raw . $ext;
		$new_path = './uploads/notes/' . $image;
		if (is_file($old_path)) {
			@rename($old_path, $new_path);
		} else {
			$image = isset($uploaddata['file_name']) ? (string) $uploaddata['file_name'] : '';
		}
		$insert = $this->security->xss_clean(array(
			'admin_id' => $admin_id,
			'title' => $title,
			'batch' => (string) $batch_id,
			'topic' => isset($data['topic']) ? trim((string) $data['topic']) : '',
			'subject' => isset($data['subject']) ? trim((string) $data['subject']) : '',
			'file_name' => $image,
			'status' => 1,
			'added_by' => (int) $payload['uid'],
			'added_at' => date('Y-m-d H:i:s'),
		));
		$new_id = (int) $this->db_model->insert_data('notes_pdf', $insert);
		$this->api_json(true, 'Notes added successfully', array(
			'id' => $new_id,
			'batch_id' => $batch_id,
			'downloadUrl' => $image !== '' ? base_url('uploads/notes/') . $image : '',
		));
	}

	public function notes_edit()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$notes_id = isset($data['notes_id']) ? (int) $data['notes_id'] : 0;
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($notes_id < 1 || $batch_id < 1) {
			$this->api_json(false, 'notes_id and batch_id are required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_notes_pdf_for_batch($notes_id, $batch_id, true);
		if (empty($row)) {
			$this->api_json(false, 'Notes not found');
			return;
		}
		$update = array();
		if (isset($data['title']) && trim((string) $data['title']) !== '') {
			$update['title'] = trim((string) $data['title']);
		}
		if (array_key_exists('subject', $data)) {
			$update['subject'] = trim((string) $data['subject']);
		}
		if (array_key_exists('topic', $data)) {
			$update['topic'] = trim((string) $data['topic']);
		}
		if (!empty($_FILES['pdf_file']['name'])) {
			$config['upload_path'] = './uploads/notes/';
			$config['allowed_types'] = '*';
			$config['max_size'] = '0';
			$this->load->library('upload', $config);
			if (!$this->upload->do_upload('pdf_file')) {
				$this->api_json(false, strip_tags($this->upload->display_errors('', '')));
				return;
			}
			$uploaddata = $this->upload->data();
			$raw = isset($uploaddata['raw_name']) ? (string) $uploaddata['raw_name'] : '';
			$ext = isset($uploaddata['file_ext']) ? (string) $uploaddata['file_ext'] : '';
			$image = $raw . date('ymdHis') . $ext;
			$old_path = './uploads/notes/' . $raw . $ext;
			$new_path = './uploads/notes/' . $image;
			if (is_file($old_path)) {
				@rename($old_path, $new_path);
			} else {
				$image = isset($uploaddata['file_name']) ? (string) $uploaddata['file_name'] : '';
			}
			$update['file_name'] = $image;
		}
		if (empty($update)) {
			$this->api_json(false, 'No changes provided');
			return;
		}
		$update = $this->security->xss_clean($update);
		$this->db_model->update_data_limit('notes_pdf', $update, array('id' => $notes_id), 1);
		$this->api_json(true, 'Notes updated successfully', array('id' => $notes_id));
	}

	public function notes_delete()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$notes_id = isset($data['notes_id']) ? (int) $data['notes_id'] : 0;
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($notes_id < 1 || $batch_id < 1) {
			$this->api_json(false, 'notes_id and batch_id are required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_notes_pdf_for_batch($notes_id, $batch_id, true);
		if (empty($row)) {
			$this->api_json(false, 'Notes not found');
			return;
		}
		$this->db_model->update_data_limit('notes_pdf', array('status' => 0), array('id' => $notes_id), 1);
		$this->api_json(true, 'Notes deleted', array('id' => $notes_id));
	}

	public function notes_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$notes_id = isset($data['notes_id']) ? (int) $data['notes_id'] : 0;
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($notes_id < 1 || $batch_id < 1) {
			$this->api_json(false, 'notes_id and batch_id are required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_notes_pdf_for_batch($notes_id, $batch_id, true);
		if (empty($row)) {
			$this->api_json(false, 'Notes not found');
			return;
		}
		$file = isset($row['file_name']) ? (string) $row['file_name'] : '';
		$this->api_json(true, 'Success', array(
			'notes' => array(
				'id' => (int) $row['id'],
				'title' => isset($row['title']) ? $row['title'] : '',
				'topic' => isset($row['topic']) ? $row['topic'] : '',
				'subject' => isset($row['subject']) ? $row['subject'] : '',
				'fileName' => $file,
				'downloadUrl' => $file !== '' ? base_url('uploads/notes/') . $file : '',
				'addedAt' => isset($row['added_at']) ? $row['added_at'] : '',
			)
		));
	}

	public function video_lecture_add()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		$title = isset($data['title']) ? trim((string) $data['title']) : '';
		if ($batch_id < 1 || $title === '') {
			$this->api_json(false, 'batch_id and title are required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$url = isset($data['url']) ? trim((string) $data['url']) : '';
		if ($url === '' && !empty($_FILES['video_file']['name'])) {
			$config['upload_path'] = './uploads/video/';
			if (!is_dir($config['upload_path'])) {
				@mkdir($config['upload_path'], 0777, true);
			}
			$config['allowed_types'] = '*';
			$config['max_size'] = '0';
			$this->load->library('upload', $config);
			if (!$this->upload->do_upload('video_file')) {
				$this->api_json(false, strip_tags($this->upload->display_errors('', '')));
				return;
			}
			$ud = $this->upload->data();
			$url = base_url('uploads/video/') . $ud['file_name'];
		}
		if ($url === '') {
			$this->api_json(false, 'url or video_file is required');
			return;
		}
		$batch = $this->db_model->select_data('admin_id', 'batches use index (id)', array('id' => $batch_id), 1);
		$admin_id = !empty($batch[0]['admin_id']) ? (int) $batch[0]['admin_id'] : 0;
		$insert = $this->security->xss_clean(array(
			'admin_id' => $admin_id,
			'title' => $title,
			'batch' => (string) $batch_id,
			'topic' => isset($data['topic']) ? trim((string) $data['topic']) : '',
			'subject' => isset($data['subject']) ? trim((string) $data['subject']) : '',
			'description' => isset($data['description']) ? trim((string) $data['description']) : '',
			'url' => $url,
			'video_type' => isset($data['video_type']) ? (int) $data['video_type'] : 1,
			'preview_type' => isset($data['preview_type']) ? (int) $data['preview_type'] : 1,
			'status' => 1,
			'added_by' => (int) $payload['uid'],
			'added_at' => date('Y-m-d H:i:s'),
		));
		$new_id = (int) $this->db_model->insert_data('video_lectures', $insert);
		$this->api_json(true, 'Video lecture added successfully', array('id' => $new_id, 'batch_id' => $batch_id));
	}

	public function video_lecture_edit()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$video_id = isset($data['video_lecture_id']) ? (int) $data['video_lecture_id'] : 0;
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($video_id < 1 || $batch_id < 1) {
			$this->api_json(false, 'video_lecture_id and batch_id are required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->db_model->select_data('id', 'video_lectures use index (id)', array('id' => $video_id, 'status' => 1), 1);
		if (empty($row)) {
			$this->api_json(false, 'Video lecture not found');
			return;
		}
		$update = array();
		foreach (array('title', 'topic', 'subject', 'description') as $f) {
			if (isset($data[$f])) {
				$update[$f] = trim((string) $data[$f]);
			}
		}
		if (isset($data['video_type'])) {
			$update['video_type'] = (int) $data['video_type'];
		}
		if (isset($data['preview_type'])) {
			$update['preview_type'] = (int) $data['preview_type'];
		}
		if (isset($data['url']) && trim((string) $data['url']) !== '') {
			$update['url'] = trim((string) $data['url']);
		}
		if (!empty($_FILES['video_file']['name'])) {
			$config['upload_path'] = './uploads/video/';
			$config['allowed_types'] = '*';
			$config['max_size'] = '0';
			$this->load->library('upload', $config);
			if (!$this->upload->do_upload('video_file')) {
				$this->api_json(false, strip_tags($this->upload->display_errors('', '')));
				return;
			}
			$ud = $this->upload->data();
			$update['url'] = base_url('uploads/video/') . $ud['file_name'];
		}
		if (empty($update)) {
			$this->api_json(false, 'No changes provided');
			return;
		}
		$update = $this->security->xss_clean($update);
		$this->db_model->update_data_limit('video_lectures', $update, array('id' => $video_id), 1);
		$this->api_json(true, 'Video lecture updated successfully', array('id' => $video_id));
	}

	public function video_lecture_delete()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$video_id = isset($data['video_lecture_id']) ? (int) $data['video_lecture_id'] : 0;
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		if ($video_id < 1 || $batch_id < 1) {
			$this->api_json(false, 'video_lecture_id and batch_id are required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$this->db_model->update_data_limit('video_lectures', array('status' => 0), array('id' => $video_id), 1);
		$this->api_json(true, 'Video lecture deleted', array('id' => $video_id));
	}

	public function exam_add()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : 0;
		$name = isset($data['name']) ? trim((string) $data['name']) : '';
		if ($batch_id < 1 || $name === '') {
			$this->api_json(false, 'batch_id and name are required');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$admin_id = ($payload['ut'] === 'teacher') ? (int) $this->teacher_tenant_admin_id((int) $payload['uid']) : (int) $payload['uid'];
		$insert = $this->security->xss_clean(array(
			'admin_id' => $admin_id > 0 ? $admin_id : 1,
			'name' => $name,
			'type' => isset($data['type']) ? (int) $data['type'] : 1,
			'format' => isset($data['format']) ? (int) $data['format'] : 1,
			'batch_id' => $batch_id,
			'total_question' => isset($data['total_question']) ? (int) $data['total_question'] : 0,
			'time_duration' => isset($data['time_duration']) ? trim((string) $data['time_duration']) : '',
			'question_ids' => isset($data['question_ids']) ? trim((string) $data['question_ids']) : '',
			'mock_sheduled_date' => isset($data['mock_sheduled_date']) ? trim((string) $data['mock_sheduled_date']) : date('Y-m-d'),
			'mock_sheduled_time' => isset($data['mock_sheduled_time']) ? trim((string) $data['mock_sheduled_time']) : '',
			'total_marks' => isset($data['total_marks']) ? (float) $data['total_marks'] : 0,
			'marking_parcent' => isset($data['marking_parcent']) ? (float) $data['marking_parcent'] : 0,
			'status' => 1,
			'added_by' => (int) $payload['uid'],
			'added_at' => date('Y-m-d H:i:s'),
		));
		$new_id = (int) $this->db_model->insert_data('exams', $insert);
		$this->api_json(true, 'Exam added successfully', array('id' => $new_id, 'batch_id' => $batch_id));
	}

	public function exam_edit()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$exam_id = isset($data['exam_id']) ? (int) $data['exam_id'] : 0;
		if ($exam_id < 1) {
			$this->api_json(false, 'exam_id is required');
			return;
		}
		$exam = $this->db_model->select_data('id,batch_id', 'exams use index (id)', array('id' => $exam_id, 'status' => 1), 1);
		if (empty($exam)) {
			$this->api_json(false, 'Exam not found');
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : (int) $exam[0]['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$update = array();
		foreach (array('name', 'time_duration', 'mock_sheduled_date', 'mock_sheduled_time', 'question_ids') as $f) {
			if (isset($data[$f])) {
				$update[$f] = trim((string) $data[$f]);
			}
		}
		foreach (array('type', 'format', 'total_question', 'total_marks', 'marking_parcent', 'batch_id') as $f) {
			if (isset($data[$f]) && $data[$f] !== '') {
				$update[$f] = $data[$f];
			}
		}
		if (empty($update)) {
			$this->api_json(false, 'No changes provided');
			return;
		}
		$update = $this->security->xss_clean($update);
		$this->db_model->update_data_limit('exams', $update, array('id' => $exam_id), 1);
		$this->api_json(true, 'Exam updated successfully', array('id' => $exam_id));
	}

	public function exam_delete()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		$exam_id = isset($data['exam_id']) ? (int) $data['exam_id'] : 0;
		if ($exam_id < 1) {
			$this->api_json(false, 'exam_id is required');
			return;
		}
		$exam = $this->db_model->select_data('id,batch_id', 'exams use index (id)', array('id' => $exam_id, 'status' => 1), 1);
		if (empty($exam)) {
			$this->api_json(false, 'Exam not found');
			return;
		}
		if (!$this->assert_batch_access_teacher_or_institute($payload, (int) $exam[0]['batch_id'])) {
			return;
		}
		$this->db_model->update_data_limit('exams', array('status' => 0), array('id' => $exam_id), 1);
		$this->api_json(true, 'Exam deleted', array('id' => $exam_id));
	}

	/**
	 * POST api/batch/library-edit-book (optional multipart pdf_file to replace file)
	 * Auth: teacher | institute only.
	 */
	public function library_edit_book()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['book_id']) || empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'book_id and batch_id are required'));
			return;
		}
		$book_id = (int) $data['book_id'];
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_book_pdf_for_batch($book_id, $batch_id, true);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Book not found'));
			return;
		}
		$batch = $this->db_model->select_data('admin_id', 'batches use index (id)', array('id' => $batch_id), 1);
		$admin_id = !empty($batch) ? (int) $batch[0]['admin_id'] : (int) $row['admin_id'];

		$update = array(
			'admin_id' => $admin_id,
			'added_by' => (int) $payload['uid'],
		);
		if (isset($data['title']) && trim((string) $data['title']) !== '') {
			$update['title'] = trim((string) $data['title']);
		}
		if (array_key_exists('subject', $data)) {
			$update['subject'] = trim((string) $data['subject']);
		}
		if (array_key_exists('topic', $data)) {
			$update['topic'] = trim((string) $data['topic']);
		}
		if (!empty($_FILES['pdf_file']['name'])) {
			$config['upload_path'] = './uploads/book/';
			$config['allowed_types'] = '*';
			$config['max_size'] = '0';
			$this->load->library('upload', $config);
			if (!$this->upload->do_upload('pdf_file')) {
				echo json_encode(array('status' => 'false', 'msg' => strip_tags($this->upload->display_errors('', ''))));
				return;
			}
			$uploaddata = $this->upload->data();
			$pic = $uploaddata['raw_name'];
			$pic_ext = $uploaddata['file_ext'];
			$image = $pic . date('ymdHis') . $pic_ext;
			$old_path = './uploads/book/' . $pic . $pic_ext;
			$new_path = './uploads/book/' . $image;
			if (is_file($old_path)) {
				@rename($old_path, $new_path);
			} else {
				$image = $uploaddata['file_name'];
			}
			$update['file_name'] = $image;
		}
		$update = $this->security->xss_clean($update);
		$this->db_model->update_data_limit('book_pdf', $update, array('id' => $book_id), 1);
		$updated = $this->get_book_pdf_for_batch($book_id, $batch_id, true);
		$file = !empty($updated['file_name']) ? $updated['file_name'] : '';
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'id' => $book_id,
				'batch_id' => $batch_id,
				'title' => isset($updated['title']) ? $updated['title'] : '',
				'topic' => isset($updated['topic']) ? $updated['topic'] : '',
				'subject' => isset($updated['subject']) ? $updated['subject'] : '',
				'fileName' => $file,
				'downloadUrl' => $file !== '' ? base_url('uploads/book/') . $file : '',
			),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/library-delete-book
	 * Auth: teacher | institute only. Soft-delete: status = 0.
	 */
	public function library_delete_book()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['book_id']) || empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'book_id and batch_id are required'));
			return;
		}
		$book_id = (int) $data['book_id'];
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_book_pdf_for_batch($book_id, $batch_id, true);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Book not found'));
			return;
		}
		$this->db_model->update_data_limit('book_pdf', array('status' => 0), array('id' => $book_id), 1);
		echo json_encode(array('status' => 'true', 'message' => 'Success', 'msg' => 'Book removed'), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/library-book-details
	 * Auth: teacher | institute only.
	 */
	public function library_book_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher', 'institute'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['book_id']) || empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'book_id and batch_id are required'));
			return;
		}
		$book_id = (int) $data['book_id'];
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_teacher_or_institute($payload, $batch_id)) {
			return;
		}
		$row = $this->get_book_pdf_for_batch($book_id, $batch_id, true);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Book not found'));
			return;
		}
		$file = isset($row['file_name']) ? $row['file_name'] : '';
		$base_path = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'book' . DIRECTORY_SEPARATOR;
		$file_size_bytes = null;
		$file_size_label = '';
		if ($file !== '' && is_file($base_path . $file)) {
			$file_size_bytes = (int) filesize($base_path . $file);
			if ($file_size_bytes >= 1048576) {
				$file_size_label = round($file_size_bytes / 1048576, 2) . ' MB';
			} elseif ($file_size_bytes >= 1024) {
				$file_size_label = round($file_size_bytes / 1024) . ' KB';
			} else {
				$file_size_label = $file_size_bytes . ' B';
			}
		}
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'id' => (int) $row['id'],
				'batch_id' => $batch_id,
				'title' => isset($row['title']) ? $row['title'] : '',
				'topic' => isset($row['topic']) ? $row['topic'] : '',
				'subject' => isset($row['subject']) ? $row['subject'] : '',
				'fileName' => $file,
				'downloadUrl' => $file !== '' ? base_url('uploads/book/') . $file : '',
				'fileSizeBytes' => $file_size_bytes,
				'fileSize' => $file_size_label,
				'addedAt' => isset($row['added_at']) ? $row['added_at'] : '',
				'addedBy' => isset($row['added_by']) ? (int) $row['added_by'] : 0,
			),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/live-class-list
	 * Live class history list for a student's enrolled batch.
	 * Params: batch_id (required), search, sort_by, sort_dir, page, limit
	 */
	public function live_class_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}

		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'entry_date_time';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'desc';
		if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
			$sort_dir = 'desc';
		}
		$order_map = array(
			'entry_date_time' => 'lch.entry_date_time',
			'date' => 'lch.date',
			'start_time' => 'lch.start_time',
			'end_time' => 'lch.end_time'
		);
		if (!isset($order_map[$sort_by])) {
			$sort_by = 'entry_date_time';
		}

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		// Count query
		$this->db->from('live_class_history lch');
		$this->db->where('lch.batch_id', $batch_id);
		if ($search !== '') {
			$this->db->join('subjects s', 's.id = lch.subject_id', 'left');
			$this->db->join('chapters c', 'c.id = lch.chapter_id', 'left');
			$this->db->join('users u', 'u.id = lch.uid', 'left');
			$this->db->group_start();
			$this->db->like('s.subject_name', $search);
			$this->db->or_like('c.chapter_name', $search);
			$this->db->or_like('u.name', $search);
			$this->db->or_like('lch.start_time', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->select(
			'lch.id as liveClassId,lch.uid as teacherId,lch.batch_id as batchId,lch.subject_id as subjectId,lch.chapter_id as chapterId,' .
			'lch.start_time as startTime,lch.end_time as endTime,lch.date,lch.entry_date_time as entryDateTime,lch.type_class as typeClass,' .
			'u.name as teacherName,u.teach_image as teacherImage,s.subject_name as subjectName,c.chapter_name as chapterName'
		);
		$this->db->from('live_class_history lch');
		$this->db->join('users u', 'u.id = lch.uid', 'left');
		$this->db->join('subjects s', 's.id = lch.subject_id', 'left');
		$this->db->join('chapters c', 'c.id = lch.chapter_id', 'left');
		$this->db->where('lch.batch_id', $batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('s.subject_name', $search);
			$this->db->or_like('c.chapter_name', $search);
			$this->db->or_like('u.name', $search);
			$this->db->or_like('lch.start_time', $search);
			$this->db->group_end();
		}
		$this->db->order_by($order_map[$sort_by], $sort_dir);
		$this->db->limit($limit, $offset);
		$rows = $this->db->get()->result_array();

		$list = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$type = isset($r['typeClass']) ? (int) $r['typeClass'] : 0;
				$r['typeLabel'] = ($type === 1) ? 'Zoom' : (($type === 2) ? 'Jetsi' : '');
				$r['teacherImageUrl'] = !empty($r['teacherImage']) ? profile_image_url($r['teacherImage'], 3, 'teacher') : '';
				$r['isLive'] = (isset($r['endTime']) && (trim((string) $r['endTime']) === '' || $r['endTime'] === '0000-00-00 00:00:00')) ? 1 : 0;
				$list[] = $r;
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'batch_id' => $batch_id,
				'liveClasses' => $list,
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			)
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/live-class-details
	 * Required: live_class_id
	 */
	public function live_class_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['live_class_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'live_class_id is required'));
			return;
		}

		$live_class_id = (int) $data['live_class_id'];
		$this->db->select(
			'lch.id as liveClassId,lch.uid as teacherId,lch.batch_id as batchId,lch.subject_id as subjectId,lch.chapter_id as chapterId,' .
			'lch.start_time as startTime,lch.end_time as endTime,lch.date,lch.entry_date_time as entryDateTime,lch.admin_id as adminId,lch.type_class as typeClass,' .
			'u.name as teacherName,u.teach_image as teacherImage,s.subject_name as subjectName,c.chapter_name as chapterName'
		);
		$this->db->from('live_class_history lch');
		$this->db->join('users u', 'u.id = lch.uid', 'left');
		$this->db->join('subjects s', 's.id = lch.subject_id', 'left');
		$this->db->join('chapters c', 'c.id = lch.chapter_id', 'left');
		$this->db->where('lch.id', $live_class_id);
		$row = $this->db->get()->row_array();

		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Live class not found'));
			return;
		}

		$batch_id = (int) $row['batchId'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$type = isset($row['typeClass']) ? (int) $row['typeClass'] : 0;
		$row['typeLabel'] = ($type === 1) ? 'Zoom' : (($type === 2) ? 'Jetsi' : '');
		$row['teacherImageUrl'] = !empty($row['teacherImage']) ? profile_image_url($row['teacherImage'], 3, 'teacher') : '';
		$row['isLive'] = (isset($row['endTime']) && (trim((string) $row['endTime']) === '' || $row['endTime'] === '0000-00-00 00:00:00')) ? 1 : 0;

		// Attach meeting settings by class type.
		if ($type === 2) {
			$meeting = $this->db_model->select_data('meeting_number as meetingNumber', 'jetsi_setting', array('batch' => $batch_id), 1, array('id', 'desc'));
			$row['meeting'] = array(
				'type' => 'jetsi',
				'meetingNumber' => !empty($meeting[0]['meetingNumber']) ? $meeting[0]['meetingNumber'] : '',
				'password' => ''
			);
		} else {
			$meeting = $this->db_model->select_data(
				'meeting_number as meetingNumber,password',
				'live_class_setting',
				array('batch' => $batch_id, 'status' => 1),
				1,
				array('id', 'desc')
			);
			$row['meeting'] = array(
				'type' => 'zoom',
				'meetingNumber' => !empty($meeting[0]['meetingNumber']) ? $meeting[0]['meetingNumber'] : '',
				'password' => !empty($meeting[0]['password']) ? $meeting[0]['password'] : ''
			);

			$meeting_number = (string) $row['meeting']['meetingNumber'];
			$meeting_pwd = (string) $row['meeting']['password'];
			$zoom_keys = $this->db_model->select_data(
				'zoom_api_key,zoom_api_secret',
				'live_class_setting',
				array('batch' => $batch_id, 'status' => 1),
				1,
				array('id', 'desc')
			);
			$sdk_key = !empty($zoom_keys[0]['zoom_api_key']) ? trim((string) $zoom_keys[0]['zoom_api_key']) : '';
			$sdk_secret = !empty($zoom_keys[0]['zoom_api_secret']) ? trim((string) $zoom_keys[0]['zoom_api_secret']) : '';
			$role = (strtolower(trim((string) $payload['ut'])) === 'teacher') ? 1 : 0;
			$signature = $this->zoom_signature($sdk_key, $sdk_secret, $meeting_number, $role);
			$display_name = $this->zoom_display_name_from_payload($payload);
			$row['meeting']['sdkKey'] = $sdk_key;
			$row['meeting']['signature'] = $signature;
			$row['meeting']['role'] = $role;
			$row['meeting']['displayName'] = $display_name;
			$row['meeting']['webJoinUrl'] = ($meeting_number !== '') ? ('https://zoom.us/wc/join/' . rawurlencode($meeting_number)) : '';
			$row['meeting']['mobileJoinUrl'] = ($meeting_number !== '') ? ('zoomus://zoom.us/join?confno=' . rawurlencode($meeting_number) . ($meeting_pwd !== '' ? ('&pwd=' . rawurlencode($meeting_pwd)) : '')) : '';
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'liveClass' => $row
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/video-lecture-list
	 * Params: batch_id (required), search, sort_by, sort_dir, page, limit
	 */
	public function video_lecture_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'added_at';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'desc';
		if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
			$sort_dir = 'desc';
		}
		$order_map = array(
			'added_at' => 'added_at',
			'date_added' => 'added_at',
			'title' => 'title',
			'topic' => 'topic',
			'subject' => 'subject'
		);
		if (!isset($order_map[$sort_by])) {
			$sort_by = 'added_at';
		}

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$this->db->from('video_lectures');
		$this->db->where('status', 1);
		$this->apply_text_batch_filter('batch', $batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('description', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->select('id,admin_id as adminId,title,batch,topic,subject,description,url,video_type as videoType,preview_type as previewType,added_by as addedBy,added_at as addedAt');
		$this->db->from('video_lectures');
		$this->db->where('status', 1);
		$this->apply_text_batch_filter('batch', $batch_id);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('topic', $search);
			$this->db->or_like('subject', $search);
			$this->db->or_like('description', $search);
			$this->db->group_end();
		}
		$this->db->order_by($order_map[$sort_by], $sort_dir);
		$this->db->limit($limit, $offset);
		$list = $this->db->get()->result_array();

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'batch_id' => $batch_id,
				'videoLectures' => !empty($list) ? $list : array(),
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			)
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/video-lecture-details
	 * Required: video_lecture_id
	 */
	public function video_lecture_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['video_lecture_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'video_lecture_id is required'));
			return;
		}
		$video_id = (int) $data['video_lecture_id'];

		$row = $this->db_model->select_data(
			'id,admin_id as adminId,title,batch,topic,subject,description,url,video_type as videoType,preview_type as previewType,added_by as addedBy,added_at as addedAt,status',
			'video_lectures use index (id)',
			array('id' => $video_id, 'status' => 1),
			1
		);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Video lecture not found'));
			return;
		}
		$video = $row[0];

		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];
		if ($ut === 'student') {
			if ($uid < 1 || $this->authorize_student_request($uid) === false) {
				return;
			}
			// Validate student enrollment with at least one batch mapped in this lecture.
			$student = $this->db_model->select_data('batch_id', 'students use index (id)', array('id' => $uid), 1);
			$student_batch_id = !empty($student[0]['batch_id']) ? (int) $student[0]['batch_id'] : 0;
			if ($student_batch_id < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not enrolled in any batch'));
				return;
			}

			$this->db->from('video_lectures');
			$this->db->where('id', $video_id);
			$this->apply_text_batch_filter('batch', $student_batch_id);
			$allowed = (int) $this->db->count_all_results();
			if ($allowed < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not allowed to access this video'));
				return;
			}
		} elseif ($ut === 'teacher') {
			if ($uid < 1) {
				echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
				return;
			}
			if (!$this->video_accessible_to_teacher($video_id, $uid)) {
				echo json_encode(array('status' => 'false', 'msg' => 'You are not allowed to access this video'));
				return;
			}
		} else {
			echo json_encode(array('status' => 'false', 'msg' => 'This action is available for student and teacher only'));
			return;
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'videoLecture' => $video
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/upcoming-exam-list
	 * Params: batch_id (required), search, sort_by, sort_dir, page, limit
	 */
	public function upcoming_exam_list()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['batch_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id is required'));
			return;
		}
		$batch_id = (int) $data['batch_id'];
		if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
			return;
		}

		$search = isset($data['search']) ? trim($data['search']) : '';
		$sort_by = isset($data['sort_by']) ? strtolower(trim($data['sort_by'])) : 'mock_sheduled_date';
		$sort_dir = isset($data['sort_dir']) ? strtolower(trim($data['sort_dir'])) : 'asc';
		if ($sort_dir !== 'asc' && $sort_dir !== 'desc') {
			$sort_dir = 'asc';
		}
		$order_map = array(
			'mock_sheduled_date' => 'mock_sheduled_date',
			'mock_sheduled_time' => 'mock_sheduled_time',
			'added_at' => 'added_at',
			'name' => 'name',
			'time_duration' => 'time_duration'
		);
		if (!isset($order_map[$sort_by])) {
			$sort_by = 'mock_sheduled_date';
		}

		$pg = $this->parse_api_list_pagination($data);
		$page = $pg['page'];
		$limit = $pg['limit'];
		$offset = $pg['offset'];

		$cond = array(
			'batch_id' => $batch_id,
			'status' => 1,
			'type' => 1,
			'mock_sheduled_date >=' => date('Y-m-d')
		);

		$this->db->from('exams');
		$this->db->where($cond);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('name', $search);
			$this->db->or_like('total_question', $search);
			$this->db->or_like('time_duration', $search);
			$this->db->group_end();
		}
		$total = (int) $this->db->count_all_results();

		$this->db->select('id,admin_id as adminId,name,type,format,batch_id as batchId,total_question as totalQuestion,time_duration as timeDuration,mock_sheduled_date as scheduledDate,mock_sheduled_time as scheduledTime,total_marks as totalMarks,marking_parcent as markingPercent,added_by as addedBy,added_at as addedAt');
		$this->db->from('exams');
		$this->db->where($cond);
		if ($search !== '') {
			$this->db->group_start();
			$this->db->like('name', $search);
			$this->db->or_like('total_question', $search);
			$this->db->or_like('time_duration', $search);
			$this->db->group_end();
		}
		$this->db->order_by($order_map[$sort_by], $sort_dir);
		$this->db->limit($limit, $offset);
		$list = $this->db->get()->result_array();

		if (!empty($list)) {
			foreach ($list as $k => $v) {
				$list[$k]['completeBy'] = trim($v['scheduledTime'] . ', ' . date('M d, Y', strtotime($v['scheduledDate'])));
				$list[$k]['examTypeLabel'] = ((int) $v['type'] === 1) ? 'mock' : 'practice';
			}
		}

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'data' => array(
				'batch_id' => $batch_id,
				'upcomingExams' => !empty($list) ? $list : array(),
				'pagination' => $this->build_api_list_pagination_meta($page, $limit, $total),
			)
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST/GET api/batch/upcoming-exam-details
	 * Required: exam_id
	 */
	public function upcoming_exam_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array(), $data);
		if ($payload === false) {
			return;
		}

		if (empty($data['exam_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'exam_id is required'));
			return;
		}
		$exam_id = (int) $data['exam_id'];

		$exam = $this->db_model->select_data(
			'id,admin_id as adminId,name,type,format,batch_id as batchId,total_question as totalQuestion,time_duration as timeDuration,mock_sheduled_date as scheduledDate,mock_sheduled_time as scheduledTime,total_marks as totalMarks,marking_parcent as markingPercent,question_ids as questionIds,status,added_by as addedBy,added_at as addedAt',
			'exams use index (id)',
			array('id' => $exam_id, 'status' => 1, 'type' => 1),
			1
		);
		if (empty($exam)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Exam not found'));
			return;
		}
		$e = $exam[0];

		if (!$this->assert_batch_access_student_or_teacher($payload, (int) $e['batchId'])) {
			return;
		}

		$e['completeBy'] = trim($e['scheduledTime'] . ', ' . date('M d, Y', strtotime($e['scheduledDate'])));
		$e['examTypeLabel'] = ((int) $e['type'] === 1) ? 'mock' : 'practice';
		$e['formatLabel'] = ((int) $e['format'] === 1) ? 'Shuffle' : (((int) $e['format'] === 2) ? 'Fix' : '');

		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'exam' => $e
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * Teacher must be assigned to this batch for this subject (batch_subjects).
	 */
	private function assert_teacher_batch_subject($teacher_id, $batch_id, $subject_id)
	{
		$teacher_id = (int) $teacher_id;
		$batch_id = (int) $batch_id;
		$subject_id = (int) $subject_id;
		if ($teacher_id < 1 || $batch_id < 1 || $subject_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid batch or subject'));
			return false;
		}
		$row = $this->db_model->select_data(
			'id',
			'batch_subjects',
			array('teacher_id' => $teacher_id, 'batch_id' => $batch_id, 'subject_id' => $subject_id),
			1
		);
		if (empty($row)) {
			echo json_encode(array('status' => 'false', 'msg' => 'You are not assigned to this subject for this batch'));
			return false;
		}
		return true;
	}

	/**
	 * Same behaviour as {@see Home::Homework()}: student + teacher, batch_id/admin_id resolution, joins, homeWork response.
	 * POST/GET api/batch/homework-list
	 */
	public function homework_list()
	{
		$this->ensure_homework_attachment_column();
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('student', 'teacher'), $data);
		if ($payload === false) {
			return;
		}

		$batch_ids = array();
		if (!empty($data['batch_id'])) {
			$batch_ids = array((int) $data['batch_id']);
		} else {
			if ($payload['ut'] === 'student') {
				$rows = $this->db_model->select_data('batch_id', 'student_batchs', array('student_id' => (int) $payload['uid'], 'status' => 1), '');
				if (!empty($rows)) {
					foreach ($rows as $r) {
						$bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
						if ($bid > 0) {
							$batch_ids[] = $bid;
						}
					}
				}
				if (empty($batch_ids)) {
					$student_row = $this->db_model->select_data('id,batch_id,admin_id', 'students use index (id)', array('id' => (int) $payload['uid']), 1);
					if (!empty($student_row[0]['batch_id'])) {
						$batch_ids[] = (int) $student_row[0]['batch_id'];
					}
				}
			} elseif ($payload['ut'] === 'teacher') {
				$rows = $this->db_model->select_data('batch_id', 'batch_subjects', array('teacher_id' => (int) $payload['uid']), '');
				if (!empty($rows)) {
					foreach ($rows as $r) {
						$bid = isset($r['batch_id']) ? (int) $r['batch_id'] : 0;
						if ($bid > 0) {
							$batch_ids[] = $bid;
						}
					}
				}
			}
			$batch_ids = array_values(array_unique(array_filter($batch_ids)));
		}

		$pg = $this->parse_api_list_pagination($data);
		$pagination_empty = $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], 0);

		if (empty($batch_ids)) {
			echo json_encode(array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_no_record_msg'),
				'homeWork' => array(),
				'pagination' => $pagination_empty,
			), JSON_UNESCAPED_SLASHES);
			die;
		}

		if ($payload['ut'] === 'student') {
			$this->mark_homework_notification_viewed((int) $payload['uid']);
		}

		$this->db->select('homeworks.id,homeworks.admin_id as adminId,homeworks.teacher_id as teacherId,homeworks.date,homeworks.subject_id as subjectId,homeworks.batch_id as batchId,homeworks.description,homeworks.attachment,homeworks.added_at as addedAt,users.name,users.teach_gender as teachGender,subjects.subject_name as subjectName');
		$this->db->from('homeworks');
		$this->db->join('users', 'users.id = homeworks.teacher_id', 'left');
		$this->db->join('subjects', 'subjects.id = homeworks.subject_id', 'left');
		$this->db->where_in('homeworks.batch_id', $batch_ids);
		if ($payload['ut'] === 'teacher') {
			$this->db->where('homeworks.teacher_id', (int) $payload['uid']);
		}
		if (!empty($data['admin_id'])) {
			$this->db->where('homeworks.admin_id', (int) $data['admin_id']);
		}
		if (!empty($data['date'])) {
			$dts = strtotime((string) $data['date']);
			if ($dts !== false) {
				$this->db->where('homeworks.date', date('Y-m-d', $dts));
			}
		}

		$count_db = clone $this->db;
		$total = (int) $count_db->count_all_results();

		$this->db->order_by('homeworks.id', 'desc');
		$this->db->limit($pg['limit'], $pg['offset']);
		$homewrkData = $this->db->get()->result_array();
		if (!empty($homewrkData)) {
			foreach ($homewrkData as $idx => $row) {
				$fn = isset($row['attachment']) ? trim((string) $row['attachment']) : '';
				$homewrkData[$idx]['attachmentUrl'] = $fn !== '' ? base_url('uploads/homework_teacher/') . $fn : '';
			}
		}

		$pagination = $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total);
		if (!empty($homewrkData)) {
			$arr = array(
				'homeWork' => $homewrkData,
				'status' => 'true',
				'msg' => $this->lang->line('ltr_fetch_successfully'),
				'pagination' => $pagination,
			);
		} else {
			$arr = array(
				'status' => 'false',
				'msg' => $this->lang->line('ltr_no_record_msg'),
				'homeWork' => array(),
				'pagination' => $pagination,
			);
		}
		echo json_encode($arr, JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * Mirrors {@see Home::viewNotificationStatus()} for notice_type homeWork (student app).
	 */
	private function mark_homework_notification_viewed($student_id)
	{
		$student_id = (int) $student_id;
		if ($student_id < 1) {
			return;
		}
		$notice_type = 'homeWork';
		$cu_date = date('Y-m-d H:i:s');
		$noticeD = $this->db_model->select_data('*', 'views_notification_student', array('student_id' => $student_id, 'notice_type' => $notice_type), 1);
		if (!empty($noticeD)) {
			$this->db_model->update_data_limit('views_notification_student ', array('views_time' => $cu_date), array('n_id' => $noticeD[0]['n_id']), 1);
		} else {
			$data_arr = $this->security->xss_clean(array(
				'student_id' => $student_id,
				'notice_type' => $notice_type,
			));
			$this->db_model->insert_data('views_notification_student', $data_arr);
		}
	}

	private function ensure_homework_submissions_table()
	{
		$sql = "CREATE TABLE IF NOT EXISTS `homework_submissions` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`homework_id` int(11) NOT NULL,
			`admin_id` int(11) NOT NULL DEFAULT 0,
			`teacher_id` int(11) NOT NULL DEFAULT 0,
			`student_id` int(11) NOT NULL,
			`batch_id` int(11) NOT NULL DEFAULT 0,
			`subject_id` int(11) NOT NULL DEFAULT 0,
			`submission_text` text,
			`attachment` varchar(255) NOT NULL DEFAULT '',
			`marks` decimal(10,2) DEFAULT NULL,
			`remark` text,
			`eval_status` tinyint(1) NOT NULL DEFAULT 0,
			`submitted_at` datetime NOT NULL,
			`evaluated_at` datetime DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_homework_id` (`homework_id`),
			KEY `idx_student_id` (`student_id`),
			KEY `idx_teacher_id` (`teacher_id`),
			KEY `idx_eval_status` (`eval_status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
		$this->db->query($sql);
	}

	/** @var bool */
	private $homework_attachment_column_checked = false;

	/**
	 * Adds homeworks.attachment when missing (teacher assignment PDF).
	 */
	private function ensure_homework_attachment_column()
	{
		if ($this->homework_attachment_column_checked) {
			return;
		}
		$this->homework_attachment_column_checked = true;
		if ($this->db->field_exists('attachment', 'homeworks')) {
			return;
		}
		$sql = 'ALTER TABLE `homeworks` ADD COLUMN `attachment` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `description`';
		@$this->db->query($sql);
	}

	/**
	 * Upload teacher homework handout (field name pdf_file). Returns [ok, filename_or_error_message].
	 *
	 * @return array
	 */
	private function upload_homework_teacher_pdf()
	{
		$path = './uploads/homework_teacher/';
		if (!is_dir($path)) {
			@mkdir($path, 0777, true);
		}
		$config = array(
			'upload_path' => $path,
			'allowed_types' => 'pdf',
			'max_size' => 15360,
		);
		if (!isset($this->upload)) {
			$this->load->library('upload', $config);
		} else {
			$this->upload->initialize($config);
		}
		if (!$this->upload->do_upload('pdf_file')) {
			return array(false, strip_tags($this->upload->display_errors('', '')));
		}
		$uploaddata = $this->upload->data();
		$pic = isset($uploaddata['raw_name']) ? (string) $uploaddata['raw_name'] : '';
		$pic_ext = isset($uploaddata['file_ext']) ? (string) $uploaddata['file_ext'] : '';
		$image = $pic . date('ymdHis') . $pic_ext;
		$old_path = $path . $pic . $pic_ext;
		$new_path = $path . $image;
		if (is_file($old_path)) {
			@rename($old_path, $new_path);
		} else {
			$image = isset($uploaddata['file_name']) ? (string) $uploaddata['file_name'] : '';
		}
		return array(true, $image);
	}

	/**
	 * POST api/batch/homework-add
	 * Auth: teacher only. Required: batch_id, subject_id, date.
	 * Body: JSON or multipart. description text and/or pdf_file (PDF). At least one of description or pdf_file is required.
	 */
	public function homework_add()
	{
		$this->ensure_homework_attachment_column();
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher'), $data);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if ($teacher_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Teacher not found'));
			return;
		}
		if (empty($data['batch_id']) || empty($data['subject_id']) || empty($data['date'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'batch_id, subject_id, and date are required'));
			return;
		}
		$batch_id = (int) $data['batch_id'];
		$subject_id = (int) $data['subject_id'];
		if (!$this->assert_teacher_batch_subject($teacher_id, $batch_id, $subject_id)) {
			return;
		}
		$admin_id = $this->teacher_tenant_admin_id($teacher_id);
		if ($admin_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Could not resolve admin for this teacher account'));
			return;
		}
		$date_ts = strtotime($data['date']);
		if ($date_ts === false) {
			echo json_encode(array('status' => 'false', 'msg' => 'Invalid date'));
			return;
		}
		$date = date('Y-m-d', $date_ts);
		$desc = isset($data['description']) ? trim((string) $data['description']) : '';
		$file_name = '';
		if (!empty($_FILES['pdf_file']['name'])) {
			list($ok, $res) = $this->upload_homework_teacher_pdf();
			if (!$ok) {
				echo json_encode(array('status' => 'false', 'msg' => $res));
				return;
			}
			$file_name = $res;
		}
		if ($desc === '' && $file_name === '') {
			echo json_encode(array('status' => 'false', 'msg' => 'Enter a description and/or upload a PDF'));
			return;
		}
		if ($desc === '') {
			$desc = 'See attached PDF.';
		}
		$insert = $this->security->xss_clean(array(
			'admin_id' => $admin_id,
			'teacher_id' => $teacher_id,
			'date' => $date,
			'subject_id' => $subject_id,
			'batch_id' => (string) $batch_id,
			'description' => $desc,
			'attachment' => $file_name,
			'added_at' => date('Y-m-d H:i:s'),
		));
		$new_id = $this->db_model->insert_data('homeworks', $insert);
		if (empty($new_id)) {
			if ($file_name !== '') {
				$fp = './uploads/homework_teacher/' . $file_name;
				if (is_file($fp)) {
					@unlink($fp);
				}
			}
			echo json_encode(array('status' => 'false', 'msg' => 'Failed to add homework'));
			return;
		}
		echo json_encode(array(
			'status' => 'true',
			'message' => 'Success',
			'msg' => $this->lang->line('ltr_homework_added_msg'),
			'data' => array(
				'id' => (int) $new_id,
				'batchId' => $batch_id,
				'subjectId' => $subject_id,
				'date' => $date,
				'attachment' => $file_name,
				'attachmentUrl' => $file_name !== '' ? base_url('uploads/homework_teacher/') . $file_name : '',
			),
		), JSON_UNESCAPED_SLASHES);
		die;
	}

	/**
	 * POST api/batch/homework-edit
	 * Auth: teacher only. Required: homework_id. Optional: batch_id, subject_id, date, description (must still teach batch+subject if changed).
	 */
	public function homework_edit()
	{
		$this->ensure_homework_attachment_column();
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher'), $data);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if (empty($data['homework_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'homework_id is required'));
			return;
		}
		$hid = (int) $data['homework_id'];
		$existing = $this->db_model->select_data('*', 'homeworks use index (id)', array('id' => $hid, 'teacher_id' => $teacher_id), 1);
		if (empty($existing)) {
			echo json_encode(array('status' => 'false', 'msg' => 'Homework not found'));
			return;
		}
		$batch_id = isset($data['batch_id']) ? (int) $data['batch_id'] : (int) $existing[0]['batch_id'];
		$subject_id = isset($data['subject_id']) ? (int) $data['subject_id'] : (int) $existing[0]['subject_id'];
		if (!$this->assert_teacher_batch_subject($teacher_id, $batch_id, $subject_id)) {
			return;
		}
		$admin_id = $this->teacher_tenant_admin_id($teacher_id);
		if ($admin_id < 1) {
			echo json_encode(array('status' => 'false', 'msg' => 'Could not resolve admin for this teacher account'));
			return;
		}
		$update = array(
			'admin_id' => $admin_id,
			'batch_id' => (string) $batch_id,
			'subject_id' => $subject_id,
		);
		if (isset($data['date']) && $data['date'] !== '') {
			$dts = strtotime($data['date']);
			if ($dts !== false) {
				$update['date'] = date('Y-m-d', $dts);
			}
		}
		$old_attachment = isset($existing[0]['attachment']) ? trim((string) $existing[0]['attachment']) : '';
		if (!empty($_FILES['pdf_file']['name'])) {
			list($ok, $res) = $this->upload_homework_teacher_pdf();
			if (!$ok) {
				echo json_encode(array('status' => 'false', 'msg' => $res));
				return;
			}
			$update['attachment'] = $res;
			if ($old_attachment !== '' && $old_attachment !== $res) {
				$p = './uploads/homework_teacher/' . $old_attachment;
				if (is_file($p)) {
					@unlink($p);
				}
			}
		}
		if (isset($data['description'])) {
			$d = trim((string) $data['description']);
			if ($d === '') {
				$has_file = !empty($update['attachment']) || $old_attachment !== '';
				if (!$has_file) {
					echo json_encode(array('status' => 'false', 'msg' => 'description cannot be empty unless a PDF is attached'));
					return;
				}
				$update['description'] = 'See attached PDF.';
			} else {
				$update['description'] = $d;
			}
		}
		$update['added_at'] = date('Y-m-d H:i:s');
		$update = $this->security->xss_clean($update);
		$this->db->where(array('id' => $hid, 'teacher_id' => $teacher_id));
		$this->db->limit(1);
		$this->db->update('homeworks', $update);
		if ($this->db->affected_rows() < 1) {
			$this->api_json(false, 'No changes applied or homework not found.');
			return;
		}
		$this->api_json(true, $this->lang->line('ltr_homework_updated_msg'), array('id' => $hid));
		die;
	}

	/**
	 * POST/GET api/batch/homework-delete
	 * Auth: teacher only. Required: homework_id.
	 */
	public function homework_delete()
	{
		$this->ensure_homework_attachment_column();
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher'), $data);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if (empty($data['homework_id'])) {
			echo json_encode(array('status' => 'false', 'msg' => 'homework_id is required'));
			return;
		}
		$hid = (int) $data['homework_id'];
		$existing = $this->db_model->select_data('id,attachment', 'homeworks use index (id)', array('id' => $hid, 'teacher_id' => $teacher_id), 1);
		if (empty($existing)) {
			$this->api_json(false, 'Homework not found');
			return;
		}
		$att = isset($existing[0]['attachment']) ? trim((string) $existing[0]['attachment']) : '';
		if ($att !== '') {
			$p = './uploads/homework_teacher/' . $att;
			if (is_file($p)) {
				@unlink($p);
			}
		}
		$this->db_model->delete_data('homeworks', array('id' => $hid, 'teacher_id' => $teacher_id), 1);
		$this->api_json(true, 'Homework deleted', array('id' => $hid));
		die;
	}

	/**
	 * POST/GET api/batch/homework-details
	 * Auth: student | teacher. Required: homework_id.
	 */
	public function homework_details()
	{
		$this->ensure_homework_attachment_column();
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('student', 'teacher'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['homework_id'])) {
			$this->api_json(false, 'homework_id is required');
			return;
		}
		$hid = (int) $data['homework_id'];
		if ($hid < 1) {
			$this->api_json(false, 'Invalid homework_id');
			return;
		}

		$this->db->select('homeworks.id,homeworks.admin_id as adminId,homeworks.teacher_id as teacherId,homeworks.subject_id as subjectId,homeworks.batch_id as batchId,homeworks.date,homeworks.description,homeworks.attachment,homeworks.added_at as addedAt,users.name as teacherName,users.teach_gender as teachGender,subjects.subject_name as subjectName');
		$this->db->from('homeworks');
		$this->db->join('users', 'users.id = homeworks.teacher_id', 'left');
		$this->db->join('subjects', 'subjects.id = homeworks.subject_id', 'left');
		$this->db->where('homeworks.id', $hid);
		$row = $this->db->get()->row_array();
		if (empty($row)) {
			$this->api_json(false, 'Homework not found');
			return;
		}

		$batch_id = (int) $row['batchId'];
		if ($payload['ut'] === 'teacher') {
			if ((int) $payload['uid'] !== (int) $row['teacherId']) {
				$this->api_json(false, 'You are not allowed to access this homework');
				return;
			}
		} else {
			if (!$this->assert_batch_access_student_or_teacher($payload, $batch_id)) {
				return;
			}
		}

		$att = isset($row['attachment']) ? trim((string) $row['attachment']) : '';
		$row['attachmentUrl'] = $att !== '' ? base_url('uploads/homework_teacher/') . $att : '';

		$this->api_json(true, 'Success', array('homework' => $row));
	}

	/**
	 * POST api/batch/homework-submit
	 * Auth: student only.
	 * Required: homework_id. One submission per student per homework (update on re-submit).
	 * Optional: submission_text, submission_file (multipart).
	 */
	public function homework_submit()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('student'), $data);
		if ($payload === false) {
			return;
		}
		$student_id = (int) $payload['uid'];
		if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
			return;
		}
		if (empty($data['homework_id'])) {
			$this->api_json(false, 'homework_id is required');
			return;
		}
		$homework_id = (int) $data['homework_id'];
		$submission_text = isset($data['submission_text']) ? trim((string) $data['submission_text']) : '';

		$hw = $this->db_model->select_data('*', 'homeworks use index (id)', array('id' => $homework_id), 1);
		if (empty($hw)) {
			$this->api_json(false, 'Homework not found');
			return;
		}
		$hw_row = $hw[0];
		if (!$this->assert_batch_access_student_or_teacher($payload, (int) $hw_row['batch_id'])) {
			return;
		}

		$file_name = '';
		if (!empty($_FILES['submission_file']['name'])) {
			$config['upload_path'] = './uploads/homework_submission/';
			if (!is_dir($config['upload_path'])) {
				@mkdir($config['upload_path'], 0777, true);
			}
			$config['allowed_types'] = '*';
			$config['max_size'] = '0';
			$this->load->library('upload', $config);
			if (!$this->upload->do_upload('submission_file')) {
				$this->api_json(false, strip_tags($this->upload->display_errors('', '')));
				return;
			}
			$uploaddata = $this->upload->data();
			$raw = isset($uploaddata['raw_name']) ? (string) $uploaddata['raw_name'] : '';
			$ext = isset($uploaddata['file_ext']) ? (string) $uploaddata['file_ext'] : '';
			$file_name = $raw . date('ymdHis') . $ext;
			$old_path = './uploads/homework_submission/' . $raw . $ext;
			$new_path = './uploads/homework_submission/' . $file_name;
			if (is_file($old_path)) {
				@rename($old_path, $new_path);
			} else {
				$file_name = isset($uploaddata['file_name']) ? (string) $uploaddata['file_name'] : '';
			}
		}

		if ($submission_text === '' && $file_name === '') {
			$this->api_json(false, 'submission_text or submission_file is required');
			return;
		}

		$this->ensure_homework_submissions_table();
		$existing = $this->db_model->select_data(
			'id,attachment',
			'homework_submissions use index (id)',
			array('homework_id' => $homework_id, 'student_id' => $student_id),
			1
		);
		$now = date('Y-m-d H:i:s');
		if (!empty($existing)) {
			$update = array(
				'submission_text' => $submission_text,
				'submitted_at' => $now,
				'eval_status' => 0,
				'marks' => null,
				'remark' => null,
				'evaluated_at' => null,
			);
			if ($file_name !== '') {
				$update['attachment'] = $file_name;
			}
			$update = $this->security->xss_clean($update);
			$this->db_model->update_data_limit('homework_submissions', $update, array('id' => (int) $existing[0]['id']), 1);
			$submission_id = (int) $existing[0]['id'];
		} else {
			$insert = $this->security->xss_clean(array(
				'homework_id' => $homework_id,
				'admin_id' => (int) $hw_row['admin_id'],
				'teacher_id' => (int) $hw_row['teacher_id'],
				'student_id' => $student_id,
				'batch_id' => (int) $hw_row['batch_id'],
				'subject_id' => (int) $hw_row['subject_id'],
				'submission_text' => $submission_text,
				'attachment' => $file_name,
				'submitted_at' => $now,
			));
			$submission_id = (int) $this->db_model->insert_data('homework_submissions', $insert);
		}

		$this->api_json(true, 'Homework submitted successfully', array(
			'id' => $submission_id,
			'homework_id' => $homework_id,
			'student_id' => $student_id,
			'submission_text' => $submission_text,
			'attachment' => $file_name,
			'attachment_url' => $file_name !== '' ? base_url('uploads/homework_submission/') . $file_name : '',
		));
	}

	/**
	 * POST/GET api/batch/homework-submissions
	 * Auth: teacher only.
	 * Required: homework_id
	 */
	public function homework_submissions()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher'), $data);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if (empty($data['homework_id'])) {
			$this->api_json(false, 'homework_id is required');
			return;
		}
		$homework_id = (int) $data['homework_id'];
		$hw = $this->db_model->select_data('id,batch_id,teacher_id', 'homeworks use index (id)', array('id' => $homework_id), 1);
		if (empty($hw)) {
			$this->api_json(false, 'Homework not found');
			return;
		}
		if ((int) $hw[0]['teacher_id'] !== $teacher_id) {
			$this->api_json(false, 'You are not allowed to access submissions of this homework');
			return;
		}

		$this->ensure_homework_submissions_table();
		$pg = $this->parse_api_list_pagination($data);

		$this->db->from('homework_submissions hs');
		$this->db->where('hs.homework_id', $homework_id);
		$total = (int) $this->db->count_all_results();

		$this->db->select('hs.id,hs.homework_id as homeworkId,hs.student_id as studentId,hs.submission_text as submissionText,hs.attachment,hs.marks,hs.remark,hs.eval_status as evalStatus,hs.submitted_at as submittedAt,hs.evaluated_at as evaluatedAt,s.name as studentName,s.image as studentImage');
		$this->db->from('homework_submissions hs');
		$this->db->join('students s', 's.id = hs.student_id', 'left');
		$this->db->where('hs.homework_id', $homework_id);
		$this->db->order_by('hs.id', 'desc');
		$this->db->limit($pg['limit'], $pg['offset']);
		$rows = $this->db->get()->result_array();

		$list = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$file = isset($r['attachment']) ? (string) $r['attachment'] : '';
				$r['attachmentUrl'] = $file !== '' ? base_url('uploads/homework_submission/') . $file : '';
				$r['studentImageUrl'] = !empty($r['studentImage']) ? profile_image_url($r['studentImage'], 2, 'student') : '';
				$list[] = $r;
			}
		}

		$this->api_json(true, 'Success', array(
			'homework_id' => $homework_id,
			'submissions' => $list,
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
		));
	}

	/**
	 * POST api/batch/homework-evaluate
	 * Auth: teacher only.
	 * Required: submission_id
	 * Optional: marks, remark, eval_status (0|1)
	 */
	public function homework_evaluate()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('teacher'), $data);
		if ($payload === false) {
			return;
		}
		$teacher_id = (int) $payload['uid'];
		if (empty($data['submission_id'])) {
			$this->api_json(false, 'submission_id is required');
			return;
		}
		$submission_id = (int) $data['submission_id'];
		$this->ensure_homework_submissions_table();

		$this->db->select('hs.id,hs.homework_id,hw.teacher_id');
		$this->db->from('homework_submissions hs');
		$this->db->join('homeworks hw', 'hw.id = hs.homework_id', 'inner');
		$this->db->where('hs.id', $submission_id);
		$row = $this->db->get()->row_array();
		if (empty($row)) {
			$this->api_json(false, 'Submission not found');
			return;
		}
		if ((int) $row['teacher_id'] !== $teacher_id) {
			$this->api_json(false, 'You are not allowed to evaluate this submission');
			return;
		}

		$update = array();
		$max_marks = null;
		if ($this->db->field_exists('max_marks', 'homeworks')) {
			$hw_meta = $this->db_model->select_data('id,max_marks', 'homeworks use index (id)', array('id' => (int) $row['homework_id']), 1);
			if (!empty($hw_meta) && isset($hw_meta[0]['max_marks']) && $hw_meta[0]['max_marks'] !== '') {
				$max_marks = (float) $hw_meta[0]['max_marks'];
			}
		}
		if (isset($data['marks']) && $data['marks'] !== '') {
			$marks = (float) $data['marks'];
			if ($max_marks !== null && $marks > $max_marks) {
				$this->api_json(false, 'Marks cannot be greater than max_marks (' . $max_marks . ')');
				return;
			}
			$update['marks'] = $marks;
		}
		if (isset($data['remark'])) {
			$update['remark'] = trim((string) $data['remark']);
		}
		$eval_status = 1;
		if (isset($data['eval_status'])) {
			$eval_status = ((int) $data['eval_status'] === 1) ? 1 : 0;
		}
		$update['eval_status'] = $eval_status;
		$update['evaluated_at'] = date('Y-m-d H:i:s');
		$update = $this->security->xss_clean($update);
		$this->db_model->update_data_limit('homework_submissions', $update, array('id' => $submission_id), 1);

		$this->api_json(true, 'Submission evaluated successfully', array(
			'submission_id' => $submission_id,
			'homework_id' => (int) $row['homework_id'],
			'eval_status' => $eval_status,
		));
	}

	/**
	 * POST/GET api/batch/my-homework-submissions
	 * Auth: student only.
	 * Optional: homework_id, batch_id, eval_status, page, limit
	 */
	public function my_homework_submissions()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('student'), $data);
		if ($payload === false) {
			return;
		}
		$student_id = (int) $payload['uid'];
		if ($student_id < 1 || $this->authorize_student_request($student_id) === false) {
			return;
		}
		$this->ensure_homework_submissions_table();
		$pg = $this->parse_api_list_pagination($data);

		$this->db->from('homework_submissions hs');
		$this->db->where('hs.student_id', $student_id);
		if (!empty($data['homework_id'])) {
			$this->db->where('hs.homework_id', (int) $data['homework_id']);
		}
		if (!empty($data['batch_id'])) {
			$this->db->where('hs.batch_id', (int) $data['batch_id']);
		}
		if (isset($data['eval_status']) && $data['eval_status'] !== '') {
			$this->db->where('hs.eval_status', ((int) $data['eval_status'] === 1) ? 1 : 0);
		}
		$total = (int) $this->db->count_all_results();

		$this->db->select('hs.id,hs.homework_id as homeworkId,hs.batch_id as batchId,hs.subject_id as subjectId,hs.submission_text as submissionText,hs.attachment,hs.marks,hs.remark,hs.eval_status as evalStatus,hs.submitted_at as submittedAt,hs.evaluated_at as evaluatedAt,hw.date as homeworkDate,hw.description as homeworkDescription,su.subject_name as subjectName,u.name as teacherName');
		$this->db->from('homework_submissions hs');
		$this->db->join('homeworks hw', 'hw.id = hs.homework_id', 'left');
		$this->db->join('subjects su', 'su.id = hs.subject_id', 'left');
		$this->db->join('users u', 'u.id = hs.teacher_id', 'left');
		$this->db->where('hs.student_id', $student_id);
		if (!empty($data['homework_id'])) {
			$this->db->where('hs.homework_id', (int) $data['homework_id']);
		}
		if (!empty($data['batch_id'])) {
			$this->db->where('hs.batch_id', (int) $data['batch_id']);
		}
		if (isset($data['eval_status']) && $data['eval_status'] !== '') {
			$this->db->where('hs.eval_status', ((int) $data['eval_status'] === 1) ? 1 : 0);
		}
		$this->db->order_by('hs.id', 'desc');
		$this->db->limit($pg['limit'], $pg['offset']);
		$rows = $this->db->get()->result_array();

		$list = array();
		if (!empty($rows)) {
			foreach ($rows as $r) {
				$file = isset($r['attachment']) ? (string) $r['attachment'] : '';
				$r['attachmentUrl'] = $file !== '' ? base_url('uploads/homework_submission/') . $file : '';
				$list[] = $r;
			}
		}

		$this->api_json(true, 'Success', array(
			'student_id' => $student_id,
			'submissions' => $list,
			'pagination' => $this->build_api_list_pagination_meta($pg['page'], $pg['limit'], $total),
		));
	}

	/**
	 * POST/GET api/batch/homework-submission-details
	 * Auth: student | teacher.
	 * Required: submission_id
	 * - student: can only view own submission
	 * - teacher: can only view submissions for own homework
	 */
	public function homework_submission_details()
	{
		$data = $this->read_request_data();
		$payload = $this->require_auth_payload(array('student', 'teacher'), $data);
		if ($payload === false) {
			return;
		}
		if (empty($data['submission_id'])) {
			$this->api_json(false, 'submission_id is required');
			return;
		}
		$submission_id = (int) $data['submission_id'];
		if ($submission_id < 1) {
			$this->api_json(false, 'Invalid submission_id');
			return;
		}

		$this->ensure_homework_submissions_table();
		$this->db->select('hs.id,hs.homework_id as homeworkId,hs.admin_id as adminId,hs.teacher_id as teacherId,hs.student_id as studentId,hs.batch_id as batchId,hs.subject_id as subjectId,hs.submission_text as submissionText,hs.attachment,hs.marks,hs.remark,hs.eval_status as evalStatus,hs.submitted_at as submittedAt,hs.evaluated_at as evaluatedAt,hw.date as homeworkDate,hw.description as homeworkDescription,su.subject_name as subjectName,st.name as studentName,st.image as studentImage,u.name as teacherName,u.teach_image as teacherImage');
		$this->db->from('homework_submissions hs');
		$this->db->join('homeworks hw', 'hw.id = hs.homework_id', 'left');
		$this->db->join('subjects su', 'su.id = hs.subject_id', 'left');
		$this->db->join('students st', 'st.id = hs.student_id', 'left');
		$this->db->join('users u', 'u.id = hs.teacher_id', 'left');
		$this->db->where('hs.id', $submission_id);
		$row = $this->db->get()->row_array();
		if (empty($row)) {
			$this->api_json(false, 'Submission not found');
			return;
		}

		$ut = strtolower(trim((string) $payload['ut']));
		$uid = (int) $payload['uid'];
		if ($ut === 'student') {
			if ($uid < 1 || $this->authorize_student_request($uid) === false) {
				return;
			}
			if ((int) $row['studentId'] !== $uid) {
				$this->api_json(false, 'You are not allowed to view this submission');
				return;
			}
		} elseif ($ut === 'teacher') {
			if ((int) $row['teacherId'] !== $uid) {
				$this->api_json(false, 'You are not allowed to view this submission');
				return;
			}
		} else {
			$this->api_json(false, 'This endpoint is available for student and teacher only');
			return;
		}

		$file = isset($row['attachment']) ? (string) $row['attachment'] : '';
		$row['attachmentUrl'] = $file !== '' ? base_url('uploads/homework_submission/') . $file : '';
		$row['studentImageUrl'] = !empty($row['studentImage']) ? profile_image_url($row['studentImage'], 2, 'student') : '';
		$row['teacherImageUrl'] = !empty($row['teacherImage']) ? profile_image_url($row['teacherImage'], 3, 'teacher') : '';

		$this->api_json(true, 'Success', array('submission' => $row));
	}
}
